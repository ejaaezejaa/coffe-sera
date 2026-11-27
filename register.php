<?php
require 'config.php';

header('Cache-Control: no-store');

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '');

if (!empty($_SESSION['customer_id'])) {
    header('Location: ' . $next);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name     = mb_substr(trim($_POST['name'] ?? ''), 0, 100);
    $phone    = normalize_phone($_POST['phone'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['confirm'] ?? '');

    $stmt = $pdo->prepare('SELECT id FROM customers WHERE phone = ?');
    $stmt->execute([$phone]);

    if ($name === '') {
        $error = 'Nama wajib diisi.';
    } elseif (!valid_phone($phone)) {
        $error = 'Nomor HP tidak valid. Contoh: 081234567890.';
    } elseif ($stmt->fetch()) {
        $error = 'Nomor HP ini sudah terdaftar. Silakan login.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO customers (name, phone, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$name, $phone, password_hash($password, PASSWORD_DEFAULT)]);

        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
        $stmt->execute([$pdo->lastInsertId()]);
        customer_login($stmt->fetch());

        header('Location: ' . $next);
        exit;
    }
}

$title  = 'Daftar';
$active = 'login';

require 'includes/header.php';
?>

<div class="auth-box">
    <h1>Daftar Akun</h1>
    <p>Cukup sekali isi nama dan nomor HP. Berikutnya tinggal login saat memesan.</p>

    <?php if ($error !== ''): ?>
        <p class="alert error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" class="form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="next" value="<?= e($next) ?>">

        <label>Nama</label>
        <input type="text" name="name" autocomplete="name" value="<?= e($_POST['name'] ?? '') ?>" required>

        <label>Nomor HP</label>
        <input type="text" name="phone" inputmode="numeric" autocomplete="tel" value="<?= e($_POST['phone'] ?? '') ?>" required>

        <label>Password (minimal 8 karakter)</label>
        <div class="password-wrap">
            <input type="password" name="password" autocomplete="new-password" required>
            <?= password_toggle() ?>
        </div>

        <label>Ulangi password</label>
        <div class="password-wrap">
            <input type="password" name="confirm" autocomplete="new-password" required>
            <?= password_toggle() ?>
        </div>

        <button type="submit" class="btn">Daftar</button>
    </form>

    <p class="center">Sudah punya akun? <a href="login.php?next=<?= e($next) ?>">Login</a></p>
</div>

<?php require 'includes/footer.php'; ?>
