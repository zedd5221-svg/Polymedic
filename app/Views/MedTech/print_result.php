<?= $this->extend('layouts/MedTechLayout') ?>

<?= $this->section('pageTitle') ?>Reports<?= $this->endSection() ?>

<?= $this->section('medtechContent') ?>

<div class="reports-container">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h4 class="page-title">Laboratory Reports</h4>
            <p class="page-subtitle">View laboratory statistics and reports</p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon orange">
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
                <h3><?= $counts['in_progress'] ?? 0 ?></h3>
                <p>In Progress</p>
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
            <div class="stat-icon purple">
                <i class="bi bi-flask"></i>
            </div>
            <div class="stat-info">
                <h3><?= $totalLabAppointments ?? 0 ?></h3>
                <p>Total Lab Patients</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-grid-3x3-gap-fill"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['total'] ?? 0 ?></h3>
                <p>Total Requests</p>
            </div>
        </div>
    </div>

    <!-- Monthly Statistics -->
    <div class="reports-card mt-4">
        <div class="card-header-custom">
            <h5><i class="bi bi-calendar3"></i> Monthly Statistics</h5>
        </div>
        <div class="table-responsive">
            <table class="table reports-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Pending</th>
                        <th>In Progress</th>
                        <th>Draft</th>
                        <th>Completed</th>
                        <th>Released</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($monthlyStats)): ?>
                        <?php foreach ($monthlyStats as $month => $stats): ?>
                            <tr>
                                <td><strong><?= date('F Y', strtotime($month . '-01')) ?></strong></td>
                                <td><span class="badge-status pending"><?= $stats['pending'] ?? 0 ?></span></td>
                                <td><span class="badge-status in_progress"><?= $stats['in_progress'] ?? 0 ?></span></td>
                                <td><span class="badge-status draft"><?= $stats['draft'] ?? 0 ?></span></td>
                                <td><span class="badge-status completed"><?= $stats['completed'] ?? 0 ?></span></td>
                                <td><span class="badge-status released"><?= $stats['released'] ?? 0 ?></span></td>
                                <td><strong><?= $stats['total'] ?? 0 ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-3 text-muted">No data available</td>
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

.stat-icon.orange { background: #fff3e0; color: #ff6b00; }
.stat-icon.blue { background: #e3f2fd; color: #0148ca; }
.stat-icon.green { background: #e8f5e9; color: #28a745; }
.stat-icon.teal { background: #ccfbf1; color: #0d9488; }
.stat-icon.purple { background: #f3e5f5; color: #800080; }
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

/* ===== REPORTS CARD ===== */
.reports-card {
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
    color: #16a34a;
    margin-right: 0.5rem;
}

/* ===== TABLE ===== */
.reports-table {
    margin: 0;
}

.reports-table thead th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    font-weight: 600;
    border-bottom: 2px solid #f0f4ff;
    padding: 0.75rem 0.5rem;
}

.reports-table tbody td {
    padding: 0.75rem 0.5rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: #0a2b4e;
    border-bottom: 1px solid #f0f4ff;
}

.reports-table tbody tr:hover {
    background: #f8faff;
}

.badge-status {
    padding: 0.2rem 0.6rem;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
}

.badge-status.pending {
    background: #fff3e0;
    color: #ff6b00;
}

.badge-status.in_progress {
    background: #e3f2fd;
    color: #0148ca;
}

.badge-status.draft {
    background: #f5f5f5;
    color: #6c757d;
}

.badge-status.completed {
    background: #e8f5e9;
    color: #28a745;
}

.badge-status.released {
    background: #ccfbf1;
    color: #0d9488;
}

/* ============================================
   RESPONSIVE
   ============================================ */

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
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .reports-table {
        font-size: 0.75rem;
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
    
    .reports-table thead th,
    .reports-table tbody td {
        padding: 0.4rem 0.25rem;
        font-size: 0.65rem;
    }
}
</style>

<?= $this->endSection() ?>