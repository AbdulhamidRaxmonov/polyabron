CREATE TABLE IF NOT EXISTS `user_streaks` (
    `user_id`          BIGINT UNSIGNED NOT NULL,
    `current_streak`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `longest_streak`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `last_active_date` DATE NULL,
    `total_active_days` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
