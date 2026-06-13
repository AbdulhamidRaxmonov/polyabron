<?php

namespace App\Handlers;

use App\Core\{Config, Database, Keyboard, Logger, StateManager, TelegramBot};
use App\Features\{FoodDiary, Statistics, UserProfile, WaterTracker, WeightTracker};

class CommandHandler
{
    private TelegramBot $bot;
    private int         $chatId;
    private int         $userId;
    private array       $update;
    private array       $user;

    public function __construct(TelegramBot $bot, array $update, array $user)
    {
        $this->bot    = $bot;
        $this->update = $update;
        $this->user   = $user;
        $this->chatId = TelegramBot::getChatId($update);
        $this->userId = TelegramBot::getUserId($update);
    }

    public function handle(string $command, string $args = ''): void
    {
        Logger::info("Command: $command", ['user' => $this->userId]);

        match ($command) {
            '/start'       => $this->cmdStart(),
            '/help'        => $this->cmdHelp(),
            '/menu'        => $this->cmdMenu(),
            '/add'         => $this->cmdAdd($args),
            '/diary'       => $this->cmdDiary($args),
            '/water'       => $this->cmdWater(),
            '/weight'      => $this->cmdWeight(),
            '/stats'       => $this->cmdStats(),
            '/profile'     => $this->cmdProfile(),
            '/settings'    => $this->cmdSettings(),
            '/reset'       => $this->cmdReset(),
            '/myfoods'     => $this->cmdMyFoods(),
            '/streak'      => $this->cmdStreak(),
            '/achievements'=> $this->cmdAchievements(),
            '/cancel'      => $this->cmdCancel(),
            '/admin'       => $this->cmdAdmin(),
            default        => $this->cmdUnknown($command),
        };
    }

    // ── /start ────────────────────────────────────────────────────────────

    private function cmdStart(): void
    {
        $name = $this->user['first_name'];
        $isNew = !$this->user['is_setup_done'];

        if ($isNew) {
            $text = "👋 <b>Salom, {$name}!</b>\n\n"
                . "🥗 <b>iCalCalorie</b> — sizning shaxsiy ovqatlanish va kaloriya hisoblagichingiz!\n\n"
                . "Men sizga yordam beraman:\n"
                . "• 🍽 Kunlik ovqatlaringizni kuzatish\n"
                . "• 📊 Kaloriya, oqsil, yog' va karbohidratlarni hisoblash\n"
                . "• 💧 Suv iste'molini nazorat qilish\n"
                . "• ⚖️ Vazn o'zgarishlarini kuzatish\n"
                . "• 🎯 Maqsadlaringizga erishish\n\n"
                . "Keling, <b>sozlashni boshlaylik!</b> Bu 2 daqiqa vaqt oladi.";

            $this->bot->sendMessage($this->chatId, $text, [
                'reply_markup' => Keyboard::inline([[
                    Keyboard::btn('🚀 Sozlashni boshlash', 'setup:start'),
                ]]),
            ]);
        } else {
            $this->sendMainMenu("🏠 <b>Asosiy menyu</b>\n\n"
                . "Quyidagi tugmalardan birini tanlang:");
        }
    }

    // ── /help ─────────────────────────────────────────────────────────────

    private function cmdHelp(): void
    {
        $text = "📖 <b>Bot buyruqlari:</b>\n\n"
            . "🍽 <b>Kunlik taom</b>\n"
            . "/add — Taom qo'shish\n"
            . "/diary — Kunlik kundalik\n"
            . "/myfoods — Mening taomlarim\n\n"
            . "💧 <b>Suv va vazn</b>\n"
            . "/water — Suv ichish qayd etish\n"
            . "/weight — Vazn kiritish\n\n"
            . "📊 <b>Statistika</b>\n"
            . "/stats — Statistika ko'rish\n"
            . "/streak — Seriya ko'rish\n"
            . "/achievements — Yutuqlar\n\n"
            . "⚙️ <b>Profil</b>\n"
            . "/profile — Profilim\n"
            . "/settings — Sozlamalar\n"
            . "/reset — Profilni qayta sozlash\n\n"
            . "❌ /cancel — Amaliyotni bekor qilish\n\n"
            . "💡 <i>Taomni matn sifatida ham yozishingiz mumkin, masalan: \"osh 200г\"</i>";

        $this->bot->sendMessage($this->chatId, $text, [
            'reply_markup' => Keyboard::backToMain(),
        ]);
    }

    // ── /menu ─────────────────────────────────────────────────────────────

    private function cmdMenu(): void
    {
        $this->sendMainMenu();
    }

    // ── /add ──────────────────────────────────────────────────────────────

    private function cmdAdd(string $args = ''): void
    {
        StateManager::clear($this->userId);

        if (!empty($args)) {
            // Direct search: /add palov
            StateManager::set($this->userId, 'add_food:meal', 'choose_meal', [
                'query' => $args,
            ]);
            $this->bot->sendMessage(
                $this->chatId,
                "🍽 <b>«{$args}» uchun meal turini tanlang:</b>",
                ['reply_markup' => Keyboard::mealType()]
            );
        } else {
            StateManager::set($this->userId, 'add_food:search', 'query');
            $this->bot->sendMessage(
                $this->chatId,
                "🔍 <b>Taom nomini kiriting:</b>\n\n"
                . "<i>Masalan: palov, tuxum, non, olma...</i>",
                ['reply_markup' => Keyboard::cancelOnly()]
            );
        }
    }

    // ── /diary ────────────────────────────────────────────────────────────

    private function cmdDiary(string $args = ''): void
    {
        $date = (!empty($args) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $args))
            ? $args
            : date('Y-m-d');

        $this->sendDiary($date);
    }

    public function sendDiary(string $date): void
    {
        $diary    = new FoodDiary($this->userId);
        $entries  = $diary->getEntries($date);
        $totals   = $diary->getTotals($date);
        $user     = $this->user;

        $dateLabel = \App\Core\Helpers::formatDate($date);
        $text = "📋 <b>Kundalik — {$dateLabel}</b>\n\n";

        if (empty($entries)) {
            $text .= "😔 <i>Bugun hali hech narsa qo'shilmagan.</i>\n\n";
        } else {
            $meals = ['breakfast' => [], 'lunch' => [], 'dinner' => [], 'snack' => []];
            foreach ($entries as $e) $meals[$e['meal_type']][] = $e;

            foreach ($meals as $mealType => $items) {
                if (!$items) continue;
                $mealCal = array_sum(array_column($items, 'calories'));
                $text .= \App\Core\Helpers::mealLabel($mealType) . " — <b>" . round($mealCal) . " ккал</b>\n";
                foreach ($items as $item) {
                    $text .= sprintf("  • %s <i>%.0fg</i> — %.0f ккал\n",
                        \App\Core\Helpers::html($item['food_name']),
                        $item['amount_g'],
                        $item['calories']
                    );
                }
                $text .= "\n";
            }
        }

        // Summary
        $cal   = round($totals['calories'] ?? 0);
        $calGoal = $user['calorie_goal'];
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "🔥 <b>Kaloriya:</b> <b>{$cal}</b> / {$calGoal} ккал\n";
        $text .= \App\Core\Helpers::progressBar($cal, $calGoal) . "\n";
        $text .= \App\Core\Helpers::macroLine(
            round($totals['protein_g'] ?? 0),
            round($totals['fat_g'] ?? 0),
            round($totals['carbs_g'] ?? 0)
        ) . "\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= \App\Core\Helpers::calorieStatus($cal, $calGoal);

        $this->bot->sendMessage($this->chatId, $text, [
            'reply_markup' => Keyboard::diaryNav($date),
        ]);
    }

    // ── /water ────────────────────────────────────────────────────────────

    private function cmdWater(): void
    {
        $water  = new WaterTracker($this->userId);
        $today  = $water->getTodayTotal();
        $goal   = $this->user['water_goal_ml'];

        $text = "💧 <b>Bugungi suv:</b> <b>{$today} ml</b> / {$goal} ml\n\n"
            . \App\Core\Helpers::progressBar($today, $goal) . "\n\n"
            . "Necha ml suv qo'shmoqchisiz?";

        $this->bot->sendMessage($this->chatId, $text, [
            'reply_markup' => Keyboard::waterAmounts(),
        ]);
    }

    // ── /weight ───────────────────────────────────────────────────────────

    private function cmdWeight(): void
    {
        $tracker  = new WeightTracker($this->userId);
        $last     = $tracker->getLastEntry();
        $current  = $this->user['weight_kg'];
        $target   = $this->user['target_weight_kg'];

        $text = "⚖️ <b>Vazn kuzatuvi</b>\n\n";
        if ($current) {
            $text .= "📌 Joriy vazn: <b>{$current} кг</b>\n";
        }
        if ($target) {
            $diff = round($current - $target, 1);
            $arrow = $diff > 0 ? '↘' : '↗';
            $text .= "🎯 Maqsad: <b>{$target} кг</b> ({$arrow} " . abs($diff) . " кг qoldi)\n";
        }
        if ($last) {
            $text .= "\n📅 Oxirgi qayd: <b>{$last['weight_kg']} кг</b> ({$last['log_date']})\n";
        }

        $text .= "\n✏️ <b>Bugungi vazningizni kiriting (кг):</b>\n<i>Masalan: 72.5</i>";

        StateManager::set($this->userId, 'add_weight');
        $this->bot->sendMessage($this->chatId, $text, [
            'reply_markup' => Keyboard::cancelOnly(),
        ]);
    }

    // ── /stats ────────────────────────────────────────────────────────────

    private function cmdStats(): void
    {
        $this->bot->sendMessage(
            $this->chatId,
            "📊 <b>Statistika</b>\n\nQaysi davrni ko'rmoqchisiz?",
            ['reply_markup' => Keyboard::statsPeriod()]
        );
    }

    // ── /profile ──────────────────────────────────────────────────────────

    private function cmdProfile(): void
    {
        $u   = $this->user;
        $bmi = \App\Core\Helpers::bmi((float)$u['weight_kg'], (float)$u['height_cm']);

        $actLabels = [
            'sedentary'  => 'Harakatsiz',
            'light'      => 'Oz harakatli',
            'moderate'   => 'O\'rtacha',
            'active'     => 'Faol',
            'very_active'=> 'Juda faol',
        ];

        $goalLabels = [
            'lose'     => '📉 Vazn yo\'qotish',
            'maintain' => '⚖️ Saqlash',
            'gain'     => '📈 Olish',
        ];

        $text = "👤 <b>Mening profilim</b>\n\n"
            . "👋 Ism: <b>" . \App\Core\Helpers::html($u['first_name']) . "</b>\n"
            . "⚧ Jins: <b>" . ($u['gender'] === 'male' ? '👨 Erkak' : '👩 Ayol') . "</b>\n"
            . "🎂 Yosh: <b>" . ($u['age'] ?? '—') . "</b>\n"
            . "📏 Bo'y: <b>" . ($u['height_cm'] ? $u['height_cm'] . ' см' : '—') . "</b>\n"
            . "⚖️ Vazn: <b>" . ($u['weight_kg'] ? $u['weight_kg'] . ' кг' : '—') . "</b>\n"
            . "🎯 Maqsad vazn: <b>" . ($u['target_weight_kg'] ? $u['target_weight_kg'] . ' кг' : '—') . "</b>\n"
            . "💪 Faollik: <b>" . ($actLabels[$u['activity_level']] ?? '—') . "</b>\n"
            . "🏁 Maqsad: <b>" . ($goalLabels[$u['goal']] ?? '—') . "</b>\n\n"
            . "━━━━━━━━━━━━━━━━━\n"
            . "🔥 Kaloriya maqsadi: <b>{$u['calorie_goal']} ккал</b>\n"
            . "🥩 Oqsil: <b>{$u['protein_goal_g']} г</b>\n"
            . "🧈 Yog': <b>{$u['fat_goal_g']} г</b>\n"
            . "🍞 Karbohidrat: <b>{$u['carbs_goal_g']} г</b>\n"
            . "💧 Suv: <b>{$u['water_goal_ml']} мл</b>\n";

        if ($bmi > 0) {
            $text .= "\n📊 BMI: <b>{$bmi}</b> — " . \App\Core\Helpers::bmiCategory($bmi);
        }

        $this->bot->sendMessage($this->chatId, $text, [
            'reply_markup' => Keyboard::inline([
                [Keyboard::btn('✏️ Profilni tahrirlash', 'setup:start')],
                [Keyboard::btn('⬅️ Orqaga', 'main_menu')],
            ]),
        ]);
    }

    // ── /settings ─────────────────────────────────────────────────────────

    private function cmdSettings(): void
    {
        $this->bot->sendMessage(
            $this->chatId,
            "⚙️ <b>Sozlamalar</b>",
            ['reply_markup' => Keyboard::settingsMenu()]
        );
    }

    // ── /reset ────────────────────────────────────────────────────────────

    private function cmdReset(): void
    {
        $this->bot->sendMessage(
            $this->chatId,
            "⚠️ <b>Profilni qayta sozlash</b>\n\n"
            . "Bu amal barcha profil ma'lumotlaringizni (jins, yosh, bo'y, vazn, maqsad) qayta sozlaydi.\n"
            . "Kundalik va statistika saqlanib qoladi.\n\n"
            . "<b>Davom etasizmi?</b>",
            ['reply_markup' => Keyboard::confirm('setup:start', 'cancel')]
        );
    }

    // ── /myfoods ──────────────────────────────────────────────────────────

    private function cmdMyFoods(): void
    {
        $foods = Database::fetchAll(
            "SELECT * FROM custom_foods WHERE user_id = ? ORDER BY use_count DESC LIMIT 20",
            [$this->userId]
        );

        if (empty($foods)) {
            $this->bot->sendMessage(
                $this->chatId,
                "🍽 <b>Mening taomlarim</b>\n\n"
                . "Siz hali hech qanday shaxsiy taom qo'shmagansiz.\n\n"
                . "/add buyrug'i orqali taom qo'shayotganingizda «✏️ O'z taom qo'shish» tugmasini bosing.",
                ['reply_markup' => Keyboard::backToMain()]
            );
            return;
        }

        $text = "🍽 <b>Mening taomlarim ({" . count($foods) . "} ta):</b>\n\n";
        foreach ($foods as $f) {
            $text .= sprintf("• <b>%s</b> — %.0f ккал/100г\n",
                \App\Core\Helpers::html($f['name']), $f['calories']);
        }

        $this->bot->sendMessage($this->chatId, $text, [
            'reply_markup' => Keyboard::backToMain(),
        ]);
    }

    // ── /streak ───────────────────────────────────────────────────────────

    private function cmdStreak(): void
    {
        $streak = Database::fetchOne(
            "SELECT * FROM user_streaks WHERE user_id = ?",
            [$this->userId]
        );

        $current = $streak['current_streak']    ?? 0;
        $longest = $streak['longest_streak']    ?? 0;
        $total   = $streak['total_active_days'] ?? 0;
        $emoji   = \App\Core\Helpers::streakEmoji($current);

        $text = "🔥 <b>Sizning seriyangiz</b>\n\n"
            . "{$emoji} Joriy seriya: <b>{$current} kun</b>\n"
            . "🏆 Eng uzun seriya: <b>{$longest} kun</b>\n"
            . "📅 Jami faol kunlar: <b>{$total} kun</b>\n\n";

        if ($current === 0) {
            $text .= "💡 Bugun taom qo'shing va seriyani boshlang!";
        } elseif ($current < 7) {
            $remaining = 7 - $current;
            $text .= "💡 1 haftalik seriya uchun yana {$remaining} kun!";
        } elseif ($current < 30) {
            $remaining = 30 - $current;
            $text .= "💡 30 kunlik seriya uchun yana {$remaining} kun!";
        } else {
            $text .= "🌟 Ajoyib! Davom eting!";
        }

        $this->bot->sendMessage($this->chatId, $text, [
            'reply_markup' => Keyboard::backToMain(),
        ]);
    }

    // ── /achievements ─────────────────────────────────────────────────────

    private function cmdAchievements(): void
    {
        $earned = Database::fetchAll(
            "SELECT a.icon, a.title, a.description, ua.earned_at
             FROM user_achievements ua
             JOIN achievements a ON a.id = ua.achievement_id
             WHERE ua.user_id = ?
             ORDER BY ua.earned_at DESC",
            [$this->userId]
        );

        $all = Database::fetchAll("SELECT * FROM achievements ORDER BY condition_value");
        $earnedIds = array_column($earned, 'title');

        $text = "🏅 <b>Yutuqlar</b>\n\n";

        if (empty($earned)) {
            $text .= "Hali yutuq yo'q. Botdan foydalanishni davom eting!\n\n";
        } else {
            $text .= "✅ <b>Erishilgan ({" . count($earned) . "} ta):</b>\n";
            foreach ($earned as $a) {
                $text .= "{$a['icon']} <b>{$a['title']}</b> — {$a['description']}\n";
            }
            $text .= "\n";
        }

        $locked = array_filter($all, fn($a) => !in_array($a['title'], $earnedIds));
        if ($locked) {
            $text .= "🔒 <b>Qulflangan:</b>\n";
            foreach ($locked as $a) {
                $text .= "  • {$a['icon']} {$a['title']}\n";
            }
        }

        $this->bot->sendMessage($this->chatId, $text, [
            'reply_markup' => Keyboard::backToMain(),
        ]);
    }

    // ── /cancel ───────────────────────────────────────────────────────────

    private function cmdCancel(): void
    {
        StateManager::clear($this->userId);
        $this->bot->sendMessage(
            $this->chatId,
            "❌ <b>Bekor qilindi.</b>",
            ['reply_markup' => Keyboard::mainMenu()]
        );
    }

    // ── /admin ────────────────────────────────────────────────────────────

    private function cmdAdmin(): void
    {
        if (!Config::isAdmin($this->userId)) {
            $this->bot->sendMessage($this->chatId, "🚫 Ruxsat yo'q.");
            return;
        }

        $totalUsers  = Database::fetchColumn("SELECT COUNT(*) FROM users");
        $todayActive = Database::fetchColumn(
            "SELECT COUNT(DISTINCT user_id) FROM diary_entries WHERE entry_date = CURDATE()"
        );
        $totalEntries = Database::fetchColumn("SELECT COUNT(*) FROM diary_entries");
        $totalFoods   = Database::fetchColumn("SELECT COUNT(*) FROM foods");

        $text = "🛡 <b>Admin panel</b>\n\n"
            . "👥 Jami foydalanuvchilar: <b>{$totalUsers}</b>\n"
            . "📅 Bugun faol: <b>{$todayActive}</b>\n"
            . "📝 Jami yozuvlar: <b>{$totalEntries}</b>\n"
            . "🍽 Taomlar bazasi: <b>{$totalFoods}</b>\n\n"
            . "🌐 <a href=\"" . Config::get('app_url') . "/admin\">Admin panel ochish</a>";

        $this->bot->sendMessage($this->chatId, $text, [
            'reply_markup' => Keyboard::inline([
                [Keyboard::btn('📢 Xabar yuborish', 'admin:broadcast')],
                [Keyboard::btn('📊 Statistika', 'admin:stats')],
            ]),
        ]);
    }

    // ── Unknown command ────────────────────────────────────────────────────

    private function cmdUnknown(string $cmd): void
    {
        $this->bot->sendMessage(
            $this->chatId,
            "❓ Noma'lum buyruq: <code>{$cmd}</code>\n\n/help — barcha buyruqlarni ko'rish",
            ['reply_markup' => Keyboard::mainMenu()]
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function sendMainMenu(string $text = ''): void
    {
        $user  = $this->user;
        $today = date('Y-m-d');

        // Today's summary
        $diary   = new FoodDiary($user['id']);
        $totals  = $diary->getTotals($today);
        $cal     = round($totals['calories'] ?? 0);
        $calGoal = $user['calorie_goal'];

        $water    = new WaterTracker($user['id']);
        $waterMl  = $water->getTodayTotal();
        $waterGoal= $user['water_goal_ml'];

        $streak  = Database::fetchOne(
            "SELECT current_streak FROM user_streaks WHERE user_id = ?",
            [$user['id']]
        );
        $currentStreak = $streak['current_streak'] ?? 0;

        if (!$text) {
            $text = "🏠 <b>Asosiy menyu</b>\n";
        }

        $text .= "\n"
            . "━━━━━━━━━━━━━━━━━\n"
            . "🔥 Bugun: <b>{$cal}</b>/{$calGoal} ккал "
            . \App\Core\Helpers::progressBar($cal, $calGoal, 6) . "\n"
            . "💧 Suv: <b>{$waterMl}</b>/{$waterGoal} мл "
            . \App\Core\Helpers::progressBar($waterMl, $waterGoal, 6) . "\n"
            . "⚡ Seriya: <b>{$currentStreak} kun</b>\n"
            . "━━━━━━━━━━━━━━━━━";

        $this->bot->sendMessage($this->chatId, $text, [
            'reply_markup' => Keyboard::mainMenu(),
        ]);
    }
}
