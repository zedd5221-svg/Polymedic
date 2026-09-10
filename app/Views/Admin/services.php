<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Service Management<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<div class="services-container">
    <!-- ===== STATS CARDS ROW ===== -->
    <div class="stats-cards-row">
        
        <!-- Laboratory -->
        <div class="stat-card-simple">
            <div class="stat-card-top">
                <span class="stat-card-icon lab-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 3h6"></path>
                        <path d="M10 3v6.5L4.5 18a2 2 0 0 0 1.8 3h11.4a2 2 0 0 0 1.8-3L14 9.5V3"></path>
                    </svg>
                </span>
                <span class="stat-card-badge"><?= $lab_count ?? 0 ?></span>
            </div>
            <div class="stat-card-number"><?= $lab_count ?? 0 ?></div>
            <div class="stat-card-title">Laboratory</div>
            <div class="stat-card-sub">Active services</div>
        </div>

        <!-- X-Ray -->
        <div class="stat-card-simple">
            <div class="stat-card-top">
                <span class="stat-card-icon xray-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 6v6l4 2"></path>
                    </svg>
                </span>
                <span class="stat-card-badge"><?= $xray_count ?? 0 ?></span>
            </div>
            <div class="stat-card-number"><?= $xray_count ?? 0 ?></div>
            <div class="stat-card-title">X-Ray</div>
            <div class="stat-card-sub">Active services</div>
        </div>

        <!-- Other -->
        <div class="stat-card-simple">
            <div class="stat-card-top">
                <span class="stat-card-icon other-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="12" cy="5" r="1"></circle>
                        <circle cx="12" cy="19" r="1"></circle>
                    </svg>
                </span>
                <span class="stat-card-badge"><?= $other_count ?? 0 ?></span>
            </div>
            <div class="stat-card-number"><?= $other_count ?? 0 ?></div>
            <div class="stat-card-title">Other</div>
            <div class="stat-card-sub">Active services</div>
        </div>

        <!-- Total Services -->
        <div class="stat-card-simple">
            <div class="stat-card-top">
                <span class="stat-card-icon total-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4"></path>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                </span>
                <span class="stat-card-badge"><?= $total ?? 0 ?></span>
            </div>
            <div class="stat-card-number"><?= $total ?? 0 ?></div>
            <div class="stat-card-title">Total Services</div>
            <div class="stat-card-sub">All categories</div>
        </div>
    </div>

    <!-- ===== FILTER TABS ===== -->
    <div class="filter-tabs-row">
        <button class="filter-tab active" data-filter="all">
            All <span class="tab-count"><?= $total ?? 0 ?></span>
        </button>
        <button class="filter-tab" data-filter="laboratory">
            Laboratory <span class="tab-count"><?= $lab_count ?? 0 ?></span>
        </button>
        <button class="filter-tab" data-filter="xray">
            X-Ray <span class="tab-count"><?= $xray_count ?? 0 ?></span>
        </button>
        <button class="filter-tab" data-filter="other">
            Other <span class="tab-count"><?= $other_count ?? 0 ?></span>
        </button>
    </div>

    <!-- ===== SEARCH BAR ===== -->
    <div class="search-container">
        <div class="search-box">
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" class="search-input" placeholder="Search services by name or code..." id="searchServices">
        </div>
    </div>

    <!-- ===== TABLE ===== -->
    <div class="table-container">
        <table class="service-table" id="servicesTable">
            <thead>
                <tr>
                    <th>CODE</th>
                    <th>SERVICE NAME</th>
                    <th>CATEGORY</th>
                    <th>PRICE (₱)</th>
                    <th>STATUS</th>
                    <th>CREATED</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($services)): ?>
                    <?php foreach ($services as $service): ?>
                        <tr data-category="<?= $service['category'] ?>">
                            <td>
                                <span class="code-badge"><?= esc($service['service_code']) ?></span>
                            </td>
                            <td>
                                <div class="service-name-cell">
                                    <span class="service-name-icon">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2">
                                            <path d="M9 3h6"></path>
                                            <path d="M10 3v6.5L4.5 18a2 2 0 0 0 1.8 3h11.4a2 2 0 0 0 1.8-3L14 9.5V3"></path>
                                        </svg>
                                    </span>
                                    <?= esc($service['service_name']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="category-badge <?= $service['category'] ?>">
                                    <?= ucfirst($service['category']) ?>
                                </span>
                            </td>
                            <td class="price-cell">
                                <strong>₱<?= number_format($service['charge'] ?? 0, 2) ?></strong>
                            </td>
                            <td>
                                <span class="status-badge <?= $service['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $service['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="date-cell"><?= date('M d, Y', strtotime($service['created_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn edit-service" 
                                            data-id="<?= $service['id'] ?>"
                                            data-code="<?= esc($service['service_code']) ?>"
                                            data-name="<?= esc($service['service_name']) ?>"
                                            data-category="<?= esc($service['category']) ?>"
                                            data-description="<?= esc($service['description'] ?? '') ?>"
                                            data-charge="<?= $service['charge'] ?? 0 ?>"
                                            data-active="<?= $service['is_active'] ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editServiceModal" title="Edit">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                        </svg>
                                    </button>
                                    <a href="<?= base_url('admin/services/toggle/' . $service['id']) ?>" 
                                       class="action-btn" 
                                       title="<?= $service['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                        </svg>
                                    </a>
                                    <button class="action-btn" title="More">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="1"></circle>
                                            <circle cx="19" cy="12" r="1"></circle>
                                            <circle cx="5" cy="12" r="1"></circle>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No services found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- ===== PAGINATION ===== -->
        <div class="pagination-row">
            <span class="showing-text">Showing <?= $startRow ?? 1 ?> to <?= $endRow ?? 0 ?> of <?= $total ?? 0 ?> services</span>
            <div class="pagination-controls">
                <?php if ($currentPage > 1): ?>
                    <a href="<?= base_url('admin/services?page=' . ($currentPage - 1)) ?>" class="page-btn"><i class="bi bi-chevron-left"></i></a>
                <?php else: ?>
                    <span class="page-btn disabled"><i class="bi bi-chevron-left"></i></span>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $currentPage): ?>
                        <span class="page-btn active"><?= $i ?></span>
                    <?php elseif ($i <= 3 || $i > $totalPages - 3 || abs($i - $currentPage) <= 1): ?>
                        <a href="<?= base_url('admin/services?page=' . $i) ?>" class="page-btn"><?= $i ?></a>
                    <?php elseif ($i == 4 && $currentPage > 5): ?>
                        <span class="page-btn dots">...</span>
                    <?php elseif ($i == $totalPages - 3 && $currentPage < $totalPages - 4): ?>
                        <span class="page-btn dots">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= base_url('admin/services?page=' . ($currentPage + 1)) ?>" class="page-btn"><i class="bi bi-chevron-right"></i></a>
                <?php else: ?>
                    <span class="page-btn disabled"><i class="bi bi-chevron-right"></i></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== ADD SERVICE MODAL ===== -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('admin/services/create') ?>" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Service Code *</label>
                        <input type="text" class="form-control" name="service_code" placeholder="e.g., LAB-CBC" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Service Name *</label>
                        <input type="text" class="form-control" name="service_name" placeholder="e.g., Complete Blood Count (CBC)" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category *</label>
                        <select class="form-select" name="category" required>
                            <option value="">Select Category</option>
                            <option value="laboratory">Laboratory</option>
                            <option value="xray">X-Ray</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price (₱)</label>
                        <input type="number" class="form-control" name="charge" placeholder="0.00" step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="is_active">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Create Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== EDIT SERVICE MODAL ===== -->
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editServiceForm" action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="service_id" id="edit_service_id">
                    <div class="mb-3">
                        <label class="form-label">Service Code *</label>
                        <input type="text" class="form-control" name="service_code" id="edit_service_code" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Service Name *</label>
                        <input type="text" class="form-control" name="service_name" id="edit_service_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category *</label>
                        <select class="form-select" name="category" id="edit_service_category" required>
                            <option value="laboratory">Laboratory</option>
                            <option value="xray">X-Ray</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_service_description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price (₱)</label>
                        <input type="number" class="form-control" name="charge" id="edit_service_charge" step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="is_active" id="edit_service_active">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Update Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* ============================================
   SERVICE MANAGEMENT - EXACT MATCH DESIGN
   ============================================ */

.services-container {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: #F8FAFC;
    padding: 24px;
    min-height: 100vh;
}

/* ===== HEADER ===== */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.header-left {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0D9488;
    margin: 0;
    letter-spacing: -0.02em;
}

.header-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #64748B;
}

.header-meta i {
    margin-right: 4px;
    color: #94A3B8;
}

.meta-separator {
    color: #CBD5E1;
}

.btn-add-service {
    background: #0D9488;
    border: none;
    color: white;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-add-service:hover {
    background: #0F766E;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
}

/* ===== STATS CARDS ROW ===== */
.stats-cards-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card-simple {
    background: #FFFFFF;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    border: 1px solid #F1F5F9;
    transition: all 0.3s ease;
}

.stat-card-simple:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.stat-card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.stat-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.lab-icon { background: #E6F7F7; color: #0D9488; }
.xray-icon { background: #FFF4E5; color: #F59E0B; }
.other-icon { background: #F0EBFE; color: #8B5CF6; }
.total-icon { background: #E6F7F7; color: #0D9488; }

.stat-card-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    background: #E6F7F7;
    color: #0D9488;
}

.stat-card-simple:nth-child(2) .stat-card-badge { background: #FFF4E5; color: #F59E0B; }
.stat-card-simple:nth-child(3) .stat-card-badge { background: #F0EBFE; color: #8B5CF6; }
.stat-card-simple:nth-child(4) .stat-card-badge { background: #E6F7F7; color: #0D9488; }

.stat-card-number {
    font-size: 2rem;
    font-weight: 800;
    color: #1E293B;
    line-height: 1;
    margin-bottom: 8px;
    letter-spacing: -0.02em;
}

.stat-card-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1E293B;
    margin-bottom: 2px;
}

.stat-card-sub {
    font-size: 0.75rem;
    color: #94A3B8;
}

/* ===== FILTER TABS ===== */
.filter-tabs-row {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.filter-tab {
    padding: 8px 16px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 500;
    color: #64748B;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-tab:hover {
    border-color: #0D9488;
    color: #0D9488;
}

.filter-tab.active {
    background: #0D9488;
    color: white;
    border-color: #0D9488;
}

.tab-count {
    background: rgba(0,0,0,0.05);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.filter-tab.active .tab-count {
    background: rgba(255,255,255,0.2);
}

/* ===== SEARCH BAR ===== */
.search-container {
    margin-bottom: 20px;
}

.search-box {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #FFFFFF;
    border-radius: 12px;
    padding: 12px 16px;
    border: 1px solid #E2E8F0;
    transition: all 0.3s ease;
}

.search-box:focus-within {
    border-color: #0D9488;
    box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.08);
}

.search-icon {
    color: #94A3B8;
}

.search-input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 0.9rem;
    color: #1E293B;
    background: transparent;
}

.search-input::placeholder {
    color: #94A3B8;
}

/* ===== TABLE ===== */
.table-container {
    background: #FFFFFF;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    border: 1px solid #F1F5F9;
    overflow: hidden;
}

.service-table {
    width: 100%;
    border-collapse: collapse;
}

.service-table thead th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #94A3B8;
    font-weight: 600;
    padding: 16px 20px;
    border-bottom: 1px solid #F1F5F9;
    text-align: left;
}

.service-table tbody td {
    padding: 16px 20px;
    vertical-align: middle;
    font-size: 0.85rem;
    color: #1E293B;
    border-bottom: 1px solid #F1F5F9;
}

.service-table tbody tr:hover {
    background: #F8FAFC;
}

.service-table tbody tr:last-child td {
    border-bottom: none;
}

.code-badge {
    background: #E6F7F7;
    color: #0D9488;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    font-family: monospace;
}

.service-name-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
}

.service-name-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: #F8FAFC;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.category-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.category-badge.laboratory {
    background: #E6F7F7;
    color: #0D9488;
}

.category-badge.xray {
    background: #FFF4E5;
    color: #F59E0B;
}

.category-badge.other {
    background: #F0EBFE;
    color: #8B5CF6;
}

.price-cell {
    font-weight: 600;
    color: #1E293B;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-badge.active {
    background: #E6F7F7;
    color: #0D9488;
}

.status-badge.inactive {
    background: #FCE7F3;
    color: #EC4899;
}

.date-cell {
    color: #64748B;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.action-btn {
    background: transparent;
    border: none;
    color: #94A3B8;
    padding: 6px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-btn:hover {
    color: #0D9488;
    background: #E6F7F7;
}

/* ===== PAGINATION ===== */
.pagination-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-top: 1px solid #F1F5F9;
}

.showing-text {
    font-size: 0.8rem;
    color: #64748B;
}

.pagination-controls {
    display: flex;
    gap: 4px;
}

.page-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: #64748B;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.page-btn:hover {
    background: #F1F5F9;
    color: #1E293B;
}

.page-btn.active {
    background: #0D9488;
    color: white;
    font-weight: 600;
}

.page-btn.disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.page-btn.dots {
    cursor: default;
    background: transparent;
}

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1200px) {
    .stats-cards-row {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 992px) {
    .services-container {
        padding: 16px;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}

@media (max-width: 768px) {
    .stats-cards-row {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .filter-tabs-row {
        flex-wrap: wrap;
    }
    
    .service-table {
        min-width: 800px;
    }
    
    .table-container {
        overflow-x: auto;
    }
}

@media (max-width: 480px) {
    .stats-cards-row {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        gap: 8px;
    }
    
    .btn-add-service {
        width: 100%;
        justify-content: center;
    }
    
    .pagination-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            const filter = this.dataset.filter;
            
            document.querySelectorAll('#servicesTable tbody tr').forEach(row => {
                if (filter === 'all' || row.dataset.category === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
    
    // Search functionality
    document.getElementById('searchServices').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('#servicesTable tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
    
    // Edit modal - populate data
    document.querySelectorAll('.edit-service').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const code = this.dataset.code;
            const name = this.dataset.name;
            const category = this.dataset.category;
            const description = this.dataset.description || '';
            const charge = this.dataset.charge || 0;
            const active = this.dataset.active;
            
            document.getElementById('edit_service_id').value = id;
            document.getElementById('edit_service_code').value = code;
            document.getElementById('edit_service_name').value = name;
            document.getElementById('edit_service_category').value = category;
            document.getElementById('edit_service_description').value = description;
            document.getElementById('edit_service_charge').value = charge;
            document.getElementById('edit_service_active').value = active;
            
            document.getElementById('editServiceForm').action = `<?= base_url('admin/services/update') ?>/${id}`;
        });
    });
});
</script>

<?= $this->endSection() ?>