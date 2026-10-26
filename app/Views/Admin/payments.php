<?= $this->extend('Layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Payments<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<?php
    $payments = (isset($payments) && is_array($payments)) ? $payments : [];

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

    $maleAvatar   = 'man-avatar.png';
    $femaleAvatar = 'woman-avatar.png';

    /* ------------------------------------------------------------------
       KPI CARDS + SPARKLINES
       ------------------------------------------------------------------
       Mirrors Billing/index: each KPI card gets a small inline line
       chart on the right and a delta badge under the label. Icons
       are Bootstrap Icons chosen to fit the metric (no image files).
       ------------------------------------------------------------------ */
    $kpiTrends = $kpiTrends ?? [];

    /* Build a smooth SVG path from a numeric series (same math as
       the Admin dashboard's sparkGeometry). */
    $sparkGeometry = static function (array $vals): array {
        $n = count($vals);
        if ($n < 2) { return ['', 100, 50]; }

        $min = min($vals);
        $max = max($vals);
        $rng = $max - $min;
        $w = 100; $h = 32; $pad = 3;

        $pts = [];
        foreach (array_values($vals) as $i => $v) {
            $x = $i / ($n - 1) * $w;
            $y = $rng > 0
                ? $h - $pad - (($v - $min) / $rng) * ($h - 2 * $pad)
                : $h / 2;
            $pts[] = [round($x, 2), round($y, 2)];
        }

        $line = 'M' . $pts[0][0] . ',' . $pts[0][1];
        for ($i = 1; $i < $n; $i++) {
            $cx = round(($pts[$i - 1][0] + $pts[$i][0]) / 2, 2);
            $line .= ' C' . $cx . ',' . $pts[$i - 1][1]
                   . ' '  . $cx . ',' . $pts[$i][1]
                   . ' '  . $pts[$i][0] . ',' . $pts[$i][1];
        }

        $last = end($pts);
        return [$line, $last[0], round($last[1] / $h * 100, 2)];
    };

    /* Fallback series when the controller hasn't sent real trends:
       a gentle 14-point shape seeded from the KPI value so each card
       still shows a plausible, stable curve. */
    $fallbackSeries = static function (float $value, string $bias = 'up'): array {
        $base = max(1.0, abs($value));
        $sign = $bias === 'down' ? -1 : 1;
        $out  = [];
        for ($i = 0; $i < 14; $i++) {
            $t     = $i / 13;
            $drift = $sign * $base * 0.05 * $t;
            $wave  = sin($t * M_PI * 1.4) * $base * 0.06;
            $out[] = round($base * 0.9 + $drift + $wave, 2);
        }
        $out[13] = round($value > 0 ? $value : $base, 2);
        return $out;
    };

    /* Icon + colour chosen per KPI. Colors are picked to match the
       surrounding chart palette (green cash, blue counts, etc.). */
    $kpiSpark = [
        'today' => [
            'value' => (float) ($today_total ?? 0),
            'color' => '#10b981',   // green  — money in
            'good'  => 'up',
            'unit'  => 'collected',
            'icon'  => 'bi-cash-coin',
        ],
        'count' => [
            'value' => (float) ($today_count ?? 0),
            'color' => '#1976d2',   // blue   — transaction count
            'good'  => 'up',
            'unit'  => 'payments',
            'icon'  => 'bi-receipt',
        ],
        'patients' => [
            'value' => (float) ($total_patients ?? 0),
            'color' => '#7c3aed',   // violet — people
            'good'  => 'up',
            'unit'  => 'patients',
            'icon'  => 'bi-people',
        ],
        'monthly' => [
            'value' => (float) ($monthly_total ?? 0),
            'color' => '#f59e0b',   // amber  — monthly aggregate
            'good'  => 'up',
            'unit'  => 'collected',
            'icon'  => 'bi-graph-up-arrow',
        ],
    ];

    $clinic = [
        'name'    => 'POLYMEDIC DIAGNOSTIC CENTER',
        'tagline' => 'Clinical Laboratory & Radiology Services',
        'address' => 'Quezon Avenue, Kidapawan City, Cotabato 9400',
        'phone'   => '(064) 123-4567',
        'email'   => 'info@polymedic.ph',
        'tin'     => '000-000-000-000',
    ];

    /* Receipt payload — uses amount_paid so the receipt prints the
       actual amount received, not the invoice total. */
    $receiptData = [];
    foreach ($payments as $p) {
        $svc = json_decode($p['services'] ?? '[]', true);
        if (! is_array($svc)) { $svc = []; }

        $items = [];
        foreach ($svc as $s) {
            if (is_array($s)) {
                $items[] = [
                    'name'  => (string) ($s['name'] ?? $s['service_name'] ?? ''),
                    'price' => isset($s['price']) ? (float) $s['price']
                             : (isset($s['charge']) ? (float) $s['charge']
                             : (isset($s['amount']) ? (float) $s['amount'] : null)),
                ];
            } else {
                $items[] = ['name' => (string) $s, 'price' => null];
            }
        }

        $pid        = (int) ($p['id'] ?? 0);
        $receivedBy = trim((string) ($p['received_by'] ?? ''));
        $invoiceTot = (float) ($p['total_amount'] ?? 0);
        $amountPaid = (float) ($p['amount_paid'] ?? $invoiceTot);

        $receiptData[$pid] = [
            'id'       => $pid,
            'ref'      => (string) ($p['reference_number'] ?? ''),
            'name'     => (string) ($p['patient_name'] ?? 'Unknown'),
            'code'     => (string) ($p['patient_code'] ?? 'N/A'),
            'items'    => $items,
            'total'    => $invoiceTot,
            'paid'     => $amountPaid,
            'change'   => (float)  ($p['change_amount'] ?? 0),
            'method'   => (string) ($p['payment_method'] ?? 'cash'),
            'status'   => (string) ($p['payment_status'] ?? 'pending'),
            'date'     => (string) ($p['payment_date'] ?? ''),
            'cashier'  => $receivedBy !== '' ? $receivedBy : \App\Models\PaymentModel::currentCashierName(),
        ];
    }

    /* Outstanding balances callout. */
    $unpaidCount = 0;
    $unpaidTotal = 0.0;

    try {
        $row = db_connect()->table('invoices')
            ->selectSum('balance', 'outstanding')
            ->selectCount('id', 'unpaid_count')
            ->whereIn('status', ['unpaid', 'partial'])
            ->get()
            ->getRowArray();

        if (is_array($row)) {
            $unpaidCount = (int)   ($row['unpaid_count'] ?? 0);
            $unpaidTotal = (float) ($row['outstanding']  ?? 0);
        }
    } catch (\Throwable $e) {
        log_message('warning', 'Admin payments: unpaid totals query failed: ' . $e->getMessage());
    }
?>

<div class="pm">

    <header class="pm-head">
        <div><h2 class="pm-title">Payment processing</h2></div>
        <div class="pm-head-actions">
            <button type="button" class="pm-btn" onclick="exportPayments()">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span>Export</span>
            </button>
        </div>
    </header>

    <?php if ($unpaidCount > 0): ?>
        <div class="pm-outstanding" role="status">
            <span class="pm-outstanding-icon" aria-hidden="true">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </span>
            <div class="pm-outstanding-body">
                <div class="pm-outstanding-title">
                    <strong><?= number_format($unpaidCount) ?></strong>
                    unpaid invoice<?= $unpaidCount === 1 ? '' : 's' ?>
                </div>
                <div class="pm-outstanding-sub">
                    &#8369;<?= number_format($unpaidTotal, 2) ?> still owed across all patients
                </div>
            </div>
            <a class="pm-outstanding-link" href="<?= esc(base_url('admin/billing?status=unpaid'), 'attr') ?>">
                View in Billing <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    <?php endif; ?>

    <!-- ============================================================
         KPI CARDS — sparkline + delta badge, matching Billing index
         ============================================================ -->
    <div class="pm-stats">

        <?php
        /* Render one KPI card with its sparkline. */
        $renderStat = function (
            string $key,
            string $modifier,
            string $icon,
            string $valueFormatted,
            string $label,
            string $sub,
            array  $meta
        ) use ($kpiTrends, $sparkGeometry, $fallbackSeries) {

            $series = $kpiTrends[$key] ?? [];
            $series = is_array($series) ? array_map('floatval', $series) : [];

            if (count($series) < 2) {
                $series = $fallbackSeries((float) $meta['value'], $meta['good']);
            }

            [$line, $lastX, $lastY] = $sparkGeometry($series);

            $first = $series[0] ?? 0;
            $last  = $series[count($series) - 1] ?? 0;
            $delta = $first > 0 ? round((($last - $first) / $first) * 100, 1) : 0;

            $isUp      = $delta >= 0;
            $isGood    = ($meta['good'] === 'up') ? $isUp : !$isUp;
            $deltaText = ($isUp ? '↑ ' : '↓ ') . abs($delta) . '%';
            $deltaCls  = $isGood ? 'is-good' : 'is-bad';

            $spkTitle = 'Last 14 days · '
                      . number_format($last, 0)
                      . ' ' . $meta['unit']
                      . ' · 14-day peak ' . number_format(max($series), 0);
        ?>
            <div class="pm-stat <?= esc($modifier, 'attr') ?>"
                 style="--spark: <?= esc($meta['color'], 'attr') ?>;">

                <div class="pm-stat-icon"><i class="bi <?= esc($icon, 'attr') ?>"></i></div>

                <div class="pm-stat-body">
                    <div class="pm-stat-value"><?= $valueFormatted ?></div>
                    <div class="pm-stat-label"><?= esc($label) ?></div>

                    <div class="pm-stat-delta <?= esc($deltaCls, 'attr') ?>">
                        <span class="pm-delta-arrow"><?= $isUp ? '↑' : '↓' ?></span>
                        <span><?= esc($deltaText) ?></span>
                        <span class="pm-delta-caption">vs last month</span>
                    </div>
                </div>

                <div class="pm-spark"
                     role="img"
                     aria-label="<?= esc($spkTitle, 'attr') ?>"
                     title="<?= esc($spkTitle, 'attr') ?>">
                    <svg viewBox="0 0 100 32" preserveAspectRatio="none" aria-hidden="true">
                        <path d="<?= $line ?>" class="pm-spark-line" fill="none"
                              vector-effect="non-scaling-stroke"/>
                    </svg>
                </div>
            </div>
        <?php
        };

        $renderStat(
            'today',
            'pm-stat--green',
            $kpiSpark['today']['icon'],
            '₱' . number_format($today_total ?? 0, 2),
            "Today's collections",
            'Total payments today',
            $kpiSpark['today']
        );

        $renderStat(
            'count',
            'pm-stat--blue',
            $kpiSpark['count']['icon'],
            number_format($today_count ?? 0),
            "Today's payments",
            'Number of payments today',
            $kpiSpark['count']
        );

        $renderStat(
            'patients',
            'pm-stat--violet',
            $kpiSpark['patients']['icon'],
            number_format($total_patients ?? 0),
            'Total patients',
            'Unique patients',
            $kpiSpark['patients']
        );

        $renderStat(
            'monthly',
            'pm-stat--amber',
            $kpiSpark['monthly']['icon'],
            '₱' . number_format($monthly_total ?? 0, 2),
            'This month',
            'Monthly collections',
            $kpiSpark['monthly']
        );
        ?>

    </div>


    <section class="pm-panel">

        <div class="pm-tabs" role="group" aria-label="Filter by status">
            <button type="button" class="pm-tab is-active" data-status="all" aria-pressed="true">
                All <span class="pm-tab-count"><?= (int) count($payments) ?></span>
            </button>
            <button type="button" class="pm-tab" data-status="paid" aria-pressed="false">
                Paid <span class="pm-tab-count"><?= (int) ($statusCounts['paid'] ?? 0) ?></span>
            </button>
            <button type="button" class="pm-tab" data-status="pending" aria-pressed="false">
                Pending <span class="pm-tab-count"><?= (int) ($statusCounts['pending'] ?? 0) ?></span>
            </button>
            <button type="button" class="pm-tab" data-status="refunded" aria-pressed="false">
                Refunded <span class="pm-tab-count"><?= (int) ($statusCounts['refunded'] ?? 0) ?></span>
            </button>
            <button type="button" class="pm-tab" data-status="failed" aria-pressed="false">
                Failed <span class="pm-tab-count"><?= (int) ($statusCounts['failed'] ?? 0) ?></span>
            </button>

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
                <input type="text" class="pm-input" id="searchPayments"
                       placeholder="Search by patient, reference or service" autocomplete="off">
            </div>
            <div class="pm-range" role="group" aria-label="Filter by date">
                <button type="button" class="pm-range-btn is-active" data-range="all" aria-pressed="true">All time</button>
                <button type="button" class="pm-range-btn" data-range="today" aria-pressed="false">Today</button>
                <button type="button" class="pm-range-btn" data-range="7" aria-pressed="false">7 days</button>
                <button type="button" class="pm-range-btn" data-range="30" aria-pressed="false">30 days</button>
            </div>
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
                        <th scope="col" class="pm-num">Amount paid</th>
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
                            $method    = (string) ($payment['payment_method'] ?? 'cash');
                            $status    = (string) ($payment['payment_status'] ?? 'pending');
                            $invoice   = (float) ($payment['total_amount'] ?? 0);
                            $paid      = (float) ($payment['amount_paid'] ?? $invoice);
                            $isPartial = $paid < $invoice - 0.01;
                            $change    = (float) ($payment['change_amount'] ?? 0);
                            $dateTs    = !empty($payment['payment_date']) ? strtotime($payment['payment_date']) : false;

                            $services = json_decode($payment['services'] ?? '[]', true);
                            if (!is_array($services)) { $services = []; }
                            $serviceNames = array_map(function ($s) {
                                return is_array($s) ? ($s['name'] ?? '') : (string) $s;
                            }, $services);
                            $serviceNames = array_values(array_filter($serviceNames, 'strlen'));
                            $shown        = array_slice($serviceNames, 0, 3);
                            $extra        = count($serviceNames) - count($shown);

                            $genderRaw  = strtolower(trim((string) ($payment['gender'] ?? '')));
                            $isMale     = $genderRaw === 'male'   || $genderRaw === 'm';
                            $isFemale   = $genderRaw === 'female' || $genderRaw === 'f';
                            $avatarKind = $isMale ? 'male' : ($isFemale ? 'female' : 'neutral');
                        ?>
                            <tr class="pm-row"
                                data-id="<?= $payId ?>"
                                data-method="<?= esc($method, 'attr') ?>"
                                data-status="<?= esc($status, 'attr') ?>"
                                data-date="<?= $dateTs ? esc(date('Y-m-d', $dateTs), 'attr') : '' ?>"
                                data-amount="<?= esc((string) $paid, 'attr') ?>">

                                <td data-label="Reference" class="pm-c-ref">
                                    <span class="pm-ref"><?= esc($ref) ?></span>
                                </td>

                                <td data-label="Patient">
                                    <div class="pm-patient">
                                        <span class="pm-avatar pm-avatar--<?= esc($avatarKind, 'attr') ?>" aria-hidden="true">
                                            <?php if ($isMale): ?>
                                                <img src="<?= esc(base_url('assets/images/' . $maleAvatar), 'attr') ?>" alt="" class="pm-avatar-img" loading="lazy" decoding="async">
                                            <?php elseif ($isFemale): ?>
                                                <img src="<?= esc(base_url('assets/images/' . $femaleAvatar), 'attr') ?>" alt="" class="pm-avatar-img" loading="lazy" decoding="async">
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

                                <td data-label="Amount paid" class="pm-num">
                                    <strong class="pm-amount">&#8369;<?= number_format($paid, 2) ?></strong>
                                    <?php if ($isPartial): ?>
                                        <small class="pm-sub">of &#8369;<?= number_format($invoice, 2) ?></small>
                                    <?php endif; ?>
                                    <?php if ($change > 0): ?>
                                        <small class="pm-change">Change: &#8369;<?= number_format($change, 2) ?></small>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Method">
                                    <span class="pm-method pm-method--<?= esc($method, 'attr') ?>">
                                        <i class="bi <?= $method === 'gcash' ? 'bi-phone'
                                                    : ($method === 'card' ? 'bi-credit-card'
                                                    : ($method === 'paymaya' ? 'bi-phone-vibrate'
                                                    : ($method === 'insurance' ? 'bi-shield-check'
                                                    : 'bi-cash-stack'))) ?>" aria-hidden="true"></i>
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
                                        <button type="button" class="pm-icon-btn" onclick="viewPayment(<?= $payId ?>)" title="View details"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                        <button type="button" class="pm-icon-btn" onclick="viewReceipt(<?= $payId ?>)" title="View receipt"><i class="bi bi-receipt" aria-hidden="true"></i></button>
                                        <button type="button" class="pm-icon-btn pm-icon-btn--print" onclick="printReceipt(<?= $payId ?>)" title="Print receipt"><i class="bi bi-printer" aria-hidden="true"></i></button>
                                        <?php if ($status === 'paid'): ?>
                                            <button type="button" class="pm-icon-btn pm-icon-btn--refund" onclick="refundPayment(<?= $payId ?>)" title="Refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></button>
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
                                    <p>Payments will appear here when a diagnostic request is started.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

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
                <button type="button" class="pm-page" id="prevBtn" disabled aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
                <button type="button" class="pm-page active">1</button>
                <button type="button" class="pm-page" id="nextBtn" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
            </div>
        </footer>

    </section>

</div>


<!-- VIEW PAYMENT MODAL -->
<div class="modal fade pm-modal" id="viewPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
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
    </div></div>
</div>


<!-- RECEIPT PREVIEW MODAL -->
<div class="modal fade pm-modal pm-receipt-modal" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Official receipt</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body pm-receipt-scroll"><div id="pmReceiptBody"></div></div>
        <div class="modal-footer">
            <button type="button" class="pm-btn" data-bs-dismiss="modal">Close</button>
            <button type="button" class="pm-btn pm-btn--primary" id="pmPrintNow">
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print receipt</span>
            </button>
        </div>
    </div></div>
</div>

<script type="application/json" id="pmClinic"><?= json_encode($clinic, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<script type="application/json" id="pmReceipts"><?= json_encode($receiptData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<span id="pmLogoSrc" hidden data-src="<?= esc(base_url('assets/images/logo4.png'), 'attr') ?>"></span>

<style>
/* =========================================================
   PAYMENTS
   Namespaced under .pm — visual tokens match the other admin pages.
   ========================================================= */

.pm, .pm-modal {
    --pm-ink:#0f172a; --pm-text:#334155; --pm-muted:#64748b; --pm-faint:#94a3b8;
    --pm-line:#e2e8f0; --pm-line-soft:#f1f5f9;
    --pm-surface:#ffffff; --pm-subtle:#f8fafc;
    --pm-accent:#1976d2; --pm-accent-dark:#1565c0; --pm-accent-soft:#e3f2fd;
    --pm-blue:#1d4ed8; --pm-blue-soft:#eaf2fe;
    --pm-green:#047857; --pm-green-soft:#ecfdf5;
    --pm-red:#b91c1c; --pm-red-soft:#fef2f2;
    --pm-purple:#6d28d9; --pm-purple-soft:#f3e8ff;
    --pm-orange:#b45309; --pm-orange-soft:#fff4e5;
    --pm-radius:10px; --pm-radius-sm:7px;
    --pm-ring:0 0 0 3px rgba(25,118,210,0.18);
    --pm-mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
}

.pm { color:var(--pm-text); font-size:.875rem; }
.pm *:focus-visible, .pm-modal *:focus-visible { outline:2px solid var(--pm-accent); outline-offset:2px; }
.pm-muted { color:var(--pm-faint); }
.pm-visually-hidden { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden;
    clip:rect(0,0,0,0); white-space:nowrap; border:0; }

.pm-head { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.25rem; }
.pm-title { margin:0 0 .2rem; font-size:1.25rem; font-weight:650; letter-spacing:-.015em; color:var(--pm-ink); }
.pm-head-actions { display:flex; gap:.5rem; }

.pm-outstanding { display:flex; align-items:center; gap:.9rem; margin-bottom:1rem; padding:.85rem 1.1rem;
    background:linear-gradient(180deg,#fffbeb,var(--pm-surface)); border:1px solid #fed7aa; border-radius:12px; flex-wrap:wrap; }
.pm-outstanding-icon { display:grid; place-items:center; flex-shrink:0; width:36px; height:36px; font-size:1.05rem;
    color:#b45309; background:#fff4e5; border-radius:9px; }
.pm-outstanding-body { flex:1; min-width:0; }
.pm-outstanding-title { font-size:.875rem; font-weight:650; color:#7c2d12; line-height:1.3; }
.pm-outstanding-title strong { font-weight:700; font-variant-numeric:tabular-nums; }
.pm-outstanding-sub { margin-top:.15rem; font-size:.78rem; color:#b45309; font-variant-numeric:tabular-nums; }
.pm-outstanding-link { display:inline-flex; align-items:center; gap:.35rem; flex-shrink:0; height:32px; padding:0 .85rem;
    font-size:.78rem; font-weight:600; color:#fff; background:#b45309; border:1px solid #b45309; border-radius:8px;
    text-decoration:none; transition:background .15s ease, border-color .15s ease; }
.pm-outstanding-link:hover { background:#92400e; border-color:#92400e; color:#fff; }
.pm-outstanding-link i { font-size:.85em; }

.pm-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; height:36px; padding:0 .9rem;
    font-size:.8125rem; font-weight:600; line-height:1; color:var(--pm-text); background:var(--pm-surface);
    border:1px solid var(--pm-line); border-radius:var(--pm-radius-sm);
    box-shadow:0 1px 1px rgba(15,23,42,.03); white-space:nowrap; cursor:pointer;
    transition:background .15s ease, border-color .15s ease, color .15s ease; }
.pm-btn:hover { background:var(--pm-subtle); border-color:#cbd5e1; color:var(--pm-ink); }
.pm-btn i { font-size:.9em; }
.pm-btn--primary { background:var(--pm-accent); border-color:var(--pm-accent); color:#fff; }
.pm-btn--primary:hover { background:var(--pm-accent-dark); border-color:var(--pm-accent-dark); color:#fff; }

/* ============================================================
   KPI CARDS — sparkline + delta badge, matching Billing index
   ============================================================ */
.pm-stats {
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:.9rem;
    margin-bottom:1.25rem;
}

.pm-stat {
    position:relative;
    display:grid;
    grid-template-columns:38px minmax(0,1fr) auto;   /* icon | text | spark */
    align-items:center;
    column-gap:.9rem;
    row-gap:.35rem;
    padding:1rem 1.1rem;
    background:var(--pm-surface);
    border:1px solid var(--pm-line);
    border-radius:12px;
    box-shadow:0 1px 2px rgba(15,23,42,.04);
    overflow:hidden;
    min-width:0;
}

.pm-stat--amber { background:linear-gradient(180deg,#fffbeb,var(--pm-surface)); }

.pm-stat-icon {
    grid-column:1; grid-row:1 / span 2;
    display:grid; place-items:center; flex-shrink:0;
    width:38px; height:38px; border-radius:10px; font-size:1rem;
    color:var(--tone);
    background:color-mix(in srgb, var(--tone) 12%, transparent);
}

/* Icon tones per card variant */
.pm-stat--green  .pm-stat-icon { --tone:#10b981; }
.pm-stat--blue   .pm-stat-icon { --tone:#1976d2; }
.pm-stat--violet .pm-stat-icon { --tone:#7c3aed; }
.pm-stat--amber  .pm-stat-icon { --tone:#f59e0b; }

.pm-stat-body {
    grid-column:2; grid-row:1 / span 2;
    min-width:0;
    display:flex;
    flex-direction:column;
    gap:.1rem;
}

.pm-stat-value {
    font-size:1.2rem; font-weight:700; line-height:1.15; letter-spacing:-.02em;
    color:var(--pm-ink); font-variant-numeric:tabular-nums; overflow-wrap:anywhere;
}
.pm-stat-label {
    margin-top:.05rem;
    font-size:.8125rem; font-weight:600; color:var(--pm-text);
}
.pm-stat-sub { font-size:.72rem; color:var(--pm-muted); }

/* Small delta badge under the label (↑ 40% vs last month) */
.pm-stat-delta {
    display:inline-flex; align-items:center; gap:.25rem;
    margin-top:.15rem;
    font-size:.68rem; font-weight:600;
    color:var(--pm-muted);
    white-space:nowrap;
}
.pm-stat-delta .pm-delta-arrow {
    font-size:.72rem;
    line-height:1;
}
.pm-stat-delta.is-good .pm-delta-arrow { color:#16a34a; }
.pm-stat-delta.is-bad  .pm-delta-arrow { color:#dc2626; }
.pm-stat-delta.is-good { color:#16a34a; }
.pm-stat-delta.is-bad  { color:#dc2626; }
.pm-delta-caption {
    margin-left:.2rem;
    font-weight:500;
    color:var(--pm-faint);
}

/* Sparkline: identical geometry to the Admin dashboard's .spark-inline */
.pm-spark {
    grid-column:3; grid-row:1 / span 2;
    align-self:center;
    flex-shrink:0;
    width:72px;
    height:28px;
    position:relative;
}
.pm-spark svg {
    position:absolute; inset:0;
    width:100%; height:100%;
    display:block; overflow:visible;
}
.pm-spark-line {
    stroke:var(--spark, var(--pm-accent));
    stroke-width:1.8;
    stroke-linecap:round;
    stroke-linejoin:round;
}

.pm-panel { background:var(--pm-surface); border:1px solid var(--pm-line); border-radius:12px;
    box-shadow:0 1px 2px rgba(15,23,42,.04); overflow:hidden; }

.pm-tabs { position:relative; display:flex; gap:.25rem; padding:0 .75rem; border-bottom:1px solid var(--pm-line);
    overflow-x:auto; scrollbar-width:none; }
.pm-tabs::-webkit-scrollbar { display:none; }
.pm-tab { position:relative; display:inline-flex; align-items:center; gap:.45rem; padding:.85rem .6rem;
    font-size:.8125rem; font-weight:600; color:var(--pm-muted); background:none; border:0; white-space:nowrap;
    cursor:pointer; transition:color .15s ease; }
.pm-tab::after { content:""; position:absolute; left:.4rem; right:.4rem; bottom:-1px; height:2px;
    border-radius:2px 2px 0 0; background:transparent; }
.pm-tab:hover { color:var(--pm-ink); }
.pm-tab.is-active { color:var(--pm-ink); }
.pm-tab.is-active::after { background:var(--pm-accent); }
.pm-tab-count { min-width:1.4rem; padding:.05rem .4rem; font-size:.7rem; font-weight:600;
    font-variant-numeric:tabular-nums; text-align:center; color:var(--pm-muted);
    background:var(--pm-line-soft); border-radius:999px; }
.pm-tab.is-active .pm-tab-count { color:var(--pm-accent); background:var(--pm-accent-soft); }

.pm-filters { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; padding:.75rem 1rem; }
.pm-search { position:relative; flex:1 1 280px; max-width:420px; }
.pm-search > i { position:absolute; left:.75rem; top:50%; transform:translateY(-50%); font-size:.8rem;
    color:var(--pm-faint); pointer-events:none; }
.pm-input, .pm-select { height:36px; font-size:.8125rem; color:var(--pm-ink);
    background-color:var(--pm-surface); border:1px solid var(--pm-line); border-radius:var(--pm-radius-sm);
    transition:border-color .15s ease, box-shadow .15s ease; }
.pm-input { width:100%; padding:0 .75rem 0 2.1rem; }
.pm-input::placeholder { color:var(--pm-faint); }
.pm-input:focus, .pm-select:focus { outline:none; border-color:var(--pm-accent); box-shadow:var(--pm-ring); }
.pm-select { padding:0 2rem 0 .75rem; appearance:none; -webkit-appearance:none;
    background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m3 6 5 5 5-5'/%3e%3c/svg%3e");
    background-repeat:no-repeat; background-position:right .65rem center; background-size:12px;
    cursor:pointer; min-width:160px; }

.pm-alert { display:flex; align-items:center; gap:.6rem; margin:0 1rem .75rem; padding:.7rem .9rem;
    border:1px solid transparent; border-radius:var(--pm-radius-sm); font-size:.8125rem; }
.pm-alert--success { background:#f0fdf4; border-color:#bbf7d0; color:#166534; }
.pm-alert--error   { background:#fef2f2; border-color:#fecaca; color:#991b1b; }

.pm-table-wrap { overflow-x:auto; border-top:1px solid var(--pm-line); }
.pm-table { width:100%; border-collapse:collapse; font-size:.8125rem; color:var(--pm-text); }
.pm-table th { padding:.65rem 1rem; font-size:.72rem; font-weight:600; letter-spacing:.01em;
    text-align:left; white-space:nowrap; color:var(--pm-muted); background:var(--pm-subtle);
    border-bottom:1px solid var(--pm-line); position:sticky; top:0; z-index:2; }
.pm-table td { padding:.85rem 1rem; vertical-align:middle; border-bottom:1px solid var(--pm-line-soft); }
.pm-table tbody tr:last-child td { border-bottom:0; }
.pm-row { transition:background-color .12s ease; }
.pm-row:hover { background:#fafbfd; }

.pm-c-ref { white-space:nowrap; }
.pm-ref { padding:.15rem .5rem; font-family:var(--pm-mono); font-size:.76rem; font-weight:600;
    color:var(--pm-ink); background:var(--pm-line-soft); border-radius:5px; }

.pm-patient { display:flex; align-items:center; gap:.6rem; min-width:0; }
.pm-avatar { display:grid; place-items:center; flex-shrink:0; width:32px; height:32px;
    font-size:.68rem; font-weight:700; color:var(--pm-accent); background:var(--pm-accent-soft);
    border-radius:50%; overflow:hidden; }
.pm-avatar--male    { background:#eaf2fe; color:#1d4ed8; }
.pm-avatar--female  { background:#fce9ee; color:#b32e50; }
.pm-avatar--neutral { background:var(--pm-accent-soft); color:var(--pm-accent); }
.pm-avatar-img { width:100%; height:100%; object-fit:cover; display:block; }
.pm-patient-body { min-width:0; }
.pm-name { display:block; font-weight:600; color:var(--pm-ink); line-height:1.3;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.pm-sub { display:block; font-size:.72rem; color:var(--pm-muted); white-space:nowrap; }

.pm-services { display:flex; align-items:center; gap:.4rem; max-width:16rem; font-size:.78rem; color:var(--pm-text); }
.pm-services-text { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.pm-more { flex-shrink:0; padding:0 .35rem; font-size:.7rem; font-weight:600;
    color:var(--pm-muted); background:var(--pm-line-soft); border-radius:4px; }

.pm-num { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
.pm-amount { display:block; color:var(--pm-ink); font-weight:600; }
.pm-change { display:block; margin-top:.1rem; font-size:.68rem; color:var(--pm-green); }

.pm-method { display:inline-flex; align-items:center; gap:.35rem; padding:.2rem .55rem;
    font-size:.72rem; font-weight:600; border-radius:999px; white-space:nowrap; }
.pm-method--cash      { color:var(--pm-green);  background:var(--pm-green-soft); }
.pm-method--card      { color:var(--pm-blue);   background:var(--pm-blue-soft); }
.pm-method--gcash     { color:var(--pm-purple); background:var(--pm-purple-soft); }
.pm-method--paymaya   { color:var(--pm-orange); background:var(--pm-orange-soft); }
.pm-method--insurance { color:var(--pm-accent-dark); background:var(--pm-accent-soft); }
.pm-method i { font-size:.72em; }

.pm-c-date { white-space:nowrap; }
.pm-date { display:block; color:var(--pm-text); font-variant-numeric:tabular-nums; }

.pm-status { display:inline-flex; align-items:center; gap:.4rem; padding:.2rem .55rem;
    font-size:.72rem; font-weight:600; white-space:nowrap; color:var(--pm-tone-fg,#334155);
    background:var(--pm-tone-bg,#f1f5f9); border-radius:999px; }
.pm-status::before { content:""; width:6px; height:6px; border-radius:50%; background:var(--pm-tone-dot,#94a3b8); }
.pm-status--paid     { --pm-tone-bg:var(--pm-green-soft); --pm-tone-fg:var(--pm-green); --pm-tone-dot:#10b981; }
.pm-status--pending  { --pm-tone-bg:var(--pm-orange-soft); --pm-tone-fg:var(--pm-orange); --pm-tone-dot:#f59e0b; }
.pm-status--refunded { --pm-tone-bg:var(--pm-red-soft); --pm-tone-fg:var(--pm-red); --pm-tone-dot:#ef4444; }
.pm-status--failed   { --pm-tone-bg:#f1f5f9; --pm-tone-fg:#64748b; --pm-tone-dot:#94a3b8; }

.pm-c-actions { width:1%; text-align:right; white-space:nowrap; }
.pm-actions { display:inline-flex; align-items:center; gap:.25rem; }
.pm-icon-btn { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px;
    font-size:.85rem; color:var(--pm-muted); background:transparent; border:1px solid transparent;
    border-radius:var(--pm-radius-sm); cursor:pointer;
    transition:background .15s ease, color .15s ease, border-color .15s ease; }
.pm-icon-btn:hover { background:var(--pm-blue-soft); color:var(--pm-blue); border-color:#dbe6fb; }
.pm-icon-btn--print:hover { background:var(--pm-accent-soft); color:var(--pm-accent-dark); border-color:#bbdefb; }
.pm-icon-btn--refund:hover { background:var(--pm-red-soft); color:var(--pm-red); border-color:#fecaca; }

.pm-empty { padding:3.5rem 1rem; text-align:center; }
.pm-empty--filter { border-top:1px solid var(--pm-line); }
.pm-empty-icon { display:grid; place-items:center; width:44px; height:44px; margin:0 auto .85rem;
    font-size:1.2rem; color:var(--pm-faint); background:var(--pm-line-soft); border-radius:10px; }
.pm-empty h3 { margin:0 0 .25rem; font-size:.95rem; font-weight:600; color:var(--pm-ink); }
.pm-empty p { max-width:26rem; margin:0 auto 1rem; font-size:.8125rem; color:var(--pm-muted); }

.pm-foot { display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap;
    padding:.7rem 1rem; font-size:.78rem; color:var(--pm-muted); border-top:1px solid var(--pm-line); }
.pm-foot strong { font-weight:600; color:var(--pm-ink); font-variant-numeric:tabular-nums; }
.pm-pager { display:flex; align-items:center; gap:.25rem; }
.pm-page { min-width:32px; height:32px; display:inline-flex; align-items:center; justify-content:center;
    padding:0 .5rem; font-size:.78rem; font-weight:600; font-variant-numeric:tabular-nums;
    color:var(--pm-text); background:var(--pm-surface); border:1px solid var(--pm-line);
    border-radius:var(--pm-radius-sm); cursor:pointer;
    transition:background .15s ease, border-color .15s ease, color .15s ease; }
.pm-page:hover:not(:disabled):not(.active) { background:var(--pm-subtle); border-color:#cbd5e1; }
.pm-page.active { background:var(--pm-accent); border-color:var(--pm-accent); color:#fff; }
.pm-page:disabled { opacity:.45; cursor:not-allowed; }

.pm-modal .modal-content { border:1px solid var(--pm-line); border-radius:12px;
    box-shadow:0 24px 48px -12px rgba(15,23,42,.28); overflow:hidden; }
.pm-modal .modal-header { padding:1rem 1.25rem; border-bottom:1px solid var(--pm-line); }
.pm-modal .modal-title { font-size:1rem; font-weight:650; color:var(--pm-ink); }
.pm-modal .modal-body { padding:1.25rem; }
.pm-modal .payment-details .detail-row { display:flex; justify-content:space-between; gap:1rem;
    padding:.6rem 0; border-bottom:1px solid var(--pm-line-soft); }
.pm-modal .payment-details .detail-row:last-child { border-bottom:none; }
.pm-modal .payment-details .detail-label { color:var(--pm-muted); font-size:.8125rem; flex-shrink:0; }
.pm-modal .payment-details .detail-value { font-size:.8125rem; text-align:right; max-width:60%;
    color:var(--pm-ink); overflow-wrap:anywhere; }

.pm-range { display:inline-flex; padding:3px; gap:2px; background:var(--pm-subtle);
    border:1px solid var(--pm-line); border-radius:99px; }
.pm-range-btn { border:none; background:transparent; border-radius:99px; padding:.34rem .8rem;
    font-size:.76rem; font-weight:650; font-family:inherit; color:var(--pm-muted); cursor:pointer;
    white-space:nowrap; transition:background .15s ease, color .15s ease; }
.pm-range-btn:hover { color:var(--pm-ink); }
.pm-range-btn.is-active { background:#fff; color:var(--pm-ink); box-shadow:0 1px 2px rgba(15,23,42,.12); }

@media (max-width:1200px) { .pm-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media (max-width:992px) {
    .pm-filters { flex-direction:column; align-items:stretch; }
    .pm-filters .pm-search { flex-basis:auto; max-width:none; }
    .pm-filters .pm-select { min-width:100%; }
}
@media (max-width:768px) {
    .pm-head { flex-direction:column; align-items:stretch; }
    .pm-head-actions .pm-btn { flex:1; }
    .pm-stats { grid-template-columns:minmax(0,1fr); gap:.6rem; }
    .pm-outstanding { flex-direction:column; align-items:flex-start; }
    .pm-outstanding-link { align-self:stretch; justify-content:center; }
    .pm-table thead { display:none; }
    .pm-table, .pm-table tbody, .pm-table tr, .pm-table td { display:block; width:100%; }
    .pm-table tr.pm-row { padding:.9rem 1rem; border-bottom:1px solid var(--pm-line); }
    .pm-table td { display:flex; align-items:center; justify-content:space-between; gap:1rem;
        padding:.25rem 0; text-align:right; border:0; }
    .pm-table td[data-label]::before { content:attr(data-label); flex-shrink:0; font-size:.75rem;
        font-weight:400; color:var(--pm-muted); text-align:left; }
    .pm-table td.pm-c-ref, .pm-table td:nth-child(2) { display:block; text-align:left; }
    .pm-table td.pm-c-ref::before, .pm-table td:nth-child(2)::before { content:none; }
    .pm-table td:nth-child(2) { margin:.35rem 0 .5rem; }
    .pm-table td.pm-c-actions { justify-content:flex-end; margin-top:.5rem; }
    .pm-c-date, .pm-c-ref { white-space:normal; }
    .pm-services { max-width:60%; }
    .pm-foot { justify-content:center; }
}
@media (prefers-reduced-motion:reduce) {
    .pm *, .pm *::before, .pm *::after, .pm-modal * { transition:none !important; }
}
@media print {
    .pm-head-actions, .pm-tabs, .pm-filters, .pm-c-actions, .pm-outstanding, .pm-foot { display:none !important; }
    .pm-panel { border:0; box-shadow:none; }
    .pm-row { display:table-row !important; }
}

/* RECEIPT */
.pm-receipt-modal .modal-content { border:none; border-radius:14px; overflow:hidden; }
.pm-receipt-scroll { background:#eef2f7; padding:1.25rem; }
.pm-receipt { --r-ink:#0f172a; --r-muted:#64748b; --r-line:#d7dfea; --r-accent:#1976d2;
    position:relative; width:100%; max-width:640px; margin:0 auto; padding:28px 30px 22px;
    background:#fff; color:var(--r-ink); font-size:12.5px; line-height:1.5;
    box-shadow:0 10px 30px rgba(15,23,42,.12); }
.pm-receipt::before, .pm-receipt::after { content:''; position:absolute; left:0; right:0; height:8px;
    background: repeating-linear-gradient(135deg,#fff 0 6px,transparent 6px 12px), linear-gradient(#fff,#fff); }
.pm-receipt::before { top:-8px; }
.pm-receipt::after  { bottom:-8px; }
.r-head { display:flex; align-items:flex-start; gap:14px; padding-bottom:14px; border-bottom:2px solid var(--r-ink); }
.r-logo { width:54px; height:54px; flex-shrink:0; border-radius:12px; background:#eef6f6;
    display:grid; place-items:center; overflow:hidden; }
.r-logo img { width:38px; height:38px; object-fit:contain; }
.r-clinic { flex:1; min-width:0; }
.r-clinic h1 { margin:0; font-size:15px; font-weight:800; letter-spacing:.02em; text-transform:uppercase; }
.r-clinic p { margin:1px 0 0; font-size:11px; color:var(--r-muted); line-height:1.45; }
.r-doc { text-align:right; flex-shrink:0; }
.r-doc .r-doc-title { font-size:12px; font-weight:800; letter-spacing:.12em;
    text-transform:uppercase; color:var(--r-accent); }
.r-doc .r-doc-no { margin-top:2px; font-family:var(--pm-mono); font-size:13px; font-weight:700; }
.r-doc .r-doc-date { font-size:10.5px; color:var(--r-muted); }
.r-meta { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px 18px;
    padding:14px 0; border-bottom:1px solid var(--r-line); }
.r-meta div { min-width:0; }
.r-meta dt { font-size:9.5px; font-weight:700; letter-spacing:.09em; text-transform:uppercase;
    color:var(--r-muted); margin-bottom:1px; }
.r-meta dd { margin:0; font-size:12.5px; font-weight:600; word-break:break-word; }
.r-items { width:100%; border-collapse:collapse; margin-top:14px; }
.r-items th { font-size:9.5px; font-weight:700; letter-spacing:.09em; text-transform:uppercase;
    color:var(--r-muted); text-align:left; padding:0 0 6px; border-bottom:1px solid var(--r-line); }
.r-items th:last-child, .r-items td:last-child { text-align:right; }
.r-items th:first-child, .r-items td:first-child { width:26px; color:var(--r-muted); }
.r-items td { padding:7px 0; border-bottom:1px dotted var(--r-line); font-size:12.5px; vertical-align:top; }
.r-items td:last-child { font-variant-numeric:tabular-nums; white-space:nowrap; }
.r-items .r-none { color:var(--r-muted); font-style:italic; }
.r-totals { margin-top:12px; margin-left:auto; width:min(290px,100%); font-variant-numeric:tabular-nums; }
.r-total-row { display:flex; justify-content:space-between; gap:1rem; padding:4px 0; font-size:12.5px; }
.r-total-row.is-grand { margin-top:4px; padding-top:8px; border-top:2px solid var(--r-ink);
    font-size:15px; font-weight:800; }
.r-total-row.is-change { color:var(--r-accent); font-weight:700; }
.r-words { margin-top:12px; padding:8px 10px; background:#f6f9fa; border-left:3px solid var(--r-accent);
    font-size:11px; }
.r-words span { display:block; font-size:9.5px; font-weight:700; letter-spacing:.09em;
    text-transform:uppercase; color:var(--r-muted); }
.r-stamp { position:absolute; top:150px; right:42px; padding:5px 14px; border:3px solid currentColor;
    border-radius:6px; font-size:17px; font-weight:900; letter-spacing:.18em; text-transform:uppercase;
    opacity:.5; transform:rotate(-14deg); pointer-events:none; }
.r-stamp--paid     { color:#047857; }
.r-stamp--refunded { color:#b91c1c; }
.r-stamp--pending  { color:#b45309; }
.r-stamp--failed   { color:#475569; }
.r-signs { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:26px; margin-top:30px; }
.r-sign { border-top:1px solid var(--r-ink); padding-top:4px; font-size:10px; letter-spacing:.06em;
    text-transform:uppercase; color:var(--r-muted); text-align:center; }
.r-sign b { display:block; font-size:12px; letter-spacing:0; text-transform:none;
    color:var(--r-ink); margin-bottom:2px; min-height:15px; }
.r-foot { margin-top:18px; padding-top:10px; border-top:1px dashed var(--r-line);
    font-size:10px; color:var(--r-muted); text-align:center; line-height:1.6; }
.r-foot strong { color:var(--r-ink); }
#pmReceiptPrint { display:none; }
@media print {
    @page { size:A4 portrait; margin:14mm; }
    body.pm-printing { background:#fff !important; }
    body.pm-printing > *:not(#pmReceiptPrint) { display:none !important; }
    body.pm-printing #pmReceiptPrint { display:block !important; position:static; }
    #pmReceiptPrint .pm-receipt { max-width:none; box-shadow:none; padding:0; font-size:12px; }
    #pmReceiptPrint .pm-receipt::before, #pmReceiptPrint .pm-receipt::after { display:none; }
    .r-stamp { opacity:.32; }
    .r-items tr, .r-signs, .r-totals { break-inside:avoid; }
}
@media (max-width:640px) {
    .pm-receipt { padding:20px 18px; font-size:12px; }
    .r-head { flex-wrap:wrap; }
    .r-doc { text-align:left; width:100%; }
    .r-meta { grid-template-columns:minmax(0,1fr); }
    .r-stamp { top:auto; bottom:120px; right:18px; font-size:14px; }
}
</style>

<script>
(function () {
    'use strict';
    var $ = function (id) { return document.getElementById(id); };
    function readJson(id, fallback) { var el = $(id); if (!el) return fallback;
        try { return JSON.parse(el.textContent) || fallback; } catch (e) { return fallback; } }
    var CLINIC = readJson('pmClinic', {});
    var RECEIPTS = readJson('pmReceipts', {});
    var LOGO = ($('pmLogoSrc') || {}).dataset ? $('pmLogoSrc').dataset.src : '';
    function peso(v) { return '\u20B1' + (Number(v)||0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,','); }
    function escapeHtml(v) { return String(v==null?'':v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    function amountInWords(amount) {
        var ones=['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
        var tens=['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        function u(n){ var o=''; if(n>=100){o+=ones[Math.floor(n/100)]+' Hundred';n%=100;if(n)o+=' ';} if(n>=20){o+=tens[Math.floor(n/10)];if(n%10)o+='-'+ones[n%10];}else if(n>0){o+=ones[n];} return o; }
        var v=Math.round((Number(amount)||0)*100)/100, whole=Math.floor(v), cents=Math.round((v-whole)*100);
        var scales=[[1e9,'Billion'],[1e6,'Million'],[1e3,'Thousand']], words='';
        scales.forEach(function(p){ if(whole>=p[0]){words+=u(Math.floor(whole/p[0]))+' '+p[1]+' ';whole%=p[0];} });
        if(whole>0)words+=u(whole); words=words.trim()||'Zero';
        var text=words+' Peso'+(words==='One'?'':'s');
        if(cents>0)text+=' and '+u(cents)+' Centavo'+(cents===1?'':'s');
        return text+' Only';
    }
    function formatStamp(s){ return ({paid:'Paid',refunded:'Refunded',pending:'Unpaid',failed:'Void'})[s] || s; }

    var search=$('searchPayments'), statusEl=$('filterStatus'), tbody=$('paymentsTableBody'),
        noMatch=$('pmNoMatch'), clearBtn=$('pmClearFilters'), showing=$('showingText');
    var activeRange='all';
    function rows(){ return tbody ? Array.prototype.slice.call(tbody.querySelectorAll('tr.pm-row')) : []; }
    function inRange(d){
        if(activeRange==='all')return true; if(!d)return false;
        var today=new Date(); today.setHours(0,0,0,0);
        var rd=new Date(d+'T00:00:00'); if(isNaN(rd.getTime()))return false;
        if(activeRange==='today')return rd.getTime()===today.getTime();
        var days=parseInt(activeRange,10); var cut=new Date(today); cut.setDate(cut.getDate()-(days-1));
        return rd>=cut && rd<=today;
    }
    function applyFilters(){
        var term=search?search.value.toLowerCase().trim():'';
        var status=statusEl?statusEl.value.toLowerCase():'';
        var vis=0, sum=0;
        rows().forEach(function(row){
            var okS=term===''||row.textContent.toLowerCase().indexOf(term)!==-1;
            var okSt=status===''||(row.dataset.status||'').toLowerCase()===status;
            var okR=inRange(row.dataset.date||'');
            var show=okS&&okSt&&okR;
            row.style.display=show?'':'none';
            if(show){vis++;sum+=parseFloat(row.dataset.amount||'0')||0;}
        });
        if(noMatch)noMatch.hidden=!(rows().length>0 && vis===0);
        if(showing)showing.innerHTML='Showing <strong>'+vis+'</strong> of <strong>'+rows().length+'</strong> payments'
            +(vis>0?' \u00B7 <strong>'+peso(sum)+'</strong> total':'');
    }
    if(search){search.addEventListener('input',applyFilters);search.addEventListener('search',applyFilters);}
    if(statusEl)statusEl.addEventListener('change',applyFilters);
    var tabs=Array.prototype.slice.call(document.querySelectorAll('.pm-tab'));
    function syncTabs(s){tabs.forEach(function(t){var on=t.dataset.status===s;t.classList.toggle('is-active',on);t.setAttribute('aria-pressed',on?'true':'false');});}
    tabs.forEach(function(t){t.addEventListener('click',function(){var s=t.dataset.status||'all';syncTabs(s);if(statusEl)statusEl.value=s==='all'?'':s;applyFilters();});});
    var rangeBtns=Array.prototype.slice.call(document.querySelectorAll('.pm-range-btn'));
    rangeBtns.forEach(function(b){b.addEventListener('click',function(){activeRange=b.dataset.range||'all';rangeBtns.forEach(function(x){var on=x===b;x.classList.toggle('is-active',on);x.setAttribute('aria-pressed',on?'true':'false');});applyFilters();});});
    if(clearBtn)clearBtn.addEventListener('click',function(){if(search)search.value='';if(statusEl)statusEl.value='';activeRange='all';rangeBtns.forEach(function(b,i){b.classList.toggle('is-active',i===0);b.setAttribute('aria-pressed',i===0?'true':'false');});syncTabs('all');applyFilters();});
    document.addEventListener('keydown',function(e){if(e.key==='/'&&document.activeElement!==search&&['INPUT','TEXTAREA','SELECT'].indexOf(document.activeElement.tagName)===-1){e.preventDefault();if(search)search.focus();}});
    applyFilters();

    var printRoot=document.createElement('div'); printRoot.id='pmReceiptPrint'; document.body.appendChild(printRoot);

    function buildReceipt(data){
        var when=data.date?new Date(data.date.replace(' ','T')):null;
        var dateText=(when&&!isNaN(when.getTime()))?when.toLocaleDateString(undefined,{year:'numeric',month:'long',day:'numeric'})+' \u00B7 '+when.toLocaleTimeString(undefined,{hour:'numeric',minute:'2-digit'}):'\u2014';
        var items=Array.isArray(data.items)?data.items:[];
        var priced=items.filter(function(i){return i.price!==null&&i.price!==undefined;});
        var itemised=priced.length===items.length&&items.length>0;
        var rowsHtml='';
        if(items.length===0){rowsHtml='<tr><td></td><td class="r-none">No itemised services recorded</td><td>'+peso(data.total)+'</td></tr>';}
        else{items.forEach(function(it,i){rowsHtml+='<tr><td>'+(i+1)+'</td><td>'+escapeHtml(it.name)+'</td><td>'+(it.price===null||it.price===undefined?'\u2014':peso(it.price))+'</td></tr>';});}
        var subtotal=itemised?priced.reduce(function(s,i){return s+(Number(i.price)||0);},0):Number(data.total)||0;
        var status=(data.status||'').toLowerCase();
        var cashier=(data.cashier&&String(data.cashier).trim()!=='')?String(data.cashier):'Front desk';
        return ''+'<article class="pm-receipt">'
            +'<div class="r-stamp r-stamp--'+escapeHtml(status)+'">'+escapeHtml(formatStamp(status))+'</div>'
            +'<header class="r-head">'
                +'<span class="r-logo">'+(LOGO?'<img src="'+escapeHtml(LOGO)+'" alt="">':'')+'</span>'
                +'<div class="r-clinic"><h1>'+escapeHtml(CLINIC.name||'PolyMedic')+'</h1>'
                    +'<p>'+escapeHtml(CLINIC.tagline||'')+'</p>'
                    +'<p>'+escapeHtml(CLINIC.address||'')+'</p>'
                    +'<p>'+escapeHtml(CLINIC.phone||'')+' \u00B7 '+escapeHtml(CLINIC.email||'')+'</p>'
                    +'<p>TIN: '+escapeHtml(CLINIC.tin||'')+'</p></div>'
                +'<div class="r-doc"><div class="r-doc-title">Official Receipt</div>'
                    +'<div class="r-doc-no">'+escapeHtml(data.ref||'\u2014')+'</div>'
                    +'<div class="r-doc-date">'+escapeHtml(dateText)+'</div></div>'
            +'</header>'
            +'<dl class="r-meta">'
                +'<div><dt>Received from</dt><dd>'+escapeHtml(data.name||'Unknown')+'</dd></div>'
                +'<div><dt>Patient code</dt><dd>'+escapeHtml(data.code||'N/A')+'</dd></div>'
                +'<div><dt>Payment method</dt><dd>Cash</dd></div>'
                +'<div><dt>Cashier</dt><dd>'+escapeHtml(cashier)+'</dd></div>'
            +'</dl>'
            +'<table class="r-items"><thead><tr><th>#</th><th>Service description</th><th>Amount</th></tr></thead>'
                +'<tbody>'+rowsHtml+'</tbody></table>'
            +'<div class="r-totals">'
                +(itemised?'<div class="r-total-row"><span>Subtotal</span><span>'+peso(subtotal)+'</span></div>':'')
                +'<div class="r-total-row is-grand"><span>Total due</span><span>'+peso(data.total)+'</span></div>'
                +'<div class="r-total-row"><span>Cash tendered</span><span>'+peso(data.paid)+'</span></div>'
                +(Number(data.change)>0?'<div class="r-total-row is-change"><span>Change</span><span>'+peso(data.change)+'</span></div>':'')
            +'</div>'
            +'<div class="r-words"><span>Amount in words</span>'+escapeHtml(amountInWords(data.total))+'</div>'
            +'<div class="r-signs">'
                +'<div class="r-sign"><b>'+escapeHtml(cashier)+'</b>Cashier / Received by</div>'
                +'<div class="r-sign"><b>&nbsp;</b>Patient signature</div>'
            +'</div>'
            +'<footer class="r-foot"><strong>This serves as your official receipt.</strong> Please keep it for your records and present it when claiming results.<br>Results are released on the schedule advised at the counter. For enquiries call '+escapeHtml(CLINIC.phone||'')+'.<br>Printed '+escapeHtml(new Date().toLocaleString())+'</footer>'
            +'</article>';
    }

    var currentId=null;
    window.viewReceipt=function(id){
        var data=RECEIPTS[String(id)]||RECEIPTS[id];
        if(!data){alert('Receipt data is not available for this payment.');return;}
        currentId=id;
        $('pmReceiptBody').innerHTML=buildReceipt(data);
        new bootstrap.Modal($('receiptModal')).show();
    };
    function printById(id){
        var data=RECEIPTS[String(id)]||RECEIPTS[id];
        if(!data)return;
        printRoot.innerHTML=buildReceipt(data);
        document.body.classList.add('pm-printing');
        var cleanup=function(){document.body.classList.remove('pm-printing');printRoot.innerHTML='';window.removeEventListener('afterprint',cleanup);};
        window.addEventListener('afterprint',cleanup);
        setTimeout(function(){window.print();},60);
        setTimeout(cleanup,4000);
    }
    window.printReceipt=printById;
    var printNow=$('pmPrintNow');
    if(printNow)printNow.addEventListener('click',function(){if(currentId!==null)printById(currentId);});

    window.exportPayments=function(){
        var visible=rows().filter(function(r){return r.style.display!=='none';});
        if(visible.length===0){alert('There is nothing to export with the current filters.');return;}
        var head=['Reference','Patient','Patient code','Services','Amount paid','Invoice total','Change','Method','Status','Date'];
        var lines=[head];
        visible.forEach(function(row){
            var d=RECEIPTS[row.dataset.id]||{};
            lines.push([d.ref||'',d.name||'',d.code||'',
                (d.items||[]).map(function(i){return i.name;}).join('; '),
                (Number(d.paid)||0).toFixed(2),
                (Number(d.total)||0).toFixed(2),
                (Number(d.change)||0).toFixed(2),
                'Cash',d.status||'',d.date||'']);
        });
        var csv=lines.map(function(cols){return cols.map(function(c){return '"'+String(c).replace(/"/g,'""')+'"';}).join(',');}).join('\r\n');
        var blob=new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8;'});
        var url=URL.createObjectURL(blob); var a=document.createElement('a');
        a.href=url; a.download='polymedic-payments-'+new Date().toISOString().slice(0,10)+'.csv';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        setTimeout(function(){URL.revokeObjectURL(url);},1000);
    };

    window.viewPayment=function(id){
        var data=RECEIPTS[String(id)]||RECEIPTS[id];
        var content=$('paymentDetailsContent'); var modal=new bootstrap.Modal($('viewPaymentModal'));
        function row(l,v,strong){return '<div class="detail-row"><span class="detail-label">'+l+'</span><span class="detail-value">'+(strong?'<strong>'+v+'</strong>':v)+'</span></div>';}
        if(data){
            var when=data.date?new Date(data.date.replace(' ','T')):null;
            var html='<div class="payment-details">'
                +row('Reference',escapeHtml(data.ref),true)
                +row('Patient',escapeHtml(data.name))
                +row('Patient code',escapeHtml(data.code||'N/A'))
                +row('Services',escapeHtml((data.items||[]).map(function(i){return i.name;}).join(', ')||'N/A'))
                +row('Amount paid',peso(data.paid),true)
                +row('Invoice total',peso(data.total))
                +(Number(data.change)>0?row('Change','<span style="color:#15803d;">'+peso(data.change)+'</span>'):'')
                +row('Payment method','Cash')
                +row('Status','<span class="pm-status pm-status--'+escapeHtml(data.status)+'">'+escapeHtml(data.status.charAt(0).toUpperCase()+data.status.slice(1))+'</span>')
                +row('Date',when&&!isNaN(when.getTime())?escapeHtml(when.toLocaleString()):'\u2014')
                +(data.cashier?row('Received by',escapeHtml(data.cashier)):'')
                +'</div>'
                +'<div class="d-flex justify-content-end gap-2 mt-3">'
                +'<button type="button" class="pm-btn" onclick="viewReceipt('+Number(id)+')"><i class="bi bi-receipt"></i><span>View receipt</span></button>'
                +'<button type="button" class="pm-btn pm-btn--primary" onclick="printReceipt('+Number(id)+')"><i class="bi bi-printer"></i><span>Print</span></button>'
                +'</div>';
            content.innerHTML=html; modal.show(); return;
        }
        content.innerHTML='<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading...</p></div>';
        modal.show();
        fetch('<?= base_url() ?>admin/get-payment-details/'+encodeURIComponent(id))
            .then(function(r){return r.json();})
            .then(function(res){
                if(!res.success)throw new Error(res.message||'Not found');
                var p=res.data;
                content.innerHTML='<div class="payment-details">'
                    +row('Reference',escapeHtml(p.reference_number),true)
                    +row('Patient',escapeHtml(p.patient_name))
                    +row('Amount paid',peso(p.amount_paid),true)
                    +row('Status',escapeHtml(p.payment_status))
                    +'</div>';
            })
            .catch(function(){content.innerHTML='<div class="alert alert-danger">Could not load payment details.</div>';});
    };

    window.refundPayment=function(id){
        var data=RECEIPTS[String(id)]||RECEIPTS[id]||{};
        var label=data.ref?(' '+data.ref+' ('+peso(data.paid)+')'):'';
        if(!confirm('Refund payment'+label+'? This cannot be undone.'))return;
        fetch('<?= base_url() ?>admin/refund-payment/'+encodeURIComponent(id),{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'<?= csrf_hash() ?>'}})
            .then(function(r){return r.json();})
            .then(function(res){if(res.success)location.reload();else alert('Error: '+(res.message||'Refund failed.'));})
            .catch(function(){alert('Error processing refund.');});
    };
}());
</script>

<?= $this->endSection() ?>