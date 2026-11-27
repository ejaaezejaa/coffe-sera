<?php
require __DIR__ . '/auth.php';
require_admin();

$title        = 'Rekap Bulanan';
$admin_active = 'recap';

$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
];

// Bulan yang dipilih berbentuk "2026-11". Kalau kosong atau salah, pakai bulan ini.
$month = $_GET['month'] ?? '';
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    $month = date('Y-m');
}

$first = new DateTime($month . '-01');
$prev  = (clone $first)->modify('-1 month');
$next  = (clone $first)->modify('+1 month');

// Pesanan dihitung dari tanggal 1 jam 00:00 sampai sebelum tanggal 1 bulan berikutnya
$from = $first->format('Y-m-d H:i:s');
$to   = $next->format('Y-m-d H:i:s');

/**
 * Contoh hasil: "November 2026"
 *
 * @param DateTime $date
 * @param array    $nama_bulan  Daftar nama bulan, nomor 1 sampai 12
 */
function nama_bulan_tahun($date, $nama_bulan) {
    return $nama_bulan[(int) $date->format('n')] . ' ' . $date->format('Y');
}

// Pesanan bulan ini (yang dibatalkan tidak dihitung)
$stmt = $pdo->prepare(
    "SELECT * FROM orders
     WHERE created_at >= ? AND created_at < ? AND status <> 'batal'
     ORDER BY created_at, id"
);
$stmt->execute([$from, $to]);
$orders = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE created_at >= ? AND created_at < ? AND status = 'batal'");
$stmt->execute([$from, $to]);
$cancelled = (int) $stmt->fetchColumn();

// Isi semua pesanan bulan ini sekaligus (satu query, bukan satu query per pesanan)
$stmt = $pdo->prepare(
    "SELECT oi.order_id, oi.qty, oi.price, oi.options, p.name
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     JOIN products p ON p.id = oi.product_id
     WHERE o.created_at >= ? AND o.created_at < ? AND o.status <> 'batal'
     ORDER BY oi.id"
);
$stmt->execute([$from, $to]);

// Kelompokkan isi per pesanan, dan hitung total tiap menu
$items_by_order = [];
$menu_totals    = [];
$cups           = 0;

foreach ($stmt->fetchAll() as $row) {
    $items_by_order[$row['order_id']][] = $row;
    $cups += $row['qty'];

    $name = $row['name'];
    if (!isset($menu_totals[$name])) {
        $menu_totals[$name] = ['cups' => 0, 'sales' => 0];
    }
    $menu_totals[$name]['cups']  += $row['qty'];
    $menu_totals[$name]['sales'] += $row['qty'] * $row['price'];
}

// Menu paling laku di atas
uasort($menu_totals, function ($a, $b) {
    return $b['cups'] <=> $a['cups'];
});

// Pendapatan hanya dari pesanan yang sudah dibayar. Pembeli dihitung per nomor HP.
$revenue = 0;
$pending = 0;
$buyers  = [];

foreach ($orders as $o) {
    if (in_array($o['status'], ['dibayar', 'selesai'], true)) {
        $revenue += $o['total'];
    }
    if ($o['status'] === 'pending') {
        $pending += $o['total'];
    }
    $buyers[$o['phone']] = true;
}

require __DIR__ . '/header.php';
?>

<h1>Rekap Bulanan</h1>

<form method="get" class="month-form">
    <label for="month">Pilih bulan</label>
    <input type="month" id="month" name="month" value="<?= e($month) ?>" required>
    <button type="submit" class="btn small">Tampilkan</button>
</form>

<div class="filters">
    <a href="recap.php?month=<?= $prev->format('Y-m') ?>">&larr; <?= e(nama_bulan_tahun($prev, $nama_bulan)) ?></a>
    <a href="recap.php?month=<?= e($month) ?>" class="active"><?= e(nama_bulan_tahun($first, $nama_bulan)) ?></a>
    <?php if ($next->format('Y-m') <= date('Y-m')): ?>
        <a href="recap.php?month=<?= $next->format('Y-m') ?>"><?= e(nama_bulan_tahun($next, $nama_bulan)) ?> &rarr;</a>
    <?php endif; ?>
</div>

<div class="stats-grid">
    <div class="stat"><strong><?= count($orders) ?></strong><span>Pesanan</span></div>
    <div class="stat"><strong><?= count($buyers) ?></strong><span>Pembeli (per nomor HP)</span></div>
    <div class="stat"><strong><?= $cups ?></strong><span>Cup terjual</span></div>
    <div class="stat"><strong><?= rupiah($revenue) ?></strong><span>Pendapatan (sudah dibayar)</span></div>
    <div class="stat"><strong><?= rupiah($pending) ?></strong><span>Belum dibayar</span></div>
</div>

<?php if ($cancelled > 0): ?>
    <p class="hint"><?= $cancelled ?> pesanan dibatalkan bulan ini dan tidak dihitung.</p>
<?php endif; ?>

<?php if (count($orders) === 0): ?>
    <p>Belum ada pesanan di <?= e(nama_bulan_tahun($first, $nama_bulan)) ?>.</p>
<?php else: ?>

    <h2>Menu yang terjual</h2>

    <div class="table-scroll">
        <table class="table data-table">
            <thead>
                <tr><th>Menu</th><th>Cup terjual</th><th>Penjualan</th></tr>
            </thead>
            <tbody>
                <?php foreach ($menu_totals as $name => $m): ?>
                    <tr>
                        <td data-label="Menu"><?= e($name) ?></td>
                        <td data-label="Cup terjual"><?= $m['cups'] ?></td>
                        <td data-label="Penjualan"><?= rupiah($m['sales']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h2>Siapa yang membeli dan apa yang dibeli</h2>

    <div class="table-scroll">
        <table class="table data-table">
            <thead>
                <tr>
                    <th>No.</th><th>Tanggal</th><th>Pembeli</th><th>Yang dibeli</th><th>Total</th><th>Pembayaran</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td data-label="No."><a href="order.php?id=<?= $o['id'] ?>">#<?= $o['id'] ?></a></td>
                        <td data-label="Tanggal"><?= e(tanggal($o['created_at'])) ?></td>
                        <td data-label="Pembeli">
                            <div class="cell-text"><?= e($o['customer_name']) ?><br><small><?= e($o['phone']) ?></small></div>
                        </td>
                        <td data-label="Yang dibeli">
                            <div class="cell-text">
                                <?php foreach ($items_by_order[$o['id']] ?? [] as $item): ?>
                                    <?= $item['qty'] ?>x <?= e($item['name']) ?> <small>(<?= e($item['options']) ?>)</small><br>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td data-label="Total">
                            <div class="cell-text">
                                <?= rupiah($o['total']) ?>
                                <?php if ((int) $o['discount'] > 0): ?>
                                    <br><small>Promo <?= e($o['promo_code']) ?> (-<?= rupiah($o['discount']) ?>)</small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td data-label="Pembayaran"><?= e(payment_label($o['payment_method'])) ?></td>
                        <td data-label="Status"><span class="status status-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
