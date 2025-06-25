</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const navLinks = document.querySelectorAll('.offcanvas .nav-link:not([data-bs-toggle="collapse"])');
    
    navLinks.forEach(link => {
        // Mengambil nama file dari atribut href
        const linkPage = link.getAttribute('href').split('/').pop();
        if (linkPage === currentPage) {
            link.classList.add('active'); // Tandai link yang aktif
            
            // Cari parent collapse dan buka jika ada
            const parentCollapse = link.closest('.collapse');
            if (parentCollapse) {
                parentCollapse.classList.add('show');
                
                // Cari tombol yang mengontrol collapse ini dan set state-nya
                const toggleButton = document.querySelector(`.nav-link[data-bs-toggle="collapse"][href="#${parentCollapse.id}"]`);
                if (toggleButton) {
                    toggleButton.setAttribute('aria-expanded', 'true');
                    toggleButton.classList.remove('collapsed');
                }
            }
        }
    });

    // Skrip untuk memutar ikon panah pada menu collapse
    const collapseToggles = document.querySelectorAll('.offcanvas .nav-link[data-bs-toggle="collapse"]');
    collapseToggles.forEach(toggle => {
        const chevron = toggle.querySelector('.bi-chevron-down');
        const targetCollapse = document.querySelector(toggle.getAttribute('href'));
        if (targetCollapse && chevron) {
            // Putar panah saat menu dibuka
            targetCollapse.addEventListener('show.bs.collapse', () => {
                chevron.style.transform = 'rotate(180deg)';
            });
            // Kembalikan panah saat menu ditutup
            targetCollapse.addEventListener('hide.bs.collapse', () => {
                chevron.style.transform = 'rotate(0deg)';
            });

            // Periksa apakah menu sudah terbuka saat halaman dimuat
            if (targetCollapse.classList.contains('show')) {
                 chevron.style.transform = 'rotate(180deg)';
            }
        }
    });
});
</script>

</body>
</html>