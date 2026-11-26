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


1. Tambahkan animasi loading.
2. Tambahkan halaman detail produk.
3. Tambahkan pilihan tingkat gula.
4. Tambahkan pilihan tingkat panas.
5. Tambahkan fitur jumlah pesanan.
6. Tambahkan fitur ulasan produk.
7. Tambahkan fitur privasi nama pada ulasan.
8. Tambahkan informasi nilai gizi produk.
9. Tambahkan perhitungan nilai gizi berdasarkan tingkat gula.
10. Tambahkan gambar produk di keranjang.
11. Tambahkan tombol tambah dan kurang jumlah produk.
12. Tambahkan fitur pilihan produk di keranjang.
13. Tambahkan tombol tambah menu lain.
14. Perbaiki tampilan keranjang.
15. Tambahkan navigasi hamburger.
16. Perbaiki tampilan responsive.
17. Tambahkan deskripsi produk.
18. Tambahkan favicon.
19. Perbaiki cache CSS dan favicon.
20. Tambahkan ikon user di navbar.
21. Tambahkan daftar anggota tim.
22. Tambahkan fitur registrasi pembeli.
23. Tambahkan fitur login pembeli.
24. Tambahkan fitur logout pembeli.
25. Tambahkan halaman akun pembeli.
26. Tambahkan fitur edit profil pembeli.
27. Tambahkan fitur ganti password pembeli.
28. Tambahkan fitur lupa password.
29. Tambahkan fitur reset password menggunakan kode verifikasi.
30. Tambahkan fitur login admin.
31. Tambahkan dashboard admin.
32. Tambahkan halaman daftar pesanan admin.
33. Tambahkan halaman detail pesanan admin.
34. Tambahkan fitur pengelolaan status pesanan.
35. Tambahkan informasi penjualan produk.
36. Tambahkan halaman pesan masuk admin.
37. Tambahkan fitur pengelolaan reset password admin.
38. Tambahkan fitur hapus pesanan yang dibatalkan.
39. Tambahkan rekap penjualan bulanan.
40. Tambahkan pilihan metode pembayaran QRIS.
41. Tambahkan pilihan pembayaran E-Wallet.
42. Tambahkan pilihan pembayaran tunai di kasir.
43. Tambahkan halaman pembayaran QRIS.
44. Tambahkan fitur pembatalan pesanan.
45. Tambahkan pembatalan otomatis pesanan yang belum dibayar.
46. Tambahkan halaman struk pembayaran.
47. Tambahkan fitur kode promo.
48. Tambahkan validasi kode promo.
49. Tambahkan pembatasan penggunaan kode promo.
50. Tambahkan perhitungan diskon pesanan.
51. Tambahkan informasi promo pada pesanan.
52. Tambahkan fitur pengingat reset password melalui WhatsApp.
53. Perbarui struktur database.
54. Tambahkan file update database.
55. Perbarui dokumentasi README.
