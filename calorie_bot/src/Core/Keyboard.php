<?php

namespace App\Core;

/**
 * Helper for building Telegram inline and reply keyboards.
 */
class Keyboard
{
    // ── Inline Keyboards ─────────────────────────────────────────────────

    public static function inline(array $rows): array
    {
        return ['inline_keyboard' => $rows];
    }

    /** Build inline button */
    public static function btn(string $text, string $callbackData): array
    {
        return ['text' => $text, 'callback_data' => $callbackData];
    }

    /** URL button */
    public static function urlBtn(string $text, string $url): array
    {
        return ['text' => $text, 'url' => $url];
    }

    // ── Reply Keyboards ──────────────────────────────────────────────────

    public static function reply(array $rows, bool $resize = true, bool $oneTime = false): array
    {
        return [
            'keyboard'          => $rows,
            'resize_keyboard'   => $resize,
            'one_time_keyboard' => $oneTime,
        ];
    }

    public static function remove(): array
    {
        return ['remove_keyboard' => true];
    }

    // ── Ready-made Keyboards ─────────────────────────────────────────────

    /** Main menu reply keyboard */
    public static function mainMenu(): array
    {
        return self::reply([
            ['🍽 Kunlik taom', '💧 Suv'],
            ['📊 Statistika',  '⚖️ Vazn'],
            ['🔍 Qidirish',    '⚙️ Sozlamalar'],
        ]);
    }

    /** Meal type selector */
    public static function mealType(): array
    {
        return self::inline([
            [
                self::btn('🌅 Nonushta',  'meal:breakfast'),
                self::btn('☀️ Tushlik',   'meal:lunch'),
            ],
            [
                self::btn('🌙 Kechki ovqat', 'meal:dinner'),
                self::btn('🍎 Perекус',   'meal:snack'),
            ],
            [self::btn('❌ Bekor qilish', 'cancel')],
        ]);
    }

    /** Water amount quick-select */
    public static function waterAmounts(): array
    {
        return self::inline([
            [
                self::btn('🥛 100 ml', 'water:100'),
                self::btn('🥛 200 ml', 'water:200'),
                self::btn('🥛 250 ml', 'water:250'),
            ],
            [
                self::btn('🥛 300 ml', 'water:300'),
                self::btn('🥛 400 ml', 'water:400'),
                self::btn('🥛 500 ml', 'water:500'),
            ],
            [
                self::btn('💧 Boshqa', 'water:custom'),
                self::btn('❌ Bekor',  'cancel'),
            ],
        ]);
    }

    /** Diary date navigation */
    public static function diaryNav(string $date): array
    {
        $prev = date('Y-m-d', strtotime($date . ' -1 day'));
        $next = date('Y-m-d', strtotime($date . ' +1 day'));
        $today = date('Y-m-d');

        $nextBtn = ($next <= $today)
            ? [self::btn('▶️', "diary:$next")]
            : [self::btn('▶️', 'noop')];

        return self::inline([
            array_merge(
                [self::btn('◀️', "diary:$prev")],
                [self::btn('📅 Bugun', 'diary:today')],
                $nextBtn
            ),
            [
                self::btn('➕ Taom qo\'shish', "addentry:$date"),
                self::btn('🔄 Yangilash', "diary:$date"),
            ],
        ]);
    }

    /** Settings menu */
    public static function settingsMenu(): array
    {
        return self::inline([
            [self::btn('🎯 Kaloriya maqsadi', 'settings:goal')],
            [self::btn('👤 Profil ma\'lumotlari', 'settings:profile')],
            [self::btn('💧 Suv maqsadi', 'settings:water_goal')],
            [self::btn('🔔 Bildirishnomalar', 'settings:notifications')],
            [self::btn('🌍 Til / Language', 'settings:language')],
            [self::btn('⬅️ Orqaga', 'main_menu')],
        ]);
    }

    /** Statistics period selector */
    public static function statsPeriod(): array
    {
        return self::inline([
            [
                self::btn('📅 Bugun',    'stats:today'),
                self::btn('📆 Bu hafta', 'stats:week'),
            ],
            [
                self::btn('🗓 Bu oy',    'stats:month'),
                self::btn('📊 Umumiy',   'stats:all'),
            ],
            [self::btn('⬅️ Orqaga', 'main_menu')],
        ]);
    }

    /** Confirm / Cancel pair */
    public static function confirm(string $confirmData, string $cancelData = 'cancel'): array
    {
        return self::inline([
            [
                self::btn('✅ Ha', $confirmData),
                self::btn('❌ Yo\'q', $cancelData),
            ],
        ]);
    }

    /** Food search results list */
    public static function foodSearchResults(array $foods, string $mealType, int $page = 0, int $total = 0): array
    {
        $rows = [];
        foreach ($foods as $food) {
            $rows[] = [self::btn(
                sprintf('%s — %s ккал/100г', $food['name_ru'], (int)$food['calories']),
                "food_select:{$food['id']}:{$mealType}"
            )];
        }

        $nav = [];
        if ($page > 0) {
            $nav[] = self::btn('◀️ Oldingi', "food_page:{$page}:{$mealType}");
        }
        if (count($foods) === 5 && ($page + 1) * 5 < $total) {
            $nav[] = self::btn('Keyingi ▶️', 'food_page:' . ($page + 1) . ":$mealType");
        }
        if ($nav) $rows[] = $nav;

        $rows[] = [
            self::btn('✏️ O\'z taom qo\'shish', "food_custom:$mealType"),
            self::btn('❌ Bekor', 'cancel'),
        ];

        return self::inline($rows);
    }

    /** Diary entry delete button */
    public static function entryActions(int $entryId, string $date): array
    {
        return self::inline([
            [
                self::btn('🗑 O\'chirish', "del_entry:$entryId:$date"),
                self::btn('⬅️ Orqaga', "diary:$date"),
            ],
        ]);
    }

    /** Goal / activity selection */
    public static function activityLevel(): array
    {
        return self::inline([
            [self::btn('🛋 Harakatsiz (stol ishi)', 'activity:sedentary')],
            [self::btn('🚶 Oz harakatli (1-3 kun/hafta)', 'activity:light')],
            [self::btn('🏃 O\'rtacha faol (3-5 kun/hafta)', 'activity:moderate')],
            [self::btn('💪 Juda faol (6-7 kun/hafta)', 'activity:active')],
            [self::btn('🏋️ Professional sport', 'activity:very_active')],
        ]);
    }

    public static function goalType(): array
    {
        return self::inline([
            [self::btn('📉 Vazn yo\'qotish', 'goal:lose')],
            [self::btn('⚖️ Vaznni saqlash', 'goal:maintain')],
            [self::btn('📈 Vazn olish', 'goal:gain')],
        ]);
    }

    public static function genderSelect(): array
    {
        return self::inline([
            [
                self::btn('👨 Erkak', 'gender:male'),
                self::btn('👩 Ayol', 'gender:female'),
            ],
        ]);
    }

    public static function cancelOnly(): array
    {
        return self::inline([[self::btn('❌ Bekor qilish', 'cancel')]]);
    }

    public static function backToMain(): array
    {
        return self::inline([[self::btn('⬅️ Asosiy menyu', 'main_menu')]]);
    }

    public static function notificationSettings(array $user): array
    {
        return self::inline([
            [self::btn(
                ($user['notify_morning'] ? '✅' : '❌') . ' Ertalabki eslatma',
                'notify:morning'
            )],
            [self::btn(
                ($user['notify_evening'] ? '✅' : '❌') . ' Kechki eslatma',
                'notify:evening'
            )],
            [self::btn(
                ($user['notify_water'] ? '✅' : '❌') . ' Suv eslatmasi',
                'notify:water'
            )],
            [self::btn('⬅️ Orqaga', 'settings:menu')],
        ]);
    }
}
