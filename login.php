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

    $phone    = normalize_phone($_POST['phone'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM customers WHERE phone = ?');
    $stmt->execute([$phone]);
    $customer = $stmt->fetch();

    // Kalau nomor tidak ada, tetap cek password palsu supaya waktunya sama
    $hash = $customer['password_hash'] ?? password_hash('palsu', PASSWORD_DEFAULT);

    if (password_verify($password, $hash) && $customer) {
        customer_login($customer);
        header('Location: ' . $next);
        exit;
    }

    sleep(1);
    $error = 'Nomor HP atau password salah.';
}

$title  = 'Login';
$active = 'login';

require 'includes/header.php';
?>

<div class="auth-box">
    <h1>Login</h1>
    <p>Masuk supaya nama dan nomor HP terisi otomatis saat memesan.</p>

    <?php if (isset($_GET['reset'])): ?>
        <p class="alert">Password berhasil diganti. Silakan login dengan password baru.</p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="alert error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" class="form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="next" value="<?= e($next) ?>">

        <label>Nomor HP</label>
        <input type="text" name="phone" inputmode="numeric" autocomplete="username" value="<?= e($_POST['phone'] ?? '') ?>" required>

        <label>Password</label>
        <div class="password-wrap">
            <input type="password" name="password" autocomplete="current-password" required>
            <?= password_toggle() ?>
        </div>

        <button type="submit" class="btn">Masuk</button>
    </form>

    <p class="center"><a href="forgot.php">Lupa password?</a></p>

    <p class="center">Belum punya akun? <a href="register.php?next=<?= e($next) ?>">Daftar</a></p>
</div>

<?php require 'includes/footer.php'; ?>
