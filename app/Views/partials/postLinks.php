<?php
/*
 * Turns <a data-post href="..."> into a POST form submit carrying the
 * CSRF token, so state-changing actions (approve, cancel, delete,
 * release...) are never plain GET links another site could trigger.
 *
 * Any inline onclick="return confirm(...)" runs first; if it cancels,
 * the click is already defaultPrevented and nothing is sent.
 * Scripts can call window.pmPost(url) directly.
 */
?>
<script>
(function () {
    var TOKEN_NAME = <?= json_encode(csrf_token()) ?>;
    var TOKEN      = <?= json_encode(csrf_hash()) ?>;

    window.pmPost = function (url) {
        var form = document.createElement('form');
        form.method = 'post';
        form.action = url;
        form.style.display = 'none';

        var input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = TOKEN_NAME;
        input.value = TOKEN;
        form.appendChild(input);

        document.body.appendChild(form);
        form.submit();
    };

    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[data-post]');
        if (!link || e.defaultPrevented) return;
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        var href = link.getAttribute('href');
        if (!href || href === '#') return;

        e.preventDefault();
        window.pmPost(link.href);
    });
})();
</script>
