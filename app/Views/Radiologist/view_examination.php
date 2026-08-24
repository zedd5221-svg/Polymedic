<?= $this->extend('layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>View X-Ray Examination<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>

<div class="view-examination-container">

    <!-- Back Button -->
    <div class="mb-3">
        <a href="<?= base_url('radiologist/examinations') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Examinations
        </a>
    </div>

    <!-- Success/Error Messages -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- ===== LEFT COLUMN - IMAGE VIEWER ===== -->
        <div class="col-lg-8">
            <div class="image-viewer-card">
                <div class="card-header-custom">
                    <h5><i class="bi bi-image"></i> Radiology Image Viewer</h5>
                    <div class="header-actions">
                        <?php if ($examination['status'] !== 'released'): ?>
                            <form action="<?= base_url('radiologist/examination/upload/' . $examination['id']) ?>" 
                                  method="POST" 
                                  enctype="multipart/form-data"
                                  class="d-inline">
                                <?= csrf_field() ?>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="file" class="form-control form-control-sm" name="xray_image" accept="image/*" required style="display:none;" id="xrayUpload">
                                    <button type="button" class="btn-upload" onclick="document.getElementById('xrayUpload').click()">
                                        <i class="bi bi-upload"></i> Upload Image
                                    </button>
                                    <button type="submit" class="btn-upload-submit d-none" id="uploadSubmit">
                                        <i class="bi bi-check"></i> Confirm
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                        <span class="badge-custom <?= $examination['status'] ?>">
                            <?= ucfirst($examination['status']) ?>
                        </span>
                    </div>
                </div>
                <div class="image-viewer-body">
                    <?php if (!empty($examination['image_path'])): ?>
                        <img src="<?= base_url($examination['image_path']) ?>" 
                             alt="X-Ray Image" 
                             class="xray-image">
                    <?php else: ?>
                        <div class="no-image-placeholder">
                            <div class="placeholder-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                                    <circle cx="12" cy="12" r="4"/>
                                    <circle cx="12" cy="12" r="1"/>
                                </svg>
                            </div>
                            <p>No image loaded. Upload a DICOM or image file.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ===== RIGHT COLUMN - PATIENT INFO ===== -->
        <div class="col-lg-4">
            <div class="patient-info-card">
                <div class="card-header-custom">
                    <h5><i class="bi bi-person"></i> Patient Information</h5>
                </div>
                <div class="patient-info-body">
                    <div class="info-row">
                        <span class="info-label">Patient</span>
                        <span class="info-value"><?= esc($examination['patient_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Patient ID</span>
                        <span class="info-value"><?= $appointment['reference_number'] ?? 'N/A' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Age / Sex</span>
                        <span class="info-value"><?= esc($examination['age']) ?> / <?= esc($examination['gender']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Doctor</span>
                        <span class="info-value"><?= $examination['doctor_name'] ?? 'N/A' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Radiologist</span>
                        <span class="info-value"><?= session()->get('full_name') ?? 'Dr. Maria Tan, MD' ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Exam Type</span>
                        <span class="info-value"><?= esc($examination['exam_type']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date</span>
                        <span class="info-value"><?= date('F d, Y', strtotime($examination['exam_date'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Priority</span>
                        <span class="info-value"><?= esc($examination['priority']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="status-badge <?= $examination['status'] ?>">
                                <?= ucfirst($examination['status']) ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FINDINGS & INTERPRETATION ===== -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="findings-card">
                <div class="card-header-custom">
                    <h5><i class="bi bi-clipboard2-pulse"></i> Radiologist Interpretation</h5>
                    <?php if ($examination['status'] === 'released'): ?>
                        <span class="badge-custom released">
                            <i class="bi bi-check-circle"></i> Released
                        </span>
                    <?php endif; ?>
                </div>
                <div class="findings-body">
                    <?php if ($examination['status'] === 'released'): ?>
                        <!-- View Mode -->
                        <div class="findings-view">
                            <div class="findings-section">
                                <h6>Findings</h6>
                                <p><?= nl2br(esc($examination['findings'])) ?></p>
                            </div>
                            <div class="findings-section">
                                <h6>Interpretation</h6>
                                <p><?= nl2br(esc($examination['interpretation'])) ?></p>
                            </div>
                            <div class="findings-section">
                                <h6>Released At</h6>
                                <p><?= date('F d, Y h:i A', strtotime($examination['released_at'])) ?></p>
                            </div>
                            <div class="action-buttons mt-3">
                                <a href="<?= base_url('radiologist/examination/print/' . $examination['id']) ?>" 
                                   class="btn btn-primary-custom" target="_blank">
                                    <i class="bi bi-download"></i> Generate PDF
                                </a>
                                <a href="<?= base_url('radiologist/examination/print/' . $examination['id']) ?>" 
                                   class="btn btn-outline-custom" target="_blank">
                                    <i class="bi bi-printer"></i> Print
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Edit Mode -->
                        <form action="<?= base_url('radiologist/examination/save/' . $examination['id']) ?>" 
                              method="POST">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <textarea class="form-control interpretation-textarea" name="findings" rows="6" 
                                          placeholder="Enter radiological interpretation and findings here..."><?= $examination['findings'] ?? '' ?></textarea>
                            </div>
                            <div class="action-buttons d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary-custom">
                                        <i class="bi bi-download"></i> Generate PDF
                                    </button>
                                    <a href="<?= base_url('radiologist/examination/print/' . $examination['id']) ?>" 
                                       class="btn btn-outline-custom" target="_blank">
                                        <i class="bi bi-printer"></i> Print
                                    </a>
                                </div>
                                <?php if ($examination['status'] === 'processing' || $examination['status'] === 'completed'): ?>
                                    <a href="<?= base_url('radiologist/examination/release/' . $examination['id']) ?>" 
                                       class="btn btn-success-custom"
                                       onclick="return confirm('Release this result? This will make it available for printing.')">
                                        <i class="bi bi-check-circle"></i> Release Result
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== VIEW EXAMINATION PAGE - MATCHING SCREENSHOT ===== */

/* Cards */
.image-viewer-card,
.patient-info-card,
.findings-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

/* Card Headers */
.card-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    background: #ffffff;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.card-header-custom h5 {
    font-weight: 600;
    color: #111827;
    margin: 0;
    font-size: 1rem;
}

.card-header-custom h5 i {
    margin-right: 0.5rem;
    color: #1976d2;
}

/* Header Actions */
.header-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Upload Button - Outlined Style */
.btn-upload {
    background: transparent;
    border: 1px solid #d1d5db;
    color: #374151;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-upload:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.btn-upload i {
    font-size: 0.9rem;
}

.btn-upload-submit {
    background: #1976d2;
    border: none;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

/* Badge */
.badge-custom {
    padding: 0.25rem 0.75rem;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;
}

.badge-custom.pending {
    background: #fff3e0;
    color: #ff6b00;
}

.badge-custom.processing {
    background: #e3f2fd;
    color: #1976d2;
}

.badge-custom.completed {
    background: #e8f5e9;
    color: #28a745;
}

.badge-custom.released {
    background: #ccfbf1;
    color: #0d9488;
}

/* ===== IMAGE VIEWER BODY ===== */
.image-viewer-body {
    padding: 1.5rem;
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
}

.xray-image {
    max-width: 100%;
    max-height: 500px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

/* No Image Placeholder - Dark Background */
.no-image-placeholder {
    text-align: center;
    background: #1e293b;
    border-radius: 12px;
    padding: 4rem 2rem;
    width: 100%;
    min-height: 350px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.placeholder-icon {
    margin-bottom: 1rem;
    color: #64748b;
}

.placeholder-icon svg {
    stroke: #64748b;
}

.no-image-placeholder p {
    color: #94a3b8;
    font-size: 0.9rem;
    margin: 0;
    max-width: 300px;
    line-height: 1.5;
}

/* ===== PATIENT INFO ===== */
.patient-info-body {
    padding: 1.25rem 1.5rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 0.8rem;
    color: #6b7280;
    font-weight: 500;
}

.info-value {
    font-size: 0.85rem;
    font-weight: 600;
    color: #111827;
    text-align: right;
}

/* ===== FINDINGS ===== */
.findings-body {
    padding: 1.5rem;
}

/* Interpretation Textarea */
.interpretation-textarea {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    font-size: 0.9rem;
    line-height: 1.6;
    color: #374151;
    background: #f9fafb;
    resize: vertical;
    min-height: 150px;
    transition: all 0.2s ease;
}

.interpretation-textarea:focus {
    outline: none;
    border-color: #1976d2;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.1);
}

.interpretation-textarea::placeholder {
    color: #9ca3af;
}

/* ===== ACTION BUTTONS ===== */
.action-buttons {
    margin-top: 1.25rem;
}

/* Primary Button (Generate PDF) */
.btn-primary-custom {
    background: #1976d2;
    border: none;
    color: white;
    padding: 0.65rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 2px 8px rgba(25, 118, 210, 0.2);
}

.btn-primary-custom:hover {
    background: #1565c0;
    box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
    transform: translateY(-1px);
    color: white;
}

/* Outline Button (Print) */
.btn-outline-custom {
    background: transparent;
    border: 1px solid #d1d5db;
    color: #374151;
    padding: 0.65rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-outline-custom:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
    color: #111827;
}

/* Success Button (Release Result) */
.btn-success-custom {
    background: #e8f5e9;
    border: 1px solid #a5d6a7;
    color: #2e7d32;
    padding: 0.65rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-success-custom:hover {
    background: #c8e6c9;
    color: #1b5e20;
}

/* Back Button */
.btn-outline-secondary {
    border: 1px solid #d1d5db;
    color: #374151;
    padding: 0.5rem 1.25rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
    background: white;
}

.btn-outline-secondary:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
    color: #111827;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
    .image-viewer-body {
        min-height: 300px;
    }
    
    .xray-image {
        max-height: 350px;
    }
    
    .no-image-placeholder {
        min-height: 250px;
        padding: 2rem 1rem;
    }
}

@media (max-width: 768px) {
    .card-header-custom {
        padding: 0.75rem 1rem;
        flex-direction: column;
        align-items: flex-start;
    }
    
    .header-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .image-viewer-body {
        min-height: 250px;
        padding: 1rem;
    }
    
    .xray-image {
        max-height: 280px;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.2rem;
    }
    
    .info-value {
        text-align: left;
    }
    
    .findings-body {
        padding: 1rem;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: stretch;
    }
    
    .action-buttons .d-flex {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .action-buttons .btn {
        width: 100%;
    }
}

@media (max-width: 576px) {
    .image-viewer-body {
        min-height: 200px;
        padding: 0.75rem;
    }
    
    .xray-image {
        max-height: 200px;
    }
    
    .no-image-placeholder {
        min-height: 200px;
        padding: 1.5rem 0.75rem;
    }
    
    .placeholder-icon svg {
        width: 60px;
        height: 60px;
    }
    
    .no-image-placeholder p {
        font-size: 0.8rem;
    }
    
    .badge-custom {
        font-size: 0.6rem;
        padding: 0.2rem 0.5rem;
    }
}
</style>

<script>
// Show confirm button when file is selected
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('xrayUpload');
    const submitBtn = document.getElementById('uploadSubmit');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                submitBtn.classList.remove('d-none');
            } else {
                submitBtn.classList.add('d-none');
            }
        });
    }
});
</script>

<?= $this->endSection() ?>