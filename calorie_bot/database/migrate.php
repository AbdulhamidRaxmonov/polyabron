<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4',
    $_ENV['DB_HOST'], $_ENV['DB_PORT'] ?? 3306);

try {
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Create DB if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$_ENV['DB_DATABASE']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$_ENV['DB_DATABASE']}`");

    $files = glob(__DIR__ . '/migrations/*.sql');
    sort($files);

    foreach ($files as $file) {
        $sql = file_get_contents($file);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $stmt) {
            if ($stmt) {
                $pdo->exec($stmt);
            }
        }
        echo "✅ Migrated: " . basename($file) . "\n";
    }

    echo "\n✅ All migrations completed successfully!\n";
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
