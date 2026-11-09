<?= $this->extend($layout) ?>
<?= $this->section('pageTitle') ?>Invoice<?= $this->endSection() ?>
<?= $this->section($section) ?>

<?php
/** @var array $invoice @var array $items @var array $payments @var array|null $request */
/** @var string $routeBase @var bool $isAdminUser */

/* ------------------------------------------------------------------
   Invoice page: the invoice on the left (who, what, totals, payments),
   what to do about it on the right (balance, record payment, admin).
   Self-contained styles, scoped to .inv.
   ------------------------------------------------------------------ */

$statusMeta = [
    'draft'    => ['label' => 'Draft',    'tone' => 'draft'],
    'unpaid'   => ['label' => 'Unpaid',   'tone' => 'unpaid'],
    'partial'  => ['label' => 'Partly paid', 'tone' => 'partial'],
    'paid'     => ['label' => 'Paid',     'tone' => 'paid'],
    'void'     => ['label' => 'Void',     'tone' => 'void'],
    'refunded' => ['label' => 'Refunded', 'tone' => 'refunded'],
];
$meta    = $statusMeta[$invoice['status']] ?? ['label' => ucfirst((string) $invoice['status']), 'tone' => 'draft'];
$total   = (float) $invoice['total'];
$paid    = (float) $invoice['amount_paid'];
$balance = (float) $invoice['balance'];
$isClosed = in_array($invoice['status'], ['void', 'refunded'], true);
$canPay  = $balance > 0 && !$isClosed;
$paidPct = $total > 0 ? min(100, $paid / $total * 100) : 0;

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

$peso = static fn (float $v): string => '₱' . number_format($v, 2);

/* Line item department, from the stored category */
$kindOf = static function (?string $category): array {
    $c = strtolower(trim((string) $category));
    if (in_array($c, ['xray', 'x-ray'], true))     { return ['xray', 'X-ray']; }
    if (in_array($c, ['laboratory', 'lab'], true)) { return ['lab', 'Lab']; }
    return ['other', $c !== '' ? ucfirst($c) : 'Other'];
};

$methodName = ['cash' => 'Cash', 'gcash' => 'GCash', 'paymaya' => 'Maya', 'card' => 'Card', 'insurance' => 'HMO / Insurance'];
$requestStatus = is_array($request) ? (string) ($request['status'] ?? '') : '';
?>

<div class="inv">

    <a class="inv-back" href="<?= base_url($routeBase) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Billing</a>

    <!-- ===== HEADER ===== -->
    <header class="inv-head">
        <div class="inv-head-main">
            <h1 class="inv-no"><?= esc($invoice['invoice_no']) ?></h1>
            <span class="inv-status inv-status--<?= esc($meta['tone'], 'attr') ?>"><?= esc($meta['label']) ?></span>
        </div>
        <p class="inv-head-meta">
            Created <?= esc(date('M j, Y · g:i A', strtotime($invoice['created_at']))) ?>
            <?php if (!empty($invoice['created_by'])): ?> by <?= esc($invoice['created_by']) ?><?php endif; ?>
        </p>
    </header>

    <div class="inv-grid">

        <!-- ================= THE INVOICE ================= -->
        <div class="inv-main">

            <section class="inv-card" aria-labelledby="invBilledTitle">
                <div class="inv-billed">
                    <div>
                        <h2 class="inv-label" id="invBilledTitle">Billed to</h2>
                        <p class="inv-patient"><?= esc($invoice['patient_name']) ?></p>
                        <?php if (!empty($invoice['patient_code'])): ?>
                            <p class="inv-sub"><span class="inv-mono"><?= esc($invoice['patient_code']) ?></span></p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($invoice['request_id']) || !empty($invoice['appointment_id'])): ?>
                        <dl class="inv-links">
                            <?php if (!empty($invoice['request_id'])): ?>
                                <div>
                                    <dt>Diagnostic request</dt>
                                    <dd>
                                        #<?= (int) $invoice['request_id'] ?> · <?= esc($invoice['request_type'] === 'xray' ? 'X-ray' : 'Laboratory') ?>
                                        <?php if ($requestStatus !== ''): ?>
                                            <span class="inv-chip"><?= esc(ucwords(str_replace('_', ' ', $requestStatus))) ?></span>
                                        <?php endif; ?>
                                    </dd>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($invoice['appointment_id'])): ?>
                                <div>
                                    <dt>Appointment</dt>
                                    <dd><a class="inv-link" href="<?= base_url(($isAdminUser ? 'admin' : 'receptionist') . '/appointment/view/' . (int) $invoice['appointment_id']) ?>">#<?= (int) $invoice['appointment_id'] ?></a></dd>
                                </div>
                            <?php endif; ?>
                        </dl>
                    <?php endif; ?>
                </div>

                <!-- Line items -->
                <table class="inv-table">
                    <caption class="visually-hidden">Line items</caption>
                    <thead>
                        <tr>
                            <th scope="col">Service</th>
                            <th scope="col" class="num">Qty</th>
                            <th scope="col" class="num">Unit price</th>
                            <th scope="col" class="num">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it):
                            [$kind, $kindLabel] = $kindOf($it['category'] ?? null); ?>
                            <tr>
                                <td>
                                    <span class="inv-item"><?= esc($it['service_name']) ?></span>
                                    <span class="inv-kind inv-kind--<?= $kind ?>"><?= esc($kindLabel) ?></span>
                                </td>
                                <td class="num"><?= rtrim(rtrim(number_format((float) $it['qty'], 2, '.', ''), '0'), '.') ?></td>
                                <td class="num"><?= esc($peso((float) $it['unit_price'])) ?></td>
                                <td class="num inv-strong"><?= esc($peso((float) $it['line_total'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="4" class="inv-none">No line items on this invoice.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Totals -->
                <dl class="inv-totals">
                    <div><dt>Subtotal</dt><dd><?= esc($peso((float) $invoice['subtotal'])) ?></dd></div>
                    <?php if ($discountAmount > 0): ?>
                        <div>
                            <dt>
                                Discount<?= $invoice['discount_type'] === 'percent' ? ' (' . (float) $invoice['discount_value'] . '%)' : '' ?>
                                <?php if (!empty($invoice['discount_reason'])): ?><small><?= esc($invoice['discount_reason']) ?></small><?php endif; ?>
                            </dt>
                            <dd>−<?= esc($peso($discountAmount)) ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ((float) $invoice['tax'] > 0): ?>
                        <div><dt>Tax</dt><dd><?= esc($peso((float) $invoice['tax'])) ?></dd></div>
                    <?php endif; ?>
                    <div class="inv-totals-grand"><dt>Total</dt><dd><?= esc($peso($total)) ?></dd></div>
                    <div><dt>Paid</dt><dd><?= esc($peso($paid)) ?></dd></div>
                    <div class="inv-totals-due<?= $balance > 0 && !$isClosed ? ' is-due' : '' ?>"><dt>Balance due</dt><dd><?= esc($peso($balance)) ?></dd></div>
                </dl>

                <?php if (!empty($invoice['notes'])): ?>
                    <p class="inv-notes"><b>Notes</b> <?= esc($invoice['notes']) ?></p>
                <?php endif; ?>
            </section>

            <!-- Payment history -->
            <section class="inv-card" aria-labelledby="invPayTitle">
                <header class="inv-card-head">
                    <h2 id="invPayTitle">Payments</h2>
                    <span class="inv-count"><?= count($payments) ?></span>
                </header>

                <?php if ($payments): ?>
                    <ul class="inv-payments">
                        <?php foreach ($payments as $p):
                            $method  = (string) ($p['payment_method'] ?? 'cash');
                            $pstatus = (string) ($p['payment_status'] ?? 'paid');
                        ?>
                            <li class="inv-pay<?= $pstatus === 'refunded' ? ' is-refunded' : '' ?>">
                                <div class="inv-pay-main">
                                    <span class="inv-pay-amount"><?= esc($peso((float) $p['amount_paid'])) ?></span>
                                    <span class="inv-method"><?= esc($methodName[$method] ?? ucfirst($method)) ?></span>
                                    <?php if ($pstatus === 'refunded'): ?><span class="inv-status inv-status--refunded">Refunded</span><?php endif; ?>
                                </div>
                                <p class="inv-pay-meta">
                                    <?= esc(date('M j, Y · g:i A', strtotime($p['payment_date']))) ?>
                                    · <?= esc($p['received_by'] ?? '—') ?>
                                    · <span class="inv-mono"><?= esc($p['reference_number'] ?? '—') ?></span>
                                    <?php if (!empty($p['reference_no']) && $p['reference_no'] !== ($p['reference_number'] ?? '')): ?>
                                        · ref <span class="inv-mono"><?= esc($p['reference_no']) ?></span>
                                    <?php endif; ?>
                                    <?php if ((float) ($p['change_amount'] ?? 0) > 0): ?>
                                        · change <?= esc($peso((float) $p['change_amount'])) ?>
                                    <?php endif; ?>
                                </p>
                                <div class="inv-pay-actions">
                                    <a class="inv-btn inv-btn--sm" href="<?= base_url($routeBase . '/receipt/' . $p['id']) ?>" target="_blank" rel="noopener">
                                        <i class="bi bi-printer" aria-hidden="true"></i>Receipt
                                    </a>
                                    <?php if ($isAdminUser && $pstatus === 'paid'): ?>
                                        <button type="button" class="inv-btn inv-btn--sm inv-btn--quiet-danger" data-refund="<?= (int) $p['id'] ?>">Refund</button>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="inv-empty">No payments recorded yet.</p>
                <?php endif; ?>
            </section>
        </div>

        <!-- ================= ACTIONS ================= -->
        <aside class="inv-side">

            <section class="inv-card inv-balance" aria-label="Balance">
                <p class="inv-label"><?= $isClosed ? 'Status' : ($balance > 0 ? 'Balance due' : 'Settled') ?></p>
                <p class="inv-balance-value<?= $balance > 0 && !$isClosed ? ' is-due' : '' ?>"><?= $isClosed ? esc($meta['label']) : esc($peso($balance)) ?></p>
                <div class="inv-progress" role="progressbar" aria-label="Paid so far" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= round($paidPct) ?>">
                    <span style="width: <?= round($paidPct, 1) ?>%"></span>
                </div>
                <p class="inv-sub"><?= esc($peso($paid)) ?> paid of <?= esc($peso($total)) ?></p>
            </section>

            <?php if ($canPay): ?>
                <section class="inv-card" id="pay" aria-labelledby="invRecordTitle">
                    <header class="inv-card-head"><h2 id="invRecordTitle">Record payment</h2></header>
                    <form method="post" action="<?= base_url($routeBase . '/pay/' . $invoice['id']) ?>" class="inv-form" id="invPayForm">
                        <?= csrf_field() ?>
                        <label class="inv-field">
                            <span>Amount <b aria-hidden="true">*</b></span>
                            <span class="inv-money"><i aria-hidden="true">₱</i>
                                <input type="number" id="pay_amount" name="amount" step="0.01" min="0.01" max="<?= esc(number_format($balance, 2, '.', ''), 'attr') ?>"
                                       value="<?= esc(number_format($balance, 2, '.', ''), 'attr') ?>" required>
                            </span>
                        </label>
                        <label class="inv-field">
                            <span>Method</span>
                            <select id="pay_method" name="method">
                                <?php foreach ($methodName as $val => $label): ?>
                                    <option value="<?= $val ?>"><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="inv-field" id="cash_tendered_wrap">
                            <span>Cash tendered</span>
                            <span class="inv-money"><i aria-hidden="true">₱</i>
                                <input type="number" id="pay_tendered" name="cash_tendered" step="0.01" min="0" placeholder="0.00">
                            </span>
                            <small class="inv-change" id="payChange" aria-live="polite"></small>
                            <button type="button" class="inv-partial" id="payPartial" hidden></button>
                        </label>
                        <label class="inv-field" id="pay_ref_wrap">
                            <span>Reference no.</span>
                            <input type="text" id="pay_ref" name="reference_no" placeholder="From the e-wallet or card slip">
                        </label>
                        <label class="inv-field">
                            <span>Notes</span>
                            <input type="text" id="pay_notes" name="notes" placeholder="Optional">
                        </label>
                        <button type="submit" class="inv-btn inv-btn--primary inv-btn--block" id="paySubmit">
                            <i class="bi bi-check2" aria-hidden="true"></i>Record payment
                        </button>
                    </form>
                </section>
            <?php endif; ?>

            <?php if ($canSync): ?>
                <section class="inv-card" aria-labelledby="invSyncTitle">
                    <header class="inv-card-head"><h2 id="invSyncTitle">Diagnostic request</h2></header>
                    <div class="inv-card-body">
                        <p class="inv-text">
                            Request <b>#<?= (int) $invoice['request_id'] ?></b> is still pending. Add this invoice's
                            <?= count($items) ?> service<?= count($items) === 1 ? '' : 's' ?> to it; nothing already on the request is removed.
                        </p>
                        <form method="post" action="<?= base_url($routeBase . '/sync-request/' . $invoice['id']) ?>"
                              onsubmit="return confirm('Sync the invoice services back to the diagnostic request?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="inv-btn inv-btn--block"><i class="bi bi-arrow-repeat" aria-hidden="true"></i>Sync to request</button>
                        </form>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($invoice['status'] === 'void' && !empty($invoice['void_reason'])): ?>
                <section class="inv-card" aria-labelledby="invVoidRecTitle">
                    <header class="inv-card-head"><h2 id="invVoidRecTitle">Void record</h2></header>
                    <dl class="inv-kv">
                        <div><dt>Voided by</dt><dd><?= esc($invoice['voided_by'] ?? '—') ?></dd></div>
                        <div><dt>When</dt><dd><?= esc(date('M j, Y · g:i A', strtotime($invoice['voided_at'] ?? $invoice['updated_at']))) ?></dd></div>
                        <div><dt>Reason</dt><dd><?= esc($invoice['void_reason']) ?></dd></div>
                    </dl>
                </section>
            <?php endif; ?>

            <?php if ($isAdminUser && !$isClosed): ?>
                <details class="inv-card inv-danger">
                    <summary>Void this invoice</summary>
                    <form method="post" action="<?= base_url($routeBase . '/void/' . $invoice['id']) ?>" class="inv-form"
                          onsubmit="return confirm('Void this invoice? This is a bookkeeping action and cannot be undone.')">
                        <?= csrf_field() ?>
                        <p class="inv-text">
                            <?= $paid > 0
                                ? 'This invoice holds ' . esc($peso($paid)) . '. Refund the payments first, then void it.'
                                : 'A void keeps the invoice on record but takes it out of the totals.' ?>
                        </p>
                        <label class="inv-field">
                            <span>Reason <b aria-hidden="true">*</b></span>
                            <input type="text" id="void_reason" name="reason" required>
                        </label>
                        <button type="submit" class="inv-btn inv-btn--danger inv-btn--block"<?= $paid > 0 ? ' disabled' : '' ?>>Void invoice</button>
                    </form>
                </details>
            <?php endif; ?>
        </aside>
    </div>
</div>

<style>
.inv {
    --inv-ink:    var(--pm-text, #10202E);
    --inv-text:   var(--pm-text-soft, #3B4C5E);
    --inv-muted:  var(--pm-text-muted);
    --inv-faint:  var(--pm-text-muted);
    --inv-line:   var(--pm-line, #DDE4EC);
    --inv-rule:   var(--pm-line);
    --inv-strong-line: var(--pm-line);
    --inv-subtle: var(--pm-subtle);
    --inv-accent: var(--pm-accent, #175563);
    --inv-accent-dark: var(--pm-accent-text);
    --inv-mono:   var(--pm-mono, ui-monospace, SFMono-Regular, Menlo, monospace);

    max-width: 1180px;
    margin: 0 auto;
    color: var(--inv-text);
    font-size: 0.875rem;
}

.inv *, .inv *::before, .inv *::after { box-sizing: border-box; }
.inv *:focus-visible { outline: 2px solid var(--inv-accent); outline-offset: 2px; border-radius: 4px; }
.inv .visually-hidden { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }
.inv .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.inv-mono { font-family: var(--inv-mono); font-size: 0.78rem; }
.inv-strong { font-weight: 600; color: var(--inv-ink); }
.inv dt { font-weight: 400; }

.inv-back {
    display: inline-flex; align-items: center; gap: 0.4rem; margin-bottom: 0.75rem;
    font-size: 0.8rem; font-weight: 500; color: var(--inv-muted); text-decoration: none;
}
.inv-back:hover { color: var(--inv-ink); }

.inv-alert {
    display: flex; align-items: center; gap: 0.5rem; margin: 0 0 1rem; padding: 0.6rem 0.85rem;
    font-size: 0.82rem; border: 1px solid; border-radius: 6px;
}
.inv-alert--ok  { color: var(--pm-dk-fg-green, #067647); background: var(--pm-dk-bg-green, #ECFDF3); border-color: var(--pm-dk-edge-green, #ABEFC6); }
.inv-alert--err { color: var(--pm-dk-fg-red, #B42318); background: var(--pm-dk-bg-red, #FEF3F2); border-color: var(--pm-dk-edge-red, #FECDCA); }

/* ---------- Header ---------- */
.inv-head { margin-bottom: 1rem; }
.inv-head-main { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
.inv-no {
    margin: 0; font-family: var(--inv-mono); font-size: 1.35rem; font-weight: 600;
    letter-spacing: -0.01em; color: var(--inv-ink);
}
.inv-head-meta { margin: 0.3rem 0 0; font-size: 0.8125rem; color: var(--inv-muted); }

.inv-status {
    display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.2rem 0.55rem;
    font-size: 0.74rem; font-weight: 600; white-space: nowrap; color: var(--fg); background: var(--bg); border-radius: 4px;
}
.inv-status::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--dot); }
.inv-status--unpaid   { --bg: #FEF6E7; --fg: #93370D; --dot: #DC6803; }
.inv-status--partial  { --bg: #EBF2F4; --fg: #10404B; --dot: #175563; }
.inv-status--paid     { --bg: #ECFDF3; --fg: #067647; --dot: #17B26A; }
.inv-status--draft,
.inv-status--void     { --bg: #F2F4F7; --fg: #475467; --dot: #98A2B3; }
.inv-status--refunded { --bg: #FEF3F2; --fg: #B42318; --dot: #F04438; }

/* ---------- Layout ---------- */
.inv-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 1rem; align-items: start; }
.inv-main, .inv-side { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }

.inv-card { margin: 0; background: var(--pm-surface); border: 1px solid var(--inv-line); border-radius: 8px; overflow: hidden; }
.inv-card-head {
    display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
    padding: 0.85rem 1.15rem; border-bottom: 1px solid var(--inv-rule);
}
.inv-card-head h2 { margin: 0; font-size: 0.95rem; font-weight: 600; color: var(--inv-ink); }
.inv-card-body { padding: 0.9rem 1.15rem 1.05rem; }
.inv-count {
    min-width: 1.4rem; padding: 0 0.35rem; font-size: 0.72rem; font-weight: 600; line-height: 1.3rem;
    text-align: center; color: var(--inv-text); background: var(--inv-rule); border-radius: 4px;
}

.inv-label { margin: 0; font-size: 0.75rem; font-weight: 500; color: var(--inv-muted); }
.inv-sub   { margin: 0.2rem 0 0; font-size: 0.8rem; color: var(--inv-muted); }
.inv-text  { margin: 0 0 0.85rem; font-size: 0.82rem; line-height: 1.55; color: var(--inv-text); }
.inv-text b { color: var(--inv-ink); }
.inv-none, .inv-empty { padding: 1.2rem 1.15rem; margin: 0; font-size: 0.82rem; color: var(--inv-faint); }
.inv-none { text-align: center; }
.inv-link { color: var(--inv-accent); font-weight: 500; text-decoration: none; }
.inv-link:hover { text-decoration: underline; text-underline-offset: 2px; }

/* ---------- Billed to ---------- */
.inv-billed {
    display: flex; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap;
    padding: 1.05rem 1.15rem; border-bottom: 1px solid var(--inv-rule);
}
.inv-patient { margin: 0.25rem 0 0; font-size: 1.1rem; font-weight: 600; color: var(--inv-ink); }
.inv-links { display: flex; gap: 1.75rem; margin: 0; flex-wrap: wrap; }
.inv-links dt { font-size: 0.75rem; font-weight: 500; color: var(--inv-muted); }
.inv-links dd { margin: 0.25rem 0 0; font-size: 0.85rem; color: var(--inv-ink); display: flex; align-items: center; gap: 0.4rem; }
.inv-chip { padding: 0.05rem 0.4rem; font-size: 0.7rem; font-weight: 600; color: var(--inv-text); background: var(--inv-rule); border-radius: 4px; }

/* ---------- Line items ---------- */
.inv-table { width: 100%; border-collapse: collapse; }
.inv-table th {
    padding: 0.6rem 1.15rem; font-size: 0.75rem; font-weight: 500; text-align: left; color: var(--inv-muted);
    border-bottom: 1px solid var(--inv-line);
}
.inv-table th.num { text-align: right; }
.inv-table td { padding: 0.7rem 1.15rem; font-size: 0.85rem; color: var(--inv-text); border-bottom: 1px solid var(--inv-rule); vertical-align: middle; }
.inv-item { font-weight: 500; color: var(--inv-ink); }

.inv-kind {
    display: inline-flex; align-items: center; gap: 0.3rem; margin-left: 0.45rem; padding: 0.05rem 0.4rem;
    font-size: 0.68rem; font-weight: 600; border-radius: 4px; vertical-align: 1px;
}
.inv-kind::before { content: ""; width: 6px; height: 6px; border-radius: 2px; background: currentColor; }
.inv-kind--lab   { color: var(--pm-dk-fg-teal, #10404B); background: var(--pm-dk-bg-teal, #EBF2F4); }
.inv-kind--xray  { color: var(--pm-dk-fg-teal, #0A6E65); background: var(--pm-dk-bg-teal, #E3F4F1); }
.inv-kind--other { color: var(--pm-text-soft); background: var(--pm-subtle); }

/* ---------- Totals ---------- */
.inv-totals { margin: 0 0 0 auto; padding: 0.85rem 1.15rem 1rem; width: min(360px, 100%); }
.inv-totals > div { display: flex; justify-content: space-between; gap: 1rem; padding: 0.25rem 0; font-size: 0.85rem; }
.inv-totals dt { color: var(--inv-muted); }
.inv-totals dt small { display: block; font-size: 0.72rem; color: var(--inv-faint); }
.inv-totals dd { margin: 0; color: var(--inv-ink); font-variant-numeric: tabular-nums; }
.inv-totals-grand { margin-top: 0.35rem; padding-top: 0.6rem !important; border-top: 1px solid var(--inv-strong-line); }
.inv-totals-grand dt, .inv-totals-grand dd { font-size: 1rem; font-weight: 600; color: var(--inv-ink); }
.inv-totals-due dt { color: var(--inv-ink); font-weight: 500; }
.inv-totals-due dd { font-weight: 600; }
.inv-totals-due.is-due dd { color: var(--pm-dk-fg-amber, #B54708); }

.inv-notes { margin: 0; padding: 0.75rem 1.15rem; font-size: 0.82rem; color: var(--inv-text); border-top: 1px solid var(--inv-rule); background: var(--inv-subtle); }
.inv-notes b { margin-right: 0.35rem; color: var(--inv-ink); }

/* ---------- Payments ---------- */
.inv-payments { margin: 0; padding: 0; list-style: none; }
.inv-pay {
    display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 0.25rem 1rem;
    padding: 0.8rem 1.15rem; border-top: 1px solid var(--inv-rule);
}
.inv-pay:first-child { border-top: 0; }
.inv-pay-main { display: flex; align-items: center; gap: 0.55rem; flex-wrap: wrap; }
.inv-pay-amount { font-size: 0.95rem; font-weight: 600; color: var(--inv-ink); font-variant-numeric: tabular-nums; }
.inv-pay.is-refunded .inv-pay-amount { color: var(--inv-faint); text-decoration: line-through; }
.inv-method { padding: 0.05rem 0.45rem; font-size: 0.72rem; font-weight: 600; color: var(--inv-text); background: var(--inv-rule); border-radius: 4px; }
.inv-pay-meta { grid-column: 1; margin: 0; font-size: 0.76rem; color: var(--inv-muted); }
.inv-pay-actions { grid-column: 2; grid-row: 1 / span 2; display: flex; gap: 0.35rem; }

/* ---------- Balance ---------- */
.inv-balance { padding: 1rem 1.15rem 1.05rem; }
.inv-balance-value { margin: 0.25rem 0 0.75rem; font-size: 1.75rem; font-weight: 600; line-height: 1.1; letter-spacing: -0.02em; color: var(--inv-ink); font-variant-numeric: tabular-nums; }
.inv-balance-value.is-due { color: var(--pm-dk-fg-amber, #B54708); }
.inv-progress { height: 6px; border-radius: 3px; background: var(--inv-rule); overflow: hidden; }
.inv-progress span { display: block; height: 100%; border-radius: 3px; background: #17B26A; }

/* ---------- Forms ---------- */
.inv-form { display: flex; flex-direction: column; gap: 0.75rem; padding: 0.9rem 1.15rem 1.1rem; }
.inv-danger .inv-form { padding-top: 0.25rem; }
.inv-field { display: flex; flex-direction: column; gap: 0.3rem; margin: 0; }
.inv-field > span:first-child { font-size: 0.78rem; font-weight: 500; color: var(--inv-ink); }
.inv-field b { color: var(--pm-dk-fg-red, #B42318); font-weight: 600; }
.inv-field input, .inv-field select {
    width: 100%; height: 36px; padding: 0 0.7rem; font: inherit; font-size: 0.85rem; color: var(--inv-ink);
    background: var(--pm-surface); border: 1px solid var(--inv-strong-line); border-radius: 6px;
}
.inv-field input::placeholder { color: #8496A8; }
.inv-field input:focus, .inv-field select:focus { outline: none; border-color: var(--inv-accent); box-shadow: 0 0 0 3px rgba(23, 85, 99, 0.14); }
.inv-field[hidden] { display: none; }
.inv-money { position: relative; display: block; }
.inv-money i { position: absolute; left: 0.7rem; top: 50%; transform: translateY(-50%); font-style: normal; font-size: 0.85rem; color: var(--inv-muted); }
.inv-money input { padding-left: 1.6rem; font-variant-numeric: tabular-nums; }
.inv-change:empty { display: none; }
.inv-change { font-size: 0.78rem; font-weight: 600; color: var(--pm-dk-fg-green, #067647); }
.inv-change.is-short { color: var(--pm-dk-fg-red, #B42318); }
.inv-partial {
    align-self: flex-start;
    margin-top: 0.35rem;
    padding: 0.3rem 0.7rem;
    border: 1px solid var(--pm-accent-line);
    border-radius: 999px;
    background: var(--pm-accent-soft);
    color: var(--pm-accent-text);
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
}
.inv-partial:hover { background: var(--pm-surface); }
#paySubmit:disabled { opacity: 0.5; cursor: not-allowed; }

/* ---------- Buttons ---------- */
.inv-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; height: 36px; padding: 0 0.9rem;
    font: inherit; font-size: 0.82rem; font-weight: 600; color: var(--inv-ink); background: var(--pm-surface);
    border: 1px solid var(--inv-line); border-radius: 6px; text-decoration: none; white-space: nowrap; cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}
.inv-btn:hover { color: var(--inv-ink); background: var(--inv-subtle); border-color: var(--inv-strong-line); }
.inv-btn--sm { height: 30px; padding: 0 0.65rem; font-size: 0.78rem; }
.inv-btn--block { width: 100%; }
.inv-btn--primary { color: #FFFFFF; background: var(--inv-accent); border-color: var(--inv-accent); }
.inv-btn--primary:hover { color: #FFFFFF; background: var(--inv-accent-dark); border-color: var(--inv-accent-dark); }
.inv-btn--danger { color: #FFFFFF; background: #B42318; border-color: #B42318; }
.inv-btn--danger:hover { color: #FFFFFF; background: #912018; border-color: #912018; }
.inv-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.inv-btn--quiet-danger { color: var(--pm-dk-fg-red, #B42318); border-color: transparent; background: transparent; }
.inv-btn--quiet-danger:hover { color: var(--pm-dk-fg-red, #B42318); background: var(--pm-dk-bg-red, #FEF3F2); border-color: transparent; }

/* ---------- Danger zone ---------- */
.inv-danger summary {
    display: flex; align-items: center; justify-content: space-between; padding: 0.8rem 1.15rem;
    font-size: 0.82rem; font-weight: 600; color: var(--pm-dk-fg-red, #B42318); cursor: pointer; list-style: none;
}
.inv-danger summary::-webkit-details-marker { display: none; }
.inv-danger summary::after { content: "\F282"; font-family: "bootstrap-icons"; font-size: 0.75rem; transition: transform 0.15s ease; }
.inv-danger[open] summary::after { transform: rotate(180deg); }

.inv-kv { margin: 0; padding: 0.75rem 1.15rem 0.95rem; }
.inv-kv > div { display: flex; justify-content: space-between; gap: 1rem; padding: 0.25rem 0; font-size: 0.82rem; }
.inv-kv dt { color: var(--inv-muted); }
.inv-kv dd { margin: 0; color: var(--inv-ink); text-align: right; }

/* ---------- Responsive ---------- */
@media (max-width: 1000px) {
    .inv-grid { grid-template-columns: minmax(0, 1fr); }
    .inv-side { order: -1; }
}
@media (max-width: 560px) {
    .inv-table th, .inv-table td { padding-left: 0.85rem; padding-right: 0.85rem; }
    .inv-table th:nth-child(3), .inv-table td:nth-child(3) { display: none; }
    .inv-pay { grid-template-columns: minmax(0, 1fr); }
    .inv-pay-actions { grid-column: 1; grid-row: auto; }
}
</style>

<script>
(function () {
    'use strict';

    var methodSel = document.getElementById('pay_method');
    var cashWrap  = document.getElementById('cash_tendered_wrap');
    var refWrap   = document.getElementById('pay_ref_wrap');
    var amount    = document.getElementById('pay_amount');
    var tendered  = document.getElementById('pay_tendered');
    var change    = document.getElementById('payChange');

    /* Cash shows "tendered" and the change; other methods show the reference no. */
    function syncMethod() {
        if (!methodSel) return;
        var isCash = methodSel.value === 'cash';
        if (cashWrap) cashWrap.hidden = !isCash;
        if (refWrap)  refWrap.hidden  = isCash;
        syncChange();
    }

    var submit  = document.getElementById('paySubmit');
    var partial = document.getElementById('payPartial');
    var money   = function (v) { return '₱' + v.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

    // Cash short of the amount: the payment can't be recorded as is.
    // Either collect the rest, or record what was handed over as a
    // partial payment (the invoice then stays Partial with a balance).
    function syncChange() {
        if (!change || !amount || !tendered) return;
        var isCash = !methodSel || methodSel.value === 'cash';
        var a = parseFloat(amount.value) || 0, t = parseFloat(tendered.value) || 0;
        var short = isCash && t > 0 && t + 0.005 < a;

        change.classList.toggle('is-short', short);
        if (submit)  { submit.disabled = short; }
        if (partial) {
            partial.hidden = !short;
            if (short) { partial.textContent = 'Record ' + money(t) + ' as partial payment'; }
        }

        if (!isCash || !t) { change.textContent = ''; return; }
        change.textContent = short
            ? 'Short by ' + money(a - t) + ' — collect the rest or record a partial payment'
            : 'Change ' + money(t - a);
    }

    if (partial) {
        partial.addEventListener('click', function () {
            amount.value = (parseFloat(tendered.value) || 0).toFixed(2);
            syncChange();
            amount.focus();
        });
    }

    if (methodSel) { methodSel.addEventListener('change', syncMethod); syncMethod(); }
    if (amount)    { amount.addEventListener('input', syncChange); }
    if (tendered)  { tendered.addEventListener('input', syncChange); }

    // Refund: the shared refund window (reason + confirmation box)
    document.querySelectorAll('[data-refund]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (window.pmRefund) { window.pmRefund.refund(Number(btn.dataset.refund)); }
        });
    });
})();
</script>

<?php if ($isAdminUser): ?>
    <?php
    // Payment data for the refund window (same shape as the Payments page)
    $refundData = [];
    foreach ($payments as $p) {
        $refundData[(int) $p['id']] = [
            'id'                  => (int) $p['id'],
            'ref'                 => (string) ($p['reference_number'] ?? ''),
            'name'                => (string) ($p['patient_name'] ?? ''),
            'paid'                => (float) ($p['amount_paid'] ?? 0),
            'date'                => (string) ($p['payment_date'] ?? ''),
            'status'              => (string) ($p['payment_status'] ?? ''),
            'refund_status'       => (string) ($p['refund_status'] ?? ''),
            'refund_reason'       => (string) ($p['refund_reason'] ?? ''),
            'refund_requested_by' => (string) ($p['refund_requested_by'] ?? ''),
            'refund_requested_at' => (string) ($p['refund_requested_at'] ?? ''),
        ];
    }
    ?>
    <script type="application/json" id="pmReceipts"><?= json_encode($refundData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?= view('partials/refund_ui', ['role' => 'admin']) ?>
<?php endif; ?>

<?= $this->endSection() ?>
