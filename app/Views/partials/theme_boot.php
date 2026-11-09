<?php
/**
 * Theme boot. Runs in <head>, before any stylesheet paints, so a dark-mode
 * user never sees a white flash. The saved choice wins; with no choice yet
 * the system setting decides. The switch lives in partials/theme_switch.
 */
?>
<script>
(function () {
    var t = null;
    try { t = localStorage.getItem('pm-theme'); } catch (e) {}
    if (t !== 'dark' && t !== 'light') {
        t = (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    }
    var root = document.documentElement;
    root.setAttribute('data-theme', t);
    root.setAttribute('data-bs-theme', t);
    root.style.colorScheme = t;
})();
</script>
