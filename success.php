<?php
require 'config.php';
require 'includes/orders.php';
require 'includes/promo.php';

header('Cache-Control: no-store');
expire_old_orders($pdo);

$title  = 'Pesanan Berhasil';
$active = 'cart';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order || ($_SESSION['last_order'] ?? 0) !== $id) {
    header('Location: index.php');
    exit;
}

$instruksi = [
    'qris'    => 'Scan QRIS Sera Coffee di halaman pembayaran QRIS, lalu tunjukkan bukti bayar ke kasir atau kirim lewat halaman Contact.',
    'ewallet' => 'Kirim ke DANA / GoPay / OVO 0812-3456-7890 a.n. Sera Coffee, lalu kirim bukti bayar lewat halaman Contact atau tunjukkan di kasir.',
    'cod'     => 'Bayar tunai di kasir saat mengambil pesanan.',
];

$stmt = $pdo->prepare(
    'SELECT products.id AS product_id, products.name, order_items.qty, order_items.price, order_items.options
     FROM order_items
     JOIN products ON products.id = order_items.product_id
     WHERE order_items.order_id = ?'
);
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$ordered = [];
foreach ($items as $item) {
    $ordered[$item['product_id']] = $item['name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ratings  = (array) ($_POST['rating'] ?? []);
    $comments = (array) ($_POST['comment'] ?? []);
    $show_name = (($_POST['show_name'] ?? '0') === '1') ? 1 : 0;

    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO reviews (order_id, product_id, rating, comment, show_name) VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($ordered as $pid => $product_name) {
        $rating  = (int) ($ratings[$pid] ?? 0);
        $comment = $comments[$pid] ?? '';

        if (!is_string($comment)) {
            $comment = '';
        }

        if ($rating >= 1 && $rating <= 5) {
            $stmt->execute([$id, $pid, $rating, mb_substr(trim($comment), 0, 500), $show_name]);
        }
    }

    header('Location: success.php?id=' . $id . '&reviewed=1');
    exit;
}

$stmt = $pdo->prepare('SELECT product_id, rating, comment, show_name FROM reviews WHERE order_id = ?');
$stmt->execute([$id]);
$promo = get_active_promo($pdo);

// Pembeli yang sudah memakai kode ini tidak perlu ditawari lagi (satu akun satu kali)
if ($promo && !empty($_SESSION['customer_id']) && promo_used($pdo, $promo['code'], $_SESSION['customer_id'])) {
    $promo = false;
}

$reviewed = [];
foreach ($stmt->fetchAll() as $r) {
    $reviewed[$r['product_id']] = $r;
}

require 'includes/header.php';
?>

<section class="section narrow">
    <?php if ($order['status'] === 'batal'): ?>
        <h1>Pesanan Dibatalkan</h1>
        <p>Pesanan <strong>#<?= $order['id'] ?></strong> atas nama <?= e($order['customer_name']) ?> sudah dibatalkan.</p>
    <?php else: ?>
        <h1>Pesanan Berhasil!</h1>
        <p>Terima kasih, <?= e($order['customer_name']) ?>. Nomor pesananmu: <strong>#<?= $order['id'] ?></strong></p>
    <?php endif; ?>

    <div class="box">
        <?php foreach ($items as $item): ?>
            <p class="row">
                <span><?= e($item['name']) ?> (<?= e($item['options']) ?>) x<?= $item['qty'] ?></span>
                <span><?= rupiah($item['price'] * $item['qty']) ?></span>
            </p>
        <?php endforeach; ?>
        <?php if ((int) $order['discount'] > 0): ?>
            <p class="row"><span>Promo <?= e($order['promo_code']) ?></span><span>-<?= rupiah($order['discount']) ?></span></p>
        <?php endif; ?>
        <hr>
        <p class="row"><strong>Total</strong><strong><?= rupiah($order['total']) ?></strong></p>
    </div>

    <?php if ($order['status'] === 'batal'): ?>
        <div class="box">
            <p class="alert error">Pesanan ini sudah dibatalkan, jadi tidak perlu dibayar.</p>
            <p><a href="menu.php" class="btn">Pesan Lagi</a></p>
        </div>
    <?php else: ?>
    <div class="box">
        <h3>Cara Pembayaran</h3>
        <p><?= e($instruksi[$order['payment_method']] ?? '') ?></p>
        <p>Status: <strong><?= e(status_label($order['status'])) ?></strong></p>

        <?php if (payment_deadline($order) !== ''): ?>
            <p class="note">Selesaikan pembayaran sebelum <strong><?= e(payment_deadline($order)) ?></strong>. Kalau lewat, pesanan dibatalkan otomatis.</p>
        <?php endif; ?>

        <?php if (in_array($order['status'], ['dibayar', 'selesai'], true)): ?>
            <p class="alert">Pembayaran sudah diterima. Terima kasih!</p>
        <?php endif; ?>

        <?php if ($order['payment_method'] === 'qris' && $order['status'] === 'pending'): ?>
            <p><a href="qris.php?id=<?= $order['id'] ?>" class="btn">Tampilkan QRIS</a></p>
        <?php endif; ?>

        <?php if ($order['status'] === 'pending'): ?>
            <form method="post" action="cancel.php" onsubmit="return confirm('Batalkan pesanan ini?');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= $order['id'] ?>">
                <button type="submit" class="link-btn">Batalkan pesanan</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($promo): ?>
        <div class="box promo-box">
            <h3>Promo untuk Pesanan Berikutnya</h3>
            <p><?= e($promo['description']) ?></p>
            <p>
                <span class="promo-code" id="promo-code"><?= e($promo['code']) ?></span>
                <button type="button" class="btn btn-outline small" id="copy-promo">Salin kode</button>
            </p>
            <p class="hint"><?= e(promo_rule_text($promo)) ?>. Masukkan kode ini di kolom kode promo pada halaman Checkout. Kamu harus login, dan satu akun hanya bisa memakainya satu kali.</p>
        </div>
    <?php endif; ?>

    <div class="box">
        <h3>Beri Rating &amp; Masukan</h3>

        <?php if (isset($_GET['reviewed'])): ?>
            <p class="alert">Terima kasih atas rating dan masukanmu!</p>
        <?php endif; ?>

        <form method="post" class="form">
            <?php $belum = 0; ?>
            <?php foreach ($ordered as $pid => $product_name): ?>
                <div class="review-item">
                    <p class="review-name"><?= e($product_name) ?></p>

                    <?php if (isset($reviewed[$pid])): ?>
                        <p class="rating"><span class="stars"><?= stars($reviewed[$pid]['rating']) ?></span></p>
                        <p><?= e($reviewed[$pid]['comment']) ?></p>
                        <p class="hint">Tampil sebagai: <?= e(review_name($order['customer_name'], $reviewed[$pid]['show_name'])) ?></p>
                    <?php else: ?>
                        <?php $belum++; ?>
                        <div class="star-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating[<?= $pid ?>]" value="<?= $i ?>" id="star-<?= $pid ?>-<?= $i ?>">
                                <label for="star-<?= $pid ?>-<?= $i ?>">★</label>
                            <?php endfor; ?>
                        </div>
                        <p class="hint">Bintang paling kiri = 1 (kurang), paling kanan = 5 (enak banget)</p>
                        <textarea name="comment[<?= $pid ?>]" rows="2" maxlength="500" placeholder="Masukan (opsional)"></textarea>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if ($belum > 0): ?>
                <fieldset class="name-choice">
                    <legend>Nama yang tampil di ulasan</legend>
                    <label class="check">
                        <input type="radio" name="show_name" value="0" checked>
                        <span>Sensor namaku: <strong><?= e(mask_name($order['customer_name'])) ?></strong></span>
                    </label>
                    <label class="check">
                        <input type="radio" name="show_name" value="1">
                        <span>Tampilkan namaku: <strong><?= e($order['customer_name']) ?></strong></span>
                    </label>
                </fieldset>
                <button type="submit" class="btn small">Kirim Ulasan</button>
            <?php endif; ?>
        </form>
    </div>
    <?php endif; ?>

    <a href="receipt.php?id=<?= $order['id'] ?>" class="btn btn-outline">Lihat Struk</a>
    <a href="menu.php" class="btn">Pesan Lagi</a>
</section>

<?php if ($promo): ?>
<script>
// Tombol "Salin kode": disembunyikan kalau browser tidak mendukung penyalinan
const copyButton = document.getElementById('copy-promo');

if (!navigator.clipboard) {
    copyButton.hidden = true;
} else {
    copyButton.addEventListener('click', function () {
        navigator.clipboard.writeText(document.getElementById('promo-code').textContent);
        copyButton.textContent = 'Tersalin!';
    });
}
</script>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
