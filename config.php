<?php
session_start();

$host   = 'localhost';
$dbname = 'sera_coffee';
$user   = 'root';
$pass   = '';

$instagram_url = 'https://www.instagram.com/homeof_coffee';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . $e->getMessage());
}

function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function cart_count() {
    if (!isset($_SESSION['cart'])) {
        return 0;
    }
    return array_sum($_SESSION['cart']);
}

function e($teks) {
    return htmlspecialchars($teks, ENT_QUOTES, 'UTF-8');
}

function get_products($pdo, $cat = 'all', $limit = 0) {
    $sql = 'SELECT p.*, COALESCE(AVG(r.rating), 0) AS avg_rating, COUNT(r.id) AS review_count
            FROM products p
            LEFT JOIN reviews r ON r.product_id = p.id';
    $params = [];

    if ($cat === 'iced' || $cat === 'hot') {
        $sql .= ' WHERE p.category = ?';
        $params[] = $cat;
    }

    $sql .= ' GROUP BY p.id ORDER BY p.id';

    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function stars($rating) {
    $full = (int) round($rating);
    return str_repeat('★', $full) . str_repeat('☆', 5 - $full);
}

function rating_line($avg, $count) {
    return '<p class="rating"><span class="stars">' . stars($avg) . '</span> '
         . number_format($avg, 1) . ' (' . (int) $count . ' ulasan)</p>';
}
