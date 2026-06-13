<?php

namespace App\Features;

use App\Core\{Database, Helpers};

class UserProfile
{
    private int   $telegramId;
    private array $user;

    public function __construct(int $telegramId, array $user = [])
    {
        $this->telegramId = $telegramId;
        $this->user       = $user;
    }

    /**
     * Find user by telegram_id. Returns null if not found.
     */
    public static function findByTelegramId(int $telegramId): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM users WHERE telegram_id = ?",
            [$telegramId]
        );
    }

    /**
     * Register a new user from a Telegram `from` object.
     */
    public static function createFromTelegram(array $from): array
    {
        $telegramId = (int) $from['id'];
        $existing   = self::findByTelegramId($telegramId);
        if ($existing) return $existing;

        $id = Database::insert('users', [
            'telegram_id'   => $telegramId,
            'username'      => $from['username'] ?? null,
            'first_name'    => $from['first_name'] ?? '',
            'last_name'     => $from['last_name']  ?? null,
            'language_code' => $from['language_code'] ?? 'ru',
            'calorie_goal'  => 2000,
            'protein_goal_g'=> 150,
            'fat_goal_g'    => 65,
            'carbs_goal_g'  => 250,
            'water_goal_ml' => 2000,
        ]);

        // Create empty streak row
        Database::insert('user_streaks', ['user_id' => $id]);

        return Database::fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
    }

    /**
     * Save all data collected during onboarding wizard.
     */
    public function saveSetupData(array $data): void
    {
        $userId = $this->getDbId();
        if (!$userId) return;

        $gender     = $data['gender']         ?? null;
        $age        = isset($data['age'])      ? (int)$data['age']    : null;
        $height     = isset($data['height_cm'])? (int)$data['height_cm'] : null;
        $weight     = isset($data['weight_kg'])? (float)$data['weight_kg'] : null;
        $target     = isset($data['target_weight_kg']) ? (float)$data['target_weight_kg'] : null;
        $activity   = $data['activity_level'] ?? 'moderate';
        $goal       = $data['goal']            ?? 'maintain';

        // Calculate targets
        $calGoal  = 2000;
        $waterGoal = $weight ? Helpers::calculateWaterGoal($weight, $activity) : 2000;

        if ($gender && $age && $height && $weight) {
            $tdee    = Helpers::calculateTDEE($gender, $age, $height, $weight, $activity);
            $calGoal = Helpers::adjustForGoal($tdee, $goal);
        }

        $macros = Helpers::calculateMacros($calGoal, $goal);

        Database::update('users', [
            'gender'           => $gender,
            'age'              => $age,
            'height_cm'        => $height,
            'weight_kg'        => $weight,
            'target_weight_kg' => $target,
            'activity_level'   => $activity,
            'goal'             => $goal,
            'calorie_goal'     => $calGoal,
            'protein_goal_g'   => $macros['protein'],
            'fat_goal_g'       => $macros['fat'],
            'carbs_goal_g'     => $macros['carbs'],
            'water_goal_ml'    => $waterGoal,
            'is_setup_done'    => 1,
        ], ['id' => $userId]);

        // Log initial weight
        if ($weight) {
            Database::upsert('weight_logs', [
                'user_id'    => $userId,
                'weight_kg'  => $weight,
                'log_date'   => date('Y-m-d'),
                'note'       => 'Boshlang\'ich vazn',
            ], ['weight_kg', 'note']);
        }
    }

    public function getDbId(): ?int
    {
        if (!empty($this->user['id'])) return (int)$this->user['id'];
        $u = Database::fetchOne("SELECT id FROM users WHERE telegram_id = ?", [$this->telegramId]);
        return $u ? (int)$u['id'] : null;
    }

    /**
     * Update profile fields directly.
     */
    public function update(array $fields): void
    {
        Database::update('users', $fields, ['telegram_id' => $this->telegramId]);
    }

    /**
     * Mark user as blocked (bot blocked).
     */
    public function markBlocked(bool $blocked = true): void
    {
        $this->update(['is_blocked' => (int)$blocked]);
    }
}
