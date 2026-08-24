<?= $this->extend('layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>Examinations<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>


<div class="examinations-container">
    <!-- Header -->
    <div class="page-header">
        <div>
            <h4 class="page-title">X-Ray Examinations</h4>
            <p class="page-subtitle">Manage all X-Ray examinations</p>
        </div>
        <div>
            <span class="badge-custom-purple">
                <i class="bi bi-x-ray me-1"></i> <?= $counts['total'] ?? 0 ?> Total
            </span>
        </div>
    </div>

    <!-- Status Filter -->
    <div class="filter-tabs">
        <a href="<?= base_url('radiologist/examinations') ?>" 
           class="filter-tab <?= empty($currentStatus) ? 'active' : '' ?>">
            All (<?= $counts['total'] ?? 0 ?>)
        </a>
        <a href="<?= base_url('radiologist/examinations?status=pending') ?>" 
           class="filter-tab <?= $currentStatus === 'pending' ? 'active' : '' ?>">
            Pending (<?= $counts['pending'] ?? 0 ?>)
        </a>
        <a href="<?= base_url('radiologist/examinations?status=processing') ?>" 
           class="filter-tab <?= $currentStatus === 'processing' ? 'active' : '' ?>">
            Processing (<?= $counts['processing'] ?? 0 ?>)
        </a>
        <a href="<?= base_url('radiologist/examinations?status=completed') ?>" 
           class="filter-tab <?= $currentStatus === 'completed' ? 'active' : '' ?>">
            Completed (<?= $counts['completed'] ?? 0 ?>)
        </a>
        <a href="<?= base_url('radiologist/examinations?status=released') ?>" 
           class="filter-tab <?= $currentStatus === 'released' ? 'active' : '' ?>">
            Released (<?= $counts['released'] ?? 0 ?>)
        </a>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table radiologist-table" id="examinationsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Exam Type</th>
                        <th>Exam Date</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($examinations)): ?>
                        <?php $counter = 1; ?>
                        <?php foreach ($examinations as $exam): ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td>
                                    <div class="patient-cell">
                                        <span><?= esc($exam['patient_name']) ?></span>
                                        <small><?= esc($exam['age']) ?> yrs · <?= esc($exam['gender']) ?></small>
                                    </div>
                                </td>
                                <td><?= esc($exam['exam_type']) ?></td>
                                <td><?= date('M d, Y', strtotime($exam['exam_date'])) ?></td>
                                <td>
                                    <span class="status-badge <?= $exam['status'] ?>">
                                        <i class="bi bi-circle-fill"></i>
                                        <?= ucfirst($exam['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y h:i A', strtotime($exam['updated_at'])) ?></td>
                                <td>
                                    <a href="<?= base_url('radiologist/examination/view/' . $exam['id']) ?>" 
                                       class="btn-action view" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($exam['status'] === 'released'): ?>
                                        <a href="<?= base_url('radiologist/examination/print/' . $exam['id']) ?>" 
                                           class="btn-action print" title="Print PDF" target="_blank">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="bi bi-x-ray"></i>
                                    <p>No examinations found</p>
                                    <small>No X-Ray examinations match the current filter</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* ===== PAGE HEADER ===== */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-title {
    font-weight: 700;
    color: #0a2b4e;
    margin: 0;
    font-size: 1.3rem;
}

.page-subtitle {
    color: #64748b;
    font-size: 0.85rem;
    margin: 0;
}

.badge-custom-purple {
    background: #f3e5f5;
    color: #800080;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 0.4rem 1rem;
    border-radius: 30px;
}

/* ===== FILTER TABS ===== */
.filter-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
}

.filter-tab {
    padding: 0.4rem 1rem;
    border-radius: 30px;
    font-size: 0.8rem;
    font-weight: 500;
    color: #64748b;
    background: #f0f4ff;
    text-decoration: none;
    transition: all 0.3s ease;
}

.filter-tab:hover {
    background: #e6f0fa;
    color: #0148ca;
    text-decoration: none;
}

.filter-tab.active {
    background: #0148ca;
    color: #fff;
}

/* ===== TABLE ===== */
.table-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
}

.radiologist-table {
    margin: 0;
}

.radiologist-table thead th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    font-weight: 600;
    border-bottom: 2px solid #f0f4ff;
    padding: 0.75rem 0.5rem;
}

.radiologist-table tbody td {
    padding: 0.75rem 0.5rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: #0a2b4e;
    border-bottom: 1px solid #f0f4ff;
}

.radiologist-table tbody tr:hover {
    background: #f8faff;
}

.patient-cell {
    display: flex;
    flex-direction: column;
}

.patient-cell span {
    font-weight: 500;
}

.patient-cell small {
    font-size: 0.7rem;
    color: #94a3b8;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.status-badge .bi-circle-fill {
    font-size: 0.4rem;
}

.status-badge.pending {
    background: #fff3e0;
    color: #ff6b00;
}

.status-badge.processing {
    background: #e3f2fd;
    color: #0148ca;
}

.status-badge.completed {
    background: #e8f5e9;
    color: #28a745;
}

.status-badge.released {
    background: #ccfbf1;
    color: #0d9488;
}

.btn-action {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.8rem;
}

.btn-action.view {
    background: #e6f0fa;
    color: #0148ca;
}

.btn-action.view:hover {
    background: #0148ca;
    color: #fff;
}

.btn-action.print {
    background: #f3e5f5;
    color: #800080;
}

.btn-action.print:hover {
    background: #800080;
    color: #fff;
}

.empty-state {
    text-align: center;
    padding: 2rem;
}

.empty-state i {
    font-size: 2.5rem;
    color: #ccfbf1;
    display: block;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #0a2b4e;
    font-weight: 500;
    margin: 0;
}

.empty-state small {
    color: #94a3b8;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .filter-tabs {
        gap: 0.3rem;
    }
    
    .filter-tab {
        font-size: 0.7rem;
        padding: 0.3rem 0.7rem;
    }
    
    .table-card {
        padding: 0.75rem;
        overflow-x: auto;
    }
}

@media (max-width: 576px) {
    .radiologist-table {
        font-size: 0.7rem;
    }
    
    .radiologist-table thead th,
    .radiologist-table tbody td {
        padding: 0.3rem 0.2rem;
        font-size: 0.7rem;
    }
    
    .btn-action {
        width: 24px;
        height: 24px;
        font-size: 0.7rem;
    }
}
</style>

<?= $this->endSection() ?>