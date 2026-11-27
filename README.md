# Sera Coffee (PHP + MySQL)

## Cara menjalankan (XAMPP)
1. Copy folder `sera-coffee` ke `C:\xampp\htdocs\`
2. Nyalakan **Apache** dan **MySQL** di XAMPP
3. Buka `http://localhost/phpmyadmin` -> tab **Import** -> pilih `database.sql` -> Go
   (Kalau databasemu sudah berisi pesanan, JANGAN import `database.sql` lagi karena akan menghapus data. Import `update-v5.sql` saja, sekali, untuk menambah tabel dan kolom baru.)
4. Buka `http://localhost/sera-coffee/`

## Yang perlu kamu ganti
- `config.php`: username/password MySQL dan link Instagram (tampil di halaman Contact)
- `success.php`: nomor rekening / e-wallet
- `database.sql`: nama menu, harga, foto

## Rating
Setelah memesan, pembeli bisa memberi rating 1-5 dan masukan di halaman pesanan. Rata-ratanya tampil di bawah harga di halaman Menu.

## Catatan
Pembayaran masih simulasi (pesanan disimpan di database dengan status `pending`).
Untuk pembayaran asli perlu payment gateway seperti Midtrans.

## Fitur tambahan
- `product.php`: halaman detail menu (klik kartu di Menu atau Home). Penjelasan lengkap ada di kolom `details` tabel `products`.
- Tombol jumlah `[ - 1 + ]` di halaman detail dan di keranjang (di keranjang, jumlah langsung tersimpan saat ditekan).
- Saat memberi ulasan, pembeli memilih menampilkan atau menyensor nama (contoh: `I***l A****i`).
- Di halaman detail, pembeli memilih tingkat gula (menu Iced) atau tingkat panas (menu Hot). Pilihan ikut tersimpan di pesanan.
- Favicon: `favicon.ico` dan `images/favicon.png`.

## Admin dan struk
- Halaman admin: buka `/admin` (contoh: `localhost/coffe-sera/admin`). Login awal: username `admin`, password `admin123`. Password wajib diganti saat login pertama.
- Dashboard menampilkan total pesanan, cup terjual, pendapatan, dan daftar menu. Klik menu untuk melihat siapa yang membeli.
- Struk pembayaran: `receipt.php`, bisa dicetak atau disimpan sebagai PDF.

## Metode pembayaran
- QRIS: setelah memilih QRIS, pembeli diarahkan ke `qris.php`. Simpan foto QRIS toko sebagai `images/qris.png` (bisa juga `.jpg`, `.jpeg`, `.webp`). Selama belum ada, yang tampil `images/qris-placeholder.png`.
- E-Wallet (DANA / GoPay dll): pembeli mengirim sendiri ke nomor e-wallet toko. Ganti nomornya di `success.php`.
- Bayar di Kasir (Cash).
- Keduanya berstatus "Menunggu pembayaran" sampai admin mengubahnya jadi "Sudah dibayar" di halaman admin.

## Pesan dari halaman Contact
- Admin bisa membaca semuanya di menu Pesan (`admin/messages.php`).

## Akun pembeli
- Ikon user di navbar membuka halaman login. Pembeli mendaftar dengan nama, nomor HP, dan password. Setelah login, nama dan nomor HP di halaman checkout terisi otomatis.
- Halaman akun (`account.php`) berisi data diri, ganti password, dan daftar pesanan.

## Pesanan belum dibayar
- Pembeli bisa membatalkan pesanannya yang belum dibayar (tombol "Batalkan pesanan").
- Pesanan QRIS / E-Wallet yang belum dibayar dibatalkan otomatis setelah 60 menit (`PAYMENT_WINDOW_MINUTES` di `includes/orders.php`).
- Admin bisa menghapus pesanan yang dibatalkan dari halaman Pesanan.

## Lupa password (pembeli)
- Di halaman Login ada link "Lupa password?" (`forgot.php`). Pembeli memasukkan nomor HP, lalu permintaannya masuk ke admin.
- Admin membuka menu **Reset Password** (`admin/resets.php`), menekan "Buat kode", lalu mengirim kode 6 angka ke WhatsApp pembeli (ada tombol WhatsApp yang pesannya sudah terisi).
- Pembeli memasukkan nomor HP, kode, dan password baru di `reset.php`.
- Kode berlaku 30 menit, hanya bisa dipakai sekali, dan terkunci setelah 5 kali salah (`RESET_CODE_MINUTES` dan `RESET_MAX_ATTEMPTS` di `config.php`). Di database hanya tersimpan hash kodenya.

## Loading
- `js/loader.js`: animasi loading tampil sampai halaman benar-benar selesai dimuat (event `load` dari browser), tanpa hitungan detik. Sinyal jelek = loading lebih lama. Angka persen di bawah cangkir naik pelan-pelan (1%, 2%, 3% ... sampai 100%) mengejar kemajuan asli proses muat halaman. Kecepatan naiknya diatur oleh `COUNT_SPEED` di bagian atas `js/loader.js` (makin kecil = makin cepat).
- Cara mencoba: Chrome -> F12 -> tab Network -> centang "Disable cache" dan ubah "No throttling" jadi "Slow 4G" atau "3G", lalu muat ulang halaman.

## Rekap bulanan (admin)
- Menu **Rekap Bulanan** (`admin/recap.php`). Pilih bulan, lalu lihat jumlah pesanan, pembeli, cup terjual, pendapatan, menu yang terjual, dan daftar siapa membeli apa. Pesanan yang dibatalkan tidak dihitung.

## Kode promo
- Setelah memesan, halaman `success.php` menampilkan promo untuk pesanan berikutnya (di bawah Cara Pembayaran). Pembeli memasukkan kodenya di kolom "Kode promo" pada halaman Checkout.
- Kode promo hanya bisa dipakai pembeli yang login, dan satu akun hanya satu kali per kode (pesanan yang dibatalkan tidak dihitung, jadi kodenya bisa dipakai lagi). Di Checkout ada tombol **Cek kode** untuk melihat kodenya benar atau salah dan besar diskonnya sebelum memesan.
- Promo disimpan di tabel `promos`: `code`, `percent` (persen diskon), `max_discount` (batas diskon, 0 = tanpa batas), `min_total` (minimal belanja), `active` (1 = aktif, 0 = mati). Ubah lewat phpMyAdmin. Bawaan: `SERA10`, diskon 10%, maksimal Rp 10.000, minimal belanja Rp 30.000.
- Diskon tercatat di pesanan (`promo_code`, `discount`) dan tampil di struk, halaman pesanan, detail pesanan admin, dan rekap bulanan.
- Pembeli yang sudah login tapi lupa password lamanya bisa menekan "Minta kode reset" di halaman Akun (`account.php`). Alurnya sama, tinggal menunggu kode dari admin.

## Admin di tablet dan HP
- Di layar kurang dari 1100px, menu admin disembunyikan dan dibuka lewat tombol **Menu** (`js/admin-menu.js`). Di layar lebar tampilannya tetap satu baris.
- Di HP, tabel berubah jadi kartu per baris. Sel yang isinya beberapa baris (misalnya "Yang dibeli" di Rekap Bulanan) dibungkus `<div class="cell-text">` supaya tidak terpecah.
