<?php

namespace App\Features;

use App\Core\Database;

/**
 * Checks and awards achievements after diary / water / weight events.
 * Returns array of newly-earned achievement rows (to notify user).
 */
class AchievementChecker
{
    private int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Run all checks. Returns list of newly-earned achievements.
     */
    public function check(): array
    {
        $allAchievements = Database::fetchAll("SELECT * FROM achievements");
        if (!$allAchievements) return [];

        $earned = Database::fetchAll(
            "SELECT achievement_id FROM user_achievements WHERE user_id = ?",
            [$this->userId]
        );
        $earnedIds = array_column($earned, 'achievement_id');

        $newlyEarned = [];

        foreach ($allAchievements as $ach) {
            if (in_array($ach['id'], $earnedIds, true)) continue;

            if ($this->meetsCondition($ach)) {
                Database::insert('user_achievements', [
                    'user_id'        => $this->userId,
                    'achievement_id' => $ach['id'],
                ]);
                $newlyEarned[] = $ach;
            }
        }

        return $newlyEarned;
    }

    private function meetsCondition(array $ach): bool
    {
        $val = (int) $ach['condition_value'];

        return match ($ach['condition_type']) {
            'streak' => $this->getCurrentStreak() >= $val,
            'total_days' => $this->getTotalActiveDays() >= $val,
            'entries_count' => $this->getTotalEntries() >= $val,
            'weight_lost' => $this->getWeightLost() >= $val,
            default => false,
        };
    }

    private function getCurrentStreak(): int
    {
        return (int) Database::fetchColumn(
            "SELECT current_streak FROM user_streaks WHERE user_id = ?",
            [$this->userId]
        );
    }

    private function getTotalActiveDays(): int
    {
        return (int) Database::fetchColumn(
            "SELECT COUNT(DISTINCT entry_date) FROM diary_entries WHERE user_id = ?",
            [$this->userId]
        );
    }

    private function getTotalEntries(): int
    {
        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM diary_entries WHERE user_id = ?",
            [$this->userId]
        );
    }

    private function getWeightLost(): float
    {
        $first = Database::fetchColumn(
            "SELECT weight_kg FROM weight_logs WHERE user_id = ? ORDER BY log_date ASC LIMIT 1",
            [$this->userId]
        );
        $last = Database::fetchColumn(
            "SELECT weight_kg FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 1",
            [$this->userId]
        );

        if (!$first || !$last) return 0;
        return max(0, round((float)$first - (float)$last, 1));
    }
}
