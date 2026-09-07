<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>System & Print Settings<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<div class="container-fluid p-0">
    <!-- Success/Error Alerts -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- ===== LEFT COLUMN: IP RESTRICTION & THEME PREFERENCE ===== -->
        <div class="col-lg-5">
            <!-- IP Restriction Settings Card -->
            <div class="card settings-card mb-4 shadow-sm border-0">
                <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="setting-icon-box bg-primary-subtle text-primary">
                            <i class="bi bi-shield-lock-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">IP Access Restriction</h5>
                            <small class="text-muted">Restrict Admin &amp; Staff access to specified IPs</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="<?= base_url('admin/settings/update-ip') ?>" method="POST">
                        <?= csrf_field() ?>
                        
                        <div class="form-check form-switch mb-4 p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <label class="form-check-label fw-bold text-dark mb-0 d-block" for="ipSwitch">
                                    Enable IP Address Restriction
                                </label>
                                <small class="text-muted">Block access from unauthorized IP addresses</small>
                            </div>
                            <input class="form-check-input fs-4 ms-0" type="checkbox" id="ipSwitch" name="ip_restriction_enabled" value="1" <?= ($settings['ip_restriction_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="allowedIps" class="form-label fw-semibold text-dark mb-0">Allowed IP Whitelist</label>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="addCurrentIp('<?= $clientIp ?>')">
                                    <i class="bi bi-plus-circle me-1"></i>Add My Current IP
                                </button>
                            </div>
                            <textarea class="form-control font-monospace text-dark border-secondary-subtle" id="allowedIps" name="allowed_ips" rows="4" placeholder="e.g. 127.0.0.1, ::1, 192.168.1.*"><?= esc($settings['allowed_ips'] ?? '') ?></textarea>
                            <div class="form-text small mt-2">
                                <i class="bi bi-info-circle me-1"></i>Separate IPs with commas. Supports exact IPs (<code>127.0.0.1</code>), wildcard patterns (<code>192.168.1.*</code>), or CIDR notation (<code>10.0.0.0/24</code>).
                            </div>
                        </div>

                        <div class="p-2 px-3 mb-3 bg-primary-subtle text-primary border border-primary-subtle rounded-3 d-flex align-items-center gap-2">
                            <i class="bi bi-display fs-5"></i>
                            <div>
                                <small class="d-block fw-semibold">Detected Client IP:</small>
                                <code class="fw-bold fs-6 text-primary"><?= esc($clientIp) ?></code>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i> Save Access Settings
                        </button>
                    </form>
                </div>
            </div>

            <!-- Appearance & Theme Card -->
            <div class="card settings-card shadow-sm border-0">
                <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center gap-2">
                    <div class="setting-icon-box bg-warning-subtle text-warning">
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
                                <small class="text-muted">Clean bright interface</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="theme-select-card p-3 text-center border rounded-3 cursor-pointer bg-dark text-white" onclick="switchThemeMode('dark')">
                                <i class="bi bi-moon-stars-fill text-info display-6 d-block mb-2"></i>
                                <span class="fw-bold text-white d-block">Night Mode</span>
                                <small class="text-light-50">Sleek dark theme</small>
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
                            <h5 class="mb-0 fw-bold">Print Layout Template Editor</h5>
                            <small class="text-muted">Customize medical reports and print layout template</small>
                        </div>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">Live Preview Enabled</span>
                </div>
                <div class="card-body p-4">
                    <form action="<?= base_url('admin/settings/save-print-template') ?>" method="POST">
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
                                <input type="text" class="form-control" id="footerNote" name="print_footer_note" value="<?= esc($settings['print_footer_note'] ?? 'This is a computer-generated medical report.') ?>" oninput="updateLivePreview()">
                            </div>
                        </div>

                        <!-- ===== LIVE PRINT TEMPLATE PREVIEW BOX ===== -->
                        <div class="mt-4">
                            <label class="form-label fw-semibold text-dark">Print Template Live Preview</label>
                            <div class="preview-paper-box p-4 border rounded-3 bg-white" id="printPreviewContainer">
                                <div class="text-center pb-3 mb-3 border-bottom" id="previewHeader" style="border-bottom-color: <?= esc($settings['print_accent_color'] ?? '#0148ca') ?> !important;">
                                    <h3 class="fw-bold mb-0" id="previewTitle" style="color: <?= esc($settings['print_accent_color'] ?? '#0148ca') ?>;"><?= esc($settings['print_header_title'] ?? 'PolyMedic') ?></h3>
                                    <p class="text-secondary small mb-1" id="previewSubtitle"><?= esc($settings['print_header_subtitle'] ?? 'Diagnostic & Laboratory Center') ?></p>
                                    <small class="text-muted" id="previewContact" style="font-size: 0.75rem;"><?= esc($settings['print_contact_info'] ?? 'Gov. Gutierrez Ave, Cotabato City 9600 | Tel: (064) 123-4567') ?></small>
                                </div>

                                <div class="py-2 text-center text-uppercase fw-bold small text-secondary tracking-wide mb-3">
                                    Medical Report Template Sample
                                </div>

                                <div class="bg-light p-3 rounded-2 small mb-3 border">
                                    <div class="row g-2">
                                        <div class="col-6"><strong>Patient:</strong> Jane Doe</div>
                                        <div class="col-6"><strong>Exam:</strong> Chest X-Ray (PA)</div>
                                        <div class="col-6"><strong>Age/Sex:</strong> 28 / Female</div>
                                        <div class="col-6"><strong>Date:</strong> <?= date('M d, Y') ?></div>
                                    </div>
                                </div>

                                <div class="p-3 bg-light-subtle rounded-2 small mb-3 border-start border-3" style="border-start-color: <?= esc($settings['print_accent_color'] ?? '#0148ca') ?> !important;">
                                    <div class="fw-bold mb-1" style="color: <?= esc($settings['print_accent_color'] ?? '#0148ca') ?>;">Findings &amp; Impression</div>
                                    <p class="mb-0 text-secondary">Lungs are clear. Heart size is normal. No focal infiltrates or pleural effusion noted.</p>
                                </div>

                                <div class="d-flex justify-content-between align-items-end pt-3 border-top mt-4 text-center small text-muted">
                                    <div>
                                        <div class="fw-bold text-dark">Prepared By:</div>
                                        <div class="text-secondary">Medical Staff</div>
                                    </div>
                                    <div style="width: 160px;">
                                        <div class="border-bottom border-dark pb-1 mb-1"></div>
                                        <div id="previewSignature" class="fw-semibold text-dark"><?= esc($settings['print_signature_title'] ?? 'Radiologist / Attending Physician') ?></div>
                                    </div>
                                </div>

                                <div class="text-center mt-3 text-muted" style="font-size: 0.7rem;" id="previewFooter">
                                    <?= esc($settings['print_footer_note'] ?? 'This is a computer-generated medical report.') ?>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success w-100 py-2.5 rounded-3 fw-semibold">
                                <i class="bi bi-save me-1"></i> Save Print Template Settings
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
    transition: all 0.25 ease;
}

.theme-select-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}

.preview-paper-box {
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
}
</style>

<script>
function addCurrentIp(ip) {
    const textarea = document.getElementById('allowedIps');
    let current = textarea.value.trim();
    if (current.includes(ip)) {
        alert('Your current IP (' + ip + ') is already in the whitelist.');
        return;
    }
    if (current.length > 0) {
        textarea.value = current + ', ' + ip;
    } else {
        textarea.value = ip;
    }
}

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
