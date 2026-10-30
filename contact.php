<?php
require 'config.php';
$title  = 'Contact';
$active = 'contact';

$error = '';

$success = isset($_GET['sent']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = mb_substr(trim($_POST['name'] ?? ''), 0, 100);
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $error = 'Format email tidak valid.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO messages (name, email, message) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $message]);
        header('Location: contact.php?sent=1');
        exit;
    }
}

require 'includes/header.php';
?>

<section class="section split top">
    <div>
        <h1>Hubungi Kami</h1>
        <p>Ada pertanyaan atau saran? Kirim pesan lewat form, atau ngobrol langsung lewat Instagram.</p>

        <div class="box">
            <h3>Instagram</h3>
            <p>Follow kami untuk info menu baru dan promo.</p>
            <a href="<?= e($instagram_url) ?>" target="_blank" rel="noopener" class="btn">@seracoffee</a>
        </div>
    </div>

    <form method="post" class="form">
        <?php if ($success): ?>
            <p class="alert">Terima kasih! Pesanmu sudah kami terima.</p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="alert error"><?= e($error) ?></p>
        <?php endif; ?>

        <label>Nama</label>
        <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>

        <label>Pesan</label>
        <textarea name="message" rows="5" required><?= e($_POST['message'] ?? '') ?></textarea>

        <button type="submit" class="btn">Kirim</button>
    </form>
</section>

<?php require 'includes/footer.php'; ?>
