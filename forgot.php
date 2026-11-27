<?php
require 'config.php';

header('Cache-Control: no-store');

if (!empty($_SESSION['customer_id'])) {
    header('Location: account.php');
    exit;
}

$sent  = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $phone = normalize_phone($_POST['phone'] ?? '');

    if (!valid_phone($phone)) {
        $error = 'Nomor HP tidak valid. Contoh: 081234567890.';
    } else {
        // Permintaan yang sudah lebih dari 1 hari dibersihkan dulu
        $pdo->exec('DELETE FROM password_resets WHERE created_at < (NOW() - INTERVAL 1 DAY)');

        $stmt = $pdo->prepare('SELECT id FROM customers WHERE phone = ?');
        $stmt->execute([$phone]);
        $customer = $stmt->fetch();

        if ($customer) {
            // INSERT IGNORE: kalau permintaannya sudah ada, tidak dibuat dua kali
            $stmt = $pdo->prepare('INSERT IGNORE INTO password_resets (customer_id) VALUES (?)');
            $stmt->execute([$customer['id']]);
        }

        // Pesan yang sama dipakai walau nomornya tidak terdaftar,
        // supaya orang lain tidak bisa menebak nomor siapa saja yang punya akun
        $sent = true;
    }
}

$title  = 'Lupa Password';
$active = 'login';

require 'includes/header.php';
?>

<div class="auth-box">
    <h1>Lupa Password</h1>

    <?php if ($sent): ?>
        <p class="alert">Permintaan terkirim. Admin akan mengirim <strong>kode reset</strong> lewat WhatsApp ke nomor itu (diproses di jam buka, 09.00 - 14.00 WIB).</p>
        <p>Kalau kodenya sudah kamu terima, lanjut ke halaman reset.</p>
        <p class="center"><a href="reset.php" class="btn">Masukkan Kode</a></p>
    <?php else: ?>
        <p>Masukkan nomor HP yang kamu pakai saat mendaftar. Admin akan mengirim kode reset ke WhatsApp nomor itu.</p>

        <?php if ($error !== ''): ?>
            <p class="alert error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post" class="form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

            <label>Nomor HP</label>
            <input type="text" name="phone" inputmode="numeric" autocomplete="username" value="<?= e($_POST['phone'] ?? '') ?>" required>

            <button type="submit" class="btn">Minta Kode Reset</button>
        </form>

        <p class="center">Sudah punya kode? <a href="reset.php">Reset password</a></p>
    <?php endif; ?>

    <p class="center"><a href="login.php">Kembali ke login</a></p>
</div>

<?php require 'includes/footer.php'; ?>
