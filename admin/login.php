<?php
require __DIR__ . '/auth.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    // Kalau username tidak ada, tetap cek password palsu supaya waktunya sama
    $hash = $admin['password_hash'] ?? password_hash('palsu', PASSWORD_DEFAULT);

    if (password_verify($password, $hash) && $admin) {
        session_regenerate_id(true);
        $_SESSION['admin_id']          = (int) $admin['id'];
        $_SESSION['admin_name']        = $admin['username'];
        $_SESSION['admin_must_change'] = ((int) $admin['must_change'] === 1);
        header('Location: index.php');
        exit;
    }

    sleep(1);
    $error = 'Username atau password salah.';
}

$title = 'Login';
require __DIR__ . '/header.php';
?>

<div class="login-box">
    <img src="../images/logo.png" alt="Sera Coffee" class="login-logo">
    <h1>Login Admin</h1>

    <?php if ($error !== ''): ?>
        <p class="alert error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" class="form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <label>Username</label>
        <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username" required autofocus>

        <label>Password</label>
        <div class="password-wrap">
            <input type="password" name="password" autocomplete="current-password" required>
            <?= password_toggle() ?>
        </div>

        <button type="submit" class="btn">Masuk</button>
    </form>
</div>

<?php require __DIR__ . '/footer.php'; ?>
