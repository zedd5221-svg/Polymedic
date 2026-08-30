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
                <small><?= isset($totalCount) && $totalCount > 0 ? round((($counts['pending'] ?? 0) / $totalCount) * 100, 1) : 0 ?>% of total</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['in_progress'] ?? 0 ?></h3>
                <p>In Progress</p>
                <small><?= isset($totalCount) && $totalCount > 0 ? round((($counts['in_progress'] ?? 0) / $totalCount) * 100, 1) : 0 ?>% of total</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon gray">
                <i class="bi bi-pencil"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['draft'] ?? 0 ?></h3>
                <p>Draft</p>
                <small><?= isset($totalCount) && $totalCount > 0 ? round((($counts['draft'] ?? 0) / $totalCount) * 100, 1) : 0 ?>% of total</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['completed'] ?? 0 ?></h3>
                <p>Completed</p>
                <small><?= isset($totalCount) && $totalCount > 0 ? round((($counts['completed'] ?? 0) / $totalCount) * 100, 1) : 0 ?>% of total</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal">
                <i class="bi bi-file-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['released'] ?? 0 ?></h3>
                <p>Released</p>
                <small><?= isset($totalCount) && $totalCount > 0 ? round((($counts['released'] ?? 0) / $totalCount) * 100, 1) : 0 ?>% of total</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="bi bi-flask"></i>
            </div>
            <div class="stat-info">
                <h3><?= $totalLabAppointments ?? 0 ?></h3>
                <p>Total Lab Patients</p>
                <small>This month</small>
            </div>
        </div>
    </div>

    <!-- Charts Row: Donut Chart + Monthly Stats Summary -->
    <div class="charts-row mt-4">
        <!-- Donut Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h5><i class="bi bi-pie-chart"></i> Request Status Overview</h5>
                    <small>Distribution of laboratory reports</small>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="statusOverviewChart"></canvas>
            </div>
        </div>
        
        <!-- Monthly Stats Summary Card -->
        <div class="summary-card">
            <div class="summary-header">
                <h5><i class="bi bi-calendar3"></i> Monthly Statistics</h5>
                <small>Overview of laboratory report status for this month</small>
            </div>
            
            <!-- Monthly Stats as Grid -->
            <div class="monthly-stats-grid">
                <div class="monthly-stat-item">
                    <span class="monthly-stat-label">Month</span>
                    <span class="monthly-stat-value highlight">
                        <?php 
                            $latestMonth = !empty($monthlyStats) ? array_key_first($monthlyStats) : date('Y-m');
                            echo date('F Y', strtotime($latestMonth . '-01'));
                        ?>
                    </span>
                </div>
                <div class="monthly-stat-item">
                    <span class="monthly-stat-label pending">Pending</span>
                    <span class="monthly-stat-value"><?= $counts['pending'] ?? 0 ?></span>
                </div>
                <div class="monthly-stat-item">
                    <span class="monthly-stat-label in_progress">In Progress</span>
                    <span class="monthly-stat-value"><?= $counts['in_progress'] ?? 0 ?></span>
                </div>
                <div class="monthly-stat-item">
                    <span class="monthly-stat-label draft">Draft</span>
                    <span class="monthly-stat-value"><?= $counts['draft'] ?? 0 ?></span>
                </div>
                <div class="monthly-stat-item">
                    <span class="monthly-stat-label completed">Completed</span>
                    <span class="monthly-stat-value"><?= $counts['completed'] ?? 0 ?></span>
                </div>
                <div class="monthly-stat-item">
                    <span class="monthly-stat-label released">Released</span>
                    <span class="monthly-stat-value"><?= $counts['released'] ?? 0 ?></span>
                </div>
                <div class="monthly-stat-item total">
                    <span class="monthly-stat-label">Total</span>
                    <span class="monthly-stat-value total-number"><?= $counts['total'] ?? 0 ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Reports -->
    <div class="recent-reports-card mt-4">
        <div class="card-header-custom">
            <div>
                <h5><i class="bi bi-file-earmark-text"></i> Recent Laboratory Reports</h5>
                <small>Latest laboratory reports and their current status</small>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table reports-table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Patient</th>
                        <th>Test / Exam</th>
                        <th>Status</th>
                        <th>Requested Date</th>
                        <th>Released Date</th>
                        <th>Technologist</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentReports) && is_array($recentReports)): ?>
                        <?php foreach ($recentReports as $report): ?>
                            <?php
                                $nameParts = preg_split('/\s+/', trim($report['patient_name'] ?? 'Unknown'));
                                $initials = strtoupper(mb_substr($nameParts[0] ?? '', 0, 1) . mb_substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));
                            ?>
                            <tr>
                                <td><code>LAB-<?= date('y') ?>-<?= str_pad($report['id'] ?? 0, 4, '0', STR_PAD_LEFT) ?></code></td>
                                <td>
                                    <div class="patient-cell">
                                        <span class="patient-avatar"><?= esc($initials) ?></span>
                                        <div class="patient-meta">
                                            <span class="patient-name"><?= esc($report['patient_name'] ?? 'Unknown') ?></span>
                                            <small><?= esc($report['age'] ?? 'N/A') ?> years</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="exam-tag lab"><?= esc($report['test_name'] ?? 'Lab Test') ?></span></td>
                                <td>
                                    <span class="status-badge <?= $report['status'] ?? 'pending' ?>">
                                        <i class="bi bi-circle-fill"></i>
                                        <?= ucfirst(str_replace('_', ' ', $report['status'] ?? 'Pending')) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($report['requested_at'])): ?>
                                        <strong><?= date('M d, Y', strtotime($report['requested_at'])) ?></strong>
                                        <small class="d-block"><?= date('h:i A', strtotime($report['requested_at'])) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($report['released_at'])): ?>
                                        <strong><?= date('M d, Y', strtotime($report['released_at'])) ?></strong>
                                        <small class="d-block"><?= date('h:i A', strtotime($report['released_at'])) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($report['technologist'] ?? 'John Doe') ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?= base_url('medtech/request/view/' . ($report['id'] ?? 0)) ?>" 
                                           class="action-icon-btn view" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (($report['status'] ?? '') === 'released' || ($report['status'] ?? '') === 'completed'): ?>
                                            <a href="<?= base_url('medtech/request/print/' . ($report['id'] ?? 0)) ?>" 
                                               class="action-icon-btn download" title="Download" target="_blank">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <p>No recent reports found</p>
                                    <small>Reports will appear here once laboratory requests are processed</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>Showing <?= count($recentReports ?? []) ?> of <?= $counts['total'] ?? 0 ?> entries</span>
            <button class="btn-view-all" onclick="window.location.href='<?= base_url('medtech/requests') ?>'">
                View All Requests <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<style>
/* ============================================
   MEDTECH REPORTS - IMPROVED UI
   ============================================ */
.reports-container {
    --ink: #101828;
    --ink-soft: #64748B;
    --ink-faint: #94A3B8;
    --line: #E5E9ED;
    --surface: #FFFFFF;
    --surface-alt: #F8FAFB;
    --blue: #1D4ED8;
    --blue-soft: #E8EFFE;
    --teal: #0d9488;
    --teal-soft: #E0F2F4;
    --green: #15803D;
    --green-soft: #E7F6EC;
    --orange: #C2410C;
    --orange-soft: #FFF1E6;
    --purple: #7c3aed;
    --purple-soft: #ede9fe;
    --gray: #6c757d;
    --gray-soft: #f5f5f5;
    font-family: 'Inter', sans-serif;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    gap: 1rem;
    flex-wrap: wrap;
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

.stat-icon.orange { background: var(--orange-soft); color: var(--orange); }
.stat-icon.blue { background: var(--blue-soft); color: var(--blue); }
.stat-icon.gray { background: var(--gray-soft); color: var(--gray); }
.stat-icon.green { background: var(--green-soft); color: var(--green); }
.stat-icon.teal { background: var(--teal-soft); color: var(--teal); }
.stat-icon.purple { background: var(--purple-soft); color: var(--purple); }

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

.stat-info small {
    font-size: 0.7rem;
    color: var(--ink-soft);
    font-weight: 400;
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
    margin-right: 0.5rem;
}

.card-header-custom small {
    color: var(--ink-soft);
    font-size: 0.8rem;
}

/* ===== TABLES ===== */
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
    white-space: nowrap;
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

/* ===== BADGE STATUS ===== */
.badge-status {
    padding: 0.2rem 0.6rem;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
}

.badge-status.pending {
    background: var(--orange-soft);
    color: var(--orange);
}

.badge-status.in_progress {
    background: var(--blue-soft);
    color: var(--blue);
}

.badge-status.draft {
    background: var(--gray-soft);
    color: var(--gray);
}

.badge-status.completed {
    background: var(--green-soft);
    color: var(--green);
}

.badge-status.released {
    background: var(--teal-soft);
    color: var(--teal);
}

/* ===== CHARTS ROW ===== */
.charts-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.chart-card {
    background: var(--surface);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.chart-header h5 {
    font-weight: 700;
    color: var(--ink);
    margin: 0 0 0.15rem;
    font-size: 1rem;
}

.chart-header h5 i {
    color: var(--purple);
    margin-right: 0.5rem;
}

.chart-header small {
    color: var(--ink-soft);
    font-size: 0.8rem;
    font-weight: 400;
}

.chart-body {
    position: relative;
    height: 250px;
}

/* ===== MONTHLY STATS SUMMARY CARD ===== */
.summary-card {
    background: var(--surface);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
    display: flex;
    flex-direction: column;
}

.summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--line);
}

.summary-header h5 {
    font-weight: 700;
    color: var(--ink);
    margin: 0;
    font-size: 1rem;
}

.summary-header h5 i {
    color: var(--purple);
    margin-right: 0.5rem;
}

.summary-header small {
    color: var(--ink-soft);
    font-size: 0.8rem;
}

/* Monthly Stats Grid */
.monthly-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    flex: 1;
}

.monthly-stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.6rem 0.75rem;
    border-radius: 10px;
    background: var(--surface-alt);
    transition: all 0.2s ease;
}

.monthly-stat-item:hover {
    background: #f0f4ff;
    transform: translateX(2px);
}

.monthly-stat-item.highlight {
    background: var(--purple-soft);
    grid-column: 1 / -1;
}

.monthly-stat-item.total {
    background: var(--blue-soft);
    grid-column: 1 / -1;
    margin-top: 0.25rem;
}

.monthly-stat-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--ink-soft);
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.monthly-stat-label.pending { color: var(--orange); }
.monthly-stat-label.in_progress { color: var(--blue); }
.monthly-stat-label.draft { color: var(--gray); }
.monthly-stat-label.completed { color: var(--green); }
.monthly-stat-label.released { color: var(--teal); }

.monthly-stat-value {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--ink);
}

.monthly-stat-value.highlight {
    font-size: 0.9rem;
    color: var(--purple);
}

.monthly-stat-value.total-number {
    font-size: 1.1rem;
    color: var(--blue);
}

/* ===== RECENT REPORTS ===== */
.recent-reports-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
}

.patient-cell {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.patient-avatar {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: var(--purple-soft);
    color: var(--purple);
    font-size: 0.65rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.patient-meta {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.patient-name {
    font-weight: 600;
    color: var(--ink);
}

.patient-meta small {
    font-size: 0.7rem;
    color: var(--ink-soft);
}

.exam-tag {
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
}

.exam-tag.lab {
    background: var(--purple-soft);
    color: var(--purple);
}

/* ===== STATUS BADGE ===== */
.status-badge {
    padding: 0.3rem 0.8rem;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    white-space: nowrap;
}

.status-badge .bi-circle-fill {
    font-size: 0.4rem;
}

.status-badge.pending {
    background: var(--orange-soft);
    color: var(--orange);
}

.status-badge.in_progress {
    background: var(--blue-soft);
    color: var(--blue);
}

.status-badge.draft {
    background: var(--gray-soft);
    color: var(--gray);
}

.status-badge.completed {
    background: var(--green-soft);
    color: var(--green);
}

.status-badge.released {
    background: var(--teal-soft);
    color: var(--teal);
}

/* ===== ACTION BUTTONS ===== */
.action-buttons {
    display: flex;
    gap: 0.4rem;
}

.action-icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.action-icon-btn.view {
    background: var(--blue-soft);
    color: var(--blue);
    border-color: #bfdbfe;
}

.action-icon-btn.view:hover {
    background: var(--blue);
    color: #ffffff;
    border-color: var(--blue);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(29, 78, 216, 0.2);
}

.action-icon-btn.download {
    background: var(--teal-soft);
    color: var(--teal);
    border-color: #99f6e4;
}

.action-icon-btn.download:hover {
    background: var(--teal);
    color: #ffffff;
    border-color: var(--teal);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2);
}

.btn-view-all {
    background: var(--blue);
    color: #ffffff;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-view-all:hover {
    background: #1e40af;
    transform: translateY(-1px);
}

.table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--line);
}

.table-footer span {
    font-size: 0.8rem;
    color: var(--ink-soft);
}

.empty-state {
    text-align: center;
    padding: 2.5rem 1rem;
}

.empty-state i {
    font-size: 2.5rem;
    color: var(--ink-faint);
    display: block;
    margin-bottom: 0.75rem;
}

.empty-state p {
    color: var(--ink);
    font-weight: 600;
    margin: 0;
}

.empty-state small {
    color: var(--ink-soft);
}

.mt-4 {
    margin-top: 1.5rem;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .charts-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .stat-card {
        padding: 1rem;
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .stat-info h3 {
        font-size: 1.1rem;
    }
    
    .stat-info p {
        font-size: 0.75rem;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .reports-table {
        font-size: 0.75rem;
    }
    
    .card-header-custom {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .summary-card {
        padding: 1rem;
    }
    
    .monthly-stats-grid {
        gap: 0.4rem;
    }
    
    .monthly-stat-item {
        padding: 0.5rem 0.6rem;
    }
    
    .monthly-stat-label {
        font-size: 0.7rem;
    }
    
    .monthly-stat-value {
        font-size: 0.8rem;
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
        gap: 0.25rem;
    }
    
    .stat-icon {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    .stat-info h3 {
        font-size: 0.95rem;
    }
    
    .stat-info p {
        font-size: 0.65rem;
    }
    
    .stat-info small {
        font-size: 0.55rem;
    }
    
    .reports-table thead th,
    .reports-table tbody td {
        padding: 0.4rem 0.25rem;
        font-size: 0.65rem;
    }
    
    .table-footer {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .chart-body {
        height: 200px;
    }
    
    .monthly-stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 0.3rem;
    }
    
    .monthly-stat-item {
        padding: 0.4rem 0.5rem;
    }
    
    .monthly-stat-label {
        font-size: 0.6rem;
    }
    
    .monthly-stat-value {
        font-size: 0.7rem;
    }
    
    .monthly-stat-value.total-number {
        font-size: 0.9rem;
    }
}
</style>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== REQUEST STATUS OVERVIEW DOUGHNUT CHART =====
    const statusCtx = document.getElementById('statusOverviewChart');
    if (statusCtx) {
        const ctx = statusCtx.getContext('2d');
        
        const statusLabels = <?= $statusLabels ?? '["Pending", "In Progress", "Draft", "Completed", "Released"]' ?>;
        const statusData = <?= $statusData ?? '[0, 0, 0, 0, 0]' ?>;
        const statusColors = <?= $statusColors ?? '["#C2410C", "#1D4ED8", "#6c757d", "#15803D", "#0d9488"]' ?>;
        const totalCount = statusData.reduce((a, b) => a + b, 0);
        
        const config = {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    backgroundColor: statusColors,
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: { size: 13, weight: '600' },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                return data.labels.map((label, i) => {
                                    const value = data.datasets[0].data[i];
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return {
                                        text: label + '  ' + value + ' (' + percentage + '%)',
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        strokeStyle: data.datasets[0].backgroundColor[i],
                                        pointStyle: 'circle',
                                        index: i
                                    };
                                });
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        };
        
        new Chart(ctx, config);
    }
});
</script>

<?= $this->endSection() ?>