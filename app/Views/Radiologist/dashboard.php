<?= $this->extend('layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>Radiologist Dashboard<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>

<div class="dashboard-container">

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['pending'] ?? 0 ?></h3>
                <p>Pending</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['processing'] ?? 0 ?></h3>
                <p>Processing</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['completed'] ?? 0 ?></h3>
                <p>Completed</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal">
                <i class="bi bi-file-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['released'] ?? 0 ?></h3>
                <p>Released</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $todayCompleted ?? 0 ?></h3>
                <p>Completed Today</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-x-ray"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['total'] ?? 0 ?></h3>
                <p>Total Exams</p>
            </div>
        </div>
    </div>

    <!-- Pending Examinations -->
    <div class="examinations-card">
        <div class="card-header-custom">
            <h5><i class="bi bi-clock-history text-purple"></i> Pending Examinations</h5>
            <div class="header-right-group">
                <span class="badge-custom">Requires Attention</span>
                <a href="<?= base_url('radiologist/examinations') ?>" class="btn-view-all">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table radiologist-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Exam Type</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pendingExaminations)): ?>
                        <?php foreach ($pendingExaminations as $exam): ?>
                            <tr>
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
                                <td>
                                    <a href="<?= base_url('radiologist/examination/view/' . $exam['id']) ?>" 
                                       class="btn-action view" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="bi bi-check-circle"></i>
                                    <p>No pending examinations</p>
                                    <small>All caught up!</small>
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
/* ===== STATS GRID ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(10, 43, 78, 0.1);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.stat-icon.purple { background: #f3e5f5; color: #800080; }
.stat-icon.blue { background: #e6f0fa; color: #0148ca; }
.stat-icon.green { background: #e8f5e9; color: #28a745; }
.stat-icon.teal { background: #ccfbf1; color: #0d9488; }
.stat-icon.orange { background: #fff3e0; color: #ff6b00; }
.stat-icon.primary { background: #e3f2fd; color: #0148ca; }

.stat-info h3 {
    font-size: 1.4rem;
    font-weight: 700;
    color: #0a2b4e;
    margin: 0;
    line-height: 1.2;
}

.stat-info p {
    color: #64748b;
    font-size: 0.8rem;
    margin: 0;
    font-weight: 500;
}

/* ===== EXAMINATIONS CARD ===== */
.examinations-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
}

.card-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.card-header-custom h5 {
    font-weight: 600;
    color: #0a2b4e;
    margin: 0;
    font-size: 0.95rem;
}

.card-header-custom h5 i {
    margin-right: 0.5rem;
}

.text-purple { color: #800080; }

.header-right-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.badge-custom {
    background: #f3e5f5;
    color: #800080;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 30px;
}

.btn-view-all {
    font-size: 0.75rem;
    color: #0148ca;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-view-all:hover {
    color: #0037a0;
    text-decoration: underline;
}

/* ===== TABLE ===== */
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
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .stat-info h3 {
        font-size: 1.1rem;
    }
    
    .card-header-custom {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .header-right-group {
        width: 100%;
        justify-content: space-between;
    }
}

@media (max-width: 576px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }
    
    .stat-card {
        padding: 0.75rem;
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .stat-icon {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }
    
    .stat-info h3 {
        font-size: 1rem;
    }
    
    .stat-info p {
        font-size: 0.7rem;
    }
    
    .radiologist-table {
        font-size: 0.75rem;
    }
    
    .radiologist-table thead th,
    .radiologist-table tbody td {
        padding: 0.4rem 0.25rem;
    }
}
</style>

<?= $this->endSection() ?>