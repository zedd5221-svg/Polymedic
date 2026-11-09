<?= $this->extend($layout) ?>
<?= $this->section('pageTitle') ?>New Invoice<?= $this->endSection() ?>
<?= $this->section($section) ?>

<?php
/** @var array $openRequests @var array $services @var string $routeBase */

/* ------------------------------------------------------------------
   New invoice. Order of work: who is it for, what are they paying for,
   any discount. A summary on the right follows every change and holds
   the Create button. Field names are unchanged from the previous form:
     patient_name, patient_code, request_id, request_type, appointment_id,
     items[i][service_name|category|qty|price],
     discount_type, discount_value, discount_reason, notes
   ------------------------------------------------------------------ */

$kindOf = static function (string $category): string {
    $c = strtolower(trim($category));
    if (in_array($c, ['xray', 'x-ray'], true))     { return 'xray'; }
    if (in_array($c, ['laboratory', 'lab'], true)) { return 'lab'; }
    return 'other';
};

/* The catalogue, in one stable shape for the search */
$catalogue = [];
foreach (($services ?? []) as $svc) {
    if (is_array($svc)) {
        $name = trim((string) ($svc['service_name'] ?? $svc['name'] ?? ''));
        if ($name === '') { continue; }
        $cat = (string) ($svc['category'] ?? '');
        $catalogue[] = ['name' => $name, 'category' => $cat, 'kind' => $kindOf($cat), 'charge' => (float) ($svc['charge'] ?? $svc['price'] ?? 0)];
    } elseif (is_string($svc) && trim($svc) !== '') {
        $catalogue[] = ['name' => trim($svc), 'category' => '', 'kind' => 'other', 'charge' => 0.0];
    }
}

/* Price map for request auto-fill: lowercase, slashes un-escaped, spaces collapsed */
$priceMap = [];
foreach ($catalogue as $svc) {
    $key = trim(strtolower(preg_replace('/\s+/', ' ', str_replace(['\\/', '\\'], ['/', ''], $svc['name']))));
    if ($key !== '') {
        $priceMap[$key] = ['name' => $svc['name'], 'charge' => $svc['charge'], 'category' => $svc['category']];
    }
}

/* Open requests (not yet invoiced), newest first */
$requestsData = [];
foreach (($openRequests ?? []) as $r) {
    if (!is_array($r)) { continue; }
    $svcList = array_values(array_filter(array_map('trim', explode(',', (string) ($r['services'] ?? '')))));
    $when    = $r['request_date'] ?? $r['created_at'] ?? null;
    $requestsData[] = [
        'id'       => (int) ($r['id'] ?? 0),
        'type'     => ($r['type'] ?? '') === 'xray' ? 'xray' : 'lab',
        'name'     => (string) ($r['patient_name'] ?? ''),
        'code'     => (string) ($r['patient_code'] ?? ''),
        'appt'     => (int) ($r['appointment_id'] ?? 0),
        'services' => $svcList,
        'status'   => (string) ($r['status'] ?? ''),
        'date'     => $when ? date('M j', strtotime($when)) : '',
    ];
}

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
?>

<div class="ni">

    <a class="ni-back" href="<?= base_url($routeBase) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>Billing</a>

    <header class="ni-head">
        <h1>New invoice</h1>
        <p>Choose the patient, add what they are paying for, and apply a discount if they have one.</p>
    </header>

    <form method="post" action="<?= base_url($routeBase . '/store') ?>" id="invoiceForm" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="request_id"     id="f_request_id">
        <input type="hidden" name="request_type"   id="f_request_type">
        <input type="hidden" name="appointment_id" id="f_appointment_id">
        <input type="hidden" name="discount_type"   id="f_discount_type" value="none">
        <input type="hidden" name="discount_value"  id="f_discount_value" value="0">
        <input type="hidden" name="discount_reason" id="f_discount_reason" value="">

        <div class="ni-grid">
            <div class="ni-main">

                <!-- ============ PATIENT ============ -->
                <section class="ni-card" aria-labelledby="niPatientTitle">
                    <header class="ni-card-head">
                        <h2 id="niPatientTitle">Patient</h2>
                    </header>
                    <div class="ni-card-body">

                        <?php if ($requestsData): ?>
                            <!-- Pick an open diagnostic request: fills the patient and the services -->
                            <div class="ni-request" id="requestPicker">
                                <label class="ni-label" for="requestSearch">
                                    Bill an open diagnostic request
                                    <span class="ni-optional">optional · <?= count($requestsData) ?> waiting</span>
                                </label>
                                <div class="ni-search">
                                    <i class="bi bi-search" aria-hidden="true"></i>
                                    <input type="search" id="requestSearch" placeholder="Search by patient name or code" autocomplete="off"
                                           role="combobox" aria-expanded="false" aria-controls="requestList">
                                </div>
                                <ul class="ni-reqlist" id="requestList" role="listbox" aria-label="Open diagnostic requests"></ul>
                            </div>
                        <?php endif; ?>

                        <!-- Shown once a request is linked -->
                        <div class="ni-linked" id="linkedBox" hidden>
                            <span class="ni-kind" id="linkedKind"></span>
                            <div class="ni-linked-text">
                                <b id="linkedTitle"></b>
                                <span id="linkedSub"></span>
                            </div>
                            <button type="button" class="ni-btn ni-btn--sm" id="unlinkBtn">Change</button>
                        </div>

                        <div class="ni-row-2">
                            <label class="ni-field">
                                <span class="ni-label">Patient name <b aria-hidden="true">*</b></span>
                                <input type="text" id="f_patient_name" name="patient_name" maxlength="120" autocomplete="off"
                                       placeholder="Full name as on their ID" required aria-describedby="nameError">
                                <small class="ni-error" id="nameError" role="alert"></small>
                            </label>
                            <label class="ni-field">
                                <span class="ni-label">Patient code <span class="ni-optional">optional</span></span>
                                <input type="text" id="f_patient_code" name="patient_code" maxlength="40" autocomplete="off" placeholder="e.g. PAT-26-0007">
                            </label>
                        </div>
                    </div>
                </section>

                <!-- ============ SERVICES ============ -->
                <section class="ni-card" aria-labelledby="niServicesTitle">
                    <header class="ni-card-head">
                        <h2 id="niServicesTitle">Services</h2>
                        <span class="ni-count" id="rowCount">0</span>
                    </header>
                    <div class="ni-card-body">

                        <div class="ni-adder">
                            <div class="ni-search ni-search--grow">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <input type="search" id="serviceSearch" placeholder="Search services to add" autocomplete="off"
                                       role="combobox" aria-expanded="false" aria-controls="serviceResults" aria-describedby="serviceTip">
                                <kbd class="ni-kbd" aria-hidden="true">/</kbd>
                                <ul class="ni-results" id="serviceResults" role="listbox" aria-label="Matching services" hidden></ul>
                            </div>
                            <div class="ni-seg" role="radiogroup" aria-label="Show services from">
                                <button type="button" class="ni-seg-btn is-active" data-kind="all" role="radio" aria-checked="true">All</button>
                                <button type="button" class="ni-seg-btn" data-kind="lab" role="radio" aria-checked="false">Lab</button>
                                <button type="button" class="ni-seg-btn" data-kind="xray" role="radio" aria-checked="false">X-ray</button>
                            </div>
                        </div>
                        <p class="ni-tip" id="serviceTip">Type a few letters, then press Enter to add the top match. Adding a service twice raises its quantity.</p>

                        <div class="ni-warn" id="unmatchedHint" hidden>
                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                            <span id="unmatchedText"></span>
                        </div>

                        <div class="ni-items-head" aria-hidden="true">
                            <span>Service</span><span>Qty</span><span>Unit price</span><span>Amount</span><span></span>
                        </div>
                        <ul class="ni-items" id="itemsList" aria-label="Services on this invoice"></ul>

                        <div class="ni-items-empty" id="itemsEmpty">
                            <p>No services yet.</p>
                            <span>Search above, or pick an open request to bring its services in.</span>
                        </div>
                        <small class="ni-error" id="itemsError" role="alert"></small>

                        <button type="button" class="ni-link-btn" id="addCustomLine">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>Add a service that is not in the list
                        </button>
                    </div>
                </section>

                <!-- ============ DISCOUNT ============ -->
                <section class="ni-card" aria-labelledby="niDiscountTitle">
                    <header class="ni-card-head">
                        <h2 id="niDiscountTitle">Discount</h2>
                    </header>
                    <div class="ni-card-body">
                        <div class="ni-choices" role="radiogroup" aria-label="Discount">
                            <button type="button" class="ni-choice is-active" data-discount="none" role="radio" aria-checked="true">
                                <b>None</b><span>Full price</span>
                            </button>
                            <button type="button" class="ni-choice" data-discount="senior" role="radio" aria-checked="false">
                                <b>Senior citizen</b><span>20% off</span>
                            </button>
                            <button type="button" class="ni-choice" data-discount="pwd" role="radio" aria-checked="false">
                                <b>PWD</b><span>20% off</span>
                            </button>
                            <button type="button" class="ni-choice" data-discount="custom" role="radio" aria-checked="false">
                                <b>Other</b><span>Set your own</span>
                            </button>
                        </div>

                        <div class="ni-custom" id="customDiscount" hidden>
                            <div class="ni-field">
                                <span class="ni-label" id="customTypeLabel">Take off</span>
                                <div class="ni-mini-seg" role="radiogroup" aria-labelledby="customTypeLabel">
                                    <button type="button" class="is-active" data-ctype="percent" role="radio" aria-checked="true">%</button>
                                    <button type="button" data-ctype="fixed" role="radio" aria-checked="false">₱</button>
                                </div>
                            </div>
                            <label class="ni-field">
                                <span class="ni-label">Amount</span>
                                <input type="number" id="customValue" min="0" step="0.01" inputmode="decimal" placeholder="0" aria-describedby="discountError">
                            </label>
                            <label class="ni-field ni-field--grow">
                                <span class="ni-label">Reason</span>
                                <input type="text" id="customReason" maxlength="120" placeholder="e.g. Employee, promo, HMO">
                            </label>
                            <small class="ni-error ni-error--row" id="discountError" role="alert"></small>
                        </div>

                        <div class="ni-note">
                            <button type="button" class="ni-link-btn" id="noteToggle" aria-expanded="false" aria-controls="noteBox">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i>Add a note
                            </button>
                            <div id="noteBox" hidden>
                                <label class="ni-field">
                                    <span class="ni-label">Note <span class="ni-optional">shown on the invoice</span></span>
                                    <textarea id="f_notes" name="notes" rows="3" maxlength="500" placeholder="Anything the patient or the cashier should know"></textarea>
                                </label>
                                <small class="ni-chars" id="notesCount">0 / 500</small>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ============ SUMMARY (follows the page) ============ -->
            <aside class="ni-side">
                <section class="ni-card ni-summary" aria-labelledby="niSummaryTitle">
                    <header class="ni-card-head"><h2 id="niSummaryTitle">Summary</h2></header>
                    <div class="ni-card-body">
                        <p class="ni-sum-patient" id="sumPatient"><span class="ni-faint">No patient yet</span></p>

                        <ul class="ni-sum-lines" id="sumLines"></ul>

                        <dl class="ni-sum-totals">
                            <div><dt>Subtotal</dt><dd data-subtotal>₱0.00</dd></div>
                            <div id="sumDiscountRow" hidden><dt id="sumDiscountLabel">Discount</dt><dd id="sumDiscount">−₱0.00</dd></div>
                            <div class="ni-sum-grand"><dt>Total</dt><dd data-total>₱0.00</dd></div>
                        </dl>

                        <ul class="ni-checks" id="checks" aria-label="Before you save">
                            <li data-check="patient">Patient name</li>
                            <li data-check="items">At least one service</li>
                            <li data-check="prices">Every service has a price</li>
                        </ul>

                        <button type="submit" class="ni-btn ni-btn--primary ni-btn--block" id="submitInvoice">
                            <i class="bi bi-check2" aria-hidden="true"></i>Create invoice
                        </button>
                        <a class="ni-btn ni-btn--quiet ni-btn--block" href="<?= base_url($routeBase) ?>">Cancel</a>
                    </div>
                </section>
            </aside>
        </div>
    </form>
</div>

<style>
.ni {
    --ni-ink:    var(--pm-text, #10202E);
    --ni-text:   var(--pm-text-soft, #3B4C5E);
    --ni-muted:  var(--pm-text-muted);
    --ni-faint:  var(--pm-text-muted);
    --ni-line:   var(--pm-line, #DDE4EC);
    --ni-rule:   var(--pm-line);
    --ni-strong: #C3CFDB;
    --ni-subtle: var(--pm-subtle);
    --ni-accent: var(--pm-accent, #175563);
    --ni-accent-dark: var(--pm-accent-text);
    --ni-accent-soft: var(--pm-accent-soft);
    --ni-mono:   var(--pm-mono, ui-monospace, SFMono-Regular, Menlo, monospace);

    max-width: 1180px;
    margin: 0 auto;
    color: var(--ni-text);
    font-size: 0.875rem;
}

.ni *, .ni *::before, .ni *::after { box-sizing: border-box; }
.ni *:focus-visible { outline: 2px solid var(--ni-accent); outline-offset: 2px; border-radius: 4px; }
.ni [hidden] { display: none !important; }
.ni dt { font-weight: 400; }
.ni-faint { color: var(--ni-faint); }

.ni-back { display: inline-flex; align-items: center; gap: 0.4rem; margin-bottom: 0.75rem; font-size: 0.8rem; font-weight: 500; color: var(--ni-muted); text-decoration: none; }
.ni-back:hover { color: var(--ni-ink); }

.ni-head { margin-bottom: 1rem; }
.ni-head h1 { margin: 0; font-size: 1.35rem; font-weight: 600; letter-spacing: -0.01em; color: var(--ni-ink); }
.ni-head p { margin: 0.3rem 0 0; font-size: 0.85rem; color: var(--ni-muted); }

.ni-alert { display: flex; align-items: center; gap: 0.5rem; margin: 0 0 1rem; padding: 0.6rem 0.85rem; font-size: 0.82rem; border: 1px solid; border-radius: 6px; }
.ni-alert--err { color: var(--pm-dk-fg-red, #B42318); background: var(--pm-dk-bg-red, #FEF3F2); border-color: var(--pm-dk-edge-red, #FECDCA); }

/* ---------- Layout ---------- */
.ni-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 1rem; align-items: start; }
.ni-main { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }
.ni-side { position: sticky; top: 1rem; min-width: 0; }

.ni-card { background: var(--pm-surface); border: 1px solid var(--ni-line); border-radius: 8px; }
.ni-card-head { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.85rem 1.15rem; border-bottom: 1px solid var(--ni-rule); }
.ni-card-head h2 { margin: 0; font-size: 0.95rem; font-weight: 600; color: var(--ni-ink); }
.ni-card-body { padding: 1rem 1.15rem 1.15rem; }
.ni-count { min-width: 1.4rem; padding: 0 0.35rem; font-size: 0.72rem; font-weight: 600; line-height: 1.3rem; text-align: center; color: var(--ni-text); background: var(--ni-rule); border-radius: 4px; font-variant-numeric: tabular-nums; }

/* ---------- Fields ---------- */
.ni-label { display: block; margin-bottom: 0.35rem; font-size: 0.8rem; font-weight: 500; color: var(--ni-ink); }
.ni-label b { color: var(--pm-dk-fg-red, #B42318); font-weight: 600; }
.ni-optional { margin-left: 0.25rem; font-weight: 400; color: var(--ni-faint); }
.ni-field { display: flex; flex-direction: column; margin: 0; min-width: 0; }
.ni-field input, .ni-field textarea, .ni-search input {
    width: 100%; height: 38px; padding: 0 0.75rem; font: inherit; font-size: 0.875rem; color: var(--ni-ink);
    background: var(--pm-surface); border: 1px solid var(--ni-strong); border-radius: 6px;
}
.ni-field textarea { height: auto; padding: 0.55rem 0.75rem; line-height: 1.5; resize: vertical; }
.ni-field input::placeholder, .ni-field textarea::placeholder, .ni-search input::placeholder { color: #8496A8; }
.ni-field input:focus, .ni-field textarea:focus, .ni-search input:focus { outline: none; border-color: var(--ni-accent); box-shadow: 0 0 0 3px rgba(23, 85, 99, 0.14); }
.ni-field input.is-invalid { border-color: #F04438; background: var(--pm-dk-bg-red, #FEF3F2); }
.ni-row-2 { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); gap: 0.9rem; }
.ni-error { display: block; min-height: 0; margin-top: 0.3rem; font-size: 0.78rem; color: var(--pm-dk-fg-red, #B42318); }
.ni-error:empty { display: none; }
.ni-tip { margin: 0.45rem 0 0; font-size: 0.75rem; color: var(--ni-faint); }

.ni-search { position: relative; }
.ni-search > .bi-search { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); font-size: 0.85rem; color: var(--ni-faint); pointer-events: none; }
.ni-search input { padding-left: 2.15rem; }
.ni-search--grow { flex: 1; min-width: 0; }
.ni-kbd { position: absolute; right: 0.6rem; top: 50%; transform: translateY(-50%); padding: 0 0.35rem; font-family: var(--ni-mono); font-size: 0.7rem; line-height: 1.25rem; color: var(--ni-muted); background: var(--ni-subtle); border: 1px solid var(--ni-line); border-radius: 4px; pointer-events: none; }

/* ---------- Request picker ---------- */
.ni-request { margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--ni-rule); }
.ni-reqlist { margin: 0.5rem 0 0; padding: 0; list-style: none; max-height: 232px; overflow-y: auto; border: 1px solid var(--ni-line); border-radius: 6px; }
.ni-reqlist:empty { display: none; }
.ni-req {
    display: grid; grid-template-columns: auto minmax(0, 1fr) auto; align-items: center; gap: 0.65rem;
    padding: 0.55rem 0.75rem; border-top: 1px solid var(--ni-rule); cursor: pointer;
}
.ni-req:first-child { border-top: 0; }
.ni-req:hover, .ni-req.is-active { background: var(--ni-accent-soft); }
.ni-req-name { display: block; font-weight: 500; color: var(--ni-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ni-req-sub { display: block; font-size: 0.75rem; color: var(--ni-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ni-req-date { font-size: 0.75rem; color: var(--ni-faint); white-space: nowrap; }
.ni-req-none { padding: 0.7rem 0.75rem; font-size: 0.8rem; color: var(--ni-faint); cursor: default; }

.ni-kind { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.05rem 0.4rem; font-size: 0.68rem; font-weight: 600; border-radius: 4px; white-space: nowrap; }
.ni-kind::before { content: ""; width: 6px; height: 6px; border-radius: 2px; background: currentColor; }
.ni-kind--lab   { color: var(--pm-dk-fg-teal, #10404B); background: var(--pm-dk-bg-teal, #EBF2F4); }
.ni-kind--xray  { color: var(--pm-dk-fg-teal, #0A6E65); background: var(--pm-dk-bg-teal, #E3F4F1); }
.ni-kind--other { color: var(--pm-text-soft); background: var(--pm-subtle); }

.ni-linked {
    display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding: 0.65rem 0.8rem;
    background: var(--ni-accent-soft); border: 1px solid var(--pm-dk-edge-teal, #C5DCE0); border-radius: 6px;
}
.ni-linked-text { flex: 1; min-width: 0; }
.ni-linked-text b { display: block; font-weight: 600; color: var(--ni-ink); }
.ni-linked-text span { display: block; font-size: 0.78rem; color: var(--ni-muted); }

/* ---------- Service adder ---------- */
.ni-adder { display: flex; gap: 0.6rem; align-items: center; }
.ni-results {
    position: absolute; left: 0; right: 0; top: calc(100% + 4px); z-index: 20; margin: 0; padding: 0.3rem; list-style: none;
    max-height: 300px; overflow-y: auto; background: var(--pm-surface); border: 1px solid var(--ni-line); border-radius: 8px;
    box-shadow: 0 8px 24px rgba(20, 45, 54, 0.14);
}
.ni-result { display: grid; grid-template-columns: minmax(0, 1fr) auto auto; align-items: center; gap: 0.6rem; padding: 0.5rem 0.6rem; border-radius: 6px; cursor: pointer; }
.ni-result.is-active, .ni-result:hover { background: var(--ni-accent-soft); }
.ni-result-name { color: var(--ni-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ni-result-name mark { padding: 0; color: inherit; background: none; font-weight: 600; }
.ni-result-price { font-weight: 600; color: var(--ni-ink); font-variant-numeric: tabular-nums; }
.ni-result-added { font-size: 0.72rem; color: var(--ni-accent); }
.ni-result-none { padding: 0.6rem; font-size: 0.8rem; color: var(--ni-faint); }

.ni-seg, .ni-mini-seg { display: inline-flex; flex: none; gap: 2px; padding: 3px; background: var(--ni-subtle); border: 1px solid var(--ni-line); border-radius: 8px; }
.ni-seg-btn, .ni-mini-seg button {
    height: 30px; padding: 0 0.75rem; font: inherit; font-size: 0.78rem; font-weight: 600; color: var(--ni-muted);
    background: transparent; border: 0; border-radius: 6px; cursor: pointer;
}
.ni-mini-seg button { height: 30px; min-width: 38px; }
.ni-seg-btn:hover, .ni-mini-seg button:hover { color: var(--ni-ink); }
.ni-seg-btn.is-active, .ni-mini-seg button.is-active { color: #FFFFFF; background: #142D36; }

.ni-warn { display: flex; align-items: flex-start; gap: 0.5rem; margin-top: 0.75rem; padding: 0.6rem 0.75rem; font-size: 0.8rem; color: var(--pm-dk-fg-amber, #93370D); background: var(--pm-dk-bg-amber, #FEF6E7); border: 1px solid var(--pm-dk-edge-amber, #FEDF89); border-radius: 6px; }

/* ---------- Item rows ---------- */
.ni-items-head, .ni-item {
    display: grid; grid-template-columns: minmax(0, 1fr) 112px 120px 104px 34px; align-items: center; gap: 0.6rem;
}
.ni-items-head { margin-top: 1rem; padding: 0 0 0.45rem; font-size: 0.75rem; font-weight: 500; color: var(--ni-muted); border-bottom: 1px solid var(--ni-line); }
.ni-items-head span:nth-child(n+2):nth-child(-n+4) { text-align: right; }
.ni-items-head span:nth-child(2) { text-align: center; }
.ni-items { margin: 0; padding: 0; list-style: none; }
.ni-items:empty + .ni-items-empty { display: block; }
.ni-items-empty { display: none; padding: 1.5rem 1rem; text-align: center; border-bottom: 1px solid var(--ni-rule); }
.ni-items:not(:empty) + .ni-items-empty { display: none; }
.ni-items-empty p { margin: 0; font-weight: 500; color: var(--ni-ink); }
.ni-items-empty span { font-size: 0.8rem; color: var(--ni-faint); }

.ni-item { padding: 0.55rem 0; border-bottom: 1px solid var(--ni-rule); }
.ni-item.is-new { animation: ni-flash 0.9s ease; }
@keyframes ni-flash { from { background: var(--ni-accent-soft); } to { background: transparent; } }
.ni-item-name { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }
.ni-item-name input {
    width: 100%; height: 32px; padding: 0 0.5rem; font: inherit; font-size: 0.86rem; font-weight: 500; color: var(--ni-ink);
    background: transparent; border: 1px solid transparent; border-radius: 6px;
}
.ni-item-name input:hover { border-color: var(--ni-line); }
.ni-item-name input:focus { outline: none; background: var(--pm-surface); border-color: var(--ni-accent); box-shadow: 0 0 0 3px rgba(23, 85, 99, 0.14); }
.ni-item-meta { display: flex; align-items: center; gap: 0.35rem; padding-left: 0.5rem; }
.ni-item-meta select {
    height: 22px; padding: 0 1.2rem 0 0.4rem; font: inherit; font-size: 0.7rem; font-weight: 600; border: 0; border-radius: 4px; cursor: pointer; appearance: none;
    background: #F2F4F7 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%235D6E80'%3E%3Cpath d='M4.5 6.5 8 10l3.5-3.5z'/%3E%3C/svg%3E") no-repeat right 0.15rem center / 12px;
    color: var(--pm-text-soft);
}
.ni-item-meta select.is-lab  { background-color: var(--pm-dk-bg-teal, #EBF2F4); color: var(--pm-dk-fg-teal, #10404B); }
.ni-item-meta select.is-xray { background-color: var(--pm-dk-bg-teal, #E3F4F1); color: var(--pm-dk-fg-teal, #0A6E65); }
.ni-item-unpriced { font-size: 0.72rem; font-weight: 600; color: var(--pm-dk-fg-amber, #B54708); }

.ni-stepper { display: inline-flex; align-items: center; justify-self: center; border: 1px solid var(--ni-strong); border-radius: 6px; overflow: hidden; }
.ni-stepper button { width: 28px; height: 30px; font-size: 0.9rem; color: var(--ni-text); background: var(--ni-subtle); border: 0; cursor: pointer; }
.ni-stepper button:hover { color: var(--ni-ink); background: var(--ni-rule); }
.ni-stepper input { width: 40px; height: 30px; padding: 0; font: inherit; font-size: 0.85rem; text-align: center; color: var(--ni-ink); border: 0; border-left: 1px solid var(--ni-line); border-right: 1px solid var(--ni-line); -moz-appearance: textfield; }
.ni-stepper input::-webkit-outer-spin-button, .ni-stepper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

.ni-price { position: relative; }
.ni-price i { position: absolute; left: 0.55rem; top: 50%; transform: translateY(-50%); font-style: normal; font-size: 0.82rem; color: var(--ni-muted); }
.ni-price input { width: 100%; height: 32px; padding: 0 0.55rem 0 1.4rem; font: inherit; font-size: 0.86rem; text-align: right; color: var(--ni-ink); background: var(--pm-surface); border: 1px solid var(--ni-strong); border-radius: 6px; font-variant-numeric: tabular-nums; }
.ni-price input:focus { outline: none; border-color: var(--ni-accent); box-shadow: 0 0 0 3px rgba(23, 85, 99, 0.14); }
.ni-item.is-unpriced .ni-price input { border-color: #F79009; background: var(--pm-dk-bg-amber, #FFFAEB); }
.ni-amount { text-align: right; font-weight: 600; color: var(--ni-ink); font-variant-numeric: tabular-nums; white-space: nowrap; }
.ni-remove { width: 30px; height: 30px; font-size: 0.85rem; color: var(--ni-faint); background: none; border: 0; border-radius: 6px; cursor: pointer; }
.ni-remove:hover { color: var(--pm-dk-fg-red, #B42318); background: var(--pm-dk-bg-red, #FEF3F2); }

.ni-link-btn { display: inline-flex; align-items: center; gap: 0.4rem; margin-top: 0.8rem; padding: 0; font: inherit; font-size: 0.82rem; font-weight: 600; color: var(--ni-accent); background: none; border: 0; cursor: pointer; }
.ni-link-btn:hover { color: var(--ni-accent-dark); text-decoration: underline; text-underline-offset: 2px; }

/* ---------- Discount ---------- */
.ni-choices { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.6rem; }
.ni-choice {
    display: flex; flex-direction: column; align-items: flex-start; gap: 0.15rem; padding: 0.7rem 0.85rem; text-align: left;
    font: inherit; background: var(--pm-surface); border: 1px solid var(--ni-strong); border-radius: 8px; cursor: pointer;
}
.ni-choice b { font-size: 0.86rem; font-weight: 600; color: var(--ni-ink); }
.ni-choice span { font-size: 0.75rem; color: var(--ni-muted); }
.ni-choice:hover { border-color: var(--ni-accent); }
.ni-choice.is-active { background: var(--ni-accent-soft); border-color: var(--ni-accent); box-shadow: inset 0 0 0 1px var(--ni-accent); }
.ni-custom { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.75rem; margin-top: 0.9rem; }
.ni-custom .ni-field:nth-child(2) { width: 130px; }
.ni-field--grow { flex: 1; min-width: 200px; }
.ni-error--row { flex-basis: 100%; margin-top: 0; }
.ni-note { margin-top: 0.25rem; }
.ni-note #noteBox { margin-top: 0.75rem; }
.ni-chars { display: block; margin-top: 0.25rem; font-size: 0.72rem; text-align: right; color: var(--ni-faint); }

/* ---------- Summary ---------- */
.ni-sum-patient { margin: 0 0 0.75rem; font-size: 1rem; font-weight: 600; color: var(--ni-ink); }
.ni-sum-patient small { display: block; margin-top: 0.15rem; font-size: 0.76rem; font-weight: 400; color: var(--ni-muted); }
.ni-sum-lines { margin: 0; padding: 0; list-style: none; max-height: 220px; overflow-y: auto; border-top: 1px solid var(--ni-rule); }
.ni-sum-lines:empty { display: none; }
.ni-sum-lines li { display: flex; justify-content: space-between; gap: 0.75rem; padding: 0.45rem 0; font-size: 0.82rem; border-bottom: 1px solid var(--ni-rule); }
.ni-sum-lines li span:first-child { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--ni-text); }
.ni-sum-lines li span:last-child { font-variant-numeric: tabular-nums; color: var(--ni-ink); }
.ni-sum-totals { margin: 0.6rem 0 0; }
.ni-sum-totals > div { display: flex; justify-content: space-between; gap: 1rem; padding: 0.2rem 0; font-size: 0.85rem; }
.ni-sum-totals dt { color: var(--ni-muted); }
.ni-sum-totals dd { margin: 0; color: var(--ni-ink); font-variant-numeric: tabular-nums; }
.ni-sum-grand { margin-top: 0.4rem; padding-top: 0.6rem !important; border-top: 1px solid var(--ni-strong); }
.ni-sum-grand dt { font-weight: 500; color: var(--ni-ink); }
.ni-sum-grand dd { font-size: 1.6rem; font-weight: 600; line-height: 1.1; letter-spacing: -0.02em; }

.ni-checks { margin: 0.9rem 0; padding: 0; list-style: none; }
.ni-checks li { display: flex; align-items: center; gap: 0.5rem; padding: 0.15rem 0; font-size: 0.8rem; color: var(--ni-muted); }
.ni-checks li::before { content: ""; flex: none; width: 14px; height: 14px; border: 1.5px solid var(--ni-strong); border-radius: 50%; }
.ni-checks li.is-done { color: var(--ni-ink); }
.ni-checks li.is-done::before { border-color: #17B26A; background: #17B26A url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='%23fff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M4 8.5 7 11l5-6'/%3E%3C/svg%3E") center / 10px no-repeat; }
.ni-checks li.is-warn { color: var(--pm-dk-fg-amber, #B54708); }
.ni-checks li.is-warn::before { border-color: #F79009; }

/* ---------- Buttons ---------- */
.ni-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; height: 38px; padding: 0 0.95rem;
    font: inherit; font-size: 0.85rem; font-weight: 600; color: var(--ni-ink); background: var(--pm-surface); border: 1px solid var(--ni-line);
    border-radius: 6px; text-decoration: none; white-space: nowrap; cursor: pointer; transition: background-color 0.15s ease, border-color 0.15s ease;
}
.ni-btn:hover { color: var(--ni-ink); background: var(--ni-subtle); border-color: var(--ni-strong); }
.ni-btn--sm { height: 30px; padding: 0 0.7rem; font-size: 0.78rem; }
.ni-btn--block { width: 100%; }
.ni-btn--block + .ni-btn--block { margin-top: 0.5rem; }
.ni-btn--primary { color: #FFFFFF; background: var(--ni-accent); border-color: var(--ni-accent); }
.ni-btn--primary:hover { color: #FFFFFF; background: var(--ni-accent-dark); border-color: var(--ni-accent-dark); }
.ni-btn--primary:disabled { opacity: 0.6; cursor: progress; }
.ni-btn--quiet { color: var(--ni-muted); background: transparent; border-color: transparent; }

/* ---------- Responsive ---------- */
@media (max-width: 1050px) {
    .ni-grid { grid-template-columns: minmax(0, 1fr); }
    .ni-side { position: static; }
}
@media (max-width: 680px) {
    .ni-row-2 { grid-template-columns: minmax(0, 1fr); }
    .ni-adder { flex-direction: column; align-items: stretch; }
    .ni-choices { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ni-items-head { display: none; }
    .ni-item { grid-template-columns: minmax(0, 1fr) auto; grid-template-areas: "name remove" "qty price" "qty amount"; row-gap: 0.4rem; }
    .ni-item-name { grid-area: name; }
    .ni-item .ni-stepper { grid-area: qty; justify-self: start; }
    .ni-item .ni-price { grid-area: price; }
    .ni-item .ni-amount { grid-area: amount; }
    .ni-item .ni-remove { grid-area: remove; }
    .ni-kbd { display: none; }
}
@media (prefers-reduced-motion: reduce) { .ni-item.is-new { animation: none; } }
</style>

<script>
(function () {
    'use strict';

    var $    = function (id) { return document.getElementById(id); };
    var peso = function (v) { return '₱' + (Number(v) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    var esc  = function (s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); };

    var CATALOGUE = <?= json_encode($catalogue, $jsonFlags) ?>;
    var PRICE_MAP = <?= json_encode($priceMap, $jsonFlags) ?>;
    var REQUESTS  = <?= json_encode($requestsData, $jsonFlags) ?>;

    var form      = $('invoiceForm');
    var list      = $('itemsList');
    var nameInput = $('f_patient_name');
    var codeInput = $('f_patient_code');

    /* ---------- Helpers ---------- */
    function kindOf(category) {
        var c = String(category || '').toLowerCase().trim();
        if (c === 'xray' || c === 'x-ray') { return 'xray'; }
        if (c === 'laboratory' || c === 'lab') { return 'lab'; }
        return 'other';
    }
    function categoryFor(kind) { return kind === 'xray' ? 'xray' : (kind === 'lab' ? 'laboratory' : ''); }

    function normaliseName(name) {
        return String(name == null ? '' : name).replace(/\\\//g, '/').replace(/\\/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    /* Catalogue price for a requested service name: exact, then prefix, then contains */
    function resolvePrice(rawName) {
        var key = normaliseName(rawName);
        if (!key) { return null; }
        if (PRICE_MAP[key]) { return PRICE_MAP[key]; }
        var keys = Object.keys(PRICE_MAP), i;
        for (i = 0; i < keys.length; i++) { if (keys[i].indexOf(key) === 0) { return PRICE_MAP[keys[i]]; } }
        for (i = 0; i < keys.length; i++) { if (keys[i].indexOf(key) !== -1) { return PRICE_MAP[keys[i]]; } }
        for (i = 0; i < keys.length; i++) { if (key.indexOf(keys[i]) !== -1) { return PRICE_MAP[keys[i]]; } }
        return null;
    }

    /* ---------- Item rows (items[i][service_name|category|qty|price]) ---------- */
    var nextIdx = 0;

    function rowFor(name) {
        var key = normaliseName(name), found = null;
        list.querySelectorAll('.ni-item').forEach(function (li) {
            if (!found && normaliseName(li.querySelector('[data-f=name]').value) === key) { found = li; }
        });
        return found;
    }

    function addRow(data, opts) {
        data = data || {}; opts = opts || {};
        var idx  = nextIdx++;
        var kind = kindOf(data.category);
        var li   = document.createElement('li');
        li.className = 'ni-item';
        li.innerHTML =
            '<div class="ni-item-name">' +
                '<input type="text" data-f="name" name="items[' + idx + '][service_name]" value="' + esc(data.name || '') + '" placeholder="Service name" aria-label="Service name" maxlength="150">' +
                '<span class="ni-item-meta">' +
                    '<select data-f="category" name="items[' + idx + '][category]" aria-label="Department" class="is-' + kind + '">' +
                        '<option value="laboratory"' + (kind === 'lab' ? ' selected' : '') + '>Lab</option>' +
                        '<option value="xray"' + (kind === 'xray' ? ' selected' : '') + '>X-ray</option>' +
                        '<option value=""' + (kind === 'other' ? ' selected' : '') + '>Other</option>' +
                    '</select>' +
                    '<span class="ni-item-unpriced" hidden>No price set</span>' +
                '</span>' +
            '</div>' +
            '<div class="ni-stepper">' +
                '<button type="button" data-step="-1" aria-label="One less">−</button>' +
                '<input type="number" data-f="qty" name="items[' + idx + '][qty]" value="' + (data.qty || 1) + '" min="1" step="1" inputmode="numeric" aria-label="Quantity">' +
                '<button type="button" data-step="1" aria-label="One more">+</button>' +
            '</div>' +
            '<label class="ni-price"><i aria-hidden="true">₱</i>' +
                '<input type="number" data-f="price" name="items[' + idx + '][price]" value="' + (Number(data.price) || 0).toFixed(2) + '" min="0" step="0.01" inputmode="decimal" aria-label="Unit price">' +
            '</label>' +
            '<span class="ni-amount" data-f="amount"></span>' +
            '<button type="button" class="ni-remove" data-remove aria-label="Remove this service"><i class="bi bi-x-lg" aria-hidden="true"></i></button>';
        list.appendChild(li);
        if (opts.flash !== false) { flash(li); }
        recalc();
        return li;
    }

    function flash(li) {
        li.classList.remove('is-new');
        void li.offsetWidth;
        li.classList.add('is-new');
    }

    /* Adding a service already on the invoice raises its quantity instead */
    function addService(svc) {
        var existing = rowFor(svc.name);
        if (existing) {
            var q = existing.querySelector('[data-f=qty]');
            q.value = (parseInt(q.value, 10) || 0) + 1;
            flash(existing);
            recalc();
            return existing;
        }
        return addRow({ name: svc.name, category: svc.category, qty: 1, price: svc.charge });
    }

    list.addEventListener('input', function (e) {
        if (e.target.matches('[data-f=category]')) { e.target.className = 'is-' + kindOf(e.target.value); }
        recalc();
    });
    list.addEventListener('change', function (e) {
        if (e.target.matches('[data-f=category]')) { e.target.className = 'is-' + kindOf(e.target.value); }
        if (e.target.matches('[data-f=qty]') && (parseInt(e.target.value, 10) || 0) < 1) { e.target.value = 1; recalc(); }
    });
    list.addEventListener('click', function (e) {
        var step = e.target.closest('[data-step]');
        if (step) {
            var q = step.parentNode.querySelector('[data-f=qty]');
            q.value = Math.max(1, (parseInt(q.value, 10) || 1) + parseInt(step.dataset.step, 10));
            recalc();
            return;
        }
        if (e.target.closest('[data-remove]')) {
            e.target.closest('.ni-item').remove();
            recalc();
        }
    });

    /* ---------- Totals, summary and checklist ---------- */
    var discount = { type: 'none', value: 0, reason: '' };

    function recalc() {
        var rows = list.querySelectorAll('.ni-item'), subtotal = 0, unpriced = 0, lines = '';
        rows.forEach(function (li) {
            var qty   = parseFloat(li.querySelector('[data-f=qty]').value) || 0;
            var price = parseFloat(li.querySelector('[data-f=price]').value) || 0;
            var name  = li.querySelector('[data-f=name]').value.trim();
            var line  = qty * price;
            subtotal += line;
            li.querySelector('[data-f=amount]').textContent = peso(line);
            li.classList.toggle('is-unpriced', price <= 0);
            li.querySelector('.ni-item-unpriced').hidden = price > 0;
            if (price <= 0) { unpriced++; }
            lines += '<li><span>' + esc(name || 'Unnamed service') + (qty !== 1 ? ' × ' + qty : '') + '</span><span>' + peso(line) + '</span></li>';
        });

        var disc = 0;
        if (discount.type === 'percent')    { disc = subtotal * Math.min(100, Math.max(0, discount.value)) / 100; }
        else if (discount.type === 'fixed') { disc = Math.min(subtotal, Math.max(0, discount.value)); }
        var total = Math.max(0, subtotal - disc);

        $('rowCount').textContent = rows.length;
        $('sumLines').innerHTML = lines;
        document.querySelector('[data-subtotal]').textContent = peso(subtotal);
        document.querySelector('[data-total]').textContent = peso(total);
        $('sumDiscountRow').hidden = disc <= 0;
        $('sumDiscount').textContent = '−' + peso(disc);
        $('sumDiscountLabel').textContent = 'Discount' + (discount.reason ? ' (' + discount.reason + ')' : '');

        var name = nameInput.value.trim(), code = codeInput.value.trim();
        $('sumPatient').innerHTML = name
            ? esc(name) + (code ? '<small>' + esc(code) + '</small>' : '')
            : '<span class="ni-faint">No patient yet</span>';

        setCheck('patient', name !== '');
        setCheck('items', rows.length > 0);
        var prices = document.querySelector('[data-check=prices]');
        prices.classList.toggle('is-done', rows.length > 0 && unpriced === 0);
        prices.classList.toggle('is-warn', unpriced > 0);
        prices.textContent = unpriced > 0 ? unpriced + ' service' + (unpriced === 1 ? ' has' : 's have') + ' no price' : 'Every service has a price';

        if (rows.length) { $('itemsError').textContent = ''; }
    }

    function setCheck(key, ok) { document.querySelector('[data-check=' + key + ']').classList.toggle('is-done', ok); }

    nameInput.addEventListener('input', function () { nameInput.classList.remove('is-invalid'); $('nameError').textContent = ''; recalc(); });
    codeInput.addEventListener('input', recalc);

    /* ---------- Service search ---------- */
    var search   = $('serviceSearch');
    var results  = $('serviceResults');
    var kindPick = 'all';
    var matches  = [];
    var activeAt = 0;

    function highlight(name, q) {
        var i = name.toLowerCase().indexOf(q);
        if (!q || i < 0) { return esc(name); }
        return esc(name.slice(0, i)) + '<mark>' + esc(name.slice(i, i + q.length)) + '</mark>' + esc(name.slice(i + q.length));
    }

    function renderResults() {
        var q = search.value.trim().toLowerCase();
        matches = CATALOGUE.filter(function (s) {
            return (kindPick === 'all' || s.kind === kindPick) && (!q || s.name.toLowerCase().indexOf(q) !== -1);
        }).sort(function (a, b) {
            var ai = a.name.toLowerCase().indexOf(q), bi = b.name.toLowerCase().indexOf(q);
            return (ai === 0 ? 0 : 1) - (bi === 0 ? 0 : 1) || a.name.localeCompare(b.name);
        }).slice(0, 12);
        activeAt = 0;
        if (!matches.length) {
            results.innerHTML = '<li class="ni-result-none">No service matches “' + esc(search.value.trim()) + '”. Use “Add a service that is not in the list” below.</li>';
        } else {
            results.innerHTML = matches.map(function (s, i) {
                return '<li class="ni-result' + (i === 0 ? ' is-active' : '') + '" role="option" data-i="' + i + '">' +
                    '<span class="ni-result-name">' + highlight(s.name, q) + '</span>' +
                    '<span class="ni-kind ni-kind--' + s.kind + '">' + (s.kind === 'xray' ? 'X-ray' : s.kind === 'lab' ? 'Lab' : 'Other') + '</span>' +
                    '<span class="ni-result-price">' + (s.charge > 0 ? peso(s.charge) : '—') + '</span></li>';
            }).join('');
        }
        results.hidden = false;
        search.setAttribute('aria-expanded', 'true');
    }

    function closeResults() { results.hidden = true; search.setAttribute('aria-expanded', 'false'); }

    function moveActive(step) {
        if (!matches.length) { return; }
        activeAt = (activeAt + step + matches.length) % matches.length;
        results.querySelectorAll('.ni-result').forEach(function (li, i) {
            li.classList.toggle('is-active', i === activeAt);
            if (i === activeAt) { li.scrollIntoView({ block: 'nearest' }); }
        });
    }

    function pick(i) {
        var s = matches[i];
        if (!s) { return; }
        addService(s);
        search.value = '';
        renderResults();
        search.focus();
    }

    search.addEventListener('focus', renderResults);
    search.addEventListener('input', renderResults);
    search.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); if (results.hidden) { renderResults(); } else { moveActive(1); } }
        else if (e.key === 'ArrowUp') { e.preventDefault(); moveActive(-1); }
        else if (e.key === 'Enter') { e.preventDefault(); pick(activeAt); }
        else if (e.key === 'Escape') { closeResults(); }
    });
    results.addEventListener('mousedown', function (e) {
        var li = e.target.closest('.ni-result');
        if (li) { e.preventDefault(); pick(parseInt(li.dataset.i, 10)); }
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.ni-search--grow')) { closeResults(); }
        if (!e.target.closest('#requestPicker')) { closeRequests(); }
    });

    document.querySelectorAll('.ni-seg-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            kindPick = btn.dataset.kind;
            document.querySelectorAll('.ni-seg-btn').forEach(function (b) {
                b.classList.toggle('is-active', b === btn);
                b.setAttribute('aria-checked', b === btn ? 'true' : 'false');
            });
            search.focus();
            renderResults();
        });
    });

    /* "/" jumps to the service search; Alt+N adds a custom line */
    document.addEventListener('keydown', function (e) {
        var typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName);
        if (e.key === '/' && !typing) { e.preventDefault(); search.focus(); }
        if (e.altKey && (e.key === 'n' || e.key === 'N')) { e.preventDefault(); addCustom(); }
    });

    function addCustom() {
        var li = addRow({ name: '', category: kindPick === 'xray' ? 'xray' : 'laboratory', qty: 1, price: 0 });
        li.querySelector('[data-f=name]').focus();
    }
    $('addCustomLine').addEventListener('click', addCustom);

    /* ---------- Open request picker ---------- */
    var reqSearch = $('requestSearch');
    var reqList   = $('requestList');
    var reqAt     = 0;
    var reqShown  = [];

    function renderRequests() {
        if (!reqSearch) { return; }
        var q = reqSearch.value.trim().toLowerCase();
        reqShown = REQUESTS.filter(function (r) {
            return !q || r.name.toLowerCase().indexOf(q) !== -1 || r.code.toLowerCase().indexOf(q) !== -1;
        }).slice(0, 30);
        reqAt = 0;
        reqList.innerHTML = reqShown.length
            ? reqShown.map(function (r, i) {
                return '<li class="ni-req' + (i === 0 ? ' is-active' : '') + '" role="option" data-i="' + i + '">' +
                    '<span class="ni-kind ni-kind--' + r.type + '">' + (r.type === 'xray' ? 'X-ray' : 'Lab') + '</span>' +
                    '<span><span class="ni-req-name">' + esc(r.name || 'Unknown patient') + '</span>' +
                    '<span class="ni-req-sub">' + (r.code ? esc(r.code) + ' · ' : '') + r.services.length + ' service' + (r.services.length === 1 ? '' : 's') +
                    (r.services.length ? ': ' + esc(r.services.join(', ')) : '') + '</span></span>' +
                    '<span class="ni-req-date">' + esc(r.date) + '</span></li>';
            }).join('')
            : '<li class="ni-req-none">No open request matches. Type the patient below instead.</li>';
        reqSearch.setAttribute('aria-expanded', 'true');
    }

    function closeRequests() { if (reqList) { reqList.innerHTML = ''; reqSearch.setAttribute('aria-expanded', 'false'); } }

    function chooseRequest(r) {
        nameInput.value = r.name;
        codeInput.value = r.code;
        $('f_request_id').value     = r.id;
        $('f_request_type').value   = r.type;
        $('f_appointment_id').value = r.appt || '';

        var kind = $('linkedKind');
        kind.className = 'ni-kind ni-kind--' + r.type;
        kind.textContent = r.type === 'xray' ? 'X-ray' : 'Lab';
        $('linkedTitle').textContent = (r.type === 'xray' ? 'X-ray' : 'Lab') + ' request #' + r.id;
        $('linkedSub').textContent = r.services.length + ' service' + (r.services.length === 1 ? '' : 's') + ' brought in' + (r.date ? ' · requested ' + r.date : '');
        $('linkedBox').hidden = false;
        $('requestPicker').hidden = true;

        /* The request decides the services: replace the list with its services at catalogue price */
        list.innerHTML = '';
        var unmatched = [];
        r.services.forEach(function (raw) {
            var hit = null;
            for (var i = 0; i < CATALOGUE.length; i++) { if (CATALOGUE[i].name === raw) { hit = CATALOGUE[i]; break; } }
            if (!hit) {
                var m = resolvePrice(raw);
                if (m) { hit = { name: raw, category: m.category, charge: m.charge }; }
            }
            if (!hit) { unmatched.push(raw); }
            addRow({ name: raw, category: hit ? hit.category : (r.type === 'xray' ? 'xray' : 'laboratory'), qty: 1, price: hit ? hit.charge : 0 }, { flash: false });
        });

        $('unmatchedText').innerHTML = unmatched.length
            ? '<b>' + unmatched.length + '</b> service' + (unmatched.length === 1 ? ' is' : 's are') + ' not in the catalogue and came in at ₱0.00. Set ' + (unmatched.length === 1 ? 'its price' : 'their prices') + ' before saving.'
            : '';
        $('unmatchedHint').hidden = unmatched.length === 0;
        recalc();
    }

    if (reqSearch) {
        reqSearch.addEventListener('focus', renderRequests);
        reqSearch.addEventListener('input', renderRequests);
        reqSearch.addEventListener('keydown', function (e) {
            var items = reqList.querySelectorAll('.ni-req');
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!items.length) { renderRequests(); return; }
                reqAt = (reqAt + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
                items.forEach(function (li, i) { li.classList.toggle('is-active', i === reqAt); if (i === reqAt) { li.scrollIntoView({ block: 'nearest' }); } });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (reqShown[reqAt]) { chooseRequest(reqShown[reqAt]); }
            } else if (e.key === 'Escape') { closeRequests(); }
        });
        reqList.addEventListener('mousedown', function (e) {
            var li = e.target.closest('.ni-req');
            if (li && li.dataset.i != null) { e.preventDefault(); chooseRequest(reqShown[parseInt(li.dataset.i, 10)]); }
        });
    }

    /* "Change": unlink the request; the patient and services stay for editing */
    $('unlinkBtn').addEventListener('click', function () {
        $('f_request_id').value = '';
        $('f_request_type').value = '';
        $('f_appointment_id').value = '';
        $('linkedBox').hidden = true;
        $('unmatchedHint').hidden = true;
        if ($('requestPicker')) { $('requestPicker').hidden = false; reqSearch.value = ''; reqSearch.focus(); }
    });

    /* ---------- Discount ---------- */
    var PRESETS = { none: { type: 'none', value: 0, reason: '' }, senior: { type: 'percent', value: 20, reason: 'Senior citizen' }, pwd: { type: 'percent', value: 20, reason: 'PWD' } };
    var customType = 'percent';

    function applyDiscount(d) {
        discount = d;
        $('f_discount_type').value   = d.type;
        $('f_discount_value').value  = d.value;
        $('f_discount_reason').value = d.reason;
        recalc();
    }

    function readCustom() {
        var v = parseFloat($('customValue').value) || 0;
        var err = '';
        if (v < 0) { err = 'A discount cannot be negative.'; }
        else if (customType === 'percent' && v > 100) { err = 'A percentage cannot be more than 100%.'; }
        $('discountError').textContent = err;
        $('customValue').classList.toggle('is-invalid', err !== '');
        applyDiscount({ type: v > 0 && !err ? customType : 'none', value: err ? 0 : v, reason: $('customReason').value.trim() });
        return err === '';
    }

    document.querySelectorAll('.ni-choice').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var key = btn.dataset.discount;
            document.querySelectorAll('.ni-choice').forEach(function (b) {
                b.classList.toggle('is-active', b === btn);
                b.setAttribute('aria-checked', b === btn ? 'true' : 'false');
            });
            $('customDiscount').hidden = key !== 'custom';
            if (key === 'custom') { readCustom(); $('customValue').focus(); }
            else { $('discountError').textContent = ''; applyDiscount(PRESETS[key]); }
        });
    });

    document.querySelectorAll('.ni-mini-seg button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            customType = btn.dataset.ctype;
            document.querySelectorAll('.ni-mini-seg button').forEach(function (b) {
                b.classList.toggle('is-active', b === btn);
                b.setAttribute('aria-checked', b === btn ? 'true' : 'false');
            });
            readCustom();
        });
    });
    $('customValue').addEventListener('input', readCustom);
    $('customReason').addEventListener('input', readCustom);

    /* ---------- Note ---------- */
    var notes = $('f_notes');
    $('noteToggle').addEventListener('click', function () {
        $('noteBox').hidden = false;
        this.hidden = true;
        this.setAttribute('aria-expanded', 'true');
        notes.focus();
    });
    notes.addEventListener('input', function () { $('notesCount').textContent = notes.value.length + ' / 500'; });

    /* ---------- Save ---------- */
    var submitted = false;
    form.addEventListener('submit', function (e) {
        if (submitted) { e.preventDefault(); return; }

        /* Drop rows left without a name, so a stray empty line never blocks the save */
        list.querySelectorAll('.ni-item').forEach(function (li) {
            if (!li.querySelector('[data-f=name]').value.trim()) { li.remove(); }
        });
        recalc();

        if (!nameInput.value.trim()) {
            e.preventDefault();
            nameInput.classList.add('is-invalid');
            $('nameError').textContent = 'Enter the patient’s name.';
            nameInput.focus();
            return;
        }
        if (!list.querySelector('.ni-item')) {
            e.preventDefault();
            $('itemsError').textContent = 'Add at least one service.';
            search.focus();
            return;
        }
        if (!$('customDiscount').hidden && !readCustom()) {
            e.preventDefault();
            $('customValue').focus();
            return;
        }

        var zero = [];
        list.querySelectorAll('.ni-item.is-unpriced').forEach(function (li) { zero.push(li.querySelector('[data-f=name]').value.trim()); });
        if (zero.length && !confirm('These services have no price (₱0.00):\n\n· ' + zero.join('\n· ') + '\n\nCreate the invoice anyway?')) {
            e.preventDefault();
            return;
        }

        submitted = true;
        var btn = $('submitInvoice');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split" aria-hidden="true"></i>Saving…';
    });

    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) { return; }
        submitted = false;
        var btn = $('submitInvoice');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i>Create invoice';
    });

    recalc();
}());
</script>

<?= $this->endSection() ?>
