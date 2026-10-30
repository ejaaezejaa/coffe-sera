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
