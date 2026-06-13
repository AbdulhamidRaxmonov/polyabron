CREATE TABLE IF NOT EXISTS `foods` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name_ru`      VARCHAR(200) NOT NULL,
    `name_uz`      VARCHAR(200) NULL,
    `name_en`      VARCHAR(200) NULL,
    `category`     VARCHAR(80) NOT NULL DEFAULT 'other',
    `brand`        VARCHAR(100) NULL,

    -- Nutrition per 100g
    `calories`     DECIMAL(7,2) NOT NULL DEFAULT 0,
    `protein_g`    DECIMAL(6,2) NOT NULL DEFAULT 0,
    `fat_g`        DECIMAL(6,2) NOT NULL DEFAULT 0,
    `carbs_g`      DECIMAL(6,2) NOT NULL DEFAULT 0,
    `fiber_g`      DECIMAL(6,2) NOT NULL DEFAULT 0,
    `sugar_g`      DECIMAL(6,2) NOT NULL DEFAULT 0,
    `sodium_mg`    DECIMAL(7,2) NOT NULL DEFAULT 0,

    -- Default serving
    `serving_size_g`   SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    `serving_name`     VARCHAR(50) NOT NULL DEFAULT '100г',

    `is_liquid`    TINYINT(1) NOT NULL DEFAULT 0,
    `is_verified`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_by`   BIGINT NULL COMMENT 'user telegram_id if user-created',

    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    FULLTEXT KEY `ft_name` (`name_ru`, `name_uz`, `name_en`),
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
