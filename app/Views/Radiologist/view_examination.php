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

    $radiologistName = trim((string) ($examination['radiologist_name'] ?? ''));
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

    $initialsOf = static function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return mb_strtoupper($first . $last);
    };
?>

<div class="xv">

    <!-- ===== TOP BAR ===== -->
    <div class="xv-topbar">
        <a href="<?= base_url('radiologist/examinations') ?>" class="xv-back">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>Back to examinations</span>
        </a>

        <span class="xv-status xv-status--<?= esc($status['tone'], 'attr') ?>">
            <?= esc($status['label']) ?>
        </span>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="xv-alert xv-alert--success" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <span><?= esc(session()->getFlashdata('success')) ?></span>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="xv-alert xv-alert--error" role="alert">
            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
    <?php endif; ?>

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

        <!-- ===== MAIN GRID ===== -->
        <div class="xv-grid">

            <!-- LEFT: IMAGE VIEWER -->
            <section class="xv-card xv-card--viewer">
                <header class="xv-card-head">
                    <h5 class="xv-card-title">
                        <i class="bi bi-images" aria-hidden="true"></i>
                        Radiology images
                        <?php if ($imageCount > 0): ?>
                            <span class="xv-count"><?= $imageCount ?> of <?= $maxImages ?></span>
                        <?php endif; ?>
                    </h5>

                    <?php if ($isEditable): ?>
                        <?php if ($slotsLeft > 0): ?>
                            <form action="<?= base_url('radiologist/examination/upload/' . (int) $examination['id']) ?>"
                                  method="POST"
                                  enctype="multipart/form-data"
                                  class="xv-upload">
                                <?= csrf_field() ?>
                                <input type="file"
                                       name="xray_images[]"
                                       id="xrayUpload"
                                       accept="image/jpeg,image/png,image/gif,image/webp"
                                       multiple
                                       hidden>
                                <button type="button" class="xv-btn xv-btn--sm" onclick="document.getElementById('xrayUpload').click()">
                                    <i class="bi bi-upload" aria-hidden="true"></i>
                                    <span id="uploadBtnLabel">
                                        <?= $imageCount > 0 ? 'Add images' : 'Choose images' ?>
                                    </span>
                                </button>
                                <button type="submit" class="xv-btn xv-btn--sm xv-btn--primary" id="uploadSubmit" hidden>
                                    <i class="bi bi-check2" aria-hidden="true"></i>
                                    Upload
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="xv-cap-note">
                                Maximum of <?= $maxImages ?> images reached.
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </header>

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
                        <span class="xv-hint-keys">Ctrl + scroll to zoom · drag to pan · 0 = reset to 100%</span>

                        <?php foreach ($imagePaths as $i => $path): ?>
                            <img src="<?= base_url($path) ?>"
                                 alt="Radiology image <?= (int) ($i + 1) ?> for <?= esc($examination['patient_name'] ?? '', 'attr') ?>"
                                 class="xv-image<?= $i === 0 ? ' is-active' : '' ?>"
                                 data-index="<?= $i ?>"
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
                                <?php if ($isEditable && $isPaid): ?>
                                    <form action="<?= base_url('radiologist/examination/remove-image/' . (int) $examination['id']) ?>"
                                          method="POST"
                                          class="xv-thumb-form"
                                          onsubmit="return confirm('Remove this image? This cannot be undone.');">
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
                            <i class="bi bi-image" aria-hidden="true"></i>
                            <p>No images uploaded yet</p>
                            <small>Upload up to <?= $maxImages ?> JPEG, PNG, GIF, or WEBP files to begin the read.</small>
                        </div>
                    </div>

                <?php endif; ?>
            </section>

            <!-- RIGHT: PATIENT -->
            <aside class="xv-card xv-card--patient">
                <header class="xv-card-head">
                    <h5 class="xv-card-title">
                        <i class="bi bi-person" aria-hidden="true"></i>
                        Patient
                    </h5>
                </header>

                <div class="xv-patient">
                    <span class="xv-avatar" aria-hidden="true"><?= esc($initialsOf($examination['patient_name'] ?? '')) ?></span>
                    <div class="xv-patient-body">
                        <h3 class="xv-patient-name"><?= esc($examination['patient_name'] ?? 'Unknown') ?></h3>
                        <p class="xv-patient-sub">
                            <?= esc($examination['age'] ?? '—') ?> yrs
                            <span aria-hidden="true">·</span>
                            <?= esc($examination['gender'] ?? '—') ?>
                        </p>
                    </div>
                </div>

                <dl class="xv-facts">
                    <div>
                        <dt>Accession</dt>
                        <dd class="xv-mono">XR-<?= esc(date('y')) ?>-<?= esc(str_pad((string) ($examination['id'] ?? 0), 4, '0', STR_PAD_LEFT)) ?></dd>
                    </div>
                    <div>
                        <dt>Exam type</dt>
                        <dd><?= esc($examination['exam_type'] ?? '—') ?></dd>
                    </div>
                    <div>
                        <dt>Exam date</dt>
                        <dd><?= !empty($examination['exam_date']) ? esc(date('M j, Y', strtotime($examination['exam_date']))) : '—' ?></dd>
                    </div>
                    <div>
                        <dt>Priority</dt>
                        <dd>
                            <?php if (strtolower((string) ($examination['priority'] ?? '')) === 'stat'): ?>
                                <span class="xv-tag xv-tag--stat">STAT</span>
                            <?php else: ?>
                                Routine
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt>Referred by</dt>
                        <dd><?= esc($examination['doctor_name'] ?? '—') ?></dd>
                    </div>
                    <div>
                        <dt>Radiologist</dt>
                        <dd><?= $radiologistName !== '' ? esc($radiologistName) : '—' ?></dd>
                    </div>
                    <?php if ($isReleased && !empty($examination['released_at'])): ?>
                        <div>
                            <dt>Released</dt>
                            <dd><?= esc(date('M j, Y g:i A', strtotime($examination['released_at']))) ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </aside>
        </div>


        <!-- ===== REPORT ===== -->
        <section class="xv-card xv-card--report">
            <header class="xv-card-head">
                <h5 class="xv-card-title">
                    <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>
                    Radiologist report
                </h5>

                <?php if ($isReleased): ?>
                    <div class="xv-report-actions">
                        <a href="<?= base_url('radiologist/examination/print/' . (int) $examination['id']) ?>"
                           class="xv-btn xv-btn--sm"
                           target="_blank">
                            <i class="bi bi-printer" aria-hidden="true"></i>
                            Print
                        </a>
                    </div>
                <?php endif; ?>
            </header>

            <?php if ($isReleased): ?>

                <div class="xv-report">
                    <div class="xv-report-section">
                        <h6 class="xv-report-label">Findings</h6>
                        <div class="xv-report-body">
                            <?= trim($findings) !== ''
                                ? nl2br(esc($findings))
                                : '<span class="xv-muted">Not recorded.</span>' ?>
                        </div>
                    </div>

                    <div class="xv-report-section">
                        <h6 class="xv-report-label">Impression</h6>
                        <div class="xv-report-body">
                            <?= trim($interpretation) !== ''
                                ? nl2br(esc($interpretation))
                                : '<span class="xv-muted">Not recorded.</span>' ?>
                        </div>
                    </div>
                </div>

            <?php else: ?>

                <?php if (!$isPaid): ?>
                    <div class="xv-alert xv-alert--error" role="alert" style="margin:1rem 1.25rem 0;">
                        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                        <span>
                            <strong>This study has not been paid yet.</strong>
                            Ask the receptionist to click <strong>Start</strong>
                            on the diagnostic-requests page before you write the report.
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
                            <p class="xv-report-hint">
                                Describe what you observe on the image. Be specific: laterality, size, position, character of any abnormality.
                            </p>
                            <textarea class="xv-textarea"
                                      id="findingsInput"
                                      name="findings"
                                      rows="8"
                                      data-required="1"
                                      placeholder="e.g. The lungs are clear. No focal consolidation, pleural effusion or pneumothorax. Cardiac silhouette is within normal limits."><?= esc($findings) ?></textarea>
                            <p class="xv-field-hint" id="findingsHint" hidden>Findings cannot be empty when completing the report.</p>
                        </div>

                        <div class="xv-report-section">
                            <label class="xv-report-label" for="interpretationInput">Impression</label>
                            <p class="xv-report-hint">
                                State the conclusion. One or two lines is usually enough.
                            </p>
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
                        <div class="xv-report-note" id="xvReportNote">
                            <?php if ($reportComplete): ?>
                                Both sections are filled. You can complete this report or release it.
                            <?php else: ?>
                                Save draft to keep working on this report. Complete requires both Findings and Impression to be filled.
                            <?php endif; ?>
                        </div>

                        <div class="xv-report-buttons">
                            <button type="submit"
                                    class="xv-btn"
                                    formaction="<?= base_url('radiologist/examination/save-draft/' . (int) $examination['id']) ?>">
                                <i class="bi bi-file-earmark" aria-hidden="true"></i>
                                Save draft
                            </button>

                            <button type="submit"
                                    class="xv-btn xv-btn--primary"
                                    id="xvCompleteBtn"
                                    <?= $reportComplete ? '' : 'disabled' ?>>
                                <i class="bi bi-check2" aria-hidden="true"></i>
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

    <?php endif; ?>

</div>

<style>
/* =========================================================
   RADIOLOGIST · VIEW EXAMINATION
   Namespaced under .xv. Visual tokens match the receptionist
   pages so this does not look like a different product.
   ========================================================= */

.xv {
    --xv-ink:         #0f172a;
    --xv-text:        #334155;
    --xv-muted:       #64748b;
    --xv-faint:       #94a3b8;
    --xv-line:        #e2e8f0;
    --xv-line-soft:   #f1f5f9;
    --xv-surface:     #ffffff;
    --xv-subtle:      #f8fafc;
    --xv-accent:      #1d4ed8;
    --xv-accent-dark: #1e40af;
    --xv-accent-soft: #eaf2fe;
    --xv-success:     #047857;
    --xv-success-soft:#ecfdf5;
    --xv-danger:      #b91c1c;
    --xv-danger-soft: #fef2f2;
    --xv-amber:       #b45309;
    --xv-amber-soft:  #fff4e5;
    --xv-radius:      12px;
    --xv-radius-sm:   8px;
    --xv-mono:        ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;

    color: var(--xv-text);
    font-size: 0.875rem;
}

.xv *:focus-visible { outline: 2px solid var(--xv-accent); outline-offset: 2px; }

.xv-muted { color: var(--xv-faint); }
.xv-mono { font-family: var(--xv-mono); font-weight: 600; font-size: 0.78rem; }

/* ---------- top bar ---------- */

.xv-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.xv-back {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    height: 34px;
    padding: 0 0.85rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--xv-text);
    background: var(--xv-surface);
    border: 1px solid var(--xv-line);
    border-radius: var(--xv-radius-sm);
    text-decoration: none;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.xv-back:hover {
    background: var(--xv-subtle);
    border-color: #cbd5e1;
    color: var(--xv-ink);
}

.xv-back i { font-size: 0.9em; }

.xv-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.25rem 0.7rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 999px;
    white-space: nowrap;
    color: var(--xv-tone-fg);
    background: var(--xv-tone-bg);
}

.xv-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--xv-tone-dot);
}

.xv-status--pending   { --xv-tone-bg: var(--xv-amber-soft); --xv-tone-fg: var(--xv-amber); --xv-tone-dot: #f59e0b; }
.xv-status--progress  { --xv-tone-bg: var(--xv-accent-soft); --xv-tone-fg: var(--xv-accent); --xv-tone-dot: #3b82f6; }
.xv-status--completed { --xv-tone-bg: var(--xv-success-soft); --xv-tone-fg: var(--xv-success); --xv-tone-dot: #10b981; }
.xv-status--released  { --xv-tone-bg: #f0fdfa; --xv-tone-fg: #0f766e; --xv-tone-dot: #14b8a6; }

/* ---------- alerts ---------- */

.xv-alert {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.7rem 0.9rem;
    margin-bottom: 0.85rem;
    font-size: 0.8125rem;
    border: 1px solid transparent;
    border-radius: var(--xv-radius-sm);
}

.xv-alert--success { background: var(--xv-success-soft); border-color: #a7f3d0; color: var(--xv-success); }
.xv-alert--error   { background: var(--xv-danger-soft);  border-color: #fecaca; color: var(--xv-danger); }

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
    color: var(--xv-text);
    background: var(--xv-surface);
    border: 1px solid var(--xv-line);
    border-radius: var(--xv-radius-sm);
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.xv-btn:hover:not(:disabled) { background: var(--xv-subtle); border-color: #cbd5e1; color: var(--xv-ink); }
.xv-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.xv-btn i { font-size: 0.9em; }

.xv-btn--sm { height: 32px; padding: 0 0.7rem; font-size: 0.78rem; }

.xv-btn--primary,
.xv-btn--primary:hover:not(:disabled) { color: #ffffff; }
.xv-btn--primary { background: var(--xv-accent); border-color: var(--xv-accent); }
.xv-btn--primary:hover:not(:disabled) { background: var(--xv-accent-dark); border-color: var(--xv-accent-dark); }

.xv-btn--success,
.xv-btn--success:hover:not(:disabled) { color: #ffffff; }
.xv-btn--success { background: var(--xv-success); border-color: var(--xv-success); }
.xv-btn--success:hover:not(:disabled) { background: #065f46; border-color: #065f46; }

/* ---------- card ---------- */

.xv-card {
    background: var(--xv-surface);
    border: 1px solid var(--xv-line);
    border-radius: var(--xv-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

.xv-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding: 0.9rem 1.1rem;
    border-bottom: 1px solid var(--xv-line-soft);
}

.xv-card-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 0.9rem;
    font-weight: 650;
    color: var(--xv-ink);
}

.xv-card-title i { color: var(--xv-accent); font-size: 0.9rem; }

.xv-count {
    margin-left: 0.35rem;
    padding: 0.15rem 0.45rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--xv-muted);
    background: var(--xv-line-soft);
    border-radius: 999px;
}

.xv-cap-note {
    font-size: 0.75rem;
    color: var(--xv-muted);
}

/* ---------- grid ---------- */

.xv-grid {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
    align-items: start;
}

/* ---------- viewer ---------- */

.xv-upload {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

/* Properly sized viewer so the image has room to grow */
.xv-viewer {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 550px;
    padding: 1rem;
    background: #0f172a;
    overflow: hidden;
}

/* With images: original holder size, plus the reading tools. */
.xv-viewer--images {
    padding: 3rem 1rem 2.5rem;
    user-select: none;
    touch-action: none;
}

/* Let the image take up space naturally before JS scales it */
.xv-image {
    max-width: 90%;
    max-height: 550px;
    display: block;
    border-radius: 6px;
}
.xv-viewer--images .xv-image {
    transform-origin: center center;
    transition: filter .15s ease;
    will-change: transform;
}
.xv-viewer--images.is-zoomed            { cursor: grab; }
.xv-viewer--images.is-zoomed.is-panning { cursor: grabbing; }
.xv-viewer--images.is-inverted .xv-image { filter: invert(1); }

/* Fullscreen: the image fills the screen */
.xv-viewer--images:fullscreen { height: 100vh; width: 100vw; padding: 0; background: #05080f; }
.xv-viewer--images:fullscreen .xv-image {
    width: 100%;
    height: 100%;
    max-width: none;
    max-height: none;
    object-fit: contain;
    border-radius: 0;
}

/* Reading tools, top-left over the image */
.xv-tools {
    position: absolute;
    top: .75rem;
    left: .75rem;
    z-index: 2;
    display: inline-flex;
    align-items: center;
    gap: 2px;
    padding: 3px;
    background: rgba(15, 23, 42, .72);
    border: 1px solid rgba(226, 232, 240, .14);
    border-radius: 10px;
    backdrop-filter: blur(4px);
}
.xv-tool {
    min-width: 32px;
    height: 30px;
    padding: 0 .45rem;
    display: inline-grid;
    place-items: center;
    color: #e2e8f0;
    background: transparent;
    border: 0;
    border-radius: 7px;
    font-size: .8rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    cursor: pointer;
}
.xv-tool:hover { background: rgba(226, 232, 240, .12); }
.xv-tool:focus-visible { outline: 2px solid #60a5fa; outline-offset: 1px; }
.xv-tool[aria-pressed="true"] { background: rgba(96, 165, 250, .25); color: #fff; }
.xv-tool-sep { width: 1px; height: 18px; margin: 0 2px; background: rgba(226, 232, 240, .18); }
.xv-tool--zoom { min-width: 52px; }

.xv-hint-keys {
    position: absolute;
    left: .75rem;
    bottom: .75rem;
    z-index: 2;
    padding: .2rem .55rem;
    font-size: .7rem;
    color: #cbd5e1;
    background: rgba(15, 23, 42, .6);
    border-radius: 999px;
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
    color: #e2e8f0;
    background: rgba(15, 23, 42, 0.55);
    border: 1px solid rgba(226, 232, 240, 0.15);
    border-radius: 50%;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease;
}

.xv-nav:hover {
    background: rgba(15, 23, 42, 0.85);
    border-color: rgba(226, 232, 240, 0.3);
}

.xv-nav--prev { left: 0.75rem; }
.xv-nav--next { right: 0.75rem; }

.xv-counter {
    position: absolute;
    bottom: 0.75rem;
    right: 0.75rem;
    padding: 0.2rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: #e2e8f0;
    background: rgba(15, 23, 42, 0.65);
    border-radius: 999px;
    font-variant-numeric: tabular-nums;
}

.xv-thumbs {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.1rem;
    overflow-x: auto;
    background: var(--xv-subtle);
    border-top: 1px solid var(--xv-line-soft);
}

.xv-thumb-form {
    position: relative;
    display: inline-flex;
    margin: 0;
}

.xv-thumb {
    width: 64px;
    height: 64px;
    padding: 0;
    overflow: hidden;
    background: #ffffff;
    border: 2px solid var(--xv-line);
    border-radius: 6px;
    cursor: pointer;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.xv-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.xv-thumb:hover { border-color: #cbd5e1; }

.xv-thumb.is-active {
    border-color: var(--xv-accent);
    box-shadow: 0 0 0 2px rgba(29, 78, 216, 0.18);
}

.xv-thumb-remove {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 20px;
    height: 20px;
    display: grid;
    place-items: center;
    color: #ffffff;
    background: var(--xv-danger);
    border: 2px solid #ffffff;
    border-radius: 50%;
    cursor: pointer;
    font-size: 0.7rem;
    line-height: 1;
    padding: 0;
    transition: background-color 0.15s ease;
}

.xv-thumb-remove:hover { background: #7f1d1d; }

.xv-viewer-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    padding: 2.5rem 1.5rem;
    color: #94a3b8;
    text-align: center;
}

.xv-viewer-empty i { font-size: 2rem; color: #64748b; }
.xv-viewer-empty p { margin: 0; font-size: 0.875rem; color: #cbd5e1; }
.xv-viewer-empty small { font-size: 0.75rem; color: #94a3b8; }

/* ---------- patient ---------- */

.xv-patient {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.1rem 0.75rem;
    border-bottom: 1px solid var(--xv-line-soft);
}

.xv-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--xv-accent);
    background: var(--xv-accent-soft);
    border-radius: 50%;
}

.xv-patient-body { min-width: 0; }

.xv-patient-name {
    margin: 0;
    font-size: 1rem;
    font-weight: 650;
    color: var(--xv-ink);
    line-height: 1.3;
    overflow-wrap: anywhere;
}

.xv-patient-sub {
    margin: 0.15rem 0 0;
    font-size: 0.78rem;
    color: var(--xv-muted);
}

.xv-patient-sub span { margin: 0 0.15rem; color: var(--xv-faint); }

.xv-facts {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0;
    margin: 0;
    padding: 0.35rem 1.1rem 1rem;
}

.xv-facts > div {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--xv-line-soft);
}

.xv-facts > div:last-child { border-bottom: 0; }

.xv-facts dt {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--xv-faint);
    margin: 0;
    flex-shrink: 0;
}

.xv-facts dd {
    margin: 0;
    font-size: 0.84rem;
    font-weight: 500;
    color: var(--xv-ink);
    text-align: right;
    overflow-wrap: anywhere;
}

.xv-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.15rem 0.5rem;
    font-size: 0.7rem;
    font-weight: 700;
    border-radius: 4px;
    letter-spacing: 0.04em;
}

.xv-tag--stat { color: var(--xv-danger); background: var(--xv-danger-soft); }

/* ---------- report ---------- */

.xv-card--report { margin-bottom: 1rem; }

.xv-report { padding: 1.1rem 1.25rem 0.25rem; }

.xv-report-section { margin-bottom: 1.35rem; }
.xv-report-section:last-child { margin-bottom: 0.75rem; }

.xv-report-label {
    display: block;
    margin: 0 0 0.35rem;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--xv-ink);
}

.xv-report-hint {
    margin: 0 0 0.55rem;
    font-size: 0.78rem;
    color: var(--xv-muted);
    line-height: 1.5;
}

.xv-report-body {
    padding: 0.85rem 1rem;
    font-size: 0.86rem;
    line-height: 1.65;
    color: var(--xv-text);
    background: var(--xv-subtle);
    border: 1px solid var(--xv-line-soft);
    border-radius: var(--xv-radius-sm);
}

.xv-textarea {
    display: block;
    width: 100%;
    min-height: 140px;
    padding: 0.85rem 1rem;
    font-family: inherit;
    font-size: 0.875rem;
    line-height: 1.6;
    color: var(--xv-ink);
    background: var(--xv-surface);
    border: 1px solid var(--xv-line);
    border-radius: var(--xv-radius-sm);
    resize: vertical;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.xv-textarea::placeholder { color: var(--xv-faint); }

.xv-textarea:focus {
    outline: none;
    border-color: var(--xv-accent);
    box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
}

.xv-textarea.is-missing {
    border-color: var(--xv-danger);
    box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.12);
}

.xv-field-hint {
    margin: 0.45rem 0 0;
    font-size: 0.75rem;
    color: var(--xv-danger);
}

.xv-report-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 0.85rem 1.25rem;
    margin-top: 0.5rem;
    background: var(--xv-subtle);
    border-top: 1px solid var(--xv-line-soft);
}

.xv-report-note {
    max-width: 32rem;
    font-size: 0.78rem;
    color: var(--xv-muted);
    line-height: 1.5;
}

.xv-report-buttons {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.xv-report-actions { display: inline-flex; align-items: center; gap: 0.4rem; }

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
    width: 46px;
    height: 46px;
    margin: 0 auto 0.85rem;
    font-size: 1.2rem;
    color: var(--xv-faint);
    background: var(--xv-line-soft);
    border-radius: 10px;
}

.xv-empty h3 { margin: 0 0 0.25rem; font-size: 0.95rem; font-weight: 600; color: var(--xv-ink); }
.xv-empty p { max-width: 26rem; margin: 0 auto 1rem; font-size: 0.8125rem; color: var(--xv-muted); }

/* ---------- responsive ---------- */

@media (max-width: 1000px) {
    .xv-grid { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 640px) {
    .xv-viewer { min-height: 240px; }
    .xv-hint-keys { display: none; }

    .xv-nav { width: 30px; height: 30px; }
    .xv-nav--prev { left: 0.5rem; }
    .xv-nav--next { right: 0.5rem; }

    .xv-thumb { width: 52px; height: 52px; }

    .xv-report-foot { flex-direction: column; align-items: stretch; }
    .xv-report-buttons { width: 100%; }
    .xv-report-buttons .xv-btn { flex: 1; justify-content: center; }
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

    var fileInput = document.getElementById('xrayUpload');
    var uploadSubmit = document.getElementById('uploadSubmit');
    var uploadLabel = document.getElementById('uploadBtnLabel');

    if (fileInput && uploadSubmit) {
        fileInput.addEventListener('change', function () {
            var n = this.files ? this.files.length : 0;

            if (n > 0) {
                uploadSubmit.hidden = false;
                if (uploadLabel) {
                    uploadLabel.textContent = n === 1
                        ? this.files[0].name
                        : n + ' files selected';
                }
            } else {
                uploadSubmit.hidden = true;
                if (uploadLabel) {
                    uploadLabel.textContent = 'Choose images';
                }
            }
        });
    }

    /* =====================================================
       Keep the report text across image uploads / removals.
       Those are separate forms that reload the page, so the
       current Findings / Impression go along with them and
       the server saves them as a draft first.
       ===================================================== */

    var findingsInput       = document.getElementById('findingsInput');
    var interpretationInput = document.getElementById('interpretationInput');

    document.querySelectorAll('form.xv-upload, form.xv-thumb-form').forEach(function (form) {
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