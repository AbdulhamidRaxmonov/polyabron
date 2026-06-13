<?php

namespace App\Handlers;

use App\Core\{Config, Database, Keyboard, Logger, StateManager, TelegramBot, Helpers};
use App\Features\{FoodDiary, FoodSearch, Statistics, UserProfile, WaterTracker, WeightTracker};

class CallbackHandler
{
    private TelegramBot $bot;
    private array       $update;
    private array       $user;
    private int         $chatId;
    private int         $userId;
    private int         $messageId;
    private string      $callbackId;

    public function __construct(TelegramBot $bot, array $update, array $user)
    {
        $this->bot        = $bot;
        $this->update     = $update;
        $this->user       = $user;
        $this->chatId     = TelegramBot::getChatId($update);
        $this->userId     = TelegramBot::getUserId($update);
        $this->messageId  = TelegramBot::getMessageId($update);
        $this->callbackId = TelegramBot::getCallbackQueryId($update);
    }

    public function handle(string $data): void
    {
        Logger::debug("Callback: $data", ['user' => $this->userId]);

        [$action, $param1, $param2] = array_pad(explode(':', $data, 3), 3, '');

        match ($action) {
            'setup'        => $this->handleSetup($param1, $param2),
            'gender'       => $this->handleGender($param1),
            'activity'     => $this->handleActivity($param1),
            'goal'         => $this->handleGoal($param1),
            'diary'        => $this->handleDiary($param1),
            'addentry'     => $this->handleAddEntry($param1),
            'meal'         => $this->handleMealType($param1),
            'food_select'  => $this->handleFoodSelect((int)$param1, $param2),
            'food_page'    => $this->handleFoodPage((int)$param1, $param2),
            'food_custom'  => $this->handleCustomFood($param1),
            'del_entry'    => $this->handleDeleteEntry((int)$param1, $param2),
            'water'        => $this->handleWater($param1),
            'stats'        => $this->handleStats($param1),
            'settings'     => $this->handleSettings($param1),
            'notify'       => $this->handleNotify($param1),
            'main_menu'    => $this->handleMainMenu(),
            'cancel'       => $this->handleCancel(),
            'admin'        => $this->handleAdmin($param1),
            'noop'         => $this->ack(),
            default        => $this->ack('Noma\'lum amal'),
        };
    }

    // ── Setup / Onboarding ────────────────────────────────────────────────

    private function handleSetup(string $step, string $param): void
    {
        $profile = new UserProfile($this->userId, $this->user);

        switch ($step) {
            case 'start':
                StateManager::set($this->userId, 'setup:gender', 'choose');
                $this->editOrSend(
                    "👤 <b>Profil sozlash (1/7)</b>\n\n"
                    . "Jinsingizni tanlang:",
                    Keyboard::genderSelect()
                );
                break;

            case 'age':
                StateManager::set($this->userId, 'setup:age', 'enter');
                $this->editOrSend(
                    "🎂 <b>Profil sozlash (2/7)</b>\n\n"
                    . "Yoshingizni kiriting:\n<i>Masalan: 25</i>",
                    Keyboard::cancelOnly()
                );
                break;

            case 'height':
                StateManager::set($this->userId, 'setup:height', 'enter');
                $this->editOrSend(
                    "📏 <b>Profil sozlash (3/7)</b>\n\n"
                    . "Bo'yingizni sm da kiriting:\n<i>Masalan: 175</i>",
                    Keyboard::cancelOnly()
                );
                break;

            case 'weight':
                StateManager::set($this->userId, 'setup:weight', 'enter');
                $this->editOrSend(
                    "⚖️ <b>Profil sozlash (4/7)</b>\n\n"
                    . "Joriy vazningizni кг da kiriting:\n<i>Masalan: 70.5</i>",
                    Keyboard::cancelOnly()
                );
                break;

            case 'target_weight':
                StateManager::set($this->userId, 'setup:target_weight', 'enter');
                $this->editOrSend(
                    "🎯 <b>Profil sozlash (5/7)</b>\n\n"
                    . "Maqsad vazningizni kiriting:\n<i>Masalan: 65</i>",
                    Keyboard::inline([
                        [Keyboard::btn('⏭ O\'tkazib yuborish', 'setup:activity')],
                        [Keyboard::btn('❌ Bekor', 'cancel')],
                    ])
                );
                break;

            case 'activity':
                StateManager::set($this->userId, 'setup:activity', 'choose');
                $this->editOrSend(
                    "💪 <b>Profil sozlash (6/7)</b>\n\n"
                    . "Faollik darajangizni tanlang:",
                    Keyboard::activityLevel()
                );
                break;

            case 'goal_type':
                StateManager::set($this->userId, 'setup:goal', 'choose');
                $this->editOrSend(
                    "🏁 <b>Profil sozlash (7/7)</b>\n\n"
                    . "Maqsadingizni tanlang:",
                    Keyboard::goalType()
                );
                break;

            case 'done':
                $this->finishSetup($profile);
                break;
        }
    }

    private function handleGender(string $gender): void
    {
        if (!in_array($gender, ['male', 'female'])) { $this->ack(); return; }
        $data = StateManager::getData($this->userId);
        $data['gender'] = $gender;
        StateManager::set($this->userId, 'setup:age', 'enter', $data);

        $this->editOrSend(
            "🎂 <b>Profil sozlash (2/7)</b>\n\n"
            . "Yoshingizni kiriting:\n<i>Masalan: 25</i>",
            Keyboard::cancelOnly()
        );
    }

    private function handleActivity(string $level): void
    {
        $valid = ['sedentary', 'light', 'moderate', 'active', 'very_active'];
        if (!in_array($level, $valid)) { $this->ack(); return; }

        $data = StateManager::getData($this->userId);
        $data['activity_level'] = $level;
        StateManager::set($this->userId, 'setup:goal', 'choose', $data);

        $this->editOrSend(
            "🏁 <b>Profil sozlash (7/7)</b>\n\nMaqsadingizni tanlang:",
            Keyboard::goalType()
        );
    }

    private function handleGoal(string $goal): void
    {
        if (!in_array($goal, ['lose', 'maintain', 'gain'])) { $this->ack(); return; }

        $data         = StateManager::getData($this->userId);
        $data['goal'] = $goal;
        StateManager::set($this->userId, 'setup:done', 'finish', $data);

        $profile = new UserProfile($this->userId, $this->user);
        $profile->saveSetupData($data);
        $this->finishSetup($profile);
    }

    private function finishSetup(UserProfile $profile): void
    {
        StateManager::clear($this->userId);
        $u    = Database::fetchOne("SELECT * FROM users WHERE telegram_id = ?", [$this->userId]);
        $bmi  = Helpers::bmi((float)($u['weight_kg'] ?? 0), (float)($u['height_cm'] ?? 0));

        $text = "🎉 <b>Profil sozlash tugadi!</b>\n\n"
            . "🔥 Kunlik kaloriya maqsadi: <b>{$u['calorie_goal']} ккал</b>\n"
            . "🥩 Oqsil: <b>{$u['protein_goal_g']} г</b>\n"
            . "🧈 Yog': <b>{$u['fat_goal_g']} г</b>\n"
            . "🍞 Karbohidrat: <b>{$u['carbs_goal_g']} г</b>\n"
            . "💧 Suv: <b>{$u['water_goal_ml']} мл</b>\n";

        if ($bmi > 0) {
            $text .= "\n📊 BMI: <b>{$bmi}</b> — " . Helpers::bmiCategory($bmi);
        }

        $text .= "\n\n✅ Endi botdan foydalanishni boshlashingiz mumkin!";

        $this->editOrSend($text, Keyboard::inline([
            [Keyboard::btn('🏠 Asosiy menyuga', 'main_menu')],
        ]));
    }

    // ── Diary ─────────────────────────────────────────────────────────────

    private function handleDiary(string $dateOrAction): void
    {
        $date = $dateOrAction === 'today' ? date('Y-m-d') : $dateOrAction;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->ack('Noto\'g\'ri sana');
            return;
        }

        // Future date guard
        if ($date > date('Y-m-d')) { $this->ack('Kelajak sanani ko\'rib bo\'lmaydi'); return; }

        $cmdHandler = new CommandHandler($this->bot, $this->update, $this->user);

        // Try edit existing message
        try {
            $diary   = new FoodDiary($this->userId);
            $entries = $diary->getEntries($date);
            $totals  = $diary->getTotals($date);
            $user    = $this->user;

            $dateLabel = Helpers::formatDate($date);
            $text = "📋 <b>Kundalik — {$dateLabel}</b>\n\n";

            if (empty($entries)) {
                $text .= "😔 <i>Bu kunda hech narsa qo'shilmagan.</i>\n\n";
            } else {
                $meals = ['breakfast' => [], 'lunch' => [], 'dinner' => [], 'snack' => []];
                foreach ($entries as $e) $meals[$e['meal_type']][] = $e;

                foreach ($meals as $mt => $items) {
                    if (!$items) continue;
                    $mealCal = array_sum(array_column($items, 'calories'));
                    $text .= Helpers::mealLabel($mt) . " — <b>" . round($mealCal) . " ккал</b>\n";
                    foreach ($items as $item) {
                        $text .= sprintf("  • %s <i>%.0fg</i> — %.0f ккал\n",
                            Helpers::html($item['food_name']),
                            $item['amount_g'],
                            $item['calories']
                        );
                    }
                    $text .= "\n";
                }
            }

            $cal     = round($totals['calories'] ?? 0);
            $calGoal = $user['calorie_goal'];
            $text .= "━━━━━━━━━━━━━━━━━━━━\n"
                . "🔥 <b>Kaloriya:</b> <b>{$cal}</b> / {$calGoal} ккал\n"
                . Helpers::progressBar($cal, $calGoal) . "\n"
                . Helpers::macroLine(
                    round($totals['protein_g'] ?? 0),
                    round($totals['fat_g'] ?? 0),
                    round($totals['carbs_g'] ?? 0)
                ) . "\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . Helpers::calorieStatus($cal, $calGoal);

            $this->bot->editMessageText($this->chatId, $this->messageId, $text, [
                'reply_markup' => Keyboard::diaryNav($date),
            ]);
            $this->ack();
        } catch (\Throwable $e) {
            Logger::exception($e);
            $this->ack('Xatolik yuz berdi');
        }
    }

    private function handleAddEntry(string $date): void
    {
        StateManager::set($this->userId, 'add_food:search', 'query', ['entry_date' => $date]);
        $this->bot->sendMessage(
            $this->chatId,
            "🔍 <b>Taom nomini kiriting:</b>\n\n"
            . "<i>Masalan: palov, tuxum, non, olma...</i>",
            ['reply_markup' => Keyboard::cancelOnly()]
        );
        $this->ack();
    }

    // ── Food selection ────────────────────────────────────────────────────

    private function handleMealType(string $mealType): void
    {
        $validMeals = ['breakfast', 'lunch', 'dinner', 'snack'];
        if (!in_array($mealType, $validMeals)) { $this->ack(); return; }

        $data = StateManager::getData($this->userId);
        $data['meal_type'] = $mealType;

        if (!empty($data['food_id'])) {
            // Already selected food, now ask amount
            $food = Database::fetchOne("SELECT * FROM foods WHERE id = ?", [$data['food_id']]);
            StateManager::set($this->userId, 'add_food:amount', 'enter', $data);
            $this->editOrSend(
                "⚖️ <b>Miqdorni kiriting ({$food['name_ru']}):</b>\n\n"
                . "Standart porsiya: <b>{$food['serving_size_g']} г ({$food['serving_name']})</b>\n"
                . "<i>Grammlarda yozing, masalan: 150</i>",
                Keyboard::inline([
                    [Keyboard::btn("✅ Standart ({$food['serving_size_g']}г)", "amount_preset:{$food['serving_size_g']}")],
                    [Keyboard::btn('❌ Bekor', 'cancel')],
                ])
            );
        } elseif (!empty($data['query'])) {
            // Search
            $this->doFoodSearch($data['query'], $mealType, 0);
        }
    }

    private function handleFoodSelect(int $foodId, string $mealType): void
    {
        $food = Database::fetchOne("SELECT * FROM foods WHERE id = ?", [$foodId]);
        if (!$food) { $this->ack('Taom topilmadi'); return; }

        $data = StateManager::getData($this->userId);
        $data['food_id']   = $foodId;
        $data['food_name'] = $food['name_ru'];
        $data['meal_type'] = $mealType;

        StateManager::set($this->userId, 'add_food:amount', 'enter', $data);

        $this->editOrSend(
            "⚖️ <b>{$food['name_ru']}</b>\n\n"
            . "100г uchun: 🔥 {$food['calories']} ккал | 🥩 {$food['protein_g']}г | 🧈 {$food['fat_g']}г | 🍞 {$food['carbs_g']}г\n\n"
            . "Miqdorni grammlarda kiriting:",
            Keyboard::inline([
                [
                    Keyboard::btn("✅ {$food['serving_size_g']}г ({$food['serving_name']})", "amount_preset:{$food['serving_size_g']}"),
                ],
                [Keyboard::btn('❌ Bekor', 'cancel')],
            ])
        );
    }

    private function handleFoodPage(int $page, string $mealType): void
    {
        $query = StateManager::getDataKey($this->userId, 'query') ?? '';
        if (!$query) { $this->ack(); return; }
        $this->doFoodSearch($query, $mealType, $page);
    }

    private function doFoodSearch(string $query, string $mealType, int $page = 0): void
    {
        $search = new FoodSearch();
        $result = $search->search($query, $page);

        StateManager::setData($this->userId, [
            'query'     => $query,
            'meal_type' => $mealType,
        ]);

        if (empty($result['foods'])) {
            $this->editOrSend(
                "😔 <b>«{$query}» topilmadi.</b>\n\nBoshqa nom kiriting yoki o'z taomingizni qo'shing:",
                Keyboard::inline([
                    [Keyboard::btn('✏️ O\'z taom qo\'shish', "food_custom:$mealType")],
                    [Keyboard::btn('❌ Bekor', 'cancel')],
                ])
            );
            return;
        }

        $this->editOrSend(
            "🔍 <b>«{$query}» — {$result['total']} natija:</b>",
            Keyboard::foodSearchResults($result['foods'], $mealType, $page, $result['total'])
        );
    }

    private function handleCustomFood(string $mealType): void
    {
        StateManager::set($this->userId, 'create_food:name', 'enter', [
            'meal_type' => $mealType,
        ]);
        $this->editOrSend(
            "✏️ <b>O'z taomingizni qo'shing</b>\n\n"
            . "<b>1/5.</b> Taom nomini kiriting:",
            Keyboard::cancelOnly()
        );
    }

    private function handleDeleteEntry(int $entryId, string $date): void
    {
        $diary = new FoodDiary($this->userId);
        if ($diary->deleteEntry($entryId)) {
            $this->ack('🗑 O\'chirildi');
            // Refresh diary
            $this->handleDiary($date);
        } else {
            $this->ack('Yozuv topilmadi');
        }
    }

    // ── Water ─────────────────────────────────────────────────────────────

    private function handleWater(string $amount): void
    {
        if ($amount === 'custom') {
            StateManager::set($this->userId, 'add_water');
            $this->editOrSend(
                "💧 <b>Necha ml suv ichdingiz?</b>\n<i>Raqam kiriting, masalan: 350</i>",
                Keyboard::cancelOnly()
            );
            return;
        }

        $ml = (int)$amount;
        if ($ml <= 0 || $ml > 3000) { $this->ack('Noto\'g\'ri miqdor'); return; }

        $water = new WaterTracker($this->userId);
        $water->addEntry($ml);

        $today = $water->getTodayTotal();
        $goal  = $this->user['water_goal_ml'];

        $text = "💧 <b>+{$ml} мл qo'shildi!</b>\n\n"
            . "Bugun jami: <b>{$today} мл</b> / {$goal} мл\n"
            . Helpers::progressBar($today, $goal);

        if ($today >= $goal) {
            $text .= "\n\n🎉 <b>Kunlik suv maqsadiga yetdingiz!</b>";
        }

        $this->editOrSend($text, Keyboard::inline([
            [
                Keyboard::btn('➕ Yana qo\'shish', 'water:250'),
                Keyboard::btn('⬅️ Orqaga', 'main_menu'),
            ],
        ]));
    }

    // ── Stats ─────────────────────────────────────────────────────────────

    private function handleStats(string $period): void
    {
        $stats = new Statistics($this->userId, $this->user);
        $text  = $stats->generateReport($period);

        $this->editOrSend($text, Keyboard::statsPeriod());
    }

    // ── Settings ──────────────────────────────────────────────────────────

    private function handleSettings(string $setting): void
    {
        switch ($setting) {
            case 'menu':
                $this->editOrSend("⚙️ <b>Sozlamalar</b>", Keyboard::settingsMenu());
                break;
            case 'goal':
                StateManager::set($this->userId, 'edit_goal');
                $this->editOrSend(
                    "🎯 <b>Kaloriya maqsadi</b>\n\n"
                    . "Joriy maqsad: <b>{$this->user['calorie_goal']} ккал</b>\n\n"
                    . "Yangi qiymatni kiriting (1200–5000):",
                    Keyboard::inline([
                        [Keyboard::btn('🔄 Avtomatik hisoblash', 'setup:start')],
                        [Keyboard::btn('❌ Bekor', 'cancel')],
                    ])
                );
                break;
            case 'profile':
                // Redirect to setup
                $this->handleSetup('start', '');
                break;
            case 'water_goal':
                StateManager::set($this->userId, 'edit_water_goal');
                $this->editOrSend(
                    "💧 <b>Suv maqsadi</b>\n\n"
                    . "Joriy maqsad: <b>{$this->user['water_goal_ml']} мл</b>\n\n"
                    . "Yangi qiymatni kiriting (1000–5000 мл):",
                    Keyboard::cancelOnly()
                );
                break;
            case 'notifications':
                $this->editOrSend(
                    "🔔 <b>Bildirishnomalar</b>",
                    Keyboard::notificationSettings($this->user)
                );
                break;
            case 'language':
                $this->editOrSend(
                    "🌍 <b>Til / Language</b>\n\nHozircha faqat O'zbek va Ruscha qo'llab-quvvatlanadi.",
                    Keyboard::inline([
                        [
                            Keyboard::btn('🇺🇿 O\'zbek', 'lang:uz'),
                            Keyboard::btn('🇷🇺 Ruscha', 'lang:ru'),
                        ],
                        [Keyboard::btn('⬅️ Orqaga', 'settings:menu')],
                    ])
                );
                break;
        }
    }

    private function handleNotify(string $type): void
    {
        $field = match ($type) {
            'morning' => 'notify_morning',
            'evening' => 'notify_evening',
            'water'   => 'notify_water',
            default   => null,
        };

        if (!$field) { $this->ack(); return; }

        $current = (int)($this->user[$field] ?? 0);
        $new     = $current ? 0 : 1;

        Database::update('users', [$field => $new], ['telegram_id' => $this->userId]);
        $this->user[$field] = $new;

        $this->bot->editMessageReplyMarkup($this->chatId, $this->messageId, [
            'reply_markup' => Keyboard::notificationSettings($this->user),
        ]) ?? $this->editOrSend("🔔 <b>Bildirishnomalar</b>", Keyboard::notificationSettings($this->user));

        $this->ack($new ? '✅ Yoqildi' : '❌ O\'chirildi');
    }

    // ── Main menu ─────────────────────────────────────────────────────────

    private function handleMainMenu(): void
    {
        StateManager::clear($this->userId);
        $this->bot->sendMessage($this->chatId, "🏠 <b>Asosiy menyu</b>", [
            'reply_markup' => Keyboard::mainMenu(),
        ]);
        $this->ack();
    }

    // ── Cancel ────────────────────────────────────────────────────────────

    private function handleCancel(): void
    {
        StateManager::clear($this->userId);
        $this->editOrSend("❌ <b>Bekor qilindi.</b>", Keyboard::backToMain());
    }

    // ── Admin ─────────────────────────────────────────────────────────────

    private function handleAdmin(string $action): void
    {
        if (!Config::isAdmin($this->userId)) { $this->ack('Ruxsat yo\'q'); return; }

        if ($action === 'broadcast') {
            StateManager::set($this->userId, 'admin:broadcast');
            $this->bot->sendMessage($this->chatId,
                "📢 <b>Barcha foydalanuvchilarga xabar yuborish</b>\n\nXabar matnini kiriting:",
                ['reply_markup' => Keyboard::cancelOnly()]
            );
        } elseif ($action === 'stats') {
            $stats = Database::fetchAll(
                "SELECT DATE(created_at) as day, COUNT(*) as cnt 
                 FROM users 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY day ORDER BY day DESC"
            );
            $text = "📊 <b>Oxirgi 7 kun registratsiyalar:</b>\n\n";
            foreach ($stats as $s) {
                $text .= "📅 {$s['day']}: <b>{$s['cnt']}</b> yangi\n";
            }
            $this->editOrSend($text, Keyboard::inline([
                [Keyboard::btn('⬅️ Orqaga', 'admin:menu')],
            ]));
        }
        $this->ack();
    }

    // ── Utilities ─────────────────────────────────────────────────────────

    private function ack(string $text = ''): void
    {
        $this->bot->answerCallbackQuery($this->callbackId, $text);
    }

    private function editOrSend(string $text, array $keyboard): void
    {
        try {
            $this->bot->editMessageText($this->chatId, $this->messageId, $text, [
                'reply_markup' => $keyboard,
            ]);
        } catch (\Throwable) {
            $this->bot->sendMessage($this->chatId, $text, [
                'reply_markup' => $keyboard,
            ]);
        }
    }
}
