<?php

namespace App\Handlers;

use App\Core\{Config, Database, Helpers, Keyboard, Logger, StateManager, TelegramBot};
use App\Features\{FoodDiary, FoodSearch, UserProfile, WaterTracker, WeightTracker};

class MessageHandler
{
    private TelegramBot $bot;
    private array       $update;
    private array       $user;
    private int         $chatId;
    private int         $userId;

    public function __construct(TelegramBot $bot, array $update, array $user)
    {
        $this->bot    = $bot;
        $this->update = $update;
        $this->user   = $user;
        $this->chatId = TelegramBot::getChatId($update);
        $this->userId = TelegramBot::getUserId($update);
    }

    public function handle(string $text): void
    {
        $stateData = StateManager::get($this->userId);
        $state     = $stateData['state'];
        $data      = $stateData['data'];

        Logger::debug("Message state: $state", ['user' => $this->userId, 'text' => mb_substr($text, 0, 50)]);

        // ── Main menu buttons (reply keyboard) ────────────────────────────
        if ($state === 'idle' || $state === '') {
            $this->handleMenuButton($text);
            return;
        }

        // ── Setup wizard ──────────────────────────────────────────────────
        if (str_starts_with($state, 'setup:')) {
            $this->handleSetupInput($state, $text, $data);
            return;
        }

        // ── Add food flow ─────────────────────────────────────────────────
        if ($state === 'add_food:search') {
            $this->handleFoodSearch($text, $data);
            return;
        }

        if ($state === 'add_food:amount') {
            $this->handleFoodAmount($text, $data);
            return;
        }

        // ── Custom food creation ──────────────────────────────────────────
        if (str_starts_with($state, 'create_food:')) {
            $this->handleCustomFoodInput($state, $text, $data);
            return;
        }

        // ── Water ─────────────────────────────────────────────────────────
        if ($state === 'add_water') {
            $this->handleWaterInput($text);
            return;
        }

        // ── Weight ────────────────────────────────────────────────────────
        if ($state === 'add_weight') {
            $this->handleWeightInput($text);
            return;
        }

        // ── Edit goals ────────────────────────────────────────────────────
        if ($state === 'edit_goal') {
            $this->handleGoalEdit($text);
            return;
        }

        if ($state === 'edit_water_goal') {
            $this->handleWaterGoalEdit($text);
            return;
        }

        // ── Admin broadcast ───────────────────────────────────────────────
        if ($state === 'admin:broadcast' && Config::isAdmin($this->userId)) {
            $this->handleAdminBroadcast($text);
            return;
        }

        // ── Free text — try food search ───────────────────────────────────
        $this->handleFreeText($text);
    }

    // ── Menu buttons ──────────────────────────────────────────────────────

    private function handleMenuButton(string $text): void
    {
        $cmd = new CommandHandler($this->bot, $this->update, $this->user);

        $map = [
            '🍽 Kunlik taom'  => fn() => $cmd->sendDiary(date('Y-m-d')),
            '💧 Suv'          => fn() => $this->cmdWater(),
            '📊 Statistika'   => fn() => $this->cmdStats(),
            '⚖️ Vazn'         => fn() => $this->cmdWeight(),
            '🔍 Qidirish'     => fn() => $this->cmdSearch(),
            '⚙️ Sozlamalar'   => fn() => $this->cmdSettings(),
        ];

        if (isset($map[$text])) {
            ($map[$text])();
            return;
        }

        // Free text search
        $this->handleFreeText($text);
    }

    private function cmdWater(): void
    {
        $water   = new WaterTracker($this->userId);
        $today   = $water->getTodayTotal();
        $goal    = $this->user['water_goal_ml'];

        $this->bot->sendMessage(
            $this->chatId,
            "💧 <b>Bugungi suv:</b> <b>{$today} мл</b> / {$goal} мл\n\n"
            . Helpers::progressBar($today, $goal) . "\n\nNecha ml qo'shmoqchisiz?",
            ['reply_markup' => Keyboard::waterAmounts()]
        );
    }

    private function cmdStats(): void
    {
        $this->bot->sendMessage(
            $this->chatId,
            "📊 <b>Statistika</b>\n\nQaysi davrni ko'rmoqchisiz?",
            ['reply_markup' => Keyboard::statsPeriod()]
        );
    }

    private function cmdWeight(): void
    {
        $tracker = new WeightTracker($this->userId);
        $last    = $tracker->getLastEntry();
        $current = $this->user['weight_kg'];

        $text = "⚖️ <b>Vazn kuzatuvi</b>\n\n";
        if ($current) $text .= "📌 Joriy: <b>{$current} кг</b>\n";
        if ($last)    $text .= "📅 Oxirgi: <b>{$last['weight_kg']} кг</b> ({$last['log_date']})\n";
        $text .= "\nBugungi vazningizni kiriting (кг):";

        StateManager::set($this->userId, 'add_weight');
        $this->bot->sendMessage($this->chatId, $text, ['reply_markup' => Keyboard::cancelOnly()]);
    }

    private function cmdSearch(): void
    {
        StateManager::set($this->userId, 'add_food:search', 'query');
        $this->bot->sendMessage(
            $this->chatId,
            "🔍 <b>Qidirish</b>\n\nTaom nomini kiriting:",
            ['reply_markup' => Keyboard::cancelOnly()]
        );
    }

    private function cmdSettings(): void
    {
        $this->bot->sendMessage(
            $this->chatId,
            "⚙️ <b>Sozlamalar</b>",
            ['reply_markup' => Keyboard::settingsMenu()]
        );
    }

    // ── Setup wizard ──────────────────────────────────────────────────────

    private function handleSetupInput(string $state, string $text, array $data): void
    {
        switch ($state) {
            case 'setup:age':
                $age = (int)$text;
                if ($age < 10 || $age > 120) {
                    $this->err("Yosh 10–120 oralig'ida bo'lishi kerak. Qayta kiriting:");
                    return;
                }
                $data['age'] = $age;
                StateManager::set($this->userId, 'setup:height', 'enter', $data);
                $this->bot->sendMessage(
                    $this->chatId,
                    "📏 <b>Profil sozlash (3/7)</b>\n\nBo'yingizni смда kiriting:\n<i>Masalan: 175</i>",
                    ['reply_markup' => Keyboard::cancelOnly()]
                );
                break;

            case 'setup:height':
                $height = (int)$text;
                if ($height < 100 || $height > 250) {
                    $this->err("Bo'y 100–250 sm oralig'ida bo'lishi kerak:");
                    return;
                }
                $data['height_cm'] = $height;
                StateManager::set($this->userId, 'setup:weight', 'enter', $data);
                $this->bot->sendMessage(
                    $this->chatId,
                    "⚖️ <b>Profil sozlash (4/7)</b>\n\nVazningizni кгда kiriting:\n<i>Masalan: 70.5</i>",
                    ['reply_markup' => Keyboard::cancelOnly()]
                );
                break;

            case 'setup:weight':
                $weight = Helpers::parseWeight($text);
                if (!$weight) {
                    $this->err("Vazn 20–500 кг oralig'ida bo'lishi kerak:");
                    return;
                }
                $data['weight_kg'] = $weight;
                StateManager::set($this->userId, 'setup:target_weight', 'enter', $data);
                $this->bot->sendMessage(
                    $this->chatId,
                    "🎯 <b>Profil sozlash (5/7)</b>\n\nMaqsad vazningizni kiriting:\n<i>Masalan: 65</i>",
                    ['reply_markup' => Keyboard::inline([
                        [Keyboard::btn('⏭ O\'tkazib yuborish', 'setup:activity')],
                        [Keyboard::btn('❌ Bekor', 'cancel')],
                    ])]
                );
                break;

            case 'setup:target_weight':
                $target = Helpers::parseWeight($text);
                if (!$target) {
                    $this->err("Maqsad vazn 20–500 кг oralig'ida:");
                    return;
                }
                $data['target_weight_kg'] = $target;
                StateManager::set($this->userId, 'setup:activity', 'choose', $data);
                $this->bot->sendMessage(
                    $this->chatId,
                    "💪 <b>Profil sozlash (6/7)</b>\n\nFaollik darajangizni tanlang:",
                    ['reply_markup' => Keyboard::activityLevel()]
                );
                break;
        }
    }

    // ── Food search ───────────────────────────────────────────────────────

    private function handleFoodSearch(string $query, array $data): void
    {
        if (mb_strlen($query) < 2) {
            $this->err("Kamida 2 ta harf kiriting:");
            return;
        }

        $search = new FoodSearch();
        $result = $search->search($query, 0);

        StateManager::set($this->userId, 'add_food:meal', 'choose_meal', array_merge($data, [
            'query' => $query,
        ]));

        if (empty($result['foods'])) {
            $this->bot->sendMessage(
                $this->chatId,
                "😔 <b>«{$query}» topilmadi.</b>\n\nBoshqa nom kiriting yoki o'z taomingizni qo'shing:",
                ['reply_markup' => Keyboard::inline([
                    [Keyboard::btn('✏️ O\'z taom qo\'shish', 'food_custom:snack')],
                    [Keyboard::btn('❌ Bekor', 'cancel')],
                ])]
            );
            return;
        }

        // First ask meal type, then show results
        $this->bot->sendMessage(
            $this->chatId,
            "🍽 <b>Qaysi ovqatga qo'shmoqchisiz?</b>",
            ['reply_markup' => Keyboard::mealType()]
        );
    }

    // ── Food amount ───────────────────────────────────────────────────────

    private function handleFoodAmount(string $text, array $data): void
    {
        // Check preset callback "amount_preset:XXX"
        $grams = Helpers::parseGrams($text);
        if (!$grams) {
            $this->err("Noto'g'ri miqdor. Grammlarda kiriting (1–5000):");
            return;
        }

        $this->saveFoodEntry($data, $grams);
    }

    private function saveFoodEntry(array $data, float $grams): void
    {
        $diary    = new FoodDiary($this->userId);
        $entryDate = $data['entry_date'] ?? date('Y-m-d');
        $mealType  = $data['meal_type'] ?? 'snack';

        if (!empty($data['food_id'])) {
            $food = Database::fetchOne("SELECT * FROM foods WHERE id = ?", [$data['food_id']]);
            if (!$food) { $this->err('Taom topilmadi'); return; }

            $ratio    = $grams / 100;
            $entryId  = $diary->addEntry([
                'food_id'    => $food['id'],
                'food_name'  => $food['name_ru'],
                'meal_type'  => $mealType,
                'entry_date' => $entryDate,
                'entry_time' => date('H:i:s'),
                'amount_g'   => $grams,
                'calories'   => round($food['calories'] * $ratio, 2),
                'protein_g'  => round($food['protein_g'] * $ratio, 2),
                'fat_g'      => round($food['fat_g'] * $ratio, 2),
                'carbs_g'    => round($food['carbs_g'] * $ratio, 2),
                'fiber_g'    => round($food['fiber_g'] * $ratio, 2),
            ]);

            $cal = round($food['calories'] * $ratio);
            $totals = $diary->getTotals($entryDate);

            $text = "✅ <b>{$food['name_ru']}</b> qo'shildi!\n\n"
                . "📦 Miqdor: <b>{$grams}г</b>\n"
                . "🔥 Kaloriya: <b>{$cal} ккал</b>\n\n"
                . "━━━━━━━━━━━━━━━━━\n"
                . "📊 Bugun jami: <b>" . round($totals['calories']) . " ккал</b> / {$this->user['calorie_goal']}\n"
                . Helpers::progressBar($totals['calories'], $this->user['calorie_goal']);

            StateManager::clear($this->userId);
            $this->bot->sendMessage($this->chatId, $text, [
                'reply_markup' => Keyboard::inline([
                    [
                        Keyboard::btn('📋 Kundalik', "diary:{$entryDate}"),
                        Keyboard::btn('➕ Yana qo\'shish', 'add_food:start'),
                    ],
                    [Keyboard::btn('🏠 Asosiy menyu', 'main_menu')],
                ]),
            ]);

            // Update streak
            $this->updateStreak();
        }
    }

    // ── Custom food creation ──────────────────────────────────────────────

    private function handleCustomFoodInput(string $state, string $text, array $data): void
    {
        switch ($state) {
            case 'create_food:name':
                if (mb_strlen($text) < 2 || mb_strlen($text) > 100) {
                    $this->err("Nom 2–100 belgidan iborat bo'lishi kerak:");
                    return;
                }
                $data['name'] = $text;
                StateManager::set($this->userId, 'create_food:calories', 'enter', $data);
                $this->bot->sendMessage($this->chatId,
                    "🔥 <b>2/5.</b> 100г uchun kaloriyani kiriting:\n<i>Masalan: 250</i>",
                    ['reply_markup' => Keyboard::cancelOnly()]
                );
                break;

            case 'create_food:calories':
                $cal = Helpers::parseGrams($text);
                if (!$cal || $cal > 900) { $this->err("Kaloriya 1–900 oralig'ida:"); return; }
                $data['calories'] = $cal;
                StateManager::set($this->userId, 'create_food:protein', 'enter', $data);
                $this->bot->sendMessage($this->chatId,
                    "🥩 <b>3/5.</b> Oqsil (г/100г):\n<i>Masalan: 15.5</i>",
                    ['reply_markup' => Keyboard::cancelOnly()]
                );
                break;

            case 'create_food:protein':
                $prot = Helpers::parseGrams($text);
                if ($prot === null) { $this->err("Noto'g'ri qiymat:"); return; }
                $data['protein_g'] = $prot;
                StateManager::set($this->userId, 'create_food:fat', 'enter', $data);
                $this->bot->sendMessage($this->chatId,
                    "🧈 <b>4/5.</b> Yog' (г/100г):",
                    ['reply_markup' => Keyboard::cancelOnly()]
                );
                break;

            case 'create_food:fat':
                $fat = Helpers::parseGrams($text);
                if ($fat === null) { $this->err("Noto'g'ri qiymat:"); return; }
                $data['fat_g'] = $fat;
                StateManager::set($this->userId, 'create_food:carbs', 'enter', $data);
                $this->bot->sendMessage($this->chatId,
                    "🍞 <b>5/5.</b> Karbohidrat (г/100г):",
                    ['reply_markup' => Keyboard::cancelOnly()]
                );
                break;

            case 'create_food:carbs':
                $carbs = Helpers::parseGrams($text);
                if ($carbs === null) { $this->err("Noto'g'ri qiymat:"); return; }
                $data['carbs_g'] = $carbs;

                // Save custom food
                $foodId = Database::insert('custom_foods', [
                    'user_id'   => $this->userId,
                    'name'      => $data['name'],
                    'calories'  => $data['calories'],
                    'protein_g' => $data['protein_g'],
                    'fat_g'     => $data['fat_g'],
                    'carbs_g'   => $data['carbs_g'],
                    'serving_g' => 100,
                ]);

                // Also add to foods table for this user
                $globalFoodId = Database::insert('foods', [
                    'name_ru'        => $data['name'],
                    'category'       => 'custom',
                    'calories'       => $data['calories'],
                    'protein_g'      => $data['protein_g'],
                    'fat_g'          => $data['fat_g'],
                    'carbs_g'        => $data['carbs_g'],
                    'is_verified'    => 0,
                    'created_by'     => $this->userId,
                ]);

                $mealType = $data['meal_type'] ?? 'snack';
                $entryData = [
                    'food_id'    => $globalFoodId,
                    'food_name'  => $data['name'],
                    'meal_type'  => $mealType,
                    'entry_date' => $data['entry_date'] ?? date('Y-m-d'),
                    'entry_time' => date('H:i:s'),
                    'amount_g'   => 100,
                    'calories'   => $data['calories'],
                    'protein_g'  => $data['protein_g'],
                    'fat_g'      => $data['fat_g'],
                    'carbs_g'    => $data['carbs_g'],
                    'fiber_g'    => 0,
                ];

                $diary = new FoodDiary($this->userId);
                $diary->addEntry($entryData);

                StateManager::clear($this->userId);

                $this->bot->sendMessage(
                    $this->chatId,
                    "✅ <b>{$data['name']}</b> qo'shildi va kundalikka yozildi!\n\n"
                    . "🔥 {$data['calories']} ккал | 🥩 {$data['protein_g']}г | 🧈 {$data['fat_g']}г | 🍞 {$data['carbs_g']}г",
                    ['reply_markup' => Keyboard::inline([
                        [Keyboard::btn('📋 Kundalikni ko\'rish', 'diary:today')],
                        [Keyboard::btn('🏠 Asosiy menyu', 'main_menu')],
                    ])]
                );
                break;
        }
    }

    // ── Water input ───────────────────────────────────────────────────────

    private function handleWaterInput(string $text): void
    {
        $ml = (int)filter_var($text, FILTER_SANITIZE_NUMBER_INT);
        if ($ml < 50 || $ml > 3000) {
            $this->err("50–3000 мл oralig'ida kiriting:");
            return;
        }

        $water = new WaterTracker($this->userId);
        $water->addEntry($ml);

        $today = $water->getTodayTotal();
        $goal  = $this->user['water_goal_ml'];

        StateManager::clear($this->userId);

        $text2 = "💧 <b>+{$ml} мл qo'shildi!</b>\n\n"
            . "Bugun jami: <b>{$today} мл</b> / {$goal} мл\n"
            . Helpers::progressBar($today, $goal);

        if ($today >= $goal) $text2 .= "\n\n🎉 Kunlik maqsadga yetdingiz!";

        $this->bot->sendMessage($this->chatId, $text2, [
            'reply_markup' => Keyboard::mainMenu(),
        ]);
    }

    // ── Weight input ──────────────────────────────────────────────────────

    private function handleWeightInput(string $text): void
    {
        $weight = Helpers::parseWeight($text);
        if (!$weight) {
            $this->err("Noto'g'ri vazn. 20–500 кг oralig'ida kiriting:");
            return;
        }

        $tracker = new WeightTracker($this->userId);
        $tracker->addEntry($weight);

        Database::update('users', ['weight_kg' => $weight], ['telegram_id' => $this->userId]);

        $prev    = $this->user['weight_kg'];
        $target  = $this->user['target_weight_kg'];
        $diff    = $prev ? round($weight - (float)$prev, 1) : 0;
        $arrow   = $diff > 0 ? '↗ +' : '↘ ';

        StateManager::clear($this->userId);

        $text2  = "✅ <b>Vazn qayd etildi: {$weight} кг</b>\n\n";
        if ($prev && $diff != 0) {
            $text2 .= "Oldingi: {$prev} кг ({$arrow}{$diff} кг)\n";
        }
        if ($target) {
            $toTarget = round(abs($weight - (float)$target), 1);
            $text2 .= "🎯 Maqsadga: {$toTarget} кг qoldi\n";
        }

        $bmi = Helpers::bmi($weight, (float)($this->user['height_cm'] ?? 0));
        if ($bmi > 0) {
            $text2 .= "\n📊 BMI: <b>{$bmi}</b> — " . Helpers::bmiCategory($bmi);
        }

        $this->bot->sendMessage($this->chatId, $text2, [
            'reply_markup' => Keyboard::inline([
                [Keyboard::btn('📈 Vazn grafigi', 'stats:weight')],
                [Keyboard::btn('🏠 Asosiy menyu', 'main_menu')],
            ]),
        ]);
    }

    // ── Goal edit ─────────────────────────────────────────────────────────

    private function handleGoalEdit(string $text): void
    {
        $goal = (int)$text;
        if ($goal < 1200 || $goal > 5000) {
            $this->err("Kaloriya maqsadi 1200–5000 oralig'ida bo'lishi kerak:");
            return;
        }

        $macros = Helpers::calculateMacros($goal, $this->user['goal']);
        Database::update('users', [
            'calorie_goal'   => $goal,
            'protein_goal_g' => $macros['protein'],
            'fat_goal_g'     => $macros['fat'],
            'carbs_goal_g'   => $macros['carbs'],
        ], ['telegram_id' => $this->userId]);

        StateManager::clear($this->userId);
        $this->bot->sendMessage(
            $this->chatId,
            "✅ <b>Kaloriya maqsadi yangilandi: {$goal} ккал</b>\n\n"
            . "🥩 Oqsil: {$macros['protein']}г | 🧈 Yog': {$macros['fat']}г | 🍞 Karbo: {$macros['carbs']}г",
            ['reply_markup' => Keyboard::mainMenu()]
        );
    }

    private function handleWaterGoalEdit(string $text): void
    {
        $ml = (int)$text;
        if ($ml < 1000 || $ml > 5000) {
            $this->err("1000–5000 мл oralig'ida kiriting:");
            return;
        }

        Database::update('users', ['water_goal_ml' => $ml], ['telegram_id' => $this->userId]);
        StateManager::clear($this->userId);

        $this->bot->sendMessage(
            $this->chatId,
            "✅ <b>Suv maqsadi yangilandi: {$ml} мл</b>",
            ['reply_markup' => Keyboard::mainMenu()]
        );
    }

    // ── Admin broadcast ───────────────────────────────────────────────────

    private function handleAdminBroadcast(string $text): void
    {
        StateManager::clear($this->userId);
        $users = Database::fetchAll("SELECT telegram_id FROM users WHERE is_blocked = 0");
        $sent = $failed = 0;

        foreach ($users as $u) {
            $result = $this->bot->sendMessage((int)$u['telegram_id'], "📢 " . $text);
            $result ? $sent++ : $failed++;
            usleep(50000); // 50ms throttle
        }

        $this->bot->sendMessage(
            $this->chatId,
            "📢 <b>Xabar yuborildi!</b>\n✅ Muvaffaqiyatli: {$sent}\n❌ Xato: {$failed}"
        );
    }

    // ── Free text / smart parse ───────────────────────────────────────────

    private function handleFreeText(string $text): void
    {
        // Try to parse "food name AMOUNT g" pattern
        // e.g. "palov 200г", "тухум 2 дона", "olma 1"
        if (preg_match('/^(.+?)\s+([\d.,]+)\s*(?:г|g|гр|gram)?$/ui', $text, $m)) {
            $query  = trim($m[1]);
            $grams  = Helpers::parseGrams($m[2]);

            if ($grams && mb_strlen($query) >= 2) {
                $search = new FoodSearch();
                $result = $search->search($query, 0);

                if (!empty($result['foods'])) {
                    $food  = $result['foods'][0]; // best match
                    $ratio = $grams / 100;
                    $cal   = round($food['calories'] * $ratio);

                    StateManager::set($this->userId, 'add_food:amount', 'confirm', [
                        'food_id'    => $food['id'],
                        'food_name'  => $food['name_ru'],
                        'amount_g'   => $grams,
                        'entry_date' => date('Y-m-d'),
                    ]);

                    $this->bot->sendMessage(
                        $this->chatId,
                        "🔍 <b>Topildi:</b> {$food['name_ru']}\n"
                        . "📦 {$grams}г → 🔥 {$cal} ккал\n\n"
                        . "Qaysi ovqatga qo'shmoqchisiz?",
                        ['reply_markup' => Keyboard::mealType()]
                    );
                    return;
                }
            }
        }

        // Generic search
        if (mb_strlen($text) >= 2 && mb_strlen($text) <= 100) {
            $search = new FoodSearch();
            $result = $search->search($text, 0);

            if (!empty($result['foods'])) {
                StateManager::set($this->userId, 'add_food:meal', 'choose_meal', ['query' => $text]);
                $this->bot->sendMessage(
                    $this->chatId,
                    "🔍 <b>«{$text}» — {$result['total']} natija topildi.</b>\n\nQaysi ovqatga qo'shmoqchisiz?",
                    ['reply_markup' => Keyboard::mealType()]
                );
                return;
            }
        }

        // Nothing found
        $this->bot->sendMessage(
            $this->chatId,
            "🤔 Tushunmadim. /help — barcha imkoniyatlarni ko'rish\n\n"
            . "<i>Maslahat: Taom nomini yozing yoki /add buyrug'ini ishlating</i>",
            ['reply_markup' => Keyboard::mainMenu()]
        );
    }

    // ── Streak ────────────────────────────────────────────────────────────

    private function updateStreak(): void
    {
        $today = date('Y-m-d');
        $row   = Database::fetchOne(
            "SELECT * FROM user_streaks WHERE user_id = ?",
            [$this->userId]
        );

        if (!$row) {
            Database::insert('user_streaks', [
                'user_id'          => $this->userId,
                'current_streak'   => 1,
                'longest_streak'   => 1,
                'last_active_date' => $today,
                'total_active_days'=> 1,
            ]);
            return;
        }

        if ($row['last_active_date'] === $today) return; // Already counted today

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $current   = ($row['last_active_date'] === $yesterday)
            ? $row['current_streak'] + 1
            : 1;

        $longest = max($current, $row['longest_streak']);

        Database::update('user_streaks', [
            'current_streak'    => $current,
            'longest_streak'    => $longest,
            'last_active_date'  => $today,
            'total_active_days' => $row['total_active_days'] + 1,
        ], ['user_id' => $this->userId]);
    }

    // ── Error helper ──────────────────────────────────────────────────────

    private function err(string $message): void
    {
        $this->bot->sendMessage($this->chatId, "⚠️ " . $message, [
            'reply_markup' => Keyboard::cancelOnly(),
        ]);
    }
}
