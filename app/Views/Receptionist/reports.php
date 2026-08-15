<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Reports<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<div class="page-header">
    <div>
        <h4 class="page-title">Financial Reports</h4>
        <p class="page-subtitle">View collection reports and financial summaries</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="report-card">
            <div class="report-icon blue">
                <i class="bi bi-calendar-day"></i>
            </div>
            <div class="report-info">
                <h5>Daily Collection</h5>
                <p>Today's revenue summary</p>
                <a href="#" class="btn btn-outline-primary btn-sm">View Report</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="report-card">
            <div class="report-icon green">
                <i class="bi bi-calendar-month"></i>
            </div>
            <div class="report-info">
                <h5>Monthly Collection</h5>
                <p>Monthly revenue summary</p>
                <a href="#" class="btn btn-outline-primary btn-sm">View Report</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="report-card">
            <div class="report-icon orange">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="report-info">
                <h5>Payment Summary</h5>
                <p>All payments summary</p>
                <a href="#" class="btn btn-outline-primary btn-sm">View Report</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="report-card">
            <div class="report-icon danger">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="report-info">
                <h5>Unpaid Bills</h5>
                <p>Outstanding balances</p>
                <a href="#" class="btn btn-outline-primary btn-sm">View Report</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="report-card">
            <div class="report-icon purple">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="report-info">
                <h5>Transaction History</h5>
                <p>All transactions</p>
                <a href="#" class="btn btn-outline-primary btn-sm">View Report</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="report-card">
            <div class="report-icon teal">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <div class="report-info">
                <h5>Refund/Void Records</h5>
                <p>Authorized refunds & voids</p>
                <a href="#" class="btn btn-outline-primary btn-sm">View Report</a>
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

.report-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 2px 12px rgba(10, 43, 78, 0.06);
    border: 1px solid rgba(1, 72, 202, 0.04);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
}

.report-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(10, 43, 78, 0.1);
}

.report-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}

.report-icon.blue { background: #e6f0fa; color: #0148ca; }
.report-icon.green { background: #e8f5e9; color: #28a745; }
.report-icon.orange { background: #fff3e0; color: #ff6b00; }
.report-icon.danger { background: #fce4ec; color: #dc3545; }
.report-icon.purple { background: #f3e5f5; color: #800080; }
.report-icon.teal { background: #e0f7fa; color: #17a2b8; }

.report-info h5 {
    font-weight: 600;
    color: #0a2b4e;
    margin: 0;
    font-size: 0.95rem;
}

.report-info p {
    color: #64748b;
    font-size: 0.8rem;
    margin: 0.1rem 0 0.5rem 0;
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