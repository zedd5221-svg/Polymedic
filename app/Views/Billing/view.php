<?= $this->extend($layout) ?>
<?= $this->section('pageTitle') ?>Invoice<?= $this->endSection() ?>
<?= $this->section($section) ?>

<?php
/** @var array $invoice @var array $items @var array $payments @var array|null $request */
/** @var string $routeBase @var bool $isAdminUser */

$statusMeta = [
    'draft'    => ['label' => 'Draft',    'tone' => 'draft'],
    'unpaid'   => ['label' => 'Unpaid',   'tone' => 'unpaid'],
    'partial'  => ['label' => 'Partial',  'tone' => 'partial'],
    'paid'     => ['label' => 'Paid',     'tone' => 'paid'],
    'void'     => ['label' => 'Void',     'tone' => 'void'],
    'refunded' => ['label' => 'Refunded', 'tone' => 'refunded'],
];
$meta    = $statusMeta[$invoice['status']] ?? ['label' => $invoice['status'], 'tone' => 'draft'];
$total   = (float) $invoice['total'];
$paid    = (float) $invoice['amount_paid'];
$balance = (float) $invoice['balance'];
$canPay  = $balance > 0 && !in_array($invoice['status'], ['void', 'refunded'], true);

$discountAmount = 0.0;
if ($invoice['discount_type'] === 'percent') {
    $discountAmount = round(((float) $invoice['subtotal']) * ((float) $invoice['discount_value']) / 100, 2);
} elseif ($invoice['discount_type'] === 'fixed') {
    $discountAmount = (float) $invoice['discount_value'];
}

// Can the admin push this invoice's line items back to the
// diagnostic request? Only while the request is still pending.
$canSync = $isAdminUser
    && !empty($invoice['request_id'])
    && !empty($invoice['request_type'])
    && is_array($request)
    && ($request['status'] ?? '') === 'pending'
    && !empty($items);
?>

<div class="bl">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="bl-alert bl-alert--success"><i class="bi bi-check-circle-fill"></i><span><?= esc(session()->getFlashdata('success')) ?></span></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="bl-alert bl-alert--error"><i class="bi bi-exclamation-circle-fill"></i><span><?= esc(session()->getFlashdata('error')) ?></span></div>
    <?php endif; ?>

    <header class="bl-head">
        <div>
            <h2 class="bl-title">
                <span class="bl-ref bl-ref--lg"><?= esc($invoice['invoice_no']) ?></span>
            </h2>
            <p class="bl-lede">
                Created <?= esc(date('M j, Y · g:i A', strtotime($invoice['created_at']))) ?>
                <?php if (!empty($invoice['created_by'])): ?>
                    <span class="bl-dot">·</span> by <?= esc($invoice['created_by']) ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="bl-head-actions">
            <a class="bl-btn" href="<?= base_url($routeBase) ?>"><i class="bi bi-arrow-left"></i> Back to list</a>
            <?php if ($canPay): ?>
                <a class="bl-btn bl-btn--primary" href="#pay"><i class="bi bi-cash-coin"></i> Record payment</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- SUMMARY -->
    <section class="bl-summary">
        <div class="bl-summary-col">
            <h3 class="bl-summary-title">Billed to</h3>
            <div class="bl-summary-name"><?= esc($invoice['patient_name']) ?></div>
            <?php if (!empty($invoice['patient_code'])): ?>
                <div class="bl-summary-sub"><?= esc($invoice['patient_code']) ?></div>
            <?php endif; ?>
            <?php if (!empty($invoice['appointment_id'])): ?>
                <div class="bl-summary-sub">Appointment #<?= (int) $invoice['appointment_id'] ?></div>
            <?php endif; ?>
            <?php if (!empty($invoice['request_id'])): ?>
                <div class="bl-summary-sub">
                    Diagnostic request #<?= (int) $invoice['request_id'] ?>
                    (<?= esc($invoice['request_type'] === 'xray' ? 'X-Ray' : 'Laboratory') ?>)
                    <?php if (is_array($request)): ?>
                        <span class="bl-status bl-status--<?= esc($request['status'] === 'pending' ? 'unpaid' : 'paid', 'attr') ?>" style="margin-left:.4rem;">
                            Request: <?= esc(ucfirst($request['status'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="bl-summary-col bl-summary-col--right">
            <div class="bl-summary-status">
                <span class="bl-status bl-status--<?= esc($meta['tone'], 'attr') ?>"><?= esc($meta['label']) ?></span>
            </div>
            <div class="bl-summary-totals">
                <div class="bl-summary-row"><span>Subtotal</span><span>₱<?= number_format((float) $invoice['subtotal'], 2) ?></span></div>
                <?php if ($discountAmount > 0): ?>
                    <div class="bl-summary-row"><span>Discount<?= $invoice['discount_type'] === 'percent' ? ' (' . (float) $invoice['discount_value'] . '%)' : '' ?></span><span>−₱<?= number_format($discountAmount, 2) ?></span></div>
                <?php endif; ?>
                <?php if ((float) $invoice['tax'] > 0): ?>
                    <div class="bl-summary-row"><span>Tax</span><span>₱<?= number_format((float) $invoice['tax'], 2) ?></span></div>
                <?php endif; ?>
                <div class="bl-summary-row bl-summary-row--grand"><span>Total</span><span>₱<?= number_format($total, 2) ?></span></div>
                <div class="bl-summary-row"><span>Paid</span><span>₱<?= number_format($paid, 2) ?></span></div>
                <div class="bl-summary-row <?= $balance > 0 ? 'bl-summary-row--due' : '' ?>"><span>Balance</span><span>₱<?= number_format($balance, 2) ?></span></div>
            </div>
        </div>
    </section>

    <!-- SYNC TO DIAGNOSTIC REQUEST -->
    <?php if ($canSync): ?>
    <section class="bl-panel bl-panel--plain">
        <h3 class="bl-section-title">Sync to diagnostic request</h3>
        <p class="bl-note" style="margin-bottom:.9rem;">
            The invoice has <?= count($items) ?> line item<?= count($items) === 1 ? '' : 's' ?>.
            The linked diagnostic request <strong>#<?= (int) $invoice['request_id'] ?></strong>
            is still <strong>pending</strong>, so you can push these services back to it.
            This adds any missing services to the request without removing existing ones.
        </p>
        <form method="post" action="<?= base_url($routeBase . '/sync-request/' . $invoice['id']) ?>"
              onsubmit="return confirm('Sync the invoice services back to the diagnostic request?');">
            <?= csrf_field() ?>
            <div class="bl-form-actions">
                <button type="submit" class="bl-btn bl-btn--primary">
                    <i class="bi bi-arrow-repeat"></i>
                    Sync to diagnostic request
                </button>
            </div>
        </form>
    </section>
    <?php endif; ?>

    <!-- LINE ITEMS -->
    <section class="bl-panel bl-panel--plain">
        <h3 class="bl-section-title">Line items</h3>
        <table class="bl-lines">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Category</th>
                    <th class="bl-num">Qty</th>
                    <th class="bl-num">Unit price</th>
                    <th class="bl-num">Line total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= esc($it['service_name']) ?></td>
                        <td><?= esc($it['category'] ?? '—') ?></td>
                        <td class="bl-num"><?= rtrim(rtrim(number_format((float) $it['qty'], 2, '.', ''), '0'), '.') ?></td>
                        <td class="bl-num">₱<?= number_format((float) $it['unit_price'], 2) ?></td>
                        <td class="bl-num">₱<?= number_format((float) $it['line_total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <tr><td colspan="5" class="bl-empty-cell">No line items on this invoice.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <!-- PAYMENT HISTORY -->
    <section class="bl-panel bl-panel--plain">
        <h3 class="bl-section-title">Payment history</h3>
        <table class="bl-lines">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Received by</th>
                    <th class="bl-num">Amount</th>
                    <th class="bl-actions-col"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $p):
                    $method  = (string) ($p['payment_method'] ?? 'cash');
                    $pstatus = (string) ($p['payment_status'] ?? 'paid');
                ?>
                    <tr>
                        <td class="bl-c-ref"><span class="bl-ref"><?= esc($p['reference_number'] ?? '—') ?></span></td>
                        <td><?= esc(date('M j, Y · g:i A', strtotime($p['payment_date']))) ?></td>
                        <td><span class="bl-method bl-method--<?= esc($method, 'attr') ?>"><?= esc(ucfirst($method)) ?></span></td>
                        <td><?= esc($p['received_by'] ?? '—') ?></td>
                        <td class="bl-num">
                            <strong>₱<?= number_format((float) $p['amount_paid'], 2) ?></strong>
                            <?php if ($pstatus === 'refunded'): ?>
                                <span class="bl-status bl-status--refunded" style="margin-left:.4rem;">Refunded</span>
                            <?php endif; ?>
                        </td>
                        <td class="bl-c-actions">
                            <div class="bl-actions">
                                <a class="bl-icon-btn" href="<?= base_url($routeBase . '/receipt/' . $p['id']) ?>" target="_blank" title="Print receipt">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <?php if ($isAdminUser && $pstatus === 'paid'): ?>
                                    <button type="button" class="bl-icon-btn bl-icon-btn--danger" data-refund="<?= (int) $p['id'] ?>" title="Refund">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="6" class="bl-empty-cell">No payments recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <!-- RECORD PAYMENT -->
    <?php if ($canPay): ?>
    <section class="bl-panel bl-panel--plain" id="pay">
        <h3 class="bl-section-title">Record payment</h3>
        <form method="post" action="<?= base_url($routeBase . '/pay/' . $invoice['id']) ?>" class="bl-pay-form">
            <?= csrf_field() ?>
            <div class="bl-grid-3">
                <div>
                    <label class="bl-label" for="pay_amount">Amount <span class="bl-req">*</span></label>
                    <input type="number" class="bl-input" id="pay_amount" name="amount" step="0.01" min="0.01"
                           value="<?= esc(number_format($balance, 2, '.', ''), 'attr') ?>" required>
                </div>
                <div>
                    <label class="bl-label" for="pay_method">Method</label>
                    <select class="bl-input" id="pay_method" name="method">
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="paymaya">Maya</option>
                        <option value="card">Card</option>
                        <option value="insurance">HMO / Insurance</option>
                    </select>
                </div>
                <div>
                    <label class="bl-label" for="pay_ref">Reference # (e-wallet / card)</label>
                    <input type="text" class="bl-input" id="pay_ref" name="reference_no" placeholder="Optional">
                </div>
                <div id="cash_tendered_wrap">
                    <label class="bl-label" for="pay_tendered">Cash tendered</label>
                    <input type="number" class="bl-input" id="pay_tendered" name="cash_tendered" step="0.01" min="0"
                           placeholder="For change computation">
                </div>
                <div class="bl-span-2">
                    <label class="bl-label" for="pay_notes">Notes</label>
                    <input type="text" class="bl-input" id="pay_notes" name="notes" placeholder="Optional">
                </div>
            </div>
            <div class="bl-form-actions">
                <button type="submit" class="bl-btn bl-btn--primary"><i class="bi bi-check2-circle"></i> Record payment</button>
            </div>
        </form>
    </section>
    <?php endif; ?>

    <!-- ADMIN ACTIONS -->
    <?php if ($isAdminUser && !in_array($invoice['status'], ['void', 'refunded'], true)): ?>
        <section class="bl-panel bl-panel--plain">
            <h3 class="bl-section-title">Admin actions</h3>
            <form method="post" action="<?= base_url($routeBase . '/void/' . $invoice['id']) ?>" class="bl-void-form"
                  onsubmit="return confirm('Void this invoice? This is a bookkeeping action and cannot be undone.')">
                <?= csrf_field() ?>
                <div class="bl-grid-2">
                    <div>
                        <label class="bl-label" for="void_reason">Reason for voiding</label>
                        <input type="text" class="bl-input" id="void_reason" name="reason" required>
                    </div>
                </div>
                <div class="bl-form-actions">
                    <button type="submit" class="bl-btn bl-btn--danger"><i class="bi bi-slash-circle"></i> Void invoice</button>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <?php if ($invoice['status'] === 'void' && !empty($invoice['void_reason'])): ?>
        <section class="bl-panel bl-panel--plain">
            <h3 class="bl-section-title">Void record</h3>
            <p class="bl-note">
                Voided by <strong><?= esc($invoice['voided_by'] ?? '—') ?></strong>
                on <?= esc(date('M j, Y · g:i A', strtotime($invoice['voided_at'] ?? $invoice['updated_at']))) ?>.<br>
                Reason: <em><?= esc($invoice['void_reason']) ?></em>
            </p>
        </section>
    <?php endif; ?>
</div>

<style>
.bl-ref--lg { font-size:1rem; padding:.35rem .7rem; }
.bl-summary { display:grid; grid-template-columns:1fr auto; gap:1rem;
              background:#fff; border:1px solid var(--bl-line); border-radius:12px;
              padding:1rem 1.1rem; margin-bottom:1rem; }
.bl-summary-col--right { text-align:right; }
.bl-summary-title { margin:0 0 .35rem; font-size:.72rem; font-weight:600;
                    letter-spacing:.06em; text-transform:uppercase; color:var(--bl-muted); }
.bl-summary-name { font-size:1.05rem; font-weight:650; color:var(--bl-ink); margin-bottom:.25rem; }
.bl-summary-sub  { font-size:.78rem; color:var(--bl-muted); }
.bl-summary-status { margin-bottom:.6rem; }
.bl-summary-totals { display:flex; flex-direction:column; gap:.35rem;
                     min-width:260px; font-variant-numeric:tabular-nums; }
.bl-summary-row { display:flex; justify-content:space-between; gap:1rem; font-size:.8125rem; }
.bl-summary-row--grand { border-top:2px solid var(--bl-ink); margin-top:.35rem; padding-top:.55rem;
                         font-weight:700; color:var(--bl-ink); font-size:.95rem; }
.bl-summary-row--due { color:var(--bl-warn); font-weight:600; }

.bl-panel--plain { padding:1rem 1.1rem; margin-bottom:1rem; }
.bl-section-title { margin:0 0 .75rem; font-size:.85rem; font-weight:650; color:var(--bl-ink); }

.bl-lines { width:100%; border-collapse:collapse; font-size:.8125rem; }
.bl-lines th { padding:.55rem .75rem; text-align:left; font-size:.72rem; font-weight:600;
               color:var(--bl-muted); background:var(--bl-subtle);
               border-bottom:1px solid var(--bl-line); }
.bl-lines td { padding:.65rem .75rem; border-bottom:1px solid var(--bl-line-soft); }
.bl-lines tbody tr:last-child td { border-bottom:0; }
.bl-lines .bl-num { text-align:right; font-variant-numeric:tabular-nums; }
.bl-lines .bl-actions-col { width:1%; }
.bl-empty-cell { padding:1.4rem; text-align:center; color:var(--bl-muted); font-style:italic; }

.bl-method { display:inline-flex; padding:.15rem .5rem; font-size:.72rem; font-weight:600;
             border-radius:6px; background:var(--bl-line-soft); color:var(--bl-text); }
.bl-method--cash { background:var(--bl-green-soft); color:var(--bl-green); }
.bl-method--gcash { background:#f3e8ff; color:#6d28d9; }
.bl-method--paymaya { background:#fff4e5; color:var(--bl-warn); }
.bl-method--card { background:var(--bl-blue-soft); color:var(--bl-blue); }
.bl-method--insurance { background:var(--bl-accent-soft); color:var(--bl-accent-dark); }

.bl-icon-btn--danger:hover { background:var(--bl-danger-soft); color:var(--bl-danger); border-color:#fecaca; }

.bl-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
.bl-grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:.75rem; }
.bl-span-2 { grid-column:span 2; }
.bl-label { display:block; margin:.4rem 0 .3rem; font-size:.78rem; font-weight:600; color:var(--bl-text); }
.bl-req { color:var(--bl-danger); }
.bl-form-actions { display:flex; gap:.5rem; justify-content:flex-end; margin-top:.9rem; }
.bl-note { margin:0; padding:.75rem .9rem; background:var(--bl-subtle); border:1px solid var(--bl-line);
           border-radius:8px; font-size:.8125rem; line-height:1.55; }

.bl-btn--danger, .bl-btn--danger:hover { color:#fff; }
.bl-btn--danger { background:var(--bl-danger); border-color:var(--bl-danger); }
.bl-btn--danger:hover { background:#991b1b; border-color:#991b1b; }

#cash_tendered_wrap { display:none; }
#cash_tendered_wrap.is-visible { display:block; }

@media (max-width:840px) {
    .bl-summary { grid-template-columns:1fr; }
    .bl-summary-col--right { text-align:left; }
    .bl-summary-totals { min-width:0; }
    .bl-grid-3 { grid-template-columns:1fr; }
    .bl-span-2 { grid-column:auto; }
}
</style>

<script>
(function () {
    'use strict';

    var methodSel = document.getElementById('pay_method');
    var cashWrap  = document.getElementById('cash_tendered_wrap');
    function syncMethod() {
        if (!methodSel || !cashWrap) return;
        cashWrap.classList.toggle('is-visible', methodSel.value === 'cash');
    }
    if (methodSel) {
        methodSel.addEventListener('change', syncMethod);
        syncMethod();
    }

    document.querySelectorAll('[data-refund]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var reason = prompt('Reason for refunding this payment:');
            if (reason === null) return;
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = <?= json_encode(base_url($routeBase . '/refund/')) ?> + btn.dataset.refund;
            var csrfName = <?= json_encode(csrf_token()) ?>;
            var csrfHash = <?= json_encode(csrf_hash()) ?>;
            var input = document.createElement('input');
            input.type = 'hidden'; input.name = csrfName; input.value = csrfHash;
            form.appendChild(input);
            var r = document.createElement('input');
            r.type = 'hidden'; r.name = 'reason'; r.value = reason;
            form.appendChild(r);
            document.body.appendChild(form);
            form.submit();
        });
    });
})();
</script>

<?= $this->endSection() ?>