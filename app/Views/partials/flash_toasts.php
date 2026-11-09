<?php
/*
 * Flash toasts, island style: one place for every "saved" / "failed" message.
 * Rendered once by each staff layout. It reads the session's success / error
 * flash, so pages no longer print their own banners.
 * A small black pill appears at the top centre and springs open into the
 * message; on close it folds back into the pill and slips away.
 * Script use: window.pmToast('Exported 12 rows', 'success' | 'error' | 'info', 'optional detail line').
 */
$flashToasts = [];
foreach (['success' => 'success', 'message' => 'success', 'error' => 'error'] as $key => $type) {
    $msg = session()->getFlashdata($key);
    if (is_string($msg) && trim($msg) !== '') {
        $flashToasts[] = ['type' => $type, 'text' => trim($msg)];
    }
}
?>
<div class="pm-toasts" id="pmToasts" aria-label="Notifications">
    <?php foreach ($flashToasts as $t): ?>
        <div data-flash data-type="<?= $t['type'] ?>" hidden><?= esc($t['text']) ?></div>
    <?php endforeach; ?>
</div>

<style>
.pm-toasts {
    --island-spring: cubic-bezier(.32, 1.32, .5, 1);
    --island-ease:   cubic-bezier(.32, .72, 0, 1);
    position: fixed; top: 10px; left: 50%; z-index: 1090;
    transform: translateX(-50%);
    display: flex; flex-direction: column; align-items: center;
    width: calc(100vw - 32px); max-width: 420px;
    pointer-events: none;
}
/* The slot collapses its height on close, so the islands below glide up */
.pm-toast-slot {
    display: grid; grid-template-rows: 1fr; grid-template-columns: minmax(0, 1fr); justify-items: center; width: 100%; margin-bottom: 8px;
    transition: grid-template-rows .32s var(--island-ease), margin .32s var(--island-ease);
}
.pm-toast-slot.is-collapsing { grid-template-rows: 0fr; margin-bottom: 0; }
.pm-toast-slot > .pm-toast-card { min-height: 0; }

.pm-toast-card {
    --tone: #30D158;
    box-sizing: border-box; overflow: hidden; pointer-events: auto;
    width: max-content; max-width: 100%;
    display: flex; justify-content: center;
    background: #0A0C10; color: #fff;
    border-radius: 28px;
    box-shadow: 0 10px 30px -8px rgba(0,0,0,.45), 0 2px 6px rgba(0,0,0,.18);
    transition:
        width .56s var(--island-spring),
        height .56s var(--island-spring),
        border-radius .56s var(--island-spring),
        transform .3s var(--island-ease),
        opacity .3s var(--island-ease);
}
.pm-toast-card[data-type="error"] { --tone: #FF453A; }
.pm-toast-card[data-type="info"]  { --tone: #0A84FF; }

/* The pill it grows from and folds back into */
.pm-toast-card.is-pill { border-radius: 999px; }
.pm-toast-card.is-born { transform: scale(.6); opacity: 0; }
.pm-toast-card.is-gone { transform: scale(.55) translateY(-6px); opacity: 0; transition-duration: .22s; }

.pm-toast-inner {
    display: grid; grid-template-columns: 34px 1fr auto; align-items: center; gap: 12px;
    box-sizing: border-box; flex: 0 0 auto;
    padding: 11px 10px 11px 11px; min-width: 260px;
    opacity: 1; transform: none; filter: none;
    transition: opacity .28s .16s ease, transform .4s .14s var(--island-ease), filter .28s .16s ease;
}
.pm-toast-card.is-pill .pm-toast-inner {
    opacity: 0; transform: scale(.92); filter: blur(4px);
    transition: opacity .14s ease, transform .14s ease, filter .14s ease;
}

.pm-toast-icon {
    width: 34px; height: 34px; border-radius: 50%;
    display: grid; place-items: center;
    background: var(--tone); color: #fff; font-size: 18px; line-height: 1;
}
.pm-toast-icon i { -webkit-text-stroke: .6px currentColor; }

.pm-toast-body  { min-width: 0; }
.pm-toast-title { margin: 0; font-size: 14px; font-weight: 600; line-height: 1.35; color: #fff; overflow-wrap: anywhere; }
.pm-toast-text  { margin: 1px 0 0; font-size: 12.5px; line-height: 1.4; color: rgba(255,255,255,.55); }

.pm-toast-close {
    width: 30px; height: 30px; border: 0; border-radius: 50%;
    background: rgba(255,255,255,.08); color: rgba(255,255,255,.65);
    display: grid; place-items: center; cursor: pointer; font-size: 12px;
    transition: background-color .15s ease, color .15s ease;
}
.pm-toast-close:hover { background: rgba(255,255,255,.16); color: #fff; }
.pm-toast-close:focus-visible { outline: 2px solid #0A84FF; outline-offset: 2px; }

@media (prefers-reduced-motion: reduce) {
    .pm-toast-card, .pm-toast-inner, .pm-toast-slot { transition: opacity .2s linear !important; }
    .pm-toast-card.is-born, .pm-toast-card.is-gone { transform: none; }
    .pm-toast-card.is-pill .pm-toast-inner { transform: none; filter: none; }
}
@media print { .pm-toasts { display: none !important; } }
</style>

<script>
(function () {
    var stack = document.getElementById('pmToasts');
    if (!stack || window.pmToast) return;

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var PILL = { w: 128, h: 36 };

    var META = {
        success: { icon: 'bi-check-lg',       detail: 'Just now',         life: 10000 },
        error:   { icon: 'bi-exclamation-lg', detail: 'Action not saved', life: 10000 },
        info:    { icon: 'bi-info-lg',        detail: 'Just now',         life: 10000 }
    };

    function close(slot) {
        if (!slot || slot.dataset.closing) return;
        slot.dataset.closing = '1';
        clearTimeout(slot._timer);
        var card = slot.firstElementChild;
        // 1. content fades, 2. capsule folds back into the pill, 3. pill slips away
        card.classList.add('is-pill');
        setTimeout(function () {
            card.style.width = PILL.w + 'px';
            card.style.height = PILL.h + 'px';
        }, reduce ? 0 : 110);
        setTimeout(function () {
            card.classList.add('is-gone');
            slot.classList.add('is-collapsing');
        }, reduce ? 0 : 420);
        setTimeout(function () { slot.remove(); }, reduce ? 260 : 780);
    }

    // auto-close, paused while the pointer or keyboard focus is on the island
    function arm(slot, ms) {
        var left = ms, started;
        function run()   { started = Date.now(); slot._timer = setTimeout(function () { close(slot); }, left); }
        function pause() { clearTimeout(slot._timer); left = Math.max(1200, left - (Date.now() - started)); }
        slot.addEventListener('mouseenter', pause);
        slot.addEventListener('mouseleave', run);
        slot.addEventListener('focusin', pause);
        slot.addEventListener('focusout', run);
        run();
    }

    function show(text, type, detail) {
        type = META[type] ? type : (type === true ? 'error' : 'success');
        var m = META[type];

        var slot = document.createElement('div');
        slot.className = 'pm-toast-slot';

        var card = document.createElement('div');
        card.className = 'pm-toast-card';
        card.dataset.type = type;
        card.setAttribute('role', type === 'error' ? 'alert' : 'status');

        var inner = document.createElement('div');
        inner.className = 'pm-toast-inner';

        var icon = document.createElement('span');
        icon.className = 'pm-toast-icon';
        icon.innerHTML = '<i class="bi ' + m.icon + '" aria-hidden="true"></i>';

        var body = document.createElement('div');
        body.className = 'pm-toast-body';
        var title = document.createElement('p');
        title.className = 'pm-toast-title';
        // First sentence is the headline; anything after it ("Report completed. Ready to release.")
        // drops to the small grey line, so the bold part stays short.
        var raw = String(text).trim(), head = raw, rest = '';
        var cut = raw.match(/^(.+?)(?:[.!?]+\s+|:\s+)([\s\S]+)$/);
        if (cut && !detail) { head = cut[1]; rest = cut[2]; }
        var tidy = function (s) { return s.replace(/[.!]+$/, '').trim(); };
        title.textContent = tidy(head);
        var sub = document.createElement('p');
        sub.className = 'pm-toast-text';
        rest = tidy(rest);
        sub.textContent = detail || (rest ? rest.charAt(0).toUpperCase() + rest.slice(1) : m.detail);
        body.appendChild(title);
        body.appendChild(sub);

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pm-toast-close';
        btn.setAttribute('aria-label', 'Dismiss notification');
        btn.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
        btn.addEventListener('click', function () { close(slot); });

        inner.appendChild(icon);
        inner.appendChild(body);
        inner.appendChild(btn);
        card.appendChild(inner);
        slot.appendChild(card);
        stack.appendChild(slot);

        // measure the open size, then start from the pill and spring out to it
        // (measured on the content, then pinned, so the text keeps its final layout while the capsule grows around it)
        inner.style.maxWidth = stack.clientWidth + 'px';
        inner.style.width = 'max-content';
        var w = Math.ceil(inner.getBoundingClientRect().width);
        inner.style.width = w + 'px';
        var h = Math.ceil(inner.getBoundingClientRect().height);
        card.classList.add('is-pill', 'is-born');
        card.style.width = PILL.w + 'px';
        card.style.height = PILL.h + 'px';
        void card.offsetWidth;
        card.classList.remove('is-born');
        setTimeout(function () {
            card.style.width = w + 'px';
            card.style.height = h + 'px';
            card.classList.remove('is-pill');
        }, reduce ? 0 : 140);

        arm(slot, m.life);

        // keep at most three on screen
        var open = stack.querySelectorAll('.pm-toast-slot:not([data-closing])');
        if (open.length > 3) close(open[0]);
        return slot;
    }

    window.pmToast = show;

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var slot = document.activeElement && document.activeElement.closest && document.activeElement.closest('.pm-toast-slot');
        if (slot) close(slot);
    });

    // messages carried over from the last request
    stack.querySelectorAll('[data-flash]').forEach(function (el, i) {
        var text = el.textContent, type = el.dataset.type;
        el.remove();
        setTimeout(function () { show(text, type); }, 180 + i * 260);
    });
})();
</script>
