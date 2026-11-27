<?php
require __DIR__ . '/auth.php';
require_admin();

$title        = 'Ganti Password';
$admin_active = 'password';
$error        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old     = (string) ($_POST['old'] ?? '');
    $new     = (string) ($_POST['new'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($old, $admin['password_hash'])) {
        $error = 'Password lama salah.';
    } elseif (strlen($new) < 8) {
        $error = 'Password baru minimal 8 karakter.';
    } elseif ($new === $old) {
        $error = 'Password baru harus berbeda dari yang lama.';
    } elseif ($new !== $confirm) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $stmt = $pdo->prepare('UPDATE admins SET password_hash = ?, must_change = 0 WHERE id = ?');
        $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);

        $_SESSION['admin_must_change'] = false;
        session_regenerate_id(true);
        header('Location: password.php?done=1');
        exit;
    }
}

require __DIR__ . '/header.php';
?>

<div class="login-box">
    <h1>Ganti Password</h1>

    <?php if (!empty($_SESSION['admin_must_change'])): ?>
        <p class="alert error">Password awal harus diganti dulu sebelum lanjut.</p>
    <?php endif; ?>

    <?php if (isset($_GET['done'])): ?>
        <p class="alert">Password berhasil diganti.</p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="alert error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" class="form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

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

        <button type="submit" class="btn">Simpan Password</button>
    </form>
</div>

<?php require __DIR__ . '/footer.php'; ?>
