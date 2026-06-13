<?php

namespace App\Core;

class Helpers
{
    /**
     * Format a number with thousands separator
     */
    public static function num(float $value, int $decimals = 0): string
    {
        return number_format($value, $decimals, '.', ' ');
    }

    /**
     * Build a visual progress bar.
     * e.g.  ██████░░░░  62%
     */
    public static function progressBar(float $current, float $target, int $bars = 10): string
    {
        if ($target <= 0) return str_repeat('░', $bars) . ' 0%';
        $pct      = min(100, ($current / $target) * 100);
        $filled   = (int) round(($pct / 100) * $bars);
        $empty    = $bars - $filled;
        $color    = $pct >= 100 ? '🔴' : ($pct >= 80 ? '🟡' : '🟢');
        return $color . ' ' . str_repeat('█', $filled) . str_repeat('░', $empty) . ' ' . round($pct) . '%';
    }

    /**
     * Format macros line: 🥩 P:82г  🧈 F:45г  🍞 C:210г
     */
    public static function macroLine(float $p, float $f, float $c): string
    {
        return sprintf(
            "🥩 Oq: <b>%.1fg</b>  🧈 Yog': <b>%.1fг</b>  🍞 Karbo: <b>%.1fg</b>",
            $p, $f, $c
        );
    }

    /**
     * Validate a gram amount entered by user (1–5000)
     */
    public static function parseGrams(string $input): ?float
    {
        $input = str_replace(',', '.', trim($input));
        if (!is_numeric($input)) return null;
        $val = (float) $input;
        return ($val > 0 && $val <= 5000) ? $val : null;
    }

    /**
     * Validate weight in kg (20–500)
     */
    public static function parseWeight(string $input): ?float
    {
        $input = str_replace(',', '.', trim($input));
        if (!is_numeric($input)) return null;
        $val = (float) $input;
        return ($val >= 20 && $val <= 500) ? round($val, 1) : null;
    }

    /**
     * Calculate BMI
     */
    public static function bmi(float $weightKg, float $heightCm): float
    {
        if ($heightCm <= 0) return 0;
        $heightM = $heightCm / 100;
        return round($weightKg / ($heightM ** 2), 1);
    }

    /**
     * BMI category text
     */
    public static function bmiCategory(float $bmi): string
    {
        if ($bmi < 18.5) return '📉 Tanqis vazn';
        if ($bmi < 25.0) return '✅ Normal vazn';
        if ($bmi < 30.0) return '⚠️ Ortiqcha vazn';
        return '🔴 Semirish';
    }

    /**
     * Calculate TDEE (Total Daily Energy Expenditure) via Mifflin-St Jeor.
     */
    public static function calculateTDEE(
        string $gender,
        int    $age,
        float  $heightCm,
        float  $weightKg,
        string $activityLevel
    ): int {
        // BMR
        if ($gender === 'male') {
            $bmr = 10 * $weightKg + 6.25 * $heightCm - 5 * $age + 5;
        } else {
            $bmr = 10 * $weightKg + 6.25 * $heightCm - 5 * $age - 161;
        }

        $multipliers = [
            'sedentary'  => 1.2,
            'light'      => 1.375,
            'moderate'   => 1.55,
            'active'     => 1.725,
            'very_active'=> 1.9,
        ];

        $tdee = $bmr * ($multipliers[$activityLevel] ?? 1.55);

        return (int) round($tdee);
    }

    /**
     * Adjust TDEE for goal (lose / maintain / gain)
     */
    public static function adjustForGoal(int $tdee, string $goal): int
    {
        return match ($goal) {
            'lose'     => max(1200, $tdee - 500),
            'gain'     => $tdee + 300,
            default    => $tdee,
        };
    }

    /**
     * Calculate macros from calorie goal:
     * Protein: 30%, Fat: 25%, Carbs: 45%
     */
    public static function calculateMacros(int $calories, string $goal): array
    {
        $proteinPct = $goal === 'gain' ? 0.30 : ($goal === 'lose' ? 0.35 : 0.30);
        $fatPct     = 0.25;
        $carbsPct   = 1 - $proteinPct - $fatPct;

        return [
            'protein' => (int) round(($calories * $proteinPct) / 4),
            'fat'     => (int) round(($calories * $fatPct) / 9),
            'carbs'   => (int) round(($calories * $carbsPct) / 4),
        ];
    }

    /**
     * Calculate ideal water intake in ml (30ml per kg)
     */
    public static function calculateWaterGoal(float $weightKg, string $activityLevel): int
    {
        $base = $weightKg * 30;
        $extra = match ($activityLevel) {
            'active', 'very_active' => 500,
            'moderate'              => 250,
            default                 => 0,
        };
        return (int) round(min(4000, max(1500, $base + $extra)));
    }

    /**
     * Format date for display (today/yesterday/date)
     */
    public static function formatDate(string $date): string
    {
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        if ($date === $today)     return '📅 Bugun';
        if ($date === $yesterday) return '📅 Kecha';
        return '📅 ' . date('d.m.Y', strtotime($date));
    }

    /**
     * Meal type to emoji + label
     */
    public static function mealLabel(string $mealType): string
    {
        return match ($mealType) {
            'breakfast' => '🌅 Nonushta',
            'lunch'     => '☀️ Tushlik',
            'dinner'    => '🌙 Kechki ovqat',
            'snack'     => '🍎 Перекуc',
            default     => '🍽 Taom',
        };
    }

    /**
     * Escape HTML special chars for Telegram HTML parse mode
     */
    public static function html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Format calories with emoji based on remaining
     */
    public static function calorieStatus(float $consumed, float $goal): string
    {
        $remaining = $goal - $consumed;
        if ($remaining < 0)   return sprintf('🔴 +%.0f kcal oshib ketdi', abs($remaining));
        if ($remaining < 200) return sprintf('🟡 %.0f kcal qoldi', $remaining);
        return sprintf('🟢 %.0f kcal qoldi', $remaining);
    }

    /**
     * Streak emoji
     */
    public static function streakEmoji(int $streak): string
    {
        if ($streak >= 30) return '🔥🔥🔥';
        if ($streak >= 14) return '🔥🔥';
        if ($streak >= 7)  return '🔥';
        if ($streak >= 3)  return '⚡';
        return '✨';
    }

    /**
     * Truncate string
     */
    public static function truncate(string $text, int $max = 30): string
    {
        return mb_strlen($text) <= $max ? $text : mb_substr($text, 0, $max - 1) . '…';
    }
}
