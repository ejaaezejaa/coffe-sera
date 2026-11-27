<?php
/**
 * Promo yang sedang aktif (kalau ada beberapa, yang paling awal dibuat), atau false kalau tidak ada
 *
 * @param PDO $pdo
 */
function get_active_promo($pdo) {
    return $pdo->query('SELECT * FROM promos WHERE active = 1 ORDER BY id LIMIT 1')->fetch();
}

/**
 * Cari promo aktif dari kode yang diketik pembeli (huruf besar/kecil tidak berpengaruh)
 *
 * @param PDO    $pdo
 * @param string $code  Kode yang diketik pembeli
 */
function find_promo($pdo, $code) {
    $stmt = $pdo->prepare('SELECT * FROM promos WHERE code = ? AND active = 1');
    $stmt->execute([$code]);
    return $stmt->fetch();
}

/**
 * Besar diskon dalam rupiah: persen dari total, tapi tidak boleh lebih dari batas diskon
 *
 * @param array            $promo  Satu baris dari tabel promos
 * @param int|float|string $total  Total belanja sebelum diskon
 */
function promo_discount($promo, $total) {
    $discount = (int) floor($total * $promo['percent'] / 100);

    if ($promo['max_discount'] > 0) {
        $discount = min($discount, (int) $promo['max_discount']);
    }

    return $discount;
}

/**
 * Syarat promo dalam satu kalimat, contoh: "Diskon 10% (maksimal Rp 10.000), minimal belanja Rp 30.000"
 *
 * @param array $promo  Satu baris dari tabel promos
 */
function promo_rule_text($promo) {
    $text = 'Diskon ' . (int) $promo['percent'] . '%';

    if ($promo['max_discount'] > 0) {
        $text .= ' (maksimal ' . rupiah($promo['max_discount']) . ')';
    }

    if ($promo['min_total'] > 0) {
        $text .= ', minimal belanja ' . rupiah($promo['min_total']);
    }

    return $text;
}

/**
 * Sudah pernah dipakai akun ini? Pesanan yang dibatalkan tidak dihitung, jadi kodenya bisa dipakai lagi.
 *
 * @param PDO        $pdo
 * @param string     $code         Kode promo
 * @param int|string $customer_id  Nomor akun pembeli
 */
function promo_used($pdo, $code, $customer_id) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM orders WHERE customer_id = ? AND promo_code = ? AND status <> 'batal'"
    );
    $stmt->execute([$customer_id, $code]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Cek kode promo untuk pembeli ini. Hasilnya:
 *   'promo'    = data promo kalau kodenya benar, atau null
 *   'discount' = besar diskon (rupiah)
 *   'error'    = alasan kalau kodenya tidak bisa dipakai, atau teks kosong kalau bisa
 *   'code'     = kode promonya kalau benar, atau teks kosong
 *
 * @param PDO              $pdo
 * @param string           $code      Kode yang diketik pembeli
 * @param int|float|string $total     Total belanja sebelum diskon
 * @param array|null       $customer  Pembeli yang login, atau null kalau belum login
 */
function check_promo($pdo, $code, $total, $customer) {
    $result = ['promo' => null, 'discount' => 0, 'error' => '', 'code' => ''];

    if (!$customer) {
        $result['error'] = 'Login dulu untuk memakai kode promo.';
        return $result;
    }

    $promo = find_promo($pdo, $code);

    if (!$promo) {
        $result['error'] = 'Kode promo salah atau sudah tidak berlaku.';
    } elseif (promo_used($pdo, $promo['code'], $customer['id'])) {
        $result['error'] = 'Kamu sudah pernah memakai kode ini. Satu akun hanya bisa memakainya satu kali.';
    } elseif ($total < $promo['min_total']) {
        $result['error'] = 'Kode benar, tapi butuh minimal belanja ' . rupiah($promo['min_total']) . '.';
    } else {
        $result['promo']    = $promo;
        $result['code']     = $promo['code'];
        $result['discount'] = promo_discount($promo, $total);
    }

    return $result;
}
