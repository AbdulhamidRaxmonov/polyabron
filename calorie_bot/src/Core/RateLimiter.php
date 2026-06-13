<?php

namespace App\Core;

/**
 * Simple in-memory (APCu) or file-based rate limiter.
 * Limits requests per user per minute.
 */
class RateLimiter
{
    private static array $cache = [];

    public static function check(int $telegramId): bool
    {
        $limit  = Config::get('rate_limit', 30);
        $key    = "rl_{$telegramId}_" . date('Y_m_d_H_i');
        $count  = (self::$cache[$key] ?? 0) + 1;
        self::$cache[$key] = $count;

        if ($count > $limit) {
            Logger::warning("Rate limit exceeded for user $telegramId (count: $count)");
            return false;
        }

        return true;
    }
}
