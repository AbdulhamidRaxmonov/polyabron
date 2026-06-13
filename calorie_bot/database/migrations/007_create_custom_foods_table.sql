CREATE TABLE IF NOT EXISTS `custom_foods` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      BIGINT UNSIGNED NOT NULL,
    `name`         VARCHAR(200) NOT NULL,
    `calories`     DECIMAL(7,2) NOT NULL DEFAULT 0,
    `protein_g`    DECIMAL(6,2) NOT NULL DEFAULT 0,
    `fat_g`        DECIMAL(6,2) NOT NULL DEFAULT 0,
    `carbs_g`      DECIMAL(6,2) NOT NULL DEFAULT 0,
    `serving_g`    SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    `use_count`    INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
