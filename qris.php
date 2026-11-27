<?php
require 'config.php';
require 'includes/orders.php';

header('Cache-Control: no-store');
expire_old_orders($pdo);

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

// Hanya pembeli yang baru memesan yang boleh membuka halaman bayar pesanannya
if (!$order || ($_SESSION['last_order'] ?? 0) !== $id) {
    header('Location: index.php');
    exit;
}

// Halaman ini khusus pesanan QRIS yang belum dibatalkan
if ($order['payment_method'] !== 'qris' || $order['status'] === 'batal') {
    header('Location: success.php?id=' . $id);
    exit;
}

// Foto QRIS toko: simpan di folder images dengan nama qris.png / qris.jpg / qris.jpeg / qris.webp
$qris_image = 'images/qris-placeholder.png';
$sudah_ada  = false;

foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
    if (is_file(__DIR__ . '/images/qris.' . $ext)) {
        $qris_image = 'images/qris.' . $ext;
        $sudah_ada  = true;
        break;
    }
}

$sudah_bayar = in_array($order['status'], ['dibayar', 'selesai'], true);

$title  = 'Bayar dengan QRIS';
$active = 'cart';

require 'includes/header.php';
?>

<section class="section narrow">
    <div class="qris-box">
        <h1>Bayar dengan QRIS</h1>
        <p>Pesanan <strong>#<?= $order['id'] ?></strong> atas nama <?= e($order['customer_name']) ?></p>

        <?php if ($sudah_bayar): ?>
            <p class="alert">Pembayaran sudah diterima. Terima kasih!</p>
        <?php endif; ?>

        <p class="qris-total">
            Total yang harus dibayar
            <strong><?= rupiah($order['total']) ?></strong>
        </p>

        <img src="<?= e($qris_image) ?>" alt="QRIS Sera Coffee" class="qris-image">

        <?php if ($sudah_ada): ?>
            <p><a href="<?= e($qris_image) ?>" download class="btn btn-outline small">Simpan gambar QRIS</a></p>
        <?php endif; ?>

        <ol class="qris-steps">
            <li>Buka aplikasi e-wallet atau mobile banking yang mendukung QRIS.</li>
            <li>Scan kode QR di atas.</li>
            <li>Masukkan nominal <strong><?= rupiah($order['total']) ?></strong> sesuai total pesanan.</li>
            <li>Simpan bukti bayar, lalu tunjukkan ke kasir atau kirim lewat halaman <a href="contact.php">Contact</a> dengan menyebut nomor pesanan #<?= $order['id'] ?>.</li>
        </ol>

        <p class="note">Status pesanan berubah jadi "Sudah dibayar" jika uang sudah masuk.</p>

        <?php if (payment_deadline($order) !== ''): ?>
            <p class="note">Selesaikan pembayaran sebelum <strong><?= e(payment_deadline($order)) ?></strong>. Kalau lewat, pesanan dibatalkan otomatis.</p>
        <?php endif; ?>

        <p class="qris-actions">
            <a href="success.php?id=<?= $order['id'] ?>" class="btn">Saya sudah bayar</a>
            <a href="receipt.php?id=<?= $order['id'] ?>&from=qris" class="btn btn-outline">Lihat Struk</a>
        </p>

        <?php if ($order['status'] === 'pending'): ?>
            <form method="post" action="cancel.php" onsubmit="return confirm('Batalkan pesanan ini?');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                <button type="submit" class="link-btn">Batalkan pesanan</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
