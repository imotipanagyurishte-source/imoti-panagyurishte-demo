CREATE TABLE managed_property_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 public_id CHAR(36) NOT NULL UNIQUE,
 owner_name VARCHAR(120) NOT NULL,
 phone VARCHAR(30) NOT NULL,
 town VARCHAR(120) NOT NULL,
 property_type VARCHAR(40) NOT NULL,
 purpose ENUM('sale','rent') NOT NULL,
 area_sqm DECIMAL(12,2) NULL,
 preferred_visit VARCHAR(150) NULL,
 notes TEXT NULL,
 status ENUM('new','contacted','visit_scheduled','contracted','closed','rejected') NOT NULL DEFAULT 'new',
 privacy_notice_version VARCHAR(30) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_status_created (status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE request_rate_limits (
 key_hash CHAR(64) NOT NULL PRIMARY KEY,
 window_start TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 request_count INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;