<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Service Management<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<div class="services-container">
    <!-- Header -->
    <div class="page-header">
        <div>
            <h4 class="page-title">Service Management</h4>
            <p class="page-subtitle">Manage laboratory and X-Ray services</p>
        </div>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addServiceModal">
            <i class="bi bi-plus-circle me-2"></i>Add New Service
        </button>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-mini">
            <span class="stat-mini-icon lab"><i class="bi bi-droplet"></i></span>
            <div>
                <span class="stat-mini-number"><?= $lab_count ?? 0 ?></span>
                <span class="stat-mini-label">Laboratory</span>
            </div>
        </div>
        <div class="stat-mini">
            <span class="stat-mini-icon xray"><i class="bi bi-x-ray"></i></span>
            <div>
                <span class="stat-mini-number"><?= $xray_count ?? 0 ?></span>
                <span class="stat-mini-label">X-Ray</span>
            </div>
        </div>
        <div class="stat-mini">
            <span class="stat-mini-icon other"><i class="bi bi-file-medical"></i></span>
            <div>
                <span class="stat-mini-number"><?= $other_count ?? 0 ?></span>
                <span class="stat-mini-label">Other</span>
            </div>
        </div>
        <div class="stat-mini">
            <span class="stat-mini-icon total"><i class="bi bi-grid-3x3-gap-fill"></i></span>
            <div>
                <span class="stat-mini-number"><?= $total ?? 0 ?></span>
                <span class="stat-mini-label">Total Services</span>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-2"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <button class="filter-tab active" data-filter="all">All (<?= $total ?? 0 ?>)</button>
        <button class="filter-tab" data-filter="laboratory">Laboratory (<?= $lab_count ?? 0 ?>)</button>
        <button class="filter-tab" data-filter="xray">X-Ray (<?= $xray_count ?? 0 ?>)</button>
        <button class="filter-tab" data-filter="other">Other (<?= $other_count ?? 0 ?>)</button>
    </div>

    <!-- Services Table -->
    <div class="table-card">
        <div class="table-toolbar">
            <div class="search-wrapper">
                <i class="bi bi-search"></i>
                <input type="text" class="form-control" placeholder="Search services..." id="searchServices">
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table service-table" id="servicesTable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Service Name</th>
                        <th>Category</th>
                        <th>Price (₱)</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($services)): ?>
                        <?php foreach ($services as $service): ?>
                            <tr data-category="<?= $service['category'] ?>">
                                <td><code><?= esc($service['service_code']) ?></code></td>
                                <td><?= esc($service['service_name']) ?></td>
                                <td>
                                    <span class="category-badge <?= $service['category'] ?>">
                                        <?= ucfirst($service['category']) ?>
                                    </span>
                                </td>
                                <td><strong>₱<?= number_format($service['charge'] ?? 0, 2) ?></strong></td>
                                <td>
                                    <span class="status-badge <?= $service['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $service['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($service['created_at'])) ?></td>
                                <td>
                                    <button class="action-icon edit-service" 
                                            data-id="<?= $service['id'] ?>"
                                            data-code="<?= esc($service['service_code']) ?>"
                                            data-name="<?= esc($service['service_name']) ?>"
                                            data-category="<?= esc($service['category']) ?>"
                                            data-description="<?= esc($service['description'] ?? '') ?>"
                                            data-charge="<?= $service['charge'] ?? 0 ?>"
                                            data-active="<?= $service['is_active'] ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editServiceModal">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="<?= base_url('admin/services/toggle/' . $service['id']) ?>" 
                                       class="action-icon" 
                                       title="<?= $service['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                        <i class="bi bi-<?= $service['is_active'] ? 'lock' : 'unlock' ?>"></i>
                                    </a>
                                    <a href="<?= base_url('admin/services/delete/' . $service['id']) ?>" 
                                       class="action-icon text-danger" 
                                       onclick="return confirm('Delete this service?')">
                                        <i class="bi bi-trash3"></i>
                                    </a>
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
                        <small class="text-muted">Unique identifier for the service</small>
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
                        <textarea class="form-control" name="description" rows="2" placeholder="Brief description of the service"></textarea>
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
/* ===== PAGE HEADER ===== */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-title {
    font-weight: 700;
    color: #0a2b4e;
    margin: 0;
    font-size: 1.3rem;
}

.page-subtitle {
    color: #64748b;
    font-size: 0.85rem;
    margin: 0;
}

.btn-primary-custom {
    background: linear-gradient(135deg, #0148ca, #0037a0);
    border: none;
    color: white;
    padding: 0.6rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(1, 72, 202, 0.3);
    color: white;
}

/* ===== STATS ROW ===== */
.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-mini {
    background: #ffffff;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
}

.stat-mini-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.stat-mini-icon.lab { background: #e8f5e9; color: #28a745; }
.stat-mini-icon.xray { background: #f3e5f5; color: #7b1fa2; }
.stat-mini-icon.other { background: #fff3e0; color: #e65100; }
.stat-mini-icon.total { background: #e3f2fd; color: #0148ca; }

.stat-mini-number {
    font-size: 1.2rem;
    font-weight: 700;
    color: #0a2b4e;
    display: block;
    line-height: 1;
}

.stat-mini-label {
    font-size: 0.7rem;
    color: #64748b;
}

/* ===== FILTER TABS ===== */
.filter-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
}

.filter-tab {
    padding: 0.3rem 1rem;
    border-radius: 30px;
    font-size: 0.78rem;
    font-weight: 500;
    color: #64748b;
    background: #f0f4ff;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-tab:hover {
    background: #e6f0fa;
    color: #0148ca;
}

.filter-tab.active {
    background: #0148ca;
    color: #fff;
}

/* ===== TABLE ===== */
.table-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
}

.table-toolbar {
    margin-bottom: 1.25rem;
}

.search-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.25rem 0.75rem;
    max-width: 300px;
    transition: all 0.3s ease;
}

.search-wrapper:focus-within {
    border-color: #0148ca;
    box-shadow: 0 0 0 4px rgba(1, 72, 202, 0.08);
}

.search-wrapper i {
    color: #94a3b8;
}

.search-wrapper .form-control {
    border: none;
    padding: 0.5rem 0;
    font-size: 0.9rem;
    background: transparent;
}

.search-wrapper .form-control:focus {
    box-shadow: none;
}

.service-table {
    margin: 0;
}

.service-table thead th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    font-weight: 600;
    border-bottom: 2px solid #f0f4ff;
    padding: 0.75rem 0.5rem;
}

.service-table tbody td {
    padding: 0.75rem 0.5rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: #0a2b4e;
    border-bottom: 1px solid #f0f4ff;
}

.service-table tbody tr:hover {
    background: #f8faff;
}

.category-badge {
    padding: 0.2rem 0.6rem;
    border-radius: 30px;
    font-size: 0.65rem;
    font-weight: 600;
}

.category-badge.laboratory {
    background: #e8f5e9;
    color: #28a745;
}

.category-badge.xray {
    background: #f3e5f5;
    color: #7b1fa2;
}

.category-badge.other {
    background: #fff3e0;
    color: #e65100;
}

.status-badge {
    padding: 0.2rem 0.6rem;
    border-radius: 30px;
    font-size: 0.65rem;
    font-weight: 600;
}

.status-badge.active {
    background: #e8f5e9;
    color: #28a745;
}

.status-badge.inactive {
    background: #fce4ec;
    color: #dc3545;
}

.action-icon {
    background: transparent;
    border: none;
    color: #94a3b8;
    padding: 0.2rem 0.4rem;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
    text-decoration: none;
}

.action-icon:hover {
    color: #0148ca;
}

.action-icon.text-danger:hover {
    color: #dc3545;
}

code {
    background: #f0f4ff;
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    font-size: 0.75rem;
    color: #0148ca;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .search-wrapper {
        max-width: 100%;
    }
}

@media (max-width: 480px) {
    .stats-row {
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }
    
    .stat-mini {
        padding: 0.75rem;
    }
    
    .stat-mini-number {
        font-size: 1rem;
    }
    
    .stat-mini-icon {
        width: 32px;
        height: 32px;
        font-size: 0.9rem;
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