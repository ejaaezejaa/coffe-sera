CREATE DATABASE IF NOT EXISTS sera_coffee CHARACTER SET utf8mb4;
USE sera_coffee;

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
    category VARCHAR(20) NOT NULL    -- 'iced' atau 'hot'
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
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Rating dan masukan dari pembeli (satu menu hanya bisa diulas sekali per pesanan)
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    rating TINYINT NOT NULL,         -- 1 sampai 5
    comment VARCHAR(500),
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

INSERT INTO products (name, description, price, image, category) VALUES
('Es Kopi Susu Sera', 'Espresso, susu segar, dan gula aren. Menu andalan kami.', 22000, 'es-kopi-susu.jpg', 'iced'),
('Iced Caramel Latte', 'Latte dingin dengan lapisan karamel manis.', 25000, 'kopi-vertikal.jpg', 'iced'),
('Iced Latte', 'Espresso dan susu dingin yang berlapis cantik.', 24000, 'iced-latte.jpeg', 'iced'),
('Hot Cappuccino', 'Espresso dengan susu steam dan foam tebal.', 24000, 'cappuccino.jpeg', 'hot'),
('Hot Latte', 'Susu steam lembut dengan latte art.', 24000, 'latte-art.jpeg', 'hot'),
('Kopi Hitam', 'Kopi hitam tanpa susu, pekat dan harum.', 15000, 'kopi-hitam.jpeg', 'hot'),
('Hot Mocha', 'Hangat dan manis, dengan whipped cream dan saus karamel.', 26000, 'mocha-whipped.jpeg', 'hot'),
('Coffee To Go', 'Kopi hangat dalam cup, praktis dibawa ke mana saja.', 20000, 'kopi-cup.jpeg', 'hot');
