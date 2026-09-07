-- scrapefeed schema — run once by the installer, or manually via phpMyAdmin.

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(120) DEFAULT '',
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    plan VARCHAR(40) NOT NULL DEFAULT 'starter',
    credits_remaining INT NOT NULL DEFAULT 200,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS extractions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    store_url VARCHAR(500) NOT NULL,
    platform VARCHAR(20) NOT NULL DEFAULT 'auto',
    detected_platform VARCHAR(20) DEFAULT NULL,
    format ENUM('csv','json') NOT NULL DEFAULT 'csv',
    product_limit INT UNSIGNED DEFAULT NULL,
    status ENUM('pending','running','done','failed') NOT NULL DEFAULT 'pending',
    product_count INT UNSIGNED DEFAULT 0,
    error_message VARCHAR(500) DEFAULT NULL,
    result_file VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (setting_key, setting_value) VALUES
    ('site_name', 'scrapefeed'),
    ('starter_credits', '200'),
    ('installed_at', NOW())
ON DUPLICATE KEY UPDATE setting_key = setting_key;
