<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Patient Management<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<div class="patients-container">

    <!-- ===== PAGE HEADER ===== -->
    <div class="page-header">
        <div>
            <h4 class="page-title">Patient Management</h4>
            <p class="page-subtitle">Manage all registered patients (<?= $total ?? 0 ?> total)</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-export" onclick="exportPatients()">
                <i class="bi bi-download"></i>
                <span>Export</span>
            </button>
            <button class="btn btn-primary" onclick="window.location.href='<?= base_url('admin/patient/add') ?>'">
                <i class="bi bi-person-plus"></i>
                <span>Add Patient</span>
            </button>
        </div>
    </div>

    <!-- ===== TABLE TOOLBAR ===== -->
    <div class="table-toolbar">
        <div class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" placeholder="Search by name, code, email, or phone..." id="searchPatients">
        </div>
        
        <div class="select-wrapper filter-wrapper">
            <select class="form-select filter-select" id="filterSource">
                <option value="">All Sources</option>
                <option value="online">Online</option>
                <option value="walk-in">Walk-in</option>
                <option value="referral">Referral</option>
            </select>
        </div>
        
        <div class="select-wrapper filter-wrapper">
            <select class="form-select filter-select" id="filterStatus">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        
        <div class="select-wrapper sort-wrapper">
            <select class="form-select sort-select" id="sortBy">
                <option value="created_at">Sort by: Newest</option>
                <option value="full_name">Sort by: Patient Name</option>
                <option value="age">Sort by: Age</option>
                <option value="created_at_oldest">Sort by: Oldest</option>
            </select>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ===== TABLE CARD ===== -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table patients-table" id="patientsTable">
                <thead>
                    <tr>
                        <th>Patient Code</th>
                        <th>Patient Name</th>
                        <th>Source</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Registered</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="patientsTableBody">
                    <?php if (!empty($patients) && is_array($patients)): ?>
                        <?php foreach ($patients as $patient): ?>
                            <?php 
                            // Determine if patient is active (visited within last 30 days)
                            $lastVisit = $patient['last_visit'] ?? $patient['created_at'] ?? null;
                            $isActive = $lastVisit && strtotime($lastVisit) > strtotime('-30 days');
                            
                            // Clean source display
                            $source = $patient['source'] ?? 'walk-in';
                            $sourceDisplay = ucfirst(strtolower($source));
                            
                            // Get patient code or fallback
                            $patientCode = $patient['patient_code'] ?? 'N/A';
                            
                            // Check if patient is approved (has patient_code)
                            $isApproved = $patientCode !== 'N/A' && !empty($patientCode);
                            
                            // Get patient ID
                            $patientId = $patient['id'] ?? 0;
                            ?>
                            <tr data-patient-row
                                data-status="<?= $isActive ? 'active' : 'inactive' ?>"
                                data-source="<?= esc(strtolower($source), 'attr') ?>"
                                data-name="<?= esc(strtolower($patient['full_name'] ?? ''), 'attr') ?>"
                                data-age="<?= esc((string) (int) ($patient['age'] ?? 0), 'attr') ?>"
                                data-created="<?= isset($patient['created_at']) ? esc(date('Y-m-d H:i:s', strtotime($patient['created_at'])), 'attr') : '' ?>">
                                <td>
                                    <span class="patient-code <?= $isApproved ? 'approved' : 'pending' ?>">
                                        <?= esc($patientCode) ?>
                                        <?php if (!$isApproved): ?>
                                            <span class="pending-badge">Pending</span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="patient-cell">
                                        <span class="patient-avatar">
                                            <?php 
                                                $nameParts = preg_split('/\s+/', trim($patient['full_name'] ?? 'Unknown'));
                                                $first = $nameParts[0] ?? 'U';
                                                $last = $nameParts[count($nameParts) - 1] ?? 'N';
                                                $initials = strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
                                                echo esc($initials);
                                            ?>
                                        </span>
                                        <div class="patient-meta">
                                            <span class="patient-name"><?= esc($patient['full_name'] ?? 'Unknown') ?></span>
                                            <?php if ($isApproved): ?>
                                                <small class="patient-code-small"><?= esc($patientCode) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="source-badge <?= strtolower($source) ?>">
                                        <i class="bi <?= strtolower($source) === 'online' ? 'bi-globe' : (strtolower($source) === 'walk-in' ? 'bi-person-walking' : 'bi-person-arms-up') ?>"></i>
                                        <?= $sourceDisplay ?>
                                    </span>
                                </td>
                                <td><?= esc($patient['gender'] ?? 'N/A') ?></td>
                                <td><?= esc($patient['age'] ?? 'N/A') ?></td>
                                <td><?= esc($patient['phone'] ?? 'N/A') ?></td>
                                <td><?= esc($patient['email'] ?? 'N/A') ?></td>
                                <td>
                                    <div class="date-cell">
                                        <i class="bi bi-calendar3"></i>
                                        <span><?= isset($patient['created_at']) ? date('M d, Y', strtotime($patient['created_at'])) : '—' ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?= $isActive ? 'active' : 'inactive' ?>">
                                        <i class="bi bi-circle-fill"></i>
                                        <?= $isActive ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="action-cell">
                                    <!-- Approve Button - Only show if not approved -->
                                    <?php if (!$isApproved && $patientId > 0): ?>
                                        <button class="action-icon-btn approve" onclick="approvePatient(<?= $patientId ?>)" title="Approve Patient">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="action-icon-btn view" onclick="viewPatient(<?= $patientId ?>)" title="View">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    
                                    <button class="action-icon-btn edit" onclick="editPatient(<?= $patientId ?>)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    
                                    <?php if (!empty($patient['email'])): ?>
                                        <a href="mailto:<?= esc($patient['email']) ?>" class="action-icon-btn email" title="Email">
                                            <i class="bi bi-envelope"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($patient['phone'])): ?>
                                        <a href="tel:<?= esc($patient['phone']) ?>" class="action-icon-btn phone" title="Call">
                                            <i class="bi bi-telephone"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr data-empty-row>
                            <td colspan="10">
                                <div class="empty-state">
                                    <i class="bi bi-people"></i>
                                    <p>No patients found</p>
                                    <small>Patients will appear here once registered</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <!-- Shown only when search/filters match nothing -->
                    <tr data-noresults-row style="display: none;">
                        <td colspan="10">
                            <div class="empty-state">
                                <i class="bi bi-search"></i>
                                <p>No matching patients</p>
                                <small>Try a different search term or clear the filters</small>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- ===== TABLE FOOTER / PAGINATION ===== -->
        <div class="table-footer">
            <span id="showingText">Showing <?= count($patients ?? []) ?> of <?= $total ?? count($patients ?? []) ?> patients</span>
            <div class="pagination" id="paginationControls">
                <button class="page-btn" id="prevBtn" disabled><i class="bi bi-chevron-left"></i></button>
                <button class="page-btn active">1</button>
                <button class="page-btn" id="nextBtn"><i class="bi bi-chevron-right"></i></button>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   ADMIN PATIENTS - ENHANCED UI
   ============================================ */

.patients-container {
    --ink: #101828;
    --ink-soft: #64748B;
    --ink-faint: #94A3B8;
    --line: #E5E9ED;
    --surface: #FFFFFF;
    --surface-alt: #F8FAFB;
    --blue: #1D4ED8;
    --blue-soft: #E8EFFE;
    --green: #15803D;
    --green-soft: #E7F6EC;
    --red: #dc2626;
    --red-soft: #FEF2F2;
    --orange: #C2410C;
    --orange-soft: #FFF1E6;
    --purple: #7c3aed;
    --purple-soft: #ede9fe;
    --gold: #f59e0b;
    --gold-soft: #fef3c7;
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
    font-size: 1.4rem;
    letter-spacing: -0.02em;
}

.page-subtitle {
    color: var(--ink-soft);
    font-size: 0.85rem;
    margin: 0.15rem 0 0;
    font-weight: 400;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.btn-export {
    background: var(--surface);
    border: 1px solid var(--line);
    color: var(--ink-soft);
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-export:hover {
    background: var(--surface-alt);
    border-color: var(--blue);
    color: var(--blue);
}

.btn-primary {
    background: var(--blue);
    color: #ffffff;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary:hover {
    background: #1e40af;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(29, 78, 216, 0.2);
    color: #ffffff;
}

/* ===== TABLE TOOLBAR ===== */
.table-toolbar {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    align-items: center;
}

.search-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 0.5rem 0.85rem;
    flex: 1;
    min-width: 250px;
    transition: all 0.2s ease;
    height: 42px;
}

.search-wrapper:focus-within {
    border-color: var(--blue);
    box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.08);
}

.search-wrapper i {
    color: var(--ink-faint);
}

.search-wrapper .form-control {
    border: none;
    padding: 0;
    font-size: 0.85rem;
    background: transparent;
    color: var(--ink);
}

.search-wrapper .form-control::placeholder {
    color: var(--ink-faint);
}

.search-wrapper .form-control:focus {
    box-shadow: none;
}

/* ===== SELECT WRAPPER ===== */
.select-wrapper {
    position: relative;
    min-width: 160px;
}

.select-wrapper.filter-wrapper::before {
    content: '\F64E';
    font-family: "bootstrap-icons";
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ink-faint);
    font-size: 0.9rem;
    pointer-events: none;
    z-index: 2;
}

.select-wrapper.sort-wrapper::before {
    content: '\F174';
    font-family: "bootstrap-icons";
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ink-faint);
    font-size: 0.9rem;
    pointer-events: none;
    z-index: 2;
}

.form-select {
    border: 1px solid var(--line);
    border-radius: 8px;
    font-size: 0.85rem;
    padding: 0.45rem 2.5rem 0.45rem 2.3rem;
    color: var(--ink);
    background-color: var(--surface);
    height: 42px;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round'%3e%3cpath d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
}

.form-select:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.08);
}

/* ===== TABLE CARD ===== */
.table-card {
    background: var(--surface);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid var(--line);
}

/* ===== TABLE ===== */
.patients-table {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0 8px;
    width: 100%;
}

.patients-table thead th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--ink-faint);
    font-weight: 600;
    border: none;
    padding: 0.75rem 1rem;
    background: transparent;
    white-space: nowrap;
    text-align: left;
}

.patients-table tbody tr {
    background: var(--surface);
    border-radius: 12px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    border: 1px solid var(--line);
}

.patients-table tbody tr:hover {
    background: var(--surface-alt);
    box-shadow: 0 4px 12px rgba(16, 24, 40, 0.06);
}

.patients-table tbody td {
    padding: 0.85rem 1rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: var(--ink);
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

/* Patient Code */
.patient-code {
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.patient-code.approved {
    color: var(--blue);
    background: var(--blue-soft);
}

.patient-code.pending {
    color: var(--gold);
    background: var(--gold-soft);
}

.pending-badge {
    font-size: 0.6rem;
    font-weight: 700;
    color: var(--gold);
    background: var(--gold-soft);
    padding: 0.05rem 0.4rem;
    border-radius: 12px;
    text-transform: uppercase;
}

.patient-code-small {
    font-size: 0.65rem;
    color: var(--ink-faint);
    font-family: 'SFMono-Regular', Consolas, monospace;
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
    border-radius: 50%;
    background: var(--blue-soft);
    color: var(--blue);
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

/* Date Cell */
.date-cell {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: var(--ink);
}

.date-cell i {
    color: var(--ink-faint);
    font-size: 0.9rem;
}

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

.status-badge.active {
    background: var(--green-soft);
    color: var(--green);
}

.status-badge.inactive {
    background: var(--red-soft);
    color: var(--red);
}

/* Source Badge */
.source-badge {
    padding: 0.3rem 0.8rem;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    white-space: nowrap;
}

.source-badge i {
    font-size: 0.65rem;
}

.source-badge.online {
    background: var(--blue-soft);
    color: var(--blue);
}

.source-badge.walk-in {
    background: var(--orange-soft);
    color: var(--orange);
}

.source-badge.referral {
    background: var(--purple-soft);
    color: var(--purple);
}

/* ===== ACTION BUTTONS ===== */
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
    background: transparent;
    color: var(--ink-faint);
    cursor: pointer;
}

.action-icon-btn:hover {
    background: var(--blue-soft);
    color: var(--blue);
    transform: translateY(-1px);
}

.action-icon-btn.approve {
    color: var(--gold);
    border-color: var(--gold-soft);
}

.action-icon-btn.approve:hover {
    background: var(--gold-soft);
    color: var(--gold);
    border-color: var(--gold);
}

.action-icon-btn.view:hover {
    background: var(--blue-soft);
    color: var(--blue);
}

.action-icon-btn.edit:hover {
    background: var(--orange-soft);
    color: var(--orange);
}

.action-icon-btn.email:hover {
    background: var(--green-soft);
    color: var(--green);
}

.action-icon-btn.phone:hover {
    background: var(--purple-soft);
    color: var(--purple);
}

/* ===== ALERTS ===== */
.alert {
    border-radius: 12px;
    padding: 0.75rem 1rem;
    border: none;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
}

.alert-success {
    background: var(--green-soft);
    color: var(--green);
}

.alert-danger {
    background: var(--red-soft);
    color: var(--red);
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

/* ===== TABLE FOOTER ===== */
.table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--line);
    flex-wrap: wrap;
    gap: 0.75rem;
}

.table-footer span {
    font-size: 0.85rem;
    color: var(--ink-soft);
}

.pagination {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.page-btn {
    width: 32px;
    height: 32px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: var(--surface);
    color: var(--ink-soft);
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.page-btn:hover:not(.active):not(:disabled) {
    background: var(--surface-alt);
    border-color: var(--blue);
    color: var(--blue);
}

.page-btn.active {
    background: var(--blue);
    border-color: var(--blue);
    color: #ffffff;
}

.page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.page-btn.page-gap {
    border-color: transparent;
    background: transparent;
    cursor: default;
    opacity: 1;
    color: var(--ink-faint);
    width: 20px;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 992px) {
    .table-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-wrapper,
    .select-wrapper {
        min-width: 100%;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .header-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .header-actions .btn {
        justify-content: center;
    }
    
    .table-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-wrapper {
        max-width: 100%;
    }
    
    .table-footer {
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
    }
}

@media (max-width: 576px) {
    .patients-table {
        font-size: 0.72rem;
    }
    
    .patients-table thead th,
    .patients-table tbody td {
        padding: 0.5rem;
        font-size: 0.72rem;
    }
    
    .patient-avatar {
        width: 28px;
        height: 28px;
        font-size: 0.55rem;
    }
    
    .page-btn {
        width: 28px;
        height: 28px;
        font-size: 0.75rem;
    }
    
    .action-icon-btn {
        width: 28px;
        height: 28px;
        font-size: 0.8rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ============================================
    // TABLE STATE
    // --------------------------------------------
    // Single source of truth. `filteredRows` is derived from search +
    // source + status together, and `render()` is the ONLY thing that
    // touches row.style.display. Nothing ever reads display state back,
    // which is what previously made hidden rows unrecoverable.
    // ============================================

    var tbody              = document.getElementById('patientsTableBody');
    var showingText        = document.getElementById('showingText');
    var paginationControls = document.getElementById('paginationControls');
    var searchInput        = document.getElementById('searchPatients');
    var filterSource       = document.getElementById('filterSource');
    var filterStatus       = document.getElementById('filterStatus');
    var sortSelect         = document.getElementById('sortBy');

    if (!tbody) { return; }

    // Rows shown per page. Change this one value to adjust page size.
    var PAGE_SIZE = 5;

    var allRows      = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-patient-row]'));
    var emptyRow     = tbody.querySelector('tr[data-empty-row]');
    var noResultsRow = tbody.querySelector('tr[data-noresults-row]');

    var loadedTotal  = allRows.length;
    var serverTotal  = <?= (int) ($total ?? 0) ?>;

    var filteredRows = allRows.slice();
    var currentPage  = 1;

    function getTotalPages() {
        return Math.max(1, Math.ceil(filteredRows.length / PAGE_SIZE));
    }

    // ============================================
    // FILTERING - search, source and status combined
    // ============================================
    function applyFilters(resetPage) {
        var term   = searchInput  ? searchInput.value.toLowerCase().trim() : '';
        var source = filterSource ? filterSource.value.toLowerCase() : '';
        var status = filterStatus ? filterStatus.value.toLowerCase() : '';

        filteredRows = allRows.filter(function (row) {
            if (term && row.textContent.toLowerCase().indexOf(term) === -1) {
                return false;
            }
            if (source && (row.dataset.source || '').toLowerCase() !== source) {
                return false;
            }
            if (status && (row.dataset.status || '').toLowerCase() !== status) {
                return false;
            }
            return true;
        });

        if (resetPage !== false) { currentPage = 1; }
        render();
    }

    // ============================================
    // SORTING - reorders the DOM and the row array
    // together, then re-paginates from page 1
    // ============================================
    function applySort() {
        var mode = sortSelect ? sortSelect.value : 'created_at';

        var sorted = allRows.slice();
        sorted.sort(function (a, b) {
            switch (mode) {
                case 'full_name':
                    return (a.dataset.name || '').localeCompare(b.dataset.name || '');
                case 'age':
                    return (parseInt(a.dataset.age, 10) || 0) - (parseInt(b.dataset.age, 10) || 0);
                case 'created_at_oldest':
                    // ISO timestamps, so a plain string compare is chronological
                    return (a.dataset.created || '').localeCompare(b.dataset.created || '');
                default: // created_at (newest first)
                    return (b.dataset.created || '').localeCompare(a.dataset.created || '');
            }
        });

        sorted.forEach(function (row) { tbody.appendChild(row); });
        if (emptyRow) { tbody.appendChild(emptyRow); }
        if (noResultsRow) { tbody.appendChild(noResultsRow); }

        allRows = sorted;
        applyFilters(true);
    }

    // ============================================
    // RENDER - hide every row, then reveal this page
    // ============================================
    function render() {
        var pages = getTotalPages();
        if (currentPage > pages) { currentPage = pages; }
        if (currentPage < 1) { currentPage = 1; }

        var start = (currentPage - 1) * PAGE_SIZE;

        allRows.forEach(function (row) { row.style.display = 'none'; });

        var pageRows = filteredRows.slice(start, start + PAGE_SIZE);
        pageRows.forEach(function (row) { row.style.display = ''; });

        if (emptyRow) {
            emptyRow.style.display = (loadedTotal === 0) ? '' : 'none';
        }
        if (noResultsRow) {
            noResultsRow.style.display = (loadedTotal > 0 && filteredRows.length === 0) ? '' : 'none';
        }

        updateShowingText(start, pageRows.length);
        renderPagination(pages);
    }

    // ============================================
    // FOOTER TEXT
    // ============================================
    function updateShowingText(start, shown) {
        if (!showingText) { return; }

        var count = filteredRows.length;

        if (count === 0) {
            showingText.textContent = (loadedTotal === 0)
                ? 'No patients to show'
                : 'No matching patients';
            return;
        }

        var text = 'Showing ' + (start + 1) + ' to ' + (start + shown) +
                   ' of ' + count + ' patient' + (count === 1 ? '' : 's');

        if (count !== loadedTotal) {
            text += ' (filtered from ' + loadedTotal + ')';
        } else if (serverTotal > loadedTotal) {
            text += ' (' + serverTotal + ' total registered)';
        }

        showingText.textContent = text;
    }

    // ============================================
    // PAGINATION CONTROLS - rebuilt on every render
    // ============================================
    function buildPageList(pages, current) {
        var list = [];
        var i;

        if (pages <= 7) {
            for (i = 1; i <= pages; i++) { list.push(i); }
            return list;
        }

        var first = 1;
        var last  = pages;
        var from  = Math.max(2, current - 1);
        var to    = Math.min(pages - 1, current + 1);

        if (current <= 3)         { from = 2;         to = 4; }
        if (current >= pages - 2) { from = pages - 3; to = pages - 1; }

        list.push(first);
        if (from > 2) { list.push('gap'); }
        for (i = from; i <= to; i++) { list.push(i); }
        if (to < pages - 1) { list.push('gap'); }
        list.push(last);

        return list;
    }

    function renderPagination(pages) {
        if (!paginationControls) { return; }

        var html = '<button type="button" class="page-btn" id="prevBtn" aria-label="Previous page"' +
                   (currentPage === 1 ? ' disabled' : '') +
                   '><i class="bi bi-chevron-left"></i></button>';

        buildPageList(pages, currentPage).forEach(function (item) {
            if (item === 'gap') {
                html += '<button type="button" class="page-btn page-gap" disabled aria-hidden="true">...</button>';
                return;
            }
            html += '<button type="button" class="page-btn' + (item === currentPage ? ' active' : '') +
                    '" data-page="' + item + '" aria-label="Page ' + item + '"' +
                    (item === currentPage ? ' aria-current="page"' : '') +
                    '>' + item + '</button>';
        });

        html += '<button type="button" class="page-btn" id="nextBtn" aria-label="Next page"' +
                (currentPage >= pages ? ' disabled' : '') +
                '><i class="bi bi-chevron-right"></i></button>';

        paginationControls.innerHTML = html;
    }

    // Delegated once on the container, so rebuilding the buttons
    // above can never detach the click handlers.
    if (paginationControls) {
        paginationControls.addEventListener('click', function (event) {
            var btn = event.target.closest('.page-btn');
            if (!btn || btn.disabled || !paginationControls.contains(btn)) { return; }

            if (btn.id === 'prevBtn') { window.changePage(-1); return; }
            if (btn.id === 'nextBtn') { window.changePage(1);  return; }

            var page = parseInt(btn.dataset.page, 10);
            if (!isNaN(page)) { window.goToPage(page); }
        });
    }

    window.changePage = function (direction) {
        window.goToPage(currentPage + direction);
    };

    window.goToPage = function (page) {
        var pages = getTotalPages();
        page = parseInt(page, 10);
        if (isNaN(page) || page < 1 || page > pages || page === currentPage) { return; }
        currentPage = page;
        render();
    };

    // ============================================
    // EVENT WIRING
    // ============================================
    if (searchInput) {
        searchInput.addEventListener('input',  function () { applyFilters(true); });
        searchInput.addEventListener('search', function () { applyFilters(true); });
    }
    if (filterSource) {
        filterSource.addEventListener('change', function () { applyFilters(true); });
    }
    if (filterStatus) {
        filterStatus.addEventListener('change', function () { applyFilters(true); });
    }
    if (sortSelect) {
        sortSelect.addEventListener('change', applySort);
    }

    applyFilters(true);
});

// ===== APPROVE PATIENT =====
function approvePatient(patientId) {
    if (!patientId || patientId === 0) {
        alert('Patient ID not available. Please sync patients first.');
        return;
    }
    
    if (!confirm('Are you sure you want to approve this patient? This will generate a patient code.')) {
        return;
    }
    
    // Show loading state
    const btn = event?.target?.closest('.approve') || document.querySelector(`.approve[data-id="${patientId}"]`);
    if (btn) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        btn.disabled = true;
    }
    
    fetch(`/polymedic/public/admin/approve-patient/${patientId}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Patient approved successfully! Patient Code: ' + data.patient_code);
            location.reload();
        } else {
            alert('Error: ' + data.message);
            if (btn) {
                btn.innerHTML = '<i class="bi bi-check-circle"></i>';
                btn.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error approving patient. Please try again.');
        if (btn) {
            btn.innerHTML = '<i class="bi bi-check-circle"></i>';
            btn.disabled = false;
        }
    });
}

// ===== VIEW PATIENT =====
function viewPatient(patientId) {
    if (!patientId || patientId === 0) {
        alert('Patient details not available.');
        return;
    }
    window.location.href = `/polymedic/public/admin/patient/view/${patientId}`;
}

// ===== EDIT PATIENT =====
function editPatient(patientId) {
    if (!patientId || patientId === 0) {
        alert('Patient cannot be edited.');
        return;
    }
    window.location.href = `/polymedic/public/admin/patient/edit/${patientId}`;
}

// ===== EXPORT FUNCTION =====
function exportPatients() {
    alert('Export functionality coming soon!');
}
</script>

<?= $this->endSection() ?>