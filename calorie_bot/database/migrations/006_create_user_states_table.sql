CREATE TABLE IF NOT EXISTS `user_states` (
    `telegram_id`  BIGINT NOT NULL,
    `state`        VARCHAR(100) NOT NULL DEFAULT 'idle',
    `step`         VARCHAR(100) NULL,
    `data`         JSON NULL COMMENT 'temporary wizard data',
    `expires_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`telegram_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
