-- Update untuk database yang SUDAH berisi pesanan (tidak menghapus data apa pun).
-- Cara pakai: phpMyAdmin -> pilih database sera_coffee -> tab Import -> pilih file ini -> Go.
-- Jalankan sekali saja.

USE sera_coffee;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    code_hash VARCHAR(255) NULL,
    expires_at DATETIME NULL,
    attempts TINYINT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_request_per_customer (customer_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS promos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL,
    percent TINYINT NOT NULL,
    max_discount INT NOT NULL DEFAULT 0,
    min_total INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1
);

INSERT IGNORE INTO promos (code, description, percent, max_discount, min_total) VALUES
('SERA10', 'Diskon 10% untuk pesanan berikutnya. Terima kasih sudah jajan di Sera Coffee!', 10, 10000, 30000);

ALTER TABLE orders
    ADD COLUMN promo_code VARCHAR(30) NULL,
    ADD COLUMN discount INT NOT NULL DEFAULT 0;
