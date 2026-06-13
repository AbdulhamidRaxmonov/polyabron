<?php

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                Config::get('db_host'),
                Config::get('db_port'),
                Config::get('db_name')
            );

            self::$instance = new PDO(
                $dsn,
                Config::get('db_user'),
                Config::get('db_pass'),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]
            );
        }

        return self::$instance;
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Run a prepared SELECT and return all rows.
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Run a prepared SELECT and return one row.
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Run a prepared SELECT and return a single scalar column.
     */
    public static function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        $val = $stmt->fetchColumn();
        return ($val === false) ? null : $val;
    }

    /**
     * Execute INSERT/UPDATE/DELETE; return affected rows.
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * INSERT one row; return last insert ID.
     */
    public static function insert(string $table, array $data): int
    {
        $cols   = implode(', ', array_map(fn($c) => "`$c`", array_keys($data)));
        $places = implode(', ', array_fill(0, count($data), '?'));
        $sql    = "INSERT INTO `$table` ($cols) VALUES ($places)";

        self::getInstance()->prepare($sql)->execute(array_values($data));
        return (int) self::getInstance()->lastInsertId();
    }

    /**
     * UPDATE rows matching $where; return affected count.
     */
    public static function update(string $table, array $data, array $where): int
    {
        $set  = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        $cond = implode(' AND ', array_map(fn($c) => "`$c` = ?", array_keys($where)));
        $sql  = "UPDATE `$table` SET $set WHERE $cond";

        $params = array_merge(array_values($data), array_values($where));
        $stmt   = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * INSERT or UPDATE (UPSERT) using ON DUPLICATE KEY UPDATE.
     */
    public static function upsert(string $table, array $data, array $updateCols = []): int
    {
        $cols    = implode(', ', array_map(fn($c) => "`$c`", array_keys($data)));
        $places  = implode(', ', array_fill(0, count($data), '?'));

        $updateCols = $updateCols ?: array_keys($data);
        $updates = implode(', ', array_map(fn($c) => "`$c` = VALUES(`$c`)", $updateCols));

        $sql = "INSERT INTO `$table` ($cols) VALUES ($places) ON DUPLICATE KEY UPDATE $updates";
        self::getInstance()->prepare($sql)->execute(array_values($data));
        return (int) self::getInstance()->lastInsertId();
    }

    public static function beginTransaction(): void  { self::getInstance()->beginTransaction(); }
    public static function commit(): void            { self::getInstance()->commit(); }
    public static function rollback(): void          { self::getInstance()->rollBack(); }
    public static function lastInsertId(): int       { return (int) self::getInstance()->lastInsertId(); }
}
