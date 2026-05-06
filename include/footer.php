<footer>

</footer>

<script>
// Ouvre/ferme les dropdowns au clic
document.querySelectorAll('.header-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const dropdown = this.querySelector('.dropdown');
        const isOpen = dropdown.classList.contains('open');
        // Fermer tous les autres
        document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('open'));
        if (!isOpen) dropdown.classList.add('open');
    });
});
// Fermer en cliquant ailleurs
document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('open'));
});
</script>