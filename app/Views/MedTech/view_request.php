<?= $this->extend('layouts/MedTechLayout') ?>

<?= $this->section('pageTitle') ?>Laboratory Findings<?= $this->endSection() ?>

<?= $this->section('medtechContent') ?>

<div class="view-request-container">

    <!-- Success/Error Messages -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Validation Errors -->
    <?php if (session()->getFlashdata('validation_errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="bi bi-exclamation-triangle me-2"></i>Please fix the following:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach (session()->getFlashdata('validation_errors') as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ===== TOP FORM SECTION ===== -->
    <div class="top-form-section">
        <div class="row g-3">
            <!-- Patient -->
            <div class="col-md-6">
                <label class="form-label">Patient</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" value="<?= esc($request['patient_name']) ?>" readonly>
                </div>
            </div>
            
            <!-- Requesting Doctor -->
            <div class="col-md-6">
                <label class="form-label">Requesting Doctor</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                    <input type="text" class="form-control" value="<?= $request['doctor_name'] ?? 'N/A' ?>" readonly>
                </div>
            </div>
            
            <!-- Medical Technologist -->
            <div class="col-md-6">
                <label class="form-label">Medical Technologist</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" value="<?= session()->get('full_name') ?? 'Medical Technologist' ?>" readonly>
                </div>
            </div>
            
            <!-- Date -->
            <div class="col-md-6">
                <label class="form-label">Date</label>
                <input type="date" class="form-control" value="<?= date('Y-m-d', strtotime($request['request_date'])) ?>" readonly>
            </div>
            
            <!-- Report Template -->
            <div class="col-md-6">
                <label class="form-label">Report Template</label>
                <select class="form-select" id="templateSelect" onchange="loadTemplate(this.value)">
                    <option value="cbc">Complete Blood Count (CBC)</option>
                    <option value="urinalysis">Urinalysis</option>
                    <option value="blood_chemistry">Blood Chemistry</option>
                    <option value="lipid_profile">Lipid Profile</option>
                    <option value="thyroid">Thyroid Panel</option>
                </select>
            </div>
            
            <!-- Upload Image -->
            <div class="col-md-6 d-flex align-items-end">
                <form action="<?= base_url('medtech/request/upload/' . $request['id']) ?>" 
                      method="POST" 
                      enctype="multipart/form-data"
                      class="w-100">
                    <?= csrf_field() ?>
                    <div class="d-flex gap-2">
                        <input type="file" name="lab_image" accept="image/*" style="display:none;" id="imageUpload" onchange="document.getElementById('uploadSubmit').click()">
                        <button type="button" class="btn btn-outline-secondary w-100" onclick="document.getElementById('imageUpload').click()">
                            <i class="bi bi-upload"></i> Upload Image
                        </button>
                        <button type="submit" class="btn btn-primary-custom d-none" id="uploadSubmit">
                            <i class="bi bi-check"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons-row mt-3 d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary-custom" onclick="generatePDF()">
                    <i class="bi bi-download"></i> Generate PDF
                </button>
                <button type="button" class="btn btn-outline-custom" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
            <div class="d-flex gap-2">
                <?php if ($request['status'] !== 'released'): ?>
                    <button type="button" class="btn btn-success-custom" onclick="releaseResult(<?= $request['id'] ?>)">
                        <i class="bi bi-check-circle"></i> Release Result
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== REPORT PREVIEW / EDIT SECTION ===== -->
    <div class="report-preview-section mt-4">
        <div class="report-preview-header">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-text"></i>
                <span>Report Preview</span>
            </div>
            <span class="badge-draft"><?= ucfirst(str_replace('_', ' ', $request['status'])) ?></span>
        </div>
        
        <div class="report-preview-body">
            <!-- PolyMedic Header -->
            <div class="polymedic-header">
                <div class="d-flex align-items-start gap-3">
                    <div class="polymedic-logo">
                        <i class="bi bi-clipboard2-pulse"></i>
                    </div>
                    <div class="polymedic-info">
                        <h3>POLYMEDIC DIAGNOSTIC CENTER</h3>
                        <p>123 Medical Drive, Makati City, Metro Manila</p>
                        <p>Tel: (02) 8888-1234 · Email: lab@polymedic.ph</p>
                        <p>LTO No.: 4A-2024-0321 · PhilHealth Accredited</p>
                    </div>
                </div>
                <div class="lab-result-header text-end">
                    <h4>LABORATORY RESULT</h4>
                    <p>Ref No.: LAB-2026-<?= str_pad($request['id'], 4, '0', STR_PAD_LEFT) ?></p>
                    <p>Date: <?= date('F d, Y', strtotime($request['request_date'])) ?></p>
                </div>
            </div>
            
            <div class="divider-line"></div>
            
            <!-- Patient Details -->
            <div class="patient-details-row">
                <div class="row">
                    <div class="col-md-3">
                        <p class="detail-label">Patient Name:</p>
                        <p class="detail-value"><?= esc($request['patient_name']) ?></p>
                    </div>
                    <div class="col-md-3">
                        <p class="detail-label">Requesting MD:</p>
                        <p class="detail-value"><?= $request['doctor_name'] ?? 'N/A' ?></p>
                    </div>
                    <div class="col-md-3">
                        <p class="detail-label">Age / Sex:</p>
                        <p class="detail-value"><?= esc($request['age']) ?> / <?= esc($request['gender']) ?></p>
                    </div>
                    <div class="col-md-3">
                        <p class="detail-label">Status:</p>
                        <p class="detail-value"><?= ucfirst(str_replace('_', ' ', $request['status'])) ?></p>
                    </div>
                    <div class="col-md-3">
                        <p class="detail-label">Lab Services:</p>
                        <p class="detail-value"><?= esc($request['lab_services']) ?></p>
                    </div>
                    <div class="col-md-3">
                        <p class="detail-label">Date Collected:</p>
                        <p class="detail-value"><?= date('F d, Y', strtotime($request['request_date'])) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="divider-line"></div>
            
            <!-- Test Results Section -->
            <div class="test-results-section">
                <h5 class="test-section-title">LABORATORY RESULTS</h5>
                
                <?php if ($request['status'] === 'released'): ?>
                    <!-- VIEW ONLY MODE -->
                    <div class="table-responsive">
                        <table class="table lab-results-table">
                            <thead>
                                <tr>
                                    <th>Test</th>
                                    <th>Result</th>
                                    <th>Unit</th>
                                    <th>Reference Range</th>
                                    <th>Flag</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($results)): ?>
                                    <?php foreach ($results as $result): ?>
                                        <tr>
                                            <td><?= esc($result['test_name']) ?></td>
                                            <td class="<?= $result['flag'] === 'high' || $result['flag'] === 'low' ? 'text-danger' : '' ?>">
                                                <?= esc($result['result']) ?>
                                            </td>
                                            <td><?= esc($result['unit']) ?></td>
                                            <td><?= esc($result['reference_range']) ?></td>
                                            <td>
                                                <?php if ($result['flag'] === 'normal'): ?>
                                                    <span class="flag-text text-success">N</span>
                                                <?php elseif ($result['flag'] === 'high'): ?>
                                                    <span class="flag-text text-danger"><i class="bi bi-arrow-up"></i> H</span>
                                                <?php elseif ($result['flag'] === 'low'): ?>
                                                    <span class="flag-text text-danger"><i class="bi bi-arrow-down"></i> L</span>
                                                <?php elseif ($result['flag'] === 'critical'): ?>
                                                    <span class="flag-text text-danger"><i class="bi bi-exclamation-triangle"></i> C</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Remarks -->
                    <div class="remarks-box mt-3">
                        <span class="remarks-label">Remarks:</span>
                        <span class="remarks-text"><?= esc($request['remarks'] ?? 'No remarks') ?></span>
                    </div>
                    
                    <div class="divider-line"></div>
                    
                    <!-- Signatures -->
                    <div class="signatures-row mt-4">
                        <div class="row">
                            <div class="col-md-6 text-center">
                                <div class="signature-line"></div>
                                <p class="signature-name"><?= session()->get('full_name') ?? 'Medical Technologist' ?></p>
                                <p class="signature-role">Medical Technologist · Lic. No. 12345</p>
                            </div>
                            <div class="col-md-6 text-center">
                                <div class="signature-line"></div>
                                <p class="signature-name"><?= $request['doctor_name'] ?? 'Pathologist' ?></p>
                                <p class="signature-role">Pathologist · PRC No. 67890</p>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- EDIT MODE -->
                    <form action="<?= base_url('medtech/request/release-directly/' . $request['id']) ?>" method="POST" id="releaseForm">
                        <?= csrf_field() ?>
                        
                        <div class="table-responsive">
                            <table class="table lab-results-table" id="resultsTable">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;">Test</th>
                                        <th style="width: 15%;">Result <span class="text-danger">*</span></th>
                                        <th style="width: 15%;">Unit <span class="text-danger">*</span></th>
                                        <th style="width: 20%;">Reference Range <span class="text-danger">*</span></th>
                                        <th style="width: 15%;">Flag</th>
                                        <th style="width: 10%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="resultsBody">
                                    <?php if (!empty($results)): ?>
                                        <?php foreach ($results as $index => $result): ?>
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="test_name[]" value="<?= esc($result['test_name']) ?>" required>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm result-input" 
                                                           name="result[]" value="<?= esc($result['result']) ?>" required>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="unit[]" value="<?= esc($result['unit']) ?>" required>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="reference_range[]" value="<?= esc($result['reference_range']) ?>" required>
                                                </td>
                                                <td>
                                                    <select class="form-select form-select-sm" name="flag[]">
                                                        <option value="normal" <?= $result['flag'] === 'normal' ? 'selected' : '' ?>>Normal</option>
                                                        <option value="high" <?= $result['flag'] === 'high' ? 'selected' : '' ?>>High</option>
                                                        <option value="low" <?= $result['flag'] === 'low' ? 'selected' : '' ?>>Low</option>
                                                        <option value="critical" <?= $result['flag'] === 'critical' ? 'selected' : '' ?>>Critical</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr id="emptyRow">
                                            <td colspan="6" class="text-center text-muted py-3">
                                                No results yet. Select a template above to load tests.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addResultRow()">
                                <i class="bi bi-plus-circle"></i> Add Row
                            </button>
                        </div>

                        <!-- Findings & Remarks -->
                        <div class="findings-remarks-section mt-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Findings / Interpretation <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="findings" rows="4" 
                                                  placeholder="Enter laboratory findings..." required><?= $request['findings'] ?? '' ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Remarks</label>
                                        <textarea class="form-control" name="remarks" rows="4" 
                                                  placeholder="Enter any remarks..."><?= $request['remarks'] ?? '' ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons mt-3 d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-success-custom">
                                <i class="bi bi-check-all"></i> Release Directly
                            </button>
                            <button type="button" class="btn btn-primary-custom" onclick="saveResults()">
                                <i class="bi bi-save"></i> Save as Draft
                            </button>
                            <button type="button" class="btn btn-warning-custom" onclick="saveAndComplete(<?= $request['id'] ?>)">
                                <i class="bi bi-check-circle"></i> Complete
                            </button>
                            <?php if ($request['status'] === 'completed' || $request['status'] === 'draft'): ?>
                                <a href="<?= base_url('medtech/request/release/' . $request['id']) ?>" 
                                   class="btn btn-success-custom"
                                   onclick="return confirm('Release this result? This will make it available for printing.')">
                                    <i class="bi bi-check-all"></i> Release Result
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Separate form for findings only -->
                    <form action="<?= base_url('medtech/request/save-findings/' . $request['id']) ?>" method="POST" id="findingsForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" id="findingsAction" value="save">
                    </form>

                    <!-- Separate form for saving results only -->
                    <form action="<?= base_url('medtech/request/save-results/' . $request['id']) ?>" method="POST" id="saveResultsForm">
                        <?= csrf_field() ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== VIEW REQUEST CONTAINER ===== */
.view-request-container {
    padding: 0;
}

/* ===== TOP FORM SECTION ===== */
.top-form-section {
    background: #fcfcfc;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    border: 1px solid #e5e7eb;
    padding: 1.5rem;
}

.form-label {
    font-weight: 600;
    color: #374151;
    font-size: 0.85rem;
    margin-bottom: 0.4rem;
}

.input-group-text {
    background: #f0f7f8  !important;
    border: 1px solid #a8a8a8;
    border-right: none;
    color: #9096a0;
}

.form-select, 
.form-control {
    border: 1px solid #a8a8a8;
    border-radius: 8px;
    font-size: 0.9rem;
    padding: 0.65rem 1rem;
    color: #374151;
    background-color: #ffffff !important;
    height: 46px;
}

.form-select:focus,
.form-control:focus {
    background-color: #ffffff !important;
    border-color: #1976d2;
    box-shadow: 0 0 0 4px rgba(25, 118, 210, 0.1);
    outline: none;
}

.input-group .form-select,
.input-group .form-control {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.form-control[readonly] {
    background-color: #f0f7f8 !important;
    cursor: default;
}

.form-select:disabled {
    background-color: #eff0f1 !important;
    cursor: default;
}

/* Action Buttons */
.action-buttons-row {
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
}

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

.btn-outline-custom {
    background: #ffffff;
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

.btn-outline-secondary {
    background: #f9fafb !important;
    border: 1px solid #e5e7eb;
    color: #374151;
    padding: 0.65rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.85rem;
    transition: all 0.2s ease;
}

.btn-outline-secondary:hover {
    background: #ffffff !important;
    border-color: #9ca3af;
    color: #111827;
}

.btn-warning-custom {
    background: linear-gradient(135deg, #f59e0b, #d97706);
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
}

.btn-warning-custom:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    color: white;
}

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

/* ===== REPORT PREVIEW SECTION ===== */
.report-preview-section {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.report-preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.85rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}

.report-preview-header .d-flex {
    gap: 0.5rem;
    color: #374151;
    font-weight: 600;
    font-size: 0.9rem;
}

.report-preview-header .d-flex i {
    color: #1976d2;
}

.badge-draft {
    background: #fef3c7;
    color: #d97706;
    padding: 0.25rem 0.75rem;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
}

.report-preview-body {
    padding: 2rem 2.5rem;
}

/* PolyMedic Header */
.polymedic-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.polymedic-logo {
    width: 56px;
    height: 56px;
    background: #e3f2fd;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.polymedic-logo i {
    font-size: 1.8rem;
    color: #1976d2;
}

.polymedic-info h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1976d2;
    margin: 0 0 0.25rem;
    letter-spacing: 0.5px;
}

.polymedic-info p {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0.15rem 0;
    line-height: 1.4;
}

.lab-result-header h4 {
    font-size: 0.95rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 0.5rem;
    letter-spacing: 1px;
}

.lab-result-header p {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0.1rem 0;
}

.divider-line {
    height: 2px;
    background: linear-gradient(90deg, #1976d2, #42a5f5);
    margin: 1rem 0;
    border-radius: 2px;
}

/* Patient Details */
.patient-details-row {
    padding: 1rem 0;
}

.detail-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #6b7280;
    margin: 0 0 0.15rem;
}

.detail-value {
    font-size: 0.9rem;
    font-weight: 500;
    color: #111827;
    margin: 0;
}

/* Test Results Section */
.test-results-section {
    margin-top: 1rem;
}

.test-section-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1976d2;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.75rem;
}

.lab-results-table {
    width: 100%;
    border-collapse: collapse;
}

.lab-results-table thead th {
    background: #1976d2;
    color: #ffffff;
    padding: 0.75rem 1rem;
    font-size: 0.8rem;
    font-weight: 600;
    text-align: left;
    border: none;
}

.lab-results-table thead th:first-child {
    border-top-left-radius: 8px;
}

.lab-results-table thead th:last-child {
    border-top-right-radius: 8px;
}

.lab-results-table tbody td {
    padding: 0.75rem 1rem;
    font-size: 0.85rem;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
}

.lab-results-table tbody tr:last-child td {
    border-bottom: none;
}

.lab-results-table tbody tr:hover {
    background: #f8fafc;
}

.text-danger {
    color: #dc2626 !important;
    font-weight: 600;
}

.text-success {
    color: #16a34a !important;
}

/* Flag Text */
.flag-text {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-weight: 600;
    font-size: 0.8rem;
}

/* Remarks Box */
.remarks-box {
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.85rem;
    color: #92400e;
}

.remarks-label {
    font-weight: 700;
}

.remarks-text {
    font-style: italic;
}

/* Signatures */
.signatures-row {
    padding-top: 1rem;
}

.signature-line {
    border-top: 1px solid #d1d5db;
    width: 200px;
    margin: 0 auto 0.5rem;
}

.signature-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.signature-role {
    font-size: 0.75rem;
    color: #6b7280;
    margin: 0;
}

/* Form Controls inside table */
.form-control-sm,
.form-select-sm {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 0.8rem;
    padding: 0.4rem 0.6rem;
    background-color: #f9fafb !important;
}

.form-control-sm:focus,
.form-select-sm:focus {
    background-color: #ffffff !important;
    border-color: #1976d2;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
}

.text-danger {
    color: #dc3545 !important;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 992px) {
    .report-preview-body {
        padding: 1.5rem;
    }
    
    .polymedic-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .lab-result-header {
        text-align: left !important;
    }
}

@media (max-width: 768px) {
    .top-form-section {
        padding: 1rem;
    }
    
    .report-preview-body {
        padding: 1rem;
    }
    
    .polymedic-info h3 {
        font-size: 0.95rem;
    }
    
    .polymedic-info p {
        font-size: 0.7rem;
    }
    
    .lab-results-table thead th,
    .lab-results-table tbody td {
        padding: 0.5rem;
        font-size: 0.75rem;
    }
    
    .signatures-row .col-md-6 {
        margin-bottom: 1.5rem;
    }
    
    .action-buttons-row {
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
    }
    
    .action-buttons-row .d-flex {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .action-buttons-row .btn {
        width: 100%;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-buttons .btn {
        width: 100%;
    }
    
    .findings-remarks-section .row {
        flex-direction: column;
    }
}

@media (max-width: 576px) {
    .report-preview-body {
        padding: 0.75rem;
    }
    
    .polymedic-logo {
        width: 40px;
        height: 40px;
    }
    
    .polymedic-logo i {
        font-size: 1.4rem;
    }
    
    .polymedic-info h3 {
        font-size: 0.85rem;
    }
    
    .lab-results-table {
        font-size: 0.7rem;
    }
    
    .lab-results-table thead th,
    .lab-results-table tbody td {
        padding: 0.4rem;
        font-size: 0.7rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-calculate flags based on results
    document.querySelectorAll('.result-input').forEach(input => {
        input.addEventListener('change', function() {
            // You can add auto-flag logic here if needed
        });
    });
});

function addResultRow() {
    const tbody = document.getElementById('resultsBody');
    const emptyRow = document.getElementById('emptyRow');
    if (emptyRow) {
        emptyRow.remove();
    }
    
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <input type="text" class="form-control form-control-sm" name="test_name[]" placeholder="Enter test name" required>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm result-input" name="result[]" placeholder="Result" required>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="unit[]" placeholder="Unit" required>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="reference_range[]" placeholder="Range" required>
        </td>
        <td>
            <select class="form-select form-select-sm" name="flag[]">
                <option value="normal">Normal</option>
                <option value="high">High</option>
                <option value="low">Low</option>
                <option value="critical">Critical</option>
            </select>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
}

function loadTemplate(template) {
    const tbody = document.getElementById('resultsBody');
    const emptyRow = document.getElementById('emptyRow');
    if (emptyRow) {
        emptyRow.remove();
    }
    
    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                Loading template...
            </td>
        </tr>
    `;
    
    fetch('<?= base_url('medtech/template/') ?>' + template)
        .then(response => response.json())
        .then(data => {
            tbody.innerHTML = '';
            data.forEach(test => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <input type="text" class="form-control form-control-sm" name="test_name[]" value="${test.test_name}" required>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm result-input" name="result[]" placeholder="Result" required>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" name="unit[]" value="${test.unit || ''}" required>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" name="reference_range[]" value="${test.reference_range || ''}" required>
                    </td>
                    <td>
                        <select class="form-select form-select-sm" name="flag[]">
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="low">Low</option>
                            <option value="critical">Critical</option>
                        </select>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(err => {
            console.error('Error loading template:', err);
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">
                        Error loading template. Please try again.
                    </td>
                </tr>
            `;
        });
}

function generatePDF() {
    window.print();
}

function releaseResult(id) {
    if (confirm('Release this result? This will make it available for printing.')) {
        window.location.href = '<?= base_url('medtech/request/release/') ?>' + id;
    }
}

function saveAndComplete(id) {
    if (confirm('Mark this request as completed? This will save all findings.')) {
        document.getElementById('findingsAction').value = 'complete';
        document.getElementById('findingsForm').submit();
    }
}

function saveResults() {
    document.getElementById('saveResultsForm').submit();
}
</script>

<?= $this->endSection() ?>