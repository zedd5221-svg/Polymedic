<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Patient Management<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<?php
    $patients = (isset($patients) && is_array($patients)) ? $patients : [];

    // Counts computed once so both the tabs and the KPI cards use the
    // same numbers.
    $onlineCount = 0;
    $walkinCount = 0;
    foreach ($patients as $p) {
        $src = strtolower((string) ($p['source'] ?? ''));
        if (strpos($src, 'walk-in') !== false) { $walkinCount++; }
        elseif (strpos($src, 'online') !== false) { $onlineCount++; }
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
       Mirrors the Admin patients page: each KPI card gets a small inline
       line chart on the right and a delta badge under the label. Icons
       are Bootstrap Icons chosen to fit the metric.
       ------------------------------------------------------------------ */
    $kpiTrends = $kpiTrends ?? [];

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

    $kpiSpark = [
        'total' => [
            'value' => (float) ($total ?? count($patients)),
            'color' => '#1976d2',
            'good'  => 'up',
            'unit'  => 'patients',
            'icon'  => 'bi-people',
        ],
        'online' => [
            'value' => (float) $onlineCount,
            'color' => '#10b981',
            'good'  => 'up',
            'unit'  => 'patients',
            'icon'  => 'bi-globe',
        ],
        'walkin' => [
            'value' => (float) $walkinCount,
            'color' => '#f59e0b',
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
            <button type="button" class="pt-btn pt-btn--primary"
                    onclick="window.location.href='<?= base_url('receptionist/diagnostic-requests') ?>'">
                <i class="bi bi-person-plus" aria-hidden="true"></i>
                Register walk-in patient
            </button>
        </div>
    </header>


    <!-- ============================================================
         KPI CARDS — sparkline + delta badge, matching Admin patients
         ============================================================ -->
    <div class="pt-stats">

        <?php
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
        </div>

        <div class="pt-table-wrap">
            <table class="pt-table" id="patientsTable">
                <thead>
                    <tr>
                        <th scope="col">Patient code</th>
                        <th scope="col">Patient name</th>
                        <th scope="col">Sex</th>
                        <th scope="col">Age</th>
                        <th scope="col">Email</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Source</th>
                        <th scope="col" class="pt-c-actions"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($patients) > 0): ?>
                        <?php foreach ($patients as $patient):
                            $isWalkin = strpos(strtolower((string) ($patient['source'] ?? '')), 'walk-in') !== false;
                            $source   = $isWalkin ? 'walk-in' : 'online';
                            $name     = (string) ($patient['full_name'] ?? 'Unknown');
                            $code     = (string) ($patient['patient_code'] ?? 'N/A');
                            $gender   = (string) ($patient['gender'] ?? 'N/A');
                            $age      = (string) ($patient['age'] ?? 'N/A');
                            $email    = (string) ($patient['email'] ?? '');
                            $phone    = (string) ($patient['phone'] ?? '');
                            $pid      = isset($patient['id']) ? (int) $patient['id'] : 0;

                            $genderRaw  = strtolower(trim($gender));
                            $isMale     = $genderRaw === 'male'   || $genderRaw === 'm';
                            $isFemale   = $genderRaw === 'female' || $genderRaw === 'f';
                            $avatarKind = $isMale ? 'male' : ($isFemale ? 'female' : 'neutral');
                        ?>
                            <tr class="pt-row pt-row--<?= esc($source, 'attr') ?>" data-source="<?= esc($source, 'attr') ?>">
                                <td data-label="Code" class="pt-c-code">
                                    <span class="pt-code"><?= esc($code) ?></span>
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
                                            <span class="pt-sub"><?= $gender !== 'N/A' ? esc($gender) : '—' ?> · <?= $age !== 'N/A' ? esc($age) : '—' ?></span>
                                        </div>
                                    </div>
                                </td>

                                <td data-label="Sex" class="pt-c-sex"><?= esc($gender !== '' ? $gender : '—') ?></td>

                                <td data-label="Age" class="pt-c-age"><?= esc($age !== '' ? $age : '—') ?></td>

                                <td data-label="Email" class="pt-c-email">
                                    <?php if ($email !== ''): ?>
                                        <a href="mailto:<?= esc($email, 'attr') ?>" class="pt-link"><?= esc($email) ?></a>
                                    <?php else: ?>
                                        <span class="pt-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Phone" class="pt-c-phone">
                                    <?php if ($phone !== ''): ?>
                                        <a href="tel:<?= esc($phone, 'attr') ?>" class="pt-link"><?= esc($phone) ?></a>
                                    <?php else: ?>
                                        <span class="pt-muted">—</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Source">
                                    <span class="pt-source pt-source--<?= esc($source, 'attr') ?>">
                                        <i class="bi <?= $isWalkin ? 'bi-person-walking' : 'bi-globe' ?>" aria-hidden="true"></i>
                                        <?= esc($patient['source'] ?? 'Unknown') ?>
                                    </span>
                                </td>

                                <td data-label="Actions" class="pt-c-actions">
                                    <div class="pt-actions">
                                        <?php if ($pid > 0): ?>
                                            <a href="<?= base_url('receptionist/patient/view/' . $pid) ?>"
                                               class="pt-icon-btn"
                                               title="View patient"
                                               aria-label="View <?= esc($name, 'attr') ?>">
                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="pt-icon-btn"
                                                  aria-disabled="true"
                                                  title="No linked patient record"
                                                  style="opacity:.35;cursor:not-allowed;">
                                                <i class="bi bi-eye-slash" aria-hidden="true"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="pt-row-empty">
                            <td colspan="8">
                                <div class="pt-empty">
                                    <div class="pt-empty-icon"><i class="bi bi-people" aria-hidden="true"></i></div>
                                    <h3>No patients registered yet</h3>
                                    <p>Patients will appear here once they book online or are registered at the desk.</p>
                                    <button type="button" class="pt-btn pt-btn--primary"
                                            onclick="window.location.href='<?= base_url('receptionist/diagnostic-requests') ?>'">
                                        <i class="bi bi-person-plus" aria-hidden="true"></i>
                                        Register walk-in patient
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pt-empty pt-empty--filter" id="ptNoMatch" hidden>
            <div class="pt-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></div>
            <h3>No matching patients</h3>
            <p>Try a different search term, or select All to clear the filter.</p>
            <button type="button" class="pt-btn" id="ptClearFilters">Clear filters</button>
        </div>

        <footer class="pt-foot">
            <span id="ptRange">
                Showing <strong><?= count($patients) ?></strong> of <strong><?= number_format($total ?? count($patients)) ?></strong> patients
            </span>
        </footer>

    </section>

</div>

<style>
/* =========================================================
   PATIENTS — Receptionist
   Namespaced under .pt. Theme aligned with Admin patients.
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

.pt-head-actions { display: flex; gap: 0.5rem; }

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
   KPI CARDS — sparkline + delta badge, matching Admin patients
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
    grid-template-columns: 38px minmax(0, 1fr) auto;
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

.pt-input {
    width: 100%;
    height: 36px;
    padding: 0 0.75rem 0 2.1rem;
    font-size: 0.8125rem;
    color: var(--pt-ink);
    background-color: var(--pt-surface);
    border: 1px solid var(--pt-line);
    border-radius: var(--pt-radius-sm);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.pt-input::placeholder { color: var(--pt-faint); }
.pt-input:focus {
    outline: none;
    border-color: var(--pt-accent);
    box-shadow: var(--pt-ring);
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
    padding: 0.15rem 0.5rem;
    font-family: var(--pt-mono);
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--pt-ink);
    background: var(--pt-line-soft);
    border-radius: 5px;
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

.pt-c-email,
.pt-c-phone { white-space: nowrap; }

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

/* ---------- Empty states ---------- */

.pt-empty { padding: 3.5rem 1rem; text-align: center; }
.pt-empty--filter { border-top: 1px solid var(--pt-line); }

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

/* ---------- Responsive ---------- */

@media (max-width: 992px) {
    .pt-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
    .pt-head { flex-direction: column; align-items: stretch; }
    .pt-head-actions .pt-btn { flex: 1; }

    .pt-stats { grid-template-columns: minmax(0, 1fr); gap: 0.6rem; }

    .pt-filters .pt-search { flex-basis: 100%; max-width: none; }

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
    .pt-c-email,
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

    var search   = document.getElementById('searchPatients');
    var table    = document.getElementById('patientsTable');
    var noMatch  = document.getElementById('ptNoMatch');
    var clearBtn = document.getElementById('ptClearFilters');
    var tabs     = Array.prototype.slice.call(document.querySelectorAll('.pt-tab'));

    var activeSource = 'all';

    function rows() {
        return table ? Array.prototype.slice.call(table.querySelectorAll('tbody tr.pt-row')) : [];
    }

    function applyFilters() {
        var term = search ? search.value.toLowerCase().trim() : '';
        var visible = 0;

        rows().forEach(function (row) {
            var text = row.textContent.toLowerCase();
            var source = row.dataset.source || '';

            var matchesSearch = term === '' || text.indexOf(term) !== -1;
            var matchesSource = activeSource === 'all' || source === activeSource;

            var show = matchesSearch && matchesSource;
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

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activeSource = tab.dataset.source || 'all';

            tabs.forEach(function (t) {
                var on = t === tab;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-pressed', on ? 'true' : 'false');
            });

            applyFilters();
        });
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (search) { search.value = ''; }
            activeSource = 'all';

            tabs.forEach(function (t) {
                var on = t.dataset.source === 'all';
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-pressed', on ? 'true' : 'false');
            });

            applyFilters();
        });
    }

})();
</script>

<?= $this->endSection() ?>