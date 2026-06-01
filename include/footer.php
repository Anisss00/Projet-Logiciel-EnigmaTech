<footer>
    <!-- vide pour l'instant -->
</footer>

<script>

    // Ouvre/ferme les menus déroulants du header
    document.querySelectorAll('.header-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation(); // empêche la fermeture immédiate via le listener document

            const dropdown = this.querySelector('.dropdown');
            const isOpen = dropdown.classList.contains('open');

            // Ferme tous les dropdowns avant d'ouvrir le bon
            document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('open'));

            if (!isOpen) {
                dropdown.classList.add('open');
            }
        });
    });

    // Ferme tous les dropdowns en cliquant ailleurs dans la page
    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('open'));
    });

</script>