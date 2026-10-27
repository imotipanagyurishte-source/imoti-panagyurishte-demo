-- Run once in phpMyAdmin for the website database.
CREATE TABLE IF NOT EXISTS property_submissions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 public_id CHAR(36) NOT NULL UNIQUE,
 listing_type ENUM('sale','rent','stay') NOT NULL,
 property_kind VARCHAR(60) NOT NULL,
 city VARCHAR(80) NOT NULL,
 price_eur DECIMAL(12,2) NOT NULL,
 area_sqm DECIMAL(10,2) NOT NULL,
 rooms SMALLINT UNSIGNED NULL,
 title VARCHAR(120) NOT NULL,
 description TEXT NOT NULL,
 contact_name VARCHAR(100) NOT NULL,
 contact_phone VARCHAR(30) NOT NULL,
 contact_email VARCHAR(190) NULL,
 status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 privacy_notice_version VARCHAR(30) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_status_created(status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS property_submission_rate_limits (
 key_hash CHAR(64) NOT NULL PRIMARY KEY,
 window_start DATETIME NOT NULL,
 request_count INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
