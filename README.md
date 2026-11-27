# Sera Coffee (PHP + MySQL)

## Cara menjalankan (XAMPP)
1. Copy folder `sera-coffee` ke `C:\xampp\htdocs\`
2. Nyalakan **Apache** dan **MySQL** di XAMPP
3. Buka `http://localhost/phpmyadmin` -> tab **Import** -> pilih `database.sql` -> Go
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
