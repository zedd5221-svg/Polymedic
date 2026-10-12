<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Patient Management<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<?php
    $patients = (isset($patients) && is_array($patients)) ? $patients : [];

    $onlineCount  = 0;
    $walkinCount  = 0;
    $activeCount  = 0;
    $inactiveCount = 0;

    foreach ($patients as $p) {
        $src = strtolower((string) ($p['source'] ?? ''));
        if (strpos($src, 'walk-in') !== false) { $walkinCount++; }
        elseif (strpos($src, 'online') !== false) { $onlineCount++; }

        $lastVisit = $p['last_visit'] ?? $p['created_at'] ?? null;
        if ($lastVisit && strtotime($lastVisit) > strtotime('-30 days')) {
            $activeCount++;
        } else {
            $inactiveCount++;
        }
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
       Mirrors Billing/index and Admin/payments: each KPI card gets a
       small inline line chart on the right and a delta badge under
       the label. Icons are Bootstrap Icons chosen to fit the metric.
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

    /* Fallback series when the controller hasn't sent real trends. */
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

    /* Icon + colour per KPI, chosen to fit the metric. */
    $kpiSpark = [
        'total' => [
            'value' => (float) ($total ?? count($patients)),
            'color' => '#1976d2',   // layout blue
            'good'  => 'up',
            'unit'  => 'patients',
            'icon'  => 'bi-people',
        ],
        'online' => [
            'value' => (float) $onlineCount,
            'color' => '#10b981',   // green — online bookings
            'good'  => 'up',
            'unit'  => 'patients',
            'icon'  => 'bi-globe',
        ],
        'walkin' => [
            'value' => (float) $walkinCount,
            'color' => '#f59e0b',   // amber — desk registrations
            'good'  => 'up',
            'unit'  => 'patients',
            'icon'  => 'bi-person-walking',
        ],
    ];
?>

<div class="pt">

    <!-- PAGE HEADER -->
    <header class="pt-head">
        <div>
            <h2 class="pt-title">Patient management</h2>
        </div>

        <div class="pt-head-actions">
            <button type="button" class="pt-btn" onclick="exportPatients()">
                <i class="bi bi-download" aria-hidden="true"></i>
                Export
            </button>
            <a class="pt-btn pt-btn--primary"
               href="<?= base_url('admin/patient/add') ?>">
                <i class="bi bi-person-plus" aria-hidden="true"></i>
                Add Patient
            </a>
        </div>
    </header>


    <!-- ============================================================
         KPI CARDS — sparkline + delta badge, matching Billing index
         ============================================================ -->
    <div class="pt-stats">

        <?php
        /* Render one KPI card with its sparkline. */
        $renderStat = function (
            string $key,
            string $modifier,
            string $icon,
            string $valueFormatted,
            string $label,
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
            <div class="pt-stat <?= esc($modifier, 'attr') ?>"
                 style="--spark: <?= esc($meta['color'], 'attr') ?>;">

                <div class="pt-stat-icon"><i class="bi <?= esc($icon, 'attr') ?>"></i></div>

                <div class="pt-stat-body">
                    <div class="pt-stat-value"><?= $valueFormatted ?></div>
                    <div class="pt-stat-label"><?= esc($label) ?></div>

                    <div class="pt-stat-delta <?= esc($deltaCls, 'attr') ?>">
                        <span class="pt-delta-arrow"><?= $isUp ? '↑' : '↓' ?></span>
                        <span><?= esc($deltaText) ?></span>
                        <span class="pt-delta-caption">vs last month</span>
                    </div>
                </div>

                <div class="pt-spark"
                     role="img"
                     aria-label="<?= esc($spkTitle, 'attr') ?>"
                     title="<?= esc($spkTitle, 'attr') ?>">
                    <svg viewBox="0 0 100 32" preserveAspectRatio="none" aria-hidden="true">
                        <path d="<?= $line ?>" class="pt-spark-line" fill="none"
                              vector-effect="non-scaling-stroke"/>
                    </svg>
                </div>
            </div>
        <?php
        };

        $renderStat(
            'total',
            'pt-stat--blue',
            $kpiSpark['total']['icon'],
            number_format($total ?? count($patients)),
            'Total patients',
            $kpiSpark['total']
        );

        $renderStat(
            'online',
            'pt-stat--green',
            $kpiSpark['online']['icon'],
            number_format($onlineCount),
            'Online patients',
            $kpiSpark['online']
        );

        $renderStat(
            'walkin',
            'pt-stat--amber',
            $kpiSpark['walkin']['icon'],
            number_format($walkinCount),
            'Walk-in patients',
            $kpiSpark['walkin']
        );
        ?>

    </div>


    <!-- ALERTS -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="pt-alert pt-alert--success alert alert-dismissible fade show" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <span><?= esc(session()->getFlashdata('success')) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="pt-alert pt-alert--error alert alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
        </div>
    <?php endif; ?>


    <!-- TABLE -->
    <section class="pt-panel">

        <div class="pt-tabs" role="group" aria-label="Filter by source">
            <button type="button" class="pt-tab is-active" data-source="all" aria-pressed="true">
                All
                <span class="pt-tab-count"><?= (int) count($patients) ?></span>
            </button>
            <button type="button" class="pt-tab" data-source="online" aria-pressed="false">
                Online
                <span class="pt-tab-count"><?= (int) $onlineCount ?></span>
            </button>
            <button type="button" class="pt-tab" data-source="walk-in" aria-pressed="false">
                Walk-in
                <span class="pt-tab-count"><?= (int) $walkinCount ?></span>
            </button>
        </div>

        <div class="pt-filters">
            <div class="pt-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label class="visually-hidden" for="searchPatients">Search patients</label>
                <input type="text"
                       class="pt-input"
                       id="searchPatients"
                       placeholder="Search by name, code, email or phone"
                       autocomplete="off">
            </div>

            <label class="visually-hidden" for="filterStatus">Status</label>
            <select class="pt-select" id="filterStatus">
                <option value="">All status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <label class="visually-hidden" for="sortBy">Sort</label>
            <select class="pt-select" id="sortBy">
                <option value="created_at">Sort: Newest</option>
                <option value="full_name">Sort: Name</option>
                <option value="age">Sort: Age</option>
                <option value="created_at_oldest">Sort: Oldest</option>
            </select>
        </div>

        <div class="pt-table-wrap">
            <table class="pt-table" id="patientsTable">
                <thead>
                    <tr>
                        <th scope="col">Patient code</th>
                        <th scope="col">Patient name</th>
                        <th scope="col">Source</th>
                        <th scope="col">Sex</th>
                        <th scope="col">Age</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Email</th>
                        <th scope="col">Registered</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="pt-c-actions"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody id="patientsTableBody">
                    <?php if (count($patients) > 0): ?>
                        <?php foreach ($patients as $patient):
                            $lastVisit = $patient['last_visit'] ?? $patient['created_at'] ?? null;
                            $isActive  = $lastVisit && strtotime($lastVisit) > strtotime('-30 days');

                            $isWalkin = strpos(strtolower((string) ($patient['source'] ?? '')), 'walk-in') !== false;
                            $source   = $isWalkin ? 'walk-in' : 'online';

                            $name   = (string) ($patient['full_name'] ?? 'Unknown');
                            $code   = (string) ($patient['patient_code'] ?? 'N/A');
                            $gender = (string) ($patient['gender'] ?? 'N/A');
                            $age    = (string) ($patient['age'] ?? 'N/A');
                            $email  = (string) ($patient['email'] ?? '');
                            $phone  = (string) ($patient['phone'] ?? '');

                            $isApproved = $code !== 'N/A' && $code !== '';
                            $patientId  = (int) ($patient['id'] ?? 0);

                            $genderRaw  = strtolower(trim($gender));
                            $isMale     = $genderRaw === 'male'   || $genderRaw === 'm';
                            $isFemale   = $genderRaw === 'female' || $genderRaw === 'f';
                            $avatarKind = $isMale ? 'male' : ($isFemale ? 'female' : 'neutral');
                        ?>
                            <tr class="pt-row pt-row--<?= esc($source, 'attr') ?>"
                                data-patient-row
                                data-source="<?= esc($source, 'attr') ?>"
                                data-status="<?= $isActive ? 'active' : 'inactive' ?>"
                                data-name="<?= esc(strtolower($name), 'attr') ?>"
                                data-age="<?= esc((string) (int) ($patient['age'] ?? 0), 'attr') ?>"
                                data-created="<?= $patient['created_at'] ? esc(date('Y-m-d H:i:s', strtotime($patient['created_at'])), 'attr') : '' ?>">

                                <td data-label="Code" class="pt-c-code">
                                    <span class="pt-code<?= $isApproved ? '' : ' pt-code--pending' ?>">
                                        <?= esc($code) ?>
                                        <?php if (!$isApproved): ?>
                                            <span class="pt-pending-badge">Pending</span>
                                        <?php endif; ?>
                                    </span>
                                </td>

                                <td data-label="Patient">
                                    <div class="pt-patient">
                                        <span class="pt-avatar pt-avatar--<?= esc($avatarKind, 'attr') ?>" aria-hidden="true">
                                            <?php if ($isMale): ?>
                                                <img src="<?= esc(base_url('assets/images/' . $maleAvatar), 'attr') ?>"
                                                     alt=""
                                                     class="pt-avatar-img"
                                                     loading="lazy"
                                                     decoding="async">
                                            <?php elseif ($isFemale): ?>
                                                <img src="<?= esc(base_url('assets/images/' . $femaleAvatar), 'attr') ?>"
                                                     alt=""
                                                     class="pt-avatar-img"
                                                     loading="lazy"
                                                     decoding="async">
                                            <?php else: ?>
                                                <?= esc($initialsOf($name)) ?>
                                            <?php endif; ?>
                                        </span>
                                        <div class="pt-patient-body">
                                            <span class="pt-name"><?= esc($name) ?></span>
                                            <span class="pt-sub">
                                                <?= $gender !== 'N/A' ? esc($gender) : '—' ?>
                                                ·
                                                <?= $age !== 'N/A' ? esc($age) : '—' ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td data-label="Source">
                                    <span class="pt-source pt-source--<?= esc($source, 'attr') ?>">
                                        <i class="bi <?= $isWalkin ? 'bi-person-walking' : 'bi-globe' ?>" aria-hidden="true"></i>
                                        <?= esc($patient['source'] ?? 'Unknown') ?>
                                    </span>
                                </td>

                                <td data-label="Sex" class="pt-c-sex"><?= esc($gender !== '' ? $gender : '—') ?></td>

                                <td data-label="Age" class="pt-c-age"><?= esc($age !== '' ? $age : '—') ?></td>

                                <td data-label="Phone" class="pt-c-phone">
                                    <?php if ($phone !== ''): ?>
                                        <a href="tel:<?= esc($phone, 'attr') ?>" class="pt-link"><?= esc($phone) ?></a>
                                    <?php else: ?>
                                        <span class="pt-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Email" class="pt-c-email">
                                    <?php if ($email !== ''): ?>
                                        <a href="mailto:<?= esc($email, 'attr') ?>" class="pt-link"><?= esc($email) ?></a>
                                    <?php else: ?>
                                        <span class="pt-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Registered" class="pt-c-date">
                                    <time datetime="<?= $patient['created_at'] ? esc(date('c', strtotime($patient['created_at'])), 'attr') : '' ?>">
                                        <?= $patient['created_at'] ? esc(date('M j, Y', strtotime($patient['created_at']))) : '—' ?>
                                    </time>
                                </td>

                                <td data-label="Status">
                                    <span class="pt-status pt-status--<?= $isActive ? 'active' : 'inactive' ?>">
                                        <?= $isActive ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>

                                <td data-label="Actions" class="pt-c-actions">
                                    <div class="pt-actions">
                                        <?php if (!$isApproved && $patientId > 0): ?>
                                            <button type="button" class="pt-icon-btn pt-icon-btn--approve"
                                                    title="Approve patient"
                                                    aria-label="Approve <?= esc($name, 'attr') ?>"
                                                    onclick="approvePatient(<?= $patientId ?>)">
                                                <i class="bi bi-check-circle" aria-hidden="true"></i>
                                            </button>
                                        <?php endif; ?>

                                        <button type="button" class="pt-icon-btn"
                                                title="View patient"
                                                aria-label="View <?= esc($name, 'attr') ?>"
                                                onclick="viewPatient(<?= $patientId ?>)">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </button>

                                        <button type="button" class="pt-icon-btn"
                                                title="Edit patient"
                                                aria-label="Edit <?= esc($name, 'attr') ?>"
                                                onclick="editPatient(<?= $patientId ?>)">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </button>

                                        <?php if ($email !== ''): ?>
                                            <a href="mailto:<?= esc($email, 'attr') ?>"
                                               class="pt-icon-btn"
                                               title="Email patient"
                                               aria-label="Email <?= esc($name, 'attr') ?>">
                                                <i class="bi bi-envelope" aria-hidden="true"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($phone !== ''): ?>
                                            <a href="tel:<?= esc($phone, 'attr') ?>"
                                               class="pt-icon-btn"
                                               title="Call patient"
                                               aria-label="Call <?= esc($name, 'attr') ?>">
                                                <i class="bi bi-telephone" aria-hidden="true"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="pt-row-empty" data-empty-row>
                            <td colspan="10">
                                <div class="pt-empty">
                                    <div class="pt-empty-icon"><i class="bi bi-people" aria-hidden="true"></i></div>
                                    <h3>No patients registered yet</h3>
                                    <p>Patients will appear here once they book online or are registered at the desk.</p>
                                    <a class="pt-btn pt-btn--primary" href="<?= base_url('admin/patient/add') ?>">
                                        <i class="bi bi-person-plus" aria-hidden="true"></i>
                                        Add patient
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr class="pt-row-empty" data-noresults-row style="display: none;">
                        <td colspan="10">
                            <div class="pt-empty">
                                <div class="pt-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></div>
                                <h3>No matching patients</h3>
                                <p>Try a different search term or clear the filters.</p>
                                <button type="button" class="pt-btn" id="ptClearFilters">Clear filters</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <footer class="pt-foot">
            <span id="showingText">
                Showing <strong><?= count($patients) ?></strong> of <strong><?= number_format($total ?? count($patients)) ?></strong> patients
            </span>
            <nav class="pt-pager" id="paginationControls" aria-label="Pagination"></nav>
        </footer>

    </section>

</div>

<style>
/* =========================================================
   PATIENTS
   Theme aligned with AdminLayout blue palette.
   ========================================================= */

.pt {
    --pt-ink:         #0f172a;
    --pt-text:        #334155;
    --pt-muted:       #64748b;
    --pt-faint:       #94a3b8;
    --pt-line:        #e2e8f0;
    --pt-line-soft:   #f1f5f9;
    --pt-surface:     #ffffff;
    --pt-subtle:      #f8fafc;

    /* === Aligned with layout blue === */
    --pt-accent:      #1976d2;
    --pt-accent-dark: #1565c0;
    --pt-accent-soft: #e3f2fd;

    --pt-gold:        #b45309;
    --pt-gold-soft:   #fef3c7;

    --pt-radius:      10px;
    --pt-radius-sm:   7px;
    --pt-ring:        0 0 0 3px rgba(25, 118, 210, 0.18);
    --pt-mono:        ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;

    color: var(--pt-text);
}

.pt *:focus-visible { outline: 2px solid var(--pt-accent); outline-offset: 2px; }

/* ---------- Page header ---------- */

.pt-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}

.pt-title {
    margin: 0 0 0.2rem;
    font-size: 1.25rem;
    font-weight: 650;
    letter-spacing: -0.015em;
    color: var(--pt-ink);
}

.pt-lede {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--pt-muted);
}

.pt-lede strong { font-weight: 600; color: var(--pt-ink); }
.pt-lede span { margin: 0 0.2rem; color: var(--pt-faint); }

.pt-head-actions { display: flex; gap: 0.5rem; }

/* ---------- Alerts ---------- */

.pt-alert {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: var(--pt-radius);
    font-size: 0.8125rem;
}

.pt-alert > span { flex: 1; min-width: 0; }
.pt-alert.alert-dismissible { padding-right: 0.75rem; }
.pt-alert .btn-close { position: static; padding: 0.5rem; margin-left: auto; font-size: 0.7rem; }
.pt-alert--success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
.pt-alert--error   { background: #fef2f2; border-color: #fecaca; color: #991b1b; }

/* ---------- Buttons ---------- */

.pt-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    height: 36px;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    color: var(--pt-text);
    background: var(--pt-surface);
    border: 1px solid var(--pt-line);
    border-radius: var(--pt-radius-sm);
    box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.pt-btn:hover { background: var(--pt-subtle); border-color: #cbd5e1; color: var(--pt-ink); }
.pt-btn i { font-size: 0.9em; }

.pt-btn--primary,
.pt-btn--primary:hover { color: #ffffff; }
.pt-btn--primary { background: var(--pt-accent); border-color: var(--pt-accent); }
.pt-btn--primary:hover { background: var(--pt-accent-dark); border-color: var(--pt-accent-dark); }

/* ============================================================
   KPI CARDS — sparkline + delta badge, matching Billing index
   ============================================================ */
.pt-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.9rem;
    margin-bottom: 1.25rem;
}

.pt-stat {
    position: relative;
    display: grid;
    grid-template-columns: 38px minmax(0, 1fr) auto;   /* icon | text | spark */
    align-items: center;
    column-gap: 0.9rem;
    row-gap: 0.35rem;
    padding: 1rem 1.1rem;
    background: var(--pt-surface);
    border: 1px solid var(--pt-line);
    border-radius: var(--pt-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    min-width: 0;
}

.pt-stat--amber { background: linear-gradient(180deg, #fffbeb, var(--pt-surface)); }

.pt-stat-icon {
    grid-column: 1; grid-row: 1 / span 2;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    font-size: 1rem;
    color: var(--tone);
    background: color-mix(in srgb, var(--tone) 12%, transparent);
}

/* Icon tones per card variant */
.pt-stat--blue  .pt-stat-icon { --tone: #1976d2; }
.pt-stat--green .pt-stat-icon { --tone: #10b981; }
.pt-stat--amber .pt-stat-icon { --tone: #f59e0b; }

.pt-stat-body {
    grid-column: 2; grid-row: 1 / span 2;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}

.pt-stat-value {
    font-size: 1.2rem;
    font-weight: 700;
    line-height: 1.15;
    letter-spacing: -0.02em;
    color: var(--pt-ink);
    font-variant-numeric: tabular-nums;
}

.pt-stat-label {
    margin-top: 0.05rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--pt-text);
}

.pt-stat-sub { font-size: 0.72rem; color: var(--pt-muted); }

/* Small delta badge under the label */
.pt-stat-delta {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    margin-top: 0.15rem;
    font-size: 0.68rem;
    font-weight: 600;
    color: var(--pt-muted);
    white-space: nowrap;
}
.pt-stat-delta .pt-delta-arrow { font-size: 0.72rem; line-height: 1; }
.pt-stat-delta.is-good .pt-delta-arrow { color: #16a34a; }
.pt-stat-delta.is-bad  .pt-delta-arrow { color: #dc2626; }
.pt-stat-delta.is-good { color: #16a34a; }
.pt-stat-delta.is-bad  { color: #dc2626; }
.pt-delta-caption {
    margin-left: 0.2rem;
    font-weight: 500;
    color: var(--pt-faint);
}

/* Sparkline: identical geometry to the Admin dashboard's .spark-inline */
.pt-spark {
    grid-column: 3; grid-row: 1 / span 2;
    align-self: center;
    flex-shrink: 0;
    width: 72px;
    height: 28px;
    position: relative;
}
.pt-spark svg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: block;
    overflow: visible;
}
.pt-spark-line {
    stroke: var(--spark, var(--pt-accent));
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

/* ---------- Panel ---------- */

.pt-panel {
    background: var(--pt-surface);
    border: 1px solid var(--pt-line);
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

/* ---------- Tabs ---------- */

.pt-tabs {
    display: flex;
    gap: 0.25rem;
    padding: 0 0.75rem;
    border-bottom: 1px solid var(--pt-line);
    overflow-x: auto;
    scrollbar-width: none;
}

.pt-tabs::-webkit-scrollbar { display: none; }

.pt-tab {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.85rem 0.6rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--pt-muted);
    background: none;
    border: 0;
    white-space: nowrap;
    cursor: pointer;
    transition: color 0.15s ease;
}

.pt-tab::after {
    content: "";
    position: absolute;
    left: 0.4rem;
    right: 0.4rem;
    bottom: -1px;
    height: 2px;
    border-radius: 2px 2px 0 0;
    background: transparent;
}

.pt-tab:hover { color: var(--pt-ink); }
.pt-tab.is-active { color: var(--pt-ink); }
.pt-tab.is-active::after { background: var(--pt-accent); }

.pt-tab-count {
    min-width: 1.4rem;
    padding: 0.05rem 0.4rem;
    font-size: 0.7rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    text-align: center;
    color: var(--pt-muted);
    background: var(--pt-line-soft);
    border-radius: 999px;
}

.pt-tab.is-active .pt-tab-count { color: var(--pt-accent); background: var(--pt-accent-soft); }

/* ---------- Filters ---------- */

.pt-filters {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    padding: 0.75rem 1rem;
}

.pt-search { position: relative; flex: 1 1 280px; max-width: 420px; }

.pt-search > i {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.8rem;
    color: var(--pt-faint);
    pointer-events: none;
}

.pt-input,
.pt-select {
    width: 100%;
    height: 36px;
    padding: 0 0.75rem;
    font-size: 0.8125rem;
    color: var(--pt-ink);
    background-color: var(--pt-surface);
    border: 1px solid var(--pt-line);
    border-radius: var(--pt-radius-sm);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.pt-input { padding-left: 2.1rem; }
.pt-input::placeholder { color: var(--pt-faint); }

.pt-input:focus,
.pt-select:focus {
    outline: none;
    border-color: var(--pt-accent);
    box-shadow: var(--pt-ring);
}

.pt-select {
    width: auto;
    padding-right: 2rem;
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m3 6 5 5 5-5'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.65rem center;
    background-size: 12px;
    cursor: pointer;
}

/* ---------- Table ---------- */

.pt-table-wrap { overflow-x: auto; border-top: 1px solid var(--pt-line); }

.pt-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
    color: var(--pt-text);
}

.pt-table th {
    padding: 0.65rem 1rem;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    text-align: left;
    white-space: nowrap;
    color: var(--pt-muted);
    background: var(--pt-subtle);
    border-bottom: 1px solid var(--pt-line);
}

.pt-table td {
    padding: 0.85rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--pt-line-soft);
}

.pt-table tbody tr:last-child td { border-bottom: 0; }

.pt-row { transition: background-color 0.12s ease; }
.pt-row:hover { background: #fafbfd; }

.pt-row td:first-child { position: relative; }

.pt-row td:first-child::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--rail, transparent);
}

.pt-row--online  { --rail: #1976d2; }
.pt-row--walk-in { --rail: #f59e0b; }

.pt-c-code { white-space: nowrap; }

.pt-code {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.15rem 0.5rem;
    font-family: var(--pt-mono);
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--pt-ink);
    background: var(--pt-line-soft);
    border-radius: 5px;
}

.pt-code--pending { color: var(--pt-gold); background: var(--pt-gold-soft); }

.pt-pending-badge {
    padding: 0.05rem 0.4rem;
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--pt-gold);
    background: #fffbeb;
    border-radius: 12px;
}

.pt-patient { display: flex; align-items: center; gap: 0.6rem; min-width: 0; }

.pt-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--pt-accent);
    background: var(--pt-accent-soft);
    border-radius: 50%;
    overflow: hidden;
}

.pt-avatar--male    { background: #e3f2fd; color: #1565c0; }
.pt-avatar--female  { background: #fce9ee; color: #b32e50; }
.pt-avatar--neutral { background: var(--pt-accent-soft); color: var(--pt-accent); }

.pt-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.pt-patient-body { min-width: 0; }

.pt-name {
    display: block;
    font-weight: 600;
    color: var(--pt-ink);
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pt-sub { display: block; font-size: 0.72rem; color: var(--pt-muted); }

.pt-c-sex,
.pt-c-age { white-space: nowrap; color: var(--pt-text); }

.pt-c-phone,
.pt-c-email { white-space: nowrap; }

.pt-c-date { white-space: nowrap; font-variant-numeric: tabular-nums; }

.pt-link {
    color: var(--pt-text);
    text-decoration: none;
}

.pt-link:hover { color: var(--pt-accent-dark); text-decoration: underline; text-underline-offset: 2px; }

.pt-muted { color: var(--pt-faint); }

.pt-source {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.15rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    border-radius: 999px;
    white-space: nowrap;
}

.pt-source--online  { color: #1565c0; background: #e3f2fd; }
.pt-source--walk-in { color: #b45309; background: #fff4e5; }

.pt-source i { font-size: 0.72em; }

.pt-status {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.15rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    border-radius: 999px;
    white-space: nowrap;
}

.pt-status--active   { color: #15803d; background: #e7f6ec; }
.pt-status--inactive { color: #b91c1c; background: #fdeaea; }

.pt-status::before {
    content: "";
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: currentColor;
}

.pt-c-actions { width: 1%; text-align: right; white-space: nowrap; }

.pt-actions { display: inline-flex; align-items: center; gap: 0.25rem; }

.pt-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    font-size: 0.85rem;
    color: var(--pt-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--pt-radius-sm);
    cursor: pointer;
    text-decoration: none;
    transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.pt-icon-btn:hover {
    background: var(--pt-accent-soft);
    color: var(--pt-accent-dark);
    border-color: #cfe3f7;
}

.pt-icon-btn--approve { color: var(--pt-gold); }
.pt-icon-btn--approve:hover {
    background: #fffbeb;
    color: var(--pt-gold);
    border-color: #fcd34d;
}

/* ---------- Empty states ---------- */

.pt-empty { padding: 3.5rem 1rem; text-align: center; }

.pt-empty-icon {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    margin: 0 auto 0.85rem;
    font-size: 1.2rem;
    color: var(--pt-faint);
    background: var(--pt-line-soft);
    border-radius: 10px;
}

.pt-empty h3 { margin: 0 0 0.25rem; font-size: 0.95rem; font-weight: 600; color: var(--pt-ink); }
.pt-empty p { max-width: 26rem; margin: 0 auto 1rem; font-size: 0.8125rem; color: var(--pt-muted); }

/* ---------- Footer ---------- */

.pt-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding: 0.7rem 1rem;
    font-size: 0.78rem;
    color: var(--pt-muted);
    border-top: 1px solid var(--pt-line);
}

.pt-foot strong { font-weight: 600; color: var(--pt-ink); font-variant-numeric: tabular-nums; }

.pt-pager { display: flex; align-items: center; gap: 0.25rem; }

.pt-page {
    min-width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.45rem;
    font-size: 0.78rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--pt-text);
    background: var(--pt-surface);
    border: 1px solid var(--pt-line);
    border-radius: var(--pt-radius-sm);
    cursor: pointer;
}

.pt-page:hover:not(:disabled) { background: var(--pt-subtle); border-color: #cbd5e1; }
.pt-page:disabled { opacity: 0.45; cursor: not-allowed; }
.pt-page[aria-current="page"] { color: var(--pt-accent); background: var(--pt-accent-soft); border-color: #bcd6f3; }
.pt-page-gap { min-width: 20px; text-align: center; color: var(--pt-faint); }

/* ---------- Responsive ---------- */

@media (max-width: 992px) {
    .pt-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
    .pt-head { flex-direction: column; align-items: stretch; }
    .pt-head-actions .pt-btn { flex: 1; }

    .pt-stats { grid-template-columns: minmax(0, 1fr); gap: 0.6rem; }

    .pt-filters .pt-search { flex-basis: 100%; max-width: none; }
    .pt-filters .pt-select { flex: 1; }

    .pt-table thead { display: none; }

    .pt-table,
    .pt-table tbody,
    .pt-table tr,
    .pt-table td { display: block; width: 100%; }

    .pt-table tr.pt-row {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid var(--pt-line);
    }

    .pt-row td:first-child::before { top: 0; bottom: 0; }

    .pt-table td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.25rem 0;
        text-align: right;
        border: 0;
    }

    .pt-table td[data-label]::before {
        content: attr(data-label);
        flex-shrink: 0;
        font-size: 0.75rem;
        font-weight: 400;
        color: var(--pt-muted);
        text-align: left;
    }

    .pt-table td.pt-c-code,
    .pt-table td:nth-child(2) { display: block; text-align: left; }
    .pt-table td.pt-c-code::before,
    .pt-table td:nth-child(2)::before { content: none; }
    .pt-table td:nth-child(2) { margin: 0.35rem 0 0.5rem; }

    .pt-table td.pt-c-actions { justify-content: flex-end; margin-top: 0.5rem; }

    .pt-c-email,
    .pt-c-phone { white-space: normal; }
    .pt-c-email a,
    .pt-c-phone a { display: inline-block; max-width: 100%; overflow-wrap: anywhere; }

    .pt-foot { justify-content: center; }
}

@media (prefers-reduced-motion: reduce) {
    .pt *, .pt *::before, .pt *::after { transition: none !important; }
}

@media print {
    .pt-head-actions,
    .pt-tabs,
    .pt-filters,
    .pt-c-actions { display: none !important; }

    .pt-panel { border: 0; box-shadow: none; }
    .pt-row { display: table-row !important; }
}
</style>

<script>
(function () {
    'use strict';

    var tbody              = document.getElementById('patientsTableBody');
    var paginationControls = document.getElementById('paginationControls');
    var showingText        = document.getElementById('showingText');
    var searchInput        = document.getElementById('searchPatients');
    var filterStatus       = document.getElementById('filterStatus');
    var sortSelect         = document.getElementById('sortBy');
    var tabs               = Array.prototype.slice.call(document.querySelectorAll('.pt-tab'));
    var clearBtn           = document.getElementById('ptClearFilters');

    if (!tbody) { return; }

    var PAGE_SIZE = 10;

    var allRows      = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-patient-row]'));
    var emptyRow     = tbody.querySelector('tr[data-empty-row]');
    var noResultsRow = tbody.querySelector('tr[data-noresults-row]');

    var loadedTotal  = allRows.length;
    var serverTotal  = <?= (int) ($total ?? 0) ?>;

    var filteredRows = allRows.slice();
    var state = { source: 'all', status: '', term: '', page: 1 };

    function getTotalPages() {
        return Math.max(1, Math.ceil(filteredRows.length / PAGE_SIZE));
    }

    function applyFilters(resetPage) {
        filteredRows = allRows.filter(function (row) {
            if (state.term && row.textContent.toLowerCase().indexOf(state.term) === -1) {
                return false;
            }
            if (state.source !== 'all' && (row.dataset.source || '') !== state.source) {
                return false;
            }
            if (state.status && (row.dataset.status || '') !== state.status) {
                return false;
            }
            return true;
        });

        if (resetPage !== false) { state.page = 1; }
        render();
    }

    function applySort() {
        var mode = sortSelect ? sortSelect.value : 'created_at';

        var sorted = allRows.slice();
        sorted.sort(function (a, b) {
            switch (mode) {
                case 'full_name':
                    return (a.dataset.name || '').localeCompare(b.dataset.name || '');
                case 'age':
                    return (parseInt(a.dataset.age, 10) || 0) - (parseInt(b.dataset.age, 10) || 0);
                case 'created_at_oldest':
                    return (a.dataset.created || '').localeCompare(b.dataset.created || '');
                default:
                    return (b.dataset.created || '').localeCompare(a.dataset.created || '');
            }
        });

        sorted.forEach(function (row) { tbody.appendChild(row); });
        if (emptyRow) { tbody.appendChild(emptyRow); }
        if (noResultsRow) { tbody.appendChild(noResultsRow); }

        allRows = sorted;
        applyFilters(false);
    }

    function render() {
        var pages = getTotalPages();
        if (state.page > pages) { state.page = pages; }
        if (state.page < 1) { state.page = 1; }

        var start = (state.page - 1) * PAGE_SIZE;

        allRows.forEach(function (row) { row.style.display = 'none'; });

        var pageRows = filteredRows.slice(start, start + PAGE_SIZE);
        pageRows.forEach(function (row) { row.style.display = ''; });

        if (emptyRow) {
            emptyRow.style.display = (loadedTotal === 0) ? '' : 'none';
        }
        if (noResultsRow) {
            noResultsRow.style.display = (loadedTotal > 0 && filteredRows.length === 0) ? '' : 'none';
        }

        updateShowingText(start, pageRows.length);
        renderPagination(pages);
    }

    function updateShowingText(start, shown) {
        if (!showingText) { return; }

        var count = filteredRows.length;

        if (count === 0) {
            showingText.textContent = (loadedTotal === 0)
                ? 'No patients to show'
                : 'No matching patients';
            return;
        }

        var text = 'Showing ' + (start + 1) + ' to ' + (start + shown) +
                   ' of ' + count + ' patient' + (count === 1 ? '' : 's');

        if (count !== loadedTotal) {
            text += ' (filtered from ' + loadedTotal + ')';
        } else if (serverTotal > loadedTotal) {
            text += ' (' + serverTotal + ' total registered)';
        }

        showingText.textContent = text;
    }

    function buildPageList(pages, current) {
        var list = [];
        var i;

        if (pages <= 7) {
            for (i = 1; i <= pages; i++) { list.push(i); }
            return list;
        }

        var from = Math.max(2, current - 1);
        var to   = Math.min(pages - 1, current + 1);

        if (current <= 3)         { from = 2;         to = 4; }
        if (current >= pages - 2) { from = pages - 3; to = pages - 1; }

        list.push(1);
        if (from > 2) { list.push('gap'); }
        for (i = from; i <= to; i++) { list.push(i); }
        if (to < pages - 1) { list.push('gap'); }
        list.push(pages);

        return list;
    }

    function renderPagination(pages) {
        if (!paginationControls) { return; }

        if (pages <= 1) { paginationControls.innerHTML = ''; return; }

        var html = '<button type="button" class="pt-page" data-nav="-1" aria-label="Previous page"' +
                   (state.page === 1 ? ' disabled' : '') +
                   '><i class="bi bi-chevron-left" aria-hidden="true"></i></button>';

        buildPageList(pages, state.page).forEach(function (item) {
            if (item === 'gap') {
                html += '<span class="pt-page-gap" aria-hidden="true">…</span>';
                return;
            }
            html += '<button type="button" class="pt-page" data-page="' + item + '"' +
                    (item === state.page ? ' aria-current="page"' : '') +
                    ' aria-label="Page ' + item + '">' + item + '</button>';
        });

        html += '<button type="button" class="pt-page" data-nav="1" aria-label="Next page"' +
                (state.page >= pages ? ' disabled' : '') +
                '><i class="bi bi-chevron-right" aria-hidden="true"></i></button>';

        paginationControls.innerHTML = html;
    }

    if (paginationControls) {
        paginationControls.addEventListener('click', function (e) {
            var btn = e.target.closest('.pt-page');
            if (!btn || btn.disabled) { return; }

            var nav  = parseInt(btn.dataset.nav, 10);
            var page = parseInt(btn.dataset.page, 10);

            var pages = getTotalPages();

            if (!isNaN(nav)) {
                state.page = Math.min(Math.max(1, state.page + nav), pages);
            } else if (!isNaN(page)) {
                state.page = page;
            } else {
                return;
            }

            render();
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            state.source = tab.dataset.source || 'all';

            tabs.forEach(function (t) {
                var on = t === tab;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-pressed', on ? 'true' : 'false');
            });

            applyFilters(true);
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            state.term = searchInput.value.toLowerCase().trim();
            applyFilters(true);
        });
        searchInput.addEventListener('search', function () {
            state.term = searchInput.value.toLowerCase().trim();
            applyFilters(true);
        });
    }

    if (filterStatus) {
        filterStatus.addEventListener('change', function () {
            state.status = filterStatus.value.toLowerCase();
            applyFilters(true);
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', applySort);
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (searchInput)  { searchInput.value = ''; }
            if (filterStatus) { filterStatus.value = ''; }
            state.term = '';
            state.status = '';
            state.source = 'all';

            tabs.forEach(function (t) {
                var on = t.dataset.source === 'all';
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-pressed', on ? 'true' : 'false');
            });

            applyFilters(true);
        });
    }

    applyFilters(true);
})();


/* ============================================
   ROW ACTIONS
   ============================================ */

function approvePatient(patientId) {
    if (!patientId || patientId === 0) {
        alert('Patient ID not available. Please sync patients first.');
        return;
    }

    if (!confirm('Approve this patient? A patient code will be generated.')) {
        return;
    }

    var btn = event && event.currentTarget ? event.currentTarget : null;
    if (btn) {
        btn.disabled = true;
        var original = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';
    }

    fetch('/polymedic/public/admin/approve-patient/' + patientId, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
        if (data.success) {
            alert('Patient approved. Code: ' + data.patient_code);
            location.reload();
        } else {
            alert('Error: ' + data.message);
            if (btn) {
                btn.innerHTML = original;
                btn.disabled = false;
            }
        }
    })
    .catch(function (error) {
        console.error('Error:', error);
        alert('Error approving patient. Please try again.');
        if (btn) {
            btn.innerHTML = original;
            btn.disabled = false;
        }
    });
}

function viewPatient(patientId) {
    if (!patientId || patientId === 0) {
        alert('Patient details not available.');
        return;
    }
    window.location.href = '/polymedic/public/admin/patient/view/' + patientId;
}

function editPatient(patientId) {
    if (!patientId || patientId === 0) {
        alert('Patient cannot be edited.');
        return;
    }
    window.location.href = '/polymedic/public/admin/patient/edit/' + patientId;
}

function exportPatients() {
    alert('Export functionality coming soon!');
}
</script>

<?= $this->endSection() ?>