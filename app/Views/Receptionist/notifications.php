<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Notifications<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<?php
$notifBase = $notif_base ?? 'receptionist/notifications';

/*
 * Avatar PNGs are expected at:
 *   public/assets/images/man-avatar.png
 *   public/assets/images/woman-avatar.png
 */
$maleAvatar   = 'man-avatar.png';
$femaleAvatar = 'woman-avatar.png';

/*
 * Initials fallback. Used when the notification is about a patient
 * whose gender we do not know, so the card still reads as a person
 * rather than showing a generic bell.
 */
$initialsOf = static function ($name) {
    $parts = preg_split('/\s+/', trim((string) $name));
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    $out   = mb_strtoupper($first . $last);
    return $out !== '' ? $out : '?';
};

/*
 * Pull a clean patient name out of the notification title.
 * Strips prefixes like "New Appointment Request: " and "New Lab Request: ".
 */
$patientNameFrom = static function ($title) {
    $name = preg_replace(
        '/^(Pending Appointment|New Appointment Request|New Appointment|Appointment|New Lab Request|New X-Ray Request|X-Ray Examination|Laboratory Request):\s*/i',
        '',
        (string) $title
    );
    return trim($name);
};

/*
 * Bucket a timestamp into a section heading. The list arrives sorted
 * newest first, so walking it in order produces the headings in order.
 */
$bucketOf = static function ($ts) {
    if (!$ts) {
        return 'Earlier';
    }
    if ($ts >= strtotime('today')) {
        return 'Today';
    }
    if ($ts >= strtotime('yesterday')) {
        return 'Yesterday';
    }
    if ($ts >= strtotime('-7 days')) {
        return 'Earlier this week';
    }
    if ($ts >= strtotime('-30 days')) {
        return 'Earlier this month';
    }
    return 'Older';
};

/*
 * Short relative time for the card. The exact timestamp stays
 * available in the title attribute and the datetime attribute.
 */
$relativeTime = static function ($ts) {
    if (!$ts) {
        return '';
    }

    $diff = time() - $ts;

    if ($diff < 60)    return 'Just now';
    if ($diff < 3600)  { $n = (int) floor($diff / 60);    return $n . ($n === 1 ? ' min ago'  : ' mins ago'); }
    if ($diff < 86400) { $n = (int) floor($diff / 3600);  return $n . ($n === 1 ? ' hour ago' : ' hours ago'); }
    if ($diff < 604800){ $n = (int) floor($diff / 86400); return $n . ($n === 1 ? ' day ago'  : ' days ago'); }

    return date('M j', $ts);
};

/* Labels for the type filter chips. */
$typeLabels = [
    'appointment' => 'Appointments',
    'xray'        => 'X-Ray',
    'lab'         => 'Laboratory',
    'billing'     => 'Billing',
    'payment'     => 'Payments',
    'system'      => 'System',
];

/* Count the types actually present, so we only show chips that matter. */
$typeCounts = [];
foreach (($notifications ?? []) as $n) {
    $t = (string) ($n['type'] ?? 'system');
    $typeCounts[$t] = ($typeCounts[$t] ?? 0) + 1;
}
arsort($typeCounts);
?>

<div class="nc">

    <!-- PAGE HEADER -->
    <header class="nc-head">
        <div class="nc-head-text">
            <h2 class="nc-title">Notifications</h2>
            <p class="nc-lede">
                <?php if ($unread_count > 0): ?>
                    <strong><?= (int) $unread_count ?></strong>
                    unread notification<?= $unread_count === 1 ? '' : 's' ?>.
                <?php else: ?>
                    You're all caught up.
                <?php endif; ?>
            </p>
        </div>

        <?php if ($unread_count > 0): ?>
            <div class="nc-head-actions">
                <a href="<?= base_url($notifBase . '/mark-all-read') ?>" class="nc-btn">
                    <i class="bi bi-check2-all" aria-hidden="true"></i>
                    <span>Mark all as read</span>
                </a>
            </div>
        <?php endif; ?>
    </header>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="nc-alert nc-alert--success alert alert-dismissible fade show" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <span><?= esc(session()->getFlashdata('success')) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="nc-alert nc-alert--error alert alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
        </div>
    <?php endif; ?>


    <!-- WORKLIST -->
    <section class="nc-panel" aria-label="Notifications">

        <div class="nc-toolbar">
            <div class="nc-tabs" role="tablist" aria-label="Filter by read status">
                <button type="button" class="nc-tab is-active" data-filter="all" aria-pressed="true">
                    <span>All</span>
                    <span class="nc-tab-count" data-count-for="all"><?= (int) $total_count ?></span>
                </button>
                <button type="button" class="nc-tab" data-filter="unread" aria-pressed="false">
                    <span>Unread</span>
                    <span class="nc-tab-count" data-count-for="unread"><?= (int) $unread_count ?></span>
                </button>
                <button type="button" class="nc-tab" data-filter="read" aria-pressed="false">
                    <span>Read</span>
                    <span class="nc-tab-count" data-count-for="read"><?= (int) ($total_count - $unread_count) ?></span>
                </button>
            </div>

            <?php if (!empty($notifications)): ?>
                <div class="nc-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search"
                           id="ncSearch"
                           class="nc-search-input"
                           placeholder="Search name or message"
                           autocomplete="off"
                           aria-label="Search notifications">
                    <button type="button" class="nc-search-clear" id="ncSearchClear" hidden aria-label="Clear search">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <?php if (count($typeCounts) > 1): ?>
            <div class="nc-chips" role="group" aria-label="Filter by type">
                <button type="button" class="nc-chip is-active" data-type="all" aria-pressed="true">
                    All types
                </button>
                <?php foreach ($typeCounts as $t => $count): ?>
                    <button type="button" class="nc-chip" data-type="<?= esc($t, 'attr') ?>" aria-pressed="false">
                        <span class="nc-chip-dot nc-chip-dot--<?= esc($t, 'attr') ?>" aria-hidden="true"></span>
                        <?= esc($typeLabels[$t] ?? ucfirst($t)) ?>
                        <span class="nc-chip-count"><?= (int) $count ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($notifications)): ?>

            <div class="nc-list" id="ncList">
                <?php
                $currentBucket = null;
                $groupIndex    = 0;
                $cardIndex     = 0;

                foreach ($notifications as $notif):

                    $isUnread = ((int) ($notif['is_read'] ?? 0)) === 0;
                    $type     = (string) ($notif['type'] ?? 'system');

                    $createdTs = !empty($notif['created_at'])
                        ? strtotime($notif['created_at'])
                        : false;

                    $bucket = $bucketOf($createdTs);

                    if ($bucket !== $currentBucket):
                        if ($currentBucket !== null): ?>
                            </div>
                        </section>
                        <?php endif;
                        $currentBucket = $bucket;
                        $groupIndex++;
                        ?>
                        <section class="nc-group" data-group="<?= esc($bucket, 'attr') ?>">
                            <h3 class="nc-group-head">
                                <span><?= esc($bucket) ?></span>
                            </h3>
                            <div class="nc-group-body">
                    <?php endif;

                    /*
                     * Gender is stored at write time on the notification row.
                     * Only 'male' or 'female' are ever persisted, so this is
                     * a direct read rather than a guess from the message.
                     */
                    $gender = strtolower((string) ($notif['reference_gender'] ?? ''));
                    if (!in_array($gender, ['male', 'female'], true)) {
                        $gender = null;
                    }

                    $icon  = (string) ($notif['icon'] ?? 'bi-bell-fill');
                    $color = (string) ($notif['color'] ?? $type);

                    $displayTitle  = (string) ($notif['title'] ?? '');
                    $message       = (string) ($notif['message'] ?? '');
                    $nameForAvatar = $patientNameFrom($displayTitle);

                    /*
                     * An avatar is shown when the notification is about a
                     * patient and we know the patient's gender. Otherwise
                     * the coloured type icon is shown instead.
                     */
                    $showAvatar = ($gender !== null && $type === 'appointment');

                    /* Lowercased haystack for the client-side search. */
                    $haystack = mb_strtolower($displayTitle . ' ' . $message);

                    $cardIndex++;
                ?>
                    <article class="nc-card<?= $isUnread ? ' is-unread' : '' ?><?= $showAvatar ? ' is-patient' : '' ?>"
                             data-read-status="<?= $isUnread ? 'unread' : 'read' ?>"
                             data-type="<?= esc($type, 'attr') ?>"
                             data-search="<?= esc($haystack, 'attr') ?>"
                             style="--nc-i: <?= (int) min($cardIndex, 12) ?>">

                        <!-- Leading visual: patient avatar or type icon -->
                        <div class="nc-lead">
                            <?php if ($showAvatar): ?>
                                <span class="nc-avatar nc-avatar--<?= esc($gender, 'attr') ?>">
                                    <img src="<?= esc(base_url('assets/images/' . ($gender === 'female' ? $femaleAvatar : $maleAvatar)), 'attr') ?>"
                                         alt=""
                                         class="nc-avatar-img"
                                         loading="lazy"
                                         decoding="async">
                                </span>
                            <?php elseif ($type === 'appointment'): ?>
                                <span class="nc-avatar nc-avatar--initials" aria-hidden="true">
                                    <?= esc($initialsOf($nameForAvatar)) ?>
                                </span>
                            <?php else: ?>
                                <span class="nc-tile nc-tile--<?= esc($color, 'attr') ?>" aria-hidden="true">
                                    <i class="bi <?= esc($icon) ?>"></i>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Body -->
                        <div class="nc-body">

                            <header class="nc-body-head">
                                <h4 class="nc-card-title"><?= esc($displayTitle) ?></h4>

                                <?php if ($createdTs): ?>
                                    <time class="nc-time"
                                          datetime="<?= esc(date('c', $createdTs), 'attr') ?>"
                                          title="<?= esc(date('M j, Y · g:i A', $createdTs), 'attr') ?>">
                                        <?= esc($relativeTime($createdTs)) ?>
                                    </time>
                                <?php endif; ?>
                            </header>

                            <p class="nc-card-message"><?= esc($message) ?></p>

                            <footer class="nc-body-foot">
                                <?php if (!empty($notif['can_open'])): ?>
                                    <a href="<?= base_url($notifBase . '/mark-read/' . $notif['id']) ?>"
                                       class="nc-btn nc-btn--sm">
                                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                        <span>View details</span>
                                    </a>
                                <?php else: ?>
                                    <span class="nc-locked">
                                        <i class="bi bi-eye-slash" aria-hidden="true"></i>
                                        <span>Notification only</span>
                                    </span>
                                <?php endif; ?>

                                <?php if ($isUnread): ?>
                                    <a href="<?= base_url($notifBase . '/mark-read/' . $notif['id']) ?>"
                                       class="nc-link">
                                        <i class="bi bi-check2" aria-hidden="true"></i>
                                        <span>Mark read</span>
                                    </a>
                                <?php endif; ?>

                                <a href="<?= base_url($notifBase . '/delete/' . $notif['id']) ?>"
                                   class="nc-link nc-link--danger"
                                   onclick="return confirm('Delete this notification?')">
                                    <i class="bi bi-trash3" aria-hidden="true"></i>
                                    <span>Delete</span>
                                </a>
                            </footer>

                        </div>

                      
                    </article>
                <?php endforeach; ?>

                <?php if ($currentBucket !== null): ?>
                            </div>
                        </section>
                <?php endif; ?>
            </div>

            <!-- No match for the current filter -->
            <div class="nc-empty" id="ncEmpty" hidden>
                <div class="nc-empty-icon"><i class="bi bi-funnel" aria-hidden="true"></i></div>
                <h3>Nothing matches that filter</h3>
                <p>Try a different filter, or clear the search to see everything.</p>
                <button type="button" class="nc-btn" id="ncClearFilter">
                    <span>Reset filters</span>
                </button>
            </div>

        <?php else: ?>

            <div class="nc-empty">
                <div class="nc-empty-icon"><i class="bi bi-bell-slash" aria-hidden="true"></i></div>
                <h3>No notifications yet</h3>
                <p>New alerts and appointment requests will show up here.</p>
            </div>

        <?php endif; ?>

    </section>

</div>


<style>
/* =========================================================
   NOTIFICATIONS
   Everything is scoped under .nc so the layout's global
   card, button, and table rules cannot leak in.
   ========================================================= */

.nc {
    /* Neutrals */
    --nc-ink:         #0f172a;
    --nc-text:        #334155;
    --nc-muted:       #64748b;
    --nc-faint:       #94a3b8;
    --nc-line:        #e5e7eb;
    --nc-line-soft:   #f1f5f9;
    --nc-surface:     #ffffff;
    --nc-canvas:      #f8fafc;
    --nc-rail:        #f9fafb;

    /* Accent (kept teal to match the receptionist palette) */
    --nc-accent:      #0d9488;
    --nc-accent-dark: #0f766e;
    --nc-accent-soft: #e6fbf6;

    /* States */
    --nc-danger:      #dc2626;
    --nc-danger-soft: #fef2f2;
    --nc-male-soft:   #eaf2fe;
    --nc-male-ink:    #1d4ed8;
    --nc-female-soft: #fce9ee;
    --nc-female-ink:  #b32e50;

    --nc-radius-lg:   14px;
    --nc-radius-md:   10px;
    --nc-radius-sm:   7px;

    color: var(--nc-text);
    -webkit-font-smoothing: antialiased;
}

.nc *,
.nc *::before,
.nc *::after { box-sizing: border-box; }

.nc *:focus-visible {
    outline: 2px solid var(--nc-accent);
    outline-offset: 2px;
    border-radius: 4px;
}

/* ---------- Page header ---------- */

.nc-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}

.nc-head-text { min-width: 0; }

.nc-title {
    margin: 0 0 0.2rem;
    font-size: 1.25rem;
    font-weight: 700;
    letter-spacing: -0.015em;
    color: var(--nc-ink);
}

.nc-lede {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--nc-muted);
}

.nc-lede strong { color: var(--nc-ink); font-weight: 600; }

.nc-head-actions { display: flex; gap: 0.5rem; }

/* ---------- Alerts ---------- */

.nc-alert {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: var(--nc-radius-md);
    font-size: 0.8125rem;
}

.nc-alert > span { flex: 1; min-width: 0; }
.nc-alert.alert-dismissible { padding-right: 0.75rem; }
.nc-alert .btn-close { position: static; padding: 0.5rem; margin-left: auto; font-size: 0.7rem; }
.nc-alert--success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
.nc-alert--error   { background: #fef2f2; border-color: #fecaca; color: #991b1b; }

/* ---------- Buttons (shared) ---------- */

.nc-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    height: 36px;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    color: var(--nc-ink);
    background: var(--nc-surface);
    border: 1px solid var(--nc-line);
    border-radius: var(--nc-radius-sm);
    box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease,
                border-color 0.15s ease,
                color 0.15s ease;
}

.nc-btn:hover {
    background: var(--nc-rail);
    border-color: #cbd5e1;
    color: var(--nc-ink);
}

.nc-btn i { font-size: 0.9em; }

/* The small variant used inside card footers. */
.nc-btn--sm {
    height: 30px;
    padding: 0 0.7rem;
    font-size: 0.775rem;
    font-weight: 600;
    color: var(--nc-accent-dark);
    background: var(--nc-accent-soft);
    border-color: transparent;
    box-shadow: none;
}

.nc-btn--sm:hover {
    background: var(--nc-accent);
    border-color: var(--nc-accent);
    color: #ffffff;
}

/* Quiet inline actions (Mark read, Delete) */
.nc-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    height: 30px;
    padding: 0 0.55rem;
    font-size: 0.775rem;
    font-weight: 500;
    color: var(--nc-muted);
    text-decoration: none;
    border-radius: var(--nc-radius-sm);
    transition: background-color 0.15s ease, color 0.15s ease;
}

.nc-link:hover {
    background: var(--nc-line-soft);
    color: var(--nc-ink);
}

.nc-link--danger {
    color: var(--nc-danger);
    margin-left: auto; /* pushes Delete to the right edge */
}

.nc-link--danger:hover {
    background: var(--nc-danger-soft);
    color: #b91c1c;
}

/* ---------- Panel ---------- */

.nc-panel {
    background: var(--nc-surface);
    border: 1px solid var(--nc-line);
    border-radius: var(--nc-radius-lg);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

/* ---------- Toolbar: tabs + search on one line ---------- */

.nc-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding-right: 0.85rem;
    border-bottom: 1px solid var(--nc-line);
    background: var(--nc-surface);
}

/* ---------- Tabs ---------- */

.nc-tabs {
    display: flex;
    gap: 0.15rem;
    padding: 0 0.85rem;
    overflow-x: auto;
    scrollbar-width: none;
}

.nc-tabs::-webkit-scrollbar { display: none; }

.nc-tab {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.9rem 0.7rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--nc-muted);
    background: none;
    border: 0;
    white-space: nowrap;
    cursor: pointer;
    transition: color 0.15s ease;
}

.nc-tab::after {
    content: "";
    position: absolute;
    left: 0.5rem;
    right: 0.5rem;
    bottom: -1px;
    height: 2px;
    border-radius: 2px 2px 0 0;
    background: transparent;
    transform: scaleX(0.4);
    opacity: 0;
    transition: transform 0.18s ease, opacity 0.18s ease, background-color 0.15s ease;
}

.nc-tab:hover { color: var(--nc-ink); }
.nc-tab.is-active { color: var(--nc-ink); }

.nc-tab.is-active::after {
    background: var(--nc-accent);
    transform: scaleX(1);
    opacity: 1;
}

.nc-tab-count {
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--nc-faint);
    font-variant-numeric: tabular-nums;
    transition: color 0.15s ease;
}

.nc-tab.is-active .nc-tab-count { color: var(--nc-accent-dark); font-weight: 600; }

/* ---------- Search ---------- */

.nc-search {
    position: relative;
    display: flex;
    align-items: center;
    flex-shrink: 0;
    width: 15rem;
    max-width: 40vw;
}

.nc-search > .bi-search {
    position: absolute;
    left: 0.6rem;
    font-size: 0.8rem;
    color: var(--nc-faint);
    pointer-events: none;
}

.nc-search-input {
    width: 100%;
    height: 32px;
    padding: 0 1.9rem 0 1.9rem;
    font-size: 0.8rem;
    color: var(--nc-ink);
    background: var(--nc-canvas);
    border: 1px solid var(--nc-line);
    border-radius: 999px;
    outline: none;
    transition: border-color 0.15s ease, background-color 0.15s ease;
}

.nc-search-input::placeholder { color: var(--nc-faint); }

.nc-search-input:focus {
    background: var(--nc-surface);
    border-color: var(--nc-accent);
}

/* Hide the browser's own clear button; we supply our own. */
.nc-search-input::-webkit-search-cancel-button { display: none; }

.nc-search-clear {
    position: absolute;
    right: 0.35rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    font-size: 0.65rem;
    color: var(--nc-muted);
    background: none;
    border: 0;
    border-radius: 50%;
    cursor: pointer;
}

.nc-search-clear:hover { background: var(--nc-line-soft); color: var(--nc-ink); }

/* ---------- Type chips ---------- */

.nc-chips {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.7rem 0.85rem;
    background: var(--nc-surface);
    border-bottom: 1px solid var(--nc-line);
    overflow-x: auto;
    scrollbar-width: none;
}

.nc-chips::-webkit-scrollbar { display: none; }

.nc-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    height: 28px;
    padding: 0 0.7rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--nc-muted);
    background: var(--nc-canvas);
    border: 1px solid var(--nc-line);
    border-radius: 999px;
    white-space: nowrap;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.nc-chip:hover {
    color: var(--nc-ink);
    border-color: #cbd5e1;
}

.nc-chip.is-active {
    color: var(--nc-accent-dark);
    background: var(--nc-accent-soft);
    border-color: rgba(13, 148, 136, 0.28);
}

.nc-chip-count {
    font-size: 0.68rem;
    font-weight: 500;
    color: var(--nc-faint);
    font-variant-numeric: tabular-nums;
}

.nc-chip.is-active .nc-chip-count { color: var(--nc-accent-dark); }

.nc-chip-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

.nc-chip-dot--appointment { background: #0d9488; }
.nc-chip-dot--xray        { background: #6d28d9; }
.nc-chip-dot--lab         { background: #15803d; }
.nc-chip-dot--billing,
.nc-chip-dot--payment     { background: #be123c; }
.nc-chip-dot--system      { background: #b45309; }

/* ---------- List ---------- */

.nc-list {
    padding: 0.35rem 1rem 1rem;
    background: var(--nc-canvas);
}

/* ---------- Date groups ---------- */

.nc-group.is-hidden { display: none; }

.nc-group-head {
    position: sticky;
    top: 0;
    z-index: 2;
    display: flex;
    align-items: center;
    margin: 0;
    padding: 0.75rem 0 0.5rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--nc-faint);
    background: linear-gradient(var(--nc-canvas) 78%, rgba(248, 250, 252, 0));
}

.nc-group-body {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

/* ---------- Card ---------- */

.nc-card {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 0.95rem;
    padding: 1rem 1.1rem 1rem 1rem;
    background: var(--nc-surface);
    border: 1px solid #e2e8f0;
    border-radius: var(--nc-radius-md);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    transition: border-color 0.15s ease,
                box-shadow 0.15s ease,
                transform 0.15s ease;
    animation: nc-rise 0.32s cubic-bezier(0.2, 0.7, 0.3, 1) both;
    animation-delay: calc(var(--nc-i, 0) * 22ms);
}

@keyframes nc-rise {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: none; }
}

.nc-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 6px 16px -8px rgba(15, 23, 42, 0.18),
                0 1px 2px rgba(15, 23, 42, 0.04);
    transform: translateY(-1px);
}

.nc-card.is-hidden { display: none; }

.nc-card.is-unread {
    border-color: #d1e7de;
}

.nc-card.is-unread::before {
    content: "";
    position: absolute;
    top: 0.75rem;
    bottom: 0.75rem;
    left: 0;
    width: 3px;
    border-radius: 0 3px 3px 0;
    background: var(--nc-accent);
}

/* Search hit highlight */
.nc-card mark {
    padding: 0 0.1em;
    color: inherit;
    background: #fef08a;
    border-radius: 2px;
}

/* ---------- Leading visual: avatar or tile ---------- */

.nc-lead {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Avatars (patient photos or initials) */
.nc-avatar {
    position: relative;
    width: 52px;
    height: 52px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    overflow: hidden;
    font-size: 0.9rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    background: var(--nc-line-soft);
    color: var(--nc-muted);
    box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.05);
}

/* Ringed highlight on the patient avatar so the photo reads
   clearly against a white card. */
.nc-avatar--male {
    background: var(--nc-male-soft);
    color: var(--nc-male-ink);
    box-shadow: inset 0 0 0 1px rgba(29, 78, 216, 0.12);
}

.nc-avatar--female {
    background: var(--nc-female-soft);
    color: var(--nc-female-ink);
    box-shadow: inset 0 0 0 1px rgba(179, 46, 80, 0.12);
}

.nc-avatar--initials {
    background: var(--nc-line-soft);
    color: var(--nc-text);
    font-size: 0.92rem;
}

.nc-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Rounded square tile for non-patient notification types
   (x-ray, lab, billing, payment, system). */
.nc-tile {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--nc-radius-md);
    font-size: 1.1rem;
}

.nc-tile--appointment { background: #ccfbf1; color: #0d9488; }
.nc-tile--xray        { background: #ede9fe; color: #6d28d9; }
.nc-tile--lab         { background: #dcfce7; color: #15803d; }
.nc-tile--billing,
.nc-tile--payment     { background: #ffe4e6; color: #be123c; }
.nc-tile--system      { background: #fef3c7; color: #b45309; }

/* ---------- Body ---------- */

.nc-body {
    flex: 1;
    min-width: 0;
}

.nc-body-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.3rem;
    flex-wrap: wrap;
}

.nc-card-title {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    color: var(--nc-ink);
    overflow-wrap: anywhere;
    min-width: 0;
}

.nc-time {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding-right: 0.9rem; /* keeps clear of the unread dot */
    font-size: 0.735rem;
    color: var(--nc-faint);
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
    cursor: help;
}

.nc-card-message {
    margin: 0 0 0.75rem;
    font-size: 0.8125rem;
    line-height: 1.55;
    color: var(--nc-text);
    overflow-wrap: anywhere;
}

.nc-body-foot {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-wrap: wrap;
}

.nc-locked {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    height: 30px;
    padding: 0 0.55rem;
    font-size: 0.775rem;
    color: var(--nc-faint);
    font-style: italic;
}

/* ---------- Empty states ---------- */

.nc-empty {
    padding: 3.5rem 1rem;
    text-align: center;
    background: var(--nc-canvas);
}

.nc-empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    margin: 0 auto 0.85rem;
    font-size: 1.3rem;
    color: var(--nc-faint);
    background: var(--nc-line-soft);
    border-radius: var(--nc-radius-md);
}

.nc-empty h3 {
    margin: 0 0 0.25rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--nc-ink);
}

.nc-empty p {
    max-width: 26rem;
    margin: 0 auto 1rem;
    font-size: 0.8125rem;
    color: var(--nc-muted);
}

/* ---------- Responsive ---------- */

@media (max-width: 768px) {
    .nc-head { flex-direction: column; align-items: stretch; }
    .nc-head-actions { width: 100%; }
    .nc-head-actions .nc-btn { width: 100%; }

    .nc-toolbar {
        flex-direction: column;
        align-items: stretch;
        gap: 0;
        padding-right: 0;
    }

    .nc-search {
        width: auto;
        max-width: none;
        margin: 0 0.85rem 0.75rem;
    }

    .nc-list { padding: 0.25rem 0.75rem 0.75rem; }
    .nc-group-body { gap: 0.5rem; }

    .nc-card { padding: 0.9rem 0.95rem; gap: 0.75rem; }

    .nc-avatar { width: 46px; height: 46px; font-size: 0.85rem; }
    .nc-tile   { width: 40px; height: 40px; font-size: 1rem; }

    .nc-body-head {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.15rem;
    }

    .nc-link--danger { margin-left: 0; }
}

@media (max-width: 480px) {
    .nc-list { padding: 0.25rem 0.5rem 0.5rem; }
    .nc-card { padding: 0.8rem; gap: 0.65rem; }

    .nc-avatar { width: 42px; height: 42px; font-size: 0.8rem; }
    .nc-tile   { width: 38px; height: 38px; font-size: 0.95rem; border-radius: 9px; }

    .nc-card-title   { font-size: 0.86rem; }
    .nc-card-message { font-size: 0.78rem; }
    .nc-time         { font-size: 0.7rem; }

    .nc-body-foot { width: 100%; }
    .nc-btn--sm { flex: 1; justify-content: center; }
}

@media (prefers-reduced-motion: reduce) {
    .nc *, .nc *::before, .nc *::after {
        transition-duration: 0.01ms !important;
        animation-duration: 0.01ms !important;
    }
}
</style>


<script>
(function () {
    'use strict';

    var tabs   = Array.prototype.slice.call(document.querySelectorAll('.nc-tab'));
    var chips  = Array.prototype.slice.call(document.querySelectorAll('.nc-chip'));
    var groups = Array.prototype.slice.call(document.querySelectorAll('.nc-group'));
    var cards  = Array.prototype.slice.call(document.querySelectorAll('.nc-card'));
    var empty  = document.getElementById('ncEmpty');
    var reset  = document.getElementById('ncClearFilter');
    var search = document.getElementById('ncSearch');
    var clearB = document.getElementById('ncSearchClear');

    var state = { read: 'all', type: 'all', term: '' };

    /* Cache each message node's original text so highlighting can be undone. */
    cards.forEach(function (card) {
        var msg = card.querySelector('.nc-card-message');
        if (msg) { msg.dataset.plain = msg.textContent; }
    });

    function escapeRe(s) {
        return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function highlight(card, term) {
        var msg = card.querySelector('.nc-card-message');
        if (!msg || !msg.dataset.plain) { return; }

        var plain = msg.dataset.plain;

        if (!term) {
            msg.textContent = plain;
            return;
        }

        /* Build the highlighted output as text nodes + <mark>, never
           by assigning innerHTML, so message content cannot inject markup. */
        msg.textContent = '';
        var re = new RegExp(escapeRe(term), 'ig');
        var last = 0;
        var m;

        while ((m = re.exec(plain)) !== null) {
            if (m.index > last) {
                msg.appendChild(document.createTextNode(plain.slice(last, m.index)));
            }
            var mark = document.createElement('mark');
            mark.textContent = m[0];
            msg.appendChild(mark);
            last = m.index + m[0].length;
            if (m[0].length === 0) { re.lastIndex++; }
        }

        if (last < plain.length) {
            msg.appendChild(document.createTextNode(plain.slice(last)));
        }
    }

    function apply() {
        var visible = 0;
        var counts  = { all: 0, unread: 0, read: 0 };

        cards.forEach(function (card) {
            var okRead = state.read === 'all' || card.dataset.readStatus === state.read;
            var okType = state.type === 'all' || card.dataset.type === state.type;
            var okTerm = state.term === '' ||
                         (card.dataset.search || '').indexOf(state.term) !== -1;

            var show = okRead && okType && okTerm;
            card.classList.toggle('is-hidden', !show);

            /* Tab counts reflect the type + search filters, so the numbers
               stay honest while a chip or search is active. */
            if (okType && okTerm) {
                counts.all++;
                counts[card.dataset.readStatus]++;
            }

            if (show) {
                visible++;
                highlight(card, state.term);
            } else {
                highlight(card, '');
            }
        });

        /* Hide a date heading when nothing under it survived the filter. */
        groups.forEach(function (group) {
            var any = group.querySelector('.nc-card:not(.is-hidden)');
            group.classList.toggle('is-hidden', !any);
        });

        tabs.forEach(function (tab) {
            var el = tab.querySelector('[data-count-for]');
            if (el) { el.textContent = counts[el.dataset.countFor] || 0; }
        });

        if (empty) { empty.hidden = visible > 0; }
        if (clearB) { clearB.hidden = state.term === ''; }
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) {
                var on = t === tab;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            state.read = tab.dataset.filter || 'all';
            apply();
        });
    });

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            chips.forEach(function (c) {
                var on = c === chip;
                c.classList.toggle('is-active', on);
                c.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            state.type = chip.dataset.type || 'all';
            apply();
        });
    });

    if (search) {
        var timer = null;
        search.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                state.term = search.value.trim().toLowerCase();
                apply();
            }, 120);
        });

        /* Escape clears the box. */
        search.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && search.value !== '') {
                e.preventDefault();
                search.value = '';
                state.term = '';
                apply();
            }
        });
    }

    if (clearB) {
        clearB.addEventListener('click', function () {
            search.value = '';
            state.term = '';
            search.focus();
            apply();
        });
    }

    if (reset) {
        reset.addEventListener('click', function () {
            state = { read: 'all', type: 'all', term: '' };
            if (search) { search.value = ''; }

            tabs.forEach(function (t) {
                var on = t.dataset.filter === 'all';
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            chips.forEach(function (c) {
                var on = c.dataset.type === 'all';
                c.classList.toggle('is-active', on);
                c.setAttribute('aria-pressed', on ? 'true' : 'false');
            });

            apply();
        });
    }

    /* "/" focuses the search box, the way most list UIs behave. */
    document.addEventListener('keydown', function (e) {
        if (e.key !== '/' || !search) { return; }
        var tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || e.target.isContentEditable) { return; }
        e.preventDefault();
        search.focus();
    });

    apply();
})();
</script>

<?= $this->endSection() ?>