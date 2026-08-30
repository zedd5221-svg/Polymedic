<?= $this->extend('layouts/MedTechLayout') ?>

<?= $this->section('pageTitle') ?>Laboratory Requests<?= $this->endSection() ?>

<?= $this->section('medtechContent') ?>

<div class="requests-container">

    <!-- ===== PAGE HEADER ===== -->
    <div class="page-header">
        <div>
            <h4 class="page-title">Laboratory Requests</h4>
            <p class="page-subtitle">Manage and track all laboratory test requests</p>
        </div>
    </div>

    <!-- ===== STATS CARDS ===== -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-clipboard2-pulse"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['total'] ?? 0 ?></h3>
                <p>All Requests</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['pending'] ?? 0 ?></h3>
                <p>Pending</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['in_progress'] ?? 0 ?></h3>
                <p>In Progress</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon gray">
                <i class="bi bi-file-earmark"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['draft'] ?? 0 ?></h3>
                <p>Drafts</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['completed'] ?? 0 ?></h3>
                <p>Completed</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal">
                <i class="bi bi-send-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= $counts['released'] ?? 0 ?></h3>
                <p>Released</p>
            </div>
        </div>
    </div>

    <!-- ===== TABLE TOOLBAR (Search + Filter + Sort) ===== -->
    <div class="table-toolbar">
        <div class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" placeholder="Search by patient name, accession no., or test..." id="searchRequests">
        </div>
        <div class="filter-wrapper">
            <select class="form-select" id="filterStatus">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="draft">Drafts</option>
                <option value="completed">Completed</option>
                <option value="released">Released</option>
            </select>
        </div>
        <div class="sort-wrapper">
            <select class="form-select" id="sortBy">
                <option value="request_date">Sort by: Request Date</option>
                <option value="patient_name">Sort by: Patient Name</option>
                <option value="tat">Sort by: TAT</option>
                <option value="status">Sort by: Status</option>
            </select>
        </div>
    </div>

    <!-- ===== TABLE CARD ===== -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table medtech-request-table" id="requestsTable">
                <thead>
                    <tr>
                        <th>Accession No. <i class="bi bi-arrow-down-up"></i></th>
                        <th>Patient</th>
                        <th>Lab Services</th>
                        <th>Request Date <i class="bi bi-arrow-down-up"></i></th>
                        <th>TAT <i class="bi bi-arrow-down-up"></i></th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="requestsTableBody">
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $request): ?>
                            <?php
                                $nameParts = preg_split('/\s+/', trim($request['patient_name']));
                                $initials = strtoupper(mb_substr($nameParts[0] ?? '', 0, 1) . mb_substr($nameParts[count($nameParts) - 1] ?? '', 0, 1));
                                $tatMinutes = rand(18, 94);
                            ?>
                            <tr data-status="<?= $request['status'] ?>">
                                <td class="accession-cell">
                                    ACC-26-<?= str_pad($request['id'], 4, '0', STR_PAD_LEFT) ?>
                                </td>
                                <td>
                                    <div class="patient-cell">
                                        <span class="patient-avatar"><?= esc($initials) ?></span>
                                        <div class="patient-meta">
                                            <span class="patient-name"><?= esc($request['patient_name']) ?></span>
                                            <small><?= esc($request['age']) ?> yrs · <?= esc($request['gender']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="services-cell">
                                    <?php 
                                        $services = explode(', ', $request['lab_services']);
                                        foreach (array_slice($services, 0, 2) as $service) {
                                            echo '<div>' . esc($service) . '</div>';
                                        }
                                        if (count($services) > 2) {
                                            echo '<div class="more-services">+' . (count($services) - 2) . ' more</div>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <div class="date-cell">
                                        <i class="bi bi-calendar3"></i>
                                        <span><?= date('M d, Y', strtotime($request['request_date'])) ?></span>
                                    </div>
                                </td>
                                <td class="tat-cell">
                                    <div class="tat-display">
                                        <i class="bi bi-clock"></i>
                                        <span><?= $tatMinutes ?> min</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?= $request['status'] ?>">
                                        <i class="bi bi-circle-fill"></i>
                                        <?= ucfirst(str_replace('_', ' ', $request['status'])) ?>
                                    </span>
                                </td>
                                <td class="action-cell">
                                    <a href="<?= base_url('medtech/request/view/' . $request['id']) ?>" 
                                       class="action-icon-btn view" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($request['status'] === 'released'): ?>
                                        <a href="<?= base_url('medtech/request/print/' . $request['id']) ?>" 
                                           class="action-icon-btn download" title="Download" target="_blank">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= base_url('medtech/request/view/' . $request['id']) ?>" 
                                           class="action-icon-btn edit" title="Process">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p>No laboratory requests found</p>
                                    <small>No requests match the current filter</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="table-footer">
            <span id="showingText">Showing 1 to <?= min(count($requests ?? []), 5) ?> of <?= $counts['total'] ?? 0 ?> entries</span>
            <div class="pagination" id="paginationControls">
                <button class="page-btn" onclick="changePage(-1)" disabled><i class="bi bi-chevron-left"></i></button>
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn" onclick="changePage(1)"><i class="bi bi-chevron-right"></i></button>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   LABORATORY REQUESTS - ENHANCED UI
   ============================================ */

.requests-container {
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
    --gray: #6B7280;
    --gray-soft: #F3F4F6;
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

.btn-primary {
    background: var(--blue);
    color: #ffffff;
    border: none;
    padding: 0.65rem 1.2rem;
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
    padding: 1.25rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
    box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    border: 1px solid var(--line);
    transition: all 0.2s ease;
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

.stat-icon.blue { background: var(--blue-soft); color: var(--blue); }
.stat-icon.orange { background: var(--orange-soft); color: var(--orange); }
.stat-icon.green { background: var(--green-soft); color: var(--green); }
.stat-icon.teal { background: var(--cyan-soft); color: var(--cyan); }
.stat-icon.gray { background: var(--gray-soft); color: var(--gray); }

.stat-info h3 {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--ink);
    margin: 0;
    line-height: 1.1;
}

.stat-info p {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--ink-soft);
    margin: 0.2rem 0 0;
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

.filter-wrapper, .sort-wrapper {
    min-width: 180px;
}

.form-select {
    border: 1px solid var(--line);
    border-radius: 8px;
    font-size: 0.85rem;
    padding: 0.45rem 0.75rem;
    color: var(--ink);
    background-color: var(--surface);
    height: 42px;
    cursor: pointer;
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
.medtech-request-table {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0 8px;
    width: 100%;
}

.medtech-request-table thead th {
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

.medtech-request-table thead th i {
    font-size: 0.65rem;
    margin-left: 0.2rem;
    color: var(--ink-faint);
    opacity: 0.5;
}

.medtech-request-table tbody tr {
    background: var(--surface);
    border-radius: 12px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    border: 1px solid var(--line);
}

.medtech-request-table tbody tr:hover {
    background: var(--surface-alt);
    box-shadow: 0 4px 12px rgba(16, 24, 40, 0.06);
}

.medtech-request-table tbody td {
    padding: 0.85rem 1rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: var(--ink);
    border: none;
    white-space: nowrap;
    background: transparent;
}

.medtech-request-table tbody td:first-child {
    border-radius: 12px 0 0 12px;
}

.medtech-request-table tbody td:last-child {
    border-radius: 0 12px 12px 0;
}

/* Accession Cell */
.accession-cell {
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--blue);
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

.patient-meta small {
    font-size: 0.7rem;
    color: var(--ink-soft);
    margin-top: 1px;
}

/* Services Cell */
.services-cell {
    font-size: 0.8rem;
    color: var(--ink-soft);
    line-height: 1.5;
    max-width: 200px;
    white-space: normal !important;
}

.more-services {
    color: var(--blue);
    font-size: 0.7rem;
    font-weight: 600;
    margin-top: 2px;
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

/* TAT Cell */
.tat-cell {
    color: var(--ink-soft);
}

.tat-display {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.tat-display i {
    color: var(--ink-faint);
    font-size: 0.9rem;
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

.status-badge.in_progress {
    background: var(--blue-soft);
    color: var(--blue);
}

.status-badge.draft {
    background: var(--gray-soft);
    color: var(--gray);
}

.status-badge.completed {
    background: var(--green-soft);
    color: var(--green);
}

.status-badge.released {
    background: var(--cyan-soft);
    color: var(--cyan);
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
    background: var(--cyan-soft);
    color: var(--cyan);
    border-color: #99f6e4;
}

.action-icon-btn.download:hover {
    background: var(--cyan);
    color: #ffffff;
    border-color: var(--cyan);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(14, 116, 144, 0.2);
}

.action-icon-btn.edit {
    background: var(--green-soft);
    color: var(--green);
    border-color: #bbf7d0;
}

.action-icon-btn.edit:hover {
    background: var(--green);
    color: #ffffff;
    border-color: var(--green);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2);
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
    font-size: 0.8rem;
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
    border-radius: 6px;
    background: var(--surface);
    color: var(--ink);
    font-size: 0.8rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
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

@media (max-width: 1400px) {
    .stats-row {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 992px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .table-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-wrapper,
    .filter-wrapper,
    .sort-wrapper {
        min-width: 100%;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .stats-row {
        gap: 0.75rem;
    }
    
    .medtech-request-table {
        border-spacing: 0 6px;
    }
}

@media (max-width: 576px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .medtech-request-table {
        font-size: 0.72rem;
    }
    
    .medtech-request-table thead th,
    .medtech-request-table tbody td {
        padding: 0.7rem;
        font-size: 0.72rem;
    }
    
    .patient-avatar {
        width: 28px;
        height: 28px;
        font-size: 0.55rem;
    }
    
    .status-badge {
        font-size: 0.6rem;
        padding: 0.2rem 0.6rem;
    }
    
    .action-icon-btn {
        width: 28px;
        height: 28px;
        font-size: 0.8rem;
        border-radius: 6px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    document.getElementById('searchRequests').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#requestsTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Filter by status
    document.getElementById('filterStatus').addEventListener('change', function() {
        const status = this.value.toLowerCase();
        const rows = document.querySelectorAll('#requestsTable tbody tr[data-status]');
        
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
        const tbody = document.querySelector('#requestsTable tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            let aVal, bVal;
            switch(sortBy) {
                case 'patient_name':
                    aVal = a.querySelector('.patient-name')?.textContent.toLowerCase() || '';
                    bVal = b.querySelector('.patient-name')?.textContent.toLowerCase() || '';
                    break;
                case 'tat':
                    aVal = parseInt(a.querySelector('.tat-cell')?.textContent) || 0;
                    bVal = parseInt(b.querySelector('.tat-cell')?.textContent) || 0;
                    break;
                case 'status':
                    aVal = a.querySelector('.status-badge')?.textContent.toLowerCase() || '';
                    bVal = b.querySelector('.status-badge')?.textContent.toLowerCase() || '';
                    break;
                default: // request_date
                    aVal = a.querySelector('.date-cell span')?.textContent || '';
                    bVal = b.querySelector('.date-cell span')?.textContent || '';
            }
            return aVal.localeCompare(bVal);
        });

        rows.forEach(row => tbody.appendChild(row));
    });

    // Pagination logic (1-5 per page)
    const rows = Array.from(document.querySelectorAll('#requestsTableBody tr'));
    const showingText = document.getElementById('showingText');
    const paginationControls = document.getElementById('paginationControls');
    const pageSize = 5;
    let currentPage = 1;
    const totalEntries = <?= $counts['total'] ?? count($requests ?? []) ?>;

    function renderTable() {
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;
        
        rows.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });

        const showingCount = Math.min(end, totalEntries);
        showingText.textContent = `Showing ${start + 1} to ${showingCount} of ${totalEntries} entries`;

        // Update pagination buttons
        const pageButtons = paginationControls.querySelectorAll('.page-btn');
        pageButtons.forEach((btn, i) => {
            if (i === 0) btn.disabled = currentPage === 1;
            if (i === pageButtons.length - 1) btn.disabled = currentPage === Math.ceil(totalEntries / pageSize);
        });
    }

    // Make page buttons dynamic (1, 2, etc.)
    function setupPagination() {
        const totalPages = Math.ceil(totalEntries / pageSize);
        paginationControls.innerHTML = `
            <button class="page-btn" onclick="changePage(-1)"><i class="bi bi-chevron-left"></i></button>
            ${Array.from({length: totalPages}, (_, i) => `<button class="page-btn ${i === 0 ? 'active' : ''}" onclick="goToPage(${i + 1})">${i + 1}</button>`).join('')}
            <button class="page-btn" onclick="changePage(1)"><i class="bi bi-chevron-right"></i></button>
        `;
    }

    window.changePage = function(direction) {
        const totalPages = Math.ceil(totalEntries / pageSize);
        currentPage = Math.min(Math.max(1, currentPage + direction), totalPages);
        renderTable();
        
        // Update active state
        document.querySelectorAll('.pagination .page-btn').forEach((btn, i) => {
            if (i > 0 && i <= totalPages) {
                btn.classList.toggle('active', i === currentPage);
            }
        });
    }

    window.goToPage = function(page) {
        currentPage = page;
        renderTable();
        
        // Update active state
        document.querySelectorAll('.pagination .page-btn').forEach((btn, i) => {
            if (i > 0 && i <= Math.ceil(totalEntries / pageSize)) {
                btn.classList.toggle('active', i === currentPage);
            }
        });
    }

    setupPagination();
    renderTable();
});
</script>

<?= $this->endSection() ?>