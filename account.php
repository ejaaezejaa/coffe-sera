<?php
require 'config.php';
require 'includes/orders.php';

header('Cache-Control: no-store');

$customer = current_customer($pdo);

if (!$customer) {
    header('Location: login.php?next=account.php');
    exit;
}

$error_profile  = '';
$error_password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name  = mb_substr(trim($_POST['name'] ?? ''), 0, 100);
        $phone = normalize_phone($_POST['phone'] ?? '');

        $stmt = $pdo->prepare('SELECT id FROM customers WHERE phone = ? AND id <> ?');
        $stmt->execute([$phone, $customer['id']]);

        if ($name === '') {
            $error_profile = 'Nama wajib diisi.';
        } elseif (!valid_phone($phone)) {
            $error_profile = 'Nomor HP tidak valid. Contoh: 081234567890.';
        } elseif ($stmt->fetch()) {
            $error_profile = 'Nomor HP itu sudah dipakai akun lain.';
        } else {
            $stmt = $pdo->prepare('UPDATE customers SET name = ?, phone = ? WHERE id = ?');
            $stmt->execute([$name, $phone, $customer['id']]);
            $_SESSION['customer_name'] = $name;

            header('Location: account.php?saved=1');
            exit;
        }
    }

    // Lupa password lama: buat permintaan reset untuk akun yang sedang login
    if ($action === 'forgot') {
        $pdo->exec('DELETE FROM password_resets WHERE created_at < (NOW() - INTERVAL 1 DAY)');

        $stmt = $pdo->prepare('INSERT IGNORE INTO password_resets (customer_id) VALUES (?)');
        $stmt->execute([$customer['id']]);

        header('Location: account.php?forgot=1');
        exit;
    }

    if ($action === 'password') {
        $old     = (string) ($_POST['old'] ?? '');
        $new     = (string) ($_POST['new'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');

        if (!password_verify($old, $customer['password_hash'])) {
            $error_password = 'Password lama salah.';
        } elseif (strlen($new) < 8) {
            $error_password = 'Password baru minimal 8 karakter.';
        } elseif ($new !== $confirm) {
            $error_password = 'Konfirmasi password tidak sama.';
        } else {
            $stmt = $pdo->prepare('UPDATE customers SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $customer['id']]);

            header('Location: account.php?pw=1');
            exit;
        }
    }
}

$stmt = $pdo->prepare(
    'SELECT o.*, (SELECT COALESCE(SUM(qty), 0) FROM order_items WHERE order_id = o.id) AS cups
     FROM orders o
     WHERE o.customer_id = ?
     ORDER BY o.id DESC
     LIMIT 20'
);
$stmt->execute([$customer['id']]);
$orders = $stmt->fetchAll();

$title  = 'Akun Saya';
$active = 'account';

require 'includes/header.php';
?>

<section class="section split top">
    <div>
        <h1>Akun Saya</h1>

        <?php if (isset($_GET['saved'])): ?>
            <p class="alert">Data akun disimpan.</p>
        <?php endif; ?>

        <form method="post" class="form box">
            <h3>Data diri</h3>
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="profile">

            <?php if ($error_profile !== ''): ?>
                <p class="alert error"><?= e($error_profile) ?></p>
            <?php endif; ?>

            <label>Nama</label>
            <input type="text" name="name" value="<?= e($_POST['name'] ?? $customer['name']) ?>" required>

            <label>Nomor HP</label>
            <input type="text" name="phone" inputmode="numeric" value="<?= e($_POST['phone'] ?? $customer['phone']) ?>" required>

            <button type="submit" class="btn small">Simpan</button>
        </form>

        <?php if (isset($_GET['pw'])): ?>
            <p class="alert">Password berhasil diganti.</p>
        <?php endif; ?>

        <form method="post" class="form box">
            <h3>Ganti password</h3>
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="password">

            <?php if ($error_password !== ''): ?>
                <p class="alert error"><?= e($error_password) ?></p>
            <?php endif; ?>

            <label>Password lama</label>
            <div class="password-wrap">
                <input type="password" name="old" autocomplete="current-password" required>
                <?= password_toggle() ?>
            </div>

            <label>Password baru (minimal 8 karakter)</label>
            <div class="password-wrap">
                <input type="password" name="new" autocomplete="new-password" required>
                <?= password_toggle() ?>
            </div>

            <label>Ulangi password baru</label>
            <div class="password-wrap">
                <input type="password" name="confirm" autocomplete="new-password" required>
                <?= password_toggle() ?>
            </div>

            <button type="submit" class="btn small">Simpan password</button>
        </form>

        <?php if (isset($_GET['forgot'])): ?>
            <p class="alert">Permintaan terkirim. Admin akan mengirim kode reset lewat WhatsApp ke <?= e($customer['phone']) ?> (diproses di jam buka, 09.00 - 14.00 WIB). Kalau kodenya sudah diterima, <a href="reset.php">masukkan di sini</a>.</p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="forgot">
            <p class="hint">Lupa password lama? <button type="submit" class="link-btn">Minta kode reset</button></p>
        </form>

        <form method="post" action="logout.php">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="btn btn-outline">Logout</button>
        </form>
    </div>

    <div>
        <h2>Pesanan saya</h2>

        <?php if (count($orders) === 0): ?>
            <p>Belum ada pesanan. <a href="menu.php">Yuk pesan kopi</a>.</p>
        <?php endif; ?>

        <?php foreach ($orders as $o): ?>
            <div class="box">
                <p class="row"><strong>#<?= $o['id'] ?></strong><small><?= e(tanggal($o['created_at'])) ?></small></p>
                <p class="row"><span><?= (int) $o['cups'] ?> cup | <?= e(payment_label($o['payment_method'])) ?></span><strong><?= rupiah($o['total']) ?></strong></p>
                <p class="row">
                    <span>Status: <strong><?= e(status_label($o['status'])) ?></strong></span>
                    <a href="receipt.php?id=<?= $o['id'] ?>&from=account">Lihat struk</a>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
