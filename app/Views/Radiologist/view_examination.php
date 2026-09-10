<?= $this->extend('layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>View X-Ray Examination<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>

<?php
    $examination = $examination ?? [];

    $statusKey  = (string) ($examination['status'] ?? 'pending');
    $isReleased = $statusKey === 'released';
    $isEditable = !$isReleased;

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
                        <i class="bi bi-image" aria-hidden="true"></i>
                        Radiology image
                    </h5>

                    <?php if ($isEditable): ?>
                        <form action="<?= base_url('radiologist/examination/upload/' . (int) $examination['id']) ?>"
                              method="POST"
                              enctype="multipart/form-data"
                              class="xv-upload">
                            <?= csrf_field() ?>
                            <input type="file"
                                   name="xray_image"
                                   id="xrayUpload"
                                   accept="image/*"
                                   required
                                   hidden>
                            <button type="button" class="xv-btn xv-btn--sm" onclick="document.getElementById('xrayUpload').click()">
                                <i class="bi bi-upload" aria-hidden="true"></i>
                                <span id="uploadBtnLabel">Choose image</span>
                            </button>
                            <button type="submit" class="xv-btn xv-btn--sm xv-btn--primary" id="uploadSubmit" hidden>
                                <i class="bi bi-check2" aria-hidden="true"></i>
                                Upload
                            </button>
                        </form>
                    <?php endif; ?>
                </header>

                <div class="xv-viewer">
                    <?php if (!empty($examination['image_path'])): ?>
                        <img src="<?= base_url($examination['image_path']) ?>"
                             alt="Radiology image for <?= esc($examination['patient_name'] ?? '', 'attr') ?>"
                             class="xv-image">
                    <?php else: ?>
                        <div class="xv-viewer-empty">
                            <i class="bi bi-image" aria-hidden="true"></i>
                            <p>No image uploaded yet</p>
                            <small>Upload a JPEG or PNG to begin the read.</small>
                        </div>
                    <?php endif; ?>
                </div>
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

                <!-- Read-only report -->
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

                <!-- Editable report.
                     Save draft posts to saveDraft/{id}: saves whatever is
                     there and keeps the status at in_progress.
                     Save report posts to save/{id}: refuses unless both
                     fields have content, then sets status to completed.
                     Release is a separate link that only fires when the
                     report is already complete. -->
                <form action="<?= base_url('radiologist/examination/save/' . (int) $examination['id']) ?>"
                      method="POST"
                      id="xvReportForm"
                      novalidate>

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
                            <!-- Save draft: always enabled. Saves what is
                                 there and keeps status at in_progress. -->
                            <button type="submit"
                                    class="xv-btn"
                                    formaction="<?= base_url('radiologist/examination/save-draft/' . (int) $examination['id']) ?>">
                                <i class="bi bi-file-earmark" aria-hidden="true"></i>
                                Save draft
                            </button>

                            <!-- Save report / Complete: only fires when
                                 both fields have content. Server also
                                 validates so a crafted POST cannot
                                 bypass the rule. -->
                            <button type="submit"
                                    class="xv-btn xv-btn--primary"
                                    id="xvCompleteBtn"
                                    <?= $reportComplete ? '' : 'disabled' ?>>
                                <i class="bi bi-check2" aria-hidden="true"></i>
                                Save report
                            </button>

                            <?php if ($statusKey === 'completed'): ?>
                                <a href="<?= base_url('radiologist/examination/release/' . (int) $examination['id']) ?>"
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
   pages so this doesn't look like a different product.
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

.xv-viewer {
    display: grid;
    place-items: center;
    min-height: 380px;
    padding: 1rem;
    background: #0f172a;
}

.xv-image {
    max-width: 100%;
    max-height: 520px;
    display: block;
    border-radius: 6px;
}

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

/* A field that failed the completeness check while completing. */
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
       Upload button: show a confirm button once a file is chosen.
       ===================================================== */

    var fileInput = document.getElementById('xrayUpload');
    var uploadSubmit = document.getElementById('uploadSubmit');
    var uploadLabel = document.getElementById('uploadBtnLabel');

    if (fileInput && uploadSubmit) {
        fileInput.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                uploadSubmit.hidden = false;
                if (uploadLabel) {
                    uploadLabel.textContent = this.files[0].name;
                }
            } else {
                uploadSubmit.hidden = true;
                if (uploadLabel) {
                    uploadLabel.textContent = 'Choose image';
                }
            }
        });
    }

    /* =====================================================
       Completeness gate.
       Save report (and its release step) are only allowed when
       both Findings and Impression have content. The button
       disables itself the moment either field goes empty, so the
       radiologist cannot accidentally complete a half-written
       report. The server checks the same thing again, so a
       crafted POST cannot skip this rule.
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

        // Mark each field separately so the radiologist sees exactly
        // which one is missing, not just that something is.
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