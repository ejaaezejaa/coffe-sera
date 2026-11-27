<?php
// Status yang berarti uangnya sudah diterima (dipakai untuk menghitung pendapatan)
const PAID_STATUSES = "('dibayar', 'selesai')";

// Pesanan QRIS / E-Wallet yang belum dibayar dibatalkan otomatis setelah sekian menit
const PAYMENT_WINDOW_MINUTES = 60;

/**
 * Membatalkan pesanan QRIS / E-Wallet yang belum dibayar dan sudah lewat batas waktu
 *
 * @param PDO $pdo
 */
function expire_old_orders($pdo) {
    $pdo->exec(
        "UPDATE orders SET status = 'batal'
         WHERE status = 'pending'
           AND payment_method IN ('qris', 'ewallet')
           AND created_at < (NOW() - INTERVAL " . (int) PAYMENT_WINDOW_MINUTES . " MINUTE)"
    );
}

/**
 * Jam batas pembayaran, atau teks kosong kalau pesanan tidak punya batas (sudah dibayar / bayar di kasir)
 *
 * @param array $order  Satu baris dari tabel orders
 */
function payment_deadline($order) {
    if ($order['status'] !== 'pending' || !in_array($order['payment_method'], ['qris', 'ewallet'], true)) {
        return '';
    }

    return date('H:i', strtotime($order['created_at']) + PAYMENT_WINDOW_MINUTES * 60) . ' WIB';
}

function status_options() {
    return [
        'pending' => 'Menunggu pembayaran',
        'dibayar' => 'Sudah dibayar',
        'selesai' => 'Selesai',
        'batal'   => 'Dibatalkan',
    ];
}

/**
 * Nama status dalam bahasa Indonesia, contoh: "dibayar" jadi "Sudah dibayar"
 *
 * @param string $status  pending, dibayar, selesai, atau batal
 */
function status_label($status) {
    return status_options()[$status] ?? $status;
}

/**
 * Nama metode pembayaran untuk ditampilkan
 *
 * @param string $method  qris, ewallet, cod, dst.
 */
function payment_label($method) {
    $labels = [
        'qris'     => 'QRIS',
        'transfer' => 'Transfer Bank',
        'ewallet'  => 'E-Wallet (DANA / GoPay dll)',
        'cod'      => 'Bayar di Kasir (Cash)',
    ];

    return $labels[$method] ?? $method;
}

/**
 * Tanggal dan jam dari database jadi bentuk "19/11/2026 23:53 WIB"
 *
 * @param string $waktu  Waktu dari database, contoh: 2026-11-19 23:53:00
 */
function tanggal($waktu) {
    return date('d/m/Y H:i', strtotime($waktu)) . ' WIB';
}

/**
 * Isi satu pesanan: nama menu, jumlah, harga, dan pilihannya
 *
 * @param PDO        $pdo
 * @param int|string $order_id  Nomor pesanan
 */
function get_order_items($pdo, $order_id) {
    $stmt = $pdo->prepare(
        'SELECT products.name, order_items.qty, order_items.price, order_items.options
         FROM order_items
         JOIN products ON products.id = order_items.product_id
         WHERE order_items.order_id = ?
         ORDER BY order_items.id'
    );
    $stmt->execute([$order_id]);
    return $stmt->fetchAll();
}
