<?php
require 'config.php';

header('Cache-Control: no-store');

// Pembeli yang sedang login (lupa password lama) juga boleh memakai halaman ini
$customer = current_customer($pdo);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $phone    = normalize_phone($_POST['phone'] ?? '');
    $code     = trim($_POST['code'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['confirm'] ?? '');

    if (strlen($password) < 8) {
        $error = 'Password baru minimal 8 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        // Cari permintaan reset yang kodenya sudah dibuat admin, belum kedaluwarsa,
        // dan belum terlalu sering salah dimasukkan
        $stmt = $pdo->prepare(
            'SELECT r.id, r.code_hash, r.customer_id
             FROM password_resets r
             JOIN customers c ON c.id = r.customer_id
             WHERE c.phone = ?
               AND r.code_hash IS NOT NULL
               AND r.expires_at > NOW()
               AND r.attempts < ?'
        );
        $stmt->execute([$phone, RESET_MAX_ATTEMPTS]);
        $reset = $stmt->fetch();

        if ($reset && password_verify($code, $reset['code_hash'])) {
            $stmt = $pdo->prepare('UPDATE customers SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $reset['customer_id']]);

            // Kode hanya bisa dipakai sekali
            $stmt = $pdo->prepare('DELETE FROM password_resets WHERE id = ?');
            $stmt->execute([$reset['id']]);

            // Kalau yang direset adalah akun yang sedang login, kembali ke halaman akun
            $own = $customer && (int) $reset['customer_id'] === (int) $customer['id'];

            header('Location: ' . ($own ? 'account.php?pw=1' : 'login.php?reset=1'));
            exit;
        }

        if ($reset) {
            $stmt = $pdo->prepare('UPDATE password_resets SET attempts = attempts + 1 WHERE id = ?');
            $stmt->execute([$reset['id']]);
        }

        sleep(1);
        $error = 'Kode salah atau sudah kedaluwarsa. Kalau perlu, minta kode baru.';
    }
}

$title  = 'Reset Password';
$active = 'login';

require 'includes/header.php';
?>

<div class="auth-box">
    <h1>Reset Password</h1>
    <p>Masukkan kode 6 angka yang dikirim admin lewat WhatsApp, lalu buat password baru. Kode berlaku <?= RESET_CODE_MINUTES ?> menit.</p>

    <?php if ($error !== ''): ?>
        <p class="alert error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" class="form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <label>Nomor HP</label>
        <input type="text" name="phone" inputmode="numeric" autocomplete="username" value="<?= e($_POST['phone'] ?? ($customer['phone'] ?? '')) ?>" required>

        <label>Kode reset (6 angka)</label>
        <input type="text" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" value="<?= e($_POST['code'] ?? '') ?>" required>

        <label>Password baru (minimal 8 karakter)</label>
        <div class="password-wrap">
            <input type="password" name="password" autocomplete="new-password" required>
            <?= password_toggle() ?>
        </div>

        <label>Ulangi password baru</label>
        <div class="password-wrap">
            <input type="password" name="confirm" autocomplete="new-password" required>
            <?= password_toggle() ?>
        </div>

        <button type="submit" class="btn">Simpan Password Baru</button>
    </form>

    <p class="center">Belum punya kode? <a href="forgot.php">Minta kode</a></p>
</div>

<?php require 'includes/footer.php'; ?>
