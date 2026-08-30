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
    </div>

    <!-- ===== TABLE TOOLBAR ===== -->
    <div class="table-toolbar">
        <div class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" placeholder="Search patients..." id="searchPatients">
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
                <option value="last_visit">Sort by: Last Visit</option>
                <option value="patient_name">Sort by: Patient Name</option>
                <option value="age">Sort by: Age</option>
                <option value="status">Sort by: Status</option>
            </select>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ===== TABLE CARD ===== -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table patients-table" id="patientsTable">
                <thead>
                    <tr>
                        <th>Patient ID <i class="bi bi-arrow-down-up"></i></th>
                        <th>Patient Name</th>
                        <th>Source</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Last Visit <i class="bi bi-arrow-down-up"></i></th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="patientsTableBody">
                    <?php if (!empty($patients) && is_array($patients)): ?>
                        <?php $counter = 1; ?>
                        <?php foreach ($patients as $patient): ?>
                            <?php 
                            $lastVisit = $patient['last_visit'] ?? null;
                            $isActive = $lastVisit && strtotime($lastVisit) > strtotime('-30 days');
                            ?>
                            <tr data-status="<?= $isActive ? 'active' : 'inactive' ?>" data-source="<?= esc($patient['source'] ?? '') ?>">
                                <td><span class="patient-id">PM-<?= str_pad($counter, 4, '0', STR_PAD_LEFT) ?></span></td>
                                <td>
                                    <div class="patient-cell">
                                        <span class="patient-avatar">
                                            <?php 
                                                $nameParts = preg_split('/\s+/', trim($patient['full_name'] ?? 'Unknown'));
                                                $initials = strtoupper(mb_substr($nameParts[0] ?? 'U', 0, 1) . mb_substr($nameParts[count($nameParts) - 1] ?? 'N', 0, 1));
                                                echo esc($initials);
                                            ?>
                                        </span>
                                        <div class="patient-meta">
                                            <span class="patient-name"><?= esc($patient['full_name'] ?? 'Unknown') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="source-badge <?= strtolower($patient['source'] ?? 'walk-in') ?>">
                                        <?= esc($patient['source'] ?? 'Walk-in') ?>
                                    </span>
                                </td>
                                <td><?= esc($patient['gender'] ?? 'N/A') ?></td>
                                <td><?= esc($patient['age'] ?? 'N/A') ?></td>
                                <td><?= esc($patient['phone'] ?? 'N/A') ?></td>
                                <td><?= esc($patient['email'] ?? 'N/A') ?></td>
                                <td>
                                    <div class="date-cell">
                                        <i class="bi bi-calendar3"></i>
                                        <span><?= $lastVisit ? date('M d, Y', strtotime($lastVisit)) : '—' ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?= $isActive ? 'active' : 'inactive' ?>">
                                        <i class="bi bi-circle-fill"></i>
                                        <?= $isActive ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="action-cell">
                                    <a href="<?= base_url('admin/patient/view/' . ($patient['id'] ?? $counter)) ?>" class="action-icon-btn view" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= base_url('admin/patient/edit/' . ($patient['id'] ?? $counter)) ?>" class="action-icon-btn edit" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="mailto:<?= esc($patient['email'] ?? '') ?>" class="action-icon-btn email" title="Email">
                                        <i class="bi bi-envelope"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php $counter++; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <i class="bi bi-people"></i>
                                    <p>No patients found</p>
                                    <small>Patients will appear here once registered</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- ===== TABLE FOOTER / PAGINATION ===== -->
        <div class="table-footer">
            <span id="showingText">Showing <?= min(count($patients ?? []), 5) ?> of <?= $total ?? count($patients ?? []) ?> patients</span>
            <div class="pagination" id="paginationControls">
                <button class="page-btn" id="prevBtn" disabled><i class="bi bi-chevron-left"></i></button>
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <button class="page-btn" id="nextBtn"><i class="bi bi-chevron-right"></i></button>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   PATIENTS - ENHANCED UI (Matching Lab Requests)
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
}

.btn-primary:hover {
    background: #1e40af;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(29, 78, 216, 0.2);
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

/* ===== SELECT WRAPPER (With Icons Inside) ===== */
.select-wrapper {
    position: relative;
    min-width: 180px;
}

.select-wrapper.filter-wrapper::before {
    content: '\F64E'; /* funnel icon */
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
    content: '\F174'; /* sort icon */
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
    padding: 0.45rem 2.5rem 0.45rem 2.3rem; /* Left padding for icon, right for dropdown arrow */
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

.patients-table thead th i {
    font-size: 0.65rem;
    margin-left: 0.2rem;
    color: var(--ink-faint);
    opacity: 0.5;
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

/* Patient ID */
.patient-id {
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--ink);
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

.source-badge.online {
    background: var(--blue-soft);
    color: var(--blue);
}

.source-badge.walk-in {
    background: var(--green-soft);
    color: var(--green);
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
}

.action-icon-btn:hover {
    background: var(--blue-soft);
    color: var(--blue);
    transform: translateY(-1px);
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
        padding: 0.7rem;
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
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    document.getElementById('searchPatients').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#patientsTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Filter by status
    document.getElementById('filterStatus').addEventListener('change', function() {
        const status = this.value.toLowerCase();
        const rows = document.querySelectorAll('#patientsTable tbody tr[data-status]');
        
        rows.forEach(row => {
            if (!status) {
                row.style.display = '';
                return;
            }
            const rowStatus = row.dataset.status.toLowerCase();
            row.style.display = rowStatus === status ? '' : 'none';
        });
    });

    // Sort by
    document.getElementById('sortBy').addEventListener('change', function() {
        const sortBy = this.value;
        const tbody = document.querySelector('#patientsTable tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            let aVal, bVal;
            switch(sortBy) {
                case 'patient_name':
                    aVal = a.querySelector('.patient-name')?.textContent.toLowerCase() || '';
                    bVal = b.querySelector('.patient-name')?.textContent.toLowerCase() || '';
                    break;
                case 'age':
                    aVal = parseInt(a.querySelector('td:nth-child(5)')?.textContent) || 0;
                    bVal = parseInt(b.querySelector('td:nth-child(5)')?.textContent) || 0;
                    break;
                case 'status':
                    aVal = a.querySelector('.status-badge')?.textContent.toLowerCase() || '';
                    bVal = b.querySelector('.status-badge')?.textContent.toLowerCase() || '';
                    break;
                default: // last_visit
                    aVal = a.querySelector('.date-cell span')?.textContent || '';
                    bVal = b.querySelector('.date-cell span')?.textContent || '';
            }
            return aVal.localeCompare(bVal);
        });

        rows.forEach(row => tbody.appendChild(row));
    });

    // Pagination logic (1-5 per page)
    const rows = Array.from(document.querySelectorAll('#patientsTableBody tr'));
    const showingText = document.getElementById('showingText');
    const paginationControls = document.getElementById('paginationControls');
    const pageSize = 5;
    let currentPage = 1;
    const totalEntries = <?= $total ?? count($patients ?? []) ?>;

    function renderTable() {
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;
        
        rows.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });

        const showingCount = Math.min(end, totalEntries);
        showingText.textContent = `Showing ${start + 1} to ${showingCount} of ${totalEntries} patients`;
    }

    // Set up dynamic pagination
    function setupPagination() {
        const totalPages = Math.ceil(totalEntries / pageSize);
        
        // Clear existing buttons
        paginationControls.innerHTML = `
            <button class="page-btn" id="prevBtn" disabled><i class="bi bi-chevron-left"></i></button>
            ${Array.from({length: totalPages}, (_, i) => `<button class="page-btn ${i === 0 ? 'active' : ''}" onclick="goToPage(${i + 1})">${i + 1}</button>`).join('')}
            <button class="page-btn" id="nextBtn"><i class="bi bi-chevron-right"></i></button>
        `;
        
        document.getElementById('prevBtn').addEventListener('click', () => changePage(-1));
        document.getElementById('nextBtn').addEventListener('click', () => changePage(1));
    }

    window.changePage = function(direction) {
        const totalPages = Math.ceil(totalEntries / pageSize);
        currentPage = Math.min(Math.max(1, currentPage + direction), totalPages);
        renderTable();
        updateActivePage();
        updatePrevNextButtons();
    }

    window.goToPage = function(page) {
        currentPage = page;
        renderTable();
        updateActivePage();
        updatePrevNextButtons();
    }

    function updateActivePage() {
        const totalPages = Math.ceil(totalEntries / pageSize);
        const buttons = document.querySelectorAll('.pagination .page-btn');
        buttons.forEach((btn, i) => {
            if (i > 0 && i <= totalPages) {
                btn.classList.toggle('active', i === currentPage);
            }
        });
    }

    function updatePrevNextButtons() {
        const totalPages = Math.ceil(totalEntries / pageSize);
        document.getElementById('prevBtn').disabled = currentPage === 1;
        document.getElementById('nextBtn').disabled = currentPage === totalPages;
    }

    setupPagination();
    renderTable();
});
</script>

<?= $this->endSection() ?>