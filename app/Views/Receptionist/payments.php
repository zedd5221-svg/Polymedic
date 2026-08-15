<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Payments<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<div class="page-header">
    <div>
        <h4 class="page-title">Payment Processing</h4>
        <p class="page-subtitle">Record and manage onsite payments</p>
    </div>
    <button class="btn btn-primary">
        <i class="bi bi-credit-card me-2"></i>Record Payment
    </button>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="table-card">
            <div class="table-toolbar">
                <div class="search-wrapper">
                    <i class="bi bi-search"></i>
                    <input type="text" class="form-control" placeholder="Search payments...">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table receptionist-table">
                    <thead>
                        <tr>
                            <th>Payment #</th>
                            <th>Patient</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No payments recorded</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
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

.table-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
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

.receptionist-table {
    margin: 0;
}

.receptionist-table thead th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    font-weight: 600;
    border-bottom: 2px solid #f0f4ff;
    padding: 0.75rem 0.5rem;
}

.receptionist-table tbody td {
    padding: 0.75rem 0.5rem;
    vertical-align: middle;
    font-size: 0.85rem;
    color: #0a2b4e;
    border-bottom: 1px solid #f0f4ff;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
    }
}
</style>

<?= $this->endSection() ?>