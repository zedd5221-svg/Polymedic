<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<div class="dashboard-container">

    <!-- ===== STATS ROW (7 cards) ===== -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($totalPatients ?? 0) ?></h3>
                <p>Total Patients</p>
                <small>All registered patients</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($todayPatients ?? 0) ?></h3>
                <p>Today's Patients</p>
                <small>Checked in today</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($pendingRequests ?? 0) ?></h3>
                <p>Pending Requests</p>
                <small>Awaiting processing</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($completedRequests ?? 0) ?></h3>
                <p>Completed Requests</p>
                <small>Results encoded</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="bi bi-file-earmark-check-fill"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($releasedResults ?? 0) ?></h3>
                <p>Released Results</p>
                <small>Sent to doctors</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-info">
                <h3>₱<?= number_format($todayRevenue ?? 0, 2) ?></h3>
                <p>Today's Revenue</p>
                <small>Collected today</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="stat-info">
                <h3>₱<?= number_format($monthlyRevenue ?? 0, 2) ?></h3>
                <p>Monthly Revenue</p>
                <small><?= date('F Y') ?></small>
            </div>
        </div>
    </div>

    <!-- ===== CHARTS ROW ===== -->
    <div class="charts-row">
        <!-- Revenue Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h5><i class="bi bi-graph-up"></i> Revenue Overview</h5>
                    <small>Monthly revenue <?= date('Y') ?></small>
                </div>
                <span class="badge-year"><?= date('Y') ?></span>
            </div>
            <div class="chart-body">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
        
        <!-- Top Lab Tests -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h5><i class="bi bi-flask"></i> Top Lab Tests</h5>
                    <small>Most requested</small>
                </div>
                <span class="badge-year">All Time</span>
            </div>
            <div class="chart-body">
                <div class="test-list">
                    <?php if (!empty($topTests)): ?>
                        <?php 
                        $maxCount = max(array_column($topTests, 'count'));
                        foreach ($topTests as $test): 
                            $width = ($maxCount > 0) ? ($test['count'] / $maxCount) * 100 : 0;
                        ?>
                            <div class="test-item">
                                <span class="test-name" title="<?= esc($test['name']) ?>"><?= esc($test['name']) ?></span>
                                <div class="test-bar"><div class="test-fill" style="width: <?= $width ?>%; background: <?= $test['color'] ?>;"></div></div>
                                <span class="test-count"><?= $test['count'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-muted">No lab tests yet</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== BOTTOM CHARTS ROW ===== -->
    <div class="charts-row">
        <!-- Daily Patient Visits -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h5><i class="bi bi-people"></i> Daily Patient Visits</h5>
                    <small>This week</small>
                </div>
                <span class="badge-year">This Week</span>
            </div>
            <div class="chart-body">
                <canvas id="visitsChart"></canvas>
            </div>
        </div>
        
        <!-- Weekly Diagnostic Requests -->
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h5><i class="bi bi-clipboard2-pulse"></i> Diagnostic Requests</h5>
                    <small>Requested vs Completed</small>
                </div>
                <span class="badge-year">This Week</span>
            </div>
            <div class="chart-body">
                <canvas id="requestsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ===== RECENT ACTIVITY ===== -->
    <div class="activity-card">
        <div class="chart-header">
            <div>
                <h5><i class="bi bi-clock-history"></i> Recent Activity</h5>
                <small>Latest updates</small>
            </div>
            <span class="badge-year">Latest</span>
        </div>
        <div class="activity-list">
            <?php if (!empty($recentActivity)): ?>
                <?php foreach ($recentActivity as $activity): ?>
                    <div class="activity-item">
                        <span class="activity-dot" style="background: <?= $activity['color'] ?>;"></span>
                        <div class="activity-content">
                            <p><?= esc($activity['message']) ?></p>
                            <small><?= date('M d, Y h:i A', strtotime($activity['time'])) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="activity-item">
                    <span class="activity-dot" style="background: #94a3b8;"></span>
                    <div class="activity-content">
                        <p>No recent activity</p>
                        <small>Check back later</small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* ============================================
   ADMIN DASHBOARD - ENHANCED RESPONSIVE VERSION
   ============================================ */
.dashboard-container {
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
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}

/* ===== STATS ROW - FIXED RESPONSIVE GRID ===== */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
    width: 100%;
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
    min-height: 150px;
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16, 24, 40, 0.08);
}

.stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
    margin-bottom: 0.25rem;
}

.stat-icon.blue { background: var(--blue-soft); color: var(--blue); }
.stat-icon.green { background: var(--green-soft); color: var(--green); }
.stat-icon.orange { background: var(--orange-soft); color: var(--orange); }
.stat-icon.teal { background: var(--teal-soft); color: var(--teal); }
.stat-icon.purple { background: var(--purple-soft); color: var(--purple); }
.stat-icon.success { background: var(--teal-soft); color: var(--teal); }
.stat-icon.primary { background: var(--blue-soft); color: var(--blue); }

.stat-info {
    min-width: 0;
    width: 100%;
}

.stat-info h3 {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--ink);
    margin: 0;
    line-height: 1.1;
    letter-spacing: -0.02em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ===== CHARTS ROW ===== */
.charts-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
    width: 100%;
}

.chart-card {
    background: var(--surface);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid var(--line);
    min-width: 0;
    width: 100%;
    box-sizing: border-box;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 0.5rem;
}

.chart-header h5 {
    font-weight: 700;
    color: var(--ink);
    margin: 0 0 0.15rem;
    font-size: 1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chart-header h5 i {
    color: var(--blue);
    margin-right: 0.5rem;
}

.chart-header small {
    color: var(--ink-soft);
    font-size: 0.8rem;
    font-weight: 400;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.badge-year {
    background: var(--surface-alt);
    color: var(--ink-soft);
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.3rem 0.85rem;
    border-radius: 30px;
    white-space: nowrap;
    flex-shrink: 0;
}

.chart-body {
    position: relative;
    height: 280px;
    width: 100%;
}

/* ===== TEST LIST ===== */
.test-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 0.5rem;
}

.test-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.test-name {
    width: 110px;
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--ink);
    flex-shrink: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.test-bar {
    flex: 1;
    height: 8px;
    background: var(--surface-alt);
    border-radius: 6px;
    overflow: hidden;
}

.test-fill {
    height: 100%;
    border-radius: 6px;
    transition: width 0.6s ease;
}

.test-count {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--ink);
    width: 40px;
    text-align: right;
    flex-shrink: 0;
}

/* ===== ACTIVITY ===== */
.activity-card {
    background: var(--surface);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid var(--line);
    width: 100%;
    box-sizing: border-box;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-top: 0.5rem;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--line);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 0.25rem;
}

.activity-content {
    min-width: 0;
    flex: 1;
}

.activity-content p {
    margin: 0;
    font-size: 0.85rem;
    color: var(--ink);
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.activity-content small {
    color: var(--ink-soft);
    font-size: 0.75rem;
}

/* ============================================
   RESPONSIVE BREAKPOINTS - FIXED
   ============================================ */

@media (max-width: 1400px) {
    .stats-row {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.85rem;
    }
    
    .stat-card {
        padding: 1.25rem 1rem;
    }
    
    .stat-info h3 {
        font-size: 1.5rem;
    }
}

@media (max-width: 1200px) {
    .stats-row {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 0.75rem;
    }
    
    .chart-card {
        padding: 1.25rem;
    }
    
    .chart-body {
        height: 240px;
    }
}

@media (max-width: 992px) {
    .stats-row {
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }
    
    .charts-row {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .chart-card {
        padding: 1.25rem;
    }
    
    .test-name {
        width: 90px;
    }
}

@media (max-width: 768px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.6rem;
    }
    
    .stat-card {
        padding: 0.85rem;
        min-height: 120px;
    }
    
    .stat-icon {
        width: 34px;
        height: 34px;
        font-size: 0.9rem;
        margin-bottom: 0.15rem;
    }
    
    .stat-info h3 {
        font-size: 1.25rem;
    }
    
    .stat-info p {
        font-size: 0.7rem;
    }
    
    .stat-info small {
        font-size: 0.6rem;
    }
    
    .chart-card {
        padding: 1rem;
    }
    
    .chart-body {
        height: 200px;
    }
    
    .chart-header h5 {
        font-size: 0.85rem;
    }
    
    .chart-header small {
        font-size: 0.7rem;
    }
    
    .badge-year {
        font-size: 0.6rem;
        padding: 0.2rem 0.6rem;
    }
    
    .test-name {
        width: 70px;
        font-size: 0.7rem;
    }
    
    .test-count {
        width: 30px;
        font-size: 0.7rem;
    }
    
    .activity-card {
        padding: 1rem;
    }
}

@media (max-width: 576px) {
    .stats-row {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .stat-card {
        flex-direction: row;
        align-items: center;
        padding: 0.85rem 1rem;
        min-height: auto;
        gap: 0.75rem;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
        margin-bottom: 0;
        flex-shrink: 0;
    }
    
    .stat-info {
        flex: 1;
    }
    
    .stat-info h3 {
        font-size: 1.4rem;
        white-space: normal;
    }
    
    .stat-info p {
        font-size: 0.75rem;
    }
    
    .stat-info small {
        font-size: 0.65rem;
        white-space: normal;
    }
    
    .charts-row {
        gap: 0.5rem;
    }
    
    .chart-card {
        padding: 0.75rem;
    }
    
    .chart-body {
        height: 180px;
    }
    
    .chart-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.3rem;
    }
    
    .badge-year {
        align-self: flex-start;
    }
    
    .activity-card {
        padding: 0.75rem;
    }
    
    .activity-item {
        padding: 0.5rem 0;
    }
    
    .activity-content p {
        font-size: 0.75rem;
    }
    
    .activity-content small {
        font-size: 0.65rem;
    }
}

@media (max-width: 400px) {
    .stat-card {
        padding: 0.6rem 0.75rem;
    }
    
    .stat-icon {
        width: 32px;
        height: 32px;
        font-size: 0.85rem;
    }
    
    .stat-info h3 {
        font-size: 1.1rem;
    }
    
    .chart-body {
        height: 150px;
    }
}
</style>

<script>
// ===== CHART.JS DATA =====
const revenueData = <?= json_encode($revenueData ?? ['labels' => [], 'values' => []]) ?>;
const visitsData = <?= json_encode($visitsData ?? ['labels' => [], 'values' => []]) ?>;
const requestsData = <?= json_encode($requestsData ?? ['labels' => [], 'requested' => [], 'completed' => []]) ?>;

// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: revenueData.labels,
        datasets: [{
            label: 'Revenue',
            data: revenueData.values,
            borderColor: '#1D4ED8',
            backgroundColor: 'rgba(29, 78, 216, 0.08)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#1D4ED8',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#F0F2F5' },
                ticks: { color: '#94A3B8', font: { size: 10 } }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#94A3B8', font: { size: 10 } }
            }
        }
    }
});

// Visits Chart
const visitsCtx = document.getElementById('visitsChart').getContext('2d');
new Chart(visitsCtx, {
    type: 'bar',
    data: {
        labels: visitsData.labels,
        datasets: [{
            label: 'Patient Visits',
            data: visitsData.values,
            backgroundColor: 'rgba(29, 78, 216, 0.7)',
            borderColor: '#1D4ED8',
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#F0F2F5' },
                ticks: { color: '#94A3B8', font: { size: 10 } }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#94A3B8', font: { size: 10 } }
            }
        }
    }
});

// Requests Chart
const requestsCtx = document.getElementById('requestsChart').getContext('2d');
new Chart(requestsCtx, {
    type: 'bar',
    data: {
        labels: requestsData.labels,
        datasets: [
            {
                label: 'Requested',
                data: requestsData.requested,
                backgroundColor: 'rgba(29, 78, 216, 0.7)',
                borderColor: '#1D4ED8',
                borderWidth: 1,
                borderRadius: 6
            },
            {
                label: 'Completed',
                data: requestsData.completed,
                backgroundColor: 'rgba(13, 148, 136, 0.7)',
                borderColor: '#0d9488',
                borderWidth: 1,
                borderRadius: 6
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 15,
                    font: { size: 10 }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#F0F2F5' },
                ticks: { color: '#94A3B8', font: { size: 10 } }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#94A3B8', font: { size: 10 } }
            }
        }
    }
});
</script>

<?= $this->endSection() ?>