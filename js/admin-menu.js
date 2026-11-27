// Tombol Menu di halaman admin (HP dan tablet): buka dan tutup daftar menu
const adminBar = document.getElementById('admin-bar');
const adminMenuButton = document.getElementById('admin-menu-btn');

// Di halaman login tidak ada menu, jadi dua elemen ini bisa kosong
if (adminBar && adminMenuButton) {
    adminMenuButton.addEventListener('click', function () {
        const isOpen = adminBar.classList.toggle('open');

        adminMenuButton.setAttribute('aria-expanded', isOpen);
        adminMenuButton.textContent = isOpen ? 'Tutup' : 'Menu';
    });
}
