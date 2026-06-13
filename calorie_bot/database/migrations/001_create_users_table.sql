CREATE TABLE IF NOT EXISTS `users` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `telegram_id`      BIGINT NOT NULL UNIQUE,
    `username`         VARCHAR(100) NULL,
    `first_name`       VARCHAR(100) NOT NULL DEFAULT '',
    `last_name`        VARCHAR(100) NULL,
    `language_code`    VARCHAR(10) NOT NULL DEFAULT 'ru',

    -- Profile
    `gender`           ENUM('male','female') NULL,
    `age`              TINYINT UNSIGNED NULL,
    `height_cm`        SMALLINT UNSIGNED NULL,
    `weight_kg`        DECIMAL(5,2) NULL,
    `target_weight_kg` DECIMAL(5,2) NULL,
    `activity_level`   ENUM('sedentary','light','moderate','active','very_active') NOT NULL DEFAULT 'moderate',
    `goal`             ENUM('lose','maintain','gain') NOT NULL DEFAULT 'maintain',

    -- Calculated daily targets
    `calorie_goal`     SMALLINT UNSIGNED NOT NULL DEFAULT 2000,
    `protein_goal_g`   SMALLINT UNSIGNED NOT NULL DEFAULT 150,
    `fat_goal_g`       SMALLINT UNSIGNED NOT NULL DEFAULT 65,
    `carbs_goal_g`     SMALLINT UNSIGNED NOT NULL DEFAULT 250,
    `water_goal_ml`    SMALLINT UNSIGNED NOT NULL DEFAULT 2000,

    -- Settings
    `notify_morning`   TINYINT(1) NOT NULL DEFAULT 1,
    `notify_evening`   TINYINT(1) NOT NULL DEFAULT 1,
    `notify_water`     TINYINT(1) NOT NULL DEFAULT 1,
    `timezone`         VARCHAR(50) NOT NULL DEFAULT 'Asia/Tashkent',
    `is_premium`       TINYINT(1) NOT NULL DEFAULT 0,
    `is_blocked`       TINYINT(1) NOT NULL DEFAULT 0,
    `is_setup_done`    TINYINT(1) NOT NULL DEFAULT 0,

    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_telegram_id` (`telegram_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
