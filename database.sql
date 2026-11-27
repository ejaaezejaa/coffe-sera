CREATE DATABASE IF NOT EXISTS sera_coffee CHARACTER SET utf8mb4;
USE sera_coffee;

DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS promos;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS products;

-- Daftar menu
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    price INT NOT NULL,              -- harga dalam rupiah
    image VARCHAR(100) NOT NULL,     -- nama file di folder images/
    category VARCHAR(20) NOT NULL,   -- 'iced' atau 'hot'
    details TEXT                     -- penjelasan lengkap, tampil di halaman detail
);

-- Satu baris = satu pesanan
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    note VARCHAR(255),
    payment_method VARCHAR(20) NOT NULL,
    total INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Isi pesanan (satu pesanan bisa punya banyak item)
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL,
    price INT NOT NULL,
    options VARCHAR(100),            -- pilihan pembeli, contoh: 'Less Sugar' atau 'Panas Banget'
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Rating dan masukan dari pembeli (satu menu hanya bisa diulas sekali per pesanan)
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    rating TINYINT NOT NULL,         -- 1 sampai 5
    comment VARCHAR(500),
    show_name TINYINT(1) NOT NULL DEFAULT 0,   -- 1 = nama ditampilkan, 0 = nama disensor
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_review_per_item (order_id, product_id),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Pesan dari form contact
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO products (name, description, price, image, category, details) VALUES
('Es Kopi Susu Sera', 'Espresso, susu segar, dan gula aren. Menu andalan kami.', 22000, 'es-kopi-susu.jpg', 'iced', 'Perpaduan espresso dari biji kopi pilihan, susu segar, dan gula aren yang manis legit. Disajikan dingin dengan es batu, cocok menemani pagi atau siang yang panas. Rasanya creamy, manis karamel, dengan aftertaste kopi yang halus.'),
('Iced Caramel Latte', 'Latte dingin dengan lapisan karamel manis.', 25000, 'kopi-vertikal.jpg', 'iced', 'Espresso dan susu dingin dengan lapisan saus karamel di atasnya. Manis, lembut, dan tidak terlalu pahit. Aduk dulu sebelum diminum supaya rasa karamelnya merata.'),
('Iced Latte', 'Espresso dan susu dingin yang berlapis cantik.', 24000, 'iced-latte.jpeg', 'iced', 'Espresso dan susu segar yang dituang berlapis, jadi tampilannya cantik sebelum diaduk. Rasanya lembut dan creamy, dengan kopi yang tetap terasa.'),
('Hot Cappuccino', 'Espresso dengan susu steam dan foam tebal.', 24000, 'cappuccino.jpeg', 'hot', 'Satu shot espresso dengan susu yang di-steam dan foam tebal di atasnya, ditaburi bubuk kayu manis. Hangat, ringan, dan wangi.'),
('Hot Latte', 'Susu steam lembut dengan latte art.', 24000, 'latte-art.jpeg', 'hot', 'Espresso dengan susu steam yang lembut dan latte art di atasnya. Pas untuk kamu yang suka kopi hangat yang tidak terlalu kuat.'),
('Kopi Hitam', 'Kopi hitam tanpa susu, pekat dan harum.', 15000, 'kopi-hitam.jpeg', 'hot', 'Kopi hitam tanpa susu dan tanpa gula. Rasanya pekat dengan aroma yang kuat, cocok untuk penikmat kopi sejati.'),
('Hot Mocha', 'Hangat dan manis, dengan whipped cream dan saus karamel.', 26000, 'mocha-whipped.jpeg', 'hot', 'Kopi, cokelat, dan susu hangat dengan whipped cream dan saus karamel di atasnya. Manis dan nyaman, cocok untuk sore yang santai.'),
('Coffee To Go', 'Kopi hangat dalam cup, praktis dibawa ke mana saja.', 20000, 'kopi-cup.jpeg', 'hot', 'Kopi hangat dalam cup yang praktis dibawa ke mana saja. Pas untuk kamu yang lagi buru-buru tapi tetap butuh kopi.');

-- Kadar gula dan nilai gizi per porsi (angka perkiraan, silakan sesuaikan dengan resep asli)
ALTER TABLE products
    ADD COLUMN sugar_normal INT,         -- gram gula tambahan, level Normal
    ADD COLUMN sugar_less INT,           -- gram gula tambahan, level Less Sugar
    ADD COLUMN calories INT,             -- kkal per porsi (level Normal)
    ADD COLUMN fat DECIMAL(4,1),         -- gram
    ADD COLUMN carbs INT,                -- gram
    ADD COLUMN protein DECIMAL(4,1),     -- gram
    ADD COLUMN caffeine INT;             -- mg

UPDATE products SET sugar_normal = 18, sugar_less = 9, calories = 170, fat = 5.0, carbs = 25, protein = 5.0, caffeine = 80 WHERE name = 'Es Kopi Susu Sera';
UPDATE products SET sugar_normal = 20, sugar_less = 10, calories = 210, fat = 6.5, carbs = 30, protein = 6.5, caffeine = 80 WHERE name = 'Iced Caramel Latte';
UPDATE products SET sugar_normal = 12, sugar_less = 6, calories = 175, fat = 6.5, carbs = 22, protein = 6.5, caffeine = 80 WHERE name = 'Iced Latte';
UPDATE products SET sugar_normal = 8, sugar_less = 4, calories = 110, fat = 4.0, carbs = 14, protein = 4.0, caffeine = 80 WHERE name = 'Hot Cappuccino';
UPDATE products SET sugar_normal = 10, sugar_less = 5, calories = 165, fat = 6.5, carbs = 20, protein = 6.5, caffeine = 80 WHERE name = 'Hot Latte';
UPDATE products SET sugar_normal = 0, sugar_less = 0, calories = 5, fat = 0.0, carbs = 1, protein = 0.3, caffeine = 95 WHERE name = 'Kopi Hitam';
UPDATE products SET sugar_normal = 24, sugar_less = 12, calories = 280, fat = 12.0, carbs = 40, protein = 7.0, caffeine = 80 WHERE name = 'Hot Mocha';
UPDATE products SET sugar_normal = 8, sugar_less = 4, calories = 130, fat = 5.0, carbs = 15, protein = 5.0, caffeine = 80 WHERE name = 'Coffee To Go';

-- Akun admin untuk halaman /admin
-- Login awal: username admin, password admin123
-- Saat login pertama, password WAJIB diganti dulu (kolom must_change = 1).
DROP TABLE IF EXISTS admins;
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    must_change TINYINT(1) NOT NULL DEFAULT 1
);

INSERT INTO admins (username, password_hash) VALUES ('admin', '$2y$10$A56AfMT6pNWB3yHATFZ.eePHj4phHl1w8XU7Cz6aYf0aY9kRANJFq');

-- Kopi Hitam sekarang juga punya pilihan gula, jadi level Normal = 8 g (kalori dan karbohidrat ikut menyesuaikan)
UPDATE products SET sugar_normal = 8, sugar_less = 4, calories = 37, carbs = 9,
    details = 'Kopi hitam tanpa susu, rasanya pekat dengan aroma yang kuat. Pilih sendiri tingkat gulanya, dari Normal sampai No Sugar.'
WHERE name = 'Kopi Hitam';

-- Akun pembeli (login pakai nomor HP + password)
DROP TABLE IF EXISTS customers;
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Pesanan yang dibuat saat login tercatat atas akun itu (kosong kalau pembeli tidak login)
ALTER TABLE orders
    ADD COLUMN customer_id INT NULL,
    ADD CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL;

-- ===== Fitur baru: lupa password dan kode promo =====
-- (Kalau database kamu sudah berisi pesanan, jangan import file ini lagi. Pakai update-v5.sql saja.)

-- Permintaan reset password. Satu pembeli hanya punya satu permintaan aktif.
-- code_hash kosong = admin belum membuat kode. Kodenya sendiri tidak disimpan, hanya hash-nya.
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    code_hash VARCHAR(255) NULL,
    expires_at DATETIME NULL,
    attempts TINYINT NOT NULL DEFAULT 0,      -- berapa kali pembeli salah memasukkan kode
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_request_per_customer (customer_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Kode promo. percent = persen diskon, max_discount = batas diskon (rupiah), min_total = minimal belanja (rupiah)
CREATE TABLE promos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL,
    percent TINYINT NOT NULL,
    max_discount INT NOT NULL DEFAULT 0,      -- 0 = tanpa batas
    min_total INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1
);

INSERT INTO promos (code, description, percent, max_discount, min_total) VALUES
('SERA10', 'Diskon 10% untuk pesanan berikutnya. Terima kasih sudah jajan di Sera Coffee!', 10, 10000, 30000);

-- Pesanan mencatat kode promo yang dipakai dan besar diskonnya (total sudah dikurangi diskon)
ALTER TABLE orders
    ADD COLUMN promo_code VARCHAR(30) NULL,
    ADD COLUMN discount INT NOT NULL DEFAULT 0;
