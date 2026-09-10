<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Patient Management<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<?php
    $patients = (isset($patients) && is_array($patients)) ? $patients : [];

    // Counts computed once here so both the header lede and the stat
    // cards use the same numbers.
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

    // PNG avatar filenames inside public/assets/images/.
    // Change these two strings if your files are named differently.
    $maleAvatar   = 'man-avatar.png';
    $femaleAvatar = 'woman-avatar.png';

    // PNG stat-card icon filenames. Change any of these three strings
    // to point at your own PNGs. Files must live in:
    //   public/assets/images/
    $statIcons = [
        'total'  => 'multiple-users-silhouette.png',
        'online' => 'worldwide.png',
        'walkin' => 'walk.png',
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


    <!-- STATS -->
    <div class="pt-stats">
        <div class="pt-stat">
            <div class="pt-stat-icon pt-stat-icon--teal">
                <img src="<?= esc(base_url('assets/images/' . $statIcons['total']), 'attr') ?>"
                     alt=""
                     class="pt-stat-img"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="pt-stat-body">
                <div class="pt-stat-value"><?= number_format($total ?? count($patients)) ?></div>
                <div class="pt-stat-label">Total patients</div>
                <div class="pt-stat-sub">All registered patients</div>
            </div>
        </div>

        <div class="pt-stat">
            <div class="pt-stat-icon pt-stat-icon--blue">
                <img src="<?= esc(base_url('assets/images/' . $statIcons['online']), 'attr') ?>"
                     alt=""
                     class="pt-stat-img"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="pt-stat-body">
                <div class="pt-stat-value"><?= number_format($onlineCount) ?></div>
                <div class="pt-stat-label">Online patients</div>
                <div class="pt-stat-sub">Booked online</div>
            </div>
        </div>

        <div class="pt-stat">
            <div class="pt-stat-icon pt-stat-icon--amber">
                <img src="<?= esc(base_url('assets/images/' . $statIcons['walkin']), 'attr') ?>"
                     alt=""
                     class="pt-stat-img"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="pt-stat-body">
                <div class="pt-stat-value"><?= number_format($walkinCount) ?></div>
                <div class="pt-stat-label">Walk-in patients</div>
                <div class="pt-stat-sub">Registered at the desk</div>
            </div>
        </div>
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

                            // Avatar kind: male, female, or neutral fallback to initials
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
                                        <button type="button" class="pt-icon-btn" title="View patient" aria-label="View <?= esc($name, 'attr') ?>">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </button>
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
   PATIENTS
   Namespaced under .pt so the layout's generic card and table
   rules can't leak in. Visual tokens match the appointments
   and diagnostic requests pages.
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
    --pt-accent:      #0d9488;
    --pt-accent-dark: #0f766e;
    --pt-accent-soft: #e6f7f7;
    --pt-radius:      10px;
    --pt-radius-sm:   7px;
    --pt-ring:        0 0 0 3px rgba(13, 148, 136, 0.18);
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

/* ---------- Stats ---------- */

.pt-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.9rem;
    margin-bottom: 1.25rem;
}

.pt-stat {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 0;
    padding: 0.9rem 1rem;
    background: var(--pt-surface);
    border: 1px solid var(--pt-line);
    border-radius: var(--pt-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.pt-stat-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    border-radius: var(--pt-radius-sm);
}

/* Stat icon backgrounds stay transparent so the PNG sits directly
   on the card. The tinted variants below are kept as hooks in case
   you want to turn a subtle color back on for one card. */
.pt-stat-icon--teal  { background: transparent; }
.pt-stat-icon--blue  { background: transparent; }
.pt-stat-icon--amber { background: transparent; }

/* The PNG stat icon. Sized to sit comfortably inside the 36x36
   wrapper with a little breathing room. */
.pt-stat-img {
    width: 24px;
    height: 24px;
    object-fit: contain;
    display: block;
}

.pt-stat-body { min-width: 0; }

.pt-stat-value {
    font-size: 1.35rem;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -0.02em;
    color: var(--pt-ink);
    font-variant-numeric: tabular-nums;
}

.pt-stat-label {
    margin-top: 0.15rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--pt-text);
}

.pt-stat-sub {
    font-size: 0.72rem;
    color: var(--pt-muted);
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

/* Source rail */
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

.pt-row--online  { --rail: #3b82f6; }
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

/* Male / female / neutral avatar tints. The PNG fills the circle;
   the tint shows through transparent PNG edges as a subtle backdrop. */
.pt-avatar--male    { background: #eaf2fe; color: #1d4ed8; }
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

.pt-source--online  { color: #047857; background: #ecfdf5; }
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
    transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.pt-icon-btn:hover {
    background: var(--pt-accent-soft);
    color: var(--pt-accent-dark);
    border-color: #99f6e4;
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