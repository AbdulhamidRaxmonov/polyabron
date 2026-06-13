<?php

namespace App\Features;

use App\Core\Database;

class FoodSearch
{
    private const PER_PAGE = 5;

    /**
     * Full-text + LIKE search.
     * Returns ['foods' => [...], 'total' => int]
     */
    public function search(string $query, int $page = 0, ?int $userId = null): array
    {
        $query   = trim($query);
        $offset  = $page * self::PER_PAGE;
        $like    = '%' . str_replace(['%', '_'], ['\%', '\_'], $query) . '%';

        // Try FULLTEXT first
        $foods = Database::fetchAll(
            "SELECT id, name_ru, name_uz, calories, protein_g, fat_g, carbs_g, serving_size_g, serving_name, category
             FROM foods
             WHERE MATCH(name_ru, name_uz, name_en) AGAINST (? IN BOOLEAN MODE)
               AND is_verified = 1
             ORDER BY is_verified DESC, MATCH(name_ru, name_uz, name_en) AGAINST (?) DESC
             LIMIT ? OFFSET ?",
            [$query . '*', $query, self::PER_PAGE, $offset]
        );

        // Fallback: LIKE search
        if (empty($foods)) {
            $foods = Database::fetchAll(
                "SELECT id, name_ru, name_uz, calories, protein_g, fat_g, carbs_g, serving_size_g, serving_name, category
                 FROM foods
                 WHERE (name_ru LIKE ? OR name_uz LIKE ? OR name_en LIKE ?)
                   AND is_verified = 1
                 ORDER BY CHAR_LENGTH(name_ru) ASC
                 LIMIT ? OFFSET ?",
                [$like, $like, $like, self::PER_PAGE, $offset]
            );
        }

        // Also search user's custom foods
        if ($userId) {
            $customFoods = Database::fetchAll(
                "SELECT f.id, f.name_ru, f.name_uz, f.calories, f.protein_g, f.fat_g, f.carbs_g,
                        f.serving_size_g, f.serving_name, 'custom' as category
                 FROM foods f
                 WHERE f.name_ru LIKE ? AND f.created_by = ?
                 LIMIT 3",
                [$like, $userId]
            );
            // Prepend custom foods
            $foods = array_merge($customFoods, $foods);
        }

        // Count total for pagination
        $total = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM foods WHERE (name_ru LIKE ? OR name_uz LIKE ?) AND is_verified = 1",
            [$like, $like]
        );

        return [
            'foods' => array_slice($foods, 0, self::PER_PAGE),
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / self::PER_PAGE),
        ];
    }

    /**
     * Get a single food by ID.
     */
    public function getById(int $foodId): ?array
    {
        return Database::fetchOne("SELECT * FROM foods WHERE id = ?", [$foodId]);
    }

    /**
     * Get popular foods (most used in diary_entries).
     */
    public function getPopular(int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT f.id, f.name_ru, f.calories, f.protein_g, f.fat_g, f.carbs_g, COUNT(d.id) as use_count
             FROM foods f
             LEFT JOIN diary_entries d ON d.food_id = f.id
             WHERE f.is_verified = 1
             GROUP BY f.id
             ORDER BY use_count DESC, f.name_ru
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get user's recently used foods.
     */
    public function getRecent(int $userId, int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT DISTINCT d.food_id as id, d.food_name as name_ru, f.calories,
                    f.protein_g, f.fat_g, f.carbs_g, f.serving_size_g, f.serving_name
             FROM diary_entries d
             LEFT JOIN foods f ON f.id = d.food_id
             WHERE d.user_id = ? AND d.food_id IS NOT NULL
             ORDER BY d.created_at DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }

    /**
     * Get foods by category.
     */
    public function getByCategory(string $category, int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT id, name_ru, calories, protein_g, fat_g, carbs_g, serving_size_g, serving_name
             FROM foods WHERE category = ? AND is_verified = 1
             ORDER BY name_ru LIMIT ?",
            [$category, $limit]
        );
    }
}
