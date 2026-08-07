<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<div class="dashboard-container">

    <!-- Stats Grid - Responsive -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="stat-info">
                <h3>4,821</h3>
                <p>Total Patients</p>
                <span class="trend up"><i class="bi bi-arrow-up"></i> 12.5%</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <div class="stat-info">
                <h3>78</h3>
                <p>Today's Patients</p>
                <span class="trend up"><i class="bi bi-arrow-up"></i> 8.3%</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="stat-info">
                <h3>24</h3>
                <p>Pending Requests</p>
                <span class="trend down"><i class="bi bi-arrow-down"></i> 3.2%</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="stat-info">
                <h3>61</h3>
                <p>Completed Requests</p>
                <span class="trend up"><i class="bi bi-arrow-up"></i> 5.7%</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="bi bi-file-earmark-check-fill"></i>
            </div>
            <div class="stat-info">
                <h3>55</h3>
                <p>Released Results</p>
                <span class="trend up"><i class="bi bi-arrow-up"></i> 9.1%</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-info">
                <h3>₱38,450</h3>
                <p>Today's Revenue</p>
                <span class="trend up"><i class="bi bi-arrow-up"></i> 15.2%</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="stat-info">
                <h3>₱1.24M</h3>
                <p>Monthly Revenue</p>
                <span class="trend up"><i class="bi bi-arrow-up"></i> 18.6%</span>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
        <!-- Revenue Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h5><i class="bi bi-graph-up"></i> Revenue Overview</h5>
                <span class="badge-year">2026</span>
            </div>
            <div class="chart-body">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
        
        <!-- Top Lab Tests -->
        <div class="chart-card">
            <div class="chart-header">
                <h5><i class="bi bi-flask"></i> Top Lab Tests</h5>
                <span class="badge-year">This Month</span>
            </div>
            <div class="chart-body">
                <div class="test-list">
                    <div class="test-item">
                        <span class="test-name">CBC</span>
                        <div class="test-bar"><div class="test-fill" style="width: 95%; background: #0148ca;"></div></div>
                        <span class="test-count">245</span>
                    </div>
                    <div class="test-item">
                        <span class="test-name">Urinalysis</span>
                        <div class="test-bar"><div class="test-fill" style="width: 82%; background: #04ccab;"></div></div>
                        <span class="test-count">189</span>
                    </div>
                    <div class="test-item">
                        <span class="test-name">Blood Chemistry</span>
                        <div class="test-bar"><div class="test-fill" style="width: 70%; background: #ff6b00;"></div></div>
                        <span class="test-count">156</span>
                    </div>
                    <div class="test-item">
                        <span class="test-name">Lipid Profile</span>
                        <div class="test-bar"><div class="test-fill" style="width: 55%; background: #800080;"></div></div>
                        <span class="test-count">98</span>
                    </div>
                    <div class="test-item">
                        <span class="test-name">Drug Test</span>
                        <div class="test-bar"><div class="test-fill" style="width: 40%; background: #17a2b8;"></div></div>
                        <span class="test-count">67</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Charts -->
    <div class="charts-row">
        <!-- Daily Patient Visits -->
        <div class="chart-card">
            <div class="chart-header">
                <h5><i class="bi bi-people"></i> Daily Patient Visits</h5>
                <span class="badge-year">This Week</span>
            </div>
            <div class="chart-body">
                <canvas id="visitsChart"></canvas>
            </div>
        </div>
        
        <!-- Weekly Diagnostic Requests -->
        <div class="chart-card">
            <div class="chart-header">
                <h5><i class="bi bi-clipboard2-pulse"></i> Diagnostic Requests</h5>
                <span class="badge-year">Requested vs Completed</span>
            </div>
            <div class="chart-body">
                <canvas id="requestsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="activity-card">
        <div class="chart-header">
            <h5><i class="bi bi-clock-history"></i> Recent Activity</h5>
            <span class="badge-year">Latest</span>
        </div>
        <div class="activity-list">
            <div class="activity-item">
                <span class="activity-dot" style="background: #04ccab;"></span>
                <div class="activity-content">
                    <p>Patient Maria Santos registered</p>
                    <small>2 minutes ago</small>
                </div>
            </div>
            <div class="activity-item">
                <span class="activity-dot" style="background: #0148ca;"></span>
                <div class="activity-content">
                    <p>CBC result released for Juan Reyes</p>
                    <small>15 minutes ago</small>
                </div>
            </div>
            <div class="activity-item">
                <span class="activity-dot" style="background: #ff6b00;"></span>
                <div class="activity-content">
                    <p>Payment ₱2,500 received — Ref #INV-0847</p>
                    <small>1 hour ago</small>
                </div>
            </div>
            <div class="activity-item">
                <span class="activity-dot" style="background: #800080;"></span>
                <div class="activity-content">
                    <p>New diagnostic request from Dr. Cruz</p>
                    <small>3 hours ago</small>
                </div>
            </div>
            <div class="activity-item">
                <span class="activity-dot" style="background: #17a2b8;"></span>
                <div class="activity-content">
                    <p>Monthly report generated by Admin</p>
                    <small>5 hours ago</small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== DASHBOARD CONTAINER ===== */
.dashboard-container {
    padding: 0;
}

/* ===== WELCOME BANNER ===== */
.welcome-banner {
    background: linear-gradient(135deg, #0a2b4e 0%, #1a4a7a 100%);
    border-radius: 16px;
    padding: 1.75rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.welcome-text h1 {
    color: #fff;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.welcome-text p {
    color: rgba(255,255,255,0.7);
    margin: 0;
    font-size: 0.95rem;
}

.welcome-date {
    color: rgba(255,255,255,0.85);
    font-weight: 500;
    font-size: 0.95rem;
    background: rgba(255,255,255,0.1);
    padding: 0.5rem 1.25rem;
    border-radius: 30px;
    border: 1px solid rgba(255,255,255,0.1);
    white-space: nowrap;
}

.welcome-date i {
    margin-right: 0.5rem;
}

/* ===== STATS GRID ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
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

.stat-icon.blue { background: #e6f0fa; color: #0148ca; }
.stat-icon.green { background: #e8f5e9; color: #28a745; }
.stat-icon.orange { background: #fff3e0; color: #ff6b00; }
.stat-icon.teal { background: #e0f7fa; color: #17a2b8; }
.stat-icon.purple { background: #f3e5f5; color: #800080; }
.stat-icon.success { background: #e8f5e9; color: #04ccab; }
.stat-icon.primary { background: #e3f2fd; color: #0148ca; }
.stat-icon.danger { background: #fce4ec; color: #dc3545; }

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

.trend {
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    margin-top: 0.15rem;
}

.trend.up { color: #28a745; }
.trend.down { color: #dc3545; }

/* ===== CHARTS ===== */
.charts-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.chart-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.chart-header h5 {
    font-weight: 600;
    color: #0a2b4e;
    margin: 0;
    font-size: 0.95rem;
}

.chart-header h5 i {
    color: #0148ca;
    margin-right: 0.5rem;
}

.badge-year {
    background: #f0f4ff;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 30px;
    white-space: nowrap;
}

.chart-body {
    position: relative;
    min-height: 200px;
}

/* ===== TEST LIST ===== */
.test-list {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.test-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.test-name {
    width: 100px;
    font-size: 0.8rem;
    font-weight: 500;
    color: #0a2b4e;
    flex-shrink: 0;
}

.test-bar {
    flex: 1;
    height: 6px;
    background: #f0f4ff;
    border-radius: 3px;
    overflow: hidden;
}

.test-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.6s ease;
}

.test-count {
    font-size: 0.8rem;
    font-weight: 600;
    color: #0a2b4e;
    width: 35px;
    text-align: right;
}

/* ===== ACTIVITY ===== */
.activity-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.4rem 0;
    border-bottom: 1px solid #f0f4ff;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 0.3rem;
}

.activity-content p {
    margin: 0;
    font-size: 0.85rem;
    color: #0a2b4e;
}

.activity-content small {
    color: #94a3b8;
    font-size: 0.7rem;
}

/* ============================================
   RESPONSIVE - MOBILE FIRST
   ============================================ */

/* Tablet & Small Laptops */
@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 992px) {
    .charts-row {
        grid-template-columns: 1fr;
    }
    
    .chart-card {
        min-height: auto;
    }
}

/* Tablet */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .welcome-banner {
        flex-direction: column;
        align-items: flex-start;
        padding: 1.25rem;
    }
    
    .welcome-text h1 {
        font-size: 1.2rem;
    }
    
    .welcome-text p {
        font-size: 0.85rem;
    }
    
    .welcome-date {
        font-size: 0.8rem;
        padding: 0.4rem 1rem;
        white-space: normal;
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
    
    .chart-card {
        padding: 1rem;
    }
    
    .chart-header h5 {
        font-size: 0.85rem;
    }
    
    .test-name {
        width: 80px;
        font-size: 0.7rem;
    }
    
    .activity-item {
        padding: 0.3rem 0;
    }
    
    .activity-content p {
        font-size: 0.8rem;
    }
}

/* Mobile Large */
@media (max-width: 576px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
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
    
    .trend {
        font-size: 0.6rem;
    }
    
    .welcome-banner {
        padding: 1rem;
    }
    
    .welcome-text h1 {
        font-size: 1rem;
    }
    
    .welcome-date {
        font-size: 0.7rem;
        padding: 0.3rem 0.75rem;
    }
    
    .chart-card {
        padding: 0.75rem;
    }
    
    .chart-header {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .chart-header h5 {
        font-size: 0.8rem;
    }
    
    .badge-year {
        font-size: 0.6rem;
        padding: 0.15rem 0.5rem;
    }
    
    .test-item {
        gap: 0.4rem;
    }
    
    .test-name {
        width: 60px;
        font-size: 0.65rem;
    }
    
    .test-count {
        width: 30px;
        font-size: 0.65rem;
    }
    
    .activity-card {
        padding: 0.75rem;
    }
}

/* Mobile Small */
@media (max-width: 400px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 0.4rem;
    }
    
    .stat-card {
        padding: 0.5rem;
    }
    
    .stat-icon {
        width: 30px;
        height: 30px;
        font-size: 0.7rem;
        border-radius: 8px;
    }
    
    .stat-info h3 {
        font-size: 0.85rem;
    }
    
    .stat-info p {
        font-size: 0.6rem;
    }
    
    .welcome-text h1 {
        font-size: 0.9rem;
    }
    
    .welcome-text p {
        font-size: 0.75rem;
    }
    
    .chart-body {
        min-height: 150px;
    }
}
</style>

<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
        datasets: [{
            label: 'Revenue',
            data: [85000, 95000, 110000, 130000, 155000, 180000, 220000],
            borderColor: '#0148ca',
            backgroundColor: 'rgba(1, 72, 202, 0.08)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#0148ca',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Visits Chart
const visitsCtx = document.getElementById('visitsChart').getContext('2d');
new Chart(visitsCtx, {
    type: 'bar',
    data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [{
            label: 'Patient Visits',
            data: [65, 72, 58, 80, 75, 45, 30],
            backgroundColor: 'rgba(1, 72, 202, 0.7)',
            borderColor: '#0148ca',
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Requests Chart
const requestsCtx = document.getElementById('requestsChart').getContext('2d');
new Chart(requestsCtx, {
    type: 'bar',
    data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [
            {
                label: 'Requested',
                data: [42, 38, 45, 50, 48, 30, 20],
                backgroundColor: 'rgba(1, 72, 202, 0.7)',
                borderColor: '#0148ca',
                borderWidth: 1,
                borderRadius: 6
            },
            {
                label: 'Completed',
                data: [35, 32, 40, 45, 42, 25, 15],
                backgroundColor: 'rgba(4, 204, 171, 0.7)',
                borderColor: '#04ccab',
                borderWidth: 1,
                borderRadius: 6
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 15,
                    font: { size: 11 }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?= $this->endSection() ?>