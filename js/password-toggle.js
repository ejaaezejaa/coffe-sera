// Ikon mata di kolom password: klik untuk menampilkan atau menyembunyikan password
document.querySelectorAll('.toggle-password').forEach(function (button) {
    button.addEventListener('click', function () {
        const input = button.parentElement.querySelector('input');
        const hidden = input.type === 'password';

        input.type = hidden ? 'text' : 'password';
        button.classList.toggle('showing', hidden);
        button.setAttribute('aria-label', hidden ? 'Sembunyikan password' : 'Lihat password');
    });
});
