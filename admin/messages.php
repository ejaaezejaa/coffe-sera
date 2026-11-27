<?php
require __DIR__ . '/auth.php';
require_admin();

$title        = 'Pesan Masuk';
$admin_active = 'messages';

$messages = $pdo->query('SELECT * FROM messages ORDER BY id DESC LIMIT 200')->fetchAll();

require __DIR__ . '/header.php';
?>

<h1>Pesan dari halaman Contact</h1>

<?php if (count($messages) === 0): ?>
    <p class="empty">Belum ada pesan masuk.</p>
<?php endif; ?>

<?php foreach ($messages as $m): ?>
    <div class="box message">
        <div class="row">
            <strong><?= e($m['name']) ?></strong>
            <small><?= e(tanggal($m['created_at'])) ?></small>
        </div>
        <p><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></p>
        <p><?= nl2br(e($m['message'])) ?></p>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/footer.php'; ?>
