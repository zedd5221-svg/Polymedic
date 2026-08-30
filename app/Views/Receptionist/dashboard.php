<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<div class="dashboard-container">

    <!-- ===== STATS ROW (6 cards, vertical layout) ===== -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon teal">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= count($today_appointments ?? []) ?></h3>
                <p>Today's Appointments</p>
                <small>All scheduled today</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-info">
                <h3><?= $pending_appointments ?? 0 ?></h3>
                <p>Pending Appointments</p>
                <small>Awaiting approval</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-person-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $today_completed ?? 0 ?></h3>
                <p>Completed Today</p>
                <small>Appointments done</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="bi bi-file-medical"></i>
            </div>
            <div class="stat-info">
                <h3><?= $pending_diagnostic ?? 0 ?></h3>
                <p>Pending Diagnostics</p>
                <small>Tests requested</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <h3><?= $unpaid_bills ?? 0 ?></h3>
                <p>Unpaid Bills</p>
                <small>Outstanding balance</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-info">
                <h3>₱<?= number_format($today_collections ?? 0, 2) ?></h3>
                <p>Today's Collections</p>
                <small>Payments received</small>
            </div>
        </div>
    </div>

    <!-- ===== CHARTS ROW (Appointment Trends + Service Distribution) ===== -->
    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h5>Appointment Trends</h5>
                    <small>Last 7 days</small>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="appointmentsChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <div class="chart-header">
                <div>
                    <h5>Service Distribution</h5>
                    <small>Today's appointments</small>
                </div>
            </div>
            <div class="chart-body">
                <canvas id="servicesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ===== TODAY'S APPOINTMENTS TABLE ===== -->
    <div class="appointments-card">
        <div class="card-header-custom">
            <h5><i class="bi bi-calendar3"></i> Today's Appointments</h5>
            <div class="header-right-group">
                <span class="badge-custom"><?= date('F d, Y') ?></span>
                <a href="<?= base_url('receptionist/appointments') ?>" class="btn-view-all">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table receptionist-table" id="todayAppointmentsTable">
                <thead>
                    <tr>
                        <th>Accession No.</th>
                        <th>Patient</th>
                        <th>Service</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($today_appointments) && count($today_appointments) > 0): ?>
                        <?php foreach ($today_appointments as $index => $appointment): ?>
                            <?php
                                $nameParts = preg_split('/\s+/', trim($appointment['full_name']));
                                $initials = strtoupper(mb_substr($nameParts[0] ?? '', 0, 1) . mb_substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));
                            ?>
                            <tr>
                                <td class="accession-cell">APT-<?= date('y') ?>-<?= str_pad($appointment['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div class="patient-cell">
                                        <span class="patient-avatar"><?= esc($initials) ?></span>
                                        <div class="patient-meta">
                                            <span class="patient-name"><?= esc($appointment['full_name']) ?></span>
                                            <small><?= esc($appointment['age']) ?> yrs · <?= esc($appointment['gender']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="service-tag"><?= ucfirst(esc($appointment['service_type'])) ?></span></td>
                                <td><strong><?= esc($appointment['appointment_time']) ?></strong></td>
                                <td>
                                    <span class="status-badge <?= $appointment['status'] ?>">
                                        <i class="bi bi-circle-fill"></i>
                                        <?= ucfirst($appointment['status']) ?>
                                    </span>
                                </td>
                                <td class="action-cell">
                                    <a href="<?= base_url('receptionist/appointment/view/' . $appointment['id']) ?>" 
                                       class="action-icon-btn view" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($appointment['status'] == 'pending'): ?>
                                        <a href="<?= base_url('receptionist/appointment/approve/' . $appointment['id']) ?>" 
                                           class="action-icon-btn approve" title="Approve"
                                           onclick="return confirm('Approve this appointment?')">
                                            <i class="bi bi-check2"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($appointment['status'] == 'approved'): ?>
                                        <a href="<?= base_url('receptionist/appointment/complete/' . $appointment['id']) ?>" 
                                           class="action-icon-btn complete" title="Complete"
                                           onclick="return confirm('Mark this appointment as completed?')">
                                            <i class="bi bi-check-all"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($appointment['status'] == 'pending' || $appointment['status'] == 'approved'): ?>
                                        <a href="<?= base_url('receptionist/appointment/cancel/' . $appointment['id']) ?>" 
                                           class="action-icon-btn cancel" title="Cancel"
                                           onclick="return confirm('Cancel this appointment?')">
                                            <i class="bi bi-x"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="bi bi-calendar-x"></i>
                                    <p>No appointments scheduled for today</p>
                                    <small>Check back later or schedule a new appointment</small>
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
/* ============================================
   RECEPTIONIST DASHBOARD - MODERN DESIGN
   ============================================ */
.dashboard-container {
    --ink: #101828;
    --ink-soft: #64748B;
    --ink-faint: #94A3B8;
    --line: #E5E9ED;
    --surface: #FFFFFF;
    --surface-alt: #F8FAFB;
    --teal: #0d9488;
    --teal-soft: #ccfbf1;
    --blue: #1D4ED8;
    --blue-soft: #E8EFFE;
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

/* ===== WELCOME BANNER ===== */
.welcome-banner {
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #0d9488 100%);
    border-radius: 16px;
    padding: 2rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
    box-shadow: 0 4px 20px rgba(13, 148, 136, 0.25);
}

.welcome-banner h1 {
    color: #fff;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.3rem;
}

.welcome-banner p {
    color: rgba(255,255,255,0.85);
    margin: 0;
    font-size: 0.9rem;
}

.welcome-date {
    background: rgba(255,255,255,0.15);
    color: #ffffff;
    padding: 0.5rem 1.25rem;
    border-radius: 30px;
    font-size: 0.9rem;
    font-weight: 600;
    border: 1px solid rgba(255,255,255,0.2);
    white-space: nowrap;
}

.welcome-date i {
    margin-right: 0.5rem;
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

.stat-icon.teal { background: var(--teal-soft); color: var(--teal); }
.stat-icon.orange { background: var(--orange-soft); color: var(--orange); }
.stat-icon.green { background: var(--green-soft); color: var(--green); }
.stat-icon.purple { background: var(--purple-soft); color: var(--purple); }
.stat-icon.red { background: var(--red-soft); color: var(--red); }
.stat-icon.blue { background: var(--blue-soft); color: var(--blue); }

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

.chart-header small {
    color: var(--ink-soft);
    font-size: 0.8rem;
    font-weight: 400;
}

.chart-body {
    position: relative;
    height: 250px;
}

/* ===== QUICK ACTIONS ===== */
.quick-actions {
    background: var(--surface);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid var(--line);
    margin-bottom: 1.5rem;
}

.quick-actions-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
}

.quick-actions h5 {
    font-weight: 700;
    color: var(--ink);
    margin: 0;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quick-actions h5 i {
    color: var(--teal);
    font-size: 1.1rem;
}

.badge-year {
    background: var(--teal-soft);
    color: var(--teal);
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.3rem 0.85rem;
    border-radius: 30px;
    white-space: nowrap;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem 1rem;
    border-radius: 12px;
    background: var(--surface-alt);
    border: 1px solid var(--line);
    color: var(--ink);
    text-decoration: none;
    transition: all 0.25s ease;
    gap: 0.5rem;
}

.action-btn:hover {
    border-color: var(--teal);
    background: var(--teal-soft);
    color: var(--teal);
    text-decoration: none;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.1);
}

.action-icon-wrap {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: var(--surface);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(16, 24, 40, 0.08);
    margin-bottom: 0.5rem;
}

.action-btn:hover .action-icon-wrap {
    background: var(--teal);
}

.action-icon-wrap i {
    font-size: 1.5rem;
    color: var(--teal);
    transition: color 0.2s ease;
}

.action-btn:hover .action-icon-wrap i {
    color: #ffffff;
}

.action-btn span {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--ink);
}

.action-btn small {
    font-size: 0.7rem;
    color: var(--ink-soft);
    font-weight: 400;
}

.action-btn:hover span {
    color: var(--teal);
}

.action-btn:hover small {
    color: var(--teal);
}

/* ===== APPOINTMENTS CARD ===== */
.appointments-card {
    background: var(--surface);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid var(--line);
}

.card-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.card-header-custom h5 {
    font-weight: 700;
    color: var(--ink);
    margin: 0;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-header-custom h5 i {
    color: var(--teal);
}

.header-right-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.badge-custom {
    background: var(--teal-soft);
    color: var(--teal);
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 30px;
    white-space: nowrap;
}

.btn-view-all {
    font-size: 0.75rem;
    color: var(--teal);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.btn-view-all:hover {
    color: #0f766e;
    text-decoration: underline;
}

/* ===== TABLE ===== */
.receptionist-table {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0 8px;
}

.receptionist-table thead th {
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

.receptionist-table tbody tr {
    background: var(--surface);
    border-radius: 12px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    border: 1px solid var(--line);
}

.receptionist-table tbody tr:hover {
    background: var(--surface-alt);
    box-shadow: 0 4px 12px rgba(16, 24, 40, 0.08);
    transform: translateY(-1px);
}

.receptionist-table tbody td {
    padding: 0.85rem 1rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: var(--ink);
    border: none;
    white-space: nowrap;
    background: transparent;
}

.receptionist-table tbody td:first-child {
    border-radius: 12px 0 0 12px;
}

.receptionist-table tbody td:last-child {
    border-radius: 0 12px 12px 0;
}

/* Accession Cell */
.accession-cell {
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--teal);
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
    background: var(--teal-soft);
    color: var(--teal);
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
    margin-top: 1px;
}

/* Service Tag */
.service-tag {
    background: var(--blue-soft);
    color: var(--blue);
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
}

/* Status Badges */
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

.status-badge.approved {
    background: var(--blue-soft);
    color: var(--blue);
}

.status-badge.completed {
    background: var(--green-soft);
    color: var(--green);
}

.status-badge.cancelled {
    background: var(--red-soft);
    color: var(--red);
}

.status-badge.late {
    background: #F1F3F5;
    color: var(--ink-soft);
}

/* ===== ACTION BUTTONS - BLUE THEME ===== */
.action-cell {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.action-icon-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
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

.action-icon-btn.approve {
    background: var(--green-soft);
    color: var(--green);
    border-color: #bbf7d0;
}

.action-icon-btn.approve:hover {
    background: var(--green);
    color: #ffffff;
    border-color: var(--green);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2);
}

.action-icon-btn.complete {
    background: var(--teal-soft);
    color: var(--teal);
    border-color: #99f6e4;
}

.action-icon-btn.complete:hover {
    background: var(--teal);
    color: #ffffff;
    border-color: var(--teal);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2);
}

.action-icon-btn.cancel {
    background: var(--red-soft);
    color: var(--red);
    border-color: #fecaca;
}

.action-icon-btn.cancel:hover {
    background: var(--red);
    color: #ffffff;
    border-color: var(--red);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
}

/* ===== EMPTY STATE ===== */
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
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .action-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .charts-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .welcome-banner {
        flex-direction: column;
        align-items: flex-start;
        padding: 1.25rem;
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
    
    .action-grid {
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    
    .action-btn {
        padding: 1rem;
    }
    
    .action-icon-wrap {
        width: 48px;
        height: 48px;
    }
    
    .action-icon-wrap i {
        font-size: 1.3rem;
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
    
    .receptionist-table {
        border-spacing: 0 6px;
    }
}

@media (max-width: 576px) {
    .stats-row {
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }
    
    .stat-card {
        padding: 0.75rem;
    }
    
    .stat-info h3 {
        font-size: 1.1rem;
    }
    
    .stat-info p {
        font-size: 0.7rem;
    }
    
    .action-grid {
        grid-template-columns: 1fr;
    }
    
    .action-btn {
        flex-direction: row;
        justify-content: flex-start;
        padding: 1rem;
    }
    
    .action-icon-wrap {
        width: 40px;
        height: 40px;
        margin-bottom: 0;
    }
    
    .action-btn span {
        font-size: 0.8rem;
    }
    
    .action-btn small {
        display: none;
    }
    
    .receptionist-table {
        font-size: 0.75rem;
    }
    
    .receptionist-table thead th,
    .receptionist-table tbody td {
        padding: 0.5rem;
        font-size: 0.75rem;
    }
    
    .patient-avatar {
        width: 28px;
        height: 28px;
        font-size: 0.55rem;
    }
    
    .action-icon-btn {
        width: 32px;
        height: 32px;
        font-size: 0.85rem;
        border-radius: 8px;
    }
    
    .chart-body {
        height: 220px;
    }
}

@media (max-width: 400px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .welcome-banner h1 {
        font-size: 1.1rem;
    }
    
    .welcome-banner p {
        font-size: 0.75rem;
    }
    
    .welcome-date {
        font-size: 0.7rem;
    }
}
</style>

<!-- Include Chart.js (already in layout but ensure it's here if not) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== APPOINTMENT TRENDS CHART (Line Chart) =====
    const apptCtx = document.getElementById('appointmentsChart').getContext('2d');
    
    // Sample data - replace with actual data from controller if available
    const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const apptData = [12, 18, 15, 22, 20, 8, 5]; // Example appointments per day
    
    new Chart(apptCtx, {
        type: 'line',
        data: {
            labels: days,
            datasets: [{
                label: 'Appointments',
                data: apptData,
                borderColor: '#0d9488',
                backgroundColor: 'rgba(13, 148, 136, 0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointBackgroundColor: '#0d9488',
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
                    ticks: { color: '#94A3B8' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94A3B8' }
                }
            }
        }
    });

    // ===== SERVICE DISTRIBUTION CHART (Doughnut Chart) =====
    const svcCtx = document.getElementById('servicesChart').getContext('2d');
    
    // Sample data - replace with actual data from controller
    const services = ['Consultation', 'Laboratory', 'Radiology', 'Ultrasound'];
    const svcCounts = [35, 28, 20, 17];
    const svcColors = ['#0d9488', '#1D4ED8', '#f59e0b', '#7c3aed'];
    
    new Chart(svcCtx, {
        type: 'doughnut',
        data: {
            labels: services,
            datasets: [{
                data: svcCounts,
                backgroundColor: svcColors,
                borderWidth: 2,
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
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: { size: 11 }
                    }
                }
            }
        }
    });
});
</script>

<?= $this->endSection() ?>