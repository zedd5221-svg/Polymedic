<?= $this->extend('layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>Radiology Reports<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>

<div class="reports-container">

    <!-- ===== PAGE HEADER ===== -->
    <div class="page-header">
        <div>
            <h4 class="page-title">Radiology Reports</h4>
            <p class="page-subtitle">View radiology statistics and reports overview</p>
        </div>
        <div class="date-range-selector">
            <i class="bi bi-calendar3"></i>
            <span><?= date('M d, Y', strtotime('-1 month')) ?> – <?= date('M d, Y') ?></span>
            <i class="bi bi-chevron-down"></i>
        </div>
    </div>

    <!-- ===== STATS ROW ===== -->
    <div class="stats-row">
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
                <h3><?= $counts['processing'] ?? 0 ?></h3>
                <p>Processing</p>
                <small><?= isset($totalCount) && $totalCount > 0 ? round((($counts['processing'] ?? 0) / $totalCount) * 100, 1) : 0 ?>% of total</small>
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
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-info">
                <h3><?= $totalXrayAppointments ?? 0 ?></h3>
                <p>Total X-Ray Patients</p>
                <small>This period</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-info">
                <h3>₱<?= number_format($totalRevenue ?? 0, 2) ?></h3>
                <p>Total Revenue</p>
                <small>This period</small>
            </div>
        </div>
    </div>

    <!-- ===== CHARTS & STATISTICS ROW ===== -->
    <div class="charts-row">
        <!-- Monthly Statistics -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h5><i class="bi bi-graph-up"></i> Monthly Statistics</h5>
                    <small>Overview of reports status per month</small>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table stats-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Pending</th>
                            <th>Processing</th>
                            <th>Completed</th>
                            <th>Released</th>
                            <th>Total</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($monthlyStats) && is_array($monthlyStats)): ?>
                            <?php foreach ($monthlyStats as $month => $stats): ?>
                                <tr>
                                    <td><strong><?= date('F Y', strtotime($month . '-01')) ?></strong></td>
                                    <td><span class="count-badge orange"><?= $stats['pending'] ?? 0 ?></span></td>
                                    <td><span class="count-badge blue"><?= $stats['processing'] ?? 0 ?></span></td>
                                    <td><span class="count-badge green"><?= $stats['completed'] ?? 0 ?></span></td>
                                    <td><span class="count-badge teal"><?= $stats['released'] ?? 0 ?></span></td>
                                    <td><strong><?= $stats['total'] ?? 0 ?></strong></td>
                                    <td>₱<?= number_format($stats['revenue'] ?? 0, 2) ?></td>
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
            <div class="table-footer">
                <span>Showing <?= count($monthlyStats ?? []) ?> entries</span>
            </div>
        </div>

        <!-- Reports Overview (Donut Chart with Custom Legend) -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h5><i class="bi bi-pie-chart"></i> Reports Overview</h5>
                    <small>Distribution of reports by status</small>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="reportsOverviewChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ===== RECENT REPORTS TABLE ===== -->
    <div class="recent-reports-card">
        <div class="chart-header">
            <div>
                <h5><i class="bi bi-file-earmark-text"></i> Recent Reports</h5>
                <small>Latest radiology reports and their status</small>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table reports-table">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Patient</th>
                        <th>Exam Type</th>
                        <th>Status</th>
                        <th>Requested Date</th>
                        <th>Completed Date</th>
                        <th>Radiologist</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentReports) && is_array($recentReports)): ?>
                        <?php foreach ($recentReports as $report): ?>
                            <?php
                                $nameParts = preg_split('/\s+/', trim($report['patient_name'] ?? 'Unknown'));
                                $initials = strtoupper(mb_substr($nameParts[0] ?? '', 0, 1) . mb_substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));
                                $modality = 'X-Ray';
                                if (isset($report['exam_type'])) {
                                    if (strpos(strtolower($report['exam_type']), 'ct') !== false) $modality = 'CT';
                                    elseif (strpos(strtolower($report['exam_type']), 'mri') !== false) $modality = 'MRI';
                                    elseif (strpos(strtolower($report['exam_type']), 'us') !== false || strpos(strtolower($report['exam_type']), 'ultra') !== false) $modality = 'US';
                                }
                            ?>
                            <tr>
                                <td><code>XRAY-<?= date('y') ?>-<?= str_pad($report['id'] ?? 0, 4, '0', STR_PAD_LEFT) ?></code></td>
                                <td>
                                    <div class="patient-cell">
                                        <span class="patient-avatar"><?= esc($initials) ?></span>
                                        <div class="patient-meta">
                                            <span class="patient-name"><?= esc($report['patient_name'] ?? 'Unknown') ?></span>
                                            <small><?= esc($report['age'] ?? 'N/A') ?> years</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="exam-tag <?= strtolower($modality) ?>"><?= esc($report['exam_type'] ?? 'X-Ray') ?></span></td>
                                <td>
                                    <span class="status-badge <?= $report['status'] ?? 'pending' ?>">
                                        <i class="bi bi-circle-fill"></i>
                                        <?= ucfirst($report['status'] ?? 'Pending') ?>
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
                                    <?php if (!empty($report['completed_at'])): ?>
                                        <strong><?= date('M d, Y', strtotime($report['completed_at'])) ?></strong>
                                        <small class="d-block"><?= date('h:i A', strtotime($report['completed_at'])) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($report['radiologist'] ?? 'Sacron Rampak') ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?= base_url('radiologist/examination/view/' . ($report['id'] ?? 0)) ?>" 
                                           class="action-icon-btn view" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= base_url('radiologist/examination/print/' . ($report['id'] ?? 0)) ?>" 
                                           class="action-icon-btn download" title="Download" target="_blank">
                                            <i class="bi bi-download"></i>
                                        </a>
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
                                    <small>Reports will appear here once examinations are processed</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>Showing <?= count($recentReports ?? []) ?> entries</span>
            <button class="btn-view-all" onclick="window.location.href='<?= base_url('radiologist/examinations') ?>'">
                View All Reports <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<style>
/* ============================================
   RADIOLOGY REPORTS - SAFE FALLBACK
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
    --red: #dc2626;
    --red-soft: #FEF2F2;
    font-family: 'Inter', sans-serif;
}

/* ===== PAGE HEADER ===== */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    gap: 1rem;
    flex-wrap: wrap;
}

.page-title {
    font-weight: 800;
    color: var(--ink);
    margin: 0;
    font-size: 1.6rem;
    letter-spacing: -0.02em;
}

.page-subtitle {
    color: var(--ink-soft);
    font-size: 0.9rem;
    margin: 0.2rem 0 0;
    font-weight: 400;
}

.date-range-selector {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 0.6rem 1rem;
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--ink);
    cursor: pointer;
    transition: all 0.2s ease;
}

.date-range-selector:hover {
    border-color: var(--purple);
}

.date-range-selector i {
    color: var(--ink-faint);
}

/* ===== STATS ROW ===== */
.stats-row {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: var(--surface);
    border-radius: 14px;
    padding: 1.5rem 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid var(--line);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    min-height: 140px;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16, 24, 40, 0.08);
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.stat-icon.orange { background: var(--orange-soft); color: var(--orange); }
.stat-icon.blue { background: var(--blue-soft); color: var(--blue); }
.stat-icon.green { background: var(--green-soft); color: var(--green); }
.stat-icon.teal { background: var(--teal-soft); color: var(--teal); }
.stat-icon.purple { background: var(--purple-soft); color: var(--purple); }
.stat-icon.success { background: var(--teal-soft); color: var(--teal); }

.stat-info h3 {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--ink);
    margin: 0;
    line-height: 1.1;
    letter-spacing: -0.02em;
}

.stat-info p {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--ink);
    margin: 0.15rem 0 0;
}

.stat-info small {
    font-size: 0.75rem;
    color: var(--ink-soft);
    font-weight: 400;
    margin-top: 2px;
    display: block;
}

/* ===== CHARTS ROW ===== */
.charts-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.chart-card {
    background: var(--surface);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid var(--line);
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

/* ===== STATS TABLE ===== */
.stats-table {
    margin: 0;
}

.stats-table thead th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--ink-faint);
    font-weight: 600;
    border: none;
    padding: 0.75rem 0.5rem;
    background: transparent;
    white-space: nowrap;
}

.stats-table tbody td {
    padding: 0.9rem 0.5rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: var(--ink);
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
}

.count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
}

.count-badge.orange { background: var(--orange-soft); color: var(--orange); }
.count-badge.blue { background: var(--blue-soft); color: var(--blue); }
.count-badge.green { background: var(--green-soft); color: var(--green); }
.count-badge.teal { background: var(--teal-soft); color: var(--teal); }

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

/* ===== RECENT REPORTS CARD ===== */
.recent-reports-card {
    background: var(--surface);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid var(--line);
}

.reports-table {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0 8px;
}

.reports-table thead th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--ink-faint);
    font-weight: 600;
    border: none;
    padding: 0.75rem 1rem;
    background: transparent;
    white-space: nowrap;
}

.reports-table tbody tr {
    background: var(--surface);
    border-radius: 12px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    border: 1px solid var(--line);
}

.reports-table tbody tr:hover {
    background: var(--surface-alt);
    box-shadow: 0 4px 12px rgba(16, 24, 40, 0.08);
    transform: translateY(-1px);
}

.reports-table tbody td {
    padding: 0.85rem 1rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: var(--ink);
    border: none;
    white-space: nowrap;
    background: transparent;
}

.reports-table tbody td:first-child {
    border-radius: 12px 0 0 12px;
}

.reports-table tbody td:last-child {
    border-radius: 0 12px 12px 0;
}

.reports-table code {
    background: var(--surface-alt);
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--purple);
}

/* Patient Cell */
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

/* Exam Type Tag */
.exam-tag {
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
}

.exam-tag.x-ray { background: var(--purple-soft); color: var(--purple); }
.exam-tag.ct { background: var(--teal-soft); color: var(--teal); }
.exam-tag.mri { background: var(--blue-soft); color: var(--blue); }
.exam-tag.us { background: var(--green-soft); color: var(--green); }

/* Status Badge */
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

.status-badge.processing {
    background: var(--blue-soft);
    color: var(--blue);
}

.status-badge.completed {
    background: var(--green-soft);
    color: var(--green);
}

.status-badge.released {
    background: var(--teal-soft);
    color: var(--teal);
}

/* Action Buttons */
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

/* Empty State */
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

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1400px) {
    .stats-row {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 992px) {
    .charts-row {
        grid-template-columns: 1fr;
    }
    
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }
    
    .stat-info h3 {
        font-size: 1.4rem;
    }
    
    .reports-table {
        border-spacing: 0 6px;
    }
    
    .reports-table thead th,
    .reports-table tbody td {
        padding: 0.5rem;
        font-size: 0.75rem;
    }
}

@media (max-width: 576px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .page-title {
        font-size: 1.2rem;
    }
    
    .chart-body {
        height: 200px;
    }
    
    .table-footer {
        flex-direction: column;
        gap: 0.75rem;
    }
}
</style>

<!-- Include Chart.js if not already loaded -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== REPORTS OVERVIEW DONUT CHART WITH CUSTOM LEGEND =====
    const reportsCtx = document.getElementById('reportsOverviewChart');
    if (reportsCtx) {
        const ctx = reportsCtx.getContext('2d');
        
        const pendingCount = <?= $counts['pending'] ?? 0 ?>;
        const processingCount = <?= $counts['processing'] ?? 0 ?>;
        const completedCount = <?= $counts['completed'] ?? 0 ?>;
        const releasedCount = <?= $counts['released'] ?? 0 ?>;
        const totalCount = pendingCount + processingCount + completedCount + releasedCount;
        
        // Chart data
        const chartData = {
            labels: ['Pending', 'Processing', 'Completed', 'Released'],
            datasets: [{
                data: [pendingCount, processingCount, completedCount, releasedCount],
                backgroundColor: [
                    '#7c3aed',  // Purple - Pending
                    '#1D4ED8',  // Blue - Processing
                    '#16a34a',  // Green - Completed
                    '#0d9488'   // Teal - Released
                ],
                borderWidth: 3,
                borderColor: '#ffffff',
                hoverOffset: 8
            }]
        };
        
        // Chart configuration with custom legend
        const config = {
            type: 'doughnut',
            data: chartData,
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