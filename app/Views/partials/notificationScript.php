<script>
(function () {
    'use strict';

    var tabs   = Array.prototype.slice.call(document.querySelectorAll('.nc-tab'));
    var chips  = Array.prototype.slice.call(document.querySelectorAll('.nc-chip'));
    var groups = Array.prototype.slice.call(document.querySelectorAll('.nc-group'));
    var cards  = Array.prototype.slice.call(document.querySelectorAll('.nc-card'));
    var empty  = document.getElementById('ncEmpty');
    var reset  = document.getElementById('ncClearFilter');
    var search = document.getElementById('ncSearch');
    var clearB = document.getElementById('ncSearchClear');

    /* One filter script serves all four role views, but only one view
       is ever on the page at a time, so guarding on presence is enough. */
    if (!cards.length) { return; }

    var state = { read: 'all', type: 'all', term: '' };

    cards.forEach(function (card) {
        var msg = card.querySelector('.nc-card-message');
        if (msg) { msg.dataset.plain = msg.textContent; }
    });

    function escapeRe(s) {
        return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function highlight(card, term) {
        var msg = card.querySelector('.nc-card-message');
        if (!msg || !msg.dataset.plain) { return; }

        var plain = msg.dataset.plain;

        if (!term) { msg.textContent = plain; return; }

        msg.textContent = '';
        var re = new RegExp(escapeRe(term), 'ig');
        var last = 0;
        var m;

        while ((m = re.exec(plain)) !== null) {
            if (m.index > last) {
                msg.appendChild(document.createTextNode(plain.slice(last, m.index)));
            }
            var mark = document.createElement('mark');
            mark.textContent = m[0];
            msg.appendChild(mark);
            last = m.index + m[0].length;
            if (m[0].length === 0) { re.lastIndex++; }
        }

        if (last < plain.length) {
            msg.appendChild(document.createTextNode(plain.slice(last)));
        }
    }

    function apply() {
        var visible = 0;
        var counts  = { all: 0, unread: 0, read: 0 };

        cards.forEach(function (card) {
            var okRead = state.read === 'all' || card.dataset.readStatus === state.read;
            var okType = state.type === 'all' || card.dataset.type === state.type;
            var okTerm = state.term === '' ||
                         (card.dataset.search || '').indexOf(state.term) !== -1;

            var show = okRead && okType && okTerm;
            card.classList.toggle('is-hidden', !show);

            if (okType && okTerm) {
                counts.all++;
                counts[card.dataset.readStatus]++;
            }

            if (show) { visible++; highlight(card, state.term); }
            else      { highlight(card, ''); }
        });

        groups.forEach(function (group) {
            var any = group.querySelector('.nc-card:not(.is-hidden)');
            group.classList.toggle('is-hidden', !any);
        });

        tabs.forEach(function (tab) {
            var el = tab.querySelector('[data-count-for]');
            if (el) { el.textContent = counts[el.dataset.countFor] || 0; }
        });

        if (empty) { empty.hidden = visible > 0; }
        if (clearB) { clearB.hidden = state.term === ''; }
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) {
                var on = t === tab;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            state.read = tab.dataset.filter || 'all';
            apply();
        });
    });

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            chips.forEach(function (c) {
                var on = c === chip;
                c.classList.toggle('is-active', on);
                c.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            state.type = chip.dataset.type || 'all';
            apply();
        });
    });

    if (search) {
        var timer = null;
        search.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                state.term = search.value.trim().toLowerCase();
                apply();
            }, 120);
        });

        search.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && search.value !== '') {
                e.preventDefault();
                search.value = '';
                state.term = '';
                apply();
            }
        });
    }

    if (clearB) {
        clearB.addEventListener('click', function () {
            search.value = '';
            state.term = '';
            search.focus();
            apply();
        });
    }

    if (reset) {
        reset.addEventListener('click', function () {
            state = { read: 'all', type: 'all', term: '' };
            if (search) { search.value = ''; }

            tabs.forEach(function (t) {
                var on = t.dataset.filter === 'all';
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            chips.forEach(function (c) {
                var on = c.dataset.type === 'all';
                c.classList.toggle('is-active', on);
                c.setAttribute('aria-pressed', on ? 'true' : 'false');
            });

            apply();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key !== '/' || !search) { return; }
        var tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || e.target.isContentEditable) { return; }
        e.preventDefault();
        search.focus();
    });

    apply();
})();
</script>