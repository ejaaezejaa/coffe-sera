<?php
require __DIR__ . '/auth.php';
require_admin();

$title        = 'Dashboard';
$admin_active = 'dashboard';

// Pesanan yang dibatalkan tidak dihitung. Pendapatan hanya dari pesanan yang sudah dibayar.
$stats = $pdo->query(
    "SELECT COUNT(*) AS orders,
            COALESCE(SUM(CASE WHEN status IN " . PAID_STATUSES . " THEN total ELSE 0 END), 0) AS revenue,
            COALESCE(SUM(status = 'pending'), 0) AS pending,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN total ELSE 0 END), 0) AS pending_total
     FROM orders
     WHERE status <> 'batal'"
)->fetch();

$cups = $pdo->query(
    "SELECT COALESCE(SUM(oi.qty), 0)
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.status <> 'batal'"
)->fetchColumn();

// Tiap menu beserta jumlah terjual dan pendapatannya, yang paling laku di atas
$menus = $pdo->query(
    "SELECT p.*, COALESCE(SUM(x.qty), 0) AS sold,
            COALESCE(SUM(CASE WHEN x.status IN " . PAID_STATUSES . " THEN x.qty * x.price ELSE 0 END), 0) AS revenue
     FROM products p
     LEFT JOIN (
         SELECT oi.product_id, oi.qty, oi.price, o.status
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE o.status <> 'batal'
     ) x ON x.product_id = p.id
     GROUP BY p.id
     ORDER BY sold DESC, p.id"
)->fetchAll();

$recent = $pdo->query('SELECT * FROM orders ORDER BY id DESC LIMIT 8')->fetchAll();

// Permintaan reset password yang kodenya belum dibuat
$reset_waiting = (int) $pdo->query('SELECT COUNT(*) FROM password_resets WHERE code_hash IS NULL')->fetchColumn();

require __DIR__ . '/header.php';
?>

<h1>Dashboard</h1>

<?php if ($reset_waiting > 0): ?>
    <p class="alert error"><?= $reset_waiting ?> pembeli menunggu kode reset password. <a href="resets.php">Buka</a></p>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat"><strong><?= (int) $stats['orders'] ?></strong><span>Total pesanan</span></div>
    <div class="stat"><strong><?= (int) $cups ?></strong><span>Cup terjual</span></div>
    <div class="stat"><strong><?= rupiah($stats['revenue']) ?></strong><span>Total pendapatan (sudah dibayar)</span></div>
    <div class="stat">
        <strong><?= (int) $stats['pending'] ?></strong>
        <span>Menunggu pembayaran (<?= rupiah($stats['pending_total']) ?> belum diterima)</span>
    </div>
</div>

<h2>Menu (klik untuk lihat siapa yang membeli)</h2>

<div class="grid">
    <?php foreach ($menus as $m): ?>
        <div class="card">
            <img src="../images/<?= e($m['image']) ?>" alt="<?= e($m['name']) ?>">
            <div class="card-body">
                <h3><a href="product.php?id=<?= $m['id'] ?>" class="card-link"><?= e($m['name']) ?></a></h3>
                <p class="card-stat">Terjual: <strong><?= (int) $m['sold'] ?> cup</strong></p>
                <p class="price"><?= rupiah($m['revenue']) ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<h2>Pesanan terbaru</h2>

<?php if (count($recent) === 0): ?>
    <p>Belum ada pesanan masuk.</p>
<?php else: ?>
    <div class="table-scroll">
        <table class="table data-table">
            <thead>
                <tr>
                    <th>No.</th><th>Waktu</th><th>Pembeli</th><th>Total</th><th>Pembayaran</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $o): ?>
                    <tr>
                        <td data-label="No.">#<?= $o['id'] ?></td>
                        <td data-label="Waktu"><?= e(tanggal($o['created_at'])) ?></td>
                        <td data-label="Pembeli"><?= e($o['customer_name']) ?></td>
                        <td data-label="Total"><?= rupiah($o['total']) ?></td>
                        <td data-label="Pembayaran"><?= e(payment_label($o['payment_method'])) ?></td>
                        <td data-label="Status"><span class="status status-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
                        <td><a href="order.php?id=<?= $o['id'] ?>">Detail</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p><a href="orders.php">Lihat semua pesanan &rarr;</a></p>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
