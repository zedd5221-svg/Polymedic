<?= $this->extend($layout) ?>
<?= $this->section('pageTitle') ?>Billing<?= $this->endSection() ?>
<?= $this->section($section) ?>

<?php
/** @var array $invoices @var int $total @var int $page @var int $perPage @var int $totalPages */
/** @var array $filters @var array $stats @var array $counts @var string $routeBase @var bool $isAdminUser */

$statusMeta = [
    'draft'    => ['label' => 'Draft',    'tone' => 'draft',    'icon' => 'bi-file-earmark'],
    'unpaid'   => ['label' => 'Unpaid',   'tone' => 'unpaid',   'icon' => 'bi-exclamation-circle'],
    'partial'  => ['label' => 'Partial',  'tone' => 'partial',  'icon' => 'bi-circle-half'],
    'paid'     => ['label' => 'Paid',     'tone' => 'paid',     'icon' => 'bi-check-circle'],
    'void'     => ['label' => 'Void',     'tone' => 'void',     'icon' => 'bi-slash-circle'],
    'refunded' => ['label' => 'Refunded', 'tone' => 'refunded', 'icon' => 'bi-arrow-counterclockwise'],
];

$buildUrl = function (array $override = []) use ($filters, $routeBase) {
    $q = array_merge([
        'status' => $filters['status'] ?? 'all',
        'q'      => $filters['search'] ?? '',
        'from'   => $filters['from']   ?? '',
        'to'     => $filters['to']     ?? '',
    ], $override);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null);
    return base_url($routeBase) . ($q ? '?' . http_build_query($q) : '');
};

$hasFilter = !empty($filters['search']) || !empty($filters['from']) || !empty($filters['to']);
?>

<div class="bl">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="bl-alert bl-alert--success">
            <i class="bi bi-check-circle-fill"></i>
            <span><?= esc(session()->getFlashdata('success')) ?></span>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="bl-alert bl-alert--error">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
    <?php endif; ?>

    <!-- HEADER -->
    <header class="bl-head">
        <div>
            <h2 class="bl-title">Billing</h2>
        </div>
        <div class="bl-head-actions">
            <?php if ($isAdminUser): ?>
                <a class="bl-btn" href="<?= base_url($routeBase . '/reports') ?>">
                    <i class="bi bi-bar-chart-fill"></i>
                    <span>Reports</span>
                </a>
            <?php endif; ?>
            <a class="bl-btn bl-btn--primary" href="<?= base_url($routeBase . '/create') ?>">
                <i class="bi bi-plus-lg"></i>
                <span>New invoice</span>
            </a>
        </div>
    </header>

    <!-- STATS -->
    <div class="bl-stats">
        <div class="bl-stat bl-stat--green">
            <div class="bl-stat-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="bl-stat-body">
                <div class="bl-stat-value">₱<?= number_format($stats['today_collected'] ?? 0, 2) ?></div>
                <div class="bl-stat-label">Collected today</div>
            </div>
        </div>
        <div class="bl-stat bl-stat--teal">
            <div class="bl-stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="bl-stat-body">
                <div class="bl-stat-value">₱<?= number_format($stats['month_collected'] ?? 0, 2) ?></div>
                <div class="bl-stat-label">Collected this month</div>
            </div>
        </div>
        <div class="bl-stat bl-stat--warn">
            <div class="bl-stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="bl-stat-body">
                <div class="bl-stat-value">₱<?= number_format($stats['outstanding'] ?? 0, 2) ?></div>
                <div class="bl-stat-label">Outstanding balance</div>
            </div>
        </div>
        <div class="bl-stat bl-stat--blue">
            <div class="bl-stat-icon"><i class="bi bi-receipt-cutoff"></i></div>
            <div class="bl-stat-body">
                <div class="bl-stat-value"><?= number_format(($counts['unpaid'] ?? 0) + ($counts['partial'] ?? 0)) ?></div>
                <div class="bl-stat-label">Unsettled invoices</div>
            </div>
        </div>
    </div>

    <!-- PANEL -->
    <section class="bl-panel">

        <div class="bl-tabs">
            <?php
            $tabList = [
                'all'      => 'All',
                'unpaid'   => 'Unpaid',
                'partial'  => 'Partial',
                'paid'     => 'Paid',
                'void'     => 'Void',
                'refunded' => 'Refunded',
            ];
            foreach ($tabList as $key => $label):
                $active = ($filters['status'] ?? 'all') === $key;
            ?>
                <a href="<?= esc($buildUrl(['status' => $key, 'page' => 1]), 'attr') ?>"
                   class="bl-tab<?= $active ? ' is-active' : '' ?>"
                   aria-pressed="<?= $active ? 'true' : 'false' ?>">
                    <?= esc($label) ?>
                    <span class="bl-tab-count"><?= (int) ($counts[$key] ?? 0) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <form class="bl-filters" method="get" action="<?= base_url($routeBase) ?>">
            <input type="hidden" name="status" value="<?= esc($filters['status'] ?? 'all', 'attr') ?>">

            <div class="bl-search">
                <i class="bi bi-search"></i>
                <input type="search" class="bl-input" name="q"
                       value="<?= esc($filters['search'] ?? '', 'attr') ?>"
                       placeholder="Search invoice no., patient name, or patient code"
                       autocomplete="off">
            </div>

            <div class="bl-date-range">
                <i class="bi bi-calendar3 bl-date-icon"></i>
                <input type="date" class="bl-input bl-input--date" name="from"
                       value="<?= esc($filters['from'] ?? '', 'attr') ?>" aria-label="From">
                <span class="bl-to">→</span>
                <input type="date" class="bl-input bl-input--date" name="to"
                       value="<?= esc($filters['to'] ?? '', 'attr') ?>" aria-label="To">
            </div>

            <button type="submit" class="bl-btn bl-btn--primary"><i class="bi bi-funnel"></i> Filter</button>
            <?php if ($hasFilter): ?>
                <a href="<?= base_url($routeBase) ?>" class="bl-btn"><i class="bi bi-x-lg"></i> Clear</a>
            <?php endif; ?>
        </form>

        <div class="bl-table-wrap">
            <table class="bl-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Patient</th>
                        <th>Date</th>
                        <th class="bl-num">Total</th>
                        <th class="bl-num">Paid</th>
                        <th class="bl-num">Balance</th>
                        <th>Status</th>
                        <th class="bl-actions-col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="bl-empty">
                                    <i class="bi bi-receipt"></i>
                                    <h3>No invoices found</h3>
                                    <p>Invoices are created automatically when a diagnostic request is priced, or manually with the button above.</p>
                                    <a class="bl-btn bl-btn--primary" href="<?= base_url($routeBase . '/create') ?>">
                                        <i class="bi bi-plus-lg"></i> New invoice
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: foreach ($invoices as $inv):
                        $meta    = $statusMeta[$inv['status']] ?? ['label' => $inv['status'], 'tone' => 'draft', 'icon' => 'bi-circle'];
                        $date    = !empty($inv['created_at']) ? date('M j, Y', strtotime($inv['created_at'])) : '—';
                        $total   = (float) ($inv['total']       ?? 0);
                        $paid    = (float) ($inv['amount_paid'] ?? 0);
                        $balance = (float) ($inv['balance']     ?? 0);
                        $pct     = $total > 0 ? min(100, round(($paid / $total) * 100)) : 0;
                    ?>
                        <tr>
                            <td class="bl-c-ref">
                                <a href="<?= base_url($routeBase . '/view/' . $inv['id']) ?>" class="bl-ref">
                                    <?= esc($inv['invoice_no']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="bl-patient">
                                    <span class="bl-name"><?= esc($inv['patient_name']) ?></span>
                                    <?php if (!empty($inv['patient_code'])): ?>
                                        <span class="bl-sub"><?= esc($inv['patient_code']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="bl-c-date"><?= esc($date) ?></td>
                            <td class="bl-num">₱<?= number_format($total, 2) ?></td>
                            <td class="bl-num">
                                <div class="bl-paid-cell">
                                    <span>₱<?= number_format($paid, 2) ?></span>
                                    <?php if ($total > 0 && $inv['status'] !== 'void'): ?>
                                        <span class="bl-progress" title="<?= $pct ?>% paid"><span style="width: <?= $pct ?>%"></span></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="bl-num <?= $balance > 0 ? 'bl-balance' : '' ?>">₱<?= number_format($balance, 2) ?></td>
                            <td>
                                <span class="bl-status bl-status--<?= esc($meta['tone'], 'attr') ?>">
                                    <i class="bi <?= esc($meta['icon'], 'attr') ?>"></i>
                                    <?= esc($meta['label']) ?>
                                </span>
                            </td>
                            <td class="bl-c-actions">
                                <div class="bl-actions">
                                    <a class="bl-icon-btn" href="<?= base_url($routeBase . '/view/' . $inv['id']) ?>"
                                       title="View invoice">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($inv['status'] !== 'void' && $inv['status'] !== 'refunded' && $balance > 0): ?>
                                        <a class="bl-icon-btn bl-icon-btn--primary"
                                           href="<?= base_url($routeBase . '/view/' . $inv['id'] . '#pay') ?>"
                                           title="Record payment">
                                            <i class="bi bi-cash-coin"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <footer class="bl-foot">
                <span>Page <strong><?= (int) $page ?></strong> of <strong><?= (int) $totalPages ?></strong></span>
                <nav class="bl-pager">
                    <?php
                    $prev = max(1, $page - 1);
                    $next = min($totalPages, $page + 1);
                    ?>
                    <a class="bl-page<?= $page <= 1 ? ' is-disabled' : '' ?>"
                       href="<?= $page <= 1 ? '#' : esc($buildUrl(['page' => $prev]), 'attr') ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <?php for ($i = 1; $i <= $totalPages; $i++):
                        if ($i === 1 || $i === $totalPages || abs($i - $page) <= 1):
                    ?>
                        <a class="bl-page<?= $i === $page ? ' active' : '' ?>"
                           href="<?= esc($buildUrl(['page' => $i]), 'attr') ?>"><?= $i ?></a>
                    <?php elseif (abs($i - $page) === 2): ?>
                        <span class="bl-gap">…</span>
                    <?php endif; endfor; ?>
                    <a class="bl-page<?= $page >= $totalPages ? ' is-disabled' : '' ?>"
                       href="<?= $page >= $totalPages ? '#' : esc($buildUrl(['page' => $next]), 'attr') ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </nav>
            </footer>
        <?php endif; ?>

    </section>
</div>

<style>
/* ── Billing namespace ─────────────────────────────────────── */
.bl {
    --bl-ink:#0f172a; --bl-text:#334155; --bl-muted:#64748b; --bl-faint:#94a3b8;
    --bl-line:#e2e8f0; --bl-line-soft:#f1f5f9; --bl-surface:#fff; --bl-subtle:#f8fafc;
    --bl-accent:#0d9488; --bl-accent-dark:#0f766e; --bl-accent-soft:#e6f7f7;
    --bl-warn:#b45309; --bl-warn-soft:#fff7ed;
    --bl-danger:#b91c1c; --bl-danger-soft:#fef2f2;
    --bl-green:#047857; --bl-green-soft:#ecfdf5;
    --bl-blue:#1d4ed8; --bl-blue-soft:#eaf2fe;
    --bl-mono:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
    color:var(--bl-text);
    font-size:.875rem;
}
.bl *:focus-visible { outline:2px solid var(--bl-accent); outline-offset:2px; border-radius:4px; }

.bl-alert {
    display:flex; align-items:center; gap:.55rem;
    padding:.7rem .9rem; margin-bottom:1rem;
    border:1px solid transparent; border-radius:10px; font-size:.8125rem;
}
.bl-alert--success { background:#f0fdf4; border-color:#bbf7d0; color:#166534; }
.bl-alert--error   { background:#fef2f2; border-color:#fecaca; color:#991b1b; }

.bl-head { display:flex; align-items:flex-end; justify-content:space-between;
           gap:1rem; flex-wrap:wrap; margin-bottom:1.1rem; }
.bl-title { margin:0 0 .2rem; font-size:1.25rem; font-weight:650;
            letter-spacing:-.015em; color:var(--bl-ink); }
.bl-lede { margin:0; font-size:.8125rem; color:var(--bl-muted); }
.bl-dot  { margin:0 .35rem; color:var(--bl-faint); }
.bl-head-actions { display:flex; gap:.5rem; flex-wrap:wrap; }

.bl-btn {
    display:inline-flex; align-items:center; gap:.4rem;
    height:36px; padding:0 .9rem;
    font-size:.8125rem; font-weight:600; line-height:1;
    color:var(--bl-text); background:var(--bl-surface);
    border:1px solid var(--bl-line); border-radius:8px;
    text-decoration:none; cursor:pointer;
    transition:background .15s, border-color .15s, color .15s;
}
.bl-btn:hover { background:var(--bl-subtle); border-color:#cbd5e1; color:var(--bl-ink); }
.bl-btn i { font-size:.9em; }
.bl-btn--primary, .bl-btn--primary:hover { color:#fff; }
.bl-btn--primary { background:var(--bl-accent); border-color:var(--bl-accent);
                   box-shadow:0 1px 2px rgba(13,148,136,.25); }
.bl-btn--primary:hover { background:var(--bl-accent-dark); border-color:var(--bl-accent-dark);
                         box-shadow:0 4px 12px rgba(13,148,136,.25); }

/* STATS */
.bl-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr));
            gap:.9rem; margin-bottom:1.1rem; }
.bl-stat {
    position:relative; display:flex; align-items:center; gap:.9rem;
    padding:1rem 1.1rem;
    background:var(--bl-surface); border:1px solid var(--bl-line);
    border-radius:12px; box-shadow:0 1px 2px rgba(15,23,42,.04);
    overflow:hidden;
}
.bl-stat::before {
    content:""; position:absolute; inset:0 0 auto 0; height:3px;
    background:var(--tone,var(--bl-line));
}
.bl-stat--green { --tone:#10b981; }
.bl-stat--teal  { --tone:var(--bl-accent); }
.bl-stat--warn  { --tone:#f59e0b; background:linear-gradient(180deg,#fffbeb,var(--bl-surface)); }
.bl-stat--blue  { --tone:#3b82f6; }
.bl-stat-icon {
    display:grid; place-items:center; flex-shrink:0;
    width:38px; height:38px; border-radius:10px; font-size:1rem;
    color:var(--tone);
    background:color-mix(in srgb, var(--tone) 12%, transparent);
}
.bl-stat-body { min-width:0; }
.bl-stat-value { font-size:1.2rem; font-weight:700; color:var(--bl-ink);
                 font-variant-numeric:tabular-nums; letter-spacing:-.02em; line-height:1.15; }
.bl-stat-label { margin-top:.2rem; font-size:.78rem; color:var(--bl-muted); }

/* PANEL */
.bl-panel { background:var(--bl-surface); border:1px solid var(--bl-line);
            border-radius:12px; box-shadow:0 1px 2px rgba(15,23,42,.04); overflow:hidden; }

/* TABS */
.bl-tabs { display:flex; gap:.25rem; padding:0 .75rem;
           border-bottom:1px solid var(--bl-line); overflow-x:auto; scrollbar-width:none; }
.bl-tabs::-webkit-scrollbar { display:none; }
.bl-tab {
    position:relative; display:inline-flex; align-items:center; gap:.45rem;
    padding:.85rem .6rem; font-size:.8125rem; font-weight:600;
    color:var(--bl-muted); text-decoration:none; white-space:nowrap;
    transition:color .15s;
}
.bl-tab:hover { color:var(--bl-ink); }
.bl-tab.is-active { color:var(--bl-ink); }
.bl-tab.is-active::after { content:""; position:absolute; left:.4rem; right:.4rem;
                           bottom:-1px; height:2px; border-radius:2px 2px 0 0;
                           background:var(--bl-accent); }
.bl-tab-count { min-width:1.4rem; padding:.05rem .4rem; font-size:.7rem;
                font-weight:600; text-align:center; color:var(--bl-muted);
                background:var(--bl-line-soft); border-radius:999px;
                font-variant-numeric:tabular-nums; }
.bl-tab.is-active .bl-tab-count { color:var(--bl-accent); background:var(--bl-accent-soft); }

/* FILTERS */
.bl-filters { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;
              padding:.85rem 1rem; border-bottom:1px solid var(--bl-line);
              background:linear-gradient(180deg,#fcfdfe,var(--bl-surface)); }
.bl-search { position:relative; flex:1 1 280px; max-width:460px; }
.bl-search > i { position:absolute; left:.75rem; top:50%;
                 transform:translateY(-50%); font-size:.8rem;
                 color:var(--bl-faint); pointer-events:none; }
.bl-input {
    height:36px; font-size:.8125rem; color:var(--bl-ink);
    background:var(--bl-surface); border:1px solid var(--bl-line);
    border-radius:8px; padding:0 .75rem;
    transition:border-color .15s, box-shadow .15s;
}
.bl-input:focus { outline:none; border-color:var(--bl-accent);
                  box-shadow:0 0 0 3px rgba(13,148,136,.15); }
.bl-search .bl-input { width:100%; padding-left:2.1rem; }
.bl-date-range { position:relative; display:inline-flex; align-items:center; gap:.35rem;
                 padding-left:1.8rem; }
.bl-date-icon { position:absolute; left:.55rem; font-size:.8rem; color:var(--bl-faint);
                pointer-events:none; }
.bl-input--date { min-width:150px; }
.bl-to { font-size:.75rem; color:var(--bl-faint); }

/* TABLE */
.bl-table-wrap { overflow-x:auto; }
.bl-table { width:100%; border-collapse:collapse; font-size:.8125rem; }
.bl-table th {
    padding:.7rem 1rem; text-align:left; font-size:.72rem;
    font-weight:600; letter-spacing:.04em; text-transform:uppercase;
    color:var(--bl-muted);
    background:var(--bl-subtle); border-bottom:1px solid var(--bl-line);
    white-space:nowrap; position:sticky; top:0; z-index:2;
}
.bl-table td { padding:.85rem 1rem; vertical-align:middle;
               border-bottom:1px solid var(--bl-line-soft); }
.bl-table tbody tr:last-child td { border-bottom:0; }
.bl-table tbody tr { transition:background .12s; }
.bl-table tbody tr:hover { background:#fafbfd; }

.bl-c-ref { white-space:nowrap; }
.bl-ref { font-family:var(--bl-mono); font-size:.78rem; font-weight:600;
          color:var(--bl-ink); padding:.2rem .55rem; background:var(--bl-line-soft);
          border-radius:6px; text-decoration:none; display:inline-block;
          transition:background .15s, color .15s; }
.bl-ref:hover { background:var(--bl-accent-soft); color:var(--bl-accent-dark); }

.bl-patient .bl-name { display:block; font-weight:600; color:var(--bl-ink); line-height:1.3; }
.bl-patient .bl-sub  { display:block; font-size:.72rem; color:var(--bl-muted); }

.bl-c-date { white-space:nowrap; color:var(--bl-text); }

.bl-num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
.bl-balance { color:var(--bl-warn); font-weight:600; }

.bl-paid-cell { display:flex; flex-direction:column; align-items:flex-end; gap:.25rem; }
.bl-progress { display:block; width:56px; height:3px; border-radius:2px;
               background:var(--bl-line); overflow:hidden; }
.bl-progress > span { display:block; height:100%; background:var(--bl-green);
                      border-radius:2px; transition:width .3s; }

.bl-status { display:inline-flex; align-items:center; gap:.4rem;
             padding:.25rem .6rem; font-size:.72rem; font-weight:600;
             border-radius:999px; white-space:nowrap;
             color:var(--tone-fg,#334155); background:var(--tone-bg,#f1f5f9); }
.bl-status i { font-size:.75em; }
.bl-status--draft    { --tone-bg:#f1f5f9; --tone-fg:#475569; }
.bl-status--unpaid   { --tone-bg:var(--bl-warn-soft); --tone-fg:var(--bl-warn); }
.bl-status--partial  { --tone-bg:var(--bl-blue-soft); --tone-fg:var(--bl-blue); }
.bl-status--paid     { --tone-bg:var(--bl-green-soft); --tone-fg:var(--bl-green); }
.bl-status--void     { --tone-bg:#f1f5f9; --tone-fg:#64748b; }
.bl-status--refunded { --tone-bg:var(--bl-danger-soft); --tone-fg:var(--bl-danger); }

.bl-actions-col { width:1%; }
.bl-c-actions { text-align:right; white-space:nowrap; width:1%; }
.bl-actions { display:inline-flex; gap:.25rem; }
.bl-icon-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:32px; height:32px; font-size:.85rem;
    color:var(--bl-muted); background:transparent; border:1px solid transparent;
    border-radius:8px; text-decoration:none; cursor:pointer;
    transition:background .15s, color .15s, border-color .15s;
}
.bl-icon-btn:hover { background:var(--bl-blue-soft); color:var(--bl-blue); border-color:#dbe6fb; }
.bl-icon-btn--primary:hover { background:var(--bl-accent-soft); color:var(--bl-accent-dark); border-color:#99f6e4; }

.bl-empty { padding:3rem 1rem; text-align:center; }
.bl-empty i { font-size:1.8rem; color:var(--bl-faint); display:block; margin-bottom:.6rem; }
.bl-empty h3 { margin:0 0 .35rem; font-size:.95rem; font-weight:600; color:var(--bl-ink); }
.bl-empty p { margin:0 auto 1rem; max-width:26rem; font-size:.8125rem; color:var(--bl-muted); }

.bl-foot { display:flex; align-items:center; justify-content:space-between;
           gap:.75rem; flex-wrap:wrap; padding:.7rem 1rem; font-size:.78rem;
           color:var(--bl-muted); border-top:1px solid var(--bl-line); }
.bl-pager { display:flex; gap:.25rem; align-items:center; }
.bl-page {
    min-width:32px; height:32px; display:inline-flex; align-items:center;
    justify-content:center; padding:0 .5rem; font-size:.78rem; font-weight:600;
    color:var(--bl-text); background:var(--bl-surface);
    border:1px solid var(--bl-line); border-radius:8px;
    text-decoration:none; font-variant-numeric:tabular-nums;
    transition:background .15s, border-color .15s, color .15s;
}
.bl-page:hover { background:var(--bl-subtle); border-color:#cbd5e1; }
.bl-page.active { background:var(--bl-accent); border-color:var(--bl-accent); color:#fff; }
.bl-page.is-disabled { opacity:.45; pointer-events:none; }
.bl-gap { color:var(--bl-faint); padding:0 .15rem; }

@media (max-width:1100px) { .bl-stats { grid-template-columns:repeat(2,1fr); } }
@media (max-width:640px) {
    .bl-stats { grid-template-columns:1fr; }
    .bl-filters { flex-direction:column; align-items:stretch; }
    .bl-search, .bl-input--date { max-width:none; width:100%; }
    .bl-date-range { width:100%; }
    .bl-date-range .bl-input--date { flex:1; min-width:0; }
}
</style>

<?= $this->endSection() ?>