<?= $this->extend('Layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>View X-Ray Examination<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>

<?php
    $examination = $examination ?? [];

    $statusKey  = (string) ($examination['status'] ?? 'pending');
    $isReleased = $statusKey === 'released';
    $isEditable = !$isReleased;

    /* Payment state passed by the controller. */
    $isPaid = !empty($isPaid ?? false);

    $statusMeta = [
        'pending'     => ['label' => 'Pending',     'tone' => 'pending'],
        'in_progress' => ['label' => 'In reading',  'tone' => 'progress'],
        'processing'  => ['label' => 'In reading',  'tone' => 'progress'],
        'completed'   => ['label' => 'Completed',   'tone' => 'completed'],
        'released'    => ['label' => 'Released',    'tone' => 'released'],
    ];
    $status = $statusMeta[$statusKey] ?? ['label' => ucfirst($statusKey), 'tone' => 'pending'];

    // The assignee, else whoever worked it — same rule as the worklist.
    $radiologistName = \App\Models\DiagnosticRequestModel::handlerName($examination);
    if ($radiologistName === '') {
        $radiologistName = trim((string) (session()->get('full_name') ?? ''));
    }

    $findings       = (string) ($examination['findings'] ?? '');
    $interpretation = (string) ($examination['interpretation'] ?? '');

    $reportComplete = trim($findings) !== '' && trim($interpretation) !== '';

    /*
     * Collect every image path for this study.
     */
    $imagePaths = [];

    if (!empty($examination['image_paths'])) {
        $decoded = json_decode($examination['image_paths'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $p) {
                if (is_string($p) && trim($p) !== '') {
                    $imagePaths[] = $p;
                }
            }
        }
    }

    if (empty($imagePaths) && !empty($examination['image_path'])) {
        $imagePaths[] = $examination['image_path'];
    }

    $imagePaths = array_values(array_unique($imagePaths));
    $imageCount = count($imagePaths);
    $maxImages  = 5;
    $slotsLeft  = max(0, $maxImages - $imageCount);

    /* The server refuses image changes unless the study is paid, unreleased
       and not assigned to someone else; the controls follow the same rules. */
    $canEditImages = $isEditable && $isPaid && empty($assignedTo);

    /* Largest upload PHP will accept (per request), so the browser can warn first. */
    $iniBytes = static function (string $v): int {
        $v = trim($v);
        $n = (int) $v;
        switch (strtolower(substr($v, -1))) {
            case 'g': $n *= 1024;
            // no break
            case 'm': $n *= 1024;
            // no break
            case 'k': $n *= 1024;
        }
        return $n;
    };
    $maxUploadBytes = min(
        $iniBytes((string) ini_get('upload_max_filesize')) ?: PHP_INT_MAX,
        $iniBytes((string) ini_get('post_max_size')) ?: PHP_INT_MAX
    );

    $initialsOf = static function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return mb_strtoupper($first . $last);
    };
?>

<?php
    $accession = 'XR-' . date('y') . '-' . str_pad((string) ($examination['id'] ?? 0), 4, '0', STR_PAD_LEFT);
    $isStat    = strtolower((string) ($examination['priority'] ?? '')) === 'stat';
    $examType  = trim((string) ($examination['exam_type'] ?? ''));
    $demoParts = array_filter([
        !empty($examination['age']) ? $examination['age'] . ' y' : '',
        trim((string) ($examination['gender'] ?? '')),
    ]);
?>

<div class="xv">
    <?php if (empty($examination)): ?>
        <div class="xv-empty">
            <div class="xv-empty-icon"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></div>
            <h3>Examination not found</h3>
            <p>The study you tried to open does not exist or has been removed.</p>
            <a href="<?= base_url('radiologist/examinations') ?>" class="xv-btn xv-btn--primary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to examinations
            </a>
        </div>
    <?php else: ?>

        <!-- ===== STUDY SUMMARY: who, what, and the request facts ===== -->
        <?php
            $requestedTs = !empty($examination['created_at']) ? strtotime($examination['created_at']) : false;
            $dash        = '<span class="xv-muted">&mdash;</span>';
        ?>
        <section class="xv-card xv-summary<?= $isStat ? ' is-stat' : '' ?>" aria-label="Study summary">
            <header class="xv-head">
                <div class="xv-head-main">
                    <span class="xv-avatar" aria-hidden="true"><?= esc($initialsOf($examination['patient_name'] ?? '')) ?></span>
                    <div class="xv-head-text">
                        <h2 class="xv-head-name"><?= esc($examination['patient_name'] ?? 'Unknown') ?></h2>
                        <p class="xv-head-meta">
                            <?php if ($demoParts): ?>
                                <span><?= esc(implode(' · ', $demoParts)) ?></span>
                                <span class="xv-sep" aria-hidden="true">·</span>
                            <?php endif; ?>
                            <span class="xv-mono"><?= esc($accession) ?></span>
                        </p>
                    </div>
                </div>

                <div class="xv-head-side">
                    <?php if ($isStat): ?>
                        <span class="xv-tag-stat" title="Urgent">STAT</span>
                    <?php endif; ?>
                    <span class="xv-status xv-status--<?= esc($status['tone'], 'attr') ?>"><?= esc($status['label']) ?></span>
                    <?php if ($isReleased): ?>
                        <a href="<?= base_url('radiologist/examination/print/' . (int) $examination['id']) ?>"
                           class="xv-btn xv-btn--sm"
                           target="_blank">
                            <i class="bi bi-printer" aria-hidden="true"></i>
                            Print
                        </a>
                    <?php endif; ?>
                </div>
            </header>

            <dl class="xv-facts-bar">
                <div class="xv-fact xv-fact--wide">
                    <dt>Exam</dt>
                    <dd title="<?= esc($examType, 'attr') ?>"><?= $examType !== '' ? esc($examType) : $dash ?></dd>
                </div>
                <div class="xv-fact">
                    <dt>Exam date</dt>
                    <dd><?= !empty($examination['exam_date']) ? esc(date('M j, Y', strtotime($examination['exam_date']))) : $dash ?></dd>
                </div>
                <div class="xv-fact">
                    <dt>Requested</dt>
                    <dd><?= $requestedTs ? '<time datetime="' . esc(date('c', $requestedTs), 'attr') . '">' . esc(date('M j · g:i A', $requestedTs)) . '</time>' : $dash ?></dd>
                </div>
                <div class="xv-fact">
                    <dt>Priority</dt>
                    <dd><?= $isStat ? '<span class="xv-priority-stat">Urgent</span>' : 'Routine' ?></dd>
                </div>

                <div class="xv-fact">
                    <dt>Radiologist</dt>
                    <dd title="<?= esc($radiologistName, 'attr') ?>"><?= $radiologistName !== '' ? esc($radiologistName) : 'Unassigned' ?></dd>
                </div>
                <?php if ($isReleased && !empty($examination['released_at'])): ?>
                    <div class="xv-fact">
                        <dt>Released</dt>
                        <dd><?= esc(date('M j · g:i A', strtotime($examination['released_at']))) ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </section>

        <!-- ===== READING LAYOUT: image left, report right ===== -->
        <div class="xv-grid">

            <!-- IMAGE VIEWER -->
            <section class="xv-card xv-card--viewer" aria-label="Radiology images">
                <header class="xv-card-head xv-card-head--dark">
                    <h3 class="xv-card-title">
                        Images
                        <?php if ($imageCount > 0): ?>
                            <span class="xv-count"><?= $imageCount ?> / <?= $maxImages ?></span>
                        <?php endif; ?>
                    </h3>

                    <?php if ($isEditable): ?>
                        <div class="xv-image-tools">
                            <?php if (!$isPaid): ?>
                                <span class="xv-cap-note"><i class="bi bi-lock" aria-hidden="true"></i> Images can be added once the study is paid</span>
                            <?php elseif (!empty($assignedTo)): ?>
                                <span class="xv-cap-note"><i class="bi bi-person-lock" aria-hidden="true"></i> Assigned to <?= esc($assignedTo) ?></span>
                            <?php else: ?>
                                <?php if ($imageCount > 0): ?>
                                    <!-- Removes whichever image is on screen (path filled in by the script) -->
                                    <form action="<?= base_url('radiologist/examination/remove-image/' . (int) $examination['id']) ?>"
                                          method="POST"
                                          class="xv-remove-form"
                                          id="xvRemoveForm"
                                          data-confirm-remove>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="image_path" value="<?= esc($imagePaths[0], 'attr') ?>">
                                        <button type="submit" class="xv-btn xv-btn--sm xv-btn--onDark xv-btn--remove"
                                                title="Remove the image on screen">
                                            <i class="bi bi-trash3" aria-hidden="true"></i>
                                            <span>Remove</span>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($slotsLeft > 0): ?>
                                    <form action="<?= base_url('radiologist/examination/upload/' . (int) $examination['id']) ?>"
                                          method="POST"
                                          enctype="multipart/form-data"
                                          class="xv-upload"
                                          id="xvUploadForm"
                                          data-slots="<?= (int) $slotsLeft ?>"
                                          data-max-bytes="<?= (int) $maxUploadBytes ?>">
                                        <?= csrf_field() ?>
                                        <input type="file"
                                               name="xray_images[]"
                                               id="xrayUpload"
                                               accept="image/jpeg,image/png,image/gif,image/webp"
                                               multiple
                                               hidden>
                                        <button type="button" class="xv-btn xv-btn--sm xv-btn--onDark" onclick="document.getElementById('xrayUpload').click()">
                                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                            <span><?= $imageCount > 0 ? 'Add images' : 'Choose images' ?></span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="xv-cap-note"><?= $maxImages ?> of <?= $maxImages ?> · remove one to add another</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </header>

                <?php if ($canEditImages && $slotsLeft > 0): ?>
                    <!-- Review tray: what is about to be uploaded -->
                    <div class="xv-tray" id="xvTray" hidden>
                        <ul class="xv-tray-list" id="xvTrayList"></ul>
                        <div class="xv-tray-foot">
                            <p class="xv-tray-msg" id="xvTrayMsg" aria-live="polite"></p>
                            <div class="xv-tray-actions">
                                <button type="button" class="xv-btn xv-btn--sm xv-btn--onDark" id="xvTrayCancel">Cancel</button>
                                <button type="submit" form="xvUploadForm" class="xv-btn xv-btn--sm xv-btn--primary" id="uploadSubmit">
                                    <i class="bi bi-upload" aria-hidden="true"></i>
                                    <span id="uploadBtnLabel">Upload</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($imageCount > 0): ?>

                    <div class="xv-viewer xv-viewer--images"
                         id="xvViewer"
                         tabindex="0"
                         aria-label="X-ray image viewer"
                         data-count="<?= $imageCount ?>">

                        <div class="xv-tools" role="toolbar" aria-label="Image tools">
                            <button type="button" class="xv-tool" data-tool="out" title="Zoom out (−)" aria-label="Zoom out"><i class="bi bi-zoom-out" aria-hidden="true"></i></button>
                            <button type="button" class="xv-tool xv-tool--zoom" data-tool="fit" id="xvZoomLabel" title="Reset to default size (0)" aria-label="Reset to default size">100%</button>
                            <button type="button" class="xv-tool" data-tool="in" title="Zoom in (+)" aria-label="Zoom in"><i class="bi bi-zoom-in" aria-hidden="true"></i></button>
                            <span class="xv-tool-sep" aria-hidden="true"></span>
                            <button type="button" class="xv-tool" data-tool="invert" title="Invert / negative (I)" aria-label="Invert image" aria-pressed="false"><i class="bi bi-circle-half" aria-hidden="true"></i></button>
                            <button type="button" class="xv-tool" data-tool="full" title="Full screen (F)" aria-label="Full screen"><i class="bi bi-arrows-fullscreen" aria-hidden="true"></i></button>
                        </div>
                        <span class="xv-hint-keys">Ctrl + scroll to zoom · drag to pan · 0 to reset</span>

                        <?php foreach ($imagePaths as $i => $path): ?>
                            <img src="<?= base_url($path) ?>"
                                 alt="Radiology image <?= (int) ($i + 1) ?> for <?= esc($examination['patient_name'] ?? '', 'attr') ?>"
                                 class="xv-image<?= $i === 0 ? ' is-active' : '' ?>"
                                 data-index="<?= $i ?>"
                                 data-path="<?= esc($path, 'attr') ?>"
                                 <?= $i === 0 ? '' : 'hidden' ?>>
                        <?php endforeach; ?>

                        <?php if ($imageCount > 1): ?>
                            <button type="button"
                                    class="xv-nav xv-nav--prev"
                                    id="xvPrev"
                                    aria-label="Previous image">
                                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                            </button>
                            <button type="button"
                                    class="xv-nav xv-nav--next"
                                    id="xvNext"
                                    aria-label="Next image">
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </button>
                            <span class="xv-counter" id="xvCounter">1 / <?= $imageCount ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($imageCount > 1): ?>
                        <div class="xv-thumbs" role="tablist" aria-label="Image thumbnails">
                            <?php foreach ($imagePaths as $i => $path): ?>
                                <?php if ($canEditImages): ?>
                                    <form action="<?= base_url('radiologist/examination/remove-image/' . (int) $examination['id']) ?>"
                                          method="POST"
                                          class="xv-thumb-form"
                                          data-confirm-remove>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="image_path" value="<?= esc($path, 'attr') ?>">
                                        <button type="button"
                                                class="xv-thumb<?= $i === 0 ? ' is-active' : '' ?>"
                                                data-index="<?= $i ?>"
                                                role="tab"
                                                aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                                                aria-label="Show image <?= (int) ($i + 1) ?>">
                                            <img src="<?= base_url($path) ?>"
                                                 alt=""
                                                 loading="lazy"
                                                 decoding="async">
                                        </button>
                                        <button type="submit"
                                                class="xv-thumb-remove"
                                                title="Remove this image"
                                                aria-label="Remove image <?= (int) ($i + 1) ?>">
                                            <i class="bi bi-x" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button type="button"
                                            class="xv-thumb<?= $i === 0 ? ' is-active' : '' ?>"
                                            data-index="<?= $i ?>"
                                            role="tab"
                                            aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                                            aria-label="Show image <?= (int) ($i + 1) ?>">
                                        <img src="<?= base_url($path) ?>"
                                             alt=""
                                             loading="lazy"
                                             decoding="async">
                                    </button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>

                    <div class="xv-viewer">
                        <div class="xv-viewer-empty">
                            <span class="xv-viewer-art" aria-hidden="true">
                                <img src="<?= base_url('assets/images/bones.png') ?>" alt="">
                            </span>
                            <p>No images uploaded yet</p>
                            <small>Upload up to <?= $maxImages ?> JPEG, PNG, GIF or WEBP files to begin the read.</small>
                        </div>
                    </div>

                <?php endif; ?>
            </section>

            <!-- REPORT + STUDY DETAILS -->
            <div class="xv-side">

                <section class="xv-card xv-card--report" aria-label="Radiologist report">
                    <header class="xv-card-head">
                        <h3 class="xv-card-title">Report</h3>
                        <?php if (!$isReleased): ?>
                            <span class="xv-report-state<?= $reportComplete ? ' is-ready' : '' ?>">
                                <?= $reportComplete ? 'Ready to complete' : 'Draft' ?>
                            </span>
                        <?php endif; ?>
                    </header>

                    <?php if ($isReleased): ?>

                        <div class="xv-report">
                            <div class="xv-report-section">
                                <h4 class="xv-report-label">Findings</h4>
                                <div class="xv-report-body">
                                    <?= trim($findings) !== ''
                                        ? nl2br(esc($findings))
                                        : '<span class="xv-muted">Not recorded.</span>' ?>
                                </div>
                            </div>

                            <div class="xv-report-section">
                                <h4 class="xv-report-label">Impression</h4>
                                <div class="xv-report-body xv-report-body--impression">
                                    <?= trim($interpretation) !== ''
                                        ? nl2br(esc($interpretation))
                                        : '<span class="xv-muted">Not recorded.</span>' ?>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>

                        <?php if (!$isPaid): ?>
                            <div class="xv-alert xv-alert--warn xv-alert--inset" role="alert">
                                <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                                <span>
                                    <strong>Not paid yet.</strong>
                                    Ask the receptionist to click <strong>Start</strong> on the diagnostic-requests page before you write the report.
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($assignedTo)): ?>
                            <div class="xv-alert xv-alert--warn xv-alert--inset" role="alert">
                                <i class="bi bi-person-lock" aria-hidden="true"></i>
                                <span>
                                    <strong>Assigned to <?= esc($assignedTo) ?>.</strong>
                                    The front desk sent this patient to another radiologist. You can view the study, but saving is refused.
                                </span>
                            </div>
                        <?php endif; ?>

                        <form action="<?= base_url('radiologist/examination/save/' . (int) $examination['id']) ?>"
                              method="POST"
                              id="xvReportForm"
                              novalidate
                              <?= !$isPaid ? 'data-unpaid="1"' : '' ?>>

                            <?= csrf_field() ?>

                            <div class="xv-report">
                                <div class="xv-report-section">
                                    <label class="xv-report-label" for="findingsInput">Findings</label>
                                    <p class="xv-report-hint">What you observe: laterality, size, position and character of any abnormality.</p>
                                    <textarea class="xv-textarea"
                                              id="findingsInput"
                                              name="findings"
                                              rows="9"
                                              data-required="1"
                                              placeholder="e.g. The lungs are clear. No focal consolidation, pleural effusion or pneumothorax. Cardiac silhouette is within normal limits."><?= esc($findings) ?></textarea>
                                    <p class="xv-field-hint" id="findingsHint" hidden>Findings cannot be empty when completing the report.</p>
                                </div>

                                <div class="xv-report-section">
                                    <label class="xv-report-label" for="interpretationInput">Impression</label>
                                    <p class="xv-report-hint">The conclusion, usually one or two lines.</p>
                                    <textarea class="xv-textarea"
                                              id="interpretationInput"
                                              name="interpretation"
                                              rows="4"
                                              data-required="1"
                                              placeholder="e.g. No significant cardiopulmonary abnormality identified."><?= esc($interpretation) ?></textarea>
                                    <p class="xv-field-hint" id="interpretationHint" hidden>Impression cannot be empty when completing the report.</p>
                                </div>
                            </div>

                            <footer class="xv-report-foot">
                                <p class="xv-report-note" id="xvReportNote">
                                    <?php if ($reportComplete): ?>
                                        Both sections are filled. You can complete this report or release it.
                                    <?php else: ?>
                                        Save a draft to keep working. Completing needs both Findings and Impression.
                                    <?php endif; ?>
                                </p>

                                <div class="xv-report-buttons">
                                    <button type="submit"
                                            class="xv-btn"
                                            formaction="<?= base_url('radiologist/examination/save-draft/' . (int) $examination['id']) ?>">
                                        Save draft
                                    </button>

                                    <button type="submit"
                                            class="xv-btn xv-btn--primary"
                                            id="xvCompleteBtn"
                                            <?= $reportComplete ? '' : 'disabled' ?>>
                                        Save report
                                    </button>

                                    <?php if ($statusKey === 'completed'): ?>
                                        <a data-post href="<?= base_url('radiologist/examination/release/' . (int) $examination['id']) ?>"
                                           class="xv-btn xv-btn--success"
                                           id="xvReleaseBtn"
                                           onclick="return confirm('Release this result? The receptionist will be able to print it.')">
                                            <i class="bi bi-send-check" aria-hidden="true"></i>
                                            Release result
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </footer>
                        </form>

                    <?php endif; ?>
                </section>
            </div>
        </div>

        <?php if ($canEditImages && $imageCount > 0): ?>
            <!-- Remove confirmation: shows the exact image that will be deleted -->
            <dialog class="xv-dialog" id="xvRemoveDialog" aria-labelledby="xvRemoveTitle">
                <form method="dialog" class="xv-dialog-inner">
                    <div class="xv-dialog-film">
                        <img id="xvRemoveImg" alt="Image that will be removed">
                    </div>
                    <div class="xv-dialog-body">
                        <h3 class="xv-dialog-title" id="xvRemoveTitle">Remove this image?</h3>
                        <p class="xv-dialog-text">It will be deleted from this study and cannot be recovered. Anything typed in the report is kept.</p>
                    </div>
                    <div class="xv-dialog-foot">
                        <button type="submit" value="cancel" class="xv-btn">Keep image</button>
                        <button type="submit" value="confirm" class="xv-btn xv-btn--danger" id="xvRemoveConfirm">
                            <i class="bi bi-trash3" aria-hidden="true"></i>
                            Remove image
                        </button>
                    </div>
                </form>
            </dialog>
        <?php endif; ?>

    <?php endif; ?>

</div>

<style>
/* =========================================================
   RADIOLOGIST · VIEW EXAMINATION
   A reading layout: the film on the left, the report beside
   it, so the radiologist never scrolls between the two.
   ========================================================= */

.xv {
    --xv-ink:         var(--pm-text, #10202E);
    --xv-text:        var(--pm-text-soft, #3B4C5E);
    --xv-muted:       var(--pm-text-muted, #5D6E80);
    --xv-faint:       var(--pm-text-muted);
    --xv-line:        var(--pm-line, #DDE4EC);
    --xv-line-strong: var(--pm-line-strong, #C3CFDB);
    --xv-line-soft:   var(--pm-line);
    --xv-surface:     var(--pm-surface);
    --xv-subtle:      var(--pm-subtle);
    --xv-accent:      var(--pm-accent, #175563);
    --xv-accent-dark: var(--pm-accent-hover, #10404B);
    --xv-accent-soft: var(--pm-accent-soft, #EBF2F4);
    --xv-success:     #067647;
    --xv-success-soft:var(--pm-tint-green);
    --xv-danger:      #B42318;
    --xv-danger-soft: var(--pm-tint-red);
    --xv-amber:       #93370D;
    --xv-amber-soft:  var(--pm-tint-amber);

    /* The film area */
    --xv-film:        #0B141C;
    --xv-film-2:      #111D27;
    --xv-film-line:   rgba(255, 255, 255, 0.08);
    --xv-film-text:   #C5D3E0;

    --xv-radius:      10px;
    --xv-radius-sm:   6px;
    --xv-mono:        var(--pm-mono, ui-monospace, monospace);

    color: var(--xv-text);
    font-size: 0.875rem;
}

.xv *:focus-visible { outline: 2px solid var(--xv-accent); outline-offset: 2px; }

.xv-muted { color: var(--xv-faint); }
.xv-mono  { font-family: var(--xv-mono); font-size: 0.78rem; }

/* ---------- back link + header ---------- */

.xv-back {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    margin-bottom: 0.75rem;
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--xv-muted);
    text-decoration: none;
}

.xv-back:hover { color: var(--xv-ink); }
.xv-back i { font-size: 0.85em; }

/* Summary card: identity on top, request facts in a row beneath */
.xv-summary { margin-bottom: 1rem; }

.xv-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 1rem 1.15rem;
}

/* STAT: a faint red wash on the identity row only */
.xv-summary.is-stat .xv-head { background: var(--pm-dk-bg-neutral, #FEF6F5); }

.xv-head-main {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 0;
}

.xv-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    font-size: 0.82rem;
    font-weight: 600;
    letter-spacing: 0.03em;
    color: var(--xv-ink);
    background: var(--pm-avatar);
    border-radius: 50%;
}

.xv-head-text { min-width: 0; }

.xv-head-name {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    letter-spacing: -0.015em;
    line-height: 1.25;
    color: var(--xv-ink);
}

.xv-head-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.2rem 0.45rem;
    margin: 0.2rem 0 0;
    font-size: 0.8125rem;
    color: var(--xv-muted);
}

.xv-sep { color: var(--xv-line-strong); }

.xv-head-side {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.xv-tag-stat {
    display: inline-block;
    padding: 0 0.35rem;
    font-size: 0.64rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    line-height: 1.7;
    color: #FFFFFF;
    background: var(--xv-danger);
    border-radius: 3px;
}

.xv-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0.55rem;
    font-size: 0.74rem;
    font-weight: 600;
    white-space: nowrap;
    color: var(--xv-tone-fg);
    background: var(--xv-tone-bg);
    border-radius: 4px;
}

.xv-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--xv-tone-dot);
}

.xv-status--pending   { --xv-tone-bg: var(--xv-amber-soft);   --xv-tone-fg: var(--xv-amber);   --xv-tone-dot: #DC6803; }
.xv-status--progress  { --xv-tone-bg: var(--xv-accent-soft);  --xv-tone-fg: var(--xv-accent-dark); --xv-tone-dot: var(--xv-accent); }
.xv-status--completed { --xv-tone-bg: var(--xv-success-soft); --xv-tone-fg: var(--xv-success); --xv-tone-dot: #17B26A; }
.xv-status--released  { --xv-tone-bg: var(--pm-tint-sea);               --xv-tone-fg: #0E6E64;           --xv-tone-dot: #0E8A7E; }

/* ---------- alerts ---------- */

.xv-alert {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    padding: 0.7rem 0.9rem;
    margin-bottom: 0.85rem;
    font-size: 0.8rem;
    line-height: 1.5;
    border: 1px solid transparent;
    border-radius: var(--xv-radius-sm);
}

.xv-alert i { margin-top: 0.15rem; }
.xv-alert--success { background: var(--xv-success-soft); border-color: var(--pm-dk-edge-green, #ABEFC6); color: var(--xv-success); }
.xv-alert--error   { background: var(--xv-danger-soft);  border-color: var(--pm-dk-edge-red, #FECDCA); color: var(--xv-danger); }
.xv-alert--warn    { background: var(--xv-amber-soft);   border-color: var(--pm-dk-edge-amber, #FEDF89); color: var(--xv-amber); }
.xv-alert--inset   { margin: 1rem 1.1rem 0; }

/* ---------- buttons ---------- */

.xv-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    height: 36px;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    color: var(--xv-ink);
    background: var(--xv-surface);
    border: 1px solid var(--xv-line);
    border-radius: var(--xv-radius-sm);
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.xv-btn:hover:not(:disabled) { background: var(--xv-subtle); border-color: var(--xv-line-strong); color: var(--xv-ink); }
.xv-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.xv-btn i { font-size: 0.9em; }

.xv-btn--sm { height: 30px; padding: 0 0.7rem; font-size: 0.78rem; }

.xv-btn--primary,
.xv-btn--primary:hover:not(:disabled) { color: #FFFFFF; }
.xv-btn--primary { background: var(--xv-accent); border-color: var(--xv-accent); }
.xv-btn--primary:hover:not(:disabled) { background: var(--xv-accent-dark); border-color: var(--xv-accent-dark); }

.xv-btn--success,
.xv-btn--success:hover:not(:disabled) { color: #FFFFFF; }
.xv-btn--success { background: var(--xv-success); border-color: var(--xv-success); }
.xv-btn--success:hover:not(:disabled) { background: #055C38; border-color: #055C38; }

/* Secondary button sitting on the dark film header */
.xv-btn--onDark { color: var(--xv-film-text); background: transparent; border-color: rgba(255, 255, 255, 0.16); }
.xv-btn--onDark:hover:not(:disabled) { color: #FFFFFF; background: rgba(255, 255, 255, 0.08); border-color: rgba(255, 255, 255, 0.28); }

/* Remove sits quietly beside Add until it is pointed at */
.xv-btn--remove:hover:not(:disabled) { color: #FFFFFF; background: rgba(180, 35, 24, 0.55); border-color: rgba(240, 68, 56, 0.6); }

.xv-btn--danger,
.xv-btn--danger:hover:not(:disabled) { color: #FFFFFF; }
.xv-btn--danger { background: var(--xv-danger); border-color: var(--xv-danger); }
.xv-btn--danger:hover:not(:disabled) { background: #912018; border-color: #912018; }

.xv-btn.is-busy { pointer-events: none; opacity: 0.75; }

/* ---------- image tools (header) ---------- */

.xv-image-tools {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.xv-remove-form { margin: 0; }

.xv-cap-note i { margin-right: 0.25rem; }

/* ---------- upload review tray ---------- */

.xv-tray {
    padding: 0.75rem 1.1rem 0.85rem;
    background: var(--xv-film-2);
    border-bottom: 1px solid var(--xv-film-line);
}

.xv-tray-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 0 0 0.65rem;
    padding: 0;
    list-style: none;
}

.xv-tray-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    max-width: 240px;
    padding: 0.3rem 0.6rem 0.3rem 0.3rem;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid var(--xv-film-line);
    border-radius: 6px;
}

.xv-tray-item img {
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    object-fit: cover;
    border-radius: 4px;
    background: var(--xv-film);
}

.xv-tray-item.is-skipped { opacity: 0.55; }
.xv-tray-item.is-skipped .xv-tray-size { color: #FDA29B; }

.xv-tray-meta { display: flex; flex-direction: column; min-width: 0; line-height: 1.3; }

.xv-tray-name {
    font-size: 0.76rem;
    color: #E3EAF2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.xv-tray-size { font-size: 0.7rem; color: #8496A8; }

.xv-tray-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.xv-tray-msg { margin: 0; font-size: 0.74rem; color: var(--xv-film-text); }
.xv-tray-msg.is-error { color: #FDA29B; }

.xv-tray-actions { display: flex; gap: 0.4rem; }

/* ---------- drag and drop ---------- */

.xv-card--viewer::after {
    content: "Drop images to add them to this study";
    position: absolute;
    inset: 0;
    z-index: 5;
    display: grid;
    place-items: center;
    font-size: 0.875rem;
    font-weight: 500;
    color: #FFFFFF;
    background: rgba(23, 85, 99, 0.28);
    border: 2px dashed var(--pm-accent-on-ink, #9CD0D8);
    border-radius: inherit;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.12s ease;
}

.xv-card--viewer.is-dropping::after { opacity: 1; }

/* ---------- remove confirmation ---------- */

.xv-dialog {
    width: min(420px, calc(100vw - 2rem));
    padding: 0;
    color: var(--xv-text);
    background: var(--xv-surface);
    border: 1px solid var(--xv-line);
    border-radius: 12px;
    box-shadow: 0 24px 48px -16px rgba(20, 45, 54, 0.35);
}

.xv-dialog::backdrop { background: rgba(20, 45, 54, 0.45); }

.xv-dialog-inner { margin: 0; }

.xv-dialog-film {
    display: grid;
    place-items: center;
    height: 200px;
    background: var(--xv-film);
}

.xv-dialog-film img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    display: block;
}

.xv-dialog-body { padding: 1rem 1.15rem 0.25rem; }

.xv-dialog-title {
    margin: 0 0 0.3rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--xv-ink);
}

.xv-dialog-text {
    margin: 0;
    font-size: 0.8125rem;
    line-height: 1.5;
    color: var(--xv-muted);
}

.xv-dialog-foot {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem 1.15rem 1.1rem;
}

/* ---------- cards ---------- */

.xv-card {
    background: var(--xv-surface);
    border: 1px solid var(--xv-line);
    border-radius: var(--xv-radius);
    overflow: hidden;
}

.xv-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    min-height: 48px;
    padding: 0.55rem 1.1rem;
    border-bottom: 1px solid var(--xv-line-soft);
}

.xv-card-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--xv-muted);
}

.xv-count {
    font-family: var(--xv-mono);
    font-size: 0.72rem;
    font-weight: 500;
    letter-spacing: 0;
    text-transform: none;
}

.xv-cap-note { font-size: 0.74rem; color: var(--xv-film-text); opacity: 0.75; }

/* ---------- layout ---------- */

.xv-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 400px;
    gap: 1rem;
    align-items: start;
}

.xv-side {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-width: 0;
}

/* The film stays in view while the report column scrolls */
.xv-card--viewer {
    position: sticky;
    top: 72px;
    background: var(--xv-film);
    border-color: var(--xv-film);
}

.xv-card-head--dark {
    background: var(--xv-film-2);
    border-bottom-color: var(--xv-film-line);
}

.xv-card-head--dark .xv-card-title { color: var(--xv-film-text); }

/* ---------- viewer ---------- */

.xv-upload {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    margin: 0;
}

.xv-viewer {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    height: clamp(420px, calc(100vh - 280px), 760px);
    padding: 1rem;
    background: var(--xv-film);
    overflow: hidden;
}

.xv-viewer--images {
    padding: 3rem 1rem 2.5rem;
    user-select: none;
    touch-action: none;
}

.xv-image {
    max-width: 92%;
    max-height: 100%;
    display: block;
    border-radius: 2px;
}

.xv-viewer--images .xv-image {
    transform-origin: center center;
    transition: filter .15s ease;
    will-change: transform;
}

.xv-viewer--images.is-zoomed            { cursor: grab; }
.xv-viewer--images.is-zoomed.is-panning { cursor: grabbing; }
.xv-viewer--images.is-inverted .xv-image { filter: invert(1); }

.xv-viewer--images:fullscreen { height: 100vh; width: 100vw; padding: 0; background: #05080F; }
.xv-viewer--images:fullscreen .xv-image {
    width: 100%;
    height: 100%;
    max-width: none;
    max-height: none;
    object-fit: contain;
    border-radius: 0;
}

/* Reading tools, top-left over the film */
.xv-tools {
    position: absolute;
    top: .75rem;
    left: .75rem;
    z-index: 2;
    display: inline-flex;
    align-items: center;
    gap: 2px;
    padding: 3px;
    background: rgba(11, 20, 28, .78);
    border: 1px solid var(--xv-film-line);
    border-radius: 8px;
}

.xv-tool {
    min-width: 32px;
    height: 30px;
    padding: 0 .45rem;
    display: inline-grid;
    place-items: center;
    color: var(--xv-film-text);
    background: transparent;
    border: 0;
    border-radius: 6px;
    font-size: .8rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    cursor: pointer;
}

.xv-tool:hover { color: #FFFFFF; background: rgba(255, 255, 255, .08); }
.xv-tool:focus-visible { outline: 2px solid var(--pm-accent-on-ink, #9CD0D8); outline-offset: 1px; }
.xv-tool[aria-pressed="true"] { color: #FFFFFF; background: rgba(111, 184, 238, .22); }
.xv-tool-sep { width: 1px; height: 18px; margin: 0 2px; background: var(--xv-film-line); }
.xv-tool--zoom { min-width: 52px; }

.xv-hint-keys {
    position: absolute;
    left: .75rem;
    bottom: .75rem;
    z-index: 2;
    padding: .2rem .5rem;
    font-size: .7rem;
    color: var(--xv-film-text);
    background: rgba(11, 20, 28, .7);
    border-radius: 4px;
    pointer-events: none;
}

.xv-nav, .xv-counter { z-index: 2; }

.xv-image[hidden] { display: none; }

.xv-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    color: var(--xv-film-text);
    background: rgba(11, 20, 28, 0.6);
    border: 1px solid var(--xv-film-line);
    border-radius: 50%;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.xv-nav:hover { color: #FFFFFF; background: rgba(11, 20, 28, 0.9); }

.xv-nav--prev { left: 0.75rem; }
.xv-nav--next { right: 0.75rem; }

.xv-counter {
    position: absolute;
    bottom: 0.75rem;
    right: 0.75rem;
    padding: 0.2rem 0.5rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--xv-film-text);
    background: rgba(11, 20, 28, 0.7);
    border-radius: 4px;
    font-variant-numeric: tabular-nums;
}

/* Thumbnails continue the film strip */
.xv-thumbs {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.7rem 1.1rem;
    overflow-x: auto;
    background: var(--xv-film-2);
    border-top: 1px solid var(--xv-film-line);
}

.xv-thumb-form {
    position: relative;
    display: inline-flex;
    margin: 0;
}

.xv-thumb {
    width: 60px;
    height: 60px;
    padding: 0;
    overflow: hidden;
    background: var(--xv-film);
    border: 2px solid transparent;
    border-radius: 6px;
    opacity: 0.6;
    cursor: pointer;
    transition: border-color 0.15s ease, opacity 0.15s ease;
}

.xv-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.xv-thumb:hover { opacity: 0.9; }

.xv-thumb.is-active {
    opacity: 1;
    border-color: var(--pm-accent-on-ink, #9CD0D8);
}

.xv-thumb-remove {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 20px;
    height: 20px;
    display: grid;
    place-items: center;
    padding: 0;
    font-size: 0.7rem;
    line-height: 1;
    color: #FFFFFF;
    background: #3A4752;
    border: 2px solid var(--xv-film-2);
    border-radius: 50%;
    opacity: 0;
    cursor: pointer;
    transition: background-color 0.15s ease, opacity 0.15s ease;
}

.xv-thumb-form:hover .xv-thumb-remove,
.xv-thumb-remove:focus-visible { opacity: 1; }
.xv-thumb-remove:hover { background: var(--xv-danger); }

@media (hover: none) {
    .xv-thumb-remove { opacity: 1; }
}

.xv-viewer-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    padding: 2.5rem 1.5rem;
    text-align: center;
}

/* The X-Ray artwork, teal, same treatment as notifications and the worklist */
.xv-viewer-art {
    display: block;
    width: 72px;
    height: 72px;
    margin-bottom: 0.6rem;
    overflow: hidden;
    border-radius: 10px;
    background: #0E8A7E;
}

.xv-viewer-art img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    mix-blend-mode: screen;
}

.xv-viewer-empty p { margin: 0; font-size: 0.875rem; font-weight: 500; color: #E3EAF2; }
.xv-viewer-empty small { font-size: 0.75rem; color: #8496A8; }

/* ---------- report ---------- */

.xv-report-state {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.74rem;
    font-weight: 500;
    color: var(--xv-muted);
}

.xv-report-state::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #98A2B3;
}

.xv-report-state.is-ready { color: var(--xv-success); }
.xv-report-state.is-ready::before { background: #17B26A; }

.xv-report { padding: 1rem 1.1rem 0.25rem; }

.xv-report-section { margin-bottom: 1.1rem; }
.xv-report-section:last-child { margin-bottom: 0.75rem; }

.xv-report-label {
    display: block;
    margin: 0 0 0.2rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--xv-ink);
}

.xv-report-hint {
    margin: 0 0 0.5rem;
    font-size: 0.74rem;
    line-height: 1.45;
    color: var(--xv-muted);
}

.xv-report-body {
    padding: 0.8rem 0.9rem;
    font-size: 0.86rem;
    line-height: 1.65;
    color: var(--xv-text);
    background: var(--xv-subtle);
    border: 1px solid var(--xv-line-soft);
    border-radius: var(--xv-radius-sm);
}

.xv-report-body--impression { font-weight: 500; color: var(--xv-ink); }

.xv-textarea {
    display: block;
    width: 100%;
    min-height: 120px;
    padding: 0.75rem 0.85rem;
    font-family: inherit;
    font-size: 0.86rem;
    line-height: 1.6;
    color: var(--xv-ink);
    background: var(--xv-surface);
    border: 1px solid var(--xv-line-strong);
    border-radius: var(--xv-radius-sm);
    resize: vertical;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.xv-textarea::placeholder { color: var(--xv-faint); }

.xv-textarea:focus {
    outline: none;
    border-color: var(--xv-accent);
    box-shadow: 0 0 0 3px rgba(23, 85, 99, 0.15);
}

.xv-textarea:disabled,
.xv-textarea[readonly] { color: var(--xv-muted); background: var(--xv-subtle); }

.xv-textarea.is-missing {
    border-color: var(--xv-danger);
    box-shadow: 0 0 0 3px rgba(180, 35, 24, 0.12);
}

.xv-field-hint {
    margin: 0.4rem 0 0;
    font-size: 0.74rem;
    color: var(--xv-danger);
}

.xv-report-foot {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    padding: 0.85rem 1.1rem 1rem;
    border-top: 1px solid var(--xv-line-soft);
}

.xv-report-note {
    margin: 0;
    font-size: 0.74rem;
    line-height: 1.5;
    color: var(--xv-muted);
}

.xv-report-buttons {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* ---------- study facts (summary row) ---------- */

/* Cells wrap onto more rows on narrow screens; each draws its own
   left/top hairline, and the ones on the outer edge are clipped. */
.xv-facts-bar {
    display: flex;
    flex-wrap: wrap;
    margin: 0;
    overflow: hidden;
    background: var(--xv-surface);
    border-top: 1px solid var(--xv-line-soft);
}

.xv-fact {
    flex: 1 1 140px;
    min-width: 0;
    margin: 0 0 0 -1px;
    padding: 0.7rem 1.15rem 0.75rem;
    border-left: 1px solid var(--xv-line-soft);
    box-shadow: 0 -1px 0 0 var(--xv-line-soft);
}

.xv-fact--wide { flex: 2 1 240px; }

.xv-fact dt {
    margin: 0;
    font-size: 0.66rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--xv-faint);
}

.xv-fact dd {
    margin: 0.2rem 0 0;
    font-size: 0.84rem;
    font-weight: 500;
    line-height: 1.35;
    color: var(--xv-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-variant-numeric: tabular-nums;
}

.xv-fact--wide dd { font-weight: 600; }

.xv-priority-stat {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    color: var(--xv-danger);
    font-weight: 600;
}

.xv-priority-stat::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

/* ---------- empty ---------- */

.xv-empty {
    padding: 3.5rem 1rem;
    text-align: center;
    background: var(--xv-surface);
    border: 1px solid var(--xv-line);
    border-radius: var(--xv-radius);
}

.xv-empty-icon {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    margin: 0 auto 0.85rem;
    font-size: 1.2rem;
    color: var(--xv-faint);
    background: var(--xv-line-soft);
    border-radius: 8px;
}

.xv-empty h3 { margin: 0 0 0.25rem; font-size: 0.95rem; font-weight: 600; color: var(--xv-ink); }
.xv-empty p { max-width: 26rem; margin: 0 auto 1rem; font-size: 0.8125rem; color: var(--xv-muted); }

/* ---------- responsive ---------- */

@media (max-width: 1280px) {
    .xv-grid { grid-template-columns: minmax(0, 1fr) 360px; }
}

@media (max-width: 1000px) {
    .xv-grid { grid-template-columns: minmax(0, 1fr); }
    .xv-card--viewer { position: static; }
    .xv-viewer { height: clamp(320px, 60vh, 560px); }
}

@media (max-width: 640px) {
    .xv-head-side { width: 100%; }
    .xv-viewer { height: 300px; }
    .xv-hint-keys { display: none; }

    .xv-nav { width: 30px; height: 30px; }
    .xv-nav--prev { left: 0.5rem; }
    .xv-nav--next { right: 0.5rem; }

    .xv-thumb { width: 52px; height: 52px; }

    .xv-report-buttons .xv-btn { flex: 1; }
}

@media (prefers-reduced-motion: reduce) {
    .xv *, .xv *::before, .xv *::after { transition: none !important; }
}
</style>

<script>
(function () {
    'use strict';

    /* =====================================================
       PAYMENT GUARD
       Block the report form entirely when the study has not
       been paid. The server refuses the save anyway; this
       is a UX guard that stops the radiologist from wasting
       time on a report that cannot be submitted.
       ===================================================== */
    var reportForm = document.getElementById('xvReportForm');
    if (reportForm && reportForm.dataset.unpaid === '1') {
        reportForm.addEventListener('submit', function (e) {
            e.preventDefault();
            alert('This study has not been paid yet. Ask the receptionist to click Start first.');
        });
        reportForm.querySelectorAll('textarea, button[type="submit"]').forEach(function (el) {
            el.disabled = true;
        });
        return;
    }

    /* =====================================================
       Upload button: reveal the confirm button once files are
       selected, and summarise how many were chosen.
       ===================================================== */

    var uploadForm   = document.getElementById('xvUploadForm');
    var fileInput    = document.getElementById('xrayUpload');
    var uploadSubmit = document.getElementById('uploadSubmit');
    var uploadLabel  = document.getElementById('uploadBtnLabel');
    var tray         = document.getElementById('xvTray');
    var trayList     = document.getElementById('xvTrayList');
    var trayMsg      = document.getElementById('xvTrayMsg');
    var trayCancel   = document.getElementById('xvTrayCancel');

    if (uploadForm && fileInput && uploadSubmit && tray) {
        var slots    = parseInt(uploadForm.dataset.slots, 10) || 0;
        var maxBytes = parseInt(uploadForm.dataset.maxBytes, 10) || 0;
        var okTypes  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        var previews = [];

        var fmtSize = function (bytes) {
            return bytes >= 1048576
                ? (bytes / 1048576).toFixed(1) + ' MB'
                : Math.max(1, Math.round(bytes / 1024)) + ' KB';
        };

        var clearTray = function () {
            previews.forEach(function (url) { URL.revokeObjectURL(url); });
            previews = [];
            trayList.innerHTML = '';
        };

        /* Show what will be uploaded, mirroring the server's rules:
           non-images are skipped and do not use a slot; extras past
           the free slots are skipped. */
        var renderTray = function () {
            clearTray();
            var files = Array.prototype.slice.call(fileInput.files || []);
            if (!files.length) { tray.hidden = true; return; }

            var used = 0, total = 0;

            files.forEach(function (file) {
                var isImage = okTypes.indexOf(file.type) !== -1;
                var fits    = isImage && used < slots;
                if (fits) { used++; total += file.size; }

                var item = document.createElement('li');
                item.className = 'xv-tray-item' + (fits ? '' : ' is-skipped');

                if (isImage) {
                    var url = URL.createObjectURL(file);
                    previews.push(url);
                    var img = document.createElement('img');
                    img.src = url;
                    img.alt = '';
                    item.appendChild(img);
                }

                var meta = document.createElement('span');
                meta.className = 'xv-tray-meta';
                var name = document.createElement('span');
                name.className = 'xv-tray-name';
                name.textContent = file.name;
                var size = document.createElement('span');
                size.className = 'xv-tray-size';
                size.textContent = !isImage ? 'Not a supported image · skipped'
                                 : (!fits ? 'No free slot · skipped' : fmtSize(file.size));
                meta.appendChild(name);
                meta.appendChild(size);
                item.appendChild(meta);
                trayList.appendChild(item);
            });

            var tooBig = maxBytes > 0 && total > maxBytes;
            trayMsg.classList.toggle('is-error', tooBig || used === 0);

            if (used === 0) {
                trayMsg.textContent = 'None of these can be added. Use JPEG, PNG, GIF or WEBP images.';
            } else if (tooBig) {
                trayMsg.textContent = 'These add up to ' + fmtSize(total) + '. Upload at most ' + fmtSize(maxBytes) + ' at a time.';
            } else {
                var left = slots - used;
                trayMsg.textContent = used + ' image' + (used === 1 ? '' : 's') + ' will be added · '
                                    + left + ' slot' + (left === 1 ? '' : 's') + ' left afterwards';
            }

            uploadSubmit.disabled = used === 0 || tooBig;
            uploadLabel.textContent = used > 0 ? 'Upload ' + used : 'Upload';
            tray.hidden = false;
        };

        fileInput.addEventListener('change', renderTray);

        trayCancel.addEventListener('click', function () {
            fileInput.value = '';
            renderTray();
        });

        uploadForm.addEventListener('submit', function () {
            setTimeout(function () {
                uploadSubmit.classList.add('is-busy');
                uploadLabel.textContent = 'Uploading…';
            }, 0);
        });

        /* Drag images straight onto the viewer */
        var dropZone = document.querySelector('.xv-card--viewer');
        var hasFiles = function (e) {
            return e.dataTransfer && Array.prototype.indexOf.call(e.dataTransfer.types || [], 'Files') !== -1;
        };

        if (dropZone) {
            var depth = 0;

            dropZone.addEventListener('dragenter', function (e) {
                if (!hasFiles(e)) { return; }
                e.preventDefault();
                depth++;
                dropZone.classList.add('is-dropping');
            });

            dropZone.addEventListener('dragover', function (e) {
                if (hasFiles(e)) { e.preventDefault(); }
            });

            dropZone.addEventListener('dragleave', function () {
                depth = Math.max(0, depth - 1);
                if (depth === 0) { dropZone.classList.remove('is-dropping'); }
            });

            dropZone.addEventListener('drop', function (e) {
                if (!hasFiles(e)) { return; }
                e.preventDefault();
                depth = 0;
                dropZone.classList.remove('is-dropping');
                try { fileInput.files = e.dataTransfer.files; } catch (err) { return; }
                renderTray();
            });
        }
    }

    /* =====================================================
       Keep the report text across image uploads / removals.
       Those are separate forms that reload the page, so the
       current Findings / Impression go along with them and
       the server saves them as a draft first.
       ===================================================== */

    var findingsInput       = document.getElementById('findingsInput');
    var interpretationInput = document.getElementById('interpretationInput');

    document.querySelectorAll('form.xv-upload, form.xv-thumb-form, form.xv-remove-form').forEach(function (form) {
        form.addEventListener('submit', function () {
            [['findings', findingsInput], ['interpretation', interpretationInput]].forEach(function (pair) {
                if (!pair[1]) { return; }
                var hidden = form.querySelector('input[type="hidden"][name="' + pair[0] + '"]');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = pair[0];
                    form.appendChild(hidden);
                }
                hidden.value = pair[1].value;
            });
        });
    });

    /* =====================================================
       Remove image: confirm with a preview of the exact
       image, then submit. The header button removes the
       image currently on screen.
       ===================================================== */

    var removeDialog = document.getElementById('xvRemoveDialog');
    var removeImg    = document.getElementById('xvRemoveImg');

    var submitForm = function (form) {
        if (typeof form.requestSubmit === 'function') { form.requestSubmit(); }
        else { form.submit(); }
    };

    document.querySelectorAll('form[data-confirm-remove]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.confirmed === '1') { return; }
            e.preventDefault();

            var input = form.querySelector('input[name="image_path"]');
            if (!input) { return; }

            if (form.id === 'xvRemoveForm') {
                var onScreen = document.querySelector('#xvViewer .xv-image.is-active');
                if (onScreen && onScreen.dataset.path) { input.value = onScreen.dataset.path; }
            }

            var match = Array.prototype.filter.call(
                document.querySelectorAll('#xvViewer .xv-image'),
                function (img) { return img.dataset.path === input.value; }
            )[0];

            if (!removeDialog || typeof removeDialog.showModal !== 'function') {
                if (window.confirm('Remove this image? This cannot be undone.')) {
                    form.dataset.confirmed = '1';
                    submitForm(form);
                }
                return;
            }

            removeImg.src = match ? match.src : '';
            removeDialog.returnValue = '';
            removeDialog.onclose = function () {
                if (removeDialog.returnValue !== 'confirm') { return; }
                form.dataset.confirmed = '1';
                var btn = form.querySelector('button[type="submit"]');
                if (btn) { btn.classList.add('is-busy'); }
                submitForm(form);
            };
            removeDialog.showModal();
            document.getElementById('xvRemoveConfirm').focus();
        });
    });

    /* =====================================================
       Image viewer.
       ===================================================== */

    var viewer = document.getElementById('xvViewer');
    var images = viewer ? Array.prototype.slice.call(viewer.querySelectorAll('.xv-image')) : [];
    var thumbs = Array.prototype.slice.call(document.querySelectorAll('.xv-thumb'));
    var counter = document.getElementById('xvCounter');
    var prevBtn = document.getElementById('xvPrev');
    var nextBtn = document.getElementById('xvNext');

    /* ---- Reading tools ---- */
    // BASE is the default size: twice the fitted size, shown as "100%".
    // Reset ('0' or the % button) returns to it.
    var BASE = 2;
    var zoom = BASE, panX = 0, panY = 0;
    var zoomLabel = document.getElementById('xvZoomLabel');
    var ZOOM_MIN = 1, ZOOM_MAX = BASE * 4;   // 50% .. 400%

    function activeImage() {
        return images.filter(function (img) { return !img.hidden; })[0] || images[0];
    }

    function applyView() {
        var img = activeImage();
        if (!img) { return; }
        if (zoom <= 1) { zoom = 1; panX = 0; panY = 0; }
        // keep the image from being dragged completely out of view
        var maxX = viewer.clientWidth  * (zoom - 1) / 2;
        var maxY = viewer.clientHeight * (zoom - 1) / 2;
        panX = Math.max(-maxX, Math.min(maxX, panX));
        panY = Math.max(-maxY, Math.min(maxY, panY));
        img.style.transform = 'translate(' + panX + 'px,' + panY + 'px) scale(' + zoom + ')';
        viewer.classList.toggle('is-zoomed', zoom > 1);
        
        // This calculates the label correctly based on the 2x BASE
        if (zoomLabel) { zoomLabel.textContent = Math.round(zoom / BASE * 100) + '%'; }
    }

    function setZoom(z) {
        zoom = Math.max(ZOOM_MIN, Math.min(ZOOM_MAX, z));
        applyView();
    }

    function resetView() {
        images.forEach(function (img) { img.style.transform = ''; });
        zoom = BASE; panX = 0; panY = 0;
        applyView();
    }

    function defaultView() {
        resetView();
    }

    function toggleInvert() {
        var on = viewer.classList.toggle('is-inverted');
        var btn = viewer.querySelector('[data-tool="invert"]');
        if (btn) { btn.setAttribute('aria-pressed', on ? 'true' : 'false'); }
    }

    function toggleFullscreen() {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else if (viewer.requestFullscreen) {
            viewer.requestFullscreen().catch(function () {});
        }
    }

    if (viewer && images.length) {
        viewer.querySelectorAll('[data-tool]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                switch (btn.dataset.tool) {
                    case 'in':     setZoom(zoom * 1.25); break;
                    case 'out':    setZoom(zoom / 1.25); break;
                    case 'fit':    resetView(); break;
                    case 'invert': toggleInvert(); break;
                    case 'full':   toggleFullscreen(); break;
                }
            });
        });

        // Ctrl / ⌘ + wheel zooms; plain wheel keeps scrolling the page
        viewer.addEventListener('wheel', function (e) {
            if (!e.ctrlKey && !e.metaKey) { return; }
            e.preventDefault();
            setZoom(zoom * (e.deltaY < 0 ? 1.15 : 1 / 1.15));
        }, { passive: false });

        viewer.addEventListener('dblclick', function (e) {
            if (e.target.closest('.xv-tools, .xv-nav')) { return; }
            if (zoom !== BASE) { resetView(); } else { setZoom(BASE * 2); }
        });

        // drag to pan while zoomed
        var drag = null;
        viewer.addEventListener('pointerdown', function (e) {
            if (zoom <= 1 || e.target.closest('.xv-tools, .xv-nav')) { return; }
            drag = { x: e.clientX - panX, y: e.clientY - panY };
            viewer.classList.add('is-panning');
            viewer.setPointerCapture(e.pointerId);
        });
        viewer.addEventListener('pointermove', function (e) {
            if (!drag) { return; }
            panX = e.clientX - drag.x;
            panY = e.clientY - drag.y;
            applyView();
        });
        ['pointerup', 'pointercancel'].forEach(function (type) {
            viewer.addEventListener(type, function () {
                drag = null;
                viewer.classList.remove('is-panning');
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable)) {
                return;
            }
            if (e.ctrlKey || e.metaKey || e.altKey) { return; }
            if (e.key === '+' || e.key === '=') { setZoom(zoom * 1.25); }
            else if (e.key === '-' || e.key === '_') { setZoom(zoom / 1.25); }
            else if (e.key === '0') { resetView(); }
            else if (e.key === 'i' || e.key === 'I') { toggleInvert(); }
            else if (e.key === 'f' || e.key === 'F') { toggleFullscreen(); }
        });

        window.addEventListener('resize', applyView);
        document.addEventListener('fullscreenchange', applyView);
        defaultView();
    }

    if (images.length > 1) {
        var current = 0;

        var show = function (index) {
            if (index < 0) { index = images.length - 1; }
            if (index >= images.length) { index = 0; }
            current = index;

            images.forEach(function (img, i) {
                var active = i === current;
                img.hidden = !active;
                img.classList.toggle('is-active', active);
            });

            thumbs.forEach(function (btn, i) {
                var active = i === current;
                btn.classList.toggle('is-active', active);
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            if (counter) {
                counter.textContent = (current + 1) + ' / ' + images.length;
            }

            defaultView();
        };

        thumbs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = parseInt(this.dataset.index, 10);
                if (!isNaN(idx)) { show(idx); }
            });
        });

        if (prevBtn) { prevBtn.addEventListener('click', function () { show(current - 1); }); }
        if (nextBtn) { nextBtn.addEventListener('click', function () { show(current + 1); }); }

        document.addEventListener('keydown', function (e) {
            if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) {
                return;
            }
            if (e.key === 'ArrowLeft')  { show(current - 1); }
            if (e.key === 'ArrowRight') { show(current + 1); }
        });
    }

    /* =====================================================
       Completeness gate for the report.
       ===================================================== */

    var findingsEl = document.getElementById('findingsInput');
    var impressionEl = document.getElementById('interpretationInput');
    var completeBtn = document.getElementById('xvCompleteBtn');
    var noteEl = document.getElementById('xvReportNote');

    if (!findingsEl || !impressionEl || !completeBtn) {
        return;
    }

    var findingsHint = document.getElementById('findingsHint');
    var impressionHint = document.getElementById('interpretationHint');

    function hasContent(el) {
        return el.value.trim() !== '';
    }

    function refresh() {
        var okFindings = hasContent(findingsEl);
        var okImpression = hasContent(impressionEl);
        var okBoth = okFindings && okImpression;

        completeBtn.disabled = !okBoth;

        findingsEl.classList.toggle('is-missing', !okFindings);
        impressionEl.classList.toggle('is-missing', !okImpression);

        if (findingsHint) { findingsHint.hidden = okFindings; }
        if (impressionHint) { impressionHint.hidden = okImpression; }

        if (noteEl) {
            noteEl.textContent = okBoth
                ? 'Both sections are filled. You can complete this report or release it.'
                : 'Save draft to keep working on this report. Complete requires both Findings and Impression to be filled.';
        }
    }

    findingsEl.addEventListener('input', refresh);
    impressionEl.addEventListener('input', refresh);

    refresh();
})();
</script>

<?= $this->endSection() ?>