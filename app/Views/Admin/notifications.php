<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Notifications<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<style>
/* =========================================================
   Lab and X-Ray PNG icons
   ========================================================= */

.nc .nc-tile {
    overflow: hidden;
}

.nc .nc-tile--png {
    background: transparent;
    border-color: transparent;
    padding: 0;
}

.nc .nc-tile-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

/* =========================================================
   Selection mode
   Cards are unchanged until the admin clicks Delete multiple.
   Selection mode adds the checkbox rail and reveals a top
   toolbar with the count and the destructive action.
   ========================================================= */

.nc-check {
    position: absolute;
    top: 14px;
    left: 8px;
    z-index: 2;
    display: none;
}

.nc-list.is-selecting .nc-card { position: relative; }
.nc-list.is-selecting .nc-card { padding-left: 26px; }
.nc-list.is-selecting .nc-check { display: block; }

.nc-check input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #1976d2;
    cursor: pointer;
    display: block;
}

.nc-list.is-selecting .nc-card.is-selected {
    border-color: #bcd6f3;
    box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.12);
}

.nc-list.is-selecting .nc-card { cursor: pointer; }

/* =========================================================
   Selection toolbar - replaces the head action while selecting
   ========================================================= */

.nc-selectbar {
    display: none;
    align-items: center;
    gap: 0.5rem;
}

.nc.is-selecting .nc-head-actions .nc-selectbar { display: inline-flex; }
.nc.is-selecting .nc-head-actions .nc-head-default { display: none; }

.nc-selectbar-count {
    font-size: 0.8125rem;
    color: #10366f;
    font-weight: 600;
    margin-right: 0.25rem;
}

.nc-btn--danger,
.nc-btn--danger:hover { color: #ffffff; }
.nc-btn--danger { background: #dc2626; border-color: #dc2626; }
.nc-btn--danger:hover { background: #b91c1c; border-color: #b91c1c; }
.nc-btn:disabled { opacity: 0.55; cursor: not-allowed; }

/* =========================================================
   Bulk delete review modal
   ========================================================= */

.bulk-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.42);
    z-index: 1080;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
}

.bulk-backdrop.is-open { display: flex; }

.bulk-modal {
    width: 100%;
    max-width: 520px;
    max-height: min(85vh, 720px);
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.32);
    overflow: hidden;
    animation: bulkIn 0.18s ease;
}

@keyframes bulkIn {
    from { opacity: 0; transform: translateY(6px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0)   scale(1);    }
}

.bulk-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.05rem 1.25rem;
    border-bottom: 1px solid #eef2f7;
}

.bulk-head-text { min-width: 0; }

.bulk-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 650;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.bulk-title i { color: #dc2626; }

.bulk-sub {
    margin: 0.15rem 0 0;
    font-size: 0.8rem;
    color: #64748b;
}

.bulk-close {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 30px;
    height: 30px;
    font-size: 0.8rem;
    color: #64748b;
    background: transparent;
    border: 0;
    border-radius: 6px;
    cursor: pointer;
}

.bulk-close:hover { background: #eef2f7; color: #0f172a; }

.bulk-body {
    padding: 0.75rem 1rem 1rem;
    overflow-y: auto;
}

.bulk-body-note {
    margin: 0 0 0.75rem;
    font-size: 0.78rem;
    color: #64748b;
}

.bulk-list {
    list-style: none;
    margin: 0;
    padding: 0;
    border: 1px solid #eef2f7;
    border-radius: 8px;
    overflow: hidden;
}

.bulk-item {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    padding: 0.65rem 0.85rem;
    border-bottom: 1px solid #f1f5f9;
    background: #ffffff;
    cursor: pointer;
}

.bulk-item:last-child { border-bottom: 0; }
.bulk-item:hover { background: #f8fafc; }

.bulk-item input[type="checkbox"] {
    width: 16px;
    height: 16px;
    margin-top: 0.15rem;
    accent-color: #1976d2;
    cursor: pointer;
    flex-shrink: 0;
}

.bulk-item-body { min-width: 0; }

.bulk-item-title {
    margin: 0;
    font-size: 0.83rem;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.35;
    overflow-wrap: anywhere;
}

.bulk-item-msg {
    margin: 0.15rem 0 0;
    font-size: 0.78rem;
    color: #64748b;
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.bulk-item-meta {
    display: block;
    margin-top: 0.2rem;
    font-size: 0.72rem;
    color: #94a3b8;
}

.bulk-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding: 0.85rem 1.25rem;
    background: #f8fafc;
    border-top: 1px solid #eef2f7;
}

.bulk-foot-note {
    font-size: 0.78rem;
    color: #64748b;
}

.bulk-foot-note strong { color: #0f172a; }

.bulk-foot-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
}

.bulk-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    height: 36px;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    border-radius: 7px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #334155;
    cursor: pointer;
    white-space: nowrap;
    text-decoration: none;
}

.bulk-btn:hover { background: #f8fafc; border-color: #cbd5e1; color: #0f172a; }

.bulk-btn--danger,
.bulk-btn--danger:hover { color: #ffffff; }
.bulk-btn--danger { background: #dc2626; border-color: #dc2626; }
.bulk-btn--danger:hover { background: #b91c1c; border-color: #b91c1c; }
.bulk-btn:disabled { opacity: 0.55; cursor: not-allowed; }
</style>

<?php
$notifBase = $notif_base ?? 'admin/notifications';

/*
 * Avatar PNGs live at:
 *   public/assets/images/man-avatar.png
 *   public/assets/images/woman-avatar.png
 *
 * Category PNGs for lab and x-ray live at:
 *   public/assets/images/lab-icon2.png
 *   public/assets/images/bones.png
 */
$maleAvatar   = 'man-avatar.png';
$femaleAvatar = 'woman-avatar.png';
$labIconPng   = 'microscope-icon.png';
$xrayIconPng  = 'bones.png';

$initialsOf = static function ($name) {
    $parts = preg_split('/\s+/', trim((string) $name));
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    $out   = mb_strtoupper($first . $last);
    return $out !== '' ? $out : '?';
};

$patientNameFrom = static function ($title) {
    $name = preg_replace(
        '/^(Pending Appointment|New Appointment Request|New Appointment|Appointment|New Lab Request|New X-Ray Request|X-Ray Examination|Laboratory Request):\s*/i',
        '',
        (string) $title
    );
    return trim($name);
};

$bucketOf = static function ($ts) {
    if (!$ts) return 'Earlier';
    if ($ts >= strtotime('today'))     return 'Today';
    if ($ts >= strtotime('yesterday')) return 'Yesterday';
    if ($ts >= strtotime('-7 days'))   return 'Earlier this week';
    if ($ts >= strtotime('-30 days'))  return 'Earlier this month';
    return 'Older';
};

$relativeTime = static function ($ts) {
    if (!$ts) return '';
    $diff = time() - $ts;
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   { $n = (int) floor($diff / 60);    return $n . ($n === 1 ? ' min ago'  : ' mins ago'); }
    if ($diff < 86400)  { $n = (int) floor($diff / 3600);  return $n . ($n === 1 ? ' hour ago' : ' hours ago'); }
    if ($diff < 604800) { $n = (int) floor($diff / 86400); return $n . ($n === 1 ? ' day ago'  : ' days ago'); }
    return date('M j', $ts);
};

$typeLabels = [
    'appointment' => 'Appointments',
    'xray'        => 'X-Ray',
    'lab'         => 'Laboratory',
    'billing'     => 'Billing',
    'payment'     => 'Payments',
    'system'      => 'System',
];

$typeCounts = [];
foreach (($notifications ?? []) as $n) {
    $t = (string) ($n['type'] ?? 'system');
    $typeCounts[$t] = ($typeCounts[$t] ?? 0) + 1;
}
arsort($typeCounts);
?>

<div class="nc" id="ncRoot">

    <header class="nc-head">
        <div class="nc-head-text">
            <h2 class="nc-title">Notifications</h2>
            <p class="nc-lede">
               
            </p>
        </div>

        <div class="nc-head-actions">

            <!-- Default actions - hidden when selection mode is on -->
            <div class="nc-head-default" style="display: inline-flex; gap: 0.5rem;">
                <?php if ($unread_count > 0): ?>
                    <a href="<?= base_url($notifBase . '/mark-all-read') ?>" class="nc-btn">
                        <i class="bi bi-check2-all" aria-hidden="true"></i>
                        <span>Mark all as read</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($notifications)): ?>
                    <button type="button" class="nc-btn" id="ncStartSelect">
                        <i class="bi bi-check2-square" aria-hidden="true"></i>
                        <span>Delete multiple</span>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Selection actions - shown only while selecting -->
            <div class="nc-selectbar" id="ncSelectBar">
                <span class="nc-selectbar-count" id="ncSelectCount">0 selected</span>
                <button type="button" class="nc-btn" id="ncCancelSelect">Cancel</button>
                <button type="button" class="nc-btn nc-btn--danger" id="ncDeleteSelected" disabled>
                    <i class="bi bi-trash3" aria-hidden="true"></i>
                    <span>Delete selected</span>
                </button>
            </div>

        </div>
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
                        ?>
                        <section class="nc-group" data-group="<?= esc($bucket, 'attr') ?>">
                            <h3 class="nc-group-head"><span><?= esc($bucket) ?></span></h3>
                            <div class="nc-group-body">
                    <?php endif;

                    $gender = strtolower((string) ($notif['reference_gender'] ?? ''));
                    if (!in_array($gender, ['male', 'female'], true)) {
                        $gender = null;
                    }

                    $icon  = (string) ($notif['icon'] ?? 'bi-bell-fill');
                    $color = (string) ($notif['color'] ?? $type);

                    $displayTitle  = (string) ($notif['title'] ?? '');
                    $message       = (string) ($notif['message'] ?? '');
                    $nameForAvatar = $patientNameFrom($displayTitle);

                    $showAvatar = ($gender !== null && $type === 'appointment');

                    $haystack = mb_strtolower($displayTitle . ' ' . $message);

                    $notifId = (int) ($notif['id'] ?? 0);

                    $cardIndex++;
                ?>
                    <article class="nc-card<?= $isUnread ? ' is-unread' : '' ?><?= $showAvatar ? ' is-patient' : '' ?>"
                             data-read-status="<?= $isUnread ? 'unread' : 'read' ?>"
                             data-type="<?= esc($type, 'attr') ?>"
                             data-search="<?= esc($haystack, 'attr') ?>"
                             data-id="<?= esc((string) $notifId, 'attr') ?>"
                             data-title="<?= esc($displayTitle, 'attr') ?>"
                             data-message="<?= esc($message, 'attr') ?>"
                             data-time="<?= esc(date('M j, Y · g:i A', $createdTs ?: time()), 'attr') ?>"
                             style="--nc-i: <?= (int) min($cardIndex, 12) ?>">

                        <label class="nc-check" title="Select this notification">
                            <input type="checkbox"
                                   class="nc-check-input"
                                   value="<?= esc((string) $notifId, 'attr') ?>"
                                   aria-label="Select notification: <?= esc($displayTitle, 'attr') ?>">
                        </label>

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

                            <?php elseif ($type === 'lab'): ?>
                                <span class="nc-tile nc-tile--png" aria-hidden="true">
                                    <img src="<?= esc(base_url('assets/images/' . $labIconPng), 'attr') ?>"
                                         alt=""
                                         class="nc-tile-img"
                                         loading="lazy"
                                         decoding="async">
                                </span>

                            <?php elseif ($type === 'xray'): ?>
                                <span class="nc-tile nc-tile--png" aria-hidden="true">
                                    <img src="<?= esc(base_url('assets/images/' . $xrayIconPng), 'attr') ?>"
                                         alt=""
                                         class="nc-tile-img"
                                         loading="lazy"
                                         decoding="async">
                                </span>

                            <?php else: ?>
                                <span class="nc-tile nc-tile--<?= esc($color, 'attr') ?>" aria-hidden="true">
                                    <i class="bi <?= esc($icon) ?>"></i>
                                </span>
                            <?php endif; ?>
                        </div>

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

<!-- =========================================================
     BULK DELETE REVIEW MODAL
     ========================================================= -->
<div class="bulk-backdrop" id="bulkBackdrop" hidden>
    <div class="bulk-modal" role="dialog" aria-modal="true" aria-labelledby="bulkTitle">

        <header class="bulk-head">
            <div class="bulk-head-text">
                <h3 class="bulk-title" id="bulkTitle">
                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                    Review before deleting
                </h3>
                <p class="bulk-sub" id="bulkSub">Uncheck anything you want to keep.</p>
            </div>
            <button type="button" class="bulk-close" id="bulkClose" aria-label="Close">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </header>

        <div class="bulk-body">
            <p class="bulk-body-note">
                The following notifications will be permanently removed. Uncheck any you want to keep.
            </p>
            <ul class="bulk-list" id="bulkList"></ul>
        </div>

        <footer class="bulk-foot">
            <span class="bulk-foot-note">
                <strong id="bulkRemaining">0</strong> of <span id="bulkTotal">0</span> will be deleted.
            </span>
            <div class="bulk-foot-actions">
                <button type="button" class="bulk-btn" id="bulkCancel">Cancel</button>
                <button type="button" class="bulk-btn bulk-btn--danger" id="bulkConfirm" disabled>
                    <i class="bi bi-trash3" aria-hidden="true"></i>
                    Delete
                </button>
            </div>
        </footer>

    </div>
</div>

<!-- The batch delete form. Submitted by the modal's Confirm button. -->
<form id="bulkForm"
      action="<?= base_url($notifBase . '/delete-batch') ?>"
      method="POST"
      hidden>
    <?= csrf_field() ?>
    <div id="bulkFormInputs"></div>
</form>

<?= $this->include('partials/notificationStyles') ?>
<?= $this->include('partials/notificationScript') ?>

<script>
(function () {
    'use strict';

    var root   = document.getElementById('ncRoot');
    var list   = document.getElementById('ncList');

    if (!root || !list) { return; }

    var allCards = Array.prototype.slice.call(list.querySelectorAll('.nc-card'));

    /* ============================================
       SELECTION MODE
       ============================================ */

    var startBtn   = document.getElementById('ncStartSelect');
    var cancelBtn  = document.getElementById('ncCancelSelect');
    var deleteBtn  = document.getElementById('ncDeleteSelected');
    var countLabel = document.getElementById('ncSelectCount');

    function isSelecting() {
        return root.classList.contains('is-selecting');
    }

    function enterSelectMode() {
        root.classList.add('is-selecting');
        list.classList.add('is-selecting');
        refreshCount();
    }

    function exitSelectMode() {
        root.classList.remove('is-selecting');
        list.classList.remove('is-selecting');
        allCards.forEach(function (card) {
            card.classList.remove('is-selected');
            var input = card.querySelector('.nc-check-input');
            if (input) { input.checked = false; }
        });
        refreshCount();
    }

    function selectedCards() {
        return allCards.filter(function (c) { return c.classList.contains('is-selected'); });
    }

    function refreshCount() {
        var n = selectedCards().length;
        countLabel.textContent = n + ' selected';
        deleteBtn.disabled = n === 0;
    }

    if (startBtn)  { startBtn.addEventListener('click', enterSelectMode); }
    if (cancelBtn) { cancelBtn.addEventListener('click', exitSelectMode); }

    /* Clicking a card while in selection mode toggles its checkbox.
       Clicking a real link or button inside the card is left alone. */
    list.addEventListener('click', function (e) {
        if (!isSelecting()) { return; }
        if (e.target.closest('a, button, input, .nc-body-foot')) { return; }

        var card = e.target.closest('.nc-card');
        if (!card) { return; }

        var input = card.querySelector('.nc-check-input');
        if (!input) { return; }

        input.checked = !input.checked;
        card.classList.toggle('is-selected', input.checked);
        refreshCount();
    });

    /* Explicit change on the checkbox itself */
    list.addEventListener('change', function (e) {
        var input = e.target.closest('.nc-check-input');
        if (!input) { return; }
        var card = input.closest('.nc-card');
        if (!card) { return; }
        card.classList.toggle('is-selected', input.checked);
        refreshCount();
    });

    /* ============================================
       BULK DELETE MODAL
       ============================================ */

    var backdrop    = document.getElementById('bulkBackdrop');
    var bulkListEl  = document.getElementById('bulkList');
    var bulkConfirm = document.getElementById('bulkConfirm');
    var bulkRemain  = document.getElementById('bulkRemaining');
    var bulkTotal   = document.getElementById('bulkTotal');
    var bulkForm    = document.getElementById('bulkForm');
    var bulkInputs  = document.getElementById('bulkFormInputs');

    function openBulk(ids) {
        bulkListEl.innerHTML = '';

        ids.forEach(function (id) {
            var card = allCards.find(function (c) { return c.dataset.id === String(id); });
            if (!card) { return; }

            var li = document.createElement('li');
            li.className = 'bulk-item';
            li.innerHTML =
                '<input type="checkbox" class="bulk-item-check" value="' + card.dataset.id + '" checked>' +
                '<div class="bulk-item-body">' +
                    '<p class="bulk-item-title"></p>' +
                    '<p class="bulk-item-msg"></p>' +
                    '<span class="bulk-item-meta"></span>' +
                '</div>';

            li.querySelector('.bulk-item-title').textContent = card.dataset.title || '';
            li.querySelector('.bulk-item-msg').textContent   = card.dataset.message || '';
            li.querySelector('.bulk-item-meta').textContent  = card.dataset.time || '';

            bulkListEl.appendChild(li);
        });

        bulkTotal.textContent = String(ids.length);
        refreshBulkFooter();

        backdrop.hidden = false;
        backdrop.classList.add('is-open');
        document.body.style.overflow = 'hidden';

        setTimeout(function () { bulkConfirm.focus(); }, 40);
    }

    function closeBulk() {
        backdrop.classList.remove('is-open');
        backdrop.hidden = true;
        document.body.style.overflow = '';
        bulkListEl.innerHTML = '';
    }

    function currentCheckedIds() {
        return Array.prototype.slice
            .call(bulkListEl.querySelectorAll('.bulk-item-check:checked'))
            .map(function (c) { return c.value; });
    }

    function refreshBulkFooter() {
        var n = currentCheckedIds().length;
        bulkRemain.textContent = String(n);
        bulkConfirm.disabled = n === 0;
        bulkConfirm.innerHTML = n === 0
            ? '<i class="bi bi-trash3" aria-hidden="true"></i> Delete'
            : '<i class="bi bi-trash3" aria-hidden="true"></i> Delete ' + n;
    }

    bulkListEl.addEventListener('change', function (e) {
        if (!e.target.closest('.bulk-item-check')) { return; }
        refreshBulkFooter();
    });

    /* ============================================
       OPEN THE MODAL
       ============================================ */

    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            var ids = selectedCards().map(function (c) { return c.dataset.id; });
            if (ids.length === 0) { return; }
            openBulk(ids);
        });
    }

    var closeEl  = document.getElementById('bulkClose');
    var cancelEl = document.getElementById('bulkCancel');

    if (closeEl)  { closeEl.addEventListener('click', closeBulk); }
    if (cancelEl) { cancelEl.addEventListener('click', closeBulk); }

    if (backdrop) {
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) { closeBulk(); }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && backdrop && backdrop.classList.contains('is-open')) {
            closeBulk();
        }
    });

    /* ============================================
       SUBMIT
       ============================================ */

    if (bulkConfirm) {
        bulkConfirm.addEventListener('click', function () {
            var ids = currentCheckedIds();
            if (ids.length === 0) { return; }

            bulkInputs.innerHTML = '';
            ids.forEach(function (id) {
                var hidden = document.createElement('input');
                hidden.type  = 'hidden';
                hidden.name  = 'ids[]';
                hidden.value = id;
                bulkInputs.appendChild(hidden);
            });

            bulkConfirm.disabled = true;
            bulkConfirm.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Deleting…';

            bulkForm.submit();
        });
    }

    /* ============================================
       START
       ============================================ */

    refreshCount();
})();
</script>

<?= $this->endSection() ?>