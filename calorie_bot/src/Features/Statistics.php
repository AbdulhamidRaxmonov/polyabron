<?php

namespace App\Features;

use App\Core\{Database, Helpers};

class Statistics
{
    private int   $userId;
    private array $user;

    public function __construct(int $userId, array $user)
    {
        $this->userId = $userId;
        $this->user   = $user;
    }

    /**
     * Generate a text statistics report for a given period.
     * Period: today | week | month | all
     */
    public function generateReport(string $period): string
    {
        [$from, $to, $label] = $this->periodDates($period);

        $diary   = new FoodDiary($this->userId);
        $water   = new WaterTracker($this->userId);
        $weight  = new WeightTracker($this->userId);

        // ── Calories / Macros ─────────────────────────────────────────────
        if ($period === 'today') {
            $totals = $diary->getTotals($from);
            $cal    = round($totals['calories']);
            $calGoal= $this->user['calorie_goal'];

            $text  = "📊 <b>Bugungi statistika</b>\n\n";
            $text .= "🔥 <b>Kaloriya:</b> <b>{$cal}</b> / {$calGoal} ккал\n";
            $text .= Helpers::progressBar($cal, $calGoal) . "\n\n";
            $text .= "🥩 Oqsil: <b>" . round($totals['protein_g']) . "г</b> / {$this->user['protein_goal_g']}г\n";
            $text .= Helpers::progressBar($totals['protein_g'], $this->user['protein_goal_g']) . "\n";
            $text .= "🧈 Yog': <b>" . round($totals['fat_g']) . "г</b> / {$this->user['fat_goal_g']}г\n";
            $text .= Helpers::progressBar($totals['fat_g'], $this->user['fat_goal_g']) . "\n";
            $text .= "🍞 Karbohidrat: <b>" . round($totals['carbs_g']) . "г</b> / {$this->user['carbs_goal_g']}г\n";
            $text .= Helpers::progressBar($totals['carbs_g'], $this->user['carbs_goal_g']) . "\n\n";

            // Water
            $waterToday = $water->getTodayTotal();
            $waterGoal  = $this->user['water_goal_ml'];
            $text .= "💧 <b>Suv:</b> <b>{$waterToday} мл</b> / {$waterGoal} мл\n";
            $text .= Helpers::progressBar($waterToday, $waterGoal) . "\n\n";

            // Meal breakdown
            $byMeal = $diary->getTotalsByMeal($from);
            if ($byMeal) {
                $text .= "🍽 <b>Ovqat bo'yicha:</b>\n";
                $mealOrder = ['breakfast','lunch','dinner','snack'];
                foreach ($mealOrder as $mt) {
                    if (!isset($byMeal[$mt])) continue;
                    $mCal = round($byMeal[$mt]['calories']);
                    $text .= "  " . Helpers::mealLabel($mt) . ": <b>{$mCal} ккал</b>\n";
                }
            }
        } else {
            $dailyRows   = $diary->getDailyTotals($from, $to);
            $activeDays  = count($dailyRows);
            $totalDays   = max(1, (int)(strtotime($to) - strtotime($from)) / 86400 + 1);

            $totalCal    = array_sum(array_column($dailyRows, 'calories'));
            $totalProt   = array_sum(array_column($dailyRows, 'protein_g'));
            $totalFat    = array_sum(array_column($dailyRows, 'fat_g'));
            $totalCarb   = array_sum(array_column($dailyRows, 'carbs_g'));

            $avgCal  = $activeDays > 0 ? round($totalCal / $activeDays) : 0;
            $avgProt = $activeDays > 0 ? round($totalProt / $activeDays) : 0;
            $avgFat  = $activeDays > 0 ? round($totalFat / $activeDays) : 0;
            $avgCarb = $activeDays > 0 ? round($totalCarb / $activeDays) : 0;

            $calGoal = $this->user['calorie_goal'];

            $text  = "📊 <b>Statistika — {$label}</b>\n\n";
            $text .= "📅 Faol kunlar: <b>{$activeDays}</b> / {$totalDays}\n\n";

            $text .= "🔥 <b>O'rtacha kunlik kaloriya: {$avgCal} ккал</b>\n";
            $text .= Helpers::progressBar($avgCal, $calGoal) . "\n\n";

            $text .= "📈 <b>O'rtacha makrolar:</b>\n";
            $text .= "  🥩 Oqsil: <b>{$avgProt}г</b>\n";
            $text .= "  🧈 Yog': <b>{$avgFat}г</b>\n";
            $text .= "  🍞 Karbo: <b>{$avgCarb}г</b>\n\n";

            // Calorie goal compliance
            $daysOnGoal = 0;
            foreach ($dailyRows as $row) {
                if (abs($row['calories'] - $calGoal) / $calGoal <= 0.1) $daysOnGoal++;
            }
            $compliance = $activeDays > 0 ? round($daysOnGoal / $activeDays * 100) : 0;
            $text .= "🎯 Maqsadga mos kunlar: <b>{$daysOnGoal}</b> ({$compliance}%)\n\n";

            // Weight trend
            $weightHistory = $weight->getHistory(
                $period === 'week' ? 7 : ($period === 'month' ? 30 : 365)
            );
            if (count($weightHistory) >= 2) {
                $first   = (float) $weightHistory[0]['weight_kg'];
                $last    = (float) end($weightHistory)['weight_kg'];
                $change  = round($last - $first, 1);
                $arrow   = $change > 0 ? '↗ +' : '↘ ';
                $text   .= "⚖️ <b>Vazn o'zgarishi:</b> {$arrow}{$change} кг\n";

                $spark = $weight->sparkline(count($weightHistory));
                if ($spark) $text .= "  <code>{$spark}</code>\n";
                $text .= "\n";
            }

            // Water average
            $waterRows = $water->getDailyTotals($from, $to);
            if ($waterRows) {
                $avgWater = round(array_sum(array_column($waterRows, 'total_ml')) / count($waterRows));
                $text .= "💧 O'rtacha suv: <b>{$avgWater} мл</b> / {$this->user['water_goal_ml']} мл\n";
            }

            // Top foods
            $topFoods = $this->getTopFoodsForPeriod($from, $to, 3);
            if ($topFoods) {
                $text .= "\n🏆 <b>Ko'p iste'mol qilingan taomlar:</b>\n";
                foreach ($topFoods as $i => $f) {
                    $text .= ($i + 1) . ". " . Helpers::html($f['food_name']) . " ({$f['times']} marta)\n";
                }
            }
        }

        // Streak
        $streak = Database::fetchOne(
            "SELECT current_streak, longest_streak FROM user_streaks WHERE user_id = ?",
            [$this->userId]
        );
        if ($streak && $streak['current_streak'] > 0) {
            $emoji = Helpers::streakEmoji($streak['current_streak']);
            $text .= "\n{$emoji} Seriya: <b>{$streak['current_streak']} kun</b>";
        }

        return $text;
    }

    // ── Admin-level stats ─────────────────────────────────────────────────

    public static function globalStats(): array
    {
        return [
            'total_users'    => Database::fetchColumn("SELECT COUNT(*) FROM users"),
            'active_today'   => Database::fetchColumn(
                "SELECT COUNT(DISTINCT user_id) FROM diary_entries WHERE entry_date = CURDATE()"
            ),
            'active_week'    => Database::fetchColumn(
                "SELECT COUNT(DISTINCT user_id) FROM diary_entries WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
            ),
            'total_entries'  => Database::fetchColumn("SELECT COUNT(*) FROM diary_entries"),
            'total_foods'    => Database::fetchColumn("SELECT COUNT(*) FROM foods WHERE is_verified = 1"),
            'total_water_l'  => round(Database::fetchColumn("SELECT COALESCE(SUM(amount_ml),0)/1000 FROM water_logs"), 1),
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function periodDates(string $period): array
    {
        $today = date('Y-m-d');

        return match ($period) {
            'today'  => [$today,                              $today,              'Bugun'],
            'week'   => [date('Y-m-d', strtotime('-6 days')), $today,              'Bu hafta (7 kun)'],
            'month'  => [date('Y-m-d', strtotime('-29 days')),$today,              'Bu oy (30 kun)'],
            'all'    => ['2000-01-01',                        $today,              'Umumiy'],
            default  => [$today,                              $today,              'Bugun'],
        };
    }

    private function getTopFoodsForPeriod(string $from, string $to, int $limit): array
    {
        return Database::fetchAll(
            "SELECT food_name, COUNT(*) AS times
             FROM diary_entries
             WHERE user_id = ? AND entry_date BETWEEN ? AND ?
             GROUP BY food_name
             ORDER BY times DESC
             LIMIT ?",
            [$this->userId, $from, $to, $limit]
        );
    }
}
