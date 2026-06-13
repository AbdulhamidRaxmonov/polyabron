<?php

namespace App\Features;

use App\Core\Database;

class FoodDiary
{
    private int $userId;   // DB users.id

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    // ── Write ─────────────────────────────────────────────────────────────

    /**
     * Add a diary entry. Returns new entry ID.
     */
    public function addEntry(array $data): int
    {
        return Database::insert('diary_entries', array_merge([
            'user_id'    => $this->userId,
            'entry_date' => date('Y-m-d'),
            'entry_time' => date('H:i:s'),
        ], $data));
    }

    /**
     * Delete an entry that belongs to this user.
     */
    public function deleteEntry(int $entryId): bool
    {
        $rows = Database::execute(
            "DELETE FROM diary_entries WHERE id = ? AND user_id = ?",
            [$entryId, $this->userId]
        );
        return $rows > 0;
    }

    /**
     * Update an entry (e.g. change amount).
     */
    public function updateEntry(int $entryId, array $data): bool
    {
        $rows = Database::update(
            'diary_entries',
            $data,
            ['id' => $entryId, 'user_id' => $this->userId]
        );
        return $rows > 0;
    }

    // ── Read ──────────────────────────────────────────────────────────────

    /**
     * Get all entries for a date, ordered by time.
     */
    public function getEntries(string $date): array
    {
        return Database::fetchAll(
            "SELECT * FROM diary_entries
             WHERE user_id = ? AND entry_date = ?
             ORDER BY FIELD(meal_type,'breakfast','lunch','dinner','snack'), entry_time",
            [$this->userId, $date]
        );
    }

    /**
     * Get entries grouped by meal_type for a date.
     */
    public function getEntriesGrouped(string $date): array
    {
        $entries = $this->getEntries($date);
        $grouped = ['breakfast' => [], 'lunch' => [], 'dinner' => [], 'snack' => []];
        foreach ($entries as $e) {
            $grouped[$e['meal_type']][] = $e;
        }
        return $grouped;
    }

    /**
     * Totals (calories, macros) for a date.
     */
    public function getTotals(string $date): array
    {
        $row = Database::fetchOne(
            "SELECT
                COALESCE(SUM(calories), 0)   AS calories,
                COALESCE(SUM(protein_g), 0)  AS protein_g,
                COALESCE(SUM(fat_g), 0)      AS fat_g,
                COALESCE(SUM(carbs_g), 0)    AS carbs_g,
                COALESCE(SUM(fiber_g), 0)    AS fiber_g,
                COUNT(*) AS entry_count
             FROM diary_entries
             WHERE user_id = ? AND entry_date = ?",
            [$this->userId, $date]
        );
        return $row ?? ['calories'=>0,'protein_g'=>0,'fat_g'=>0,'carbs_g'=>0,'fiber_g'=>0,'entry_count'=>0];
    }

    /**
     * Totals per meal_type for a date.
     */
    public function getTotalsByMeal(string $date): array
    {
        $rows = Database::fetchAll(
            "SELECT meal_type,
                    COALESCE(SUM(calories), 0)  AS calories,
                    COALESCE(SUM(protein_g), 0) AS protein_g,
                    COALESCE(SUM(fat_g), 0)     AS fat_g,
                    COALESCE(SUM(carbs_g), 0)   AS carbs_g
             FROM diary_entries
             WHERE user_id = ? AND entry_date = ?
             GROUP BY meal_type",
            [$this->userId, $date]
        );

        $result = [];
        foreach ($rows as $r) $result[$r['meal_type']] = $r;
        return $result;
    }

    /**
     * Daily totals for a date range (for charts/statistics).
     */
    public function getDailyTotals(string $from, string $to): array
    {
        return Database::fetchAll(
            "SELECT entry_date,
                    COALESCE(SUM(calories), 0)  AS calories,
                    COALESCE(SUM(protein_g), 0) AS protein_g,
                    COALESCE(SUM(fat_g), 0)     AS fat_g,
                    COALESCE(SUM(carbs_g), 0)   AS carbs_g
             FROM diary_entries
             WHERE user_id = ? AND entry_date BETWEEN ? AND ?
             GROUP BY entry_date
             ORDER BY entry_date",
            [$this->userId, $from, $to]
        );
    }

    /**
     * Average daily intake over N days.
     */
    public function getAverages(int $days = 7): array
    {
        $from = date('Y-m-d', strtotime("-{$days} days"));
        $to   = date('Y-m-d');

        $row = Database::fetchOne(
            "SELECT
                COALESCE(AVG(daily_cal), 0)  AS avg_calories,
                COALESCE(AVG(daily_prot), 0) AS avg_protein,
                COALESCE(AVG(daily_fat), 0)  AS avg_fat,
                COALESCE(AVG(daily_carb), 0) AS avg_carbs
             FROM (
                SELECT entry_date,
                       SUM(calories)  AS daily_cal,
                       SUM(protein_g) AS daily_prot,
                       SUM(fat_g)     AS daily_fat,
                       SUM(carbs_g)   AS daily_carb
                FROM diary_entries
                WHERE user_id = ? AND entry_date BETWEEN ? AND ?
                GROUP BY entry_date
             ) daily",
            [$this->userId, $from, $to]
        );

        return $row ?? ['avg_calories'=>0,'avg_protein'=>0,'avg_fat'=>0,'avg_carbs'=>0];
    }

    /**
     * Count active days (days with at least one entry).
     */
    public function countActiveDays(string $from = null, string $to = null): int
    {
        $from = $from ?? '2000-01-01';
        $to   = $to   ?? date('Y-m-d');

        return (int) Database::fetchColumn(
            "SELECT COUNT(DISTINCT entry_date) FROM diary_entries
             WHERE user_id = ? AND entry_date BETWEEN ? AND ?",
            [$this->userId, $from, $to]
        );
    }

    /**
     * Most-eaten foods for this user.
     */
    public function getTopFoods(int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT food_name, COUNT(*) as times, SUM(calories) as total_cal
             FROM diary_entries WHERE user_id = ?
             GROUP BY food_name
             ORDER BY times DESC LIMIT ?",
            [$this->userId, $limit]
        );
    }

    /**
     * Was there an entry on a specific date?
     */
    public function hasEntryOnDate(string $date): bool
    {
        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM diary_entries WHERE user_id = ? AND entry_date = ?",
            [$this->userId, $date]
        ) > 0;
    }
}
