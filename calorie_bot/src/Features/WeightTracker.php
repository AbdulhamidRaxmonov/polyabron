<?php

namespace App\Features;

use App\Core\Database;

class WeightTracker
{
    private int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    // ── Write ─────────────────────────────────────────────────────────────

    public function addEntry(float $weightKg, string $note = ''): int
    {
        // Upsert for today — only one entry per day
        $existing = Database::fetchOne(
            "SELECT id FROM weight_logs WHERE user_id = ? AND log_date = ?",
            [$this->userId, date('Y-m-d')]
        );

        if ($existing) {
            Database::update(
                'weight_logs',
                ['weight_kg' => $weightKg, 'note' => $note ?: null],
                ['id' => $existing['id']]
            );
            return $existing['id'];
        }

        return Database::insert('weight_logs', [
            'user_id'   => $this->userId,
            'weight_kg' => $weightKg,
            'log_date'  => date('Y-m-d'),
            'note'      => $note ?: null,
        ]);
    }

    // ── Read ──────────────────────────────────────────────────────────────

    public function getLastEntry(): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM weight_logs WHERE user_id = ? ORDER BY log_date DESC, id DESC LIMIT 1",
            [$this->userId]
        );
    }

    public function getHistory(int $days = 30): array
    {
        $from = date('Y-m-d', strtotime("-{$days} days"));
        return Database::fetchAll(
            "SELECT * FROM weight_logs WHERE user_id = ? AND log_date >= ?
             ORDER BY log_date ASC",
            [$this->userId, $from]
        );
    }

    public function getAllHistory(): array
    {
        return Database::fetchAll(
            "SELECT * FROM weight_logs WHERE user_id = ? ORDER BY log_date ASC",
            [$this->userId]
        );
    }

    public function getMinMax(): array
    {
        $row = Database::fetchOne(
            "SELECT MIN(weight_kg) AS min_weight, MAX(weight_kg) AS max_weight,
                    MIN(log_date) AS first_date, MAX(log_date) AS last_date
             FROM weight_logs WHERE user_id = ?",
            [$this->userId]
        );
        return $row ?? ['min_weight'=>null,'max_weight'=>null,'first_date'=>null,'last_date'=>null];
    }

    /**
     * Total weight change since first entry.
     */
    public function getTotalChange(): float
    {
        $first = Database::fetchOne(
            "SELECT weight_kg FROM weight_logs WHERE user_id = ? ORDER BY log_date ASC LIMIT 1",
            [$this->userId]
        );
        $last = $this->getLastEntry();

        if (!$first || !$last) return 0;

        return round((float)$last['weight_kg'] - (float)$first['weight_kg'], 1);
    }

    /**
     * Simple ASCII sparkline for the last N entries.
     */
    public function sparkline(int $days = 14): string
    {
        $history = $this->getHistory($days);
        if (count($history) < 2) return '';

        $weights = array_column($history, 'weight_kg');
        $min     = min($weights);
        $max     = max($weights);
        $range   = $max - $min;

        $blocks = ['▁','▂','▃','▄','▅','▆','▇','█'];
        $line   = '';

        foreach ($weights as $w) {
            if ($range > 0) {
                $idx  = (int) round((($w - $min) / $range) * (count($blocks) - 1));
            } else {
                $idx = 3;
            }
            $line .= $blocks[$idx];
        }

        return $line;
    }
}
