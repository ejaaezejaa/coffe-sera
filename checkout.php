<?php
require 'config.php';
require 'includes/promo.php';

header('Cache-Control: no-store');

$title  = 'Checkout';
$active = 'cart';

if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$methods = [
    'qris'    => 'QRIS',
    'ewallet' => 'E-Wallet (DANA / GoPay)',
    'cod'     => 'Bayar di Kasir (Cash)',
];

$items = get_cart_items($pdo);

if (count($items) === 0) {
    header('Location: cart.php');
    exit;
}

$total    = array_sum(array_column($items, 'subtotal'));
$customer = current_customer($pdo);
$promo_info = get_active_promo($pdo);   // hanya untuk petunjuk di bawah kolom kode promo

$error         = '';
$promo_checked = false;   // true kalau pembeli mengetik kode promo atau menekan Cek kode
$promo_result  = ['promo' => null, 'discount' => 0, 'error' => '', 'code' => ''];   // hasil cek kode promo
$discount      = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $note   = trim($_POST['note'] ?? '');
    $method = $_POST['method'] ?? '';

    // Kode promo (boleh kosong). Hanya untuk pembeli yang login, satu akun satu kali.
    $check_only = isset($_POST['check_promo']);   // true kalau yang ditekan tombol "Cek kode"
    $promo_code = strtoupper(trim($_POST['promo'] ?? ''));

    if ($promo_code !== '') {
        $promo_checked = true;
        $promo_result  = check_promo($pdo, $promo_code, $total, $customer);
        $discount      = $promo_result['discount'];
    } elseif ($check_only) {
        $promo_checked         = true;
        $promo_result['error'] = 'Ketik kode promonya dulu.';
    }

    if ($check_only) {
        // Hanya mengecek kode: pesanan belum dibuat, hasilnya tampil di bawah kolom kode promo
    } elseif ($name === '' || $phone === '') {
        $error = 'Nama dan nomor HP wajib diisi.';
    } elseif (!preg_match('/^[0-9+\- ]{8,20}$/', $phone)) {
        $error = 'Nomor HP tidak valid (8-20 karakter, hanya angka, +, - dan spasi).';
    } elseif (!isset($methods[$method])) {
        $error = 'Pilih metode pembayaran.';
    } elseif ($promo_checked && $promo_result['error'] !== '') {
        $error = 'Kode promo belum bisa dipakai. Perbaiki kodenya, atau kosongkan kolomnya kalau tidak mau pakai promo.';
    } else {
        $name = mb_substr($name, 0, 100);
        $note = mb_substr($note, 0, 255);

        $order_id = 0;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO orders (customer_name, phone, note, payment_method, total, customer_id, promo_code, discount)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            // total yang disimpan sudah dikurangi diskon
            $stmt->execute([
                $name, $phone, $note, $method, $total - $discount, $customer['id'] ?? null,
                $discount > 0 ? $promo_result['code'] : null, $discount,
            ]);
            $order_id = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, qty, price, options) VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($items as $item) {
                $stmt->execute([$order_id, $item['id'], $item['qty'], $item['price'], $item['option']]);
            }

            $pdo->commit();
        } catch (PDOException $ex) {
            $pdo->rollBack();
            $error = 'Pesanan gagal disimpan, coba lagi ya.';
        }

        if ($error === '') {
            $_SESSION['cart'] = [];
            $_SESSION['last_order'] = $order_id;

            // Pilih QRIS: langsung ke halaman pembayaran QRIS
            header('Location: ' . ($method === 'qris' ? 'qris.php?id=' : 'success.php?id=') . $order_id);
            exit;
        }
    }
}

require 'includes/header.php';
?>

<section class="section split top">
    <form method="post" class="form">
        <!-- Tombol bawaan saat menekan Enter. Tanpa ini, Enter malah menekan "Cek kode" yang ada lebih dulu. -->
        <button type="submit" hidden></button>

        <h1>Checkout</h1>

        <?php if ($error !== ''): ?>
            <p class="alert error"><?= e($error) ?></p>
        <?php endif; ?>

        <?php if ($customer): ?>
            <p class="hint">Masuk sebagai <strong><?= e($customer['name']) ?></strong>. Nama dan nomor HP terisi otomatis.</p>
        <?php else: ?>
            <p class="hint">Sudah punya akun? <a href="login.php?next=checkout.php">Login</a> supaya nama dan nomor HP terisi otomatis.</p>
        <?php endif; ?>

        <label>Nama</label>
        <input type="text" name="name" value="<?= e($_POST['name'] ?? ($customer['name'] ?? '')) ?>" required>

        <label>Nomor HP / WhatsApp</label>
        <input type="text" name="phone" value="<?= e($_POST['phone'] ?? ($customer['phone'] ?? '')) ?>" required>

        <label>Catatan (opsional)</label>
        <textarea name="note" rows="3" placeholder="Contoh: tanpa sedotan"><?= e($_POST['note'] ?? '') ?></textarea>

        <label>Kode promo (opsional)</label>

        <?php if ($customer): ?>
            <div class="promo-field">
                <input type="text" name="promo" value="<?= e($_POST['promo'] ?? '') ?>" autocomplete="off">
                <button type="submit" name="check_promo" value="1" class="btn btn-outline small">Cek kode</button>
            </div>

            <?php if ($promo_checked && $promo_result['error'] !== ''): ?>
                <p class="alert error"><?= e($promo_result['error']) ?></p>
            <?php elseif ($promo_checked): ?>
                <p class="alert">Kode <strong><?= e($promo_result['code']) ?></strong> benar! Kamu hemat <strong><?= rupiah($discount) ?></strong>.</p>
            <?php endif; ?>

            <?php if ($promo_info): ?>
                <p class="hint"><?= e(promo_rule_text($promo_info)) ?>. Satu akun hanya bisa memakai satu kali.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="hint">Kode promo hanya untuk yang sudah login (satu akun satu kali). <a href="login.php?next=checkout.php">Login dulu</a>, isi keranjangmu tetap tersimpan.</p>
        <?php endif; ?>

        <label>Metode Pembayaran</label>
        <?php foreach ($methods as $key => $label): ?>
            <label class="radio">
                <input type="radio" name="method" value="<?= $key ?>"
                    <?= (($_POST['method'] ?? '') === $key) ? 'checked' : '' ?>>
                <?= e($label) ?>
            </label>
        <?php endforeach; ?>

        <button type="submit" class="btn">Buat Pesanan</button>
    </form>

    <div class="box">
        <h3>Ringkasan Pesanan</h3>
        <?php foreach ($items as $item): ?>
            <p class="row">
                <span><?= e($item['name']) ?> (<?= e($item['option']) ?>) x<?= $item['qty'] ?></span>
                <span><?= rupiah($item['subtotal']) ?></span>
            </p>
        <?php endforeach; ?>
        <?php if ($discount > 0): ?>
            <p class="row"><span>Promo <?= e($promo_result['code']) ?></span><span>-<?= rupiah($discount) ?></span></p>
        <?php endif; ?>
        <hr>
        <p class="row"><strong>Total</strong><strong><?= rupiah($total - $discount) ?></strong></p>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
