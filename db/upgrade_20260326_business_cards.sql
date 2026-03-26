-- Business card images table
CREATE TABLE IF NOT EXISTS business_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    ocr_raw_text TEXT,
    ocr_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (contact_id) REFERENCES address_book(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add business card columns to address_book
ALTER TABLE address_book ADD COLUMN IF NOT EXISTS has_business_card TINYINT(1) DEFAULT 0;
ALTER TABLE address_book ADD COLUMN IF NOT EXISTS business_card_image VARCHAR(500) DEFAULT NULL;
