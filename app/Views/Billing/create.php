<?= $this->extend($layout) ?>
<?= $this->section('pageTitle') ?>New Invoice<?= $this->endSection() ?>
<?= $this->section($section) ?>

<?php
/** @var array $openRequests @var array $services @var string $routeBase */

// Normalise the services list into a stable shape:
//   [ ['service_name' => '...', 'category' => '...', 'charge' => 123.45], ... ]
$normalisedServices = [];
foreach (($services ?? []) as $svc) {
    if (is_array($svc)) {
        $normalisedServices[] = [
            'service_name' => (string) ($svc['service_name'] ?? $svc['name'] ?? ''),
            'category'     => (string) ($svc['category'] ?? ''),
            'charge'       => (float)  ($svc['charge'] ?? $svc['price'] ?? 0),
        ];
    } elseif (is_string($svc) && trim($svc) !== '') {
        $normalisedServices[] = [
            'service_name' => trim($svc),
            'category'     => '',
            'charge'       => 0.0,
        ];
    }
}

// Build a normalised price map for the JS auto-fill.
// Key = lowercase, slashes/backslashes stripped, whitespace collapsed.
$priceMap = [];
foreach ($normalisedServices as $svc) {
    $key = strtolower(preg_replace('/\s+/', ' ', str_replace(['\\/', '\\'], ['/', ''], $svc['service_name'])));
    $key = trim($key);
    if ($key !== '') {
        $priceMap[$key] = [
            'name'     => $svc['service_name'],
            'charge'   => $svc['charge'],
            'category' => $svc['category'],
        ];
    }
}

// Group the catalogue so the picker is scannable
$grouped = [];
foreach ($normalisedServices as $svc) {
    $grouped[$svc['category'] !== '' ? $svc['category'] : 'Other'][] = $svc;
}
ksort($grouped);
?>

<div class="bl bl-create">

    <header class="bl-head">
        <div>
            <h2 class="bl-title">New invoice</h2>
            <p class="bl-lede">Pick a patient, add line items, apply a discount if needed.</p>
        </div>
        <div class="bl-head-actions">
            <a class="bl-btn" href="<?= base_url($routeBase) ?>">
                <i class="bi bi-arrow-left"></i> Back to list
            </a>
        </div>
    </header>

    <form class="bl-form" method="post" action="<?= base_url($routeBase . '/store') ?>" id="invoiceForm" novalidate>
        <?= csrf_field() ?>

        <div class="bl-form-cols">

            <!-- =============== PATIENT =============== -->
            <section class="bl-card">
                <h3 class="bl-card-title"><i class="bi bi-person-vcard"></i> Patient</h3>

                <div class="bl-prefill">
                    <label class="bl-label" for="openRequestSelect">
                        Start from a diagnostic request
                        <span class="bl-hint-inline">optional</span>
                    </label>
                    <select class="bl-input" id="openRequestSelect">
                        <option value="">— Choose a request to auto-fill —</option>
                        <?php foreach (($openRequests ?? []) as $r):
                            if (!is_array($r)) { continue; }
                            $servicesStr = (string) ($r['services'] ?? '');
                            $svcList     = array_filter(array_map('trim', explode(',', $servicesStr)));
                        ?>
                            <option value="<?= (int) ($r['id'] ?? 0) ?>"
                                    data-type="<?= esc($r['type'] ?? '', 'attr') ?>"
                                    data-name="<?= esc($r['patient_name'] ?? '', 'attr') ?>"
                                    data-code="<?= esc($r['patient_code'] ?? '', 'attr') ?>"
                                    data-appt="<?= (int) ($r['appointment_id'] ?? 0) ?>"
                                    data-services="<?= esc(implode('|', $svcList), 'attr') ?>">
                                <?= esc((($r['type'] ?? '') === 'xray' ? 'X-Ray' : 'Lab')
                                    . ' · ' . ($r['patient_name'] ?? 'Unknown')
                                    . ' · ' . ($r['patient_code'] ?? '—')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="bl-field-hint">
                        Fills the patient and pulls every requested service in at catalogue price.
                    </p>
                </div>

                <input type="hidden" name="request_id"     id="f_request_id">
                <input type="hidden" name="request_type"   id="f_request_type">
                <input type="hidden" name="appointment_id" id="f_appointment_id">

                <!-- Shown once a request is chosen -->
                <div class="bl-linked" id="linkedBadge" hidden>
                    <i class="bi bi-link-45deg"></i>
                    <span id="linkedText">Linked to request</span>
                    <button type="button" id="unlinkBtn" aria-label="Unlink request">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="bl-grid-2" style="margin-top:.85rem;">
                    <div>
                        <label class="bl-label" for="f_patient_name">Patient name <span class="bl-req">*</span></label>
                        <input type="text" class="bl-input" id="f_patient_name" name="patient_name"
                               placeholder="Juan Dela Cruz" maxlength="120" autocomplete="off" required>
                        <p class="bl-field-error" id="nameError" role="alert"></p>
                    </div>
                    <div>
                        <label class="bl-label" for="f_patient_code">Patient code</label>
                        <input type="text" class="bl-input" id="f_patient_code" name="patient_code"
                               placeholder="PT-00000" maxlength="40" autocomplete="off">
                        <p class="bl-field-hint">Format <code>PT-00000</code>, if the patient has one.</p>
                    </div>
                </div>
            </section>

            <!-- =============== DISCOUNT =============== -->
            <section class="bl-card">
                <h3 class="bl-card-title"><i class="bi bi-percent"></i> Discount &amp; notes</h3>

                <div class="bl-grid-2">
                    <div>
                        <label class="bl-label" for="f_discount_type">Discount type</label>
                        <select class="bl-input" id="f_discount_type" name="discount_type">
                            <option value="none">None</option>
                            <option value="percent">Percent (%)</option>
                            <option value="fixed">Fixed (₱)</option>
                        </select>
                    </div>
                    <div>
                        <label class="bl-label" for="f_discount_value">Value</label>
                        <input type="number" class="bl-input" id="f_discount_value" name="discount_value"
                               step="0.01" min="0" value="0" inputmode="decimal" disabled>
                        <p class="bl-field-hint" id="discountHint">Choose a type first.</p>
                        <p class="bl-field-error" id="discountError" role="alert"></p>
                    </div>
                </div>

                <!-- Statutory shortcuts; both are 20% in PH -->
                <div class="bl-quick" style="margin-top:.5rem;">
                    <button type="button" class="bl-quick-btn" data-preset="20" data-reason="Senior citizen">Senior 20%</button>
                    <button type="button" class="bl-quick-btn" data-preset="20" data-reason="PWD">PWD 20%</button>
                    <button type="button" class="bl-quick-btn" data-preset="0"  data-reason="">Clear</button>
                </div>

                <label class="bl-label" style="margin-top:.85rem;" for="f_discount_reason">Discount reason</label>
                <input type="text" class="bl-input" id="f_discount_reason" name="discount_reason"
                       placeholder="e.g. Senior citizen, PWD, promo" maxlength="120">

                <label class="bl-label" style="margin-top:.85rem;" for="f_notes">Notes</label>
                <textarea class="bl-input" id="f_notes" name="notes" rows="3" maxlength="500"
                          placeholder="Optional remarks printed on the invoice"></textarea>
                <div class="bl-count-row">
                    <span class="bl-field-hint">Visible to staff and on the printed invoice.</span>
                    <span class="bl-char-count" id="notesCount">0 / 500</span>
                </div>
            </section>
        </div>

        <!-- =============== LINE ITEMS =============== -->
        <section class="bl-card">
            <div class="bl-card-head">
                <h3 class="bl-card-title" style="margin:0;">
                    <i class="bi bi-list-check"></i> Line items
                    <span class="bl-section-count" id="rowCount">0 items</span>
                </h3>
                <div class="bl-card-tools">
                    <label class="visually-hidden" for="servicePicker">Add from catalogue</label>
                    <select class="bl-input bl-input--sm" id="servicePicker" style="max-width:300px;">
                        <option value="">+ Add from catalogue…</option>
                        <?php foreach ($grouped as $cat => $list): ?>
                            <optgroup label="<?= esc($cat) ?>">
                                <?php foreach ($list as $svc): ?>
                                    <option value="<?= esc($svc['service_name'], 'attr') ?>"
                                            data-category="<?= esc($svc['category'], 'attr') ?>"
                                            data-charge="<?= esc((string) $svc['charge'], 'attr') ?>">
                                        <?= esc($svc['service_name']) ?><?= $svc['charge'] > 0 ? ' — ₱' . number_format($svc['charge'], 2) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="bl-btn bl-btn--sm" id="addCustomLine">
                        <i class="bi bi-plus"></i> Custom line
                    </button>
                </div>
            </div>

            <!-- Warning when auto-filled services had no catalogue price -->
            <div class="bl-alert bl-alert--warn" id="unmatchedHint" hidden>
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span id="unmatchedText"></span>
            </div>

            <div class="bl-items-wrap">
                <table class="bl-items">
                    <thead>
                        <tr>
                            <th style="width:42%">Service</th>
                            <th style="width:16%">Category</th>
                            <th style="width:10%" class="bl-num">Qty</th>
                            <th style="width:16%" class="bl-num">Unit price</th>
                            <th style="width:16%" class="bl-num">Line total</th>
                            <th style="width:74px"><span class="visually-hidden">Row actions</span></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                </table>

                <div class="bl-items-empty" id="itemsEmpty" hidden>
                    <i class="bi bi-inbox"></i>
                    <p>No line items yet. Pick one from the catalogue above, or add a custom line.</p>
                </div>
            </div>

            <p class="bl-field-error" id="itemsError" role="alert"></p>

            <div class="bl-totals">
                <div class="bl-totals-row"><span>Subtotal</span><span data-subtotal>₱ 0.00</span></div>
                <div class="bl-totals-row bl-totals-row--discount" data-discount-row hidden>
                    <span>Discount</span><span data-discount>−₱ 0.00</span>
                </div>
                <div class="bl-totals-row bl-totals-row--grand"><span>Total due</span><span data-total>₱ 0.00</span></div>
            </div>
        </section>

        <!-- Sticky action bar so totals and Save stay reachable on long forms -->
        <div class="bl-savebar">
            <div class="bl-savebar-sum">
                <div>
                    <span class="bl-savebar-label">Total due</span>
                    <span class="bl-savebar-value" data-total>₱ 0.00</span>
                </div>
                <span class="bl-savebar-meta" id="savebarMeta">No items yet</span>
            </div>
            <div class="bl-savebar-actions">
                <a class="bl-btn" href="<?= base_url($routeBase) ?>">Cancel</a>
                <button type="submit" class="bl-btn bl-btn--primary" id="submitInvoice">
                    <i class="bi bi-check2-circle"></i> Create invoice
                </button>
            </div>
        </div>
    </form>
</div>

<style>
/* =========================================================
   This page is self-contained: the .bl design tokens and the
   shared components it uses are inlined below, so it renders
   correctly whether or not billing/_styles.php is in place.
   ========================================================= */

/* =========================================================
   TOKENS — accent aligned with the layout's --active-blue
   ========================================================= */
.bl {
    --bl-ink:        #0f172a;
    --bl-text:       #334155;
    --bl-muted:      #64748b;
    --bl-faint:      #94a3b8;
    --bl-line:       #e2e8f0;
    --bl-line-soft:  #f1f5f9;
    --bl-surface:    #ffffff;
    --bl-subtle:     #f8fafc;

    /* Accent: navy — matches the sidebar / active nav item */
    --bl-accent:      #1976d2;
    --bl-accent-dark: #1565c0;
    --bl-accent-soft: #e3f2fd;

    --bl-warn:        #b45309;
    --bl-warn-soft:   #fff7ed;
    --bl-danger:      #b91c1c;
    --bl-danger-soft: #fef2f2;
    --bl-green:       #047857;
    --bl-green-soft:  #ecfdf5;
    --bl-blue:        #1d4ed8;
    --bl-blue-soft:   #eaf2fe;

    --bl-radius:    12px;
    --bl-radius-sm: 8px;
    --bl-shadow:    0 1px 2px rgba(15, 23, 42, .04), 0 1px 3px rgba(15, 23, 42, .03);
    --bl-mono:      ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;

    color: var(--bl-text);
    font-size: .875rem;
    line-height: 1.55;
}

.bl,
.bl *,
.bl *::before,
.bl *::after { box-sizing: border-box; }

.bl *:focus-visible {
    outline: 2px solid var(--bl-accent);
    outline-offset: 2px;
    border-radius: 4px;
}

.bl [hidden] { display: none !important; }

/* =========================================================
   ALERTS
   ========================================================= */
.bl-alert {
    display: flex;
    align-items: flex-start;
    gap: .55rem;
    padding: .75rem .9rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: var(--bl-radius-sm);
    font-size: .8125rem;
}

.bl-alert--success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
.bl-alert--error   { background: var(--bl-danger-soft); border-color: #fecaca; color: #991b1b; }
.bl-alert--warn    { background: var(--bl-warn-soft); border-color: #fed7aa; color: var(--bl-warn); }

/* =========================================================
   PAGE HEADER
   ========================================================= */
.bl-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding-bottom: 1rem;
    margin-bottom: 1.1rem;
    border-bottom: 1px solid var(--bl-line);
}

.bl-title {
    display: flex;
    align-items: center;
    gap: .6rem;
    margin: 0 0 .2rem;
    font-size: 1.25rem;
    font-weight: 650;
    letter-spacing: -.015em;
    color: var(--bl-ink);
}

.bl-lede  { margin: 0; font-size: .8125rem; color: var(--bl-muted); }
.bl-dot   { margin: 0 .35rem; color: var(--bl-faint); }
.bl-muted { color: var(--bl-muted); }
.bl-head-actions { display: flex; gap: .5rem; flex-wrap: wrap; }

/* =========================================================
   BUTTONS — only these adopt the navy accent.
   Everything else on the page keeps its existing palette.
   ========================================================= */
.bl-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    height: 36px;
    padding: 0 .9rem;
    font-family: inherit;
    font-size: .8125rem;
    font-weight: 600;
    line-height: 1;
    color: var(--bl-text);
    background: var(--bl-surface);
    border: 1px solid var(--bl-line);
    border-radius: var(--bl-radius-sm);
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: background .15s, border-color .15s, color .15s, box-shadow .15s, transform .1s;
}

.bl-btn:hover  { background: var(--bl-subtle); border-color: #cbd5e1; color: var(--bl-ink); }
.bl-btn:active { transform: translateY(1px); }
.bl-btn i      { font-size: .9em; }

.bl-btn--primary,
.bl-btn--primary:hover { color: #fff; }

.bl-btn--primary {
    background: var(--bl-accent);
    border-color: var(--bl-accent);
    box-shadow: 0 1px 2px rgba(25, 118, 210, .25);
}

.bl-btn--primary:hover {
    background: var(--bl-accent-dark);
    border-color: var(--bl-accent-dark);
    box-shadow: 0 4px 12px rgba(25, 118, 210, .25);
}

.bl-btn--danger,
.bl-btn--danger:hover { color: #fff; }

.bl-btn--danger       { background: var(--bl-danger); border-color: var(--bl-danger); }
.bl-btn--danger:hover { background: #991b1b; border-color: #991b1b; }

.bl-btn--ghost-danger {
    background: var(--bl-surface);
    border-color: #fecaca;
    color: var(--bl-danger);
}

.bl-btn--ghost-danger:hover { background: var(--bl-danger-soft); border-color: #fca5a5; color: #991b1b; }

.bl-btn--sm { height: 30px; padding: 0 .6rem; font-size: .75rem; }
.bl-btn[disabled] { opacity: .55; pointer-events: none; }

/* =========================================================
   STAT CARDS
   ========================================================= */
.bl-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .9rem;
    margin-bottom: 1.1rem;
}

.bl-stat {
    position: relative;
    display: flex;
    align-items: center;
    gap: .9rem;
    padding: 1rem 1.1rem;
    background: var(--bl-surface);
    border: 1px solid var(--bl-line);
    border-radius: var(--bl-radius);
    box-shadow: var(--bl-shadow);
    overflow: hidden;
}

.bl-stat::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 3px;
    background: var(--tone, var(--bl-line));
}

.bl-stat--green  { --tone: #10b981; }
.bl-stat--teal   { --tone: var(--bl-accent); }
.bl-stat--warn   { --tone: #f59e0b; background: linear-gradient(180deg, #fffbeb, var(--bl-surface)); }
.bl-stat--blue   { --tone: #3b82f6; }
.bl-stat--danger { --tone: #dc2626; background: linear-gradient(180deg, #fef2f2, var(--bl-surface)); }

.bl-stat-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    font-size: 1rem;
    color: var(--tone, var(--bl-muted));
    background: color-mix(in srgb, var(--tone, #64748b) 12%, transparent);
}

.bl-stat-body  { min-width: 0; }

.bl-stat-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--bl-ink);
    font-variant-numeric: tabular-nums;
    letter-spacing: -.02em;
    line-height: 1.15;
}

.bl-stat-label { margin-top: .2rem; font-size: .78rem; color: var(--bl-muted); }
.bl-stat-foot  { margin-top: .25rem; font-size: .72rem; color: var(--bl-faint); }

/* =========================================================
   PANELS & CARDS
   ========================================================= */
.bl-panel {
    background: var(--bl-surface);
    border: 1px solid var(--bl-line);
    border-radius: var(--bl-radius);
    box-shadow: var(--bl-shadow);
    overflow: hidden;
}

.bl-panel--plain  { padding: 1.1rem 1.25rem; margin-bottom: 1rem; }
.bl-panel--danger { border-color: #fecaca; background: linear-gradient(180deg, #fffafa, #fff); }

.bl-card {
    background: var(--bl-surface);
    border: 1px solid var(--bl-line);
    border-radius: var(--bl-radius);
    box-shadow: var(--bl-shadow);
    padding: 1.05rem 1.15rem;
    margin-bottom: 1rem;
}

.bl-card-title {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin: 0 0 .85rem;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--bl-ink);
}

.bl-card-title i { color: var(--bl-accent); font-size: 1.05em; }

.bl-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: .85rem;
    flex-wrap: wrap;
}

.bl-card-tools { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }

.bl-section-title {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin: 0 0 .85rem;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--bl-ink);
}

.bl-section-title i { color: var(--bl-accent); font-size: 1.05em; }
.bl-section-title--danger i { color: var(--bl-danger); }

.bl-section-count {
    margin-left: auto;
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: 0;
    text-transform: none;
    color: var(--bl-muted);
    background: var(--bl-subtle);
    border: 1px solid var(--bl-line);
    border-radius: 999px;
    padding: .1rem .55rem;
}

/* =========================================================
   TABS
   ========================================================= */
.bl-tabs {
    display: flex;
    gap: .25rem;
    padding: 0 .75rem;
    border-bottom: 1px solid var(--bl-line);
    overflow-x: auto;
    scrollbar-width: none;
}

.bl-tabs::-webkit-scrollbar { display: none; }

.bl-tab {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .85rem .6rem;
    font-size: .8125rem;
    font-weight: 600;
    color: var(--bl-muted);
    text-decoration: none;
    white-space: nowrap;
    transition: color .15s;
}

.bl-tab:hover,
.bl-tab.is-active { color: var(--bl-ink); }

.bl-tab.is-active::after {
    content: "";
    position: absolute;
    left: .4rem;
    right: .4rem;
    bottom: -1px;
    height: 2px;
    border-radius: 2px 2px 0 0;
    background: var(--bl-accent);
}

.bl-tab-count {
    min-width: 1.4rem;
    padding: .05rem .4rem;
    font-size: .7rem;
    font-weight: 600;
    text-align: center;
    color: var(--bl-muted);
    background: var(--bl-line-soft);
    border-radius: 999px;
    font-variant-numeric: tabular-nums;
}

.bl-tab.is-active .bl-tab-count { color: var(--bl-accent); background: var(--bl-accent-soft); }

/* =========================================================
   FORM CONTROLS — unchanged colour scheme
   ========================================================= */
.bl-filters {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
    padding: .85rem 1rem;
    border-bottom: 1px solid var(--bl-line);
    background: linear-gradient(180deg, #fcfdfe, var(--bl-surface));
}

.bl-search { position: relative; flex: 1 1 280px; max-width: 460px; }

.bl-search > i {
    position: absolute;
    left: .75rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: .8rem;
    color: var(--bl-faint);
    pointer-events: none;
}

.bl-input,
select.bl-input,
textarea.bl-input {
    height: 36px;
    font-family: inherit;
    font-size: .8125rem;
    color: var(--bl-ink);
    background: var(--bl-surface);
    border: 1px solid var(--bl-line);
    border-radius: var(--bl-radius-sm);
    padding: 0 .75rem;
    transition: border-color .15s, box-shadow .15s;
}

textarea.bl-input { height: auto; padding: .55rem .75rem; line-height: 1.5; }

.bl-input:hover { border-color: #cbd5e1; }

/* Focus ring adopts the navy accent so it matches the buttons */
.bl-input:focus {
    outline: none;
    border-color: var(--bl-accent);
    box-shadow: 0 0 0 3px rgba(25, 118, 210, .15);
}

.bl-input.is-invalid {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, .13);
}

.bl-search .bl-input { width: 100%; padding-left: 2.1rem; }

.bl-date-range {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding-left: 1.8rem;
}

.bl-date-icon {
    position: absolute;
    left: .55rem;
    font-size: .8rem;
    color: var(--bl-faint);
    pointer-events: none;
}

.bl-input--date { min-width: 150px; }
.bl-input--sm   { height: 32px; font-size: .78rem; }
.bl-to { font-size: .75rem; color: var(--bl-faint); }

.bl-label {
    display: block;
    margin: 0 0 .3rem;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--bl-muted);
}

.bl-hint-inline { color: var(--bl-muted); font-weight: 400; text-transform: none; letter-spacing: 0; }
.bl-req { color: #ef4444; }

.bl-field-hint  { margin: .3rem 0 0; font-size: .73rem; color: var(--bl-faint); }
.bl-field-hint code {
    background: var(--bl-subtle);
    border-radius: 4px;
    padding: .05rem .3rem;
    color: var(--bl-accent-dark);
}

.bl-field-error {
    display: none;
    margin: .3rem 0 0;
    font-size: .75rem;
    font-weight: 500;
    color: #dc2626;
}

.bl-field-error.show { display: block; }

.bl-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; }
.bl-grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .85rem; }
.bl-span-2 { grid-column: span 2; }
.bl-form-actions { display: flex; justify-content: flex-end; gap: .5rem; margin-top: 1rem; }

/* Segmented control */
.bl-segment {
    display: inline-flex;
    padding: 3px;
    gap: 2px;
    background: var(--bl-subtle);
    border: 1px solid var(--bl-line);
    border-radius: 999px;
}

.bl-segment a,
.bl-segment button {
    border: none;
    background: transparent;
    border-radius: 999px;
    padding: .3rem .75rem;
    font-family: inherit;
    font-size: .75rem;
    font-weight: 650;
    color: var(--bl-muted);
    text-decoration: none;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s, color .15s;
}

.bl-segment a:hover,
.bl-segment button:hover { color: var(--bl-ink); }

.bl-segment .is-active {
    background: #fff;
    color: var(--bl-ink);
    box-shadow: 0 1px 2px rgba(15, 23, 42, .12);
}

/* =========================================================
   TABLES — every colour inside the table is unchanged
   ========================================================= */
.bl-table-wrap { overflow-x: auto; }

.bl-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .8125rem;
}

.bl-table th {
    padding: .7rem 1rem;
    text-align: left;
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: var(--bl-muted);
    background: var(--bl-subtle);
    border-bottom: 1px solid var(--bl-line);
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 2;
}

.bl-table td {
    padding: .85rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--bl-line-soft);
}

.bl-table tbody tr:last-child td { border-bottom: 0; }
.bl-table tbody tr { transition: background .12s; }
.bl-table tbody tr:hover { background: #fafbfd; }

.bl-lines-wrap { border: 1px solid var(--bl-line); border-radius: 10px; overflow: hidden; }

.bl-lines { width: 100%; border-collapse: collapse; font-size: .8125rem; }

.bl-lines th {
    padding: .6rem .85rem;
    text-align: left;
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: var(--bl-muted);
    background: var(--bl-subtle);
    border-bottom: 1px solid var(--bl-line);
    white-space: nowrap;
}

.bl-lines td { padding: .72rem .85rem; border-bottom: 1px solid var(--bl-line-soft); }
.bl-lines tbody tr:last-child td { border-bottom: 0; }
.bl-lines tbody tr:hover { background: #fafbfd; }
.bl-lines tfoot td { background: var(--bl-subtle); font-weight: 600; color: var(--bl-ink); border-top: 1px solid var(--bl-line); }
.bl-lines .bl-actions-col { width: 1%; }

.bl-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.bl-strong { font-weight: 600; color: var(--bl-ink); }
.bl-balance { color: var(--bl-warn); font-weight: 600; }

.bl-empty-cell {
    padding: 1.5rem;
    text-align: center;
    color: var(--bl-muted);
    font-style: italic;
}

.bl-c-ref { white-space: nowrap; }

.bl-ref {
    display: inline-block;
    padding: .2rem .55rem;
    font-family: var(--bl-mono);
    font-size: .78rem;
    font-weight: 600;
    color: var(--bl-ink);
    background: var(--bl-line-soft);
    border-radius: 6px;
    text-decoration: none;
    transition: background .15s, color .15s;
}

a.bl-ref:hover { background: var(--bl-accent-soft); color: var(--bl-accent-dark); }
.bl-ref--lg { font-size: 1rem; padding: .4rem .75rem; }

.bl-patient .bl-name { display: block; font-weight: 600; color: var(--bl-ink); line-height: 1.3; }
.bl-patient .bl-sub  { display: block; font-size: .72rem; color: var(--bl-muted); }
.bl-c-date { white-space: nowrap; color: var(--bl-text); }

.bl-paid-cell { display: flex; flex-direction: column; align-items: flex-end; gap: .25rem; }

.bl-progress {
    display: block;
    width: 56px;
    height: 3px;
    border-radius: 2px;
    background: var(--bl-line);
    overflow: hidden;
}

.bl-progress > span {
    display: block;
    height: 100%;
    background: var(--bl-green);
    border-radius: 2px;
    transition: width .3s;
}

/* =========================================================
   BADGES
   ========================================================= */
.bl-status {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .25rem .6rem;
    font-size: .72rem;
    font-weight: 600;
    border-radius: 999px;
    white-space: nowrap;
    color: var(--tone-fg, #334155);
    background: var(--tone-bg, #f1f5f9);
}

.bl-status i { font-size: .75em; }
.bl-status--draft    { --tone-bg: #f1f5f9; --tone-fg: #475569; }
.bl-status--unpaid   { --tone-bg: var(--bl-warn-soft);   --tone-fg: var(--bl-warn); }
.bl-status--partial  { --tone-bg: var(--bl-blue-soft);   --tone-fg: var(--bl-blue); }
.bl-status--paid     { --tone-bg: var(--bl-green-soft);  --tone-fg: var(--bl-green); }
.bl-status--void     { --tone-bg: #f1f5f9; --tone-fg: #64748b; }
.bl-status--refunded { --tone-bg: var(--bl-danger-soft); --tone-fg: var(--bl-danger); }

.bl-cat {
    display: inline-block;
    padding: .15rem .5rem;
    font-size: .72rem;
    font-weight: 600;
    color: #0e7490;
    background: #ecfeff;
    border: 1px solid #cffafe;
    border-radius: 6px;
}

.bl-method {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .2rem .55rem;
    font-size: .72rem;
    font-weight: 600;
    border-radius: 6px;
    background: var(--bl-line-soft);
    color: var(--bl-text);
}

.bl-method i { font-size: .85em; }
.bl-method--cash      { background: var(--bl-green-soft); color: var(--bl-green); }
.bl-method--card      { background: var(--bl-blue-soft);  color: var(--bl-blue); }
.bl-method--gcash,
.bl-method--paymaya   { background: #f3e8ff; color: #6d28d9; }
.bl-method--insurance { background: var(--bl-accent-soft); color: var(--bl-accent-dark); }

.bl-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .18rem .55rem;
    font-size: .72rem;
    font-weight: 600;
    border-radius: 999px;
    background: var(--bl-subtle);
    border: 1px solid var(--bl-line);
    color: var(--bl-muted);
}

.bl-chip--overdue { background: var(--bl-warn-soft); border-color: #fed7aa; color: var(--bl-warn); }

/* =========================================================
   ICON BUTTONS
   ========================================================= */
.bl-actions-col { width: 1%; }
.bl-c-actions { text-align: right; white-space: nowrap; width: 1%; }
.bl-actions { display: inline-flex; gap: .25rem; }

.bl-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    font-size: .85rem;
    color: var(--bl-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--bl-radius-sm);
    text-decoration: none;
    cursor: pointer;
    transition: background .15s, color .15s, border-color .15s;
}

.bl-icon-btn:hover          { background: var(--bl-blue-soft); color: var(--bl-blue); border-color: #dbe6fb; }
.bl-icon-btn--primary:hover { background: var(--bl-accent-soft); color: var(--bl-accent-dark); border-color: #bbdefb; }
.bl-icon-btn--danger:hover  { background: var(--bl-danger-soft); color: var(--bl-danger); border-color: #fecaca; }

/* =========================================================
   EMPTY STATE / FOOTER / PAGER
   ========================================================= */
.bl-empty { padding: 3rem 1rem; text-align: center; }

.bl-empty-icon {
    display: grid;
    place-items: center;
    width: 52px;
    height: 52px;
    margin: 0 auto .8rem;
    font-size: 1.4rem;
    color: var(--bl-faint);
    background: var(--bl-subtle);
    border-radius: 14px;
}

.bl-empty h3 { margin: 0 0 .35rem; font-size: .95rem; font-weight: 650; color: var(--bl-ink); }
.bl-empty p  { margin: 0 auto 1rem; max-width: 28rem; font-size: .8125rem; color: var(--bl-muted); }

.bl-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    flex-wrap: wrap;
    padding: .7rem 1rem;
    font-size: .78rem;
    color: var(--bl-muted);
    border-top: 1px solid var(--bl-line);
}

.bl-pager { display: flex; gap: .25rem; align-items: center; }

.bl-page {
    min-width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 .5rem;
    font-size: .78rem;
    font-weight: 600;
    color: var(--bl-text);
    background: var(--bl-surface);
    border: 1px solid var(--bl-line);
    border-radius: var(--bl-radius-sm);
    text-decoration: none;
    font-variant-numeric: tabular-nums;
    transition: background .15s, border-color .15s, color .15s;
}

.bl-page:hover      { background: var(--bl-subtle); border-color: #cbd5e1; }
.bl-page.active     { background: var(--bl-accent); border-color: var(--bl-accent); color: #fff; }
.bl-page.is-disabled{ opacity: .45; pointer-events: none; }
.bl-gap { color: var(--bl-faint); padding: 0 .15rem; }

.bl-note { margin: 0; font-size: .8125rem; line-height: 1.65; color: var(--bl-text); }
.bl-note em { color: var(--bl-ink); font-style: normal; font-weight: 600; }

/* =========================================================
   RESPONSIVE
   ========================================================= */
@media (max-width: 1100px) {
    .bl-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 840px) {
    .bl-grid-2, .bl-grid-3 { grid-template-columns: minmax(0, 1fr); }
    .bl-span-2 { grid-column: auto; }
}

@media (max-width: 640px) {
    .bl-stats { grid-template-columns: minmax(0, 1fr); }
    .bl-filters { flex-direction: column; align-items: stretch; }
    .bl-search { flex: 0 0 auto; }
    .bl-search, .bl-input--date { max-width: none; width: 100%; }
    .bl-date-range { width: 100%; }
    .bl-date-range .bl-input--date { flex: 1; min-width: 0; }
    .bl-head { align-items: flex-start; }
    .bl-head-actions { width: 100%; }
    .bl-head-actions .bl-btn { flex: 1; }
}

@media (prefers-reduced-motion: reduce) {
    .bl *, .bl *::before, .bl *::after { transition: none !important; }
}

/* =========================================================
   PRINT
   ========================================================= */
@media print {
    .bl-head-actions,
    .bl-filters,
    .bl-tabs,
    .bl-pager,
    .bl-actions,
    .bl-no-print { display: none !important; }

    .bl-panel, .bl-card, .bl-stat, .bl-summary {
        box-shadow: none !important;
        border: 1px solid #cbd5e1 !important;
        break-inside: avoid;
    }
}


/* ===== PAGE-SPECIFIC ===== */

/* Leave room so the sticky bar never rests on the last line item */
.bl-create .bl-form { padding-bottom: .5rem; }
.bl-create .bl-card:last-of-type { margin-bottom: .75rem; }

.bl-create .bl-form-cols {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
    align-items: start;
}

.bl-prefill {
    padding: .8rem .9rem;
    border: 1px dashed var(--bl-line);
    border-radius: 10px;
    background: var(--bl-subtle);
}

.bl-prefill .bl-label { margin-top: 0; }

/* Linked-request chip already uses the accent-soft family, so it now
   reads navy instead of teal, matching the primary buttons. */
.bl-linked {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    margin-top: .7rem;
    padding: .3rem .4rem .3rem .65rem;
    border-radius: 999px;
    background: var(--bl-accent-soft);
    border: 1px solid #bbdefb;
    font-size: .76rem;
    font-weight: 600;
    color: var(--bl-accent-dark);
}

.bl-linked button {
    display: grid;
    place-items: center;
    width: 18px;
    height: 18px;
    border: none;
    border-radius: 50%;
    background: transparent;
    color: var(--bl-accent-dark);
    font-size: .65rem;
    cursor: pointer;
}

.bl-linked button:hover { background: #fff; }

.bl-count-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 1rem;
}

.bl-char-count { font-size: .72rem; color: var(--bl-faint); white-space: nowrap; }
.bl-char-count.near-limit { color: var(--bl-warn); font-weight: 600; }

/* Quick discount presets — hover tints with the accent */
.bl-quick { display: flex; gap: .35rem; flex-wrap: wrap; }

.bl-quick-btn {
    border: 1px solid var(--bl-line);
    background: var(--bl-surface);
    border-radius: 999px;
    padding: .2rem .65rem;
    font-family: inherit;
    font-size: .72rem;
    font-weight: 600;
    color: var(--bl-muted);
    cursor: pointer;
    transition: background .15s, color .15s, border-color .15s;
}

.bl-quick-btn:hover {
    background: var(--bl-accent-soft);
    color: var(--bl-accent-dark);
    border-color: #bbdefb;
}

/* ITEMS TABLE */
.bl-items-wrap { overflow-x: auto; border: 1px solid var(--bl-line); border-radius: 10px; }
.bl-items { width: 100%; border-collapse: collapse; font-size: .8125rem; }

.bl-items th {
    padding: .55rem .75rem;
    text-align: left;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--bl-muted);
    background: var(--bl-subtle);
    border-bottom: 1px solid var(--bl-line);
}

.bl-items td { padding: .4rem .5rem; border-bottom: 1px solid var(--bl-line-soft); vertical-align: middle; }
.bl-items tbody tr:last-child td { border-bottom: 0; }
.bl-items tbody tr.is-unpriced { background: #fffbf5; }
.bl-items .bl-num { text-align: right; font-variant-numeric: tabular-nums; }

.bl-items input,
.bl-items select {
    width: 100%;
    height: 32px;
    font-family: inherit;
    font-size: .78rem;
    border: 1px solid var(--bl-line);
    border-radius: 6px;
    padding: 0 .55rem;
    background: #fff;
    color: var(--bl-ink);
    transition: border-color .15s, box-shadow .15s;
}

/* Focus ring inside the items table adopts the navy accent too */
.bl-items input:focus,
.bl-items select:focus {
    outline: none;
    border-color: var(--bl-accent);
    box-shadow: 0 0 0 2px rgba(25, 118, 210, .15);
}

.bl-items input[readonly] { background: var(--bl-subtle); color: var(--bl-muted); }
.bl-items .bl-num input { text-align: right; font-variant-numeric: tabular-nums; }
.bl-items .bl-line-total { font-weight: 650; color: var(--bl-ink); padding-right: .75rem; }

.bl-items .bl-remove {
    width: 30px;
    height: 30px;
    border: none;
    background: transparent;
    color: var(--bl-faint);
    cursor: pointer;
    border-radius: 6px;
    transition: background .15s, color .15s;
}

.bl-items .bl-remove:hover { background: var(--bl-danger-soft); color: var(--bl-danger); }

.bl-items-empty { padding: 2rem 1rem; text-align: center; color: var(--bl-faint); }
.bl-items-empty i { font-size: 1.4rem; display: block; margin-bottom: .4rem; }
.bl-items-empty p { margin: 0; font-size: .8125rem; }

/* TOTALS */
.bl-totals { margin-top: 1rem; margin-left: auto; width: min(320px, 100%); font-variant-numeric: tabular-nums; }
.bl-totals-row { display: flex; justify-content: space-between; padding: .4rem 0; font-size: .8125rem; }
.bl-totals-row--discount { color: var(--bl-green); }

.bl-totals-row--grand {
    border-top: 2px solid var(--bl-ink);
    margin-top: .4rem;
    padding-top: .6rem;
    font-weight: 700;
    font-size: 1rem;
    color: var(--bl-ink);
}

/* ROW TOOLS */
.bl-row-tools { display: flex; gap: .15rem; justify-content: flex-end; }

.bl-row-btn {
    width: 28px;
    height: 28px;
    border: none;
    background: transparent;
    color: var(--bl-faint);
    cursor: pointer;
    border-radius: 6px;
    font-size: .8rem;
    transition: background .15s, color .15s;
}

.bl-row-btn:hover  { background: var(--bl-line-soft); color: var(--bl-ink); }
.bl-remove:hover   { background: var(--bl-danger-soft); color: var(--bl-danger); }
.bl-duplicate:hover{ background: var(--bl-accent-soft); color: var(--bl-accent-dark); }

/* STICKY SAVE BAR */
.bl-savebar {
    position: sticky;
    bottom: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: .25rem;
    padding: .7rem 1.1rem;
    background: rgba(255, 255, 255, .95);
    backdrop-filter: blur(8px);
    border: 1px solid var(--bl-line);
    border-radius: var(--bl-radius);
    box-shadow: 0 -2px 14px rgba(15, 23, 42, .07);
}

.bl-savebar-sum { display: flex; flex-direction: column; gap: .1rem; }
.bl-savebar-sum > div { display: flex; align-items: baseline; gap: .5rem; }
.bl-savebar-meta { font-size: .72rem; color: var(--bl-faint); }

.bl-savebar-label {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--bl-faint);
}

.bl-savebar-value {
    font-size: 1.2rem;
    font-weight: 750;
    color: var(--bl-ink);
    font-variant-numeric: tabular-nums;
}

.bl-savebar-actions { display: flex; gap: .5rem; }

@media (max-width: 840px) {
    .bl-create .bl-form { padding-bottom: .5rem; }
    .bl-create .bl-card:last-of-type { margin-bottom: .75rem; }

    .bl-create .bl-form-cols { grid-template-columns: minmax(0, 1fr); }
    .bl-savebar { flex-direction: column; align-items: stretch; }
    .bl-savebar-actions .bl-btn { flex: 1; }
}
</style>

<script>
(function () {
    'use strict';

    var $    = function (id) { return document.getElementById(id); };
    var peso = function (v) { return '₱ ' + (Number(v) || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };
    var esc  = function (s) { return String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;'); };

    // Server-side price map (normalised keys)
    var PRICE_MAP = <?= json_encode($priceMap, JSON_UNESCAPED_UNICODE) ?>;

    function normaliseName(name) {
        return String(name == null ? '' : name)
            .replace(/\\\//g, '/')
            .replace(/\\/g, '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();
    }

    function resolvePrice(rawName) {
        var key = normaliseName(rawName);
        if (key === '') { return null; }
        if (PRICE_MAP[key]) { return PRICE_MAP[key]; }

        var keys = Object.keys(PRICE_MAP);
        for (var i = 0; i < keys.length; i++) { if (keys[i].indexOf(key) === 0) { return PRICE_MAP[keys[i]]; } }
        for (var j = 0; j < keys.length; j++) { if (keys[j].indexOf(key) !== -1) { return PRICE_MAP[keys[j]]; } }
        for (var k = 0; k < keys.length; k++) { if (key.indexOf(keys[k]) !== -1) { return PRICE_MAP[keys[k]]; } }
        return null;
    }

    var itemsBody  = $('itemsBody');
    var itemsEmpty = $('itemsEmpty');

    // Each row gets a stable index, so PHP receives
    //   items[0][service_name], items[0][qty], items[0][price]
    function nextRowIndex() {
        var max = -1;
        itemsBody.querySelectorAll('tr[data-idx]').forEach(function (tr) {
            var n = parseInt(tr.dataset.idx, 10);
            if (!isNaN(n) && n > max) { max = n; }
        });
        return max + 1;
    }

    function addRow(data) {
        data = data || {};
        var idx = nextRowIndex();
        var tr  = document.createElement('tr');
        tr.dataset.idx = idx;

        tr.innerHTML =
            '<td><input type="text" name="items[' + idx + '][service_name]" value="' + esc(data.name || '') +
                '" placeholder="Service description" required></td>' +
            '<td><input type="text" name="items[' + idx + '][category]" value="' + esc(data.category || '') +
                '" placeholder="—"></td>' +
            '<td class="bl-num"><input type="number" name="items[' + idx + '][qty]" value="' + (data.qty || 1) +
                '" min="0.01" step="0.01" inputmode="decimal"></td>' +
            '<td class="bl-num"><input type="number" name="items[' + idx + '][price]" value="' + (data.price || 0) +
                '" min="0" step="0.01" inputmode="decimal"></td>' +
            '<td class="bl-num bl-line-total" data-line-total>' + peso((data.qty || 1) * (data.price || 0)) + '</td>' +
            '<td class="bl-row-tools">' +
                '<button type="button" class="bl-row-btn bl-duplicate" title="Duplicate line" aria-label="Duplicate line">' +
                    '<i class="bi bi-copy"></i></button>' +
                '<button type="button" class="bl-row-btn bl-remove" title="Remove line" aria-label="Remove line">' +
                    '<i class="bi bi-x-lg"></i></button>' +
            '</td>';

        itemsBody.appendChild(tr);
        recalc();
        return tr;
    }

    function recalc() {
        var rows = itemsBody.querySelectorAll('tr');
        var subtotal = 0;

        rows.forEach(function (tr) {
            var qty   = parseFloat(tr.querySelector('input[name$="[qty]"]').value) || 0;
            var price = parseFloat(tr.querySelector('input[name$="[price]"]').value) || 0;
            var line  = qty * price;

            tr.querySelector('[data-line-total]').textContent = peso(line);
            tr.classList.toggle('is-unpriced', price <= 0);
            subtotal += line;
        });

        var type  = $('f_discount_type').value;
        var value = parseFloat($('f_discount_value').value) || 0;
        var disc  = 0;

        if (type === 'percent')     { disc = subtotal * Math.min(100, Math.max(0, value)) / 100; }
        else if (type === 'fixed')  { disc = Math.min(subtotal, Math.max(0, value)); }

        var total = Math.max(0, subtotal - disc);

        document.querySelector('[data-subtotal]').textContent = peso(subtotal);

        var drow = document.querySelector('[data-discount-row]');
        if (disc > 0) {
            drow.hidden = false;
            document.querySelector('[data-discount]').textContent = '−' + peso(disc);
        } else {
            drow.hidden = true;
        }

        // There are two [data-total] nodes: the panel and the sticky bar
        document.querySelectorAll('[data-total]').forEach(function (el) { el.textContent = peso(total); });

        var count = rows.length;
        $('rowCount').textContent = count + (count === 1 ? ' item' : ' items');
        itemsEmpty.hidden = count > 0;

        $('savebarMeta').textContent = count === 0
            ? 'No items yet'
            : count + (count === 1 ? ' item' : ' items') + ' · subtotal ' + peso(subtotal) +
              (disc > 0 ? ' · less ' + peso(disc) : '');

        if (count > 0) { $('itemsError').classList.remove('show'); }
    }

    itemsBody.addEventListener('input', function (e) {
        if (e.target.matches('input')) { recalc(); }
    });

    itemsBody.addEventListener('click', function (e) {
        var dup = e.target.closest('.bl-duplicate');
        if (dup) {
            var src = dup.closest('tr');
            addRow({
                name:     src.querySelector('input[name$="[service_name]"]').value,
                category: src.querySelector('input[name$="[category]"]').value,
                qty:      src.querySelector('input[name$="[qty]"]').value,
                price:    src.querySelector('input[name$="[price]"]').value
            });
            return;
        }

        var btn = e.target.closest('.bl-remove');
        if (!btn) { return; }

        // Keep at least one row on screen so the table never looks broken
        btn.closest('tr').remove();
        recalc();
    });

    // ===== DISCOUNT =====
    var dType   = $('f_discount_type');
    var dValue  = $('f_discount_value');
    var dHint   = $('discountHint');
    var dError  = $('discountError');
    var dReason = $('f_discount_reason');

    function syncDiscount() {
        var type = dType.value;
        dValue.disabled = (type === 'none');

        if (type === 'none') {
            dHint.textContent = 'Choose a type first.';
            dValue.value = 0;
        } else if (type === 'percent') {
            dHint.innerHTML = 'Percentage off the subtotal — e.g. <code>20</code> for senior or PWD.';
        } else {
            dHint.innerHTML = 'Flat amount off the subtotal — e.g. <code>150</code> for ₱150.00.';
        }

        validateDiscount();
        recalc();
    }

    function validateDiscount() {
        var type  = dType.value;
        var value = parseFloat(dValue.value) || 0;

        if (type === 'percent' && value > 100) {
            dError.textContent = 'A percentage discount cannot exceed 100%.';
            dError.classList.add('show');
            dValue.classList.add('is-invalid');
            return false;
        }

        if (value < 0) {
            dError.textContent = 'A discount cannot be negative.';
            dError.classList.add('show');
            dValue.classList.add('is-invalid');
            return false;
        }

        dError.classList.remove('show');
        dValue.classList.remove('is-invalid');
        return true;
    }

    dType.addEventListener('change', syncDiscount);
    dValue.addEventListener('input', function () { validateDiscount(); recalc(); });

    document.querySelectorAll('.bl-quick-btn[data-preset]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var preset = parseFloat(btn.dataset.preset);
            if (preset > 0) {
                dType.value  = 'percent';
                dValue.disabled = false;
                dValue.value = preset;
                dReason.value = btn.dataset.reason || '';
            } else {
                dType.value  = 'none';
                dValue.value = 0;
                dReason.value = '';
            }
            syncDiscount();
        });
    });

    // ===== NOTES COUNTER =====
    var notes = $('f_notes');
    var count = $('notesCount');

    function updateCount() {
        count.textContent = notes.value.length + ' / 500';
        count.classList.toggle('near-limit', notes.value.length > 450);
    }

    notes.addEventListener('input', updateCount);
    updateCount();

    // ===== CATALOGUE PICKER =====
    $('servicePicker').addEventListener('change', function () {
        var opt = this.selectedOptions[0];
        if (!opt || !opt.value) { return; }

        var row = addRow({
            name:     opt.value,
            category: opt.dataset.category || '',
            qty:      1,
            price:    parseFloat(opt.dataset.charge) || 0
        });

        this.value = '';
        row.querySelector('input[name$="[qty]"]').focus();
    });

    $('addCustomLine').addEventListener('click', function () {
        var row = addRow({ name: '', category: '', qty: 1, price: 0 });
        row.querySelector('input[name$="[service_name]"]').focus();
    });

    // ===== AUTO-FILL FROM A DIAGNOSTIC REQUEST =====
    var linked     = $('linkedBadge');
    var linkedText = $('linkedText');

    $('openRequestSelect').addEventListener('change', function () {
        var opt = this.selectedOptions[0];
        if (!opt || !opt.value) { return; }

        $('f_patient_name').value   = opt.dataset.name || '';
        $('f_patient_code').value   = opt.dataset.code || '';
        $('f_request_id').value     = opt.value;
        $('f_request_type').value   = opt.dataset.type || '';
        $('f_appointment_id').value = opt.dataset.appt || '';

        linkedText.textContent = (opt.dataset.type === 'xray' ? 'X-Ray' : 'Lab') +
                                 ' request #' + opt.value;
        linked.hidden = false;

        itemsBody.innerHTML = '';

        var services = (opt.dataset.services || '')
            .split('|')
            .map(function (s) { return s.trim(); })
            .filter(Boolean);

        var unmatched = [];
        var pickerOpts = $('servicePicker').options;

        services.forEach(function (rawName) {
            var price = 0, category = '', matched = false;

            for (var i = 0; i < pickerOpts.length; i++) {
                if (pickerOpts[i].value === rawName) {
                    price    = parseFloat(pickerOpts[i].dataset.charge) || 0;
                    category = pickerOpts[i].dataset.category || '';
                    matched  = true;
                    break;
                }
            }

            if (!matched) {
                var hit = resolvePrice(rawName);
                if (hit) {
                    price    = parseFloat(hit.charge) || 0;
                    category = hit.category || '';
                    matched  = true;
                }
            }

            if (!matched) { unmatched.push(rawName); }

            addRow({ name: rawName, category: category, qty: 1, price: price });
        });

        var hint = $('unmatchedHint');
        if (unmatched.length) {
            $('unmatchedText').innerHTML =
                '<strong>' + unmatched.length + '</strong> service' + (unmatched.length === 1 ? '' : 's') +
                ' had no catalogue price and came in at ₱0.00 — the highlighted rows below. ' +
                'Set a unit price on each before saving.';
            hint.hidden = false;
        } else {
            hint.hidden = true;
        }
    });

    $('unlinkBtn').addEventListener('click', function () {
        $('openRequestSelect').value = '';
        $('f_request_id').value = '';
        $('f_request_type').value = '';
        $('f_appointment_id').value = '';
        linked.hidden = true;
    });

    // ===== VALIDATION =====
    var form      = $('invoiceForm');
    var nameInput = $('f_patient_name');
    var nameError = $('nameError');
    var submitted = false;

    nameInput.addEventListener('input', function () {
        nameError.classList.remove('show');
        nameInput.classList.remove('is-invalid');
    });

    form.addEventListener('submit', function (e) {
        if (submitted) { e.preventDefault(); return; }

        // Patient name
        if (nameInput.value.trim() === '') {
            e.preventDefault();
            nameError.textContent = 'Enter the patient name.';
            nameError.classList.add('show');
            nameInput.classList.add('is-invalid');
            nameInput.focus();
            return;
        }

        // At least one line
        if (itemsBody.querySelectorAll('tr').length === 0) {
            e.preventDefault();
            var err = $('itemsError');
            err.textContent = 'Add at least one line item before saving.';
            err.classList.add('show');
            err.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        if (!validateDiscount()) {
            e.preventDefault();
            dValue.focus();
            return;
        }

        // Zero-priced lines are a warning, not a block
        var zeroPriced = [];
        itemsBody.querySelectorAll('tr').forEach(function (tr) {
            var name  = tr.querySelector('input[name$="[service_name]"]').value.trim();
            var price = parseFloat(tr.querySelector('input[name$="[price]"]').value) || 0;
            if (name !== '' && price <= 0) { zeroPriced.push(name); }
        });

        if (zeroPriced.length) {
            var ok = confirm('These line items have a ₱0.00 price:\n\n· ' + zeroPriced.join('\n· ') +
                             '\n\nSave the invoice anyway?');
            if (!ok) { e.preventDefault(); return; }
        }

        submitted = true;
        var btn = $('submitInvoice');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…';
    });

    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) { return; }
        submitted = false;
        var btn = $('submitInvoice');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle"></i> Create invoice';
    });

    // Alt+N adds a line without reaching for the mouse
    document.addEventListener('keydown', function (e) {
        if (e.altKey && (e.key === 'n' || e.key === 'N')) {
            e.preventDefault();
            var row = addRow({ name: '', category: '', qty: 1, price: 0 });
            row.querySelector('input[name$="[service_name]"]').focus();
        }
    });

    // Start with one empty row, as before
    syncDiscount();
    addRow({ name: '', category: '', qty: 1, price: 0 });
}());
</script>

<?= $this->endSection() ?>