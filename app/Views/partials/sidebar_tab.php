<?php
/**
 * Sidebar active tab.
 *
 * One canvas-coloured tab sits under the menu and glides to whichever item
 * is active, so moving between pages reads as one tab travelling rather
 * than one item switching off and another switching on. It watches the
 * items' `active` class, so the layouts' own navigation code needs no
 * changes. Styles live in pm-theme.css (.pm-navtab, body.has-navtab).
 */
?>
<script>
(function () {
    var nav = document.querySelector('.admin-sidebar .sidebar-nav');
    if (!nav || !window.requestAnimationFrame) return;

    var tab = document.createElement('span');
    tab.className = 'pm-navtab';
    tab.setAttribute('aria-hidden', 'true');
    nav.insertBefore(tab, nav.firstChild);
    document.body.classList.add('has-navtab');

    var current = null;

    function place() {
        var li = nav.querySelector('.menu-item.active');
        var btn = li && li.querySelector('.menu-btn');
        if (!btn) { tab.classList.remove('is-on'); current = null; return; }

        var n = nav.getBoundingClientRect();
        var b = btn.getBoundingClientRect();
        var left = b.left - n.left;
        tab.style.setProperty('--tab-y', (b.top - n.top + nav.scrollTop) + 'px');
        tab.style.setProperty('--tab-x', left + 'px');
        tab.style.setProperty('--tab-h', b.height + 'px');
        tab.style.setProperty('--tab-w', (nav.clientWidth - left) + 'px');

        if (!tab.classList.contains('is-on')) {
            tab.classList.add('is-on', 'is-enter');
        } else if (current && current !== btn) {
            /* Restart the small settle on the item that just received the tab */
            btn.classList.remove('is-tab-arrive');
            void btn.offsetWidth;
            btn.classList.add('is-tab-arrive');
        }
        current = btn;
    }

    tab.addEventListener('animationend', function () { tab.classList.remove('is-enter'); });
    place();
    /* Place first, then allow movement, so the page never opens with a slide */
    requestAnimationFrame(function () {
        requestAnimationFrame(function () { tab.classList.add('is-ready'); });
    });

    var queued = false;
    function schedule() {
        if (queued) return;
        queued = true;
        requestAnimationFrame(function () { queued = false; place(); });
    }

    new MutationObserver(schedule).observe(nav, { attributes: true, attributeFilter: ['class'], subtree: true });

    /* Collapse/expand and window resizes change the item geometry */
    var sidebar = nav.closest('.admin-sidebar');
    if (window.ResizeObserver) {
        var ro = new ResizeObserver(schedule);
        ro.observe(nav);
        if (sidebar) ro.observe(sidebar);
    }
    if (sidebar) sidebar.addEventListener('transitionend', schedule);
    window.addEventListener('resize', schedule);
})();
</script>
