<?php
/**
 * Dark / Light switch for the staff header (left of the clock).
 *
 * A segmented pill: one ink "thumb" slides under the chosen side. Switching
 * plays a circular reveal of the new theme from the switch itself where the
 * browser supports View Transitions, and a short cross-fade elsewhere.
 * Styles: pm-theme.css (.pm-theme-switch). Boot: partials/theme_boot.
 */
?>
<button type="button" class="pm-theme-switch" role="switch" aria-checked="false" aria-label="Dark mode" title="Switch theme">
    <span class="pts-thumb" aria-hidden="true"></span>
    <span class="pts-opt pts-dark" aria-hidden="true">
        <svg class="pts-ic pts-moon" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 14.3A8.5 8.5 0 0 1 9.7 3.5a8.5 8.5 0 1 0 10.8 10.8Z"/></svg>
        <span class="pts-label">Dark</span>
    </span>
    <span class="pts-opt pts-light" aria-hidden="true">
        <svg class="pts-ic pts-sun" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="12" cy="12" r="3.6"/><path d="M12 2.8v2.1M12 19.1v2.1M2.8 12h2.1M19.1 12h2.1M5.5 5.5 7 7M17 17l1.5 1.5M5.5 18.5 7 17M17 7l1.5-1.5"/></svg>
        <span class="pts-label">Light</span>
    </span>
</button>

<script>
(function () {
    var btn = document.currentScript && document.currentScript.previousElementSibling;
    if (!btn || !btn.classList.contains('pm-theme-switch')) btn = document.querySelector('.pm-theme-switch');
    if (!btn) return;

    var root = document.documentElement;
    var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

    function current() { return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'; }

    function sync() {
        var dark = current() === 'dark';
        btn.setAttribute('aria-checked', dark ? 'true' : 'false');
        btn.setAttribute('title', dark ? 'Switch to light mode' : 'Switch to dark mode');
    }

    /* Chart.js draws on canvas and cannot read CSS variables, so its
       default text and grid colours are set here and charts redrawn. */
    /* Light-theme colours hard-coded in chart configs (white bar gaps, pale
       grid lines) are swapped while dark mode is on and restored after. */
    function isLight(c) {
        if (typeof c !== 'string') return false;
        var m = c.trim().match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i), r, g, b;
        if (m) {
            var h = m[1].length === 3 ? m[1].replace(/./g, '$&$&') : m[1];
            r = parseInt(h.slice(0, 2), 16); g = parseInt(h.slice(2, 4), 16); b = parseInt(h.slice(4, 6), 16);
        } else if ((m = c.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/i))) {
            r = +m[1]; g = +m[2]; b = +m[3];
        } else return /^white$/i.test(c.trim());
        return (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255 > 0.82;
    }
    function swap(obj, key, darkVal, dark) {
        if (!obj) return;
        var store = '_pm_' + key;
        if (dark) {
            if (obj[store] === undefined && isLight(obj[key])) { obj[store] = obj[key]; obj[key] = darkVal; }
        } else if (obj[store] !== undefined) {
            obj[key] = obj[store]; delete obj[store];
        }
    }
    function themeChart(chart) {
        var dark = current() === 'dark';
        var o = chart.options || {};
        var scales = o.scales || {};
        Object.keys(scales).forEach(function (k) {
            var s = scales[k] || {};
            swap(s.grid, 'color', 'rgba(255,255,255,0.07)', dark);
            swap(s.border, 'color', 'rgba(255,255,255,0.12)', dark);
            swap(s.grid, 'borderColor', 'rgba(255,255,255,0.12)', dark);
        });
        var t = o.plugins && o.plugins.tooltip;
        if (t) swap(t, 'backgroundColor', '#1B252C', dark);
        ((chart.config.data || chart.data || {}).datasets || []).forEach(function (d) {
            swap(d, 'borderColor', '#131B20', dark);
        });
    }
    var pluginOn = false;
    function registerChartPlugin() {
        if (pluginOn || !window.Chart || !Chart.register) return;
        try { Chart.register({ id: 'pmTheme', beforeUpdate: themeChart }); pluginOn = true; } catch (e) {}
    }

    function tuneCharts() {
        if (!window.Chart || !Chart.defaults) return;
        registerChartPlugin();
        var dark = current() === 'dark';
        Chart.defaults.color = dark ? '#9AA8B0' : '#5E6873';
        Chart.defaults.borderColor = dark ? 'rgba(255,255,255,0.08)' : 'rgba(20,28,36,0.08)';
        var list = Chart.instances ? Object.keys(Chart.instances).map(function (k) { return Chart.instances[k]; }) : [];
        list.forEach(function (c) { try { c.update('none'); } catch (e) {} });
    }

    function apply(theme) {
        root.setAttribute('data-theme', theme);
        root.setAttribute('data-bs-theme', theme);
        root.style.colorScheme = theme;
        try { localStorage.setItem('pm-theme', theme); } catch (e) {}
        sync();
        tuneCharts();
        try { document.dispatchEvent(new CustomEvent('pm:themechange', { detail: { theme: theme } })); } catch (e) {}
    }

    btn.addEventListener('click', function () {
        var next = current() === 'dark' ? 'light' : 'dark';

        /* Thumb moves first; the page follows a beat later */
        btn.classList.add('is-switching');
        setTimeout(function () { btn.classList.remove('is-switching'); }, 520);

        if (reduce) { apply(next); return; }

        if (document.startViewTransition) {
            var r = btn.getBoundingClientRect();
            var x = r.left + r.width / 2, y = r.top + r.height / 2;
            var end = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));
            root.style.setProperty('--vt-x', x + 'px');
            root.style.setProperty('--vt-y', y + 'px');
            root.style.setProperty('--vt-r', end + 'px');
            root.classList.add('pm-vt');
            var vt = document.startViewTransition(function () { apply(next); });
            vt.finished.then(function () { root.classList.remove('pm-vt'); }, function () { root.classList.remove('pm-vt'); });
            return;
        }

        root.classList.add('pm-theme-fading');
        apply(next);
        setTimeout(function () { root.classList.remove('pm-theme-fading'); }, 420);
    });

    /* Follow the system setting until the user has picked one */
    if (window.matchMedia) {
        var mq = matchMedia('(prefers-color-scheme: dark)');
        var onSys = function (e) {
            var saved = null;
            try { saved = localStorage.getItem('pm-theme'); } catch (err) {}
            if (saved) return;
            root.setAttribute('data-theme', e.matches ? 'dark' : 'light');
            root.setAttribute('data-bs-theme', e.matches ? 'dark' : 'light');
            sync(); tuneCharts();
        };
        if (mq.addEventListener) mq.addEventListener('change', onSys);
    }

    sync();
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', tuneCharts);
    else tuneCharts();
    window.addEventListener('load', tuneCharts);
})();
</script>
