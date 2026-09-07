<?= $this->extend('layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>Settings<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>

<div class="container-fluid p-0">
    <!-- Success/Error Alerts -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- ===== LEFT COLUMN: THEME PREFERENCE ===== -->
        <div class="col-lg-5">
            <div class="card settings-card shadow-sm border-0 mb-4">
                <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center gap-2">
                    <div class="setting-icon-box bg-purple-subtle text-purple">
                        <i class="bi bi-palette-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">Day &amp; Night Theme</h5>
                        <small class="text-muted">Set visual appearance preferences</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="theme-select-card p-3 text-center border rounded-3 cursor-pointer" onclick="switchThemeMode('light')">
                                <i class="bi bi-sun-fill text-warning display-6 d-block mb-2"></i>
                                <span class="fw-bold text-dark d-block">Day Mode</span>
                                <small class="text-muted">Bright interface</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="theme-select-card p-3 text-center border rounded-3 cursor-pointer bg-dark text-white" onclick="switchThemeMode('dark')">
                                <i class="bi bi-moon-stars-fill text-info display-6 d-block mb-2"></i>
                                <span class="fw-bold text-white d-block">Night Mode</span>
                                <small class="text-light-50">Dark theme</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== RIGHT COLUMN: PRINT LAYOUT TEMPLATE EDITOR ===== -->
        <div class="col-lg-7">
            <div class="card settings-card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="setting-icon-box bg-success-subtle text-success">
                            <i class="bi bi-printer-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">Radiology Report Print Template</h5>
                            <small class="text-muted">Customize X-Ray report header and print template</small>
                        </div>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">Live Preview</span>
                </div>
                <div class="card-body p-4">
                    <form action="<?= base_url('radiologist/settings/save-print-template') ?>" method="POST">
                        <?= csrf_field() ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="headerTitle" class="form-label fw-semibold text-dark">Facility / Header Title</label>
                                <input type="text" class="form-control" id="headerTitle" name="print_header_title" value="<?= esc($settings['print_header_title'] ?? 'PolyMedic') ?>" oninput="updateLivePreview()">
                            </div>
                            <div class="col-md-6">
                                <label for="headerSubtitle" class="form-label fw-semibold text-dark">Header Subtitle</label>
                                <input type="text" class="form-control" id="headerSubtitle" name="print_header_subtitle" value="<?= esc($settings['print_header_subtitle'] ?? 'Diagnostic & Laboratory Center') ?>" oninput="updateLivePreview()">
                            </div>
                            <div class="col-12">
                                <label for="contactInfo" class="form-label fw-semibold text-dark">Address &amp; Contact Info Line</label>
                                <input type="text" class="form-control" id="contactInfo" name="print_contact_info" value="<?= esc($settings['print_contact_info'] ?? 'Gov. Gutierrez Ave, Cotabato City 9600 | Tel: (064) 123-4567') ?>" oninput="updateLivePreview()">
                            </div>
                            <div class="col-md-6">
                                <label for="accentColor" class="form-label fw-semibold text-dark">Theme Accent Color</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="color" class="form-control form-control-color" id="accentColorPicker" value="<?= esc($settings['print_accent_color'] ?? '#0148ca') ?>" oninput="document.getElementById('accentColor').value = this.value; updateLivePreview()">
                                    <input type="text" class="form-control font-monospace" id="accentColor" name="print_accent_color" value="<?= esc($settings['print_accent_color'] ?? '#0148ca') ?>" oninput="document.getElementById('accentColorPicker').value = this.value; updateLivePreview()">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="layoutStyle" class="form-label fw-semibold text-dark">Template Style Preset</label>
                                <select class="form-select" id="layoutStyle" name="print_layout_style" onchange="updateLivePreview()">
                                    <option value="modern" <?= ($settings['print_layout_style'] ?? 'modern') === 'modern' ? 'selected' : '' ?>>Modern Medical (Clean Blue Accent)</option>
                                    <option value="classic" <?= ($settings['print_layout_style'] ?? '') === 'classic' ? 'selected' : '' ?>>Classic Serif (Minimalist Standard)</option>
                                    <option value="bordered" <?= ($settings['print_layout_style'] ?? '') === 'bordered' ? 'selected' : '' ?>>Bordered Official (Formal Hospital Box)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="signatureTitle" class="form-label fw-semibold text-dark">Signature Block Label</label>
                                <input type="text" class="form-control" id="signatureTitle" name="print_signature_title" value="<?= esc($settings['print_signature_title'] ?? 'Radiologist / Attending Physician') ?>" oninput="updateLivePreview()">
                            </div>
                            <div class="col-md-6">
                                <label for="footerNote" class="form-label fw-semibold text-dark">Footer Note / Disclaimer</label>
                                <input type="text" class="form-control" id="footerNote" name="print_footer_note" value="<?= esc($settings['print_footer_note'] ?? 'This is a computer-generated medical report. PolyMedic Diagnostic Center.') ?>" oninput="updateLivePreview()">
                            </div>
                        </div>

                        <!-- ===== LIVE PRINT TEMPLATE PREVIEW BOX ===== -->
                        <div class="mt-4">
                            <label class="form-label fw-semibold text-dark">X-Ray Report Live Preview</label>
                            <div class="preview-paper-box p-4 border rounded-3 bg-white" id="printPreviewContainer">
                                <div class="text-center pb-3 mb-3 border-bottom" id="previewHeader" style="border-bottom-color: <?= esc($settings['print_accent_color'] ?? '#0148ca') ?> !important;">
                                    <h3 class="fw-bold mb-0" id="previewTitle" style="color: <?= esc($settings['print_accent_color'] ?? '#0148ca') ?>;"><?= esc($settings['print_header_title'] ?? 'PolyMedic') ?></h3>
                                    <p class="text-secondary small mb-1" id="previewSubtitle"><?= esc($settings['print_header_subtitle'] ?? 'Diagnostic & Laboratory Center') ?></p>
                                    <small class="text-muted" id="previewContact" style="font-size: 0.75rem;"><?= esc($settings['print_contact_info'] ?? 'Gov. Gutierrez Ave, Cotabato City 9600 | Tel: (064) 123-4567') ?></small>
                                </div>

                                <div class="py-2 text-center text-uppercase fw-bold small text-secondary tracking-wide mb-3">
                                    X-Ray Examination Report
                                </div>

                                <div class="bg-light p-3 rounded-2 small mb-3 border">
                                    <div class="row g-2">
                                        <div class="col-6"><strong>Patient:</strong> John Doe</div>
                                        <div class="col-6"><strong>Exam Type:</strong> Chest X-Ray</div>
                                        <div class="col-6"><strong>Age/Sex:</strong> 35 / Male</div>
                                        <div class="col-6"><strong>Exam Date:</strong> <?= date('M d, Y') ?></div>
                                    </div>
                                </div>

                                <div class="p-3 bg-light-subtle rounded-2 small mb-3 border-start border-3" style="border-start-color: <?= esc($settings['print_accent_color'] ?? '#0148ca') ?> !important;">
                                    <div class="fw-bold mb-1" style="color: <?= esc($settings['print_accent_color'] ?? '#0148ca') ?>;">Findings &amp; Impression</div>
                                    <p class="mb-0 text-secondary">Both lungs are clear and well expanded. Heart and mediastinal contours are within normal limits.</p>
                                </div>

                                <div class="d-flex justify-content-between align-items-end pt-3 border-top mt-4 text-center small text-muted">
                                    <div>
                                        <div class="fw-bold text-dark">Prepared By:</div>
                                        <div class="text-secondary">Radiology Staff</div>
                                    </div>
                                    <div style="width: 160px;">
                                        <div class="border-bottom border-dark pb-1 mb-1"></div>
                                        <div id="previewSignature" class="fw-semibold text-dark"><?= esc($settings['print_signature_title'] ?? 'Radiologist / Attending Physician') ?></div>
                                    </div>
                                </div>

                                <div class="text-center mt-3 text-muted" style="font-size: 0.7rem;" id="previewFooter">
                                    <?= esc($settings['print_footer_note'] ?? 'This is a computer-generated medical report. PolyMedic Diagnostic Center.') ?>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success w-100 py-2.5 rounded-3 fw-semibold">
                                <i class="bi bi-save me-1"></i> Save Radiology Print Template
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.setting-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.theme-select-card {
    transition: all 0.25s ease;
}

.theme-select-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}
</style>

<script>
function updateLivePreview() {
    const title = document.getElementById('headerTitle').value || 'PolyMedic';
    const subtitle = document.getElementById('headerSubtitle').value || 'Diagnostic & Laboratory Center';
    const contact = document.getElementById('contactInfo').value || '';
    const color = document.getElementById('accentColor').value || '#0148ca';
    const sig = document.getElementById('signatureTitle').value || 'Radiologist / Attending Physician';
    const footer = document.getElementById('footerNote').value || '';

    document.getElementById('previewTitle').innerText = title;
    document.getElementById('previewTitle').style.color = color;
    document.getElementById('previewSubtitle').innerText = subtitle;
    document.getElementById('previewContact').innerText = contact;
    document.getElementById('previewHeader').style.borderBottomColor = color;
    document.getElementById('previewSignature').innerText = sig;
    document.getElementById('previewFooter').innerText = footer;
}

function switchThemeMode(theme) {
    if (typeof window.applyTheme === 'function') {
        window.applyTheme(theme);
    } else {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('polymedic_theme', theme);
    }
}
</script>

<?= $this->endSection() ?>
