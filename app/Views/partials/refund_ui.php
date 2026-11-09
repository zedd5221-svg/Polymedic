<?php
/*
 * Refund window shared by the Admin and Receptionist Payments pages.
 *
 * Modes
 *   request  Receptionist: asks an administrator to refund (payment stays paid).
 *   refund   Administrator: refunds at once, no approval needed.
 *   review   Administrator: confirms or rejects a receptionist's request.
 *
 * Every mode needs the "I confirm" box ticked before either footer button
 * works (Confirm / Cancel, or Confirm refund / Reject request). The × in
 * the header still closes the window so nobody is trapped. The server
 * re-checks the reason and the confirmation.
 *
 * Expects: $role ('admin' | 'receptionist'). Reads payment data from the
 * page's #pmReceipts JSON (keyed by payment id).
 */
$isAdmin = ($role ?? '') === 'admin';
$prefix  = $isAdmin ? 'admin' : 'receptionist';
$reasons = ['Test was not performed', 'Duplicate payment', 'Overcharged / wrong amount', 'Patient cancelled the request'];
?>
<div class="modal fade pm-modal rf-modal" id="pmRefundModal" tabindex="-1" aria-hidden="true" aria-labelledby="rfTitle">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="rfTitle">Refund payment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
            <!-- What is being refunded -->
            <div class="rf-summary">
                <div class="rf-summary-main">
                    <span class="rf-label">Refund amount</span>
                    <strong class="rf-amount" id="rfAmount">₱0.00</strong>
                </div>
                <dl class="rf-facts">
                    <div><dt>Patient</dt><dd id="rfPatient">—</dd></div>
                    <div><dt>Receipt</dt><dd id="rfRef" class="rf-mono">—</dd></div>
                    <div><dt>Paid on</dt><dd id="rfDate">—</dd></div>
                </dl>
            </div>

            <!-- review: the receptionist's request -->
            <div class="rf-request" id="rfRequestBox" hidden>
                <span class="rf-label">Reason for the refund</span>
                <blockquote class="rf-quote" id="rfRequestReason"></blockquote>
                <p class="rf-meta" id="rfRequestMeta"></p>
            </div>

            <!-- request / refund: the reason -->
            <div class="rf-field" id="rfReasonField">
                <label class="rf-label" for="rfReason">Reason for the refund <span class="rf-req" aria-hidden="true">*</span></label>
                <div class="rf-chips" role="group" aria-label="Common reasons">
                    <?php foreach ($reasons as $r): ?>
                        <button type="button" class="rf-chip" data-reason="<?= esc($r, 'attr') ?>"><?= esc($r) ?></button>
                    <?php endforeach; ?>
                </div>
                <textarea id="rfReason" class="form-control rf-textarea" rows="3" maxlength="500"
                          placeholder="Why does the customer want a refund? This is printed on the receipt."
                          aria-describedby="rfReasonHint"></textarea>
                <div class="rf-hint" id="rfReasonHint"><span id="rfCount">0</span>/500 · at least 5 characters</div>
            </div>

            <p class="rf-note" id="rfNote" hidden></p>

            <label class="rf-confirm">
                <input type="checkbox" id="rfConfirm">
                <span id="rfConfirmText">I confirm this refund is correct.</span>
            </label>

            <p class="rf-error" id="rfError" role="alert" hidden></p>
        </div>

        <div class="modal-footer rf-foot">
            <button type="button" class="pm-btn" id="rfSecondary" disabled>Cancel</button>
            <button type="button" class="pm-btn pm-btn--primary" id="rfPrimary" disabled>
                <span class="rf-spin" aria-hidden="true"></span>
                <span id="rfPrimaryText">Confirm</span>
            </button>
        </div>
    </div></div>
</div>

<style>
/* Self-contained: also used on the invoice page, which has no Payments CSS */
.rf-modal .modal-dialog { max-width: 520px; }
.rf-modal .modal-content { border:1px solid var(--pm-line); border-radius:16px; background:var(--pm-surface); color:var(--pm-text); }
.rf-modal .modal-header { padding:1rem 1.25rem; border-bottom:1px solid var(--pm-line); }
.rf-modal .modal-title { font-size:1rem; font-weight:650; color:var(--pm-text); }
.rf-modal .modal-body { padding:1.25rem; }
.rf-modal .modal-footer { padding:.85rem 1.25rem; border-top:1px solid var(--pm-line); }
.rf-modal .pm-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; height:36px; padding:0 .9rem;
    border:1px solid var(--pm-line); border-radius:10px; background:var(--pm-surface); color:var(--pm-text); font-size:.85rem; font-weight:600; }
.rf-modal .pm-btn:hover:not(:disabled) { background:var(--pm-subtle); }
.rf-modal .pm-btn--primary { background:var(--pm-accent); border-color:var(--pm-accent); color:#fff; }
.rf-modal .pm-btn--primary:hover:not(:disabled) { background:var(--pm-accent-hover); border-color:var(--pm-accent-hover); color:#fff; }
.rf-label { display:block; font-size:.7rem; font-weight:600; letter-spacing:.06em; text-transform:uppercase; color:var(--pm-text-muted); margin-bottom:.35rem; }
.rf-req { color:var(--pm-danger); }
.rf-mono { font-family:var(--pm-mono); font-size:.8rem; }

.rf-summary { border:1px solid var(--pm-line); border-radius:12px; padding:.9rem 1rem; margin-bottom:1rem; background:var(--pm-subtle); }
.rf-summary-main { display:flex; align-items:baseline; justify-content:space-between; gap:1rem; padding-bottom:.65rem; margin-bottom:.65rem; border-bottom:1px solid var(--pm-line); }
.rf-summary-main .rf-label { margin:0; }
.rf-amount { font-size:1.5rem; font-weight:700; color:var(--pm-text); font-variant-numeric:tabular-nums; }
.rf-facts { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:.75rem; margin:0; }
.rf-facts dt { font-size:.68rem; font-weight:600; color:var(--pm-text-muted); text-transform:uppercase; letter-spacing:.05em; }
.rf-facts dd { margin:.1rem 0 0; font-size:.84rem; color:var(--pm-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

.rf-request { margin-bottom:1rem; }
.rf-quote { margin:0; padding:.7rem .9rem; border-left:3px solid var(--pm-accent); background:var(--pm-accent-soft); border-radius:0 10px 10px 0; color:var(--pm-text); font-size:.9rem; white-space:pre-line; }
.rf-meta { margin:.45rem 0 0; font-size:.78rem; color:var(--pm-text-muted); }

.rf-field { margin-bottom:1rem; }
.rf-chips { display:flex; flex-wrap:wrap; gap:.35rem; margin-bottom:.5rem; }
.rf-chip { border:1px solid var(--pm-line); background:var(--pm-surface); color:var(--pm-text-soft); border-radius:999px; padding:.22rem .65rem; font-size:.76rem; cursor:pointer; }
.rf-chip:hover { border-color:var(--pm-accent-line); color:var(--pm-accent-text); }
.rf-textarea { font-size:.9rem; resize:vertical; min-height:84px; }
.rf-hint { margin-top:.3rem; font-size:.74rem; color:var(--pm-text-muted); }

.rf-note { margin:0 0 1rem; padding:.6rem .8rem; border-radius:10px; font-size:.82rem; color:var(--pm-text-soft); background:var(--pm-tint-amber); }

.rf-confirm { display:flex; gap:.6rem; align-items:flex-start; padding:.75rem .85rem; border:1px solid var(--pm-line); border-radius:10px; cursor:pointer; font-size:.86rem; color:var(--pm-text); }
.rf-confirm:has(input:checked) { border-color:var(--pm-accent-line); background:var(--pm-accent-soft); }
.rf-confirm input { width:17px; height:17px; margin-top:.1rem; flex-shrink:0; accent-color:var(--pm-accent); cursor:pointer; }

.rf-error { margin:.75rem 0 0; font-size:.82rem; color:var(--pm-danger); }

.rf-foot { gap:.5rem; }
.rf-foot .pm-btn:disabled { opacity:.45; cursor:not-allowed; }
.rf-foot .pm-btn--danger { background:var(--pm-danger); border-color:var(--pm-danger); color:#fff; }
.rf-foot .pm-btn--danger:hover:not(:disabled) { filter:brightness(.92); color:#fff; }
.rf-spin { display:none; width:14px; height:14px; border:2px solid currentColor; border-right-color:transparent; border-radius:50%; animation:rfSpin .7s linear infinite; }
.rf-modal.is-busy .rf-spin { display:inline-block; }
@keyframes rfSpin { to { transform:rotate(360deg); } }
@media (prefers-reduced-motion: reduce) { .rf-spin { animation:none; } }
@media (max-width: 480px) { .rf-facts { grid-template-columns:1fr 1fr; } }

/* Refund state on payment rows */
.rf-row-state { display:block; margin-top:.25rem; font-size:.72rem; color:var(--pm-text-muted); }
.rf-row-state--pending  { color:var(--pm-dk-fg-amber, #b45309); }
.rf-row-state--rejected { color:var(--pm-text-muted); }
.pm-icon-btn--review { color:var(--pm-dk-fg-amber, #b45309); border-color:var(--pm-dk-edge-amber, #fcd9a8); background:var(--pm-tint-amber); }
.pm-icon-btn:disabled { opacity:.45; cursor:not-allowed; }
</style>

<script>
(function () {
    'use strict';
    // No "already loaded" guard: the sidebar swaps pages without a reload
    // and re-runs this script, so it must bind to the new modal each time.

    var IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
    var BASE     = <?= json_encode(base_url($prefix)) ?>;
    var CSRF     = <?= json_encode(csrf_hash()) ?>;
    var FLASH    = 'pm-refund-flash';

    var $ = function (id) { return document.getElementById(id); };
    var modalEl = $('pmRefundModal');
    if (!modalEl) { return; }

    function peso(v) { return '₱' + (Number(v) || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }
    function data(id) {
        var el = $('pmReceipts'); if (!el) { return null; }
        try { var all = JSON.parse(el.textContent) || {}; return all[String(id)] || all[id] || null; } catch (e) { return null; }
    }
    function fmtDate(s) {
        if (!s) { return '—'; }
        var d = new Date(String(s).replace(' ', 'T'));
        return isNaN(d.getTime()) ? s : d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    }

    var reason = $('rfReason'), confirmBox = $('rfConfirm'), primary = $('rfPrimary'), secondary = $('rfSecondary'),
        errorEl = $('rfError'), count = $('rfCount');
    var state = { id: null, mode: null };

    function setError(msg) { errorEl.textContent = msg || ''; errorEl.hidden = !msg; }

    // Both footer buttons wait for the confirmation box.
    function sync() {
        var on = confirmBox.checked && !modalEl.classList.contains('is-busy');
        primary.disabled = !on;
        secondary.disabled = !on;
    }

    function open(id, mode) {
        var d = data(id);
        if (!d) { alert('Payment details are not available.'); return; }
        state = { id: id, mode: mode };

        $('rfAmount').textContent  = peso(d.paid);
        $('rfPatient').textContent = d.name || '—';
        $('rfRef').textContent     = d.ref || '—';
        $('rfDate').textContent    = fmtDate(d.date);

        var review = mode === 'review';
        $('rfRequestBox').hidden  = !review;
        $('rfReasonField').hidden = review;
        reason.value = ''; count.textContent = '0';

        var note = $('rfNote');
        if (review) {
            $('rfTitle').textContent = 'Review refund request';
            $('rfRequestReason').textContent = d.refund_reason || '—';
            $('rfRequestMeta').textContent = 'Requested by ' + (d.refund_requested_by || 'the front desk')
                + (d.refund_requested_at ? ' · ' + new Date(String(d.refund_requested_at).replace(' ', 'T')).toLocaleString() : '');
            $('rfConfirmText').textContent = 'I have checked this request and the reason above.';
            $('rfPrimaryText').textContent = 'Confirm refund';
            secondary.textContent = 'Reject request';
            secondary.classList.add('pm-btn--danger');
            note.hidden = true;
        } else {
            secondary.textContent = 'Cancel';
            secondary.classList.remove('pm-btn--danger');
            if (mode === 'request') {
                $('rfTitle').textContent = 'Request a refund';
                $('rfConfirmText').textContent = 'I confirm the customer is asking for a refund of ' + peso(d.paid) + ' for the reason above.';
                $('rfPrimaryText').textContent = 'Confirm';
                note.textContent = 'An administrator must approve this refund. The payment stays paid until they do.';
                note.hidden = false;
            } else {
                $('rfTitle').textContent = 'Refund payment';
                $('rfConfirmText').textContent = 'I confirm ' + peso(d.paid) + ' will be refunded to ' + (d.name || 'the customer') + '. This cannot be undone.';
                $('rfPrimaryText').textContent = 'Confirm';
                note.hidden = true;
            }
        }

        confirmBox.checked = false;
        modalEl.classList.remove('is-busy');
        setError('');
        sync();
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        if (!review) { setTimeout(function () { reason.focus(); }, 250); }
    }

    function post(url, body) {
        var form = new URLSearchParams(body);
        form.append('confirmed', '1');
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/x-www-form-urlencoded' },
            body: form.toString()
        }).then(function (r) { return r.json(); });
    }

    function run(url, body) {
        modalEl.classList.add('is-busy'); sync(); setError('');
        post(url, body)
            .then(function (res) {
                if (!res.success) { throw new Error(res.message || 'Something went wrong.'); }
                try { sessionStorage.setItem(FLASH, res.message || 'Done.'); } catch (e) {}
                location.reload();
            })
            .catch(function (err) {
                modalEl.classList.remove('is-busy'); sync();
                setError(err.message || 'Something went wrong. Please try again.');
            });
    }

    primary.addEventListener('click', function () {
        if (!confirmBox.checked) { return; }
        var id = encodeURIComponent(state.id);
        if (state.mode === 'review') { run(BASE + '/refund-approve/' + id, {}); return; }

        var text = reason.value.trim();
        if (text.length < 5) { setError('Please give the reason for the refund (at least 5 characters).'); reason.focus(); return; }
        run(BASE + (state.mode === 'request' ? '/refund-request/' : '/refund-payment/') + id, { reason: text });
    });

    secondary.addEventListener('click', function () {
        if (!confirmBox.checked) { return; }
        if (state.mode === 'review') {
            run(BASE + '/refund-reject/' + encodeURIComponent(state.id), {});
        } else {
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        }
    });

    confirmBox.addEventListener('change', sync);
    reason.addEventListener('input', function () { count.textContent = String(reason.value.length); setError(''); });
    modalEl.querySelectorAll('.rf-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            reason.value = chip.dataset.reason;
            count.textContent = String(reason.value.length);
            reason.focus();
        });
    });

    window.pmRefund = {
        open: open,
        // Row buttons
        request: function (id) { open(id, 'request'); },
        refund:  function (id) { open(id, IS_ADMIN ? 'refund' : 'request'); },
        review:  function (id) { if (IS_ADMIN) { open(id, 'review'); } }
    };

    // Result of the last action, shown after the reload.
    try {
        var msg = sessionStorage.getItem(FLASH);
        if (msg) {
            sessionStorage.removeItem(FLASH);
            var show = function () { if (window.pmToast) { window.pmToast(msg, 'success'); } };
            if (window.pmToast) { show(); } else { window.addEventListener('load', show); }
        }
    } catch (e) {}

    // Deep links: admin/payments?review=ID (from the notification),
    // receptionist/payments?payment=ID (result notification).
    var params = new URLSearchParams(location.search);
    var reviewId = params.get('review'), paymentId = params.get('payment');
    var deepOpen = function () {
        if (IS_ADMIN && reviewId) {
            var d = data(reviewId);
            if (d && d.refund_status === 'pending') { open(reviewId, 'review'); }
            else if (d && window.viewPayment) { window.viewPayment(Number(reviewId)); }
        } else if (paymentId && window.viewPayment) {
            window.viewPayment(Number(paymentId));
        }
    };
    if (reviewId || paymentId) { setTimeout(deepOpen, 300); }
})();
</script>
