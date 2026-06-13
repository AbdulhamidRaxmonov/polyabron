<?php

namespace App\Core;

class Config
{
    private static array $data = [];
    private static bool  $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) return;

        $root = dirname(__DIR__, 2);
        $env  = $root . '/.env';

        if (file_exists($env)) {
            $dotenv = \Dotenv\Dotenv::createImmutable($root);
            $dotenv->load();
        }

        self::$data = [
            // App
            'app_name'    => $_ENV['APP_NAME']    ?? 'iCalCalorie Bot',
            'app_url'     => $_ENV['APP_URL']      ?? '',
            'app_env'     => $_ENV['APP_ENV']      ?? 'production',
            'app_debug'   => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'log_level'   => $_ENV['LOG_LEVEL']    ?? 'info',

            // Telegram
            'bot_token'         => $_ENV['TELEGRAM_BOT_TOKEN']   ?? '',
            'webhook_secret'    => $_ENV['TELEGRAM_WEBHOOK_SECRET'] ?? '',
            'bot_username'      => $_ENV['TELEGRAM_BOT_USERNAME'] ?? '',
            'admin_ids'         => array_map(
                'intval',
                array_filter(explode(',', $_ENV['TELEGRAM_ADMIN_IDS'] ?? ''))
            ),

            // Database
            'db_host'     => $_ENV['DB_HOST']     ?? '127.0.0.1',
            'db_port'     => $_ENV['DB_PORT']     ?? 3306,
            'db_name'     => $_ENV['DB_DATABASE'] ?? 'calorie_bot',
            'db_user'     => $_ENV['DB_USERNAME'] ?? 'root',
            'db_pass'     => $_ENV['DB_PASSWORD'] ?? '',

            // OpenAI (food photo recognition)
            'openai_key'   => $_ENV['OPENAI_API_KEY'] ?? '',
            'openai_model' => $_ENV['OPENAI_MODEL']   ?? 'gpt-4o',

            // Limits
            'rate_limit'         => (int)($_ENV['RATE_LIMIT_PER_MINUTE']    ?? 30),
            'max_daily_entries'  => (int)($_ENV['MAX_DAILY_FOOD_ENTRIES']   ?? 100),
        ];

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        return self::$data[$key] ?? $default;
    }

    public static function isDebug(): bool
    {
        return self::get('app_debug', false);
    }

    public static function isAdmin(int $telegramId): bool
    {
        return in_array($telegramId, self::get('admin_ids', []), true);
    }
}
