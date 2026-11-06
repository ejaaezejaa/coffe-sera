</main>

<footer class="footer">
    <p>&copy; <?= date('Y') ?> Sera Coffee. A Better Day Begins with Coffee.</p>
    <p>Buka setiap hari, 09.00 - 14.00 WIB</p>
</footer>

<script>
// Tombol + dan - di samping angka jumlah
document.querySelectorAll('.stepper').forEach(function (box) {
    const input = box.querySelector('input');

    function submitIfNeeded() {
        if (box.dataset.autosubmit) {
            input.form.submit();
        }
    }

    box.querySelectorAll('button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const next = Number(input.value) + Number(btn.dataset.step);
            input.value = Math.min(Number(input.max), Math.max(Number(input.min), next));
            submitIfNeeded();
        });
    });

    input.addEventListener('change', submitIfNeeded);
});

// Tombol menu (hamburger) di HP
document.querySelector('.menu-toggle').addEventListener('click', function () {
    document.querySelector('.navbar').classList.toggle('open');
});
</script>

</body>
</html>
