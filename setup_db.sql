-- ═══════════════════════════════════════════════
--  BikeValue — Database Schema
--  Run this in Railway MySQL console after deploy
-- ═══════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS `bikevalue`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `bikevalue`;

-- ── USERS TABLE ──
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    VARCHAR(50)  NOT NULL UNIQUE,
  `email`      VARCHAR(100) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,   -- bcrypt hashed
  `role`       ENUM('user','admin') NOT NULL DEFAULT 'user',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PREDICTIONS TABLE ──
CREATE TABLE IF NOT EXISTS `predictions` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`          VARCHAR(50)  NOT NULL,
  `bike_name`        VARCHAR(100) NOT NULL,
  `brand`            VARCHAR(50)  NOT NULL,
  `engine_capacity`  INT          NOT NULL,
  `age`              INT          NOT NULL,
  `owner`            INT          NOT NULL DEFAULT 1,
  `kms_driven`       INT          NOT NULL DEFAULT 0,
  `city`             VARCHAR(50)  NOT NULL,
  `accident_count`   INT          NOT NULL DEFAULT 0,
  `accident_history` VARCHAR(20)  NOT NULL DEFAULT 'none',
  `ml_price`         DECIMAL(12,2),
  `ml_adjusted`      DECIMAL(12,2),
  `formula_price`    DECIMAL(12,2),
  `final_price`      DECIMAL(12,2),
  `ml_used`          TINYINT(1) DEFAULT 0,
  `created_at`       DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── DEFAULT ADMIN ACCOUNT ──
-- Password: admin123 (bcrypt)
INSERT IGNORE INTO `users` (`user_id`, `email`, `password`, `role`) VALUES (
  'admin',
  'admin@bikevalue.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'admin'
);
-- NOTE: Change this password immediately after first login!
-- Use: php -r "echo password_hash('YOUR_NEW_PASSWORD', PASSWORD_DEFAULT);"
