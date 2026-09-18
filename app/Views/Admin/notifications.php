<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Notifications<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<?php
$notifBase = $notif_base ?? 'admin/notifications';

/*
 * Avatar PNGs live at:
 *   public/assets/images/man-avatar.png
 *   public/assets/images/woman-avatar.png
 */
$maleAvatar   = 'man-avatar.png';
$femaleAvatar = 'woman-avatar.png';

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

<div class="nc">

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

                    $cardIndex++;
                ?>
                    <article class="nc-card<?= $isUnread ? ' is-unread' : '' ?><?= $showAvatar ? ' is-patient' : '' ?>"
                             data-read-status="<?= $isUnread ? 'unread' : 'read' ?>"
                             data-type="<?= esc($type, 'attr') ?>"
                             data-search="<?= esc($haystack, 'attr') ?>"
                             style="--nc-i: <?= (int) min($cardIndex, 12) ?>">

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

<?= $this->include('partials/notificationStyles') ?>
<?= $this->include('partials/notificationScript') ?>

<?= $this->endSection() ?>