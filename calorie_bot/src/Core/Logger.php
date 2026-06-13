<?php

namespace App\Core;

class Logger
{
    private static string $logDir = '';

    private static function dir(): string
    {
        if (!self::$logDir) {
            self::$logDir = dirname(__DIR__, 2) . '/logs';
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
        }
        return self::$logDir;
    }

    private static function write(string $level, string $message, array $context = []): void
    {
        $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
        $configLevel = Config::get('log_level', 'info');

        if (($levels[$level] ?? 1) < ($levels[$configLevel] ?? 1)) return;

        $file = self::dir() . '/bot-' . date('Y-m-d') . '.log';
        $ctx  = $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = sprintf("[%s] [%s] %s%s\n", date('Y-m-d H:i:s'), strtoupper($level), $message, $ctx);

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function debug(string $msg, array $ctx = []): void   { self::write('debug',   $msg, $ctx); }
    public static function info(string $msg, array $ctx = []): void    { self::write('info',    $msg, $ctx); }
    public static function warning(string $msg, array $ctx = []): void { self::write('warning', $msg, $ctx); }
    public static function error(string $msg, array $ctx = []): void   { self::write('error',   $msg, $ctx); }

    public static function exception(\Throwable $e): void
    {
        self::error($e->getMessage(), [
            'file'  => $e->getFile() . ':' . $e->getLine(),
            'trace' => substr($e->getTraceAsString(), 0, 1000),
        ]);
    }
}
