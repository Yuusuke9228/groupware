-- アドレス帳テーブル
CREATE TABLE IF NOT EXISTS address_book (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_kana VARCHAR(100) DEFAULT '',
    company VARCHAR(200) DEFAULT '',
    department VARCHAR(100) DEFAULT '',
    position_title VARCHAR(100) DEFAULT '',
    email VARCHAR(200) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    mobile VARCHAR(50) DEFAULT '',
    fax VARCHAR(50) DEFAULT '',
    postal_code VARCHAR(20) DEFAULT '',
    address TEXT,
    url VARCHAR(500) DEFAULT '',
    category VARCHAR(50) DEFAULT '',
    memo TEXT,
    created_by INT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_name (name),
    INDEX idx_name_kana (name_kana),
    INDEX idx_company (company),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 施設テーブル
CREATE TABLE IF NOT EXISTS facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    capacity INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 施設予約テーブル
CREATE TABLE IF NOT EXISTS facility_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facility_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    memo TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_facility_date (facility_id, start_time),
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
