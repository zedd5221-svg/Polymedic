<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Patient Visits<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<div class="page-header">
    <div class="header-actions">
        <button class="btn btn-export">
            <i class="bi bi-download me-1"></i>Export
        </button>
        <button class="btn btn-primary-custom">
            <i class="bi bi-plus-circle me-2"></i>New Visit
        </button>
    </div>
</div>

<div class="table-card">
    <!-- Search Bar -->
    <div class="search-container">
        <div class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" placeholder="Search visits..." id="searchVisits">
        </div>
    </div>

    <!-- Status Filter Buttons -->
    <div class="filter-container">
        <div class="filter-buttons" id="statusFilters">
            <button class="filter-btn active" data-status="all">All</button>
            <button class="filter-btn" data-status="in-progress">In Progress</button>
            <button class="filter-btn" data-status="completed">Completed</button>
            <button class="filter-btn" data-status="released">Released</button>
            <button class="filter-btn" data-status="cancelled">Cancelled</button>
        </div>
    </div>
    
    <!-- Visits Grid -->
    <div class="visits-grid" id="visitsGrid">
        <!-- Visit Card 1 - In Progress -->
        <div class="visit-card" data-status="in-progress">
            <div class="visit-card-header">
                <div class="patient-info">
                    <div class="patient-avatar">
                        <span>MS</span>
                    </div>
                    <div>
                        <h5>Maria Cristina Santos</h5>
                        <span class="patient-id">#VIS-2024-001</span>
                    </div>
                </div>
                <span class="status-badge in-progress">In Progress</span>
            </div>
            
            <div class="visit-card-body">
                <div class="visit-meta">
                    <span><i class="bi bi-calendar3"></i> Jul 15, 2026</span>
                    <span><i class="bi bi-clock"></i> 09:14 AM</span>
                    <span><i class="bi bi-person"></i> Dr. Ana Cruz</span>
                </div>
                <p class="visit-reason">
                    <i class="bi bi-chat-quote"></i>
                    Follow-up consultation, hypertension check
                </p>
                <div class="visit-requests">
                    <span class="request-tag"><i class="bi bi-flask"></i> CBC</span>
                    <span class="request-tag"><i class="bi bi-flask"></i> Urinalysis</span>
                </div>
            </div>
            
            <div class="visit-card-footer">
                <button class="btn-view"><i class="bi bi-eye"></i> View</button>
                <button class="btn-edit"><i class="bi bi-pencil"></i> Edit</button>
                <button class="btn-more"><i class="bi bi-three-dots-vertical"></i></button>
            </div>
        </div>

        <!-- Visit Card 2 - Completed -->
        <div class="visit-card" data-status="completed">
            <div class="visit-card-header">
                <div class="patient-info">
                    <div class="patient-avatar" style="background: #e8f5e9; color: #28a745;">
                        <span>JR</span>
                    </div>
                    <div>
                        <h5>Juan Pablo Reyes</h5>
                        <span class="patient-id">#VIS-2024-002</span>
                    </div>
                </div>
                <span class="status-badge completed">Completed</span>
            </div>
            
            <div class="visit-card-body">
                <div class="visit-meta">
                    <span><i class="bi bi-calendar3"></i> Jul 14, 2026</span>
                    <span><i class="bi bi-clock"></i> 02:30 PM</span>
                    <span><i class="bi bi-person"></i> Dr. Mark Rivera</span>
                </div>
                <p class="visit-reason">
                    <i class="bi bi-chat-quote"></i>
                    Chest pain evaluation, ECG requested
                </p>
                <div class="visit-requests">
                    <span class="request-tag"><i class="bi bi-heart-pulse"></i> ECG</span>
                    <span class="request-tag"><i class="bi bi-flask"></i> Lipid Profile</span>
                </div>
            </div>
            
            <div class="visit-card-footer">
                <button class="btn-view"><i class="bi bi-eye"></i> View</button>
                <button class="btn-edit"><i class="bi bi-pencil"></i> Edit</button>
                <button class="btn-more"><i class="bi bi-three-dots-vertical"></i></button>
            </div>
        </div>

        <!-- Visit Card 3 - Completed -->
        <div class="visit-card" data-status="completed">
            <div class="visit-card-header">
                <div class="patient-info">
                    <div class="patient-avatar" style="background: #f3e5f5; color: #800080;">
                        <span>EB</span>
                    </div>
                    <div>
                        <h5>Elena Bautista</h5>
                        <span class="patient-id">#VIS-2024-003</span>
                    </div>
                </div>
                <span class="status-badge completed">Completed</span>
            </div>
            
            <div class="visit-card-body">
                <div class="visit-meta">
                    <span><i class="bi bi-calendar3"></i> Jul 14, 2026</span>
                    <span><i class="bi bi-clock"></i> 10:00 AM</span>
                    <span><i class="bi bi-person"></i> Dr. Jose Torres</span>
                </div>
                <p class="visit-reason">
                    <i class="bi bi-chat-quote"></i>
                    Annual physical examination
                </p>
                <div class="visit-requests">
                    <span class="request-tag"><i class="bi bi-flask"></i> CBC</span>
                    <span class="request-tag"><i class="bi bi-flask"></i> Urinalysis</span>
                    <span class="request-tag"><i class="bi bi-flask"></i> Blood Chemistry</span>
                </div>
            </div>
            
            <div class="visit-card-footer">
                <button class="btn-view"><i class="bi bi-eye"></i> View</button>
                <button class="btn-edit"><i class="bi bi-pencil"></i> Edit</button>
                <button class="btn-more"><i class="bi bi-three-dots-vertical"></i></button>
            </div>
        </div>

        <!-- Visit Card 4 - Released -->
        <div class="visit-card" data-status="released">
            <div class="visit-card-header">
                <div class="patient-info">
                    <div class="patient-avatar" style="background: #e0f7fa; color: #17a2b8;">
                        <span>RD</span>
                    </div>
                    <div>
                        <h5>Roberto Dela Cruz</h5>
                        <span class="patient-id">#VIS-2024-004</span>
                    </div>
                </div>
                <span class="status-badge released">Released</span>
            </div>
            
            <div class="visit-card-body">
                <div class="visit-meta">
                    <span><i class="bi bi-calendar3"></i> Jul 13, 2026</span>
                    <span><i class="bi bi-clock"></i> 03:45 PM</span>
                    <span><i class="bi bi-person"></i> Dr. Ana Cruz</span>
                </div>
                <p class="visit-reason">
                    <i class="bi bi-chat-quote"></i>
                    Pre-employment drug test requirement
                </p>
                <div class="visit-requests">
                    <span class="request-tag"><i class="bi bi-capsule"></i> Drug Test</span>
                </div>
            </div>
            
            <div class="visit-card-footer">
                <button class="btn-view"><i class="bi bi-eye"></i> View</button>
                <button class="btn-edit"><i class="bi bi-pencil"></i> Edit</button>
                <button class="btn-more"><i class="bi bi-three-dots-vertical"></i></button>
            </div>
        </div>
    </div>
    
    <!-- Table Footer -->
    <div class="table-footer">
        <span>Showing <strong id="visibleCount">4</strong> of <strong id="totalCount">4</strong> visits</span>
        <div class="pagination-wrapper">
            <button class="page-btn"><i class="bi bi-chevron-left"></i></button>
            <button class="page-btn active">1</button>
            <button class="page-btn"><i class="bi bi-chevron-right"></i></button>
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
    flex-wrap: wrap;
    gap: 1rem;
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

/* ===== ENHANCED BUTTONS ===== */
.header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.btn-export {
    background: transparent;
    border: 2px solid #e2e8f0;
    color: #64748b;
    padding: 0.6rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-export:hover {
    border-color: #0148ca;
    color: #0148ca;
    background: #f0f7ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(1, 72, 202, 0.12);
}

.btn-export i {
    font-size: 1rem;
}

.btn-primary-custom {
    background: linear-gradient(135deg, #0148ca, #0037a0);
    border: none;
    color: white;
    padding: 0.6rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 4px 12px rgba(1, 72, 202, 0.2);
}

.btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(1, 72, 202, 0.3);
    color: white;
}

.btn-primary-custom:active {
    transform: translateY(0);
}

/* ===== TABLE CARD ===== */
.table-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
}

/* ===== SEARCH CONTAINER ===== */
.search-container {
    margin-bottom: 1rem;
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

/* ===== FILTER CONTAINER ===== */
.filter-container {
    margin-bottom: 1.5rem;
}

.filter-buttons {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
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

/* ===== VISITS GRID ===== */
.visits-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.visit-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem;
    border: 1px solid #eef2f7;
    transition: all 0.3s ease;
}

.visit-card:hover {
    box-shadow: 0 8px 25px rgba(10, 43, 78, 0.08);
    transform: translateY(-3px);
    border-color: #dbeafe;
}

.visit-card.hidden {
    display: none;
}

.visit-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.patient-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.patient-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #e6f0fa;
    color: #0148ca;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.patient-info h5 {
    font-weight: 600;
    color: #0a2b4e;
    margin: 0;
    font-size: 0.95rem;
}

.patient-id {
    font-size: 0.7rem;
    color: #94a3b8;
}

.status-badge {
    padding: 0.2rem 0.7rem;
    border-radius: 30px;
    font-size: 0.65rem;
    font-weight: 600;
    white-space: nowrap;
}

.status-badge.in-progress {
    background: #fff3e0;
    color: #ff6b00;
}

.status-badge.completed {
    background: #e8f5e9;
    color: #28a745;
}

.status-badge.released {
    background: #e3f2fd;
    color: #0148ca;
}

.status-badge.cancelled {
    background: #fce4ec;
    color: #dc3545;
}

.visit-card-body {
    padding: 0.25rem 0;
}

.visit-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.8rem;
    color: #64748b;
}

.visit-meta i {
    margin-right: 0.25rem;
    font-size: 0.75rem;
}

.visit-reason {
    font-size: 0.85rem;
    color: #0a2b4e;
    margin: 0.5rem 0;
    background: #f8faff;
    padding: 0.4rem 0.75rem;
    border-radius: 8px;
    border-left: 3px solid #0148ca;
}

.visit-reason i {
    color: #0148ca;
    margin-right: 0.4rem;
}

.visit-requests {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
    margin-top: 0.25rem;
}

.request-tag {
    background: #f0f7ff;
    border: 1px solid #dbeafe;
    padding: 0.15rem 0.7rem;
    border-radius: 30px;
    font-size: 0.7rem;
    color: #0148ca;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.request-tag i {
    font-size: 0.6rem;
}

.visit-card-footer {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f0f4ff;
    align-items: center;
}

.btn-view,
.btn-edit {
    background: transparent;
    border: 1px solid #e2e8f0;
    padding: 0.3rem 0.9rem;
    border-radius: 8px;
    font-size: 0.75rem;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.btn-view:hover {
    border-color: #0148ca;
    color: #0148ca;
    background: #f0f7ff;
}

.btn-edit:hover {
    border-color: #ff6b00;
    color: #ff6b00;
    background: #fff3e0;
}

.btn-more {
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    margin-left: auto;
    transition: all 0.2s ease;
}

.btn-more:hover {
    background: #f0f4ff;
    color: #0a2b4e;
}

/* ===== TABLE FOOTER ===== */
.table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid #f0f4ff;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.table-footer span {
    font-size: 0.85rem;
    color: #64748b;
}

.pagination-wrapper {
    display: flex;
    gap: 0.25rem;
}

.page-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: transparent;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
}

.page-btn:hover {
    border-color: #0148ca;
    color: #0148ca;
}

.page-btn.active {
    background: #0148ca;
    border-color: #0148ca;
    color: white;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
    .visits-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .header-actions {
        width: 100%;
        gap: 0.5rem;
    }
    
    .header-actions .btn {
        flex: 1;
        justify-content: center;
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
    }
    
    .btn-export {
        border-width: 2px;
    }
    
    .search-wrapper {
        max-width: 100%;
    }
    
    .filter-buttons {
        justify-content: center;
    }
    
    .visit-card-header {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .patient-info {
        width: 100%;
    }
    
    .visit-card-footer {
        flex-wrap: wrap;
    }
    
    .visit-card-footer .btn {
        flex: 1;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .header-actions {
        flex-direction: column;
    }
    
    .header-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .visit-card {
        padding: 0.75rem;
    }
    
    .visit-meta {
        gap: 0.5rem;
        font-size: 0.7rem;
    }
    
    .visit-reason {
        font-size: 0.75rem;
        padding: 0.3rem 0.5rem;
    }
}
</style>

<script>
// ===== STATUS FILTERS =====
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const status = this.dataset.status;
        const cards = document.querySelectorAll('.visit-card');
        let visibleCount = 0;
        
        cards.forEach(card => {
            if (status === 'all' || card.dataset.status === status) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });
        
        document.getElementById('visibleCount').textContent = visibleCount;
    });
});

// ===== SEARCH =====
document.getElementById('searchVisits').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const cards = document.querySelectorAll('.visit-card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            card.classList.remove('hidden');
            visibleCount++;
        } else {
            card.classList.add('hidden');
        }
    });
    
    document.getElementById('visibleCount').textContent = visibleCount;
});
</script>

<?= $this->endSection() ?>