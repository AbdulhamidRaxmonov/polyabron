CREATE TABLE IF NOT EXISTS `diary_entries` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        BIGINT UNSIGNED NOT NULL,
    `food_id`        INT UNSIGNED NULL COMMENT 'NULL if custom entry',
    `food_name`      VARCHAR(200) NOT NULL,
    `meal_type`      ENUM('breakfast','lunch','dinner','snack') NOT NULL DEFAULT 'snack',
    `entry_date`     DATE NOT NULL,
    `entry_time`     TIME NOT NULL,

    -- Amount
    `amount_g`       DECIMAL(7,2) NOT NULL DEFAULT 100,

    -- Calculated nutrition (from food × amount/100)
    `calories`       DECIMAL(7,2) NOT NULL DEFAULT 0,
    `protein_g`      DECIMAL(6,2) NOT NULL DEFAULT 0,
    `fat_g`          DECIMAL(6,2) NOT NULL DEFAULT 0,
    `carbs_g`        DECIMAL(6,2) NOT NULL DEFAULT 0,
    `fiber_g`        DECIMAL(6,2) NOT NULL DEFAULT 0,

    `note`           VARCHAR(300) NULL,

    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_user_date` (`user_id`, `entry_date`),
    KEY `idx_meal_type` (`meal_type`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`food_id`) REFERENCES `foods`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
