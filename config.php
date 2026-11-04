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

function get_product($pdo, $id) {
    $stmt = $pdo->prepare(
        'SELECT p.*, COALESCE(AVG(r.rating), 0) AS avg_rating, COUNT(r.id) AS review_count
         FROM products p
         LEFT JOIN reviews r ON r.product_id = p.id
         WHERE p.id = ?
         GROUP BY p.id'
    );
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// "Ijlal Assami" jadi "I***l A****i": huruf pertama dan terakhir tiap kata tetap terlihat
function mask_name($name) {
    $masked = [];

    foreach (preg_split('/\s+/', trim($name)) as $word) {
        $len = mb_strlen($word);

        if ($len <= 1) {
            $masked[] = $word;
        } elseif ($len === 2) {
            $masked[] = mb_substr($word, 0, 1) . '*';
        } else {
            $masked[] = mb_substr($word, 0, 1) . str_repeat('*', $len - 2) . mb_substr($word, -1);
        }
    }

    return implode(' ', $masked);
}

function review_name($name, $show_name) {
    return ((int) $show_name === 1) ? $name : mask_name($name);
}

// Pilihan untuk tiap kategori. Iced dan Hot sengaja dibuat sama bentuknya: 1 grup, 3 pilihan.
// Kunci (normal, less, none) dipakai di keranjang. Tulisan (Normal, Less Sugar) yang tampil ke pembeli.
function product_options($category) {
    if ($category === 'iced') {
        return [
            'label'   => 'Tingkat gula',
            'choices' => ['normal' => 'Normal', 'less' => 'Less Sugar', 'none' => 'No Sugar'],
        ];
    }

    return [
        'label'   => 'Tingkat panas',
        'choices' => ['biasa' => 'Biasa', 'panas' => 'Panas', 'banget' => 'Panas Banget'],
    ];
}

// Isi keranjang. Kunci di session berbentuk "3_less" = menu nomor 3 dengan pilihan "less",
// jadi menu yang sama dengan pilihan beda tampil sebagai dua baris.
function get_cart_items($pdo) {
    $items = [];
    $stmt  = $pdo->prepare('SELECT * FROM products WHERE id = ?');

    foreach ($_SESSION['cart'] ?? [] as $key => $qty) {
        $parts = explode('_', (string) $key, 2);
        $stmt->execute([(int) $parts[0]]);
        $p = $stmt->fetch();

        $choices = $p ? product_options($p['category'])['choices'] : [];
        $code    = $parts[1] ?? '';

        if ($p && isset($choices[$code])) {
            $p['key']      = (string) $key;
            $p['qty']      = $qty;
            $p['option']   = $choices[$code];
            $p['subtotal'] = $p['price'] * $qty;
            $items[]       = $p;
        }
    }

    // Buang baris yang sudah tidak valid (menu dihapus, format lama)
    $_SESSION['cart'] = array_intersect_key($_SESSION['cart'] ?? [], array_flip(array_column($items, 'key')));

    return $items;
}
