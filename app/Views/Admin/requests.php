<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Diagnostic Requests<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<div class="page-header">
    
    <div class="header-actions">
        <button class="btn btn-export">
            <i class="bi bi-download me-1"></i>Export
        </button>
        <button class="btn btn-primary-custom">
            <i class="bi bi-plus-circle me-2"></i>New Request
        </button>
    </div>
</div>

<div class="table-card">
    <!-- Search Bar -->
    <div class="search-container">
        <div class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" placeholder="Search requests..." id="searchRequests">
        </div>
    </div>

    <!-- Status Filter Buttons -->
    <div class="filter-container">
        <div class="filter-buttons" id="statusFilters">
            <button class="filter-btn active" data-status="all">All</button>
            <button class="filter-btn" data-status="pending">Pending</button>
            <button class="filter-btn" data-status="processing">Processing</button>
            <button class="filter-btn" data-status="completed">Completed</button>
            <button class="filter-btn" data-status="released">Released</button>
            <button class="filter-btn" data-status="cancelled">Cancelled</button>
        </div>
    </div>
    
    <!-- Requests Grid -->
    <div class="requests-grid" id="requestsGrid">
        <!-- Request Card 1 - Pending -->
        <div class="request-card" data-status="pending">
            <div class="request-header">
                <div class="request-id-section">
                    <span class="request-id">DR-2024-0091</span>
                    <span class="status-badge pending">Pending</span>
                    <span class="priority-badge stat">STAT</span>
                </div>
               
            </div>
            <div class="request-body">
                <div class="request-patient">
                    <strong>Maria Cristina Santos</strong>
                    <span>Dr. Ana Cruz</span>
                </div>
                <div class="request-date">
                    <i class="bi bi-calendar3"></i> Jul 15, 2026
                </div>
                <div class="request-services">
                    <span class="service-tag">CBC</span>
                    <span class="service-tag">Urinalysis</span>
                    <span class="service-tag">Blood Chemistry</span>
                </div>
            </div>
            <div class="request-footer">
                <button class="btn-approve" onclick="updateStatus(this, 'processing')"><i class="bi bi-check-circle"></i> Approve</button>
                <button class="btn-reject" onclick="updateStatus(this, 'cancelled')"><i class="bi bi-x-circle"></i> Reject</button>
                <button class="btn-print"><i class="bi bi-printer"></i> Print</button>
            </div>
        </div>

        <!-- Request Card 2 - Processing -->
        <div class="request-card" data-status="processing">
            <div class="request-header">
                <div class="request-id-section">
                    <span class="request-id">DR-2024-0090</span>
                    <span class="status-badge processing">Processing</span>
                    <span class="priority-badge routine">Routine</span>
                </div>
              
            </div>
            <div class="request-body">
                <div class="request-patient">
                    <strong>Juan Pablo Reyes</strong>
                    <span>Dr. Mark Rivera</span>
                </div>
                <div class="request-date">
                    <i class="bi bi-calendar3"></i> Jul 15, 2026
                </div>
                <div class="request-services">
                    <span class="service-tag">Lipid Profile</span>
                    <span class="service-tag">FBS</span>
                </div>
            </div>
            <div class="request-footer">
                <button class="btn-approve" onclick="updateStatus(this, 'completed')"><i class="bi bi-check-circle"></i> Complete</button>
                <button class="btn-reject" onclick="updateStatus(this, 'cancelled')"><i class="bi bi-x-circle"></i> Reject</button>
                <button class="btn-print"><i class="bi bi-printer"></i> Print</button>
            </div>
        </div>

        <!-- Request Card 3 - Completed -->
        <div class="request-card" data-status="completed">
            <div class="request-header">
                <div class="request-id-section">
                    <span class="request-id">DR-2024-0089</span>
                    <span class="status-badge completed">Completed</span>
                    <span class="priority-badge routine">Routine</span>
                </div>
             
            </div>
            <div class="request-body">
                <div class="request-patient">
                    <strong>Elena Bautista</strong>
                    <span>Dr. Jose Torres</span>
                </div>
                <div class="request-date">
                    <i class="bi bi-calendar3"></i> Jul 14, 2026
                </div>
                <div class="request-services">
                    <span class="service-tag">CBC</span>
                    <span class="service-tag">Urinalysis</span>
                </div>
            </div>
            <div class="request-footer">
                <button class="btn-approve" onclick="updateStatus(this, 'released')"><i class="bi bi-check-circle"></i> Release</button>
                <button class="btn-reject" onclick="updateStatus(this, 'cancelled')"><i class="bi bi-x-circle"></i> Reject</button>
                <button class="btn-print"><i class="bi bi-printer"></i> Print</button>
            </div>
        </div>

        <!-- Request Card 4 - Released -->
        <div class="request-card" data-status="released">
            <div class="request-header">
                <div class="request-id-section">
                    <span class="request-id">DR-2024-0088</span>
                    <span class="status-badge released">Released</span>
                    <span class="priority-badge stat">STAT</span>
                </div>
              
            </div>
            <div class="request-body">
                <div class="request-patient">
                    <strong>Roberto Dela Cruz</strong>
                    <span>Dr. Ana Cruz</span>
                </div>
                <div class="request-date">
                    <i class="bi bi-calendar3"></i> Jul 14, 2026
                </div>
                <div class="request-services">
                    <span class="service-tag">Drug Test</span>
                </div>
            </div>
            <div class="request-footer">
                <button class="btn-approve" disabled style="opacity:0.5;cursor:not-allowed;"><i class="bi bi-check-circle"></i> Released</button>
                <button class="btn-reject" onclick="updateStatus(this, 'cancelled')"><i class="bi bi-x-circle"></i> Reject</button>
                <button class="btn-print"><i class="bi bi-printer"></i> Print</button>
            </div>
        </div>

        <!-- Request Card 5 - Cancelled -->
        <div class="request-card" data-status="cancelled">
            <div class="request-header">
                <div class="request-id-section">
                    <span class="request-id">DR-2024-0087</span>
                    <span class="status-badge cancelled">Cancelled</span>
                    <span class="priority-badge routine">Routine</span>
                </div>
             
            </div>
            <div class="request-body">
                <div class="request-patient">
                    <strong>Luz Fernandez</strong>
                    <span>Dr. Mark Rivera</span>
                </div>
                <div class="request-date">
                    <i class="bi bi-calendar3"></i> Jul 13, 2026
                </div>
                <div class="request-services">
                    <span class="service-tag">X-Ray</span>
                    <span class="service-tag">ECG</span>
                </div>
            </div>
            <div class="request-footer">
                <button class="btn-approve" onclick="updateStatus(this, 'pending')"><i class="bi bi-check-circle"></i> Restore</button>
                <button class="btn-reject" disabled style="opacity:0.5;cursor:not-allowed;"><i class="bi bi-x-circle"></i> Cancelled</button>
                <button class="btn-print"><i class="bi bi-printer"></i> Print</button>
            </div>
        </div>
    </div>
    
    <!-- Table Footer -->
    <div class="table-footer">
        <span>Showing <strong id="visibleCount">5</strong> of <strong id="totalCount">5</strong> requests</span>
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

/* ===== REQUESTS GRID ===== */
.requests-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.request-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 1.25rem;
    border: 1px solid #eef2f7;
    transition: all 0.3s ease;
}

.request-card:hover {
    box-shadow: 0 4px 12px rgba(10, 43, 78, 0.08);
}

.request-card.hidden {
    display: none;
}

.request-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.request-id {
    font-weight: 700;
    color: #0a2b4e;
    font-size: 0.9rem;
    margin-right: 0.5rem;
}

.request-actions {
    display: flex;
    gap: 0.25rem;
}

.status-badge {
    padding: 0.15rem 0.6rem;
    border-radius: 30px;
    font-size: 0.6rem;
    font-weight: 600;
}

.status-badge.pending { background: #fff3e0; color: #ff6b00; }
.status-badge.processing { background: #e3f2fd; color: #0148ca; }
.status-badge.completed { background: #e8f5e9; color: #28a745; }
.status-badge.released { background: #e8f5e9; color: #04ccab; }
.status-badge.cancelled { background: #fce4ec; color: #dc3545; }

.priority-badge {
    padding: 0.15rem 0.6rem;
    border-radius: 30px;
    font-size: 0.55rem;
    font-weight: 700;
    text-transform: uppercase;
    margin-left: 0.25rem;
}

.priority-badge.stat { background: #fce4ec; color: #dc3545; }
.priority-badge.routine { background: #e3f2fd; color: #0148ca; }

.request-patient {
    display: flex;
    flex-direction: column;
}

.request-patient strong {
    font-size: 0.9rem;
    color: #0a2b4e;
}

.request-patient span {
    font-size: 0.75rem;
    color: #64748b;
}

.request-date {
    font-size: 0.8rem;
    color: #64748b;
    margin: 0.25rem 0;
}

.request-services {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
    margin-top: 0.25rem;
}

.service-tag {
    background: #f0f7ff;
    border: 1px solid #dbeafe;
    padding: 0.15rem 0.6rem;
    border-radius: 30px;
    font-size: 0.7rem;
    color: #0148ca;
    font-weight: 500;
}

.request-footer {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #eef2f7;
}

.btn-approve,
.btn-reject,
.btn-print {
    background: transparent;
    border: 1px solid #e2e8f0;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.7rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    color: #64748b;
}

.btn-approve:hover { border-color: #28a745; color: #28a745; }
.btn-reject:hover { border-color: #dc3545; color: #dc3545; }
.btn-print:hover { border-color: #0148ca; color: #0148ca; }

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
    
    .requests-grid {
        grid-template-columns: 1fr;
    }
    
    .search-wrapper {
        max-width: 100%;
    }
    
    .filter-buttons {
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
    
    .request-card {
        padding: 0.75rem;
    }
    
    .request-footer {
        flex-wrap: wrap;
    }
    
    .request-footer .btn {
        flex: 1;
        justify-content: center;
        font-size: 0.65rem;
        padding: 0.2rem 0.5rem;
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
        const cards = document.querySelectorAll('.request-card');
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
document.getElementById('searchRequests').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const cards = document.querySelectorAll('.request-card');
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

// ===== UPDATE STATUS FUNCTION =====
function updateStatus(button, newStatus) {
    const card = button.closest('.request-card');
    const statusBadge = card.querySelector('.status-badge');
    const currentStatus = card.dataset.status;
    
    if (currentStatus === newStatus) return;
    
    const actionMap = {
        'pending': 'restore',
        'processing': 'approve',
        'completed': 'complete',
        'released': 'release',
        'cancelled': 'reject'
    };
    
    const action = actionMap[newStatus] || newStatus;
    if (!confirm(`Are you sure you want to ${action} this request?`)) return;
    
    const statusLabels = {
        'pending': 'Pending',
        'processing': 'Processing',
        'completed': 'Completed',
        'released': 'Released',
        'cancelled': 'Cancelled'
    };
    
    statusBadge.textContent = statusLabels[newStatus];
    statusBadge.className = `status-badge ${newStatus}`;
    card.dataset.status = newStatus;
    
    const approveBtn = card.querySelector('.btn-approve');
    const rejectBtn = card.querySelector('.btn-reject');
    
    approveBtn.disabled = false;
    approveBtn.style.opacity = '1';
    approveBtn.style.cursor = 'pointer';
    rejectBtn.disabled = false;
    rejectBtn.style.opacity = '1';
    rejectBtn.style.cursor = 'pointer';
    
    if (newStatus === 'pending') {
        approveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Approve';
        approveBtn.onclick = function() { updateStatus(this, 'processing'); };
        rejectBtn.innerHTML = '<i class="bi bi-x-circle"></i> Reject';
        rejectBtn.onclick = function() { updateStatus(this, 'cancelled'); };
    } else if (newStatus === 'processing') {
        approveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Complete';
        approveBtn.onclick = function() { updateStatus(this, 'completed'); };
        rejectBtn.innerHTML = '<i class="bi bi-x-circle"></i> Reject';
        rejectBtn.onclick = function() { updateStatus(this, 'cancelled'); };
    } else if (newStatus === 'completed') {
        approveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Release';
        approveBtn.onclick = function() { updateStatus(this, 'released'); };
        rejectBtn.innerHTML = '<i class="bi bi-x-circle"></i> Reject';
        rejectBtn.onclick = function() { updateStatus(this, 'cancelled'); };
    } else if (newStatus === 'released') {
        approveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Released';
        approveBtn.disabled = true;
        approveBtn.style.opacity = '0.5';
        approveBtn.style.cursor = 'not-allowed';
        rejectBtn.innerHTML = '<i class="bi bi-x-circle"></i> Reject';
        rejectBtn.onclick = function() { updateStatus(this, 'cancelled'); };
    } else if (newStatus === 'cancelled') {
        approveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Restore';
        approveBtn.onclick = function() { updateStatus(this, 'pending'); };
        rejectBtn.innerHTML = '<i class="bi bi-x-circle"></i> Cancelled';
        rejectBtn.disabled = true;
        rejectBtn.style.opacity = '0.5';
        rejectBtn.style.cursor = 'not-allowed';
    }
    
    const activeFilter = document.querySelector('.filter-btn.active');
    if (activeFilter) {
        const filterStatus = activeFilter.dataset.status;
        const allCards = document.querySelectorAll('.request-card');
        let visibleCount = 0;
        
        allCards.forEach(c => {
            if (filterStatus === 'all' || c.dataset.status === filterStatus) {
                c.classList.remove('hidden');
                visibleCount++;
            } else {
                c.classList.add('hidden');
            }
        });
        
        document.getElementById('visibleCount').textContent = visibleCount;
    }
    
    const actionLabels = {
        'pending': 'Restored',
        'processing': 'Approved',
        'completed': 'Completed',
        'released': 'Released',
        'cancelled': 'Rejected'
    };
    
    button.innerHTML = `<i class="bi bi-check-circle"></i> ${actionLabels[newStatus]}`;
    button.style.borderColor = '#28a745';
    button.style.color = '#28a745';
    
    setTimeout(() => {
        if (newStatus === 'pending') {
            button.innerHTML = '<i class="bi bi-check-circle"></i> Approve';
        } else if (newStatus === 'processing') {
            button.innerHTML = '<i class="bi bi-check-circle"></i> Complete';
        } else if (newStatus === 'completed') {
            button.innerHTML = '<i class="bi bi-check-circle"></i> Release';
        } else if (newStatus === 'released') {
            button.innerHTML = '<i class="bi bi-check-circle"></i> Released';
        } else if (newStatus === 'cancelled') {
            button.innerHTML = '<i class="bi bi-check-circle"></i> Restore';
        }
        button.style.borderColor = '#e2e8f0';
        button.style.color = '#64748b';
    }, 2000);
}
</script>

<?= $this->endSection() ?>