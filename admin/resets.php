<?php
require __DIR__ . '/auth.php';
require_admin();

$title        = 'Reset Password Pembeli';
$admin_active = 'resets';

// Kode yang baru dibuat hanya ditampilkan sekali (di database cuma tersimpan hash-nya)
$new_code = $_SESSION['new_reset_code'] ?? null;
unset($_SESSION['new_reset_code']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $id     = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $code = (string) random_int(100000, 999999);

        $stmt = $pdo->prepare(
            'UPDATE password_resets
             SET code_hash = ?, expires_at = NOW() + INTERVAL ' . (int) RESET_CODE_MINUTES . ' MINUTE, attempts = 0
             WHERE id = ?'
        );
        $stmt->execute([password_hash($code, PASSWORD_DEFAULT), $id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['new_reset_code'] = ['id' => $id, 'code' => $code];
        }
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM password_resets WHERE id = ?');
        $stmt->execute([$id]);
    }

    header('Location: resets.php');
    exit;
}

$requests = $pdo->query(
    'SELECT r.*, c.name, c.phone
     FROM password_resets r
     JOIN customers c ON c.id = r.customer_id
     ORDER BY r.created_at DESC'
)->fetchAll();

/**
 * Status tiap permintaan
 *
 * @param array $r  Satu permintaan reset (gabungan password_resets dan customers)
 */
function reset_status($r) {
    if ($r['code_hash'] === null) {
        return 'Menunggu kode dari admin';
    }

    if ($r['attempts'] >= RESET_MAX_ATTEMPTS) {
        return 'Terlalu banyak salah memasukkan kode';
    }

    if (strtotime($r['expires_at']) < time()) {
        return 'Kode kedaluwarsa';
    }

    return 'Kode aktif sampai ' . date('H:i', strtotime($r['expires_at'])) . ' WIB';
}

/**
 * Nomor 0812... jadi 62812... untuk link WhatsApp
 *
 * @param string $phone  Nomor HP diawali 0
 */
function whatsapp_number($phone) {
    return '62' . substr($phone, 1);
}

require __DIR__ . '/header.php';
?>

<h1>Reset Password Pembeli</h1>
<p>Pembeli yang lupa password meminta kode lewat website. Buat kodenya di sini, lalu kirim ke WhatsApp pembeli itu.</p>

<?php if ($new_code): ?>
    <?php foreach ($requests as $r): ?>
        <?php if ((int) $r['id'] === (int) $new_code['id']): ?>
            <?php
            $pesan = 'Halo ' . $r['name'] . ', ini kode reset password Sera Coffee kamu: ' . $new_code['code']
                   . '. Berlaku ' . RESET_CODE_MINUTES . ' menit. Masukkan di halaman Reset Password. Jangan bagikan kode ini ke siapa pun.';
            ?>
            <div class="box">
                <h3>Kode untuk <?= e($r['name']) ?> (<?= e($r['phone']) ?>)</h3>
                <p class="reset-code"><?= e($new_code['code']) ?></p>
                <p class="hint">Kode ini hanya tampil sekali. Berlaku <?= RESET_CODE_MINUTES ?> menit.</p>
                <p>
                    <a href="https://wa.me/<?= e(whatsapp_number($r['phone'])) ?>?text=<?= urlencode($pesan) ?>"
                       target="_blank" rel="noopener" class="btn small">Kirim lewat WhatsApp</a>
                </p>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (count($requests) === 0): ?>
    <p>Belum ada permintaan reset password.</p>
<?php else: ?>
    <div class="table-scroll">
        <table class="table data-table">
            <thead>
                <tr><th>Waktu</th><th>Pembeli</th><th>No. HP</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td data-label="Waktu"><?= e(tanggal($r['created_at'])) ?></td>
                        <td data-label="Pembeli"><?= e($r['name']) ?></td>
                        <td data-label="No. HP"><?= e($r['phone']) ?></td>
                        <td data-label="Status"><?= e(reset_status($r)) ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button type="submit" name="action" value="create" class="btn small">
                                    <?= $r['code_hash'] === null ? 'Buat kode' : 'Buat kode baru' ?>
                                </button>
                                <button type="submit" name="action" value="delete" class="link-btn">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
