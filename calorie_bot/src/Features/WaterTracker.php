<?php

namespace App\Features;

use App\Core\Database;

class WaterTracker
{
    private int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    // ── Write ─────────────────────────────────────────────────────────────

    public function addEntry(int $ml): int
    {
        return Database::insert('water_logs', [
            'user_id'    => $this->userId,
            'amount_ml'  => $ml,
            'log_date'   => date('Y-m-d'),
            'log_time'   => date('H:i:s'),
        ]);
    }

    public function deleteLastEntry(): bool
    {
        $last = Database::fetchOne(
            "SELECT id FROM water_logs WHERE user_id = ? AND log_date = ? ORDER BY id DESC LIMIT 1",
            [$this->userId, date('Y-m-d')]
        );
        if (!$last) return false;

        return Database::execute(
            "DELETE FROM water_logs WHERE id = ? AND user_id = ?",
            [$last['id'], $this->userId]
        ) > 0;
    }

    // ── Read ──────────────────────────────────────────────────────────────

    public function getTodayTotal(): int
    {
        return (int) Database::fetchColumn(
            "SELECT COALESCE(SUM(amount_ml), 0) FROM water_logs WHERE user_id = ? AND log_date = ?",
            [$this->userId, date('Y-m-d')]
        );
    }

    public function getDayTotal(string $date): int
    {
        return (int) Database::fetchColumn(
            "SELECT COALESCE(SUM(amount_ml), 0) FROM water_logs WHERE user_id = ? AND log_date = ?",
            [$this->userId, $date]
        );
    }

    public function getEntriesForDay(string $date): array
    {
        return Database::fetchAll(
            "SELECT * FROM water_logs WHERE user_id = ? AND log_date = ? ORDER BY log_time",
            [$this->userId, $date]
        );
    }

    /**
     * Daily totals for a date range.
     */
    public function getDailyTotals(string $from, string $to): array
    {
        return Database::fetchAll(
            "SELECT log_date, COALESCE(SUM(amount_ml), 0) AS total_ml
             FROM water_logs
             WHERE user_id = ? AND log_date BETWEEN ? AND ?
             GROUP BY log_date ORDER BY log_date",
            [$this->userId, $from, $to]
        );
    }

    public function getWeekAverage(): float
    {
        $from = date('Y-m-d', strtotime('-7 days'));
        $avg  = Database::fetchColumn(
            "SELECT AVG(daily) FROM (
                SELECT SUM(amount_ml) AS daily FROM water_logs
                WHERE user_id = ? AND log_date >= ?
                GROUP BY log_date
             ) d",
            [$this->userId, $from]
        );
        return round((float)($avg ?? 0));
    }
}
