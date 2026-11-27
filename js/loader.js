// Animasi loading: tampil sampai halaman BENAR-BENAR selesai dimuat (tanpa hitungan detik).
// Ada dua angka yang dibedakan:
//   target = kemajuan ASLI dari proses muat halaman (bisa melompat, misalnya 30% langsung 90%)
//   shown  = angka yang tampil di layar, naik pelan-pelan (1%, 2%, 3% ...) mengejar target
// Jadi kalau sinyal jelek, angka naik pelan karena targetnya memang lambat naik.
(function () {
    const loader = document.getElementById('loader');

    if (!loader) {
        return;
    }

    const label = loader.querySelector('p');

    // Kecepatan angka naik. Makin KECIL = makin cepat (tapi loncatannya makin besar).
    // Dengan 12, halaman yang cepat ditutup sekitar 0,7 detik dan angka tetap terlihat berjalan.
    const COUNT_SPEED = 12;

    let target = 10;        // sudah 10%: HTML dan CSS bagian atas sudah sampai (script ini jalan)
    let shown = 0;
    let loaded = false;     // true setelah event "load" (semua gambar dan file selesai diunduh)
    let counting = true;    // false saat loading sedang dipakai untuk pindah halaman

    function hide() {
        counting = false;
        loader.classList.add('done');
    }

    // Klik link atau kirim form: kemajuannya tidak bisa diukur, jadi tampil tanpa angka
    function showWithoutPercent() {
        counting = false;
        loader.classList.remove('done');
        label.textContent = 'Memuat...';
    }

    // Menaikkan angka yang tampil. Dipanggil sekali per frame layar (sekitar 60 kali per detik)
    function tick() {
        if (!counting) {
            return;
        }

        if (shown < target) {
            // Makin jauh dari target, makin besar langkahnya. Paling sedikit naik 1% per frame
            shown += Math.max(1, Math.floor((target - shown) / COUNT_SPEED));
            label.textContent = 'Memuat... ' + shown + '%';
        }

        // Halaman sudah selesai dan angkanya sudah sampai 100%: baru loading ditutup
        if (loaded && shown >= 100) {
            hide();
            return;
        }

        requestAnimationFrame(tick);
    }

    // Kemajuan asli bagian gambar: dari 30% sampai 95% sesuai jumlah gambar yang sudah selesai
    function trackImages() {
        target = Math.max(target, 30);   // struktur halaman (DOM) sudah siap

        const images = Array.from(document.images);
        let done = 0;

        function update() {
            const part = images.length === 0 ? 1 : done / images.length;
            target = Math.max(target, 30 + Math.round(65 * part));
        }

        images.forEach(function (img) {
            if (img.complete) {
                done++;
                return;
            }

            // Berhasil atau gagal sama-sama dihitung selesai, supaya angkanya tidak macet
            img.addEventListener('load', function () { done++; update(); });
            img.addEventListener('error', function () { done++; update(); });
        });

        update();
    }

    function finish() {
        loaded = true;
        target = 100;

        // Tab sedang tidak dilihat: layar tidak menggambar frame, jadi langsung ditutup
        if (document.hidden) {
            hide();
        }
    }

    label.textContent = 'Memuat... 0%';
    requestAnimationFrame(tick);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', trackImages);
    } else {
        trackImages();
    }

    if (document.readyState === 'complete') {
        finish();
    } else {
        window.addEventListener('load', finish);
    }

    // Halaman dibuka lagi lewat tombol Back: jangan tampilkan loading
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            hide();
        }
    });

    // Klik link ke halaman lain di website ini: tampilkan loading sampai halaman baru muncul
    document.addEventListener('click', function (event) {
        const link = event.target.closest('a');

        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        if (link.target === '_blank' || link.hasAttribute('download') || link.origin !== location.origin) {
            return;
        }

        if (link.pathname === location.pathname && link.search === location.search) {
            return;   // link ke halaman yang sama
        }

        showWithoutPercent();
    });

    // Kirim form (misalnya checkout): tampilkan loading selama diproses
    document.addEventListener('submit', function (event) {
        if (!event.defaultPrevented) {
            showWithoutPercent();
        }
    });
})();
