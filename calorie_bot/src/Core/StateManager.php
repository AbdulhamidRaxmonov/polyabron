<?php

namespace App\Core;

/**
 * Manages per-user conversation state in the `user_states` table.
 *
 * States:
 *   idle                 — normal operation
 *   setup:gender         — onboarding wizard step
 *   setup:age
 *   setup:height
 *   setup:weight
 *   setup:target_weight
 *   setup:activity
 *   setup:goal
 *   add_food:search      — food search for diary
 *   add_food:amount      — entering gram amount
 *   add_food:meal        — selecting meal type
 *   add_water            — entering ml amount
 *   add_weight           — entering today's weight
 *   create_food:name     — custom food wizard
 *   create_food:calories
 *   create_food:protein
 *   create_food:fat
 *   create_food:carbs
 *   create_food:serving
 *   edit_goal            — editing calorie goal
 */
class StateManager
{
    private const TTL_MINUTES = 30;

    // ── Read ─────────────────────────────────────────────────────────────

    public static function get(int $telegramId): array
    {
        $row = Database::fetchOne(
            "SELECT state, step, data FROM user_states WHERE telegram_id = ?",
            [$telegramId]
        );

        if (!$row) return ['state' => 'idle', 'step' => null, 'data' => []];

        if ($row['data']) {
            $row['data'] = json_decode($row['data'], true) ?? [];
        } else {
            $row['data'] = [];
        }

        return $row;
    }

    public static function getState(int $telegramId): string
    {
        return self::get($telegramId)['state'];
    }

    public static function getStep(int $telegramId): ?string
    {
        return self::get($telegramId)['step'];
    }

    public static function getData(int $telegramId): array
    {
        return self::get($telegramId)['data'];
    }

    public static function getDataKey(int $telegramId, string $key): mixed
    {
        return self::getData($telegramId)[$key] ?? null;
    }

    // ── Write ────────────────────────────────────────────────────────────

    public static function set(int $telegramId, string $state, ?string $step = null, array $data = []): void
    {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TTL_MINUTES . ' minutes'));

        Database::upsert('user_states', [
            'telegram_id' => $telegramId,
            'state'       => $state,
            'step'        => $step,
            'data'        => json_encode($data, JSON_UNESCAPED_UNICODE),
            'expires_at'  => $expiresAt,
        ], ['state', 'step', 'data', 'expires_at']);
    }

    public static function setData(int $telegramId, array $data): void
    {
        $current = self::get($telegramId);
        self::set($telegramId, $current['state'], $current['step'], array_merge($current['data'], $data));
    }

    public static function nextStep(int $telegramId, string $newStep): void
    {
        $current = self::get($telegramId);
        self::set($telegramId, $current['state'], $newStep, $current['data']);
    }

    public static function clear(int $telegramId): void
    {
        Database::execute("DELETE FROM user_states WHERE telegram_id = ?", [$telegramId]);
    }

    public static function reset(int $telegramId): void
    {
        self::set($telegramId, 'idle', null, []);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    public static function is(int $telegramId, string $state): bool
    {
        return self::getState($telegramId) === $state;
    }

    public static function isIdle(int $telegramId): bool
    {
        return self::getState($telegramId) === 'idle';
    }

    public static function isSetup(int $telegramId): bool
    {
        return str_starts_with(self::getState($telegramId), 'setup:');
    }

    public static function isAddFood(int $telegramId): bool
    {
        return str_starts_with(self::getState($telegramId), 'add_food:');
    }

    /** Clean up expired states (call periodically) */
    public static function cleanExpired(): int
    {
        return Database::execute(
            "DELETE FROM user_states WHERE expires_at IS NOT NULL AND expires_at < NOW()"
        );
    }
}
