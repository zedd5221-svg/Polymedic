</main>

<script>
    // Annex on/off without a reload; keep the choice in the URL so a refresh or a shared link keeps it.
    (function () {
        var toggle = document.getElementById('annexToggle');
        var annex  = document.getElementById('annex');
        if (!toggle || !annex) return;
        toggle.addEventListener('change', function () {
            annex.classList.toggle('is-off', !this.checked);
            var url = new URL(window.location.href);
            if (this.checked) url.searchParams.delete('annex'); else url.searchParams.set('annex', '0');
            history.replaceState(null, '', url);
        });
    })();
</script>
</body>
</html>
