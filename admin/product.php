<?php
require __DIR__ . '/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit;
}

// Angka penjualan (pesanan yang dibatalkan tidak dihitung)
$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(oi.qty), 0) AS sold,
            COALESCE(SUM(CASE WHEN o.status IN " . PAID_STATUSES . " THEN oi.qty * oi.price ELSE 0 END), 0) AS revenue,
            COUNT(DISTINCT oi.order_id) AS orders
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE oi.product_id = ? AND o.status <> 'batal'"
);
$stmt->execute([$id]);
$sales = $stmt->fetch();

// Pilihan yang paling sering dipesan (Normal, Less Sugar, Panas, dst.)
$stmt = $pdo->prepare(
    "SELECT oi.options, SUM(oi.qty) AS qty
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE oi.product_id = ? AND o.status <> 'batal'
     GROUP BY oi.options
     ORDER BY qty DESC"
);
$stmt->execute([$id]);
$by_option = $stmt->fetchAll();

// Daftar pembeli
$stmt = $pdo->prepare(
    'SELECT o.id, o.created_at, o.customer_name, o.phone, o.status, oi.qty, oi.price, oi.options
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE oi.product_id = ?
     ORDER BY o.id DESC
     LIMIT 200'
);
$stmt->execute([$id]);
$buyers = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS total FROM reviews WHERE product_id = ?');
$stmt->execute([$id]);
$rating = $stmt->fetch();

$title        = $product['name'];
$admin_active = 'dashboard';

require __DIR__ . '/header.php';
?>

<p><a href="index.php">&larr; Dashboard</a></p>

<div class="admin-product">
    <img src="../images/<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>">
    <div>
        <h1><?= e($product['name']) ?></h1>
        <p><?= rupiah($product['price']) ?> | <?= $product['category'] === 'iced' ? 'Iced' : 'Hot' ?></p>
        <?= rating_line($rating['avg_rating'], $rating['total']) ?>
    </div>
</div>

<div class="stats-grid">
    <div class="stat"><strong><?= (int) $sales['sold'] ?></strong><span>Cup terjual</span></div>
    <div class="stat"><strong><?= (int) $sales['orders'] ?></strong><span>Jumlah pesanan</span></div>
    <div class="stat"><strong><?= rupiah($sales['revenue']) ?></strong><span>Pendapatan (sudah dibayar)</span></div>
</div>

<h2>Pilihan yang dipesan</h2>

<?php if (count($by_option) === 0): ?>
    <p>Belum ada yang memesan menu ini.</p>
<?php else: ?>
    <div class="chips">
        <?php foreach ($by_option as $row): ?>
            <span class="tag"><?= e($row['options']) ?>: <?= (int) $row['qty'] ?> cup</span>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<h2>Siapa yang membeli</h2>

<?php if (count($buyers) === 0): ?>
    <p>Belum ada pembeli.</p>
<?php else: ?>
    <div class="table-scroll">
        <table class="table data-table">
            <thead>
                <tr>
                    <th>Pesanan</th><th>Waktu</th><th>Pembeli</th><th>No. HP</th><th>Pilihan</th><th>Jumlah</th><th>Subtotal</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($buyers as $b): ?>
                    <tr>
                        <td data-label="Pesanan"><a href="order.php?id=<?= $b['id'] ?>">#<?= $b['id'] ?></a></td>
                        <td data-label="Waktu"><?= e(tanggal($b['created_at'])) ?></td>
                        <td data-label="Pembeli"><?= e($b['customer_name']) ?></td>
                        <td data-label="No. HP"><?= e($b['phone']) ?></td>
                        <td data-label="Pilihan"><?= e($b['options']) ?></td>
                        <td data-label="Jumlah"><?= $b['qty'] ?></td>
                        <td data-label="Subtotal"><?= rupiah($b['price'] * $b['qty']) ?></td>
                        <td data-label="Status"><span class="status status-<?= e($b['status']) ?>"><?= e(status_label($b['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
