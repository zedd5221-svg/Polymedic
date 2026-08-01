<?= $this->extend('layouts/AppointmentLayout') ?>

<?= $this->section('AppointmentContent') ?>

<style>
/* ===== MEDICAL THEME STYLES ===== */
:root {
    --primary-blue: #0a2b4e;
    --primary-light: #1a4a7a;
    --accent-teal: #04ccab;
    --accent-blue: #0148ca;
    --bg-light: #f0f7ff;
    --card-shadow: 0 10px 40px rgba(10, 43, 78, 0.08);
    --radius-lg: 24px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.success-page {
    background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 50%, #f5faff 100%);
    min-height: 100vh;
    padding: 3rem 0 4rem;
}

/* ===== CONFETTI ANIMATION ===== */
.confetti-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 9999;
    overflow: hidden;
}

.confetti {
    position: absolute;
    width: 10px;
    height: 10px;
    opacity: 0;
    animation: confettiFall 3s ease-in forwards;
}

@keyframes confettiFall {
    0% {
        opacity: 1;
        transform: translateY(-20px) rotate(0deg);
    }
    100% {
        opacity: 0;
        transform: translateY(100vh) rotate(720deg);
    }
}

/* ===== SUCCESS CARD ===== */
.success-card {
    background: #ffffff;
    border-radius: var(--radius-lg);
    box-shadow: var(--card-shadow);
    border: 1px solid rgba(1, 72, 202, 0.06);
    overflow: hidden;
    max-width: 700px;
    margin: 0 auto;
    transition: var(--transition);
}

.success-card:hover {
    box-shadow: 0 20px 60px rgba(10, 43, 78, 0.12);
}

.card-header-success {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-light) 100%);
    padding: 2rem 2.5rem;
    border-bottom: none;
    text-align: center;
}

.card-header-success .success-icon-wrapper {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--accent-teal), #03b898);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: -4rem auto 1rem;
    box-shadow: 0 8px 30px rgba(4, 204, 171, 0.35);
}

.card-header-success .success-icon-wrapper i {
    font-size: 2.5rem;
    color: white;
}

.card-header-success h2 {
    color: white;
    font-weight: 700;
    font-size: 1.8rem;
    margin: 0.5rem 0 0.25rem;
}

.card-header-success p {
    color: rgba(255,255,255,0.8);
    margin: 0;
    font-size: 1rem;
}

.card-body-success {
    padding: 2.5rem;
}

/* ===== REFERENCE NUMBER ===== */
.ref-number {
    background: linear-gradient(135deg, #f0f7ff, #fafcff);
    border-radius: 16px;
    padding: 1.25rem;
    text-align: center;
    border: 2px dashed #dbeafe;
    margin-bottom: 1.5rem;
}

.ref-number .label {
    color: #64748b;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.ref-number .number {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--accent-blue);
    letter-spacing: 1px;
    font-family: 'Courier New', monospace;
}

.ref-number .copy-btn {
    background: transparent;
    border: none;
    color: var(--accent-blue);
    cursor: pointer;
    font-size: 0.9rem;
    padding: 0.25rem 0.75rem;
    border-radius: 8px;
    transition: var(--transition);
}

.ref-number .copy-btn:hover {
    background: #dbeafe;
}

/* ===== DETAILS GRID ===== */
.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.detail-item {
    background: #f8faff;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    border: 1px solid #eef2f7;
}

.detail-item .label {
    font-size: 0.7rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
}

.detail-item .value {
    font-weight: 700;
    color: var(--primary-blue);
    font-size: 0.95rem;
    margin-top: 0.15rem;
}

/* ===== PAYMENT STATUS BADGE ===== */
.payment-badge {
    display: inline-block;
    background: linear-gradient(135deg, var(--accent-teal), #03b898);
    color: white;
    padding: 0.3rem 1.2rem;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.5px;
}

/* ===== SERVICES LIST ===== */
.services-list {
    background: #f8faff;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    border: 1px solid #eef2f7;
    margin-bottom: 1.5rem;
}

.services-list .label {
    font-size: 0.7rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 0.5rem;
}

.services-list .tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.services-list .tag {
    background: #ffffff;
    border: 1px solid #dbeafe;
    padding: 0.25rem 0.75rem;
    border-radius: 30px;
    font-size: 0.8rem;
    color: var(--primary-blue);
    font-weight: 500;
}

/* ===== BUTTONS ===== */
.btn-primary-custom {
    background: linear-gradient(135deg, var(--accent-blue), #0037a0);
    border: none;
    color: white;
    padding: 0.75rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(1, 72, 202, 0.3);
    color: white;
}

.btn-outline-custom {
    background: transparent;
    border: 2px solid #e2e8f0;
    color: #64748b;
    padding: 0.75rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-outline-custom:hover {
    border-color: var(--primary-blue);
    color: var(--primary-blue);
    background: #f8faff;
}

.btn-group-success {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 1.5rem;
}

/* ===== PRINT STYLES ===== */
@media print {
    .navbar,
    .btn-group-success,
    .footer {
        display: none !important;
    }
    .success-card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .card-header-success {
        padding: 1.5rem;
    }
    
    .card-body-success {
        padding: 1.5rem;
    }
    
    .details-grid {
        grid-template-columns: 1fr;
    }
    
    .ref-number .number {
        font-size: 1.3rem;
    }
    
    .btn-group-success {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-group-success .btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .card-header-success .success-icon-wrapper {
        width: 60px;
        height: 60px;
    }
    
    .card-header-success .success-icon-wrapper i {
        font-size: 1.8rem;
    }
    
    .card-header-success h2 {
        font-size: 1.3rem;
    }
}
</style>

<!-- ===== CONFETTI ANIMATION ===== -->
<div class="confetti-container" id="confettiContainer"></div>

<!-- ===== SUCCESS PAGE ===== -->
<section class="success-page">
    <div class="container">
        
        <!-- ===== SUCCESS CARD ===== -->
        <div class="success-card">
            <div class="card-header-success">
                <div class="success-icon-wrapper">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h2>Appointment Confirmed!</h2>
                <p>Your appointment has been successfully booked</p>
            </div>
            
            <div class="card-body-success">
                
                <!-- Reference Number -->
                <div class="ref-number">
                    <span class="label">Reference Number</span>
                    <div>
                        <span class="number" id="refNumber"><?= $reference ?? 'CP-2026-000-000' ?></span>
                        <button class="copy-btn" onclick="copyReference()" title="Copy Reference Number">
                            <i class="bi bi-copy"></i> Copy
                        </button>
                    </div>
                </div>
                
                <!-- Appointment Details -->
                <?php if (isset($appointment)): ?>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="label"><i class="bi bi-calendar3 me-1"></i> Date</span>
                        <span class="value"><?= $appointment['appointment_date'] ?? 'N/A' ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label"><i class="bi bi-clock me-1"></i> Time</span>
                        <span class="value"><?= $appointment['appointment_time'] ?? 'N/A' ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label"><i class="bi bi-person me-1"></i> Patient</span>
                        <span class="value"><?= $appointment['full_name'] ?? 'N/A' ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label"><i class="bi bi-gender-ambiguous me-1"></i> Age / Gender</span>
                        <span class="value"><?= ($appointment['age'] ?? 'N/A') . ' yrs, ' . ($appointment['gender'] ?? 'N/A') ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label"><i class="bi bi-envelope me-1"></i> Email</span>
                        <span class="value" style="font-size: 0.85rem;"><?= $appointment['email'] ?? 'N/A' ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label"><i class="bi bi-phone me-1"></i> Phone</span>
                        <span class="value"><?= $appointment['phone'] ?? 'N/A' ?></span>
                    </div>
                    <div class="detail-item" style="grid-column: 1 / -1; text-align: center; background: linear-gradient(135deg, #f0fdf4, #f0f7ff);">
                        <span class="label"><i class="bi bi-credit-card me-1"></i> Payment Status</span>
                        <span class="value">
                            <span class="payment-badge">✅ Payment Confirmed</span>
                        </span>
                    </div>
                </div>
                
                <!-- Services -->
                <?php 
                $services = [];
                if (isset($appointment['lab_services']) && is_array($appointment['lab_services'])) {
                    $services = array_merge($services, $appointment['lab_services']);
                }
                if (isset($appointment['xray_services']) && is_array($appointment['xray_services'])) {
                    $services = array_merge($services, $appointment['xray_services']);
                }
                ?>
                
                <?php if (!empty($services)): ?>
                <div class="services-list">
                    <span class="label"><i class="bi bi-clipboard2-pulse me-1"></i> Selected Services</span>
                    <div class="tags">
                        <?php foreach ($services as $service): ?>
                            <span class="tag"><?= esc($service) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Other Requests -->
                <?php if (isset($appointment['other_requests']) && !empty($appointment['other_requests'])): ?>
                <div class="services-list" style="background: #fff8f0; border-color: #fde8d0;">
                    <span class="label"><i class="bi bi-clipboard me-1"></i> Other Requests</span>
                    <p class="mb-0 text-muted" style="font-size: 0.95rem;"><?= esc($appointment['other_requests']) ?></p>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="alert alert-warning rounded-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    No appointment data found. Please contact us for assistance.
                </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="btn-group-success">
                    <a href="/polymedic/public/" class="btn btn-outline-custom btn-lg px-4">
                        <i class="bi bi-house me-2"></i>Back Home
                    </a>
                    <a href="/polymedic/public/index.php/appointment/book" class="btn btn-primary-custom btn-lg px-5">
                        <i class="bi bi-plus-circle me-2"></i>New Appointment
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-lg px-4" style="border-color: #dbeafe; color: var(--primary-blue);">
                        <i class="bi bi-printer me-2"></i>Print
                    </button>
                </div>
                
                <!-- Help Text -->
                <div class="text-center mt-4">
                    <p class="text-muted small">
                        <i class="bi bi-envelope me-1"></i> A confirmation email has been sent to your email address.
                        <br>
                        For urgent concerns, please call <strong>(064) 123-4567</strong>
                    </p>
                </div>
            </div>
        </div>
        
    </div>
</section>

<footer class="py-2 bg-dark text-white footer">
    <div class="container px-5">
        <p class="text-center mb-0">&copy; 2026 PolyMedic. All rights reserved.</p>
    </div>
</footer>

<script>
// ===== CONFETTI ANIMATION =====
document.addEventListener('DOMContentLoaded', function() {
    const colors = ['#04ccab', '#0148ca', '#ff6b6b', '#ffd93d', '#6c5ce7', '#00b894', '#fdcb6e', '#e17055'];
    const container = document.getElementById('confettiContainer');
    
    for (let i = 0; i < 50; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.width = (Math.random() * 8 + 4) + 'px';
        confetti.style.height = (Math.random() * 8 + 4) + 'px';
        confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
        confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
        confetti.style.animationDelay = (Math.random() * 2) + 's';
        container.appendChild(confetti);
    }
    
    // Remove confetti after animation
    setTimeout(function() {
        container.style.display = 'none';
    }, 5000);
});

// ===== COPY REFERENCE NUMBER =====
function copyReference() {
    const refNumber = document.getElementById('refNumber').textContent;
    navigator.clipboard.writeText(refNumber).then(function() {
        const btn = document.querySelector('.copy-btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
        setTimeout(function() {
            btn.innerHTML = originalText;
        }, 2000);
    });
}
</script>

<?= $this->endSection() ?>