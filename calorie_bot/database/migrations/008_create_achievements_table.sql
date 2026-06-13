CREATE TABLE IF NOT EXISTS `achievements` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`         VARCHAR(50) NOT NULL UNIQUE,
    `title`        VARCHAR(100) NOT NULL,
    `description`  VARCHAR(300) NOT NULL,
    `icon`         VARCHAR(10) NOT NULL DEFAULT '🏅',
    `condition_type` ENUM('streak','total_days','weight_lost','entries_count') NOT NULL,
    `condition_value` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_achievements` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         BIGINT UNSIGNED NOT NULL,
    `achievement_id`  INT UNSIGNED NOT NULL,
    `earned_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_achievement` (`user_id`, `achievement_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`achievement_id`) REFERENCES `achievements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
