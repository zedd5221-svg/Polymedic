<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Patient Management<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<div class="table-card">

    <!-- Header / Page Title -->
    <div class="page-header">
        <div>
            <h4 class="page-title">Patient Management</h4>
            <p class="page-subtitle">View all registered patients</p>
        </div>
        <button class="btn-primary" onclick="window.location.href='<?= base_url('receptionist/diagnostic-requests') ?>'">
            <i class="bi bi-person-plus me-2"></i>Register Walk-in Patient
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon teal">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-info">
                <h3><?= $total ?? 0 ?></h3>
                <p>Total Patients</p>
                <small>All registered patients</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-globe"></i>
            </div>
            <div class="stat-info">
                <h3>
                    <?php 
                    $onlineCount = 0;
                    if (!empty($patients)) {
                        foreach ($patients as $p) {
                            if (stripos($p['source'] ?? '', 'online') !== false) $onlineCount++;
                        }
                    }
                    echo $onlineCount;
                    ?>
                </h3>
                <p>Online Patients</p>
                <small>Booked online</small>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-person-walking"></i>
            </div>
            <div class="stat-info">
                <h3>
                    <?php 
                    $walkinCount = 0;
                    if (!empty($patients)) {
                        foreach ($patients as $p) {
                            if (stripos($p['source'] ?? '', 'walk-in') !== false) $walkinCount++;
                        }
                    }
                    echo $walkinCount;
                    ?>
                </h3>
                <p>Walk-in Patients</p>
                <small>Walk-in registration</small>
            </div>
        </div>
    </div>

    <!-- Table Toolbar -->
    <div class="table-toolbar">
        <div class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" placeholder="Search patients..." id="searchPatients">
        </div>
        <div class="filter-buttons">
            <button class="filter-btn active" data-source="all">All</button>
            <button class="filter-btn" data-source="online">Online</button>
            <button class="filter-btn" data-source="walk-in">Walk-in</button>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table patients-table" id="patientsTable">
            <thead>
                <tr>
                    <th>Patient Code</th>
                    <th>Patient Name</th>
                    <th>Gender</th>
                    <th>Age</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Source</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($patients) && count($patients) > 0): ?>
                    <?php foreach ($patients as $patient): ?>
                        <tr data-source="<?= strpos(strtolower($patient['source'] ?? ''), 'walk-in') !== false ? 'walk-in' : 'online' ?>">
                            <td>
                                <span class="patient-id"><?= esc($patient['patient_code'] ?? 'N/A') ?></span>
                            </td>
                            <td>
                                <div class="patient-cell">
                                    <span class="patient-avatar">
                                        <?php 
                                            $nameParts = preg_split('/\s+/', trim($patient['full_name'] ?? 'Unknown'));
                                            $initials = strtoupper(mb_substr($nameParts[0] ?? '', 0, 1) . mb_substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));
                                            echo esc($initials);
                                        ?>
                                    </span>
                                    <div class="patient-meta">
                                        <span class="patient-name"><?= esc($patient['full_name'] ?? 'Unknown') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= esc($patient['gender'] ?? 'N/A') ?></td>
                            <td><?= esc($patient['age'] ?? 'N/A') ?></td>
                            <td><?= esc($patient['email'] ?? '—') ?></td>
                            <td><?= esc($patient['phone'] ?? '—') ?></td>
                            <td>
                                <span class="source-badge <?= strpos(strtolower($patient['source'] ?? ''), 'walk-in') !== false ? 'walkin' : 'online' ?>">
                                    <i class="bi <?= strpos(strtolower($patient['source'] ?? ''), 'walk-in') !== false ? 'bi-person-walking' : 'bi-globe' ?>"></i>
                                    <?= esc($patient['source'] ?? 'Unknown') ?>
                                </span>
                            </td>
                            <td class="action-cell">
                                <button class="action-icon-btn view" title="View Patient">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <p>No patients registered yet.</p>
                                <small>Patients will appear here once they are registered.</small>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Footer -->
    <div class="table-footer">
        <span>Showing <?= count($patients ?? []) ?> of <?= $total ?? 0 ?> patients</span>
    </div>
</div>

<style>
/* ============================================
   PATIENTS - CONSISTENT UI
   ============================================ */

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
    color: #101828;
    margin: 0;
    font-size: 1.4rem;
    letter-spacing: -0.02em;
}

.page-subtitle {
    color: #64748B;
    font-size: 0.85rem;
    margin: 0.15rem 0 0;
    font-weight: 400;
}

.btn-primary {
    background: #0d9488;
    color: white;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary:hover {
    background: #0f766e;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2);
}

/* Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid #E5E9ED;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    min-height: 120px;
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
}

.stat-icon.teal { background: #E0F2F4; color: #0d9488; }
.stat-icon.blue { background: #E8EFFE; color: #1D4ED8; }
.stat-icon.orange { background: #FFF1E6; color: #C2410C; }

.stat-info h3 {
    font-size: 1.5rem;
    font-weight: 800;
    color: #101828;
    margin: 0;
    line-height: 1.1;
}

.stat-info p {
    font-size: 0.85rem;
    font-weight: 600;
    color: #101828;
    margin: 0.2rem 0 0;
}

.stat-info small {
    font-size: 0.7rem;
    color: #64748B;
    font-weight: 400;
}

/* Table Card */
.table-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid #E5E9ED;
}

.table-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.search-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid #E5E9ED;
    border-radius: 10px;
    padding: 0.45rem 0.85rem;
    max-width: 300px;
    flex: 1;
    transition: all 0.2s ease;
    background: #F8FAFB;
}

.search-wrapper:focus-within {
    border-color: #0d9488;
    box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.08);
}

.search-wrapper i {
    color: #94A3B8;
}

.search-wrapper .form-control {
    border: none;
    padding: 0;
    font-size: 0.85rem;
    background: transparent;
    color: #101828;
}

.search-wrapper .form-control::placeholder {
    color: #94A3B8;
}

.search-wrapper .form-control:focus {
    box-shadow: none;
}

.filter-buttons {
    display: flex;
    gap: 0.25rem;
}

.filter-btn {
    padding: 0.3rem 0.9rem;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    background: transparent;
    color: #64748b;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-btn:hover {
    border-color: #0148ca;
    color: #0148ca;
}

.filter-btn.active {
    background: #0148ca;
    border-color: #0148ca;
    color: white;
}

/* Table */
.patients-table {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0 8px;
}

.patients-table thead th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #94A3B8;
    font-weight: 600;
    border: none;
    padding: 0.75rem 1rem;
    background: transparent;
    white-space: nowrap;
}

.patients-table tbody tr {
    background: #ffffff;
    border-radius: 12px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    border: 1px solid #E5E9ED;
}

.patients-table tbody tr:hover {
    background: #F8FAFB;
    box-shadow: 0 4px 12px rgba(16, 24, 40, 0.08);
    transform: translateY(-1px);
}

.patients-table tbody td {
    padding: 0.85rem 1rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: #101828;
    border: none;
    white-space: nowrap;
    background: transparent;
}

.patients-table tbody td:first-child {
    border-radius: 12px 0 0 12px;
}

.patients-table tbody td:last-child {
    border-radius: 0 12px 12px 0;
}

/* Patient ID */
.patient-id {
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: 0.7rem;
    font-weight: 600;
    color: #1D4ED8;
    background: #E8EFFE;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
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
    background: #E0F2F4;
    color: #0d9488;
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
    color: #101828;
}

/* Source Badge */
.source-badge {
    padding: 0.15rem 0.6rem;
    border-radius: 30px;
    font-size: 0.6rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.source-badge.online {
    background: #E7F6EC;
    color: #15803D;
}

.source-badge.walkin {
    background: #FFF1E6;
    color: #C2410C;
}

/* Action Buttons */
.action-cell {
    display: flex;
    gap: 0.4rem;
    align-items: center;
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
    background: #E8EFFE;
    color: #1D4ED8;
    border-color: #bfdbfe;
}

.action-icon-btn.view:hover {
    background: #1D4ED8;
    color: #ffffff;
    border-color: #1D4ED8;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(29, 78, 216, 0.2);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 2.5rem 1rem;
}

.empty-state i {
    font-size: 2.5rem;
    color: #94A3B8;
    display: block;
    margin-bottom: 0.75rem;
}

.empty-state p {
    color: #101828;
    font-weight: 600;
    margin: 0;
}

.empty-state small {
    color: #64748B;
}

/* Footer */
.table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid #E5E9ED;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.table-footer span {
    font-size: 0.85rem;
    color: #64748B;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .stats-row {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .table-toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .search-wrapper {
        max-width: 100%;
    }
    
    .filter-buttons {
        justify-content: center;
    }

    .table-footer {
        flex-direction: column;
        align-items: center;
    }

    .patients-table {
        font-size: 0.75rem;
    }

    .patients-table thead th,
    .patients-table tbody td {
        padding: 0.5rem;
        font-size: 0.75rem;
    }
}
</style>

<script>
// Search functionality
document.getElementById('searchPatients').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#patientsTable tbody tr');
    
    rows.forEach(row => {
        if (row.querySelector('td')) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        }
    });
});

// Source filter
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const source = this.dataset.source;
        const rows = document.querySelectorAll('#patientsTable tbody tr');
        
        rows.forEach(row => {
            if (source === 'all' || row.dataset.source === source) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>

<?= $this->endSection() ?>