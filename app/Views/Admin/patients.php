<?= $this->extend('Layouts/AdminLayout') ?>

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


    $kpiTrends = $kpiTrends ?? [];

?>

<div class="pt">

    <!-- PAGE HEADER -->
    <header class="pt-head">

        <div class="pt-head-actions">
            <button type="button" class="pt-btn" onclick="exportPatients()">
                <i class="bi bi-download" aria-hidden="true"></i>
                Export
            </button>
            <a class="pt-btn pt-btn--primary"
               href="<?= base_url('admin/diagnostic-requests') ?>">
                <i class="bi bi-person-plus" aria-hidden="true"></i>
                Add Patient
            </a>
        </div>
    </header>


    <?= view('partials/kpi_strip', [
        'id'      => 'patients',
        'trends'  => $kpiTrends ?? [],
        'cards'   => [
            ['key' => 'total', 'label' => 'Total patients', 'value' => (int) ($total ?? count($patients)), 'delta' => 'sum', 'context' => 'new in 14 days'],
            ['key' => 'online', 'label' => 'Online', 'value' => (int) $onlineCount,                 'delta' => 'sum', 'context' => 'new in 14 days'],
            ['key' => 'walkin', 'label' => 'Walk-in', 'value' => (int) $walkinCount,                 'delta' => 'sum', 'context' => 'new in 14 days'],
        ],
    ]) ?>


    <!-- ALERTS -->

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
                        <th scope="col">Patient</th>
                        <th scope="col">Code</th>
                        <th scope="col">Source</th>
                        <th scope="col">Contact</th>
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
                        ?>
                            <tr class="pt-row pt-row--<?= esc($source, 'attr') ?>"
                                data-patient-row
                                data-source="<?= esc($source, 'attr') ?>"
                                data-status="<?= $isActive ? 'active' : 'inactive' ?>"
                                data-name="<?= esc(strtolower($name), 'attr') ?>"
                                data-age="<?= esc((string) (int) ($patient['age'] ?? 0), 'attr') ?>"
                                data-created="<?= $patient['created_at'] ? esc(date('Y-m-d H:i:s', strtotime($patient['created_at'])), 'attr') : '' ?>">

                                <?php
                                $demo = implode(' · ', array_filter([
                                    ($gender !== '' && $gender !== 'N/A') ? $gender : '',
                                    ($age !== '' && $age !== 'N/A') ? $age . ' y' : '',
                                ]));
                                ?>
                                <td class="pt-c-patient">
                                    <div class="pt-patient">
                                        <span class="pt-avatar" aria-hidden="true"><?= esc($initialsOf($name)) ?></span>
                                        <div class="pt-patient-body">
                                            <span class="pt-name"><?= esc($name) ?></span>
                                            <span class="pt-sub"><?= $demo !== '' ? esc($demo) : 'No age or sex on file' ?></span>
                                        </div>
                                    </div>
                                </td>

                                <td data-label="Code" class="pt-c-code">
                                    <?php if ($isApproved): ?>
                                        <span class="pt-code"><?= esc($code) ?></span>
                                    <?php else: ?>
                                        <span class="pt-code-pending">Pending approval</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Source">
                                    <span class="pt-source pt-source--<?= esc($source, 'attr') ?>"><?= $isWalkin ? 'Walk-in' : 'Online' ?></span>
                                </td>

                                <td data-label="Contact" class="pt-c-contact">
                                    <?php if ($phone === '' && $email === ''): ?>
                                        <span class="pt-muted">&mdash;</span>
                                    <?php else: ?>
                                        <div class="pt-contact">
                                            <?php if ($phone !== ''): ?>
                                                <a href="tel:<?= esc($phone, 'attr') ?>" class="pt-link"><?= esc($phone) ?></a>
                                            <?php endif; ?>
                                            <?php if ($email !== ''): ?>
                                                <a href="mailto:<?= esc($email, 'attr') ?>" class="pt-link pt-link--sub"><?= esc($email) ?></a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Registered" class="pt-c-date">
                                    <?php if (!empty($patient['created_at'])): ?>
                                        <time datetime="<?= esc(date('c', strtotime($patient['created_at'])), 'attr') ?>"><?= esc(date('M j, Y', strtotime($patient['created_at']))) ?></time>
                                    <?php else: ?>
                                        <span class="pt-muted">&mdash;</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Status">
                                    <span class="pt-status pt-status--<?= $isActive ? 'active' : 'inactive' ?>"
                                          title="<?= $isActive ? 'Visited in the last 30 days' : 'No visit in the last 30 days' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
                                </td>

                                <td data-label="Actions" class="pt-c-actions">
                                    <div class="pt-actions">
                                        <?php if (!$isApproved && $patientId > 0): ?>
                                            <button type="button" class="pt-approve-btn"
                                                    aria-label="Approve <?= esc($name, 'attr') ?>"
                                                    onclick="approvePatient(<?= $patientId ?>)">
                                                <i class="bi bi-check2" aria-hidden="true"></i>
                                                Approve
                                            </button>
                                        <?php endif; ?>

                                        <button type="button" class="pt-icon-btn"
                                                title="View patient"
                                                aria-label="View <?= esc($name, 'attr') ?>"
                                                onclick="viewPatient(<?= $patientId ?>)">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="pt-row-empty" data-empty-row>
                            <td colspan="7">
                                <div class="pt-empty">
                                    <div class="pt-empty-icon"><i class="bi bi-people" aria-hidden="true"></i></div>
                                    <h3>No patients registered yet</h3>
                                    <p>Patients will appear here once they book online or are registered at the desk.</p>
                                    <a class="pt-btn pt-btn--primary" href="<?= base_url('admin/diagnostic-requests') ?>">
                                        <i class="bi bi-person-plus" aria-hidden="true"></i>
                                        Add patient
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr class="pt-row-empty" data-noresults-row style="display: none;">
                        <td colspan="7">
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
    --pt-ink:         var(--pm-text, #10202E);
    --pt-text:        var(--pm-text-soft, #3B4C5E);
    --pt-muted:       var(--pm-text-muted, #5D6E80);
    --pt-faint:       var(--pm-text-muted);
    --pt-line:        var(--pm-line, #DDE4EC);
    --pt-line-soft:   var(--pm-line);
    --pt-surface:     var(--pm-surface);
    --pt-subtle:      var(--pm-subtle);
    --pt-row-hover:   var(--pm-subtle);

    --pt-accent:      var(--pm-accent, #175563);
    --pt-accent-dark: var(--pm-accent-hover, #10404B);
    --pt-accent-soft: var(--pm-accent-soft, #EBF2F4);

    --pt-gold:        #93370D;
    --pt-gold-soft:   var(--pm-tint-amber);

    --pt-radius:      8px;
    --pt-radius-sm:   6px;
    --pt-ring:        0 0 0 3px rgba(23, 85, 99, 0.18);
    --pt-mono:        var(--pm-mono, ui-monospace, monospace);

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
.pt-alert--success { background: var(--pm-dk-bg-green, #f0fdf4); border-color: var(--pm-dk-edge-green, #bbf7d0); color: var(--pm-dk-fg-green, #166534); }
.pt-alert--error   { background: var(--pm-dk-bg-red, #fef2f2); border-color: var(--pm-dk-edge-red, #fecaca); color: var(--pm-dk-fg-red, #991b1b); }

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
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.pt-btn:hover { background: var(--pt-subtle); border-color: var(--pm-line); color: var(--pt-ink); }
.pt-btn i { font-size: 0.9em; }

.pt-btn--primary,
.pt-btn--primary:hover { color: #ffffff; }
.pt-btn--primary { background: var(--pt-accent); border-color: var(--pt-accent); }
.pt-btn--primary:hover { background: var(--pt-accent-dark); border-color: var(--pt-accent-dark); }





/* Icon tones per card variant */





/* Small delta badge under the label */


/* ---------- Panel ---------- */

.pt-panel {
    background: var(--pt-surface);
    border: 1px solid var(--pt-line);
    border-radius: var(--pt-radius);
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
    border-radius: 4px;
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
    padding: 0.6rem 1rem;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    text-align: left;
    white-space: nowrap;
    color: var(--pt-muted);
    background: var(--pt-subtle);
    border-bottom: 1px solid var(--pt-line);
}

.pt-table td {
    padding: 0.7rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid var(--pt-line-soft);
}

.pt-table tbody tr:last-child td { border-bottom: 0; }

.pt-row { transition: background-color 0.12s ease; }
.pt-row:hover { background: var(--pt-row-hover); }

/* ---- patient ---- */

.pt-patient { display: flex; align-items: center; gap: 0.65rem; min-width: 0; }

.pt-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--pt-text);
    background: var(--pt-line-soft);
    border-radius: 50%;
}

.pt-patient-body { min-width: 0; }

.pt-name {
    display: block;
    max-width: 260px;
    font-weight: 600;
    color: var(--pt-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pt-sub { display: block; font-size: 0.74rem; color: var(--pt-muted); }

/* ---- code ---- */

.pt-c-code { white-space: nowrap; }

.pt-code {
    font-family: var(--pt-mono);
    font-size: 0.76rem;
    font-weight: 500;
    color: var(--pt-text);
}

.pt-code-pending {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--pt-gold);
}

.pt-code-pending::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #DC6803;
}

/* ---- source: a dot label, not a tinted pill ---- */

.pt-source {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--pt-text);
    white-space: nowrap;
}

.pt-source::before {
    content: "";
    width: 7px;
    height: 7px;
    border-radius: 2px;
    background: var(--src, var(--pt-faint));
}

.pt-source--online  { --src: #175563; }
.pt-source--walk-in { --src: #D29A1E; }

/* ---- contact ---- */

.pt-contact { display: flex; flex-direction: column; min-width: 0; line-height: 1.35; }

.pt-link {
    max-width: 240px;
    color: var(--pt-text);
    text-decoration: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-variant-numeric: tabular-nums;
}

.pt-link--sub { font-size: 0.74rem; color: var(--pt-muted); }

.pt-link:hover { color: var(--pt-accent); text-decoration: underline; text-underline-offset: 2px; }

.pt-muted { color: var(--pt-faint); }

.pt-c-date { white-space: nowrap; font-variant-numeric: tabular-nums; color: var(--pt-text); }

/* ---- status: dot + text ---- */

.pt-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--pt-text);
    white-space: nowrap;
}

.pt-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--dot, #98A2B3);
}

.pt-status--active   { --dot: #17B26A; }
.pt-status--inactive { --dot: #98A2B3; color: var(--pt-muted); }

/* ---- actions ---- */

.pt-c-actions { width: 1%; text-align: right; white-space: nowrap; }

.pt-actions { display: inline-flex; align-items: center; gap: 0.35rem; }

.pt-icon-btn {
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    color: var(--pt-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.pt-icon-btn:hover { background: var(--pt-line-soft); color: var(--pt-ink); }
.pt-icon-btn.is-disabled { opacity: 0.35; cursor: not-allowed; }
.pt-icon-btn.is-disabled:hover { background: transparent; color: var(--pt-muted); }

.pt-approve-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    height: 30px;
    padding: 0 0.7rem;
    font-size: 0.76rem;
    font-weight: 600;
    color: #FFFFFF;
    background: var(--pt-accent);
    border: 1px solid var(--pt-accent);
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease;
}

.pt-approve-btn:hover { background: var(--pt-accent-dark); border-color: var(--pt-accent-dark); }

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

.pt-page:hover:not(:disabled) { background: var(--pt-subtle); border-color: var(--pm-line); }
.pt-page:disabled { opacity: 0.45; cursor: not-allowed; }
.pt-page[aria-current="page"] { color: var(--pt-accent); background: var(--pt-accent-soft); border-color: var(--pm-dk-edge-blue, #bcd6f3); }
.pt-page-gap { min-width: 20px; text-align: center; color: var(--pt-faint); }

/* ---------- Responsive ---------- */

@media (max-width: 992px) {
}

@media (max-width: 768px) {
    .pt-head { flex-direction: column; align-items: stretch; }
    .pt-head-actions .pt-btn { flex: 1; }


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

    .pt-table td.pt-c-patient { display: block; margin-bottom: 0.4rem; text-align: left; }
    .pt-name { max-width: none; }

    .pt-table td.pt-c-actions { justify-content: flex-end; margin-top: 0.5rem; }

    .pt-contact { align-items: flex-end; }
    .pt-link { max-width: 60vw; }

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

    fetch('<?= base_url() ?>admin/approve-patient/' + patientId, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '<?= csrf_hash() ?>'
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
    window.location.href = '<?= base_url() ?>admin/patient/view/' + patientId;
}

function exportPatients() {
    alert('Export functionality coming soon!');
}
</script>

<?= $this->endSection() ?>