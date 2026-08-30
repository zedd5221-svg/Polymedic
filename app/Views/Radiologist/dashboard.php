<?= $this->extend('layouts/RadiologistLayout') ?>

<?= $this->section('pageTitle') ?>Radiologist Dashboard<?= $this->endSection() ?>

<?= $this->section('radiologistContent') ?>

<div class="dashboard-container">

    <!-- ===== STATS ROW (6 cards, vertical layout) ===== -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['pending'] ?? 0 ?></h3>
                <p>Pending Read</p>
                <small>Awaiting interpretation</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['processing'] ?? 0 ?></h3>
                <p>In Reading</p>
                <small>Currently reading</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['completed'] ?? 0 ?></h3>
                <p>Completed</p>
                <small>Reports signed</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal">
                <i class="bi bi-file-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['released'] ?? 0 ?></h3>
                <p>Released</p>
                <small>Sent to doctor</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['critical'] ?? 0 ?></h3>
                <p>Critical Findings</p>
                <small>Needs urgent call</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-x-ray"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['total'] ?? 0 ?></h3>
                <p>Avg TAT</p>
                <small>Target: 60 min</small>
            </div>
        </div>
    </div>

    <!-- ===== CRITICAL FINDINGS ALERT ===== -->
    <?php if (!empty($criticalFindings)): ?>
    <div class="critical-alert">
        <div class="critical-header">
            <h5><i class="bi bi-exclamation-octagon"></i> Critical Findings — Immediate Action Required</h5>
            <span class="critical-count"><?= count($criticalFindings) ?> active</span>
        </div>
        <div class="critical-list">
            <?php foreach ($criticalFindings as $index => $finding): ?>
                <div class="critical-item">
                    <span class="modality-tag <?= strtolower($finding['modality']) ?>"><?= strtoupper($finding['modality']) ?></span>
                    <div class="critical-content">
                        <p class="patient-ref"><?= esc($finding['patient_name']) ?> · RAD-26-<?= str_pad($finding['id'], 4, '0', STR_PAD_LEFT) ?></p>
                        <p class="finding-text"><?= esc($finding['finding']) ?></p>
                    </div>
                    <div class="critical-time">
                        <span><?= date('h:i A', strtotime($finding['created_at'])) ?></span>
                        <?php if ($index === 0): ?>
                            <button class="btn-notify">Notify Now</button>
                        <?php else: ?>
                            <span class="btn-notified"><i class="bi bi-check-circle"></i> Notified</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== CHARTS ROW ===== -->
    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h5>Weekly Study Volume by Modality</h5>
                    <small>Stacked counts per day — week of <?= date('M d, Y') ?></small>
                </div>
                <div class="chart-legend-dots" id="weeklyLegend">
                    <!-- Legend will be populated by JavaScript -->
                </div>
            </div>
            <div class="chart-body">
                <canvas id="weeklyVolumeChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h5>Today's Breakdown</h5>
                    <small>Studies by modality</small>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="breakdownChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ===== LOWER ROW (Reading Worklist + Equipment Status) ===== -->
    <div class="lower-row">
        <!-- Reading Worklist -->
        <div class="queue-card">
            <div class="queue-header">
                <div class="queue-title">
                    <h5>Reading Worklist</h5>
                </div>
                <div class="queue-filters">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="stat">STAT</button>
                    <button class="filter-btn" data-filter="pending">Pending</button>
                    <button class="filter-btn" data-filter="reading">Reading</button>
                    <button class="filter-btn" data-filter="completed">Completed</button>
                    <button class="filter-btn" data-filter="released">Released</button>
                </div>
                <div class="queue-actions">
                    <button class="refresh-btn" onclick="refreshTable()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>

                </div>
            </div>
            <div class="table-responsive">
                <table class="table queue-table" id="queueTable">
                    <thead>
                        <tr>
                            <th>Accession</th>
                            <th>Patient</th>
                            <th>Modality</th>
                            <th>Study</th>
                            <th>Referring MD</th>
                            <th>Priority</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pendingExaminations)): ?>
                            <?php foreach ($pendingExaminations as $exam): ?>
                                <?php 
                                    $modality = 'X-Ray';
                                    if (strpos(strtolower($exam['exam_type']), 'ct') !== false) $modality = 'CT';
                                    elseif (strpos(strtolower($exam['exam_type']), 'mri') !== false) $modality = 'MRI';
                                    elseif (strpos(strtolower($exam['exam_type']), 'us') !== false || strpos(strtolower($exam['exam_type']), 'ultra') !== false) $modality = 'US';
                                    
                                    $priority = $exam['priority'] ?? 'Routine';
                                    $isStat = strtolower($priority) === 'stat';
                                ?>
                                <tr class="queue-row" data-status="<?= esc($exam['status']) ?>">
                                    <td class="accession">RAD-26-<?= str_pad($exam['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                    <td>
                                        <div class="patient-cell">
                                            <span class="patient-name"><?= esc($exam['patient_name']) ?></span>
                                            <small><?= esc($exam['age']) ?>y / <?= esc($exam['gender'][0] ?? 'F') ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="modality-tag <?= strtolower($modality) ?>"><?= $modality ?></span>
                                    </td>
                                    <td><?= esc($exam['exam_type']) ?></td>
                                    <td><?= $exam['doctor_name'] ?? 'Dr. Ana Cruz' ?></td>
                                    <td>
                                        <?php if ($isStat): ?>
                                            <span class="priority-badge stat"><i class="bi bi-circle-fill"></i> STAT</span>
                                        <?php else: ?>
                                            <span class="priority-badge routine"><i class="bi bi-circle-fill"></i> Routine</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('h:i A', strtotime($exam['exam_date'])) ?></td>
                                    <td>
                                        <span class="status-badge <?= esc($exam['status']) ?>">
                                            <i class="bi bi-circle-fill"></i>
                                            <?= ucfirst($exam['status']) ?>
                                        </span>
                                    </td>
                                    <td class="action-cell">
                                        <!-- ALWAYS SHOW ICONS - EYE AND PENCIL -->
                                        <a href="<?= base_url('radiologist/examination/view/' . $exam['id']) ?>" class="action-icons" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= base_url('radiologist/examination/view/' . $exam['id']) ?>" class="action-icons" title="Edit/Interpret">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p>No examinations in worklist</p>
                                        <small>All caught up!</small>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Equipment Status -->
        <div class="equipment-card">
            <div class="equipment-header">
                <h5>Equipment Status</h5>
                <span class="online-badge"><i class="bi bi-circle-fill"></i> 5/6 Online</span>
            </div>
            <div class="equipment-list">
                <div class="equipment-item">
                    <div class="equip-info">
                        <span class="equip-dot online"></span>
                        <div>
                            <p class="equip-name">X-Ray Room 1</p>
                            <small>8 in queue · 99.2% uptime</small>
                        </div>
                    </div>
                    <span class="equip-status online">Online</span>
                </div>
                <div class="equipment-item">
                    <div class="equip-info">
                        <span class="equip-dot online"></span>
                        <div>
                            <p class="equip-name">X-Ray Room 2</p>
                            <small>5 in queue · 98.8% uptime</small>
                        </div>
                    </div>
                    <span class="equip-status online">Online</span>
                </div>
                <div class="equipment-item">
                    <div class="equip-info">
                        <span class="equip-dot online"></span>
                        <div>
                            <p class="equip-name">CT Scanner</p>
                            <small>4 in queue · 97.4% uptime</small>
                        </div>
                    </div>
                    <span class="equip-status online">Online</span>
                </div>
                <div class="equipment-item">
                    <div class="equip-info">
                        <span class="equip-dot maintenance"></span>
                        <div>
                            <p class="equip-name">MRI 1.5T</p>
                            <small>Scheduled maintenance</small>
                        </div>
                    </div>
                    <span class="equip-status maintenance">Maintenance</span>
                </div>
                <div class="equipment-item">
                    <div class="equip-info">
                        <span class="equip-dot online"></span>
                        <div>
                            <p class="equip-name">US Room 1</p>
                            <small>6 in queue · 99.9% uptime</small>
                        </div>
                    </div>
                    <span class="equip-status online">Online</span>
                </div>
                <div class="equipment-item">
                    <div class="equip-info">
                        <span class="equip-dot online"></span>
                        <div>
                            <p class="equip-name">US Room 2</p>
                            <small>3 in queue · 99.6% uptime</small>
                        </div>
                    </div>
                    <span class="equip-status online">Online</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   RADIOLOGIST DASHBOARD - MATCHING SCREENSHOT
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
    --purple: #7c3aed;
    --purple-soft: #ede9fe;
    --green: #15803D;
    --green-soft: #E7F6EC;
    --cyan: #0E7490;
    --cyan-soft: #E0F2F4;
    --orange: #C2410C;
    --orange-soft: #FFF1E6;
    --red: #dc2626;
    --red-soft: #FEF2F2;
    font-family: 'Inter', sans-serif;
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

.stat-icon.purple { background: var(--purple-soft); color: var(--purple); }
.stat-icon.blue { background: var(--blue-soft); color: var(--blue); }
.stat-icon.green { background: var(--green-soft); color: var(--green); }
.stat-icon.teal { background: var(--cyan-soft); color: var(--cyan); }
.stat-icon.orange { background: var(--orange-soft); color: var(--orange); }
.stat-icon.primary { background: var(--blue-soft); color: var(--blue); }

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

/* ===== CRITICAL FINDINGS ===== */
.critical-alert {
    background: #fff5f5;
    border: 1px solid #fecaca;
    border-radius: 16px;
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.critical-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: #fef2f2;
    border-bottom: 1px solid #fecaca;
}

.critical-header h5 {
    font-weight: 700;
    color: #991b1b;
    margin: 0;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.critical-count {
    background: #dc2626;
    color: #ffffff;
    padding: 0.2rem 0.7rem;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 700;
}

.critical-list {
    padding: 0.5rem 1.5rem 1rem;
}

.critical-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #fee2e2;
}

.critical-item:last-child {
    border-bottom: none;
}

.modality-tag {
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
}

.modality-tag.x-ray { background: var(--blue-soft); color: var(--blue); }
.modality-tag.ct { background: var(--cyan-soft); color: var(--cyan); }
.modality-tag.mri { background: var(--purple-soft); color: var(--purple); }
.modality-tag.us { background: var(--green-soft); color: var(--green); }

.critical-content {
    flex: 1;
}

.patient-ref {
    font-weight: 700;
    color: var(--ink);
    margin: 0 0 0.15rem;
    font-size: 0.85rem;
}

.finding-text {
    color: var(--ink-soft);
    margin: 0;
    font-size: 0.8rem;
}

.critical-time {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.75rem;
    color: var(--ink-soft);
}

.btn-notify {
    background: #dc2626;
    color: #ffffff;
    border: none;
    padding: 0.35rem 0.85rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
}

.btn-notified {
    color: var(--green);
    font-weight: 600;
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

.chart-header small {
    color: var(--ink-soft);
    font-size: 0.8rem;
    font-weight: 400;
}

.chart-legend-dots {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.chart-legend-dots span {
    font-size: 0.75rem;
    color: var(--ink-soft);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.dot.blue { background: var(--blue); }
.dot.cyan { background: var(--cyan); }
.dot.purple { background: var(--purple); }
.dot.green { background: var(--green); }

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
    padding: 1.5rem;
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

.queue-actions {
    display: flex;
    gap: 0.5rem;
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

.new-study-btn {
    background: var(--blue);
    color: #ffffff;
    border: none;
    padding: 0.4rem 0.85rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s ease;
}

.new-study-btn:hover {
    background: #1e40af;
}

/* ===== TABLE ===== */
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
    padding: 0.75rem;
    background: var(--surface-alt);
    white-space: nowrap;
}

.queue-table thead th:first-child {
    border-radius: 8px 0 0 0;
}

.queue-table thead th:last-child {
    border-radius: 0 8px 0 0;
}

.queue-table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    font-size: 0.8rem;
    color: var(--ink);
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
}

.queue-table tbody tr:last-child td {
    border-bottom: none;
}

.queue-table tbody tr:hover {
    background: var(--surface-alt);
}

.accession {
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: 0.7rem;
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

.modality-tag {
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
}

.modality-tag.x-ray { background: var(--blue-soft); color: var(--blue); }
.modality-tag.ct { background: var(--cyan-soft); color: var(--cyan); }
.modality-tag.mri { background: var(--purple-soft); color: var(--purple); }
.modality-tag.us { background: var(--green-soft); color: var(--green); }

.priority-badge {
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
}

.priority-badge.stat {
    background: var(--red-soft);
    color: var(--red);
}

.priority-badge.routine {
    background: var(--surface-alt);
    color: var(--ink-soft);
}

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
    background: var(--cyan-soft);
    color: var(--cyan);
}

/* ===== ACTION CELL - ALWAYS SHOW ICONS ===== */
.action-cell {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    justify-content: flex-start;
}

/* Light blue/gray icon style - matching screenshot */
.action-icons {
    color: #93c5fd; /* LIGHT BLUE */
    font-size: 1.1rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.action-icons:hover {
    color: var(--blue);
    background: var(--blue-soft);
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

/* ===== EQUIPMENT STATUS ===== */
.equipment-card {
    background: var(--surface);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid var(--line);
}

.equipment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.equipment-header h5 {
    font-weight: 700;
    color: var(--ink);
    margin: 0;
    font-size: 1rem;
}

.online-badge {
    background: var(--green-soft);
    color: var(--green);
    padding: 0.2rem 0.7rem;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
}

.equipment-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.equipment-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--line);
}

.equipment-item:last-child {
    border-bottom: none;
}

.equip-info {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.equip-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.equip-dot.online {
    background: var(--green);
}

.equip-dot.maintenance {
    background: var(--orange);
}

.equip-name {
    font-weight: 600;
    color: var(--ink);
    margin: 0;
    font-size: 0.8rem;
}

.equip-info small {
    color: var(--ink-soft);
    font-size: 0.7rem;
}

.equip-status {
    font-size: 0.7rem;
    font-weight: 600;
}

.equip-status.online {
    color: var(--green);
}

.equip-status.maintenance {
    color: var(--orange);
}

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
    
    .queue-actions {
        width: 100%;
        justify-content: space-between;
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
    
    .critical-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .critical-time {
        width: 100%;
        justify-content: space-between;
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
    // ===== WEEKLY STUDY VOLUME (Stacked Bar Chart) =====
    const weeklyCtx = document.getElementById('weeklyVolumeChart').getContext('2d');
    
    // Get data from PHP
    const weeklyLabels = <?= $weeklyLabels ?? '["Mon","Tue","Wed","Thu","Fri","Sat","Sun"]' ?>;
    const weeklyDatasets = <?= $weeklyDatasets ?? '[]' ?>;
    
    // If no datasets or all zeros, show empty state data
    let datasets = weeklyDatasets;
    if (!datasets || datasets.length === 0) {
        datasets = [{
            label: 'No Data',
            data: [0, 0, 0, 0, 0, 0, 0],
            backgroundColor: '#94A3B8',
            borderRadius: 4,
            barPercentage: 0.6
        }];
    }
    
    // Chart.js color palette for modalities
    const colorPalette = {
        'X-Ray': '#1D4ED8',
        'CT': '#0E7490',
        'MRI': '#7c3aed',
        'Ultrasound': '#16a34a',
        'Mammography': '#f59e0b',
        'Other': '#94A3B8'
    };
    
    // Apply colors to datasets if not already set
    datasets = datasets.map(ds => {
        if (!ds.backgroundColor || ds.backgroundColor === '#94A3B8') {
            ds.backgroundColor = colorPalette[ds.label] || '#94A3B8';
        }
        return ds;
    });
    
    // ===== UPDATE LEGEND =====
    const legendContainer = document.getElementById('weeklyLegend');
    if (legendContainer) {
        let legendHtml = '';
        datasets.forEach(ds => {
            const color = ds.backgroundColor || '#94A3B8';
            legendHtml += `<span><i class="dot" style="background:${color};"></i> ${ds.label}</span>`;
        });
        legendContainer.innerHTML = legendHtml;
    }
    
    new Chart(weeklyCtx, {
        type: 'bar',
        data: {
            labels: weeklyLabels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { color: '#94A3B8' }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: { color: '#F0F2F5' },
                    ticks: { color: '#94A3B8', stepSize: 1 }
                }
            }
        }
    });

    // ===== TODAY'S BREAKDOWN (Horizontal Bar Chart) =====
    const breakdownCtx = document.getElementById('breakdownChart').getContext('2d');
    
    // Get breakdown data from PHP
    const breakdownData = <?= json_encode($breakdownData ?? ['labels' => ['No Data'], 'data' => [0]]) ?>;
    
    // Colors for breakdown chart
    const breakdownColors = {
        'X-Ray': '#1D4ED8',
        'CT': '#0E7490',
        'MRI': '#7c3aed',
        'Ultrasound': '#16a34a',
        'Mammography': '#f59e0b',
        'Other': '#94A3B8',
        'No Data': '#94A3B8'
    };
    
    const bgColors = breakdownData.labels.map(label => breakdownColors[label] || '#94A3B8');
    
    new Chart(breakdownCtx, {
        type: 'bar',
        data: {
            labels: breakdownData.labels,
            datasets: [{
                label: 'Studies',
                data: breakdownData.data,
                backgroundColor: bgColors,
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
            const text = row.textContent.toLowerCase();
            
            if (filter === 'all' || status === filter || text.includes(filter)) {
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