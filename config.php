<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

$host   = 'localhost';
$dbname = 'sera_coffee';
$user   = 'root';
$pass   = '';

$instagram_url = 'https://www.instagram.com/homeof_coffee';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . $e->getMessage());
}

/**
 * Mengubah angka jadi format rupiah, contoh: 48000 jadi "Rp 48.000"
 *
 * @param int|float|string|null $angka  Jumlah uang
 */
function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function cart_count() {
    if (!isset($_SESSION['cart'])) {
        return 0;
    }
    return array_sum($_SESSION['cart']);
}

/**
 * Mengamankan teks sebelum ditampilkan di HTML (mencegah XSS)
 *
 * @param string|int|float|null $teks  Teks yang mau ditampilkan
 */
function e($teks) {
    return htmlspecialchars($teks, ENT_QUOTES, 'UTF-8');
}

/**
 * Daftar menu beserta rata-rata rating
 *
 * @param PDO    $pdo
 * @param string $cat    all, iced, atau hot
 * @param int    $limit  0 = tanpa batas
 */
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

/**
 * Rating jadi bintang, contoh: 4 jadi "★★★★☆"
 *
 * @param int|float|string $rating  Nilai 0 sampai 5
 */
function stars($rating) {
    $full = (int) round($rating);
    return str_repeat('★', $full) . str_repeat('☆', 5 - $full);
}

/**
 * Baris rating: bintang, nilai rata-rata, dan jumlah ulasan
 *
 * @param int|float|string $avg    Rata-rata rating
 * @param int|string       $count  Jumlah ulasan
 */
function rating_line($avg, $count) {
    return '<p class="rating"><span class="stars">' . stars($avg) . '</span> '
         . number_format($avg, 1) . ' (' . (int) $count . ' ulasan)</p>';
}

/**
 * Satu menu beserta rata-rata rating, atau false kalau tidak ditemukan
 *
 * @param PDO        $pdo
 * @param int|string $id   Nomor menu
 */
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

/**
 * "Ijlal Assami" jadi "I***l A****i": huruf pertama dan terakhir tiap kata tetap terlihat
 *
 * @param string $name  Nama lengkap
 */
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

/**
 * Nama penulis ulasan: nama asli kalau dia mengizinkan, kalau tidak disamarkan
 *
 * @param string     $name       Nama lengkap
 * @param int|string $show_name  1 = boleh ditampilkan
 */
function review_name($name, $show_name) {
    return ((int) $show_name === 1) ? $name : mask_name($name);
}

// Pilihan gula, dipakai semua menu (Iced maupun Hot)
function sugar_choices() {
    return ['normal' => 'Normal', 'less' => 'Less Sugar', 'none' => 'No Sugar'];
}

/**
 * Pilihan untuk tiap kategori. Iced dan Hot sama bentuknya: 2 grup pilihan.
 *   Iced : Es (Normal / Less Ice) dan Tingkat gula
 *   Hot  : Tingkat panas (Biasa / Panas / Panas Banget) dan Tingkat gula
 * 'key' = nama field di form, 'short' = singkatan yang tampil di keranjang dan struk
 *
 * @param string $category  iced atau hot
 */
function product_options($category) {
    $sugar = [
        'key'     => 'sugar',
        'short'   => 'Gula',
        'label'   => 'Tingkat gula',
        'choices' => sugar_choices(),
    ];

    if ($category === 'iced') {
        $ice = [
            'key'     => 'ice',
            'short'   => 'Es',
            'label'   => 'Es',
            'choices' => ['normal' => 'Normal', 'less' => 'Less Ice'],
        ];

        return [$ice, $sugar];
    }

    $heat = [
        'key'     => 'heat',
        'short'   => 'Panas',
        'label'   => 'Tingkat panas',
        'choices' => ['biasa' => 'Biasa', 'panas' => 'Panas', 'banget' => 'Panas Banget'],
    ];

    return [$heat, $sugar];
}

/**
 * Isi keranjang. Kunci di session berbentuk "3_less_less" = menu nomor 3 dengan pilihan
 * "less" (es) dan "less" (gula), urutannya sesuai grup di product_options, jadi menu yang sama
 * dengan pilihan beda tampil sebagai baris terpisah.
 *
 * @param PDO $pdo
 */
function get_cart_items($pdo) {
    $items = [];
    $stmt  = $pdo->prepare('SELECT * FROM products WHERE id = ?');

    foreach ($_SESSION['cart'] ?? [] as $key => $qty) {
        $codes = explode('_', (string) $key);
        $stmt->execute([(int) array_shift($codes)]);
        $p = $stmt->fetch();

        if (!$p) {
            continue;
        }

        $groups = product_options($p['category']);
        $labels = [];

        if (count($codes) === count($groups)) {
            foreach ($groups as $i => $group) {
                if (!isset($group['choices'][$codes[$i]])) {
                    $labels = [];
                    break;
                }

                $labels[] = $group['short'] . ': ' . $group['choices'][$codes[$i]];
            }
        }

        if (count($labels) === count($groups)) {
            $p['key']      = (string) $key;
            $p['qty']      = $qty;
            $p['option']   = implode(', ', $labels);
            $p['subtotal'] = $p['price'] * $qty;
            $items[]       = $p;
        }
    }

    // Buang baris yang sudah tidak valid (menu dihapus, format lama)
    $_SESSION['cart'] = array_intersect_key($_SESSION['cart'] ?? [], array_flip(array_column($items, 'key')));

    return $items;
}

// Token rahasia di setiap form, supaya form tidak bisa dikirim dari website lain
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check() {
    $token = $_SESSION['csrf'] ?? '';

    // Token kosong tidak boleh dianggap cocok dengan kiriman yang kosong
    if ($token === '' || !hash_equals($token, (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Permintaan tidak valid. Kembali dan muat ulang halaman.');
    }
}

// Tombol ikon mata di kolom password: mata biasa saat password tersembunyi, mata dicoret saat terlihat
function password_toggle() {
    return '<button type="button" class="toggle-password" aria-label="Lihat password">'
        . '<svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
        . '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
        . '<svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
        . '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>'
        . '<line x1="1" y1="1" x2="23" y2="23"/></svg>'
        . '</button>';
}

// ===== Akun pembeli =====

/**
 * Nomor HP disimpan seragam: hanya angka, diawali 0 (+62812... jadi 0812...)
 *
 * @param string|null $phone  Nomor HP seperti yang diketik pembeli
 */
function normalize_phone($phone) {
    $digits = preg_replace('/\D/', '', (string) $phone);

    if (strpos($digits, '62') === 0) {
        $digits = '0' . substr($digits, 2);
    } elseif (strpos($digits, '8') === 0) {
        $digits = '0' . $digits;
    }

    return $digits;
}

/**
 * Nomor HP yang sudah dinormalkan: diawali 0 dan terdiri dari 9 sampai 14 angka
 *
 * @param string $phone  Hasil normalize_phone()
 */
function valid_phone($phone) {
    return (bool) preg_match('/^0[0-9]{8,13}$/', $phone);
}

/**
 * Data pembeli yang sedang login, atau null kalau belum login
 *
 * @param PDO $pdo
 */
function current_customer($pdo) {
    if (empty($_SESSION['customer_id'])) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$_SESSION['customer_id']]);
    $customer = $stmt->fetch();

    if (!$customer) {
        unset($_SESSION['customer_id'], $_SESSION['customer_name']);
        return null;
    }

    return $customer;
}

/**
 * Menandai pembeli sebagai sudah login (id session diganti supaya lebih aman)
 *
 * @param array $customer  Satu baris dari tabel customers
 */
function customer_login($customer) {
    session_regenerate_id(true);
    $_SESSION['customer_id']   = (int) $customer['id'];
    $_SESSION['customer_name'] = $customer['name'];
}

/**
 * Setelah login hanya boleh diarahkan ke halaman-halaman ini
 *
 * @param string|null $next  Tujuan setelah login, dari parameter next
 */
function safe_next($next) {
    return in_array($next, ['checkout.php', 'account.php', 'cart.php', 'menu.php'], true) ? $next : 'account.php';
}


// Reset password: kode dari admin berlaku sekian menit, dan salah memasukkan kode dibatasi
const RESET_CODE_MINUTES = 30;
const RESET_MAX_ATTEMPTS = 5;
