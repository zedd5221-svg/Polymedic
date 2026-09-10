<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<?php
/* ------------------------------------------------------------------
   Safe local defaults. These do not change any controller logic,
   they only stop the view from failing when a variable is missing.
   ------------------------------------------------------------------ */
$revenueDataArr  = $revenueData  ?? ['labels' => [], 'values' => []];
$visitsDataArr   = $visitsData   ?? ['labels' => [], 'values' => []];
$requestsDataArr = $requestsData ?? ['labels' => [], 'requested' => [], 'completed' => []];

$hasRevenueChart  = !empty($revenueDataArr['labels'])  && !empty($revenueDataArr['values']);
$hasVisitsChart   = !empty($visitsDataArr['labels'])   && !empty($visitsDataArr['values']);
$hasRequestsChart = !empty($requestsDataArr['labels']) && !empty($requestsDataArr['requested']);

$monthlyRevenueVal = (float) ($monthlyRevenue ?? 0);
$avgMonthlyRevenue = $monthlyRevenueVal / max(1, (int) date('m'));

/* Only allow a colour value that is safe to place inside a style attribute. */
$safeColor = static function ($color, $fallback = '#0D9488') {
    $color = is_string($color) ? trim($color) : '';
    if ($color !== '' && preg_match('/^(#[0-9A-Fa-f]{3,8}|rgba?\([0-9.,%\s]+\)|hsla?\([0-9.,%\sdegrad]+\)|[A-Za-z]{3,20})$/', $color)) {
        return $color;
    }
    return $fallback;
};

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>

<div class="dashboard-wrapper">

    <!-- ===== TOP STATS ROW (7 Cards) ===== -->
    <div class="stats-row">

        <!-- Total Patients -->
        <a class="stat-card" href="<?= base_url('admin/patients') ?>">
            <div class="stat-top">
                <div class="stat-icon icon-cyan">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <svg class="stat-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
            <div class="stat-value"><?= number_format($totalPatients ?? 0) ?></div>
            <div class="stat-label">Total Patients</div>
            <div class="stat-sub">All registered patients</div>
        </a>

        <!-- Today's Patients -->
        <a class="stat-card" href="<?= base_url('admin/visits') ?>">
            <div class="stat-top">
                <div class="stat-icon icon-teal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <svg class="stat-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
            <div class="stat-value"><?= number_format($todayPatients ?? 0) ?></div>
            <div class="stat-label">Today's Patients</div>
            <div class="stat-sub">Checked in today</div>
        </a>

        <!-- Pending Requests -->
        <a class="stat-card" href="<?= base_url('admin/requests') ?>">
            <div class="stat-top">
                <div class="stat-icon icon-orange">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <svg class="stat-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
            <div class="stat-value"><?= number_format($pendingRequests ?? 0) ?></div>
            <div class="stat-label">Pending Requests</div>
            <div class="stat-sub">Awaiting processing</div>
        </a>

        <!-- Completed Requests -->
        <a class="stat-card" href="<?= base_url('admin/requests') ?>">
            <div class="stat-top">
                <div class="stat-icon icon-green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <svg class="stat-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
            <div class="stat-value"><?= number_format($completedRequests ?? 0) ?></div>
            <div class="stat-label">Completed Requests</div>
            <div class="stat-sub">Results encoded</div>
        </a>

        <!-- Released Results -->
        <a class="stat-card" href="<?= base_url('admin/results') ?>">
            <div class="stat-top">
                <div class="stat-icon icon-blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <svg class="stat-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
            <div class="stat-value"><?= number_format($releasedResults ?? 0) ?></div>
            <div class="stat-label">Released Results</div>
            <div class="stat-sub">Sent to doctors</div>
        </a>

        <!-- Today's Revenue -->
        <a class="stat-card" href="<?= base_url('admin/payments') ?>">
            <div class="stat-top">
                <div class="stat-icon icon-yellow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                </div>
                <svg class="stat-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
            <div class="stat-value stat-value-money">&#8369;<?= number_format($todayRevenue ?? 0, 2) ?></div>
            <div class="stat-label">Today's Revenue</div>
            <div class="stat-sub">Collected today</div>
        </a>

        <!-- Monthly Revenue -->
        <a class="stat-card" href="<?= base_url('admin/reports/revenue') ?>">
            <div class="stat-top">
                <div class="stat-icon icon-pink">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                        <line x1="3" y1="20" x2="21" y2="20"></line>
                    </svg>
                </div>
                <svg class="stat-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
            <div class="stat-value stat-value-money">&#8369;<?= number_format($monthlyRevenueVal, 2) ?></div>
            <div class="stat-label">Monthly Revenue</div>
            <div class="stat-sub"><?= date('F Y') ?></div>
        </a>
    </div>

    <!-- ===== MAIN ROW: Revenue Chart + Top Lab Tests ===== -->
    <div class="main-row">

        <!-- Revenue Overview Chart -->
        <div class="chart-card">
            <div class="card-header">
                <div class="card-title-group">
                    <h5 class="card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                            <line x1="3" y1="20" x2="21" y2="20"></line>
                        </svg>
                        Revenue Overview
                    </h5>
                    <span class="card-subtitle">Monthly revenue <?= date('Y') ?></span>
                </div>
                <div class="avg-box">
                    <span class="avg-label">Avg. monthly</span>
                    <span class="avg-value">&#8369;<?= number_format($avgMonthlyRevenue, 2) ?></span>
                </div>
            </div>
            <?php if ($hasRevenueChart): ?>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            <?php else: ?>
                <div class="empty-state empty-state-chart">
                    <p>No revenue data recorded yet</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Top Lab Tests -->
        <div class="chart-card">
            <div class="card-header">
                <div class="card-title-group">
                    <h5 class="card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 3h6"></path>
                            <path d="M10 3v6.5L4.5 18a2 2 0 0 0 1.8 3h11.4a2 2 0 0 0 1.8-3L14 9.5V3"></path>
                        </svg>
                        Top Lab Tests
                    </h5>
                    <span class="card-subtitle">Most requested</span>
                </div>
                <span class="badge-time">All Time</span>
            </div>
            <div class="tests-list">
                <?php if (!empty($topTests)): ?>
                    <?php
                    $testCounts = array_filter(array_column($topTests, 'count'), 'is_numeric');
                    $maxCount   = !empty($testCounts) ? max($testCounts) : 0;
                    foreach ($topTests as $test):
                        $count = (float) ($test['count'] ?? 0);
                        $width = ($maxCount > 0) ? min(100, ($count / $maxCount) * 100) : 0;
                    ?>
                        <div class="test-item">
                            <div class="test-top">
                                <span class="test-name"><?= esc($test['name'] ?? '') ?></span>
                                <span class="test-count"><?= number_format($count) ?></span>
                            </div>
                            <div class="test-progress">
                                <div class="test-fill" style="width: <?= round($width, 2) ?>%; background: <?= $safeColor($test['color'] ?? '') ?>;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No lab test data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== BOTTOM ROW: Daily Patient Visits + Diagnostic Requests + Quick Actions ===== -->
    <div class="bottom-row">

        <!-- Daily Patient Visits -->
        <div class="chart-card">
            <div class="card-header">
                <div class="card-title-group">
                    <h5 class="card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        Daily Patient Visits
                    </h5>
                    <span class="card-subtitle">This week</span>
                </div>
                <span class="badge-time">This Week</span>
            </div>
            <?php if ($hasVisitsChart): ?>
                <div class="chart-container">
                    <canvas id="visitsChart"></canvas>
                </div>
            <?php else: ?>
                <div class="empty-state empty-state-chart">
                    <p>No visits recorded this week</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Diagnostic Requests -->
        <div class="chart-card">
            <div class="card-header">
                <div class="card-title-group">
                    <h5 class="card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                        </svg>
                        Diagnostic Requests
                    </h5>
                    <span class="card-subtitle">Requested vs Completed</span>
                </div>
                <span class="badge-time">This Week</span>
            </div>
            <?php if ($hasRequestsChart): ?>
                <div class="chart-container">
                    <canvas id="requestsChart"></canvas>
                </div>
            <?php else: ?>
                <div class="empty-state empty-state-chart">
                    <p>No diagnostic requests this week</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="chart-card quick-actions-card">
            <div class="card-header">
                <div class="card-title-group">
                    <h5 class="card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
                        </svg>
                        Quick Actions
                    </h5>
                    <span class="card-subtitle">Navigation shortcuts</span>
                </div>
            </div>
            <div class="quick-actions-grid">
                <a href="<?= base_url('admin/patients/add') ?>" class="quick-action-item">
                    <div class="qa-icon qa-blue">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                    </div>
                    <div class="qa-text">
                        <span class="qa-title">New Patient</span>
                        <span class="qa-desc">Register patient</span>
                    </div>
                    <svg class="qa-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
                <a href="<?= base_url('admin/appointments') ?>" class="quick-action-item">
                    <div class="qa-icon qa-teal">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                            <line x1="12" y1="14" x2="12" y2="18"></line>
                            <line x1="10" y1="16" x2="14" y2="16"></line>
                        </svg>
                    </div>
                    <div class="qa-text">
                        <span class="qa-title">New Appointment</span>
                        <span class="qa-desc">Schedule visit</span>
                    </div>
                    <svg class="qa-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
                <a href="<?= base_url('admin/requests') ?>" class="quick-action-item">
                    <div class="qa-icon qa-orange">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                            <line x1="9" y1="14" x2="15" y2="14"></line>
                            <line x1="9" y1="18" x2="15" y2="18"></line>
                            <line x1="9" y1="10" x2="11" y2="10"></line>
                        </svg>
                    </div>
                    <div class="qa-text">
                        <span class="qa-title">Create Request</span>
                        <span class="qa-desc">Lab or X-Ray</span>
                    </div>
                    <svg class="qa-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
                <a href="<?= base_url('admin/reports') ?>" class="quick-action-item">
                    <div class="qa-icon qa-slate">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    <div class="qa-text">
                        <span class="qa-title">Reports</span>
                        <span class="qa-desc">Generate reports</span>
                    </div>
                    <svg class="qa-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- ===== RECENT ACTIVITY ===== -->
    <div class="chart-card full-width">
        <div class="card-header">
            <div class="card-title-group">
                <h5 class="card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Recent Activity
                </h5>
                <span class="card-subtitle">Latest system updates</span>
            </div>
            <button type="button" class="btn-refresh" onclick="refreshActivity()" aria-label="Refresh activity" title="Refresh activity">
                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
            </button>
        </div>
        <div class="activity-list">
            <?php if (!empty($recentActivity)): ?>
                <?php foreach ($recentActivity as $activity): ?>
                    <div class="activity-item">
                        <span class="activity-dot" style="background: <?= $safeColor($activity['color'] ?? '') ?>;"></span>
                        <div class="activity-content">
                            <p><?= esc($activity['message'] ?? '') ?></p>
                            <small><?= !empty($activity['time']) ? esc(date('M d, Y h:i A', strtotime($activity['time']))) : '' ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No recent activity</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Toast Container -->
<div id="toastContainer" role="status" aria-live="polite"></div>

<style>
/* ============================================
   DASHBOARD
   ============================================ */

.dashboard-wrapper,
#toastContainer {
    --db-accent:        #0D9488;
    --db-accent-soft:   #E6F7F7;
    --db-surface:       #FFFFFF;
    --db-canvas:        #F8FAFC;
    --db-subtle:        #F1F5F9;
    --db-border:        #E2E8F0;
    --db-border-strong: #CBD5E1;
    --db-text:          #0F172A;
    --db-text-soft:     #475569;
    --db-text-muted:    #94A3B8;
    --db-radius:        12px;
    --db-radius-sm:     8px;
    --db-radius-xs:     6px;
    --db-shadow:        0 1px 2px rgba(15, 23, 42, 0.04);
    --db-shadow-hover:  0 6px 16px rgba(15, 23, 42, 0.07);
    --db-gap:           20px;
}

.dashboard-wrapper {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--db-canvas);
    padding: 24px;
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    color: var(--db-text);
}

.dashboard-wrapper :focus-visible,
#toastContainer :focus-visible {
    outline: 2px solid var(--db-accent);
    outline-offset: 2px;
    border-radius: var(--db-radius-xs);
}

/* ===== STATS ROW ===== */
.stats-row {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: var(--db-gap);
}

.stat-card {
    display: block;
    background: var(--db-surface);
    border-radius: var(--db-radius);
    padding: 18px;
    box-shadow: var(--db-shadow);
    border: 1px solid var(--db-border);
    text-decoration: none;
    color: inherit;
    transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
}

.stat-card:hover,
.stat-card:focus-visible {
    border-color: var(--db-border-strong);
    box-shadow: var(--db-shadow-hover);
    text-decoration: none;
    color: inherit;
}

.stat-card:hover .stat-arrow {
    color: var(--db-text-soft);
}

.stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--db-radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-arrow {
    color: #CBD5E1;
    flex-shrink: 0;
    transition: color 0.18s ease;
}

.icon-cyan   { background: #E6F7F7; color: #0D9488; }
.icon-teal   { background: #E3F7ED; color: #059669; }
.icon-orange { background: #FFF4E5; color: #D97706; }
.icon-green  { background: #E3F7ED; color: #059669; }
.icon-blue   { background: #EAF2FE; color: #2563EB; }
.icon-yellow { background: #FEF3C7; color: #B45309; }
.icon-pink   { background: #FCE7F3; color: #DB2777; }

.stat-value {
    font-size: 1.65rem;
    font-weight: 700;
    color: var(--db-text);
    line-height: 1.15;
    margin-bottom: 6px;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
}

.stat-value-money {
    font-size: 1.35rem;
    overflow-wrap: anywhere;
}

.stat-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--db-text-soft);
    margin-bottom: 3px;
}

.stat-sub {
    font-size: 0.775rem;
    color: var(--db-text-muted);
}

/* ===== LAYOUT ROWS ===== */
.main-row {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
    gap: var(--db-gap);
    margin-bottom: var(--db-gap);
}

.bottom-row {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: var(--db-gap);
    margin-bottom: var(--db-gap);
}

/* ===== CHART CARDS ===== */
.chart-card {
    background: var(--db-surface);
    border-radius: var(--db-radius);
    padding: 20px;
    box-shadow: var(--db-shadow);
    border: 1px solid var(--db-border);
    min-width: 0;
}

.full-width {
    margin-bottom: var(--db-gap);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    flex-wrap: wrap;
    padding-bottom: 14px;
    margin-bottom: 16px;
    border-bottom: 1px solid var(--db-subtle);
}

.card-title-group {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.card-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--db-text);
    margin: 0 0 3px;
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: -0.01em;
}

.card-title svg {
    color: var(--db-accent);
    flex-shrink: 0;
}

.card-subtitle {
    font-size: 0.775rem;
    color: var(--db-text-muted);
}

.avg-box {
    text-align: right;
}

.avg-label {
    display: block;
    font-size: 0.725rem;
    color: var(--db-text-muted);
    margin-bottom: 2px;
}

.avg-value {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--db-text);
    font-variant-numeric: tabular-nums;
}

.badge-time {
    background: var(--db-subtle);
    color: var(--db-text-soft);
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    padding: 5px 10px;
    border-radius: var(--db-radius-xs);
    border: 1px solid var(--db-border);
    white-space: nowrap;
}

.btn-refresh {
    background: var(--db-surface);
    border: 1px solid var(--db-border);
    color: var(--db-text-soft);
    width: 34px;
    height: 34px;
    border-radius: var(--db-radius-xs);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.95rem;
    line-height: 1;
    transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
}

.btn-refresh:hover {
    background: var(--db-subtle);
    border-color: var(--db-border-strong);
    color: var(--db-text);
}

.btn-refresh[disabled] {
    opacity: 0.6;
    cursor: default;
}

.btn-refresh.is-busy i {
    animation: db-spin 0.8s linear infinite;
}

@keyframes db-spin {
    to { transform: rotate(360deg); }
}

.chart-container {
    height: 280px;
    position: relative;
}

/* ===== TESTS LIST ===== */
.tests-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.test-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}

.test-top {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 12px;
}

.test-name {
    font-size: 0.825rem;
    font-weight: 500;
    color: var(--db-text-soft);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

.test-count {
    font-size: 0.825rem;
    font-weight: 600;
    color: var(--db-text);
    font-variant-numeric: tabular-nums;
    flex-shrink: 0;
}

.test-progress {
    height: 6px;
    background: var(--db-subtle);
    border-radius: 3px;
    overflow: hidden;
}

.test-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.5s ease;
}

/* ===== QUICK ACTIONS ===== */
.quick-actions-card {
    display: flex;
    flex-direction: column;
}

.quick-actions-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
}

.quick-action-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 12px;
    background: var(--db-surface);
    border: 1px solid var(--db-border);
    border-radius: var(--db-radius-sm);
    text-decoration: none;
    transition: background-color 0.18s ease, border-color 0.18s ease;
}

.quick-action-item:hover,
.quick-action-item:focus-visible {
    background: var(--db-canvas);
    border-color: var(--db-border-strong);
    text-decoration: none;
}

.quick-action-item:hover .qa-arrow {
    color: var(--db-text-soft);
}

.qa-icon {
    width: 36px;
    height: 36px;
    border-radius: var(--db-radius-xs);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.qa-blue   { background: #EAF2FE; color: #2563EB; }
.qa-teal   { background: #E6F7F7; color: #0D9488; }
.qa-orange { background: #FFF4E5; color: #D97706; }
.qa-slate  { background: #EEF2F6; color: #475569; }

.qa-text {
    flex: 1;
    min-width: 0;
}

.qa-title {
    display: block;
    font-size: 0.83rem;
    font-weight: 600;
    color: var(--db-text);
    margin-bottom: 1px;
}

.qa-desc {
    display: block;
    font-size: 0.72rem;
    color: var(--db-text-muted);
}

.qa-arrow {
    color: #CBD5E1;
    flex-shrink: 0;
    transition: color 0.18s ease;
}

/* ===== ACTIVITY ===== */
.activity-list {
    display: flex;
    flex-direction: column;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 8px;
    margin: 0 -8px;
    border-bottom: 1px solid var(--db-subtle);
    border-radius: var(--db-radius-xs);
    transition: background-color 0.18s ease;
}

.activity-item:hover {
    background: var(--db-canvas);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-top: 6px;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
    min-width: 0;
}

.activity-content p {
    margin: 0 0 3px;
    font-size: 0.84rem;
    color: var(--db-text);
    line-height: 1.5;
}

.activity-content small {
    font-size: 0.735rem;
    color: var(--db-text-muted);
    font-variant-numeric: tabular-nums;
}

/* ===== EMPTY STATE ===== */
.empty-state {
    text-align: center;
    padding: 24px 16px;
    color: var(--db-text-muted);
    font-size: 0.84rem;
}

.empty-state p {
    margin: 0;
}

.empty-state-chart {
    height: 280px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--db-canvas);
    border: 1px dashed var(--db-border);
    border-radius: var(--db-radius-sm);
}

/* ===== TOAST ===== */
#toastContainer {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-width: calc(100vw - 40px);
}

.toast {
    background: var(--db-surface);
    border: 1px solid var(--db-border);
    border-radius: var(--db-radius-sm);
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.10);
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 280px;
    animation: slideIn 0.25s ease;
    border-left: 3px solid #2563EB;
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.toast.success { border-left-color: #059669; }
.toast.error   { border-left-color: #DC2626; }
.toast.warning { border-left-color: #D97706; }
.toast.info    { border-left-color: #2563EB; }

.toast i {
    font-size: 1.05rem;
    line-height: 1;
}

.toast.success i { color: #059669; }
.toast.error i   { color: #DC2626; }
.toast.warning i { color: #D97706; }
.toast.info i    { color: #2563EB; }

.toast-message {
    flex: 1;
    font-size: 0.84rem;
    color: var(--db-text);
    line-height: 1.45;
}

.toast-close {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--db-text-muted);
    font-size: 0.8rem;
    padding: 2px;
    line-height: 1;
}

.toast-close:hover {
    color: var(--db-text);
}

@keyframes slideIn {
    from { transform: translateX(16px); opacity: 0; }
    to   { transform: translateX(0); opacity: 1; }
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1500px) {
    .stats-row { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}

@media (max-width: 1200px) {
    .stats-row  { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .main-row   { grid-template-columns: minmax(0, 1fr); }
    .bottom-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 992px) {
    .dashboard-wrapper { padding: 16px; }
    .stats-row  { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .bottom-row { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 768px) {
    .stat-card         { padding: 16px; }
    .chart-card        { padding: 16px; }
    .stat-value        { font-size: 1.45rem; }
    .stat-value-money  { font-size: 1.2rem; }
    .chart-container,
    .empty-state-chart { height: 240px; }
}

@media (max-width: 576px) {
    .dashboard-wrapper { padding: 12px; }
    .stats-row  { grid-template-columns: minmax(0, 1fr); gap: 12px; }
    .main-row,
    .bottom-row { gap: 12px; }
    .card-header { flex-direction: column; align-items: flex-start; }
    .avg-box     { text-align: left; }
    .chart-container,
    .empty-state-chart { height: 220px; }

    #toastContainer {
        top: auto;
        bottom: 16px;
        left: 12px;
        right: 12px;
        max-width: none;
    }
    .toast { min-width: 0; }
}

/* ===== MOTION PREFERENCES ===== */
@media (prefers-reduced-motion: reduce) {
    .dashboard-wrapper *,
    #toastContainer * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* ===== PRINT ===== */
@media print {
    .dashboard-wrapper { background: #FFFFFF; padding: 0; min-height: 0; }
    .stat-card,
    .chart-card { box-shadow: none; break-inside: avoid; page-break-inside: avoid; }
    .btn-refresh,
    .quick-actions-card,
    #toastContainer { display: none !important; }
}
</style>

<script>
// ============================================
// CHART DATA
// ============================================

const revenueData  = <?= json_encode($revenueDataArr, $jsonFlags) ?>;
const visitsData   = <?= json_encode($visitsDataArr, $jsonFlags) ?>;
const requestsData = <?= json_encode($requestsDataArr, $jsonFlags) ?>;

// ============================================
// SHARED HELPERS
// ============================================

const DB_COLORS = {
    accent:  '#0D9488',
    success: '#10B981',
    muted:   '#E2E8F0',
    grid:    '#F1F5F9',
    tick:    '#94A3B8',
    text:    '#1E293B',
    border:  '#E2E8F0',
    surface: '#FFFFFF'
};

function dbFormatPeso(value, decimals) {
    const number = Number(value) || 0;
    return '\u20B1' + number.toLocaleString('en-PH', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

function dbCompactPeso(value) {
    const number = Number(value) || 0;
    if (Math.abs(number) >= 1000000) {
        return '\u20B1' + (number / 1000000).toLocaleString('en-PH', { maximumFractionDigits: 1 }) + 'M';
    }
    if (Math.abs(number) >= 1000) {
        return '\u20B1' + (number / 1000).toLocaleString('en-PH', { maximumFractionDigits: 1 }) + 'k';
    }
    return '\u20B1' + number.toLocaleString('en-PH', { maximumFractionDigits: 0 });
}

const dbBaseTooltip = {
    backgroundColor: DB_COLORS.surface,
    titleColor: DB_COLORS.text,
    bodyColor: DB_COLORS.text,
    borderColor: DB_COLORS.border,
    borderWidth: 1,
    padding: 10,
    cornerRadius: 6,
    titleFont: { size: 12, weight: '600' },
    bodyFont: { size: 12 }
};

const dbBaseLegend = {
    display: true,
    position: 'bottom',
    labels: {
        usePointStyle: true,
        pointStyle: 'circle',
        boxWidth: 8,
        boxHeight: 8,
        padding: 16,
        font: { size: 11 },
        color: '#64748B'
    }
};

function dbScales(yTickCallback) {
    return {
        y: {
            beginAtZero: true,
            border: { display: false },
            grid: { color: DB_COLORS.grid, drawBorder: false, drawTicks: false },
            ticks: {
                color: DB_COLORS.tick,
                font: { size: 11 },
                padding: 8,
                maxTicksLimit: 6,
                callback: yTickCallback
            }
        },
        x: {
            border: { display: false },
            grid: { display: false, drawBorder: false },
            ticks: { color: DB_COLORS.tick, font: { size: 11 }, padding: 6 }
        }
    };
}

// Guards against a missing canvas or a Chart.js file that failed to load,
// so one absent element can never stop the rest of the page scripts.
function dbInitChart(canvasId, config) {
    if (typeof Chart === 'undefined') { return null; }
    const canvas = document.getElementById(canvasId);
    if (!canvas) { return null; }
    return new Chart(canvas.getContext('2d'), config);
}

// ============================================
// 1. REVENUE CHART
// ============================================

dbInitChart('revenueChart', {
    type: 'line',
    data: {
        labels: revenueData.labels,
        datasets: [{
            label: 'Revenue',
            data: revenueData.values,
            borderColor: DB_COLORS.accent,
            backgroundColor: 'rgba(13, 148, 136, 0.06)',
            fill: true,
            tension: 0.35,
            pointBackgroundColor: DB_COLORS.accent,
            pointBorderColor: '#FFFFFF',
            pointBorderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 6,
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: Object.assign({}, dbBaseTooltip, {
                displayColors: false,
                callbacks: {
                    label: function (context) {
                        return 'Revenue: ' + dbFormatPeso(context.parsed.y, 2);
                    }
                }
            })
        },
        scales: dbScales(function (value) { return dbCompactPeso(value); })
    }
});

// ============================================
// 2. DAILY PATIENT VISITS CHART
// ============================================
// The optional "Target" series is drawn only when the controller supplies a
// real visitsData.targets array. No placeholder values are generated here.

const visitsDatasets = [{
    label: 'Visits',
    data: visitsData.values,
    backgroundColor: DB_COLORS.accent,
    borderRadius: 4,
    borderSkipped: false,
    barPercentage: 0.7,
    categoryPercentage: 0.8,
    maxBarThickness: 34
}];

if (Array.isArray(visitsData.targets) && visitsData.targets.length) {
    visitsDatasets.push({
        label: 'Target',
        data: visitsData.targets,
        backgroundColor: DB_COLORS.muted,
        borderRadius: 4,
        borderSkipped: false,
        barPercentage: 0.7,
        categoryPercentage: 0.8,
        maxBarThickness: 34
    });
}

dbInitChart('visitsChart', {
    type: 'bar',
    data: {
        labels: visitsData.labels,
        datasets: visitsDatasets
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: Object.assign({}, dbBaseLegend, { display: visitsDatasets.length > 1 }),
            tooltip: Object.assign({}, dbBaseTooltip, { displayColors: true })
        },
        scales: dbScales(undefined)
    }
});

// ============================================
// 3. DIAGNOSTIC REQUESTS CHART
// ============================================

dbInitChart('requestsChart', {
    type: 'bar',
    data: {
        labels: requestsData.labels,
        datasets: [
            {
                label: 'Requested',
                data: requestsData.requested,
                backgroundColor: DB_COLORS.accent,
                borderRadius: 4,
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8,
                maxBarThickness: 34
            },
            {
                label: 'Completed',
                data: requestsData.completed,
                backgroundColor: DB_COLORS.success,
                borderRadius: 4,
                borderSkipped: false,
                barPercentage: 0.7,
                categoryPercentage: 0.8,
                maxBarThickness: 34
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: dbBaseLegend,
            tooltip: Object.assign({}, dbBaseTooltip, { displayColors: true })
        },
        scales: dbScales(undefined)
    }
});

// ============================================
// TOAST NOTIFICATIONS
// ============================================

function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) { return; }

    const icons = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
    };

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;

    const icon = document.createElement('i');
    icon.className = `bi ${icons[type] || icons.info}`;
    icon.setAttribute('aria-hidden', 'true');

    const text = document.createElement('span');
    text.className = 'toast-message';
    text.textContent = message;

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'toast-close';
    close.setAttribute('aria-label', 'Dismiss notification');
    close.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
    close.onclick = function () { toast.remove(); };

    toast.appendChild(icon);
    toast.appendChild(text);
    toast.appendChild(close);
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(16px)';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

function refreshActivity() {
    const btn = document.querySelector('.btn-refresh');
    if (btn) {
        btn.classList.add('is-busy');
        btn.disabled = true;
    }
    window.location.reload();
}
</script>

<?= $this->endSection() ?>