<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Payments<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<?php
    $payments = (isset($payments) && is_array($payments)) ? $payments : [];

    // Counts for the tab strip
    $methodCounts = [];
    $statusCounts = [];
    foreach ($payments as $p) {
        $m = strtolower((string) ($p['payment_method'] ?? ''));
        $s = strtolower((string) ($p['payment_status'] ?? ''));
        if ($m !== '') { $methodCounts[$m] = ($methodCounts[$m] ?? 0) + 1; }
        if ($s !== '') { $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1; }
    }

    $initialsOf = static function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return mb_strtoupper($first . $last);
    };

    // PNG stat-card icon filenames. Change any of these four strings
    // to point at your own PNGs. Files must live in:
    //   public/assets/images/
    $statIcons = [
        'collections' => 'money.png',
        'payments'    => 'wallet.png',
        'patients'    => 'multiple-users-silhouette.png',
        'monthly'     => 'profit.png',
    ];

    // PNG avatar filenames inside public/assets/images/.
    // Change these two strings if your files are named differently.
    $maleAvatar   = 'man-avatar.png';
    $femaleAvatar = 'woman-avatar.png';
?>

<div class="pm">

    <!-- PAGE HEADER -->
    <header class="pm-head">
        <div>
            <h2 class="pm-title">Payment processing</h2>
            <p class="pm-lede">
                <?php if (count($payments) > 0): ?>
                    <strong><?= number_format(count($payments)) ?></strong> recorded
                    <span aria-hidden="true">·</span>
                    <strong><?= number_format($today_count ?? 0) ?></strong> today
                    <span aria-hidden="true">·</span>
                    <strong>&#8369;<?= number_format($today_total ?? 0, 2) ?></strong> collected today
                <?php else: ?>
                    No payments recorded yet.
                <?php endif; ?>
            </p>
        </div>

        <div class="pm-head-actions">
            <button type="button" class="pm-btn" onclick="exportPayments()">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span>Export</span>
            </button>
        </div>
    </header>


    <!-- STATS -->
    <div class="pm-stats">
        <div class="pm-stat">
            <div class="pm-stat-icon">
                <img src="<?= esc(base_url('assets/images/' . $statIcons['collections']), 'attr') ?>"
                     alt=""
                     class="pm-stat-img"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="pm-stat-body">
                <div class="pm-stat-value">&#8369;<?= number_format($today_total ?? 0, 2) ?></div>
                <div class="pm-stat-label">Today's collections</div>
                <div class="pm-stat-sub">Total payments today</div>
            </div>
        </div>

        <div class="pm-stat">
            <div class="pm-stat-icon">
                <img src="<?= esc(base_url('assets/images/' . $statIcons['payments']), 'attr') ?>"
                     alt=""
                     class="pm-stat-img"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="pm-stat-body">
                <div class="pm-stat-value"><?= number_format($today_count ?? 0) ?></div>
                <div class="pm-stat-label">Today's payments</div>
                <div class="pm-stat-sub">Number of payments today</div>
            </div>
        </div>

        <div class="pm-stat">
            <div class="pm-stat-icon">
                <img src="<?= esc(base_url('assets/images/' . $statIcons['patients']), 'attr') ?>"
                     alt=""
                     class="pm-stat-img"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="pm-stat-body">
                <div class="pm-stat-value"><?= number_format($total_patients ?? 0) ?></div>
                <div class="pm-stat-label">Total patients</div>
                <div class="pm-stat-sub">Unique patients</div>
            </div>
        </div>

        <div class="pm-stat">
            <div class="pm-stat-icon">
                <img src="<?= esc(base_url('assets/images/' . $statIcons['monthly']), 'attr') ?>"
                     alt=""
                     class="pm-stat-img"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="pm-stat-body">
                <div class="pm-stat-value">&#8369;<?= number_format($monthly_total ?? 0, 2) ?></div>
                <div class="pm-stat-label">This month</div>
                <div class="pm-stat-sub">Monthly collections</div>
            </div>
        </div>
    </div>


    <!-- TABLE -->
    <section class="pm-panel">

        <div class="pm-tabs" role="group" aria-label="Filter by status">
            <button type="button" class="pm-tab is-active" data-status="all" aria-pressed="true">
                All
                <span class="pm-tab-count"><?= (int) count($payments) ?></span>
            </button>
            <button type="button" class="pm-tab" data-status="paid" aria-pressed="false">
                Paid
                <span class="pm-tab-count"><?= (int) ($statusCounts['paid'] ?? 0) ?></span>
            </button>
            <button type="button" class="pm-tab" data-status="pending" aria-pressed="false">
                Pending
                <span class="pm-tab-count"><?= (int) ($statusCounts['pending'] ?? 0) ?></span>
            </button>
            <button type="button" class="pm-tab" data-status="refunded" aria-pressed="false">
                Refunded
                <span class="pm-tab-count"><?= (int) ($statusCounts['refunded'] ?? 0) ?></span>
            </button>
            <button type="button" class="pm-tab" data-status="failed" aria-pressed="false">
                Failed
                <span class="pm-tab-count"><?= (int) ($statusCounts['failed'] ?? 0) ?></span>
            </button>

            <!-- The original filter element, kept so existing JS still works -->
            <select class="pm-visually-hidden" id="filterStatus" aria-hidden="true" tabindex="-1">
                <option value="">All Status</option>
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
                <option value="refunded">Refunded</option>
                <option value="failed">Failed</option>
            </select>
        </div>

        <div class="pm-filters">
            <div class="pm-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label class="visually-hidden" for="searchPayments">Search payments</label>
                <input type="text"
                       class="pm-input"
                       id="searchPayments"
                       placeholder="Search by patient, reference or service"
                       autocomplete="off">
            </div>

            <label class="visually-hidden" for="filterMethod">Payment method</label>
            <select class="pm-select" id="filterMethod">
                <option value="">All methods</option>
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="gcash">GCash</option>
                <option value="paymaya">PayMaya</option>
                <option value="insurance">Insurance</option>
            </select>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="pm-alert pm-alert--success">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <span><?= esc(session()->getFlashdata('success')) ?></span>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="pm-alert pm-alert--error">
                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                <span><?= esc(session()->getFlashdata('error')) ?></span>
            </div>
        <?php endif; ?>

        <div class="pm-table-wrap">
            <table class="pm-table" id="paymentsTable">
                <thead>
                    <tr>
                        <th scope="col">Reference</th>
                        <th scope="col">Patient</th>
                        <th scope="col">Services</th>
                        <th scope="col" class="pm-num">Amount</th>
                        <th scope="col">Method</th>
                        <th scope="col">Date</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="pm-c-actions"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody id="paymentsTableBody">
                    <?php if (count($payments) > 0): ?>
                        <?php foreach ($payments as $payment):
                            $payId     = (int) ($payment['id'] ?? 0);
                            $ref       = (string) ($payment['reference_number'] ?? '');
                            $name      = (string) ($payment['patient_name'] ?? 'Unknown');
                            $code      = (string) ($payment['patient_code'] ?? 'N/A');
                            $method    = (string) ($payment['payment_method'] ?? '');
                            $status    = (string) ($payment['payment_status'] ?? 'pending');
                            $total     = (float) ($payment['total_amount'] ?? 0);
                            $change    = (float) ($payment['change_amount'] ?? 0);
                            $dateTs    = !empty($payment['payment_date']) ? strtotime($payment['payment_date']) : false;

                            // Parse services
                            $services = json_decode($payment['services'] ?? '[]', true);
                            if (!is_array($services)) { $services = []; }
                            $serviceNames = array_map(function ($s) {
                                return is_array($s) ? ($s['name'] ?? '') : (string) $s;
                            }, $services);
                            $serviceNames = array_values(array_filter($serviceNames, 'strlen'));
                            $shown        = array_slice($serviceNames, 0, 3);
                            $extra        = count($serviceNames) - count($shown);

                            // Gender attached by the controller from the patients
                            // table, matched on patient_code. Payments whose
                            // patient_code is 'N/A' or does not match a patient
                            // row get a null gender and fall back to initials.
                            $genderRaw  = strtolower(trim((string) ($payment['gender'] ?? '')));
                            $isMale     = $genderRaw === 'male'   || $genderRaw === 'm';
                            $isFemale   = $genderRaw === 'female' || $genderRaw === 'f';
                            $avatarKind = $isMale ? 'male' : ($isFemale ? 'female' : 'neutral');
                        ?>
                            <tr class="pm-row"
                                data-method="<?= esc($method, 'attr') ?>"
                                data-status="<?= esc($status, 'attr') ?>">

                                <td data-label="Reference" class="pm-c-ref">
                                    <span class="pm-ref"><?= esc($ref) ?></span>
                                </td>

                                <td data-label="Patient">
                                    <div class="pm-patient">
                                        <span class="pm-avatar pm-avatar--<?= esc($avatarKind, 'attr') ?>" aria-hidden="true">
                                            <?php if ($isMale): ?>
                                                <img src="<?= esc(base_url('assets/images/' . $maleAvatar), 'attr') ?>"
                                                     alt=""
                                                     class="pm-avatar-img"
                                                     loading="lazy"
                                                     decoding="async">
                                            <?php elseif ($isFemale): ?>
                                                <img src="<?= esc(base_url('assets/images/' . $femaleAvatar), 'attr') ?>"
                                                     alt=""
                                                     class="pm-avatar-img"
                                                     loading="lazy"
                                                     decoding="async">
                                            <?php else: ?>
                                                <?= esc($initialsOf($name)) ?>
                                            <?php endif; ?>
                                        </span>
                                        <div class="pm-patient-body">
                                            <span class="pm-name"><?= esc($name) ?></span>
                                            <span class="pm-sub"><?= esc($code) ?></span>
                                        </div>
                                    </div>
                                </td>

                                <td data-label="Services">
                                    <?php if (count($serviceNames) > 0): ?>
                                        <div class="pm-services" title="<?= esc(implode(', ', $serviceNames), 'attr') ?>">
                                            <span class="pm-services-text"><?= esc(implode(', ', $shown)) ?></span>
                                            <?php if ($extra > 0): ?>
                                                <span class="pm-more">+<?= $extra ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="pm-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Amount" class="pm-num">
                                    <strong class="pm-amount">&#8369;<?= number_format($total, 2) ?></strong>
                                    <?php if ($change > 0): ?>
                                        <small class="pm-change">Change: &#8369;<?= number_format($change, 2) ?></small>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Method">
                                    <span class="pm-method pm-method--<?= esc($method, 'attr') ?>">
                                        <i class="bi <?= $method === 'cash' ? 'bi-cash' : ($method === 'gcash' ? 'bi-phone' : 'bi-credit-card') ?>" aria-hidden="true"></i>
                                        <?= esc(ucfirst($method)) ?>
                                    </span>
                                </td>

                                <td data-label="Date" class="pm-c-date">
                                    <?php if ($dateTs): ?>
                                        <span class="pm-date"><?= esc(date('M j, Y', $dateTs)) ?></span>
                                        <span class="pm-sub"><?= esc(date('g:i A', $dateTs)) ?></span>
                                    <?php else: ?>
                                        <span class="pm-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Status">
                                    <span class="pm-status pm-status--<?= esc($status, 'attr') ?>">
                                        <?= esc(ucfirst($status)) ?>
                                    </span>
                                </td>

                                <td data-label="Actions" class="pm-c-actions">
                                    <div class="pm-actions">
                                        <button type="button" class="pm-icon-btn" onclick="viewPayment(<?= $payId ?>)" title="View details" aria-label="View payment <?= esc($ref, 'attr') ?>">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="pm-icon-btn pm-icon-btn--print" onclick="printReceipt(<?= $payId ?>)" title="Print receipt" aria-label="Print receipt for <?= esc($ref, 'attr') ?>">
                                            <i class="bi bi-printer" aria-hidden="true"></i>
                                        </button>
                                        <?php if ($status === 'paid'): ?>
                                            <button type="button" class="pm-icon-btn pm-icon-btn--refund" onclick="refundPayment(<?= $payId ?>)" title="Refund" aria-label="Refund payment <?= esc($ref, 'attr') ?>">
                                                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="pm-row-empty">
                            <td colspan="8">
                                <div class="pm-empty">
                                    <div class="pm-empty-icon"><i class="bi bi-credit-card" aria-hidden="true"></i></div>
                                    <h3>No payments recorded</h3>
                                    <p>Payments will appear here when you start processing a diagnostic request.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Shown only when a filter or search matches nothing -->
        <div class="pm-empty pm-empty--filter" id="pmNoMatch" hidden>
            <div class="pm-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></div>
            <h3>No matching payments</h3>
            <p>Try a different search term, or clear the filters.</p>
            <button type="button" class="pm-btn" id="pmClearFilters">Clear filters</button>
        </div>

        <footer class="pm-foot">
            <span id="showingText">
                Showing <strong><?= count($payments) ?></strong> of <strong><?= number_format($total ?? count($payments)) ?></strong> payments
            </span>

            <div class="pm-pager" id="paginationControls">
                <button type="button" class="pm-page" id="prevBtn" disabled aria-label="Previous page">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
                <button type="button" class="pm-page active">1</button>
                <button type="button" class="pm-page" id="nextBtn" aria-label="Next page">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </footer>

    </section>

</div>


<!-- VIEW PAYMENT MODAL -->
<div class="modal fade pm-modal" id="viewPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Loading...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* =========================================================
   PAYMENTS
   Namespaced under .pm so the layout's generic card and
   table rules can't leak in. Visual tokens match the other
   receptionist pages.
   ========================================================= */

.pm,
.pm-modal {
    --pm-ink:         #0f172a;
    --pm-text:        #334155;
    --pm-muted:       #64748b;
    --pm-faint:       #94a3b8;
    --pm-line:        #e2e8f0;
    --pm-line-soft:   #f1f5f9;
    --pm-surface:     #ffffff;
    --pm-subtle:      #f8fafc;
    --pm-accent:      #0d9488;
    --pm-accent-dark: #0f766e;
    --pm-accent-soft: #e6f7f7;
    --pm-blue:        #1d4ed8;
    --pm-blue-soft:   #eaf2fe;
    --pm-green:       #047857;
    --pm-green-soft:  #ecfdf5;
    --pm-red:         #b91c1c;
    --pm-red-soft:    #fef2f2;
    --pm-purple:      #6d28d9;
    --pm-purple-soft: #f3e8ff;
    --pm-orange:      #b45309;
    --pm-orange-soft: #fff4e5;
    --pm-radius:      10px;
    --pm-radius-sm:   7px;
    --pm-ring:        0 0 0 3px rgba(13, 148, 136, 0.18);
    --pm-mono:        ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
}

.pm {
    color: var(--pm-text);
    font-size: 0.875rem;
}

.pm *:focus-visible,
.pm-modal *:focus-visible { outline: 2px solid var(--pm-accent); outline-offset: 2px; }

.pm-muted { color: var(--pm-faint); }

.pm-visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* ---------- Page header ---------- */

.pm-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}

.pm-title {
    margin: 0 0 0.2rem;
    font-size: 1.25rem;
    font-weight: 650;
    letter-spacing: -0.015em;
    color: var(--pm-ink);
}

.pm-lede {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--pm-muted);
}

.pm-lede strong { font-weight: 600; color: var(--pm-ink); }
.pm-lede span { margin: 0 0.2rem; color: var(--pm-faint); }

.pm-head-actions { display: flex; gap: 0.5rem; }

/* ---------- Buttons ---------- */

.pm-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    height: 36px;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    color: var(--pm-text);
    background: var(--pm-surface);
    border: 1px solid var(--pm-line);
    border-radius: var(--pm-radius-sm);
    box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
    white-space: nowrap;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.pm-btn:hover { background: var(--pm-subtle); border-color: #cbd5e1; color: var(--pm-ink); }
.pm-btn i { font-size: 0.9em; }

/* ---------- Stats ---------- */

.pm-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.9rem;
    margin-bottom: 1.25rem;
}

.pm-stat {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 0;
    padding: 0.9rem 1rem;
    background: var(--pm-surface);
    border: 1px solid var(--pm-line);
    border-radius: var(--pm-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.pm-stat-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    border-radius: var(--pm-radius-sm);
}

.pm-stat-img {
    width: 24px;
    height: 24px;
    object-fit: contain;
    display: block;
}

.pm-stat-body { min-width: 0; }

.pm-stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.15;
    letter-spacing: -0.02em;
    color: var(--pm-ink);
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
}

.pm-stat-label {
    margin-top: 0.15rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--pm-text);
}

.pm-stat-sub {
    font-size: 0.72rem;
    color: var(--pm-muted);
}

/* ---------- Panel ---------- */

.pm-panel {
    background: var(--pm-surface);
    border: 1px solid var(--pm-line);
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

/* ---------- Tabs ---------- */

.pm-tabs {
    position: relative;
    display: flex;
    gap: 0.25rem;
    padding: 0 0.75rem;
    border-bottom: 1px solid var(--pm-line);
    overflow-x: auto;
    scrollbar-width: none;
}

.pm-tabs::-webkit-scrollbar { display: none; }

.pm-tab {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.85rem 0.6rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--pm-muted);
    background: none;
    border: 0;
    white-space: nowrap;
    cursor: pointer;
    transition: color 0.15s ease;
}

.pm-tab::after {
    content: "";
    position: absolute;
    left: 0.4rem;
    right: 0.4rem;
    bottom: -1px;
    height: 2px;
    border-radius: 2px 2px 0 0;
    background: transparent;
}

.pm-tab:hover { color: var(--pm-ink); }
.pm-tab.is-active { color: var(--pm-ink); }
.pm-tab.is-active::after { background: var(--pm-accent); }

.pm-tab-count {
    min-width: 1.4rem;
    padding: 0.05rem 0.4rem;
    font-size: 0.7rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    text-align: center;
    color: var(--pm-muted);
    background: var(--pm-line-soft);
    border-radius: 999px;
}

.pm-tab.is-active .pm-tab-count { color: var(--pm-accent); background: var(--pm-accent-soft); }

/* ---------- Filters ---------- */

.pm-filters {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    padding: 0.75rem 1rem;
}

.pm-search { position: relative; flex: 1 1 280px; max-width: 420px; }

.pm-search > i {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.8rem;
    color: var(--pm-faint);
    pointer-events: none;
}

.pm-input,
.pm-select {
    height: 36px;
    font-size: 0.8125rem;
    color: var(--pm-ink);
    background-color: var(--pm-surface);
    border: 1px solid var(--pm-line);
    border-radius: var(--pm-radius-sm);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.pm-input {
    width: 100%;
    padding: 0 0.75rem 0 2.1rem;
}

.pm-input::placeholder { color: var(--pm-faint); }

.pm-input:focus,
.pm-select:focus {
    outline: none;
    border-color: var(--pm-accent);
    box-shadow: var(--pm-ring);
}

.pm-select {
    padding: 0 2rem 0 0.75rem;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m3 6 5 5 5-5'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.65rem center;
    background-size: 12px;
    cursor: pointer;
    min-width: 160px;
}

/* ---------- Alerts ---------- */

.pm-alert {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin: 0 1rem 0.75rem;
    padding: 0.7rem 0.9rem;
    border: 1px solid transparent;
    border-radius: var(--pm-radius-sm);
    font-size: 0.8125rem;
}

.pm-alert--success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
.pm-alert--error   { background: #fef2f2; border-color: #fecaca; color: #991b1b; }

/* ---------- Table ---------- */

.pm-table-wrap { overflow-x: auto; border-top: 1px solid var(--pm-line); }

.pm-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
    color: var(--pm-text);
}

.pm-table th {
    padding: 0.65rem 1rem;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    text-align: left;
    white-space: nowrap;
    color: var(--pm-muted);
    background: var(--pm-subtle);
    border-bottom: 1px solid var(--pm-line);
}

.pm-table td {
    padding: 0.85rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--pm-line-soft);
}

.pm-table tbody tr:last-child td { border-bottom: 0; }

.pm-row { transition: background-color 0.12s ease; }
.pm-row:hover { background: #fafbfd; }

.pm-c-ref { white-space: nowrap; }

.pm-ref {
    padding: 0.15rem 0.5rem;
    font-family: var(--pm-mono);
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--pm-ink);
    background: var(--pm-line-soft);
    border-radius: 5px;
}

.pm-patient { display: flex; align-items: center; gap: 0.6rem; min-width: 0; }

.pm-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--pm-accent);
    background: var(--pm-accent-soft);
    border-radius: 50%;
    overflow: hidden;
}

/* Male / female / neutral avatar tints. The PNG fills the circle;
   the tint shows through transparent PNG edges as a subtle backdrop. */
.pm-avatar--male    { background: #eaf2fe; color: #1d4ed8; }
.pm-avatar--female  { background: #fce9ee; color: #b32e50; }
.pm-avatar--neutral { background: var(--pm-accent-soft); color: var(--pm-accent); }

.pm-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.pm-patient-body { min-width: 0; }

.pm-name {
    display: block;
    font-weight: 600;
    color: var(--pm-ink);
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pm-sub {
    display: block;
    font-size: 0.72rem;
    color: var(--pm-muted);
    white-space: nowrap;
}

.pm-c-ref + .pm-c-ref { padding-left: 1rem; }

.pm-services {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    max-width: 16rem;
    font-size: 0.78rem;
    color: var(--pm-text);
}

.pm-services-text {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pm-more {
    flex-shrink: 0;
    padding: 0 0.35rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--pm-muted);
    background: var(--pm-line-soft);
    border-radius: 4px;
}

.pm-num {
    text-align: right;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.pm-amount {
    display: block;
    color: var(--pm-ink);
    font-weight: 600;
}

.pm-change {
    display: block;
    margin-top: 0.1rem;
    font-size: 0.68rem;
    color: var(--pm-green);
}

.pm-method {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.2rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    border-radius: 999px;
    white-space: nowrap;
}

.pm-method--cash      { color: var(--pm-green);  background: var(--pm-green-soft); }
.pm-method--card      { color: var(--pm-blue);   background: var(--pm-blue-soft); }
.pm-method--gcash     { color: var(--pm-purple); background: var(--pm-purple-soft); }
.pm-method--paymaya   { color: var(--pm-orange); background: var(--pm-orange-soft); }
.pm-method--insurance { color: var(--pm-accent-dark); background: var(--pm-accent-soft); }

.pm-method i { font-size: 0.72em; }

.pm-c-date { white-space: nowrap; }

.pm-date {
    display: block;
    color: var(--pm-text);
    font-variant-numeric: tabular-nums;
}

.pm-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
    color: var(--pm-tone-fg, #334155);
    background: var(--pm-tone-bg, #f1f5f9);
    border-radius: 999px;
}

.pm-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--pm-tone-dot, #94a3b8);
}

.pm-status--paid     { --pm-tone-bg: var(--pm-green-soft); --pm-tone-fg: var(--pm-green); --pm-tone-dot: #10b981; }
.pm-status--pending  { --pm-tone-bg: var(--pm-orange-soft); --pm-tone-fg: var(--pm-orange); --pm-tone-dot: #f59e0b; }
.pm-status--refunded { --pm-tone-bg: var(--pm-red-soft); --pm-tone-fg: var(--pm-red); --pm-tone-dot: #ef4444; }
.pm-status--failed   { --pm-tone-bg: #f1f5f9; --pm-tone-fg: #64748b; --pm-tone-dot: #94a3b8; }

.pm-c-actions { width: 1%; text-align: right; white-space: nowrap; }

.pm-actions { display: inline-flex; align-items: center; gap: 0.25rem; }

.pm-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    font-size: 0.85rem;
    color: var(--pm-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--pm-radius-sm);
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.pm-icon-btn:hover { background: var(--pm-blue-soft); color: var(--pm-blue); border-color: #dbe6fb; }
.pm-icon-btn--print:hover { background: var(--pm-accent-soft); color: var(--pm-accent-dark); border-color: #99f6e4; }
.pm-icon-btn--refund:hover { background: var(--pm-red-soft); color: var(--pm-red); border-color: #fecaca; }

/* ---------- Empty states ---------- */

.pm-empty { padding: 3.5rem 1rem; text-align: center; }
.pm-empty--filter { border-top: 1px solid var(--pm-line); }

.pm-empty-icon {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    margin: 0 auto 0.85rem;
    font-size: 1.2rem;
    color: var(--pm-faint);
    background: var(--pm-line-soft);
    border-radius: 10px;
}

.pm-empty h3 { margin: 0 0 0.25rem; font-size: 0.95rem; font-weight: 600; color: var(--pm-ink); }
.pm-empty p { max-width: 26rem; margin: 0 auto 1rem; font-size: 0.8125rem; color: var(--pm-muted); }

/* ---------- Footer + pagination ---------- */

.pm-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding: 0.7rem 1rem;
    font-size: 0.78rem;
    color: var(--pm-muted);
    border-top: 1px solid var(--pm-line);
}

.pm-foot strong { font-weight: 600; color: var(--pm-ink); font-variant-numeric: tabular-nums; }

.pm-pager { display: flex; align-items: center; gap: 0.25rem; }

.pm-page {
    min-width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.5rem;
    font-size: 0.78rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--pm-text);
    background: var(--pm-surface);
    border: 1px solid var(--pm-line);
    border-radius: var(--pm-radius-sm);
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.pm-page:hover:not(:disabled):not(.active) { background: var(--pm-subtle); border-color: #cbd5e1; }
.pm-page.active { background: var(--pm-accent); border-color: var(--pm-accent); color: #ffffff; }
.pm-page:disabled { opacity: 0.45; cursor: not-allowed; }

/* ---------- Modal (view details) ---------- */

.pm-modal .modal-content {
    border: 1px solid var(--pm-line);
    border-radius: 12px;
    box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.28);
    overflow: hidden;
}

.pm-modal .modal-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--pm-line);
}

.pm-modal .modal-title {
    font-size: 1rem;
    font-weight: 650;
    color: var(--pm-ink);
}

.pm-modal .modal-body {
    padding: 1.25rem;
}

.pm-modal .payment-details .detail-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.6rem 0;
    border-bottom: 1px solid var(--pm-line-soft);
}

.pm-modal .payment-details .detail-row:last-child { border-bottom: none; }

.pm-modal .payment-details .detail-label {
    color: var(--pm-muted);
    font-size: 0.8125rem;
    flex-shrink: 0;
}

.pm-modal .payment-details .detail-value {
    font-size: 0.8125rem;
    text-align: right;
    max-width: 60%;
    color: var(--pm-ink);
    overflow-wrap: anywhere;
}

/* ---------- Responsive ---------- */

@media (max-width: 1200px) {
    .pm-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 992px) {
    .pm-filters { flex-direction: column; align-items: stretch; }
    .pm-filters .pm-search { flex-basis: auto; max-width: none; }
    .pm-filters .pm-select { min-width: 100%; }
}

@media (max-width: 768px) {
    .pm-head { flex-direction: column; align-items: stretch; }
    .pm-head-actions .pm-btn { flex: 1; }

    .pm-stats { grid-template-columns: minmax(0, 1fr); gap: 0.6rem; }

    .pm-table thead { display: none; }

    .pm-table,
    .pm-table tbody,
    .pm-table tr,
    .pm-table td { display: block; width: 100%; }

    .pm-table tr.pm-row {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid var(--pm-line);
    }

    .pm-table td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.25rem 0;
        text-align: right;
        border: 0;
    }

    .pm-table td[data-label]::before {
        content: attr(data-label);
        flex-shrink: 0;
        font-size: 0.75rem;
        font-weight: 400;
        color: var(--pm-muted);
        text-align: left;
    }

    .pm-table td.pm-c-ref,
    .pm-table td:nth-child(2) { display: block; text-align: left; }
    .pm-table td.pm-c-ref::before,
    .pm-table td:nth-child(2)::before { content: none; }
    .pm-table td:nth-child(2) { margin: 0.35rem 0 0.5rem; }

    .pm-table td.pm-c-actions { justify-content: flex-end; margin-top: 0.5rem; }

    .pm-c-date, .pm-c-ref { white-space: normal; }
    .pm-services { max-width: 60%; }

    .pm-foot { justify-content: center; }
}

@media (prefers-reduced-motion: reduce) {
    .pm *, .pm *::before, .pm *::after,
    .pm-modal * { transition: none !important; }
}

@media print {
    .pm-head-actions,
    .pm-tabs,
    .pm-filters,
    .pm-c-actions,
    .pm-foot { display: none !important; }

    .pm-panel { border: 0; box-shadow: none; }
    .pm-row { display: table-row !important; }
}
</style>

<script>
(function () {
    'use strict';

    /* =====================================================
       FILTERS: search + method + status (all cooperate)
       ===================================================== */

    var search       = document.getElementById('searchPayments');
    var methodFilter = document.getElementById('filterMethod');
    var statusFilter = document.getElementById('filterStatus');
    var tbody        = document.getElementById('paymentsTableBody');
    var noMatch      = document.getElementById('pmNoMatch');
    var clearBtn     = document.getElementById('pmClearFilters');

    function rows() {
        return tbody ? Array.prototype.slice.call(tbody.querySelectorAll('tr.pm-row')) : [];
    }

    function applyFilters() {
        var term   = search       ? search.value.toLowerCase().trim()     : '';
        var method = methodFilter ? methodFilter.value.toLowerCase()       : '';
        var status = statusFilter ? statusFilter.value.toLowerCase()       : '';
        var visible = 0;

        rows().forEach(function (row) {
            var text = row.textContent.toLowerCase();
            var rowMethod = (row.dataset.method || '').toLowerCase();
            var rowStatus = (row.dataset.status || '').toLowerCase();

            var okSearch = term   === '' || text.indexOf(term) !== -1;
            var okMethod = method === '' || rowMethod === method;
            var okStatus = status === '' || rowStatus === status;

            var show = okSearch && okMethod && okStatus;
            row.style.display = show ? '' : 'none';
            if (show) { visible++; }
        });

        if (noMatch) {
            noMatch.hidden = !(rows().length > 0 && visible === 0);
        }
    }

    if (search) {
        search.addEventListener('input', applyFilters);
        search.addEventListener('search', applyFilters);
    }

    if (methodFilter) { methodFilter.addEventListener('change', applyFilters); }
    if (statusFilter) { statusFilter.addEventListener('change', applyFilters); }

    /* =====================================================
       TAB STRIP  →  drives the hidden select above
       ===================================================== */

    var tabs = Array.prototype.slice.call(document.querySelectorAll('.pm-tab'));

    function syncTabs(status) {
        tabs.forEach(function (tab) {
            var on = tab.dataset.status === status;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var status = tab.dataset.status || 'all';
            syncTabs(status);

            if (statusFilter) {
                statusFilter.value = status === 'all' ? '' : status;
                statusFilter.dispatchEvent(new Event('change'));
            } else {
                applyFilters();
            }
        });
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (search)       { search.value = ''; }
            if (methodFilter) { methodFilter.value = ''; }
            if (statusFilter) { statusFilter.value = ''; }
            syncTabs('all');
            applyFilters();
        });
    }

})();


/* =====================================================
   VIEW PAYMENT
   ===================================================== */

function viewPayment(id) {
    const modal = new bootstrap.Modal(document.getElementById('viewPaymentModal'));
    const content = document.getElementById('paymentDetailsContent');

    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Loading...</p>
        </div>
    `;

    modal.show();

    fetch(`/polymedic/public/receptionist/get-payment-details/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const p = data.data;
                const services = JSON.parse(p.services || '[]');
                const serviceList = services.map(s => s.name || s).join(', ');

                content.innerHTML = `
                    <div class="payment-details">
                        <div class="detail-row">
                            <span class="detail-label">Reference</span>
                            <span class="detail-value"><strong>${p.reference_number}</strong></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Patient</span>
                            <span class="detail-value">${p.patient_name}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Patient Code</span>
                            <span class="detail-value">${p.patient_code || 'N/A'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Services</span>
                            <span class="detail-value">${serviceList || 'N/A'}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Total Amount</span>
                            <span class="detail-value"><strong>₱${parseFloat(p.total_amount).toFixed(2)}</strong></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Amount Paid</span>
                            <span class="detail-value">₱${parseFloat(p.amount_paid).toFixed(2)}</span>
                        </div>
                        ${parseFloat(p.change_amount) > 0 ? `
                        <div class="detail-row">
                            <span class="detail-label">Change</span>
                            <span class="detail-value" style="color: #15803d;">₱${parseFloat(p.change_amount).toFixed(2)}</span>
                        </div>
                        ` : ''}
                        <div class="detail-row">
                            <span class="detail-label">Payment Method</span>
                            <span class="detail-value">${p.payment_method.charAt(0).toUpperCase() + p.payment_method.slice(1)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status</span>
                            <span class="detail-value"><span class="pm-status pm-status--${p.payment_status}">${p.payment_status.charAt(0).toUpperCase() + p.payment_status.slice(1)}</span></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Date</span>
                            <span class="detail-value">${new Date(p.payment_date).toLocaleString()}</span>
                        </div>
                        ${p.received_by ? `
                        <div class="detail-row">
                            <span class="detail-label">Received By</span>
                            <span class="detail-value">${p.received_by}</span>
                        </div>
                        ` : ''}
                    </div>
                `;
            } else {
                content.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            content.innerHTML = `<div class="alert alert-danger">Error loading payment details</div>`;
        });
}

// ===== PRINT RECEIPT =====
function printReceipt(id) {
    window.open(`/polymedic/public/receptionist/print-receipt/${id}`, '_blank');
}

// ===== REFUND PAYMENT =====
function refundPayment(id) {
    if (confirm('Are you sure you want to refund this payment?')) {
        fetch(`/polymedic/public/receptionist/refund-payment/${id}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error processing refund');
        });
    }
}

// ===== EXPORT =====
function exportPayments() {
    alert('Export functionality coming soon!');
}
</script>

<?= $this->endSection() ?>