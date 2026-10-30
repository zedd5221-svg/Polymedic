<?= $this->extend('Layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Diagnostic Requests<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<?php
/* ------------------------------------------------------------------
   View-local helpers. No controller or database logic is changed.

   The row shape produced by Admin::buildDiagnosticRow() is identical
   to the one the receptionist page uses, so this view can be a
   direct copy. The only differences are the role prefix in the
   URLs — admin/ instead of receptionist/.
   ------------------------------------------------------------------ */
$priceBook = [];

foreach ([
    'Laboratory' => $labServices ?? [],
    'X-Ray'      => $xrayServices ?? [],
] as $category => $list) {
    foreach ($list as $svc) {
        $key = strtolower(trim((string) ($svc['service_name'] ?? '')));
        if ($key !== '') {
            $priceBook[$key] = [
                'charge'   => (float) ($svc['charge'] ?? 0),
                'category' => $category,
            ];
        }
    }
}

$requestTotal = static function (array $services) use ($priceBook): array {
    $total   = 0.0;
    $matched = 0;
    foreach ($services as $name) {
        $key = strtolower(trim((string) $name));
        if ($key !== '' && isset($priceBook[$key])) {
            $total += $priceBook[$key]['charge'];
            $matched++;
        }
    }
    return ['total' => $total, 'matched' => $matched];
};

$statusMeta = [
    'pending'     => ['label' => 'Pending',     'tone' => 'pending'],
    'queued'      => ['label' => 'Waiting',     'tone' => 'queued'],
    'in_progress' => ['label' => 'In progress', 'tone' => 'progress'],
    'draft'       => ['label' => 'Draft',       'tone' => 'draft'],
    'completed'   => ['label' => 'Completed',   'tone' => 'completed'],
    'released'    => ['label' => 'Released',    'tone' => 'released'],
    'cancelled'   => ['label' => 'Cancelled',   'tone' => 'cancelled'],
];

$requests = is_array($requests ?? null) ? $requests : [];

$validationErrors = session()->getFlashdata('validation_errors');
$exportRows       = [];

$maleAvatar   = 'man-avatar.png';
$femaleAvatar = 'woman-avatar.png';

$initialsOf = static function ($name) {
    $parts = preg_split('/\s+/', trim((string) $name));
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
};

$filterTabs = [
    'all'         => ['label' => 'All',         'count' => $counts['total']      ?? count($requests)],
    'pending'     => ['label' => 'Pending',     'count' => $counts['pending']    ?? 0],
    'queued'      => ['label' => 'Waiting',     'count' => $counts['queued']     ?? 0],
    'in_progress' => ['label' => 'In progress', 'count' => $counts['in_progress'] ?? $counts['processing'] ?? 0],
    'completed'   => ['label' => 'Completed',   'count' => $counts['completed']  ?? 0],
    'released'    => ['label' => 'Released',    'count' => $counts['released']   ?? 0],
    'cancelled'   => ['label' => 'Cancelled',   'count' => $counts['cancelled']  ?? 0],
];
?>

<div class="dx">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="dx-alert dx-alert--success alert alert-dismissible fade show" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <span><?= esc(session()->getFlashdata('success')) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error') && empty($validationErrors)): ?>
        <div class="dx-alert dx-alert--error alert alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
        </div>
    <?php endif; ?>


    <!-- PAGE HEADER -->
    <header class="dx-head">
        <div>
            <h2 class="dx-title">Diagnostic requests</h2>
        </div>

        <div class="dx-head-actions">
            <button type="button" class="dx-btn" id="dxExport">
                <i class="bi bi-download" aria-hidden="true"></i>
                Export CSV
            </button>
            <button type="button" class="dx-btn dx-btn--primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createRequestModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                New request
            </button>
        </div>
    </header>


    <!-- WORKLIST -->
    <section class="dx-panel" aria-label="Diagnostic request worklist">

        <div class="dx-tabs" role="group" aria-label="Filter by status">
            <?php foreach ($filterTabs as $key => $tab): ?>
                <button type="button"
                        class="dx-tab<?= $key === 'all' ? ' is-active' : '' ?>"
                        data-status="<?= esc($key, 'attr') ?>"
                        aria-pressed="<?= $key === 'all' ? 'true' : 'false' ?>">
                    <?= esc($tab['label']) ?>
                    <span class="dx-tab-count"><?= (int) $tab['count'] ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="dx-filters">
            <div class="dx-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label class="visually-hidden" for="searchRequests">Search requests</label>
                <input type="search"
                       class="dx-input"
                       id="searchRequests"
                       placeholder="Search patient, reference, physician or service"
                       autocomplete="off">
                <kbd class="dx-kbd" aria-hidden="true">/</kbd>
            </div>

            <label class="visually-hidden" for="dxTypeFilter">Request type</label>
            <select class="dx-select" id="dxTypeFilter">
                <option value="all">All types</option>
                <option value="lab">Laboratory</option>
                <option value="xray">X-Ray</option>
            </select>
        </div>

        <div class="dx-grid-wrap" id="dxTableWrap"<?= empty($requests) ? ' hidden' : '' ?>>
            <div class="dx-cards" id="dxRows">
                <?php foreach ($requests as $request):

                    $id        = (string) ($request['id'] ?? '');
                    $type      = (string) ($request['type'] ?? '');
                    $key       = $type . '-' . $id;
                    $reference = (string) ($request['reference'] ?? '');
                    $patient   = (string) ($request['patient_name'] ?? 'Unknown patient');
                    $kind      = (string) ($request['request_type'] ?? '');
                    $source    = trim((string) ($request['source'] ?? ''));
                    $status    = (string) ($request['status'] ?? 'pending');
                    $meta      = $statusMeta[$status] ?? $statusMeta['pending'];
                    $monitor   = $request['monitor'] ?? ['tags' => [$status], 'badges' => [['key' => $status] + $meta], 'short' => '', 'note' => ''];
                    $isStat    = ($request['priority'] ?? '') === 'stat';
                    $doctor    = trim((string) ($request['doctor_name'] ?? ''));
                    $phone     = trim((string) ($request['phone'] ?? ''));
                    $email     = trim((string) ($request['email'] ?? ''));
                    $startLock = trim((string) ($request['start_lock'] ?? ''));

                    /* Normalise the source for a badge. The controller passes
                       values such as "Walk-in", "Online", or an empty string. */
                    $sourceKey = strtolower($source);
                    if (strpos($sourceKey, 'walk') !== false) {
                        $sourceKey = 'walkin';
                    } elseif (strpos($sourceKey, 'online') !== false) {
                        $sourceKey = 'online';
                    } else {
                        $sourceKey = '';
                    }

                    $age     = $request['patient_age'] ?? '';
                    $ageText = is_numeric($age) ? ((int) $age) . ' y' : '';
                    $gender  = trim((string) ($request['patient_gender'] ?? ''));
                    $gender  = strtoupper($gender) === 'N/A' ? '' : $gender;
                    $demo    = implode(' · ', array_filter([$ageText, $gender]));

                    $genderRaw  = strtolower($gender);
                    $isMale     = $genderRaw === 'male'   || $genderRaw === 'm';
                    $isFemale   = $genderRaw === 'female' || $genderRaw === 'f';
                    $avatarKind = $isMale ? 'male' : ($isFemale ? 'female' : 'neutral');

                    $services = is_array($request['services'] ?? null) ? $request['services'] : [];
                    $services = array_values(array_filter(array_map('trim', array_map('strval', $services)), 'strlen'));
                    $shown    = array_slice($services, 0, 3);
                    $extra    = count($services) - count($shown);

                    $money     = $requestTotal($services);
                    $amount    = $money['matched'] > 0 ? $money['total'] : null;

                    $createdTs = !empty($request['created_at']) ? strtotime($request['created_at']) : false;

                    $kindSlug = strtolower($kind) === 'x-ray' ? 'xray' : 'lab';

                    $exportRows[$key] = [
                        'reference' => $reference,
                        'patient'   => $patient,
                        'age'       => is_numeric($age) ? (int) $age : '',
                        'sex'       => $gender,
                        'type'      => $kind,
                        'source'    => $source,
                        'priority'  => $isStat ? 'STAT' : 'Routine',
                        'status'    => $meta['label'],
                        'services'  => implode('; ', $services),
                        'doctor'    => $doctor,
                        'phone'     => $phone,
                        'email'     => $email,
                        'requested' => $createdTs ? date('Y-m-d H:i', $createdTs) : '',
                        'amount'    => $amount !== null ? number_format($amount, 2, '.', '') : '',
                    ];
                ?>
                    <article class="dx-card dx-card--<?= esc($status, 'attr') ?><?= $isStat ? ' is-stat' : '' ?>"
                             data-dx-ctx
                             data-key="<?= esc($key, 'attr') ?>"
                             data-id="<?= esc($id, 'attr') ?>"
                             data-type="<?= esc($type, 'attr') ?>"
                             data-status="<?= esc($status, 'attr') ?>"
                             data-statuses="<?= esc(implode(' ', $monitor['tags']), 'attr') ?>"
                             data-reference="<?= esc($reference, 'attr') ?>"
                             data-patient="<?= esc($patient, 'attr') ?>"
                             data-kind="<?= esc($kind, 'attr') ?>"
                             data-amount="<?= $amount !== null ? esc(number_format($amount, 2, '.', ''), 'attr') : '' ?>"
                             data-start-lock="<?= esc($startLock, 'attr') ?>"
                             data-search="<?= esc(strtolower(implode(' ', [$patient, $reference, $doctor, $kind, $phone, $source, implode(' ', $services)])), 'attr') ?>">

                        <header class="dx-card-head">
                            <button type="button"
                                    class="dx-ref"
                                    data-action="view"
                                    aria-label="Open <?= esc($reference, 'attr') ?> for <?= esc($patient, 'attr') ?>">
                                <?= esc($reference) ?>
                            </button>

                            <div class="dx-card-flags">
                                <?php if ($sourceKey === 'walkin'): ?>
                                    <span class="dx-source-badge dx-source-badge--walkin" title="Walk-in patient">
                                        <i class="bi bi-person-walking" aria-hidden="true"></i>
                                        Walk-in
                                    </span>
                                <?php elseif ($sourceKey === 'online'): ?>
                                    <span class="dx-source-badge dx-source-badge--online" title="Online appointment">
                                        <i class="bi bi-globe2" aria-hidden="true"></i>
                                        Online
                                    </span>
                                <?php endif; ?>
                                <?php if ($isStat): ?>
                                    <span class="dx-tag-stat" title="Urgent">STAT</span>
                                <?php endif; ?>
                                <?php foreach ($monitor['badges'] as $badge): ?>
                                    <span class="dx-status dx-status--<?= esc($badge['tone'], 'attr') ?>" title="<?= esc($monitor['note'], 'attr') ?>"><?= esc($badge['label']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </header>

                        <div class="dx-card-patient">
                            <span class="dx-avatar dx-avatar--<?= esc($avatarKind, 'attr') ?>" aria-hidden="true">
                                <?php if ($isMale): ?>
                                    <img src="<?= esc(base_url('assets/images/' . $maleAvatar), 'attr') ?>"
                                         alt=""
                                         class="dx-avatar-img"
                                         loading="lazy"
                                         decoding="async">
                                <?php elseif ($isFemale): ?>
                                    <img src="<?= esc(base_url('assets/images/' . $femaleAvatar), 'attr') ?>"
                                         alt=""
                                         class="dx-avatar-img"
                                         loading="lazy"
                                         decoding="async">
                                <?php else: ?>
                                    <?= esc($initialsOf($patient)) ?>
                                <?php endif; ?>
                            </span>

                            <div class="dx-card-patient-body">
                                <h3 class="dx-card-name"><?= esc($patient) ?></h3>

                                <p class="dx-card-demo">
                                    <?php if ($demo !== ''): ?>
                                        <?= esc($demo) ?>
                                    <?php else: ?>
                                        <span class="dx-muted">No age or sex on file</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <div class="dx-card-tags">
                            <span class="dx-kind dx-kind--<?= esc($kindSlug, 'attr') ?>">
                                <i class="bi <?= $kindSlug === 'xray' ? 'bi-radioactive' : 'bi-droplet-half' ?>" aria-hidden="true"></i>
                                <?= esc($kind) ?>
                            </span>
                            <?php if ($source !== ''): ?>
                                <span class="dx-source"><?= esc($source) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="dx-card-services">
                            <?php if ($services): ?>
                                <?php foreach ($shown as $service): ?>
                                    <span class="dx-chip"><?= esc($service) ?></span>
                                <?php endforeach; ?>
                                <?php if ($extra > 0): ?>
                                    <span class="dx-chip dx-chip--more">+<?= $extra ?> more</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="dx-muted">No services listed</span>
                            <?php endif; ?>
                        </div>

                        <dl class="dx-card-meta">
                            <div>
                                <dt>Requested</dt>
                                <dd>
                                    <?php if ($createdTs): ?>
                                        <time datetime="<?= esc(date('c', $createdTs), 'attr') ?>">
                                            <?= esc(date('M j, Y', $createdTs)) ?>
                                            <span aria-hidden="true">·</span>
                                            <?= esc(date('g:i A', $createdTs)) ?>
                                        </time>
                                    <?php else: ?>
                                        &mdash;
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div>
                                <dt>Referred by</dt>
                                <dd><?= $doctor !== '' ? esc($doctor) : '<span class="dx-muted">&mdash;</span>' ?></dd>
                            </div>
                            <?php if ($amount !== null): ?>
                                <div>
                                    <dt>Amount</dt>
                                    <dd class="dx-amount"
                                        title="<?= (int) $money['matched'] ?> priced service<?= $money['matched'] === 1 ? '' : 's' ?>">
                                        &#8369;<?= number_format($amount, 2) ?>
                                    </dd>
                                </div>
                            <?php endif; ?>
                        </dl>

                        <footer class="dx-card-foot">
                            <?php if ($status === 'pending' && $startLock !== ''): ?>
                                <span class="dx-waiting dx-waiting--sm dx-waiting--locked" title="<?= esc($startLock, 'attr') ?>">
                                    <i class="bi bi-person-lock" aria-hidden="true"></i>
                                    Locked · patient busy
                                </span>
                            <?php elseif ($status === 'pending'): ?>
                                <button type="button" class="dx-btn dx-btn--sm dx-btn--primary"
                                        data-action="start">
                                    Start
                                </button>
                            <?php elseif ($status === 'released'): ?>
                                <a class="dx-btn dx-btn--sm"
                                   href="<?= esc(base_url('admin/print-request/' . (int) $id . '/' . $type), 'attr') ?>">
                                    <i class="bi bi-printer" aria-hidden="true"></i>
                                    Print
                                </a>
                            <?php elseif ($status === 'cancelled'): ?>
                                <button type="button" class="dx-btn dx-btn--sm"
                                        data-action="status"
                                        data-status="pending">
                                    Restore
                                </button>
                            <?php elseif (in_array($status, ['in_progress', 'draft', 'completed'], true)): ?>
                                <span class="dx-waiting dx-waiting--sm dx-waiting--<?= esc($monitor['stage'] ?? $status, 'attr') ?>" title="<?= esc($monitor['note'], 'attr') ?>">
                                    <i class="bi <?= ($monitor['stage'] ?? '') === 'queued' ? 'bi-inbox' : (($monitor['stage'] ?? '') === 'completed' ? 'bi-clipboard-check' : 'bi-hourglass-split') ?>" aria-hidden="true"></i>
                                    <?= esc($monitor['short'] ?: 'With lab / x-ray') ?>
                                </span>
                            <?php endif; ?>

                            <div class="dropdown dx-card-menu">
                                <button type="button" class="dx-icon-btn"
                                        data-bs-toggle="dropdown"
                                        data-bs-popper-config='{"strategy":"fixed"}'
                                        aria-expanded="false"
                                        aria-label="More actions for <?= esc($reference, 'attr') ?>">
                                    <i class="bi bi-three-dots" aria-hidden="true"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end dx-menu">
                                    <li>
                                        <button type="button" class="dropdown-item" data-action="view">
                                            <i class="bi bi-file-text" aria-hidden="true"></i> View details
                                        </button>
                                    </li>
                                    <?php if ($status === 'released'): ?>
                                        <li>
                                            <a class="dropdown-item"
                                               href="<?= esc(base_url('admin/print-request/' . (int) $id . '/' . $type), 'attr') ?>">
                                                <i class="bi bi-printer" aria-hidden="true"></i> Print result
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (in_array($status, ['pending', 'in_progress', 'completed'], true)): ?>
                                        <li>
                                            <button type="button" class="dropdown-item" data-action="status" data-status="cancelled">
                                                <i class="bi bi-slash-circle" aria-hidden="true"></i> Cancel request
                                            </button>
                                        </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button" class="dropdown-item dx-menu-danger" data-action="delete">
                                            <i class="bi bi-trash3" aria-hidden="true"></i> Delete
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- No requests at all -->
        <div class="dx-empty" id="dxEmpty"<?= empty($requests) ? '' : ' hidden' ?>>
            <div class="dx-empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></div>
            <h3>No diagnostic requests yet</h3>
            <p>Walk-in requests created here, and approved online appointments, will appear in this list.</p>
            <button type="button" class="dx-btn dx-btn--primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createRequestModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                New request
            </button>
        </div>

        <!-- Filters match nothing -->
        <div class="dx-empty" id="dxNoMatch" hidden>
            <div class="dx-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></div>
            <h3>No matching requests</h3>
            <p>Try a different search term or clear the filters.</p>
            <button type="button" class="dx-btn" id="dxClearFilters">Clear filters</button>
        </div>

        <footer class="dx-foot" id="dxFoot"<?= empty($requests) ? ' hidden' : '' ?>>
            <span id="dxRange" aria-live="polite"></span>
            <nav class="dx-pager" id="dxPager" aria-label="Pagination"></nav>
        </footer>

    </section>

</div>


<!-- =========================================================
     CREATE REQUEST MODAL
     ========================================================= -->
<div class="modal fade dx-modal"
     id="createRequestModal"
     tabindex="-1"
     aria-labelledby="createRequestModalLabel"
     aria-hidden="true"
     <?= !empty($validationErrors) ? 'data-open-on-load="1"' : '' ?>>

    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable dx-dialog-md">
        <div class="modal-content">

            <div class="modal-header">
                <div>
                    <h2 class="dx-modal-title" id="createRequestModalLabel">New diagnostic request</h2>
                    <p class="dx-modal-sub">Walk-in patient. The request is created as pending.</p>
                </div>
                <button type="button" class="dx-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <form action="<?= base_url('admin/create-diagnostic-request') ?>"
                  method="POST"
                  id="createRequestForm">

                <?= function_exists('csrf_field') ? csrf_field() : '' ?>

                <div class="modal-body">

                    <?php if (!empty($validationErrors) && is_array($validationErrors)): ?>
                        <div class="dx-form-alert" role="alert">
                            <strong>Please fix the following:</strong>
                            <ul>
                                <?php foreach ($validationErrors as $error): ?>
                                    <li><?= esc($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- REQUEST -->
                    <section class="dx-section">
                        <h3 class="dx-section-title">Request</h3>

                        <div class="dx-form-grid">
                            <div class="dx-span-6">
                                <span class="dx-label" id="requestTypeLabel">Type</span>
                                <div class="dx-seg" role="radiogroup" aria-labelledby="requestTypeLabel">
                                    <input type="radio" class="dx-seg-input" name="request_type"
                                           id="requestTypeLab" value="lab"
                                           <?= old('request_type', 'lab') === 'lab' ? 'checked' : '' ?>>
                                    <label class="dx-seg-btn" for="requestTypeLab">Laboratory</label>

                                    <input type="radio" class="dx-seg-input" name="request_type"
                                           id="requestTypeXray" value="xray"
                                           <?= old('request_type') === 'xray' ? 'checked' : '' ?>>
                                    <label class="dx-seg-btn" for="requestTypeXray">X-Ray</label>

                                    <input type="radio" class="dx-seg-input" name="request_type"
                                           id="requestTypeBoth" value="both"
                                           <?= old('request_type') === 'both' ? 'checked' : '' ?>>
                                    <label class="dx-seg-btn" for="requestTypeBoth">Lab + X-Ray</label>
                                </div>
                                <p class="dx-help" id="typeHelp" hidden>Creates two requests — one for the lab, one for radiology — each billed and released separately.</p>
                            </div>

                            <div class="dx-span-6">
                                <span class="dx-label" id="priorityLabel">Priority</span>
                                <div class="dx-seg" role="radiogroup" aria-labelledby="priorityLabel" aria-describedby="priorityHelp">
                                    <input type="radio" class="dx-seg-input" name="priority"
                                           id="priorityRoutine" value="routine"
                                           <?= old('priority', 'routine') === 'routine' ? 'checked' : '' ?>>
                                    <label class="dx-seg-btn" for="priorityRoutine">Routine</label>

                                    <input type="radio" class="dx-seg-input is-stat" name="priority"
                                           id="priorityStat" value="stat"
                                           <?= old('priority') === 'stat' ? 'checked' : '' ?>>
                                    <label class="dx-seg-btn" for="priorityStat">STAT</label>
                                </div>
                                <p class="dx-help" id="priorityHelp">Use STAT only for urgent cases that must be processed first.</p>
                            </div>
                        </div>
                    </section>

                    <!-- PATIENT -->
                    <section class="dx-section">
                        <h3 class="dx-section-title">Patient</h3>

                        <div class="dx-form-grid">
                            <div class="dx-span-6">
                                <label class="dx-label" for="fPatientName">Full name <span class="dx-req" aria-hidden="true">*</span></label>
                                <input type="text" class="dx-input" id="fPatientName" name="patient_name"
                                       value="<?= old('patient_name') ?>"
                                       autocomplete="off" required>
                            </div>

                            <div class="dx-span-3">
                                <label class="dx-label" for="fAge">Age <span class="dx-req" aria-hidden="true">*</span></label>
                                <input type="number" class="dx-input" id="fAge" name="age"
                                       min="0" max="130" inputmode="numeric"
                                       value="<?= old('age') ?>" required>
                            </div>

                            <div class="dx-span-3">
                                <label class="dx-label" for="fGender">Sex <span class="dx-req" aria-hidden="true">*</span></label>
                                <select class="dx-select" id="fGender" name="gender" required>
                                    <option value="">Select</option>
                                    <option value="Male"<?= old('gender') === 'Male' ? ' selected' : '' ?>>Male</option>
                                    <option value="Female"<?= old('gender') === 'Female' ? ' selected' : '' ?>>Female</option>
                                </select>
                            </div>

                            <div class="dx-span-6">
                                <label class="dx-label" for="fPhone">Phone <span class="dx-opt">Optional</span></label>
                                <input type="tel" class="dx-input" id="fPhone" name="phone"
                                       value="<?= old('phone') ?>" autocomplete="off">
                            </div>

                            <div class="dx-span-6">
                                <label class="dx-label" for="fEmail">Email <span class="dx-opt">Optional</span></label>
                                <input type="email" class="dx-input" id="fEmail" name="email"
                                       value="<?= old('email') ?>" autocomplete="off">
                            </div>

                            <?php
                            // Referring physician: one field for Laboratory / X-Ray; Lab + X-Ray
                            // swaps it for one per request (med techs for lab, radiologists for x-ray).
                            $staffRoleShort = ['radiologist' => 'Radiologist', 'med_tech' => 'Med Tech'];
                            $referringStaff = $referringStaff ?? [];
                            $staffFor = static fn(string $dept): array => array_values(array_filter($referringStaff, static fn($s) => $s['dept'] === $dept));
                            $doctorFields = [
                                ['id' => 'fDoctor', 'span' => 'dx-span-12', 'name' => 'doctor_name', 'old' => 'doctor_name', 'split' => false,
                                 'label' => 'Referring physician', 'pick' => 'Clinic staff', 'staff' => $referringStaff,
                                 'placeholder' => 'Pick clinic staff below or type a name'],
                                ['id' => 'fDoctorLab', 'span' => 'dx-span-6', 'name' => 'doctor_by_type[lab]', 'old' => 'doctor_by_type.lab', 'split' => true,
                                 'label' => 'Lab request · referring', 'pick' => 'Med Techs', 'staff' => $staffFor('lab'),
                                 'placeholder' => 'Pick a med tech or type a name'],
                                ['id' => 'fDoctorXray', 'span' => 'dx-span-6', 'name' => 'doctor_by_type[xray]', 'old' => 'doctor_by_type.xray', 'split' => true,
                                 'label' => 'X-Ray request · referring', 'pick' => 'Radiologists', 'staff' => $staffFor('xray'),
                                 'placeholder' => 'Pick a radiologist or type a name'],
                            ];
                            ?>
                            <?php foreach ($doctorFields as $df): ?>
                                <div class="<?= $df['span'] ?> dx-doctor"<?= $df['split'] ? ' hidden' : '' ?>>
                                    <label class="dx-label" for="<?= $df['id'] ?>"><?= esc($df['label']) ?> <span class="dx-opt">Optional</span></label>
                                    <input type="text" class="dx-input" id="<?= $df['id'] ?>" name="<?= $df['name'] ?>"
                                           value="<?= old($df['old']) ?>" autocomplete="off"
                                           list="<?= $df['id'] ?>List" placeholder="<?= esc($df['placeholder'], 'attr') ?>"<?= $df['split'] ? ' disabled' : '' ?>>
                                    <?php if (!empty($df['staff'])): ?>
                                        <datalist id="<?= $df['id'] ?>List">
                                            <?php foreach ($df['staff'] as $staff): ?>
                                                <option value="<?= esc($staff['name'], 'attr') ?>" label="<?= esc($staff['role_label'], 'attr') ?>" data-dept="<?= esc($staff['dept'], 'attr') ?>"></option>
                                            <?php endforeach; ?>
                                        </datalist>
                                        <div class="dx-staff" role="group" aria-label="<?= esc($df['pick'], 'attr') ?>">
                                            <span class="dx-staff-label" data-default="<?= esc($df['pick'], 'attr') ?>"><?= esc($df['pick']) ?></span>
                                            <?php foreach ($df['staff'] as $staff): ?>
                                                <button type="button" class="dx-staff-chip" aria-pressed="false"
                                                        data-name="<?= esc($staff['name'], 'attr') ?>"
                                                        data-dept="<?= esc($staff['dept'], 'attr') ?>"
                                                        title="<?= esc($staff['role_label'] . ($staff['prc_license'] !== '' ? ' · PRC ' . $staff['prc_license'] : ''), 'attr') ?>">
                                                    <?= esc($staff['name']) ?>
                                                    <span class="dx-staff-role dx-staff-role--<?= esc($staff['role'], 'attr') ?>"><?= $staffRoleShort[$staff['role']] ?? esc($staff['role_label']) ?></span>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- SERVICES -->
                    <section class="dx-section">
                        <div class="dx-section-head">
                            <h3 class="dx-section-title">Services <span class="dx-req" aria-hidden="true">*</span></h3>
                            <div class="dx-section-tools">
                                <button type="button" class="dx-textbtn" id="selectVisibleServices">Select shown</button>
                                <button type="button" class="dx-textbtn" id="clearServices">Clear</button>
                            </div>
                        </div>

                        <div class="dx-search dx-search--block">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <label class="visually-hidden" for="serviceSearch">Search services</label>
                            <input type="search" class="dx-input" id="serviceSearch"
                                   placeholder="Search services" autocomplete="off">
                        </div>

                        <?php
                        $oldServices = old('services', [], false);
                        $oldServices = is_array($oldServices) ? $oldServices : [];
                        ?>

                        <div class="dx-svc-list" id="servicesContainer" role="group" aria-label="Services" aria-describedby="servicesError">
                            <?php foreach (['lab' => $labServices ?? [], 'xray' => $xrayServices ?? []] as $category => $list): ?>
                                <?php foreach ($list as $service):
                                    $name   = (string) ($service['service_name'] ?? '');
                                    $charge = (float) ($service['charge'] ?? 0);
                                    $inputId = 'service_' . $category . '_' . ($service['id'] ?? md5($name));
                                ?>
                                    <label class="dx-svc" for="<?= esc($inputId, 'attr') ?>"
                                           data-category="<?= $category ?>"
                                           data-name="<?= esc(strtolower($name), 'attr') ?>">
                                        <input type="checkbox"
                                               id="<?= esc($inputId, 'attr') ?>"
                                               name="services[<?= $category ?>][]"
                                               value="<?= esc($name, 'attr') ?>"
                                               data-charge="<?= esc((string) $charge, 'attr') ?>"
                                               <?= in_array($name, (array) ($oldServices[$category] ?? []), true) ? 'checked' : '' ?>>
                                        <span class="dx-svc-name"><?= esc($name) ?></span>
                                        <span class="dx-svc-cat dx-svc-cat--<?= $category ?>"><?= $category === 'xray' ? 'X-Ray' : 'Lab' ?></span>
                                        <span class="dx-svc-price">&#8369;<?= number_format($charge, 2) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endforeach; ?>

                            <div class="dx-svc-empty" id="servicesEmpty" hidden>No services match your search.</div>
                        </div>

                        <p class="dx-field-error" id="servicesError" hidden>Select at least one service.</p>
                    </section>

                </div>

                <div class="modal-footer">
                    <div class="dx-summary" id="selectedSummary" aria-live="polite">No services selected</div>
                    <div class="dx-footer-actions">
                        <button type="button" class="dx-btn" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="dx-btn dx-btn--primary" id="createRequestSubmit">Create request</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>


<!-- =========================================================
     REQUEST DETAILS MODAL
     ========================================================= -->
<div class="modal fade dx-modal"
     id="viewRequestModal"
     tabindex="-1"
     aria-labelledby="viewRequestModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <div class="dx-modal-heading">
                    <div class="dx-title-row">
                        <h2 class="dx-modal-title" id="viewRequestModalLabel">Request details</h2>
                        <span id="viewRequestStatus"></span>
                    </div>
                    <p class="dx-modal-sub" id="viewRequestSubtitle"></p>
                </div>
                <button type="button" class="dx-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="modal-body" id="viewRequestContent" aria-live="polite"></div>

            <div class="modal-footer">
                <button type="button" class="dx-btn" data-bs-dismiss="modal">Close</button>
                <div class="dx-footer-actions" id="viewRequestActions" data-dx-ctx></div>
            </div>

        </div>
    </div>
</div>


<!-- =========================================================
     CONFIRM MODAL
     ========================================================= -->
<div class="modal fade dx-modal"
     id="dxConfirmModal"
     tabindex="-1"
     aria-labelledby="dxConfirmTitle"
     aria-describedby="dxConfirmText"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered dx-dialog-sm">
        <div class="modal-content">
            <div class="modal-body dx-confirm">
                <h2 class="dx-modal-title" id="dxConfirmTitle"></h2>
                <p class="dx-confirm-text" id="dxConfirmText"></p>
                <p class="dx-confirm-error" id="dxConfirmError" role="alert" hidden></p>
            </div>
            <div class="modal-footer dx-footer-end">
                <button type="button" class="dx-btn" id="dxConfirmDismiss" data-bs-dismiss="modal">Go back</button>
                <button type="button" class="dx-btn dx-btn--primary" id="dxConfirmOk">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     ASSIGN MODAL (Start = assign to one med tech / radiologist)
     ========================================================= -->
<div class="modal fade dx-modal"
     id="dxAssignModal"
     tabindex="-1"
     aria-labelledby="dxAssignTitle"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable dx-dialog-sm">
        <div class="modal-content">
            <div class="modal-header">
                <div class="dx-modal-heading">
                    <h2 class="dx-modal-title" id="dxAssignTitle">Assign and start</h2>
                    <p class="dx-modal-sub" id="dxAssignSub"></p>
                </div>
                <button type="button" class="dx-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="modal-body">
                <p class="dx-assign-intro" id="dxAssignIntro"></p>
                <p class="dx-confirm-error" id="dxAssignError" role="alert" hidden></p>
                <div id="dxAssignBody" aria-live="polite"></div>
            </div>

            <div class="modal-footer dx-footer-end">
                <button type="button" class="dx-btn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="dx-btn dx-btn--primary" id="dxAssignOk" disabled>Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="dx-toast" id="dxToast" role="status" aria-live="polite" hidden></div>


<style>
/* =========================================================
   DIAGNOSTIC REQUESTS - ADMIN
   Identical to the receptionist page's stylesheet. The
   namespace .dx / .dx-modal means the two pages share the
   exact same visual language.
   ========================================================= */

.dx,
.dx-modal {
    --dx-ink:         #0f172a;
    --dx-text:        #334155;
    --dx-muted:       #64748b;
    --dx-faint:       #94a3b8;
    --dx-line:        #e2e8f0;
    --dx-line-soft:   #f1f5f9;
    --dx-surface:     #ffffff;
    --dx-subtle:      #f8fafc;
    --dx-accent:      #1976d2;
    --dx-accent-dark: #1565c0;
    --dx-accent-soft: #e8f1fb;
    --dx-danger:      #dc2626;
    --dx-danger-dark: #b91c1c;
    --dx-radius:      10px;
    --dx-radius-sm:   7px;
    --dx-ring:        0 0 0 3px rgba(25, 118, 210, 0.2);
    --dx-mono:        ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;

    color: var(--dx-text);
}

.dx *:focus-visible,
.dx-modal *:focus-visible {
    outline: 2px solid var(--dx-accent);
    outline-offset: 2px;
}

.dx-muted { color: var(--dx-faint); }

/* ---------- Alerts ---------- */

.dx-alert {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: var(--dx-radius);
    font-size: 0.8125rem;
}

.dx-alert > span { flex: 1; min-width: 0; }
.dx-alert.alert-dismissible { padding-right: 0.75rem; }
.dx-alert .btn-close { position: static; padding: 0.5rem; margin-left: auto; font-size: 0.7rem; }
.dx-alert--success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
.dx-alert--error   { background: #fef2f2; border-color: #fecaca; color: #991b1b; }

/* ---------- Page header ---------- */

.dx-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}

.dx-title {
    margin: 0 0 0.2rem;
    font-size: 1.25rem;
    font-weight: 650;
    letter-spacing: -0.015em;
    color: var(--dx-ink);
}

.dx-head-actions { display: flex; gap: 0.5rem; }

/* ---------- Buttons ---------- */

.dx-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    height: 36px;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    color: var(--dx-text);
    background: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: var(--dx-radius-sm);
    box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.dx-btn:hover { background: var(--dx-subtle); border-color: #cbd5e1; color: var(--dx-ink); }
.dx-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.dx-btn i { font-size: 0.9em; }

.dx-btn--primary,
.dx-btn--primary:hover { color: #ffffff; }
.dx-btn--primary { background: var(--dx-accent); border-color: var(--dx-accent); }
.dx-btn--primary:hover { background: var(--dx-accent-dark); border-color: var(--dx-accent-dark); }

.dx-btn--danger,
.dx-btn--danger:hover { color: #ffffff; }
.dx-btn--danger { background: var(--dx-danger); border-color: var(--dx-danger); }
.dx-btn--danger:hover { background: var(--dx-danger-dark); border-color: var(--dx-danger-dark); }

.dx-btn--sm { height: 32px; padding: 0 0.75rem; font-size: 0.78rem; flex: 1; min-width: 0; }

.dx-icon-btn {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    color: var(--dx-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--dx-radius-sm);
    cursor: pointer;
}

.dx-icon-btn:hover,
.dx-icon-btn[aria-expanded="true"] { background: var(--dx-line-soft); color: var(--dx-ink); }

.dx-waiting {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    height: 32px;
    padding: 0 0.75rem;
    font-size: 0.78rem;
    font-weight: 500;
    font-style: italic;
    color: var(--dx-muted);
    background: var(--dx-subtle);
    border: 1px solid var(--dx-line);
    border-radius: var(--dx-radius-sm);
    white-space: nowrap;
}

.dx-waiting i { font-style: normal; }
.dx-waiting--sm { height: 30px; padding: 0 0.65rem; font-size: 0.75rem; }

.dx-textbtn {
    padding: 0.25rem 0.45rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--dx-accent);
    background: none;
    border: 0;
    border-radius: 5px;
    cursor: pointer;
}

.dx-textbtn:hover { background: var(--dx-accent-soft); }

/* ---------- Inputs ---------- */

.dx-input,
.dx-select {
    width: 100%;
    height: 36px;
    padding: 0 0.75rem;
    font-size: 0.8125rem;
    color: var(--dx-ink);
    background-color: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: var(--dx-radius-sm);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.dx-input::placeholder { color: var(--dx-faint); }

.dx-input:focus,
.dx-select:focus {
    outline: none;
    border-color: var(--dx-accent);
    box-shadow: var(--dx-ring);
}

.dx-select {
    width: auto;
    padding-right: 2rem;
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m3 6 5 5 5-5'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.65rem center;
    background-size: 12px;
    cursor: pointer;
}

.dx-search { position: relative; flex: 1 1 280px; max-width: 380px; }
.dx-search > i {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.8rem;
    color: var(--dx-faint);
    pointer-events: none;
}

.dx-search .dx-input { padding-left: 2.1rem; }
.dx-search--block { max-width: none; margin-bottom: 0.6rem; }

.dx-kbd {
    position: absolute;
    right: 0.6rem;
    top: 50%;
    transform: translateY(-50%);
    padding: 0.05rem 0.35rem;
    font-family: var(--dx-mono);
    font-size: 0.7rem;
    line-height: 1.3;
    color: var(--dx-muted);
    background: var(--dx-subtle);
    border: 1px solid var(--dx-line);
    border-radius: 4px;
    box-shadow: none;
    pointer-events: none;
}

.dx-search .dx-input:focus ~ .dx-kbd,
.dx-search .dx-input:not(:placeholder-shown) ~ .dx-kbd { display: none; }

/* ---------- Worklist panel ---------- */

.dx-panel {
    background: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

.dx-tabs {
    display: flex;
    gap: 0.25rem;
    padding: 0 0.75rem;
    border-bottom: 1px solid var(--dx-line);
    overflow-x: auto;
    scrollbar-width: none;
}

.dx-tabs::-webkit-scrollbar { display: none; }

.dx-tab {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.85rem 0.6rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--dx-muted);
    background: none;
    border: 0;
    white-space: nowrap;
    cursor: pointer;
    transition: color 0.15s ease;
}

.dx-tab::after {
    content: "";
    position: absolute;
    left: 0.4rem;
    right: 0.4rem;
    bottom: -1px;
    height: 2px;
    border-radius: 2px 2px 0 0;
    background: transparent;
}

.dx-tab:hover { color: var(--dx-ink); }
.dx-tab.is-active { color: var(--dx-ink); }
.dx-tab.is-active::after { background: var(--dx-accent); }

.dx-tab-count {
    min-width: 1.4rem;
    padding: 0.05rem 0.4rem;
    font-size: 0.7rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    text-align: center;
    color: var(--dx-muted);
    background: var(--dx-line-soft);
    border-radius: 999px;
}

.dx-tab.is-active .dx-tab-count { color: var(--dx-accent); background: var(--dx-accent-soft); }

.dx-filters {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    padding: 0.75rem 1rem;
}

/* ---------- Card grid ---------- */

.dx-grid-wrap {
    border-top: 1px solid var(--dx-line);
    background: #f8fafc;
}

.dx-cards {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.1rem;
    padding: 1.15rem;
}

.dx-card {
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    padding: 1rem 1.05rem 1rem;
    background: var(--dx-surface);
    border: 1px solid #d4dbe5;
    border-radius: var(--dx-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 1px 3px rgba(15, 23, 42, 0.06);
    cursor: pointer;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.dx-card:hover {
    border-color: #b6c2d2;
    box-shadow: 0 8px 20px -8px rgba(15, 23, 42, 0.25), 0 2px 4px rgba(15, 23, 42, 0.06);
}

.dx-card.is-stat {
    background-image: linear-gradient(180deg, rgba(254, 242, 242, 0.55), transparent 45%);
}

.dx-card.is-hidden { display: none; }

.dx-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.6rem;
}

.dx-ref {
    padding: 0;
    font-family: var(--dx-mono);
    font-size: 0.76rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    color: var(--dx-ink);
    background: none;
    border: 0;
    white-space: nowrap;
    cursor: pointer;
}

.dx-ref:hover { color: var(--dx-accent); text-decoration: underline; text-underline-offset: 2px; }

.dx-card-flags {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
}

.dx-tag-stat {
    padding: 0 0.35rem;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    line-height: 1.6;
    color: var(--dx-danger-dark);
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 4px;
}

/* ---------- Source badge (Walk-in / Online) ---------- */

.dx-source-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.15rem 0.5rem;
    font-size: 0.68rem;
    font-weight: 600;
    line-height: 1.4;
    white-space: nowrap;
    border-radius: 999px;
    border: 1px solid transparent;
}

.dx-source-badge i { font-size: 0.72em; }

.dx-source-badge--walkin {
    color: #92400e;
    background: #fef3c7;
    border-color: #fde68a;
}

.dx-source-badge--online {
    color: #1e40af;
    background: #dbeafe;
    border-color: #bfdbfe;
}

.dx-card-patient {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    margin-bottom: 0.7rem;
}

.dx-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--dx-accent);
    background: var(--dx-accent-soft);
    border-radius: 50%;
    overflow: hidden;
}

.dx-avatar--male    { color: #1d4ed8; background: #eaf2fe; }
.dx-avatar--female  { color: #b32e50; background: #fce9ee; }
.dx-avatar--neutral { color: var(--dx-accent); background: var(--dx-accent-soft); }

.dx-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.dx-card-patient-body { min-width: 0; }

.dx-card-name {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 650;
    letter-spacing: -0.01em;
    line-height: 1.3;
    color: var(--dx-ink);
    overflow-wrap: anywhere;
}

.dx-card-demo {
    margin: 0.1rem 0 0;
    font-size: 0.76rem;
    color: var(--dx-muted);
}

.dx-card-tags {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-wrap: wrap;
    margin-bottom: 0.7rem;
}

.dx-kind {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.15rem 0.5rem;
    font-size: 0.72rem;
    font-weight: 600;
    border-radius: 5px;
    border: 1px solid transparent;
    white-space: nowrap;
}

.dx-kind--lab  { color: #0e7490; background: #ecfeff; border-color: #cffafe; }
.dx-kind--xray { color: #4338ca; background: #eef2ff; border-color: #e0e7ff; }

.dx-kind i { font-size: 0.72em; }

.dx-source {
    padding: 0.15rem 0.5rem;
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--dx-muted);
    background: var(--dx-subtle);
    border: 1px solid var(--dx-line);
    border-radius: 5px;
    white-space: nowrap;
}

.dx-card-services {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    margin-bottom: 0.85rem;
    min-height: 1.55rem;
}

.dx-chip {
    padding: 0.15rem 0.5rem;
    font-size: 0.72rem;
    color: var(--dx-text);
    background: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: 5px;
    white-space: nowrap;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dx-chip--more {
    color: var(--dx-muted);
    background: var(--dx-line-soft);
    font-weight: 600;
}

.dx-card-meta {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.4rem;
    margin: 0 0 0.85rem;
    padding: 0.7rem 0;
    border-top: 1px solid var(--dx-line-soft);
    border-bottom: 1px solid var(--dx-line-soft);
    font-size: 0.78rem;
}

.dx-card-meta > div {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.75rem;
    min-width: 0;
}

.dx-card-meta dt {
    flex-shrink: 0;
    color: var(--dx-muted);
    font-weight: 500;
}

.dx-card-meta dd {
    margin: 0;
    text-align: right;
    color: var(--dx-ink);
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.dx-card-meta time { font-variant-numeric: tabular-nums; }
.dx-card-meta time > span { color: var(--dx-faint); margin: 0 0.1rem; }

.dx-amount { font-variant-numeric: tabular-nums; font-weight: 600; }

.dx-card-foot {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    margin-top: auto;
}

.dx-card-menu { flex-shrink: 0; }

.dx-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
    color: var(--tone-fg);
    background: var(--tone-bg);
    border-radius: 999px;
}

.dx-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--tone-dot);
}

.dx-status--pending   { --tone-bg: #fffbeb; --tone-fg: #b45309; --tone-dot: #f59e0b; }
.dx-status--progress  { --tone-bg: #eff6ff; --tone-fg: #1d4ed8; --tone-dot: #3b82f6; }
.dx-status--completed { --tone-bg: #ecfdf5; --tone-fg: #047857; --tone-dot: #10b981; }
.dx-status--released  { --tone-bg: #f0fdfa; --tone-fg: #0f766e; --tone-dot: #14b8a6; }
.dx-status--cancelled { --tone-bg: #f1f5f9; --tone-fg: #64748b; --tone-dot: #94a3b8; }
.dx-status--queued    { --tone-bg: #f5f3ff; --tone-fg: #6d28d9; --tone-dot: #8b5cf6; }
.dx-status--draft     { --tone-bg: #f8fafc; --tone-fg: #475569; --tone-dot: #94a3b8; }
.dx-waiting--queued    { color: #6d28d9; }
.dx-waiting--completed { color: #047857; }

.dx-menu {
    min-width: 11rem;
    padding: 0.3rem;
    font-size: 0.8125rem;
    border: 1px solid var(--dx-line);
    border-radius: 9px;
    box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.2);
}

.dx-menu .dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.45rem 0.6rem;
    color: var(--dx-text);
    border-radius: 6px;
}

.dx-menu .dropdown-item i { font-size: 0.85rem; color: var(--dx-faint); }
.dx-menu .dropdown-item:hover,
.dx-menu .dropdown-item:focus { background: var(--dx-line-soft); color: var(--dx-ink); }
.dx-menu .dropdown-divider { margin: 0.3rem 0; border-color: var(--dx-line-soft); }
.dx-menu .dx-menu-danger,
.dx-menu .dx-menu-danger i { color: var(--dx-danger); }
.dx-menu .dx-menu-danger:hover { background: #fef2f2; color: var(--dx-danger-dark); }

.dx-empty { padding: 3.5rem 1rem; text-align: center; border-top: 1px solid var(--dx-line); }

.dx-empty-icon {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    margin: 0 auto 0.85rem;
    font-size: 1.2rem;
    color: var(--dx-faint);
    background: var(--dx-line-soft);
    border-radius: 10px;
}

.dx-empty h3 { margin: 0 0 0.25rem; font-size: 0.95rem; font-weight: 600; color: var(--dx-ink); }
.dx-empty p { max-width: 26rem; margin: 0 auto 1rem; font-size: 0.8125rem; color: var(--dx-muted); }

.dx-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding: 0.7rem 1rem;
    font-size: 0.78rem;
    color: var(--dx-muted);
    border-top: 1px solid var(--dx-line);
}

.dx-foot strong { font-weight: 600; color: var(--dx-ink); font-variant-numeric: tabular-nums; }

.dx-pager { display: flex; align-items: center; gap: 0.25rem; }

.dx-page {
    min-width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.45rem;
    font-size: 0.78rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--dx-text);
    background: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: var(--dx-radius-sm);
    cursor: pointer;
}

.dx-page:hover:not(:disabled) { background: var(--dx-subtle); border-color: #cbd5e1; }
.dx-page:disabled { opacity: 0.45; cursor: not-allowed; }
.dx-page[aria-current="page"] { color: var(--dx-accent); background: var(--dx-accent-soft); border-color: #bcd6f3; }
.dx-page-gap { min-width: 20px; text-align: center; color: var(--dx-faint); }

/* ---------- Modals ---------- */

.dx-modal .modal-content {
    border: 1px solid var(--dx-line);
    border-radius: 12px;
    box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.28);
    overflow: hidden;
}

.dx-modal .modal-header {
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--dx-line);
}

.dx-modal .modal-body { padding: 1.25rem; }

.dx-modal .modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.8rem 1.25rem;
    background: var(--dx-subtle);
    border-top: 1px solid var(--dx-line);
}

.dx-modal .modal-footer > * { margin: 0; }
.dx-modal .modal-footer.dx-footer-end { justify-content: flex-end; gap: 0.5rem; }

.dx-footer-actions { display: flex; align-items: center; gap: 0.5rem; margin-left: auto; }

.dx-modal-heading { min-width: 0; }
.dx-title-row { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }

.dx-modal-title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 650;
    letter-spacing: -0.01em;
    color: var(--dx-ink);
    overflow-wrap: anywhere;
}

.dx-modal-sub {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
    margin: 0.25rem 0 0;
    font-size: 0.8rem;
    color: var(--dx-muted);
}

.dx-modal-sub .dx-ref { font-family: var(--dx-mono); font-weight: 600; color: var(--dx-text); }

.dx-close {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    margin: -0.3rem -0.45rem 0 0;
    font-size: 0.85rem;
    color: var(--dx-muted);
    background: transparent;
    border: 0;
    border-radius: var(--dx-radius-sm);
    cursor: pointer;
}

.dx-close:hover { background: var(--dx-line-soft); color: var(--dx-ink); }

.dx-dialog-md { max-width: 720px; }
.dx-dialog-sm { max-width: 440px; }

#createRequestModal form {
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}

#createRequestModal .modal-body { overflow-y: auto; }

.dx-form-alert {
    margin-bottom: 1.25rem;
    padding: 0.75rem 0.9rem;
    font-size: 0.8125rem;
    color: #991b1b;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
}

.dx-form-alert ul { margin: 0.3rem 0 0; padding-left: 1.1rem; }

.dx-section { margin-bottom: 1.5rem; }
.dx-section:last-child { margin-bottom: 0; }

.dx-section-title {
    margin: 0 0 0.75rem;
    font-size: 0.8125rem;
    font-weight: 650;
    color: var(--dx-ink);
}

.dx-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.6rem;
}

.dx-section-head .dx-section-title { margin: 0; }
.dx-section-tools { display: flex; gap: 0.15rem; }

.dx-form-grid {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 0.9rem 1rem;
}

.dx-span-12 { grid-column: span 12; }
.dx-span-6  { grid-column: span 6; }
.dx-span-3  { grid-column: span 3; }

.dx-label {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--dx-text);
}

.dx-req { color: var(--dx-danger); }
.dx-opt { margin-left: 0.25rem; font-weight: 400; color: var(--dx-faint); }

.dx-modal .dx-input,
.dx-modal .dx-select { height: 38px; }
.dx-modal .dx-select { width: 100%; }

.dx-help { margin: 0.4rem 0 0; font-size: 0.75rem; color: var(--dx-muted); }

/* Referring physician — clinic staff quick picks */
.dx-staff { display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; margin-top: 0.5rem; }
.dx-staff-label { margin-right: 0.15rem; font-size: 0.72rem; font-weight: 600; color: var(--dx-faint); }
.dx-staff-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    height: 28px;
    padding: 0 0.35rem 0 0.65rem;
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--dx-text);
    background: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: 999px;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}
.dx-staff-chip:hover { border-color: var(--dx-accent); color: var(--dx-ink); }
.dx-staff-chip:focus-visible { outline: none; box-shadow: var(--dx-ring); }
.dx-staff-chip[aria-pressed="true"] { background: #dbeafe; border-color: var(--dx-accent); color: var(--dx-accent-dark); font-weight: 600; box-shadow: inset 0 0 0 1px var(--dx-accent); }
.dx-staff-chip[aria-pressed="true"]::before { content: "\2713"; margin-right: -0.1rem; font-weight: 700; }
.dx-staff-role {
    padding: 0.05rem 0.45rem;
    font-size: 0.66rem;
    font-weight: 700;
    border-radius: 999px;
    background: var(--dx-line-soft);
    color: var(--dx-muted);
}
.dx-staff-role--radiologist { background: #f3e8ff; color: #6d28d9; }
.dx-staff-role--med_tech    { background: #e6f6f4; color: #0f766e; }
.dx-doctor[hidden], .dx-staff[hidden], .dx-staff-chip[hidden] { display: none; }

.dx-seg {
    position: relative;
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: 1fr;
    padding: 3px;
    background: var(--dx-line-soft);
    border: 1px solid var(--dx-line);
    border-radius: 9px;
}

.dx-seg-input { position: absolute; opacity: 0; pointer-events: none; }

.dx-seg-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 30px;
    margin: 0;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--dx-muted);
    border-radius: 6px;
    cursor: pointer;
    user-select: none;
    transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
}

.dx-seg-btn:hover { color: var(--dx-ink); }

.dx-seg-input:checked + .dx-seg-btn {
    color: var(--dx-ink);
    background: var(--dx-surface);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.1), 0 0 0 1px rgba(15, 23, 42, 0.04);
}

.dx-seg-input.is-stat:checked + .dx-seg-btn { color: var(--dx-danger-dark); }
.dx-seg-input:focus-visible + .dx-seg-btn { box-shadow: var(--dx-ring); }

.dx-svc-list {
    max-height: 280px;
    overflow-y: auto;
    border: 1px solid var(--dx-line);
    border-radius: var(--dx-radius);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.dx-svc-list.has-error { border-color: var(--dx-danger); box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12); }

.dx-svc {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    margin: 0;
    padding: 0.6rem 0.85rem;
    font-size: 0.8125rem;
    color: var(--dx-text);
    border-bottom: 1px solid var(--dx-line-soft);
    cursor: pointer;
    transition: background-color 0.12s ease;
}

.dx-svc:hover { background: var(--dx-subtle); }
.dx-svc.is-checked { background: #f5f9fe; }

/* Lab + X-Ray: tag each service with its department */
.dx-svc-cat { display: none; flex-shrink: 0; padding: 0.05rem 0.45rem; font-size: 0.66rem; font-weight: 700; border-radius: 999px; }
.dx-svc-list.is-both .dx-svc-cat { display: inline-block; }
.dx-svc-cat--lab  { background: #e6f6f4; color: #0f766e; }
.dx-svc-cat--xray { background: #f3e8ff; color: #6d28d9; }

.dx-svc input {
    flex-shrink: 0;
    width: 16px;
    height: 16px;
    margin: 0;
    accent-color: var(--dx-accent);
    cursor: pointer;
}

.dx-svc-name { flex: 1; min-width: 0; }
.dx-svc.is-checked .dx-svc-name { color: var(--dx-ink); font-weight: 500; }

.dx-svc-price {
    font-size: 0.78rem;
    font-variant-numeric: tabular-nums;
    color: var(--dx-muted);
    white-space: nowrap;
}

.dx-svc-empty { padding: 1.5rem 1rem; text-align: center; font-size: 0.8125rem; color: var(--dx-muted); }

.dx-field-error { margin: 0.45rem 0 0; font-size: 0.75rem; color: var(--dx-danger); }

.dx-summary { font-size: 0.8125rem; color: var(--dx-muted); }
.dx-summary strong { font-weight: 600; color: var(--dx-ink); font-variant-numeric: tabular-nums; }

.dx-dl {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem 1.25rem;
    margin: 0 0 1.5rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid var(--dx-line-soft);
}

.dx-dl dt { margin: 0 0 0.2rem; font-size: 0.72rem; font-weight: 600; color: var(--dx-muted); }
.dx-dl dd { margin: 0; font-size: 0.8125rem; color: var(--dx-ink); overflow-wrap: anywhere; }

.dx-block { margin-bottom: 1.5rem; }
.dx-block:last-child { margin-bottom: 0; }

.dx-lines { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }

.dx-lines th {
    padding: 0 0 0.45rem;
    font-size: 0.72rem;
    font-weight: 600;
    text-align: left;
    color: var(--dx-muted);
    border-bottom: 1px solid var(--dx-line);
}

.dx-lines td { padding: 0.55rem 0; border-bottom: 1px solid var(--dx-line-soft); color: var(--dx-ink); }
.dx-lines td + td,
.dx-lines th + th { padding-left: 1rem; }
.dx-lines .dx-cat { color: var(--dx-muted); white-space: nowrap; }
.dx-lines .dx-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
.dx-lines tfoot td { padding-top: 0.7rem; font-weight: 650; border-bottom: 0; }

.dx-note-small { margin: 0.5rem 0 0; font-size: 0.75rem; color: var(--dx-muted); }

.dx-timeline { list-style: none; margin: 0; padding: 0; }

.dx-timeline li {
    position: relative;
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0 0 0.8rem 1.2rem;
    font-size: 0.8125rem;
}

.dx-timeline li::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0.4em;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--dx-surface);
    border: 2px solid var(--dx-faint);
}

.dx-timeline li:not(:last-child)::after {
    content: "";
    position: absolute;
    left: 3px;
    top: 1.15em;
    bottom: 0.1rem;
    width: 2px;
    background: var(--dx-line);
}

.dx-timeline li.is-done::before { border-color: #10b981; background: #10b981; }
.dx-timeline span { color: var(--dx-ink); font-weight: 500; }
.dx-timeline time { color: var(--dx-muted); white-space: nowrap; }

.dx-note {
    margin: 0;
    padding: 0.75rem 0.9rem;
    font-size: 0.8125rem;
    line-height: 1.6;
    white-space: pre-wrap;
    color: var(--dx-text);
    background: var(--dx-subtle);
    border: 1px solid var(--dx-line);
    border-radius: 8px;
}

.dx-state { padding: 3rem 1rem; text-align: center; font-size: 0.8125rem; color: var(--dx-muted); }
.dx-state .spinner-border { width: 1.5rem; height: 1.5rem; color: var(--dx-accent); margin-bottom: 0.75rem; }
.dx-state strong { display: block; margin-bottom: 0.2rem; color: var(--dx-ink); }

.dx-confirm { padding: 1.35rem 1.35rem 1.1rem; }
.dx-confirm-text { margin: 0.5rem 0 0; font-size: 0.8125rem; line-height: 1.55; color: var(--dx-text); }
.dx-confirm-text strong { color: var(--dx-ink); font-weight: 600; }

.dx-confirm-error {
    margin: 0.85rem 0 0;
    padding: 0.55rem 0.75rem;
    font-size: 0.78rem;
    color: #991b1b;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 7px;
}

.dx-toast {
    position: fixed;
    right: 1.25rem;
    bottom: 1.25rem;
    z-index: 1090;
    display: flex;
    align-items: center;
    gap: 0.55rem;
    max-width: min(26rem, calc(100vw - 2.5rem));
    padding: 0.7rem 1rem;
    font-size: 0.8125rem;
    color: #f8fafc;
    background: #0f172a;
    border-radius: 9px;
    box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.45);
}

.dx-toast i { color: #34d399; }
.dx-toast.is-error i { color: #f87171; }

@media (max-width: 1200px) {
    .dx-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
    .dx-head { flex-direction: column; align-items: stretch; }
    .dx-head-actions .dx-btn { flex: 1; }

    .dx-filters .dx-search { flex-basis: 100%; max-width: none; }
    .dx-filters .dx-select { flex: 1; }
    .dx-kbd { display: none; }

    .dx-cards { grid-template-columns: minmax(0, 1fr); padding: 0.75rem; }

    .dx-card-meta > div { align-items: flex-start; flex-direction: column; gap: 0.1rem; }
    .dx-card-meta dd { text-align: left; white-space: normal; }

    .dx-foot { justify-content: center; }
    .dx-dl { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 575.98px) {
    .dx-span-6 { grid-column: span 12; }
    .dx-span-3 { grid-column: span 6; }

    .dx-modal .modal-footer { flex-wrap: wrap; }
    .dx-summary { width: 100%; }
    .dx-footer-actions { width: 100%; }
    .dx-footer-actions .dx-btn { flex: 1; }
}

@media (prefers-reduced-motion: reduce) {
    .dx *,
    .dx-modal * { transition: none !important; }
}

@media print {
    .dx-head-actions,
    .dx-tabs,
    .dx-filters,
    .dx-foot,
    .dx-card-foot { display: none !important; }

    .dx-panel { border: 0; box-shadow: none; }
    .dx-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .dx-card.is-hidden { display: flex; }
}
/* ---------- Assign modal ---------- */

.dx-assign-intro { margin: 0 0 0.85rem; font-size: 0.8125rem; line-height: 1.55; color: var(--dx-text); }
.dx-assign-intro strong { color: var(--dx-ink); font-weight: 600; }
#dxAssignError { margin: 0 0 0.85rem; }

.dx-assign-list { display: flex; flex-direction: column; gap: 0.4rem; margin: 0; padding: 0; list-style: none; }

.dx-assign-opt {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.6rem 0.75rem;
    background: var(--dx-surface);
    border: 1px solid var(--dx-line);
    border-radius: var(--dx-radius-sm);
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease;
}
.dx-assign-opt:hover { border-color: var(--dx-faint); }
.dx-assign-opt:has(input:checked) { border-color: var(--dx-accent); background: var(--dx-accent-soft); }
.dx-assign-opt:has(input:focus-visible) { box-shadow: var(--dx-ring); }
.dx-assign-opt input { flex: none; margin: 0; accent-color: var(--dx-accent); }

.dx-assign-who { flex: 1; min-width: 0; }
.dx-assign-name { display: block; font-size: 0.8125rem; font-weight: 600; color: var(--dx-ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dx-assign-role { display: block; font-size: 0.72rem; color: var(--dx-muted); }

.dx-assign-load {
    flex: none;
    padding: 0.15rem 0.5rem;
    font-size: 0.72rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--dx-muted);
    background: var(--dx-subtle);
    border: 1px solid var(--dx-line);
    border-radius: 999px;
    white-space: nowrap;
}
.dx-assign-load.is-free { color: #166534; background: #f0fdf4; border-color: #bbf7d0; }
.dx-assign-load.is-busy { color: #92400e; background: #fffbeb; border-color: #fde68a; }

.dx-assign-state { padding: 1.25rem 0.5rem; text-align: center; font-size: 0.8125rem; color: var(--dx-muted); }
.dx-assign-state i { display: block; margin-bottom: 0.4rem; font-size: 1.5rem; color: var(--dx-faint); }
.dx-assign-state strong { display: block; margin-bottom: 0.2rem; color: var(--dx-ink); }
.dx-assign-state .dx-btn { margin-top: 0.75rem; }

.dx-waiting--locked { color: #92400e; background: #fffbeb; border-color: #fde68a; }
</style>


<script>
(function () {
    'use strict';

    /*
     * The only two lines that differ from the receptionist page.
     * Everything below is identical.
     */
    var API        = '<?= base_url() ?>admin/';
    var PRINT_BASE = <?= json_encode(base_url('admin/print-request')) ?>;
    var PER_PAGE   = 9;

    var PRICE_BOOK  = <?= json_encode($priceBook ?: new stdClass(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var EXPORT_ROWS = <?= json_encode($exportRows ?: new stdClass(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    var STATUS = {
        pending:     { label: 'Pending',     tone: 'pending' },
        queued:      { label: 'Waiting',     tone: 'queued' },
        in_progress: { label: 'In progress', tone: 'progress' },
        draft:       { label: 'Draft',       tone: 'draft' },
        completed:   { label: 'Completed',   tone: 'completed' },
        released:    { label: 'Released',    tone: 'released' },
        cancelled:   { label: 'Cancelled',   tone: 'cancelled' }
    };

    var STEPS = {
        cancelled: {
            title: 'Cancel this request?',
            verb:  'Cancel request',
            done:  'Request cancelled',
            danger: true
        },
        pending: {
            title: 'Restore this request?',
            verb:  'Restore',
            done:  'Request restored to pending'
        }
    };

    var PESO = String.fromCharCode(0x20B1);
    var GENERIC_ERROR = 'Something went wrong. Check your connection and try again.';

    function byId(id) { return document.getElementById(id); }

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function money(value) {
        return PESO + (Number(value) || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatDate(value) {
        if (!value) { return '—'; }
        var date = new Date(String(value).replace(' ', 'T'));
        if (isNaN(date.getTime())) { return String(value); }
        return date.toLocaleString('en-US', {
            month: 'short', day: 'numeric', year: 'numeric',
            hour: 'numeric', minute: '2-digit'
        });
    }

    function orDash(value) {
        var v = value == null ? '' : String(value).trim();
        return v === '' || v.toUpperCase() === 'N/A' ? '—' : v;
    }

    function parseJson(response) {
        if (!response.ok) { throw new Error('HTTP ' + response.status); }
        return response.json();
    }

    function messageOf(err) {
        return typeof err === 'string' ? err : GENERIC_ERROR;
    }

    function modal(el) { return bootstrap.Modal.getOrCreateInstance(el); }

    function ctxOf(el) {
        var host = el.closest('[data-dx-ctx]');
        if (!host || !host.dataset.id) { return null; }
        var d = host.dataset;
        return {
            id: d.id,
            type: d.type,
            status: d.status,
            reference: d.reference || 'this request',
            patient: d.patient || 'Unknown patient',
            kind: d.kind || '',
            amount: d.amount || '',
            startLock: d.startLock || ''
        };
    }

    var toastTimer = null;

    function toast(message, tone) {
        var el = byId('dxToast');
        if (!el) { return; }
        el.className = 'dx-toast' + (tone === 'error' ? ' is-error' : '');
        el.innerHTML =
            '<i class="bi ' + (tone === 'error' ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill') + '" aria-hidden="true"></i>' +
            '<span>' + esc(message) + '</span>';
        el.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { el.hidden = true; }, 4000);
    }

    function queueToast(message) {
        try { sessionStorage.setItem('dxToast', message); } catch (e) { /* storage blocked */ }
    }

    var container = byId('dxRows');
    var cards = container ? Array.prototype.slice.call(container.querySelectorAll('.dx-card')) : [];
    var matches = cards.slice();
    var state = { status: 'all', type: 'all', term: '', page: 1 };

    var searchInput = byId('searchRequests');
    var typeFilter  = byId('dxTypeFilter');

    function applyFilters(keepPage) {
        matches = cards.filter(function (card) {
            var tags = ' ' + (card.dataset.statuses || card.dataset.status || '') + ' ';
            return (state.status === 'all' || tags.indexOf(' ' + state.status + ' ') !== -1) &&
                   (state.type === 'all' || card.dataset.type === state.type) &&
                   (state.term === '' || (card.dataset.search || '').indexOf(state.term) !== -1);
        });
        if (!keepPage) { state.page = 1; }
        render();
    }

    function render() {
        var pages = Math.max(1, Math.ceil(matches.length / PER_PAGE));
        state.page = Math.min(Math.max(1, state.page), pages);

        var start = (state.page - 1) * PER_PAGE;
        var end   = Math.min(start + PER_PAGE, matches.length);

        cards.forEach(function (card) { card.classList.add('is-hidden'); });
        matches.slice(start, end).forEach(function (card) { card.classList.remove('is-hidden'); });

        var none = cards.length === 0;
        byId('dxEmpty').hidden     = !none;
        byId('dxNoMatch').hidden   = none || matches.length > 0;
        byId('dxTableWrap').hidden = none || matches.length === 0;
        byId('dxFoot').hidden      = none || matches.length === 0;

        byId('dxRange').innerHTML = matches.length
            ? 'Showing <strong>' + (start + 1) + '–' + end + '</strong> of <strong>' + matches.length + '</strong>'
            : '';

        renderPager(pages);
    }

    function renderPager(pages) {
        var pager = byId('dxPager');
        if (!pager) { return; }

        if (pages <= 1) { pager.innerHTML = ''; return; }

        var list = [];
        var i;

        if (pages <= 7) {
            for (i = 1; i <= pages; i++) { list.push(i); }
        } else {
            var from = Math.max(2, state.page - 1);
            var to   = Math.min(pages - 1, state.page + 1);
            if (state.page <= 3)         { from = 2; to = 4; }
            if (state.page >= pages - 2) { from = pages - 3; to = pages - 1; }

            list.push(1);
            if (from > 2) { list.push('gap'); }
            for (i = from; i <= to; i++) { list.push(i); }
            if (to < pages - 1) { list.push('gap'); }
            list.push(pages);
        }

        var html = '<button type="button" class="dx-page" data-page="' + (state.page - 1) + '"' +
                   (state.page === 1 ? ' disabled' : '') + ' aria-label="Previous page">' +
                   '<i class="bi bi-chevron-left" aria-hidden="true"></i></button>';

        list.forEach(function (item) {
            if (item === 'gap') {
                html += '<span class="dx-page-gap" aria-hidden="true">…</span>';
                return;
            }
            html += '<button type="button" class="dx-page" data-page="' + item + '"' +
                    (item === state.page ? ' aria-current="page"' : '') +
                    ' aria-label="Page ' + item + '">' + item + '</button>';
        });

        html += '<button type="button" class="dx-page" data-page="' + (state.page + 1) + '"' +
                (state.page === pages ? ' disabled' : '') + ' aria-label="Next page">' +
                '<i class="bi bi-chevron-right" aria-hidden="true"></i></button>';

        pager.innerHTML = html;
    }

    function setStatusTab(status) {
        document.querySelectorAll('.dx-tab').forEach(function (tab) {
            var on = tab.dataset.status === status;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        state.status = status;
    }

    function refreshCounts() {
        var counts = { all: cards.length };

        cards.forEach(function (card) {
            (card.dataset.statuses || card.dataset.status || '').split(' ').forEach(function (s) {
                if (s) { counts[s] = (counts[s] || 0) + 1; }
            });
        });

        document.querySelectorAll('.dx-tab').forEach(function (tab) {
            var badge = tab.querySelector('.dx-tab-count');
            if (badge) { badge.textContent = counts[tab.dataset.status] || 0; }
        });
    }

    document.querySelectorAll('.dx-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            setStatusTab(tab.dataset.status || 'all');
            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            state.term = searchInput.value.toLowerCase().trim();
            applyFilters();
        });
    }

    if (typeFilter) {
        typeFilter.addEventListener('change', function () {
            state.type = typeFilter.value;
            applyFilters();
        });
    }

    byId('dxPager').addEventListener('click', function (e) {
        var btn = e.target.closest('.dx-page');
        if (!btn || btn.disabled) { return; }
        state.page = parseInt(btn.dataset.page, 10) || 1;
        render();
        byId('dxTableWrap').scrollIntoView({ block: 'nearest' });
    });

    byId('dxClearFilters').addEventListener('click', function () {
        if (searchInput) { searchInput.value = ''; }
        if (typeFilter) { typeFilter.value = 'all'; }
        state.term = '';
        state.type = 'all';
        setStatusTab('all');
        applyFilters();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) { return; }
        var t = e.target;
        if (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName)) { return; }
        if (document.querySelector('.modal.show')) { return; }
        e.preventDefault();
        if (searchInput) { searchInput.focus(); }
    });

    if (container) {
        container.addEventListener('click', function (e) {
            if (e.target.closest('button, a, input, .dropdown-menu')) { return; }
            var card = e.target.closest('.dx-card');
            if (card) { openDetails(ctxOf(card)); }
        });
    }

    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-action]');
        if (!el) { return; }

        var ctx = ctxOf(el);
        if (!ctx) { return; }

        e.preventDefault();

        switch (el.dataset.action) {
            case 'view':   openDetails(ctx); break;
            case 'start':  openAssign(ctx); break;
            case 'status': askStatus(ctx, el.dataset.status); break;
            case 'delete': askDelete(ctx); break;
        }
    });

    var pendingConfirm = null;

    function openConfirm(opts) {
        var confirmEl = byId('dxConfirmModal');
        var viewEl = byId('viewRequestModal');

        var show = function () {
            byId('dxConfirmTitle').textContent = opts.title;
            byId('dxConfirmText').innerHTML = opts.body;

            var error = byId('dxConfirmError');
            error.hidden = true;
            error.textContent = '';

            var ok = byId('dxConfirmOk');
            ok.textContent = opts.okLabel;
            ok.className = 'dx-btn ' + (opts.danger ? 'dx-btn--danger' : 'dx-btn--primary');
            ok.disabled = false;

            pendingConfirm = opts;
            modal(confirmEl).show();
        };

        if (viewEl && viewEl.classList.contains('show')) {
            viewEl.addEventListener('hidden.bs.modal', show, { once: true });
            modal(viewEl).hide();
        } else {
            show();
        }
    }

    byId('dxConfirmOk').addEventListener('click', function () {
        if (!pendingConfirm) { return; }

        var ok = this;
        var label = ok.textContent;
        var error = byId('dxConfirmError');

        ok.disabled = true;
        ok.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Working…';
        error.hidden = true;

        Promise.resolve()
            .then(pendingConfirm.run)
            .catch(function (err) {
                ok.disabled = false;
                ok.textContent = label;
                error.textContent = messageOf(err);
                error.hidden = false;
            });
    });

    byId('dxConfirmModal').addEventListener('shown.bs.modal', function () {
        byId('dxConfirmOk').focus();
    });

    function askStatus(ctx, to) {
        var step = STEPS[to];
        if (!step) { return; }

        var who  = '<strong>' + esc(ctx.patient) + '</strong> (' + esc(ctx.reference) + ')';

        var body = {
            cancelled:   'Cancel ' + who + '? You can restore it to pending later.',
            pending:     'Restore ' + who + ' to pending?'
        }[to];

        openConfirm({
            title: step.title,
            body: body,
            okLabel: step.verb,
            danger: !!step.danger,
            run: function () {
                return fetch(
                    API + 'update-diagnostic-status/' + encodeURIComponent(ctx.id) + '/' +
                        encodeURIComponent(ctx.type) + '/' + encodeURIComponent(to),
                    { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' } }
                )
                .then(parseJson)
                .then(function (data) {
                    if (!data.success) { throw data.message || 'The status could not be updated.'; }
                    queueToast(step.done + ' · ' + ctx.reference);
                    location.reload();
                    return new Promise(function () {});
                });
            }
        });
    }

    /* =====================================================
       ASSIGN + START
       Start never sends a request on its own: it opens this modal,
       and the request starts only once one user is chosen. The
       server re-checks everything on confirm; a conflict reloads
       the list so the modal always shows the current state.
       ===================================================== */

    var assignCtx = null;
    var assignToken = 0;

    function assignError(message) {
        var el = byId('dxAssignError');
        el.textContent = message || '';
        el.hidden = !message;
    }

    function assignReady(ready) {
        var ok = byId('dxAssignOk');
        ok.disabled = !ready;
        ok.textContent = 'Confirm';
    }

    function openAssign(ctx) {
        if (ctx.startLock) { toast(ctx.startLock, 'error'); return; }

        assignCtx = ctx;
        var dept = ctx.type === 'xray' ? 'the X-ray department' : 'the laboratory';

        byId('dxAssignSub').innerHTML = subtitleHtml(ctx.reference, ctx.kind, '', false);
        byId('dxAssignIntro').innerHTML =
            'Choose who will handle <strong>' + esc(ctx.patient) + '</strong> in ' + dept + '. ' +
            'Starting records the cash payment of <strong>' +
            (ctx.amount ? esc(money(ctx.amount)) : 'the amount due') + '</strong>.';

        var show = function () {
            modal(byId('dxAssignModal')).show();
            loadAssign('');
        };

        var viewEl = byId('viewRequestModal');
        if (viewEl && viewEl.classList.contains('show')) {
            viewEl.addEventListener('hidden.bs.modal', show, { once: true });
            modal(viewEl).hide();
        } else {
            show();
        }
    }

    function loadAssign(notice) {
        var ctx = assignCtx;
        var body = byId('dxAssignBody');
        var token = ++assignToken;

        assignError(notice);
        assignReady(false);
        body.innerHTML = '<div class="dx-assign-state"><div class="spinner-border spinner-border-sm" role="status"></div>' +
                         '<div>Loading available staff…</div></div>';

        fetch(
            API + 'assignment-options/' + encodeURIComponent(ctx.id) + '/' + encodeURIComponent(ctx.type),
            { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
        )
        .then(parseJson)
        .then(function (data) {
            if (token !== assignToken) { return; }
            if (!data.success) { throw data.message || 'The available staff could not be loaded.'; }

            if (data.locked) {
                assignError('');
                body.innerHTML = '<div class="dx-assign-state"><i class="bi bi-person-lock" aria-hidden="true"></i>' +
                                 '<strong>Already in progress</strong>' + esc(data.message) + '</div>';
                return;
            }

            var staff = data.staff || [];
            if (!staff.length) {
                body.innerHTML = '<div class="dx-assign-state"><i class="bi bi-people" aria-hidden="true"></i>' +
                                 '<strong>No available ' + esc(data.role || 'staff') + '</strong>' +
                                 'There is no active ' + esc(data.role || 'staff member') + ' to assign. ' +
                                 'Ask an administrator to activate one, then try again.' +
                                 '<br><button type="button" class="dx-btn dx-btn--sm" data-assign-retry>Refresh</button></div>';
                return;
            }

            body.innerHTML = '<ul class="dx-assign-list" role="radiogroup" aria-label="Assign to">' +
                staff.map(function (u) {
                    var n = Number(u.workload) || 0;
                    var tone = n === 0 ? ' is-free' : (n >= 3 ? ' is-busy' : '');
                    return '<li><label class="dx-assign-opt">' +
                        '<input type="radio" name="dxAssignee" value="' + esc(u.id) + '" data-name="' + esc(u.name) + '">' +
                        '<span class="dx-assign-who"><span class="dx-assign-name">' + esc(u.name) + '</span>' +
                        '<span class="dx-assign-role">' + esc(u.role_label) + '</span></span>' +
                        '<span class="dx-assign-load' + tone + '">' + (n === 0 ? 'Free' : n + ' active') + '</span>' +
                        '</label></li>';
                }).join('') + '</ul>';
        })
        .catch(function (err) {
            if (token !== assignToken) { return; }
            body.innerHTML = '<div class="dx-assign-state"><i class="bi bi-exclamation-circle" aria-hidden="true"></i>' +
                             '<strong>Could not load staff</strong>' + esc(messageOf(err)) +
                             '<br><button type="button" class="dx-btn dx-btn--sm" data-assign-retry>Try again</button></div>';
        });
    }

    byId('dxAssignBody').addEventListener('change', function (e) {
        if (e.target.name === 'dxAssignee') { assignReady(true); }
    });

    byId('dxAssignBody').addEventListener('click', function (e) {
        if (e.target.closest('[data-assign-retry]')) { loadAssign(''); }
    });

    byId('dxAssignOk').addEventListener('click', function () {
        var picked = document.querySelector('input[name="dxAssignee"]:checked');
        if (!picked || !assignCtx) { assignError('Choose who will handle this patient.'); return; }

        var ok = this;
        var ctx = assignCtx;
        var form = new FormData();
        form.append('assigned_to', picked.value);

        ok.disabled = true;
        ok.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Starting…';
        assignError('');

        fetch(
            API + 'update-diagnostic-status/' + encodeURIComponent(ctx.id) + '/' +
                encodeURIComponent(ctx.type) + '/in_progress',
            { method: 'POST', body: form, headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' } }
        )
        .then(parseJson)
        .then(function (data) {
            if (data.success) {
                queueToast('Assigned to ' + (data.assigned_to || picked.dataset.name) + ' · ' + ctx.reference);
                location.reload();
                return;
            }
            if (data.conflict) {
                loadAssign(data.message || 'Someone else changed this request. The list has been refreshed.');
                return;
            }
            throw data.message || 'The request could not be started.';
        })
        .catch(function (err) {
            assignError(messageOf(err));
            assignReady(true);
        });
    });

    byId('dxAssignModal').addEventListener('hidden.bs.modal', function () {
        assignToken++;
        assignCtx = null;
    });


    function askDelete(ctx) {
        openConfirm({
            title: 'Delete this request?',
            body: 'Permanently delete <strong>' + esc(ctx.reference) + '</strong> for <strong>' +
                  esc(ctx.patient) + '</strong>? This cannot be undone.',
            okLabel: 'Delete request',
            danger: true,
            run: function () {
                return fetch(
                    API + 'delete-diagnostic-request/' + encodeURIComponent(ctx.id) + '/' + encodeURIComponent(ctx.type),
                    { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' } }
                )
                .then(parseJson)
                .then(function (data) {
                    if (!data.success) { throw data.message || 'The request could not be deleted.'; }

                    var card = container && container.querySelector('.dx-card[data-key="' + ctx.type + '-' + ctx.id + '"]');
                    if (card) {
                        cards = cards.filter(function (c) { return c !== card; });
                        card.remove();
                    }

                    modal(byId('dxConfirmModal')).hide();
                    refreshCounts();
                    applyFilters(true);
                    toast('Deleted ' + ctx.reference);
                });
            }
        });
    }

    var detailToken = 0;

    function subtitleHtml(reference, kind, source, isStat) {
        var parts = ['<span class="dx-ref">' + esc(reference) + '</span>'];
        if (kind)   { parts.push(esc(kind)); }
        if (source) { parts.push(esc(source)); }
        return parts.join('<span aria-hidden="true">·</span>') +
               (isStat ? ' <span class="dx-tag-stat">STAT</span>' : '');
    }

    function openDetails(ctx) {
        if (!ctx) { return; }

        var body    = byId('viewRequestContent');
        var actions = byId('viewRequestActions');

        byId('viewRequestModalLabel').textContent = ctx.patient;
        byId('viewRequestSubtitle').innerHTML = subtitleHtml(ctx.reference, ctx.kind, '', false);
        byId('viewRequestStatus').innerHTML = '';

        ['id', 'type', 'status', 'reference', 'patient', 'kind', 'amount', 'startLock'].forEach(function (k) {
            actions.dataset[k] = ctx[k] || '';
        });
        actions.innerHTML = '';

        body.innerHTML =
            '<div class="dx-state"><div class="spinner-border" role="status"></div>' +
            '<div>Loading request…</div></div>';

        modal(byId('viewRequestModal')).show();

        var token = ++detailToken;

        fetch(
            API + 'get-request-details/' + encodeURIComponent(ctx.id) + '/' + encodeURIComponent(ctx.type),
            { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
        )
        .then(parseJson)
        .then(function (data) {
            if (token !== detailToken) { return; }
            if (!data.success) { throw data.message || 'This request could not be loaded.'; }
            renderDetails(data.data || {}, ctx);
        })
        .catch(function (err) {
            if (token !== detailToken) { return; }
            body.innerHTML =
                '<div class="dx-state"><strong>Unable to load this request</strong>' +
                esc(messageOf(err)) + '</div>';
        });
    }

    function renderDetails(d, ctx) {
        var status = String(d.status || ctx.status || 'pending').toLowerCase();
        var meta   = STATUS[status] || STATUS.pending;
        var isStat = String(d.priority || '').toLowerCase() === 'stat';

        byId('viewRequestModalLabel').textContent = d.patient_name || ctx.patient;
        var badges = (d.monitor && d.monitor.badges && d.monitor.badges.length) ? d.monitor.badges : [meta];
        byId('viewRequestStatus').innerHTML = badges.map(function (b) {
            return '<span class="dx-status dx-status--' + esc(b.tone) + '">' + esc(b.label) + '</span>';
        }).join(' ');
        byId('viewRequestSubtitle').innerHTML = subtitleHtml(ctx.reference, ctx.kind, d.source, isStat);

        var age = parseInt(d.age, 10);
        var ageSex = [
            isNaN(age) ? '' : age + ' y',
            orDash(d.gender) === '—' ? '' : d.gender
        ].filter(Boolean).join(' · ') || '—';

        var facts = [
            ['Patient code', orDash(d.patient_code)],
            ['Age / sex',    ageSex],
            ['Phone',        orDash(d.phone)],
            ['Email',        orDash(d.email)],
            ['Referred by',  orDash(d.doctor_name)],
            ['Priority',     isStat ? 'STAT' : 'Routine'],
            ['Source',       orDash(d.source)],
            ['Requested',    formatDate(d.created_at)]
        ];

        var html = '<dl class="dx-dl">' + facts.map(function (f) {
            return '<div><dt>' + esc(f[0]) + '</dt><dd>' + esc(f[1]) + '</dd></div>';
        }).join('') + '</dl>';

        var names = String(d.services || '').split(',')
            .map(function (s) { return s.trim(); })
            .filter(Boolean);

        html += '<section class="dx-block"><h3 class="dx-section-title">Services</h3>';

        if (names.length) {
            var total = 0;
            var priced = 0;

            var lines = names.map(function (name) {
                var hit = PRICE_BOOK[name.toLowerCase()];
                if (hit) { total += hit.charge; priced++; }
                return '<tr><td>' + esc(name) + '</td>' +
                       '<td class="dx-cat">' + (hit ? esc(hit.category) : '') + '</td>' +
                       '<td class="dx-num">' + (hit ? esc(money(hit.charge)) : '—') + '</td></tr>';
            }).join('');

            html += '<table class="dx-lines"><thead><tr><th>Service</th><th>Category</th>' +
                    '<th class="dx-num">Charge</th></tr></thead><tbody>' + lines + '</tbody>' +
                    (priced ? '<tfoot><tr><td colspan="2">Total</td><td class="dx-num">' +
                              esc(money(total)) + '</td></tr></tfoot>' : '') +
                    '</table>';

            if (priced < names.length) {
                html += '<p class="dx-note-small">' + (names.length - priced) + ' of ' + names.length +
                        ' services have no price on file.</p>';
            }
        } else {
            html += '<p class="dx-note-small">No services listed.</p>';
        }

        html += '</section>';

        var events = ['<li><span>Request created</span><time>' + esc(formatDate(d.created_at)) + '</time></li>'];
        if (d.updated_at && d.updated_at !== d.created_at) {
            events.push('<li><span>Last updated</span><time>' + esc(formatDate(d.updated_at)) + '</time></li>');
        }
        if (d.released_at) {
            events.push('<li class="is-done"><span>Results released</span><time>' + esc(formatDate(d.released_at)) + '</time></li>');
        }

        html += '<section class="dx-block"><h3 class="dx-section-title">Activity</h3>' +
                '<ul class="dx-timeline">' + events.join('') + '</ul></section>';

        [['Findings', d.findings], ['Interpretation', d.interpretation], ['Remarks', d.remarks]]
            .forEach(function (n) {
                if (n[1] && String(n[1]).trim() !== '') {
                    html += '<section class="dx-block"><h3 class="dx-section-title">' + n[0] + '</h3>' +
                            '<p class="dx-note">' + esc(n[1]) + '</p></section>';
                }
            });

        byId('viewRequestContent').innerHTML = html;

        var actions = byId('viewRequestActions');
        actions.dataset.status = status;

        var buttons = '';

        if (status === 'released') {
            buttons += '<a class="dx-btn dx-btn--primary" href="' +
                       esc(PRINT_BASE + '/' + encodeURIComponent(ctx.id) + '/' + encodeURIComponent(ctx.type)) +
                       '"><i class="bi bi-printer" aria-hidden="true"></i> Print result</a>';
        } else if (status === 'cancelled') {
            buttons += '<button type="button" class="dx-btn" data-action="status" data-status="pending">Restore</button>';
        } else if (status === 'pending' && ctx.startLock) {
            buttons += '<span class="dx-waiting dx-waiting--locked"><i class="bi bi-person-lock" aria-hidden="true"></i> ' +
                       esc(ctx.startLock) + '</span>';
        } else if (status === 'pending') {
            buttons += '<button type="button" class="dx-btn dx-btn--primary" data-action="start">' +
                       '<i class="bi bi-send" aria-hidden="true"></i> Send to lab / x-ray</button>';
        } else {
            buttons += '<span class="dx-waiting"><i class="bi bi-hourglass-split" aria-hidden="true"></i> ' +
                       esc((d.monitor && d.monitor.note) || (status === 'completed'
                           ? 'Awaiting release by staff'
                           : 'With the diagnostic staff')) +
                       '</span>';
        }

        actions.innerHTML = buttons;
    }

    var EXPORT_COLUMNS = [
        ['reference', 'Reference'], ['patient', 'Patient'], ['age', 'Age'], ['sex', 'Sex'],
        ['type', 'Type'], ['source', 'Source'], ['priority', 'Priority'], ['status', 'Status'],
        ['services', 'Services'], ['doctor', 'Referred by'], ['phone', 'Phone'], ['email', 'Email'],
        ['requested', 'Requested'], ['amount', 'Amount (PHP)']
    ];

    function csvCell(value) {
        var v = value == null ? '' : String(value);
        if (/^[=@\t\r]/.test(v) || /^[+\-](?![\d\s()]+$)/.test(v)) { v = "'" + v; }
        return '"' + v.replace(/"/g, '""') + '"';
    }

    byId('dxExport').addEventListener('click', function () {
        var list = matches
            .map(function (card) { return EXPORT_ROWS[card.dataset.key]; })
            .filter(Boolean);

        if (!list.length) {
            toast('There are no requests in the current view to export.', 'error');
            return;
        }

        var lines = [EXPORT_COLUMNS.map(function (c) { return csvCell(c[1]); }).join(',')];
        list.forEach(function (item) {
            lines.push(EXPORT_COLUMNS.map(function (c) { return csvCell(item[c[0]]); }).join(','));
        });

        var blob = new Blob([String.fromCharCode(0xFEFF) + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'diagnostic-requests-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(function () { URL.revokeObjectURL(link.href); }, 1000);

        toast('Exported ' + list.length + ' request' + (list.length === 1 ? '' : 's'));
    });

    /* =====================================================
       REFERRING PHYSICIAN — clinic staff quick picks
       Clicking a chip fills its field (again clears it);
       the chips follow whatever is typed.
       Laboratory -> med techs, X-Ray -> radiologists.
       Lab + X-Ray swaps the single field for one per
       request: lab (med techs) and x-ray (radiologists).
       ===================================================== */

    (function () {
        var single = byId('fDoctor');
        if (!single || !single.form) { return; }

        var reqForm  = single.form;
        var deptName = { lab: 'Med Techs', xray: 'Radiologists' };

        // One text field plus the staff chips / suggestions under it.
        var picker = function (input) {
            var wrap    = input.closest('.dx-doctor');
            var chips   = Array.prototype.slice.call(wrap.querySelectorAll('.dx-staff-chip'));
            var options = Array.prototype.slice.call(wrap.querySelectorAll('datalist option'));
            var box     = wrap.querySelector('.dx-staff');
            var label   = wrap.querySelector('.dx-staff-label');

            var sync = function () {
                var value = input.value.trim().toLowerCase();
                chips.forEach(function (chip) {
                    chip.setAttribute('aria-pressed', !chip.hidden && chip.dataset.name.toLowerCase() === value ? 'true' : 'false');
                });
            };

            chips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    input.value = chip.getAttribute('aria-pressed') === 'true' ? '' : chip.dataset.name;
                    sync();
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                });
            });
            input.addEventListener('input', sync);

            return {
                input: input,
                sync: sync,
                // Offer only one department's staff.
                scope: function (dept) {
                    var value = input.value.trim().toLowerCase();
                    var shown = 0;
                    chips.forEach(function (chip) {
                        var on = chip.dataset.dept === dept;
                        chip.hidden = !on;
                        if (on) { shown++; }
                        // A staff member from the other department is dropped.
                        if (!on && chip.dataset.name.toLowerCase() === value) { input.value = ''; }
                    });
                    options.forEach(function (opt) { opt.disabled = opt.dataset.dept !== dept; });
                    if (box)   { box.hidden = shown === 0; }
                    if (label) { label.textContent = deptName[dept] || label.dataset.default; }
                    sync();
                },
                // Department of the staff member in the field, if any.
                deptOf: function () {
                    var value = input.value.trim().toLowerCase();
                    var hit = chips.filter(function (c) { return c.dataset.name.toLowerCase() === value; })[0];
                    return hit ? hit.dataset.dept : '';
                },
                // Hidden fields are disabled too, so they are not posted.
                show: function (on) { wrap.hidden = !on; input.disabled = !on; }
            };
        };

        var main  = picker(single);
        var split = {
            lab:  byId('fDoctorLab')  ? picker(byId('fDoctorLab'))  : null,
            xray: byId('fDoctorXray') ? picker(byId('fDoctorXray')) : null
        };
        var lastType = null;

        var applyType = function () {
            var checked = reqForm.querySelector('input[name="request_type"]:checked');
            var type    = checked ? checked.value : 'lab';
            var both    = type === 'both' && !!split.lab && !!split.xray;

            // Carry a name across when switching between one field and two.
            if (both && lastType !== null && lastType !== 'both' && main.input.value.trim() !== '') {
                var dept = main.deptOf();
                ['lab', 'xray'].forEach(function (t) {
                    if (split[t].input.value.trim() === '' && (!dept || dept === t)) { split[t].input.value = main.input.value; }
                });
                main.input.value = '';
                main.sync();
            } else if (!both && lastType === 'both' && split[type] && main.input.value.trim() === '') {
                main.input.value = split[type].input.value;
            }

            main.show(!both);
            if (split.lab)  { split.lab.show(both);  split.lab.sync(); }
            if (split.xray) { split.xray.show(both); split.xray.sync(); }
            if (!both) { main.scope(type === 'both' ? 'lab' : type); }
            lastType = type;
        };

        // Deferred so the services script has finished reacting first.
        var later = function () { setTimeout(applyType, 0); };
        reqForm.querySelectorAll('input[name="request_type"]').forEach(function (radio) {
            radio.addEventListener('change', later);
        });
        reqForm.addEventListener('reset', function () { lastType = null; later(); });
        applyType();
    })();


    var form = byId('createRequestForm');

    if (form) {
        var createEl  = byId('createRequestModal');
        var svcItems  = Array.prototype.slice.call(form.querySelectorAll('.dx-svc'));
        var svcList   = byId('servicesContainer');
        var svcSearch = byId('serviceSearch');
        var svcEmpty  = byId('servicesEmpty');
        var svcError  = byId('servicesError');
        var summary   = byId('selectedSummary');
        var submitBtn = byId('createRequestSubmit');

        var currentType = function () {
            var r = form.querySelector('input[name="request_type"]:checked');
            return r ? r.value : 'lab';
        };

        var filterServices = function () {
            var type = currentType();
            var term = svcSearch ? svcSearch.value.toLowerCase().trim() : '';
            var any = false;

            svcItems.forEach(function (item) {
                var show = (type === 'both' || item.dataset.category === type) &&
                           (term === '' || item.dataset.name.indexOf(term) !== -1);
                item.hidden = !show;
                if (show) { any = true; }
            });

            svcList.classList.toggle('is-both', type === 'both');
            var typeHelp = byId('typeHelp');
            if (typeHelp) { typeHelp.hidden = type !== 'both'; }

            svcEmpty.hidden = any;
            svcEmpty.textContent = term ? 'No services match your search.' : 'No services are set up for this request type.';
        };

        var updateSummary = function () {
            var count = 0;
            var total = 0;
            var byCat = { lab: 0, xray: 0 };

            svcItems.forEach(function (item) {
                var input = item.querySelector('input');
                item.classList.toggle('is-checked', input.checked);
                if (input.checked) {
                    count++;
                    byCat[item.dataset.category]++;
                    total += parseFloat(input.dataset.charge) || 0;
                }
            });

            summary.innerHTML = count
                ? '<strong>' + count + '</strong> service' + (count === 1 ? '' : 's') +
                  (currentType() === 'both' ? ' (' + byCat.lab + ' Lab · ' + byCat.xray + ' X-Ray)' : '') +
                  ' selected · <strong>' + money(total) + '</strong>'
                : 'No services selected';

            if (!svcError.hidden && !missingServices()) {
                svcList.classList.remove('has-error');
                svcError.hidden = true;
            }
        };

        // Lab + X-Ray needs at least one service from each department.
        var missingServices = function () {
            var has = function (cat) { return !!form.querySelector('.dx-svc[data-category="' + cat + '"] input:checked'); };
            if (currentType() !== 'both') {
                return form.querySelector('.dx-svc input:checked') ? '' : 'Select at least one service.';
            }
            if (!has('lab') && !has('xray')) { return 'Select at least one Laboratory and one X-Ray service.'; }
            if (!has('lab'))  { return 'Select at least one Laboratory service, or change the type to X-Ray.'; }
            if (!has('xray')) { return 'Select at least one X-Ray service, or change the type to Laboratory.'; }
            return '';
        };

        form.querySelectorAll('input[name="request_type"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var type = currentType();
                svcItems.forEach(function (item) {
                    if (type !== 'both' && item.dataset.category !== type) { item.querySelector('input').checked = false; }
                });
                filterServices();
                updateSummary();
            });
        });

        svcList.addEventListener('change', updateSummary);
        if (svcSearch) { svcSearch.addEventListener('input', filterServices); }

        byId('selectVisibleServices').addEventListener('click', function () {
            svcItems.forEach(function (item) {
                if (!item.hidden) { item.querySelector('input').checked = true; }
            });
            updateSummary();
        });

        byId('clearServices').addEventListener('click', function () {
            svcItems.forEach(function (item) { item.querySelector('input').checked = false; });
            updateSummary();
        });

        form.addEventListener('submit', function (e) {
            var missing = missingServices();
            if (missing) {
                e.preventDefault();
                svcError.textContent = missing;
                svcList.classList.add('has-error');
                svcError.hidden = false;
                svcList.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Creating…';
        });

        createEl.addEventListener('show.bs.modal', function () {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create request';
            filterServices();
            updateSummary();
        });

        createEl.addEventListener('shown.bs.modal', function () {
            var first = byId('fPatientName');
            if (first && !first.value) { first.focus(); }
        });

        filterServices();
        updateSummary();
    }

    applyFilters();

    document.addEventListener('DOMContentLoaded', function () {
        var createEl = byId('createRequestModal');
        if (createEl && createEl.dataset.openOnLoad) { modal(createEl).show(); }

        try {
            var queued = sessionStorage.getItem('dxToast');
            if (queued) {
                sessionStorage.removeItem('dxToast');
                toast(queued);
            }
        } catch (e) { /* storage blocked */ }
    });

})();
</script>

<?= $this->endSection() ?>