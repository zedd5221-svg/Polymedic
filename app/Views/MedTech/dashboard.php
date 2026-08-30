<?= $this->extend('layouts/MedTechLayout') ?>

<?= $this->section('pageTitle') ?>MedTech Dashboard<?= $this->endSection() ?>

<?= $this->section('medtechContent') ?>

<div class="dashboard-container">

    <!-- ===== STATS ROW ===== -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-flask"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['total'] ?? 0 ?></h3>
                <p>Total Requests</p>
                <small>All specimens</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['pending'] ?? 0 ?></h3>
                <p>Pending</p>
                <small>Awaiting processing</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon cyan">
                <i class="bi bi-activity"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['in_progress'] ?? 0 ?></h3>
                <p>In-Progress</p>
                <small>Currently running</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['completed'] ?? 0 ?></h3>
                <p>Completed</p>
                <small>Results encoded</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal">
                <i class="bi bi-send-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['released'] ?? 0 ?></h3>
                <p>Released</p>
                <small>Sent to doctor</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <div class="stat-info">
                <h3><?= ($counts['total'] ?? 0) - ($counts['released'] ?? 0) ?></h3>
                <p>In Queue</p>
                <small>Waiting for processing</small>
            </div>
        </div>
    </div>

    <!-- ===== CHARTS ROW ===== -->
    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h5>Specimen Intake — Today</h5>
                    <small>Hourly volume received</small>
                </div>
                <span class="date-badge"><?= date('F d, Y') ?></span>
            </div>
            <div class="chart-body">
                <canvas id="intakeChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h5>Volume by Section</h5>
                    <small>Tests today per laboratory section</small>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="sectionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ===== SPECIMEN QUEUE ===== -->
    <div class="lower-row">
        <div class="queue-card">
            <div class="queue-header">
                <div class="queue-title">
                    <h5>Specimen Queue</h5>
                    <small class="text-muted">Showing <?= count($allRequests ?? []) ?> of <?= $totalRequests ?? 0 ?> specimens</small>
                </div>
                <div class="queue-filters">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="pending">Pending</button>
                    <button class="filter-btn" data-filter="in_progress">Processing</button>
                    <button class="filter-btn" data-filter="completed">Completed</button>
                    <button class="filter-btn" data-filter="released">Released</button>
                </div>
                <button class="refresh-btn" onclick="refreshTable()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
            <div class="table-responsive">
                <table class="table queue-table" id="queueTable">
                    <thead>
                        <tr>
                            <th>Accession No.</th>
                            <th>Patient</th>
                            <th>Test</th>
                            <th>Received</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($allRequests)): ?>
                            <?php foreach ($allRequests as $request): ?>
                                <tr class="queue-row" data-status="<?= esc($request['status']) ?>">
                                    <td class="accession">LAB-<?= date('y') ?>-<?= str_pad($request['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                    <td>
                                        <div class="patient-cell">
                                            <span class="patient-name"><?= esc($request['patient_name']) ?></span>
                                            <small><?= esc($request['age']) ?> yrs · <?= esc($request['gender']) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $services = explode(', ', $request['lab_services'] ?? '');
                                        $displayServices = array_slice($services, 0, 2);
                                        $moreCount = count($services) - 2;
                                        ?>
                                        <?php foreach ($displayServices as $service): ?>
                                            <?php if (!empty($service)): ?>
                                                <span class="service-tag"><?= esc($service) ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php if ($moreCount > 0): ?>
                                            <span class="service-tag more">+<?= $moreCount ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('h:i A', strtotime($request['created_at'] ?? date('Y-m-d H:i:s'))) ?></td>
                                    <td>
                                        <span class="status-badge <?= esc($request['status']) ?>">
                                            <i class="bi bi-circle-fill"></i>
                                            <?= ucfirst(str_replace('_', ' ', $request['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('medtech/request/view/' . $request['id']) ?>" class="action-icons" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($request['status'] !== 'released' && $request['status'] !== 'completed'): ?>
                                            <a href="<?= base_url('medtech/request/view/' . $request['id']) ?>" class="action-icons" title="Process">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p>No specimens in queue</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== TURNAROUND TIME ===== -->
        <div class="tat-card">
            <div class="tat-header">
                <h5>Turnaround Time</h5>
                <small>Avg vs target (today)</small>
            </div>
            <div class="tat-body">
                <?php 
                $tests = $turnaroundData ?? [];
                if (!empty($tests)): 
                    foreach ($tests as $test): 
                        $pct = $test['avg'] > 0 ? min(($test['avg'] / $test['target']) * 100, 100) : 0;
                        $barColor = $test['color'] === 'green' ? '#16a34a' : ($test['color'] === 'orange' ? '#f59e0b' : ($test['color'] === 'red' ? '#dc2626' : '#94A3B8'));
                ?>
                <div class="tat-item">
                    <div class="tat-label">
                        <span><?= $test['name'] ?></span>
                        <span class="tat-target">target <?= $test['target'] ?>m</span>
                    </div>
                    <div class="tat-bar-wrap">
                        <div class="tat-bar" style="width: <?= $pct ?>%; background: <?= $barColor ?>;"></div>
                    </div>
                    <div class="tat-value">
                        <span><?= $test['avg'] > 0 ? $test['avg'] . ' min' : 'No data' ?></span>
                    </div>
                </div>
                <?php 
                    endforeach; 
                else: 
                ?>
                <div class="tat-item">
                    <div class="tat-label">
                        <span>No data available</span>
                    </div>
                    <div class="tat-bar-wrap">
                        <div class="tat-bar" style="width: 0%; background: #94A3B8;"></div>
                    </div>
                    <div class="tat-value">
                        <span>—</span>
                    </div>
                </div>
                <?php endif; ?>
                <div class="tat-legend">
                    <span><i class="bi bi-circle-fill green"></i> On time</span>
                    <span><i class="bi bi-circle-fill orange"></i> Near limit</span>
                    <span><i class="bi bi-circle-fill red"></i> Exceeded</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   DASHBOARD STYLES - VERTICAL STAT CARDS
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
    --orange: #C2410C;
    --orange-soft: #FFF1E6;
    --green: #15803D;
    --green-soft: #E7F6EC;
    --cyan: #0E7490;
    --cyan-soft: #E0F2F4;
    --teal: #0d9488;
    --teal-soft: #ccfbf1;
    --purple: #7c3aed;
    --purple-soft: #ede9fe;
    --red: #dc2626;
    --red-soft: #fef2f2;
    font-family: 'Inter', sans-serif;
}

/* ===== STATS ROW ===== */
.stats-row {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

/* ===== VERTICAL STAT CARD LAYOUT ===== */
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
    min-height: 160px;
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

.stat-icon.blue   { background: var(--blue-soft); color: var(--blue); }
.stat-icon.orange { background: var(--orange-soft); color: var(--orange); }
.stat-icon.cyan   { background: var(--cyan-soft); color: var(--cyan); }
.stat-icon.green  { background: var(--green-soft); color: var(--green); }
.stat-icon.teal  { background: var(--teal-soft); color: var(--teal); }
.stat-icon.purple { background: var(--purple-soft); color: var(--purple); }

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
    grid-template-columns: 2fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.chart-card {
    background: var(--surface);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
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

.chart-header small {
    color: var(--ink-soft);
    font-size: 0.8rem;
    font-weight: 400;
}

.date-badge {
    background: #E8EFFE;
    color: var(--blue);
    padding: 0.25rem 0.75rem;
    border-radius: 30px;
    font-size: 0.8rem;
    font-weight: 600;
}

.chart-body {
    position: relative;
    height: 300px;
}

/* ===== LOWER ROW ===== */
.lower-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1rem;
}

/* ===== QUEUE CARD ===== */
.queue-card {
    background: var(--surface);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid var(--line);
}

.queue-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.queue-title h5 {
    font-weight: 700;
    color: var(--ink);
    margin: 0;
    font-size: 1rem;
}

.queue-title small {
    font-size: 0.75rem;
    color: var(--ink-soft);
}

.queue-filters {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.filter-btn {
    background: transparent;
    border: none;
    padding: 0.3rem 0.75rem;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--ink-soft);
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-btn:hover {
    background: var(--surface-alt);
}

.filter-btn.active {
    background: var(--blue);
    color: #ffffff;
}

.refresh-btn {
    background: var(--surface);
    border: 1px solid var(--line);
    padding: 0.4rem 0.85rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--ink);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s ease;
}

.refresh-btn:hover {
    background: var(--surface-alt);
}

/* ===== QUEUE TABLE ===== */
.queue-table {
    margin: 0;
}

.queue-table thead th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--ink-faint);
    font-weight: 600;
    border-bottom: 1px solid var(--line);
    padding: 0.7rem 0.75rem;
    background: var(--surface-alt);
    white-space: nowrap;
}

.queue-table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: var(--ink);
    border-bottom: 1px solid var(--line);
}

.queue-table tbody tr:hover {
    background: var(--surface-alt);
}

.accession {
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--blue);
}

.patient-cell {
    display: flex;
    flex-direction: column;
}

.patient-name {
    font-weight: 600;
    color: var(--ink);
}

.patient-cell small {
    font-size: 0.65rem;
    color: var(--ink-soft);
}

.service-tag {
    background: var(--blue-soft);
    color: var(--blue);
    padding: 0.1rem 0.5rem;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 500;
    display: inline-block;
    margin: 0.1rem;
}

.service-tag.more {
    background: var(--surface-alt);
    color: var(--ink-soft);
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

.status-badge.pending {
    background: var(--orange-soft);
    color: var(--orange);
}

.status-badge.in_progress {
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

.action-icons {
    color: var(--ink-faint);
    margin: 0 0.25rem;
    font-size: 0.95rem;
    transition: color 0.2s ease;
    text-decoration: none;
}

.action-icons:hover {
    color: var(--blue);
}

.empty-state {
    text-align: center;
    padding: 2rem;
}

.empty-state i {
    font-size: 2rem;
    color: var(--ink-faint);
    margin-bottom: 0.5rem;
    display: block;
}

.empty-state p {
    color: var(--ink-soft);
    font-weight: 500;
}

/* ===== TURNAROUND TIME ===== */
.tat-card {
    background: var(--surface);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid var(--line);
}

.tat-header {
    margin-bottom: 1.25rem;
}

.tat-header h5 {
    font-weight: 700;
    color: var(--ink);
    margin: 0 0 0.15rem;
    font-size: 1rem;
}

.tat-header small {
    color: var(--ink-soft);
    font-size: 0.8rem;
}

.tat-item {
    margin-bottom: 1rem;
}

.tat-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.4rem;
    font-size: 0.8rem;
    font-weight: 600;
}

.tat-target {
    color: var(--ink-faint);
    font-weight: 400;
}

.tat-bar-wrap {
    height: 8px;
    background: var(--surface-alt);
    border-radius: 6px;
    overflow: hidden;
    position: relative;
}

.tat-bar {
    height: 100%;
    border-radius: 6px;
    transition: width 0.5s ease;
}

.tat-value {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--ink-soft);
    text-align: right;
}

.tat-legend {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--line);
    font-size: 0.7rem;
    color: var(--ink-soft);
}

.tat-legend i {
    font-size: 0.5rem;
    margin-right: 0.25rem;
}

.tat-legend .green { color: var(--green); }
.tat-legend .orange { color: var(--orange); }
.tat-legend .red { color: var(--red); }

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1400px) {
    .stats-row {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 1200px) {
    .charts-row,
    .lower-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 992px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .queue-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .queue-filters {
        flex-wrap: wrap;
    }
    
    .stats-row {
        grid-template-columns: 1fr 1fr;
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
}

@media (max-width: 576px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .chart-body {
        height: 250px;
    }
    
    .queue-table {
        font-size: 0.75rem;
    }
    
    .queue-table thead th,
    .queue-table tbody td {
        padding: 0.5rem;
    }
}
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== SPECIMEN INTAKE CHART - 24 HOURS =====
    const intakeCtx = document.getElementById('intakeChart').getContext('2d');
    
    // Get data from PHP
    const intakeLabels = <?= json_encode($intakeData['labels'] ?? []) ?>;
    const intakeData = <?= json_encode($intakeData['data'] ?? []) ?>;
    
    // Fallback data if empty
    const labels = intakeLabels.length > 0 ? intakeLabels : Array.from({length: 24}, (_, i) => String(i).padStart(2, '0') + ':00');
    const data = intakeData.length > 0 ? intakeData : Array(24).fill(0);
    
    new Chart(intakeCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Specimen Intake',
                data: data,
                borderColor: '#1D4ED8',
                backgroundColor: 'rgba(29, 78, 216, 0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointBackgroundColor: '#1D4ED8',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#F0F2F5' },
                    ticks: { color: '#94A3B8', font: { size: 11 }, stepSize: 1 }
                },
                x: {
                    grid: { display: false },
                    ticks: { 
                        color: '#94A3B8', 
                        font: { size: 9 },
                        maxTicksLimit: 12,
                        maxRotation: 45
                    }
                }
            }
        }
    });

    // ===== VOLUME BY SECTION CHART =====
    const sectionCtx = document.getElementById('sectionChart').getContext('2d');
    
    // Get data from PHP
    const sectionLabels = <?= json_encode($sectionData['labels'] ?? ['No Data']) ?>;
    const sectionValues = <?= json_encode($sectionData['data'] ?? [0]) ?>;
    const maxValue = Math.max(...sectionValues, 1);
    
    new Chart(sectionCtx, {
        type: 'bar',
        data: {
            labels: sectionLabels,
            datasets: [{
                label: 'Tests',
                data: sectionValues,
                backgroundColor: '#1D4ED8',
                borderRadius: 4,
                barThickness: 16
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    max: maxValue + 5,
                    grid: { color: '#F0F2F5' },
                    ticks: { color: '#94A3B8', stepSize: 1 }
                },
                y: {
                    grid: { display: false },
                    ticks: { color: '#374151', font: { size: 12, weight: '600' } }
                }
            }
        }
    });
});

// ===== QUEUE FILTER =====
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.dataset.filter;
        const rows = document.querySelectorAll('#queueTable .queue-row');
        
        rows.forEach(row => {
            const status = row.dataset.status ? row.dataset.status.toLowerCase() : '';
            if (filter === 'all' || status === filter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});

// ===== REFRESH =====
function refreshTable() {
    const btn = document.querySelector('.refresh-btn');
    const icon = btn.querySelector('i');
    icon.style.animation = 'spin 0.8s linear infinite';
    
    setTimeout(() => {
        icon.style.animation = 'none';
        location.reload();
    }, 800);
}

// ===== Add spin animation =====
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>

<?= $this->endSection() ?>