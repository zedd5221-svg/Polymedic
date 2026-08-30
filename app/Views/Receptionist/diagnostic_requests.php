<?= $this->extend('layouts/ReceptionistLayout') ?>

<?= $this->section('pageTitle') ?>Diagnostic Requests<?= $this->endSection() ?>

<?= $this->section('receptionistContent') ?>

<div class="requests-container">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h4 class="page-title">
                Diagnostic Requests
            </h4>
            <p class="page-subtitle">Manage walk-in diagnostic requests</p>
        </div>

        <div class="header-actions">
            <button class="btn btn-export" onclick="exportRequests()">
                <i class="bi bi-download"></i>
                <span>Export</span>
            </button>

            <button class="btn btn-primary-custom"
                    data-bs-toggle="modal"
                    data-bs-target="#createRequestModal">
                <i class="bi bi-plus-circle"></i>
                <span>New Request</span>
            </button>
        </div>
    </div>

    <!-- SUCCESS / ERROR MESSAGES -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i>
            <span><?= session()->getFlashdata('success') ?></span>
            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?= session()->getFlashdata('error') ?></span>
            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('validation_errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <div>
                <ul class="mb-0">
                    <?php foreach (session()->getFlashdata('validation_errors') as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>


    <!-- MAIN REQUEST PANEL -->
    <div class="table-card">

        <!-- SEARCH + FILTER -->
        <div class="search-filter-row">

            <div class="search-wrapper">
                <i class="bi bi-search"></i>

                <input type="text"
                       class="form-control"
                       placeholder="Search by patient or request ID..."
                       id="searchRequests">
            </div>

            <div class="filter-buttons" id="statusFilters">

                <button class="filter-btn active"
                        data-status="all">
                    All
                    <span class="filter-count">
                        <?= $counts['total'] ?? 0 ?>
                    </span>
                </button>

                <button class="filter-btn"
                        data-status="pending">
                    Pending
                    <span class="filter-count">
                        <?= $counts['pending'] ?? 0 ?>
                    </span>
                </button>

                <button class="filter-btn"
                        data-status="in_progress">
                    Processing
                    <span class="filter-count">
                        <?= $counts['processing'] ?? 0 ?>
                    </span>
                </button>

                <button class="filter-btn"
                        data-status="completed">
                    Completed
                    <span class="filter-count">
                        <?= $counts['completed'] ?? 0 ?>
                    </span>
                </button>

                <button class="filter-btn"
                        data-status="released">
                    Released
                    <span class="filter-count">
                        <?= $counts['released'] ?? 0 ?>
                    </span>
                </button>

                <button class="filter-btn"
                        data-status="cancelled">
                    Cancelled
                    <span class="filter-count">
                        <?= $counts['cancelled'] ?? 0 ?>
                    </span>
                </button>

            </div>
        </div>


        <!-- REQUEST GRID -->
        <div class="requests-grid" id="requestsGrid">

            <?php if (!empty($requests) && is_array($requests)): ?>

                <?php foreach ($requests as $request):

                    $statusColor = [
                        'pending'     => 'pending',
                        'in_progress' => 'processing',
                        'completed'   => 'completed',
                        'released'   => 'released',
                        'cancelled'  => 'cancelled'
                    ][$request['status']] ?? 'pending';

                    $typeIcon = $request['request_type'] === 'Laboratory'
                        ? '🧪'
                        : '📷';

                    $typeClass = $request['request_type'] === 'Laboratory'
                        ? 'lab-type'
                        : 'xray-type';

                    $sourceClass = $request['source'] === 'Walk-in'
                        ? 'walkin'
                        : 'online';

                ?>

                    <div class="request-card"
                         data-status="<?= $request['status'] ?>"
                         data-search="<?= strtolower($request['patient_name'] . ' ' . $request['reference']) ?>">

                        <!-- CARD HEADER -->
                        <div class="request-header">

                            <div class="request-id-section">

                                <span class="request-id">
                                    <?= esc($request['reference']) ?>
                                </span>

                                <span class="type-badge <?= $typeClass ?>">
                                    <?= $typeIcon ?>
                                    <?= esc($request['request_type']) ?>
                                </span>

                                <span class="source-badge <?= $sourceClass ?>">
                                    <?= esc($request['source']) ?>
                                </span>

                                <span class="status-badge <?= $statusColor ?>">
                                    <?= ucfirst(str_replace('_', ' ', $request['status'])) ?>
                                </span>

                                <?php if ($request['priority'] === 'stat'): ?>

                                    <span class="priority-badge stat">
                                        <i class="bi bi-lightning-charge-fill"></i>
                                        STAT
                                    </span>

                                <?php endif; ?>

                            </div>

                            <div class="request-actions">

                                <button class="action-icon view-request"
                                        data-id="<?= $request['id'] ?>"
                                        data-type="<?= $request['type'] ?>"
                                        title="View Details">

                                    <i class="bi bi-eye"></i>

                                </button>

                                <button class="action-icon delete-request"
                                        data-id="<?= $request['id'] ?>"
                                        data-type="<?= $request['type'] ?>"
                                        title="Delete">

                                    <i class="bi bi-trash3"></i>

                                </button>

                            </div>

                        </div>


                        <!-- CARD BODY -->
                        <div class="request-body">

                            <div class="request-patient">

                                <strong>
                                    <?= esc($request['patient_name']) ?>
                                </strong>

                                <span>
                                    <?= esc($request['doctor_name']) ?>
                                </span>

                            </div>


                            <div class="request-meta">

                                <span class="meta-item">

                                    <i class="bi bi-calendar3"></i>

                                    <?= date(
                                        'M d, Y',
                                        strtotime($request['created_at'])
                                    ) ?>

                                </span>

                                <span class="meta-item">

                                    <i class="bi bi-person"></i>

                                    <?= $request['patient_age'] ?>
                                    yrs ·
                                    <?= $request['patient_gender'] ?>

                                </span>

                                <?php if (!empty($request['email'])): ?>
                                <span class="meta-item">
                                    <i class="bi bi-envelope"></i>
                                    <?= esc($request['email']) ?>
                                </span>
                                <?php endif; ?>

                                <?php if (!empty($request['phone'])): ?>
                                <span class="meta-item">
                                    <i class="bi bi-telephone"></i>
                                    <?= esc($request['phone']) ?>
                                </span>
                                <?php endif; ?>

                            </div>


                            <div class="request-services">

                                <?php

                                $services = $request['services'] ?? [];

                                $displayServices = array_slice(
                                    $services,
                                    0,
                                    3
                                );

                                foreach ($displayServices as $service):

                                    if (!empty(trim($service))):

                                ?>

                                    <span class="service-tag">
                                        <?= esc(trim($service)) ?>
                                    </span>

                                <?php

                                    endif;

                                endforeach;

                                if (count($services) > 3):

                                ?>

                                    <span class="service-tag more">
                                        +<?= count($services) - 3 ?>
                                        more
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- CARD FOOTER -->
                        <div class="request-footer">

                            <?php if ($request['status'] === 'pending'): ?>

                                <button class="btn-status btn-approve"
                                        data-id="<?= $request['id'] ?>"
                                        data-type="<?= $request['type'] ?>"
                                        data-status="in_progress">

                                    <i class="bi bi-play-circle-fill"></i>
                                    Start Processing

                                </button>

                                <button class="btn-status btn-reject"
                                        data-id="<?= $request['id'] ?>"
                                        data-type="<?= $request['type'] ?>"
                                        data-status="cancelled">

                                    <i class="bi bi-x-circle"></i>
                                    Cancel

                                </button>


                            <?php elseif ($request['status'] === 'in_progress'): ?>

                                <button class="btn-status btn-approve"
                                        data-id="<?= $request['id'] ?>"
                                        data-type="<?= $request['type'] ?>"
                                        data-status="completed">

                                    <i class="bi bi-check-circle-fill"></i>
                                    Complete

                                </button>

                                <button class="btn-status btn-reject"
                                        data-id="<?= $request['id'] ?>"
                                        data-type="<?= $request['type'] ?>"
                                        data-status="cancelled">

                                    <i class="bi bi-x-circle"></i>
                                    Cancel

                                </button>


                            <?php elseif ($request['status'] === 'completed'): ?>

                                <button class="btn-status btn-approve"
                                        data-id="<?= $request['id'] ?>"
                                        data-type="<?= $request['type'] ?>"
                                        data-status="released">

                                    <i class="bi bi-check-circle-fill"></i>
                                    Release

                                </button>

                                <button class="btn-status btn-reject"
                                        data-id="<?= $request['id'] ?>"
                                        data-type="<?= $request['type'] ?>"
                                        data-status="cancelled">

                                    <i class="bi bi-x-circle"></i>
                                    Cancel

                                </button>


                            <?php elseif ($request['status'] === 'released'): ?>

                                <button class="btn-status btn-released"
                                        disabled>

                                    <i class="bi bi-check-circle-fill"></i>
                                    Released

                                </button>

                                <button class="btn-status btn-print"
                                        onclick="window.location.href='<?= base_url('receptionist/print-request/' . $request['id'] . '/' . $request['type']) ?>'">

                                    <i class="bi bi-printer-fill"></i>
                                    Print

                                </button>


                            <?php elseif ($request['status'] === 'cancelled'): ?>

                                <button class="btn-status btn-restore"
                                        data-id="<?= $request['id'] ?>"
                                        data-type="<?= $request['type'] ?>"
                                        data-status="pending">

                                    <i class="bi bi-arrow-counterclockwise"></i>
                                    Restore

                                </button>

                                <button class="btn-status btn-cancelled"
                                        disabled>

                                    <i class="bi bi-x-circle-fill"></i>
                                    Cancelled

                                </button>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        <i class="bi bi-inbox"></i>
                    </div>

                    <p>No diagnostic requests found</p>

                    <small>
                        Click "New Request" to create one
                    </small>

                </div>

            <?php endif; ?>

        </div>


        <!-- TABLE FOOTER -->
        <div class="table-footer">

            <span>
                Showing
                <strong id="visibleCount">
                    <?= count($requests ?? []) ?>
                </strong>
                of
                <strong id="totalCount">
                    <?= count($requests ?? []) ?>
                </strong>
                requests
            </span>

            <div class="pagination-wrapper">

                <button class="page-btn"
                        id="prevPage">

                    <i class="bi bi-chevron-left"></i>

                </button>

                <button class="page-btn active"
                        id="currentPage">
                    1
                </button>

                <button class="page-btn"
                        id="nextPage">

                    <i class="bi bi-chevron-right"></i>

                </button>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     CREATE REQUEST MODAL
     ========================================================= -->
<div class="modal fade"
     id="createRequestModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">

        <div class="modal-content">

            <!-- MODAL HEADER -->
            <div class="modal-header">

                <div class="modal-heading">

                    <!-- IMAGE ICON -->
                    <div class="modal-icon-box">

                        <img src="/polymedic/public/assets/images/cardiology.png"
                             alt="New Diagnostic Request"
                             class="modal-img-icon">

                    </div>

                    <div>

                        <h5 class="modal-title">
                            New Diagnostic Request
                        </h5>

                        <p class="modal-subtitle">
                            Create a new laboratory or X-Ray request
                        </p>

                    </div>

                </div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>

            </div>


            <!-- CREATE FORM -->
            <form action="<?= base_url('receptionist/create-diagnostic-request') ?>"
                  method="POST"
                  id="createRequestForm">

                <div class="modal-body">


                    <!-- REQUEST TYPE -->
                    <div class="form-section">

                        <div class="section-label">

                            <i class="bi bi-ui-checks-grid"></i>

                            Request Type

                        </div>


                        <div class="request-type-group">

                            <div class="type-option <?= old('request_type', 'lab') === 'lab' ? 'active' : '' ?>"
                                 data-value="lab">

                                <input type="radio"
                                       class="btn-check"
                                       name="request_type"
                                       id="requestTypeLab"
                                       value="lab"
                                       <?= old('request_type', 'lab') === 'lab' ? 'checked' : '' ?>>

                                <label class="type-label"
                                       for="requestTypeLab">

                                    <span class="type-icon">

                                        <img src="/polymedic/public/assets/images/lab-icon.png"
                                             alt="Laboratory"
                                             class="type-img-icon">

                                    </span>

                                    <span class="type-name">
                                        Laboratory
                                    </span>

                                </label>

                            </div>


                            <div class="type-option <?= old('request_type') === 'xray' ? 'active' : '' ?>"
                                 data-value="xray">

                                <input type="radio"
                                       class="btn-check"
                                       name="request_type"
                                       id="requestTypeXray"
                                       value="xray"
                                       <?= old('request_type') === 'xray' ? 'checked' : '' ?>>

                                <label class="type-label"
                                       for="requestTypeXray">

                                    <span class="type-icon">

                                        <img src="/polymedic/public/assets/images/xray-icon.png"
                                             alt="X-Ray"
                                             class="type-img-icon">

                                    </span>

                                    <span class="type-name">
                                        X-Ray
                                    </span>

                                </label>

                            </div>

                        </div>

                    </div>


                    <!-- PATIENT INFORMATION -->
                    <div class="form-section">

                        <div class="section-label">

                            <i class="bi bi-person-vcard"></i>

                            Patient Information

                        </div>


                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label required">
                                    Patient Name
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>

                                    <input type="text"
                                           class="form-control"
                                           name="patient_name"
                                           placeholder="Enter full name"
                                           value="<?= old('patient_name') ?>"
                                           required>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <label class="form-label required">
                                    Age
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        <i class="bi bi-cake"></i>
                                    </span>

                                    <input type="number"
                                           class="form-control"
                                           name="age"
                                           placeholder="Age"
                                           min="0"
                                           value="<?= old('age') ?>"
                                           required>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <label class="form-label required">
                                    Gender
                                </label>

                                <select class="form-select"
                                        name="gender"
                                        required>

                                    <option value="">
                                        Select gender
                                    </option>

                                    <option value="Male"
                                        <?= old('gender') === 'Male' ? 'selected' : '' ?>>
                                        Male
                                    </option>

                                    <option value="Female"
                                        <?= old('gender') === 'Female' ? 'selected' : '' ?>>
                                        Female
                                    </option>

                                </select>

                            </div>


                            <!-- NEW: Email Field -->
                            <div class="col-md-6">

                                <label class="form-label">
                                    Email
                                    <span class="text-muted">(optional)</span>
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>

                                    <input type="email"
                                           class="form-control"
                                           name="email"
                                           placeholder="Enter email address"
                                           value="<?= old('email') ?>">

                                </div>

                            </div>


                            <!-- NEW: Phone Field -->
                            <div class="col-md-6">

                                <label class="form-label">
                                    Phone
                                    <span class="text-muted">(optional)</span>
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        <i class="bi bi-telephone"></i>
                                    </span>

                                    <input type="text"
                                           class="form-control"
                                           name="phone"
                                           placeholder="Enter phone number"
                                           value="<?= old('phone') ?>">

                                </div>

                            </div>


                            <div class="col-12">

                                <label class="form-label">
                                    Referring Doctor
                                    <span class="text-muted">(optional)</span>
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text">
                                        <i class="bi bi-person-badge"></i>
                                    </span>

                                    <input type="text"
                                           class="form-control"
                                           name="doctor_name"
                                           placeholder="Enter doctor's name"
                                           value="<?= old('doctor_name') ?>">

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- SERVICES -->
                    <div class="form-section">

                        <div class="services-heading">

                            <div class="section-label mb-0">

                                <i class="bi bi-list-check"></i>

                                Services

                            </div>

                            <div class="service-search-wrapper">

                                <i class="bi bi-search"></i>

                                <input type="text"
                                       class="form-control form-control-sm"
                                       id="serviceSearch"
                                       placeholder="Search services...">

                            </div>

                        </div>


                        <div class="service-actions">

                            <button type="button"
                                    class="service-action clear-all"
                                    onclick="clearAllServices()">

                                <i class="bi bi-x-circle"></i>
                                Clear All

                            </button>

                            <span class="selected-count"
                                  id="selectedCount">
                                0 selected
                            </span>

                        </div>


                        <div class="services-grid"
                             id="servicesContainer">

                            <?php

                            $allServices = array_merge(
                                $labServices ?? [],
                                $xrayServices ?? []
                            );

                            $labServiceNames = array_column(
                                $labServices ?? [],
                                'service_name'
                            );

                            $xrayServiceNames = array_column(
                                $xrayServices ?? [],
                                'service_name'
                            );

                            $oldServices = old('services') ?? [];

                            ?>


                            <?php foreach ($allServices as $service): ?>

                                <?php

                                $isLab = in_array(
                                    $service['service_name'],
                                    $labServiceNames
                                );

                                $serviceClass = $isLab
                                    ? 'lab-service'
                                    : 'xray-service';

                                $checked = in_array(
                                    $service['service_name'],
                                    $oldServices
                                )
                                    ? 'checked'
                                    : '';

                                ?>

                                <div class="service-item <?= $serviceClass ?>"
                                     data-category="<?= $isLab ? 'lab' : 'xray' ?>">

                                    <input type="checkbox"
                                           class="service-input"
                                           name="services[]"
                                           value="<?= esc($service['service_name']) ?>"
                                           id="service_<?= $service['id'] ?>"
                                           <?= $checked ?>>

                                    <label class="service-label"
                                           for="service_<?= $service['id'] ?>">

                                        <span class="service-name">
                                            <?= esc($service['service_name']) ?>
                                        </span>

                                        <span class="service-price">
                                            ₱<?= number_format($service['charge'] ?? 0, 2) ?>
                                        </span>

                                    </label>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>


                    <!-- PRIORITY -->
                    <div class="form-section">

                        <div class="section-label">

                            <i class="bi bi-exclamation-diamond"></i>

                            Priority

                        </div>


                        <div class="priority-group">


                            <!-- ROUTINE -->
                            <div class="priority-option <?= old('priority', 'routine') === 'routine' ? 'active' : '' ?>">

                                <input type="radio"
                                       class="btn-check"
                                       name="priority"
                                       id="priorityRoutine"
                                       value="routine"
                                       <?= old('priority', 'routine') === 'routine' ? 'checked' : '' ?>>

                                <label class="priority-label"
                                       for="priorityRoutine">

                                    <span class="priority-icon">

                                        <img src="/polymedic/public/assets/images/file.png"
                                             alt="Routine"
                                             class="priority-img-icon">

                                    </span>

                                    <span class="priority-text">

                                        <span class="priority-name">
                                            Routine
                                        </span>

                                        <span class="priority-desc">
                                            Standard
                                        </span>

                                    </span>

                                </label>

                            </div>


                            <!-- STAT -->
                            <div class="priority-option <?= old('priority') === 'stat' ? 'active' : '' ?>">

                                <input type="radio"
                                       class="btn-check"
                                       name="priority"
                                       id="priorityStat"
                                       value="stat"
                                       <?= old('priority') === 'stat' ? 'checked' : '' ?>>

                                <label class="priority-label"
                                       for="priorityStat">

                                    <span class="priority-icon">

                                        <img src="/polymedic/public/assets/images/alert.png"
                                             alt="STAT"
                                             class="priority-img-icon">

                                    </span>

                                    <span class="priority-text">

                                        <span class="priority-name">
                                            STAT
                                        </span>

                                        <span class="priority-desc">
                                            Urgent
                                        </span>

                                    </span>

                                </label>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- MODAL FOOTER -->
                <div class="modal-footer modal-footer-custom">

                    <div class="btn-group-left">

                        <button type="button"
                                class="btn modal-cancel-btn"
                                data-bs-dismiss="modal">

                            <i class="bi bi-x-circle"></i>
                            Cancel

                        </button>

                    </div>


                    <div class="btn-group-right">

                        <button type="submit"
                                class="btn modal-create-btn"
                                name="action"
                                value="create">

                            <i class="bi bi-plus-circle"></i>
                            Create Request

                        </button>


                        <button type="button"
                                class="btn modal-add-btn"
                                onclick="saveAndAddAnother()">

                            <i class="bi bi-plus-square"></i>
                            Save & Add Another

                        </button>


                        <button type="button"
                                class="btn modal-view-btn"
                                onclick="saveAndView()">

                            <i class="bi bi-eye"></i>
                            Save & View

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =========================================================
     VIEW REQUEST MODAL
     ========================================================= -->
<div class="modal fade"
     id="viewRequestModal"
     tabindex="-1">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="bi bi-file-earmark-text me-2"></i>

                    Request Details

                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body"
                 id="viewRequestContent">

                <div class="text-center py-4">

                    <div class="spinner-border text-primary"
                         role="status">
                    </div>

                    <p class="mt-2">
                        Loading...
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>


<style>

/* =========================================================
   PROFESSIONAL DIAGNOSTIC REQUESTS UI
   ========================================================= */

.requests-container {

    --primary: #1558d6;
    --primary-dark: #0e45ad;
    --primary-light: #eef5ff;

    --ink: #12263f;
    --muted: #66758a;
    --faint: #98a6b8;

    --line: #e3e9f0;
    --bg: #f6f8fb;
    --white: #ffffff;

    --success: #16803c;
    --success-light: #ecfdf3;

    --warning: #b66a05;
    --warning-light: #fff7e8;

    --danger: #d92d20;
    --danger-light: #fff1f0;

    --purple: #7047c8;
    --purple-light: #f4f0ff;

    --teal: #087f73;
    --teal-light: #eafaf7;

    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

    color: var(--ink);
}


/* =========================================================
   PAGE HEADER
   ========================================================= */

.page-header {

    display: flex;
    justify-content: space-between;
    align-items: center;

    gap: 20px;

    margin-bottom: 20px;
}

.page-title {

    display: flex;
    align-items: center;

    gap: 9px;

    margin: 0;

    color: var(--ink);

    font-size: 1.3rem;
    font-weight: 750;

    letter-spacing: -.02em;
}

.page-subtitle {

    margin: 5px 0 0 0;

    color: var(--muted);

    font-size: .82rem;
}

.header-actions {

    display: flex;
    align-items: center;

    gap: 8px;
}


/* =========================================================
   HEADER BUTTONS
   ========================================================= */

.btn-export,
.btn-primary-custom {

    height: 40px !important;

    padding: 0 15px !important;

    display: inline-flex !important;

    align-items: center !important;
    justify-content: center !important;

    gap: 6px;

    border-radius: 8px !important;

    font-size: .78rem !important;
    font-weight: 650 !important;

    white-space: nowrap;

    transition: all .18s ease !important;
}

.btn-export {

    background: #ffffff !important;

    border: 1px solid #cbd5e1 !important;

    color: #344054 !important;

    box-shadow:
        0 1px 2px rgba(16,24,40,.04) !important;
}

.btn-export:hover {

    background: #f8fafc !important;

    border-color: #94a3b8 !important;

    color: var(--primary) !important;
}

.btn-primary-custom {

    background: var(--primary) !important;

    border: 1px solid var(--primary) !important;

    color: #ffffff !important;

    -webkit-text-fill-color: #ffffff !important;

    box-shadow:
        0 2px 5px rgba(21,88,214,.20) !important;
}

.btn-primary-custom:hover {

    background: var(--primary-dark) !important;

    border-color: var(--primary-dark) !important;

    color: #ffffff !important;

    -webkit-text-fill-color: #ffffff !important;

    box-shadow:
        0 4px 10px rgba(21,88,214,.25) !important;
}


/* =========================================================
   ALERTS
   ========================================================= */

.requests-container .alert {

    display: flex;

    align-items: flex-start;

    gap: 10px;

    border-radius: 9px;

    font-size: .8rem;

    padding: 11px 14px;

    border-width: 1px;
}


/* =========================================================
   MAIN PANEL
   ========================================================= */

.table-card {

    background: #ffffff;

    border: 1px solid var(--line);

    border-radius: 12px;

    padding: 18px;

    box-shadow:
        0 2px 8px rgba(16,38,63,.035);
}


/* =========================================================
   SEARCH + FILTER
   ========================================================= */

.search-filter-row {

    display: grid;

    grid-template-columns: 310px minmax(0,1fr);

    align-items: center;

    gap: 14px;

    margin-bottom: 18px;
}

.search-wrapper {

    width: 100%;
    height: 40px;

    display: flex;

    align-items: center;

    gap: 8px;

    padding: 0 12px;

    background: #ffffff;

    border: 1px solid #dce3ec;

    border-radius: 8px;

    box-sizing: border-box;

    transition: .18s ease;
}

.search-wrapper:focus-within {

    border-color: var(--primary);

    box-shadow:
        0 0 0 3px rgba(21,88,214,.08);
}

.search-wrapper i {

    color: #91a0b2;

    font-size: .82rem;
}

.search-wrapper .form-control {

    height: 36px;

    border: 0 !important;

    padding: 0 !important;

    background: transparent;

    box-shadow: none !important;

    font-size: .78rem;
}


/* =========================================================
   FILTER BUTTONS
   ========================================================= */

.filter-buttons {

    display: flex;

    justify-content: flex-end;

    align-items: center;

    gap: 6px;

    flex-wrap: wrap;
}

.filter-btn {

    height: 34px !important;

    padding: 0 11px !important;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 5px;

    border: 1px solid #d5dde8 !important;

    border-radius: 7px !important;

    background: #ffffff !important;

    color: #53657a !important;

    font-size: .68rem !important;

    font-weight: 650 !important;

    cursor: pointer;

    transition: all .18s ease !important;
}

.filter-count {

    font-size: .61rem;

    font-weight: 700;
}

.filter-btn:hover {

    background: #f1f6ff !important;

    border-color: #9db9e8 !important;

    color: var(--primary) !important;
}

.filter-btn.active {

    background: #1558d6 !important;

    border-color: #1558d6 !important;

    color: #ffffff !important;

    -webkit-text-fill-color: #ffffff !important;

    font-weight: 750 !important;

    box-shadow:
        0 3px 8px rgba(21,88,214,.30) !important;
}

.filter-btn.active .filter-count {

    color: #ffffff !important;

    -webkit-text-fill-color: #ffffff !important;
}


/* =========================================================
   REQUEST GRID
   ========================================================= */

.requests-grid {

    display: grid;

    grid-template-columns:
        repeat(2,minmax(0,1fr));

    gap: 12px;

    margin-bottom: 4px;
}


/* =========================================================
   REQUEST CARD
   ========================================================= */

.request-card {

    background: #ffffff;

    border: 1px solid var(--line);

    border-radius: 10px;

    padding: 15px;

    transition: all .18s ease;

    min-width: 0;
}

.request-card:hover {

    border-color: #cbd7e6;

    box-shadow:
        0 5px 16px rgba(16,38,63,.07);

    transform: translateY(-1px);
}

.request-card.hidden {

    display: none !important;
}


/* =========================================================
   CARD HEADER
   ========================================================= */

.request-header {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 10px;

    margin-bottom: 12px;
}

.request-id-section {

    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 5px;

    min-width: 0;

    flex: 1;
}

.request-id {

    font-size: .78rem;

    font-weight: 750;

    color: var(--ink);

    margin-right: 2px;
}


/* =========================================================
   BADGES
   ========================================================= */

.type-badge,
.source-badge,
.status-badge,
.priority-badge {

    display: inline-flex;

    align-items: center;

    min-height: 20px;

    padding: 2px 7px;

    border-radius: 20px;

    font-size: .59rem;

    font-weight: 650;

    white-space: nowrap;
}

.type-badge.lab-type {

    background: var(--primary-light);

    color: var(--primary);
}

.type-badge.xray-type {

    background: var(--purple-light);

    color: var(--purple);
}

.source-badge.walkin {

    background: var(--warning-light);

    color: var(--warning);
}

.source-badge.online {

    background: var(--success-light);

    color: var(--success);
}

.status-badge.pending {

    background: var(--warning-light);

    color: var(--warning);
}

.status-badge.processing {

    background: var(--primary-light);

    color: var(--primary);
}

.status-badge.completed {

    background: var(--success-light);

    color: var(--success);
}

.status-badge.released {

    background: var(--teal-light);

    color: var(--teal);
}

.status-badge.cancelled {

    background: var(--danger-light);

    color: var(--danger);
}

.priority-badge.stat {

    background: var(--danger-light);

    color: var(--danger);
}


/* =========================================================
   CARD ACTIONS
   ========================================================= */

.request-actions {

    display: flex;

    align-items: center;

    gap: 3px;

    flex: 0 0 auto;
}

.action-icon {

    width: 30px;
    height: 30px;

    padding: 0 !important;

    border: 1px solid transparent !important;

    border-radius: 7px !important;

    background: transparent !important;

    color: #7d8da1 !important;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    cursor: pointer;

    transition: .18s ease;
}

.action-icon:hover {

    background: #f1f6ff !important;

    border-color: #dbe7fb !important;

    color: var(--primary) !important;
}

.action-icon.delete-request:hover {

    background: #fff2f2 !important;

    border-color: #f7d5d3 !important;

    color: var(--danger) !important;
}


/* =========================================================
   CARD BODY
   ========================================================= */

.request-body {

    padding-bottom: 2px;
}

.request-patient {

    display: flex;

    flex-direction: column;

    gap: 2px;
}

.request-patient strong {

    font-size: .91rem;

    font-weight: 700;

    color: var(--ink);
}

.request-patient span {

    font-size: .72rem;

    color: var(--muted);
}

.request-meta {

    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 12px;

    margin: 9px 0;

    font-size: .68rem;

    color: var(--muted);
}

.meta-item {

    display: inline-flex;

    align-items: center;

    gap: 5px;
}

.meta-item i {

    color: #8c9bad;
}


/* =========================================================
   SERVICES
   ========================================================= */

.request-services {

    display: flex;

    gap: 5px;

    flex-wrap: wrap;

    margin-top: 5px;
}

.service-tag {

    background: #f5f8fc;

    border: 1px solid #e2eaf4;

    padding: 3px 7px;

    border-radius: 5px;

    font-size: .61rem;

    color: #49627f;

    font-weight: 550;
}

.service-tag.more {

    background: #f8fafc;

    color: #8795a6;

    border-color: #e6ebf1;
}


/* =========================================================
   CARD FOOTER
   ========================================================= */

.request-footer {

    display: flex;

    align-items: center;

    gap: 6px;

    flex-wrap: wrap;

    margin-top: 13px;

    padding-top: 11px;

    border-top: 1px solid #edf1f5;
}

.btn-status {

    height: 32px !important;

    min-height: 32px !important;

    padding: 0 11px !important;

    display: inline-flex !important;

    align-items: center !important;

    justify-content: center !important;

    gap: 5px;

    border-radius: 7px !important;

    font-size: .66rem !important;

    font-weight: 650 !important;

    cursor: pointer;

    transition: all .18s ease !important;
}

.btn-approve {

    background: #ffffff !important;

    border: 1px solid #79c997 !important;

    color: #16803c !important;
}

.btn-approve:hover:not(:disabled) {

    background: #16803c !important;

    border-color: #16803c !important;

    color: #ffffff !important;
}

.btn-reject {

    background: #ffffff !important;

    border: 1px solid #e99b95 !important;

    color: #c62828 !important;
}

.btn-reject:hover:not(:disabled) {

    background: #d92d20 !important;

    border-color: #d92d20 !important;

    color: #ffffff !important;
}

.btn-released {

    background: var(--teal-light) !important;

    border: 1px solid #75c9bf !important;

    color: var(--teal) !important;
}

.btn-restore {

    background: #ffffff !important;

    border: 1px solid #91afe2 !important;

    color: var(--primary) !important;
}

.btn-restore:hover:not(:disabled) {

    background: var(--primary) !important;

    border-color: var(--primary) !important;

    color: #ffffff !important;
}

.btn-print {

    background: #ffffff !important;

    border: 1px solid #b8a4e3 !important;

    color: var(--purple) !important;
}

.btn-print:hover:not(:disabled) {

    background: var(--purple) !important;

    border-color: var(--purple) !important;

    color: #ffffff !important;
}

.btn-cancelled {

    background: var(--danger-light) !important;

    border: 1px solid #efb3ae !important;

    color: var(--danger) !important;
}

.btn-status:disabled {

    opacity: .70 !important;

    cursor: not-allowed !important;
}


/* =========================================================
   PAGINATION
   ========================================================= */

.table-footer {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 12px;

    margin-top: 15px;

    padding-top: 13px;

    border-top: 1px solid #edf1f5;
}

.table-footer span {

    font-size: .72rem;

    color: var(--muted);
}

.pagination-wrapper {

    display: flex;

    gap: 4px;
}

.page-btn {

    width: 30px;
    height: 30px;

    border: 1px solid #dce3ec;

    border-radius: 7px;

    background: #ffffff;

    color: #6d7c8e;

    display: flex;

    align-items: center;

    justify-content: center;

    cursor: pointer;

    font-size: .75rem;

    transition: .18s ease;
}

.page-btn:hover {

    border-color: #b8ccee;

    color: var(--primary);

    background: #f8fbff;
}

.page-btn.active {

    background: var(--primary);

    border-color: var(--primary);

    color: #ffffff;
}


/* =========================================================
   EMPTY STATE
   ========================================================= */

.empty-state {

    grid-column: 1 / -1;

    text-align: center;

    padding: 55px 20px;

    color: var(--muted);
}

.empty-icon {

    width: 58px;
    height: 58px;

    margin: 0 auto 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: #f1f5f9;

    color: #94a3b8;

    font-size: 1.5rem;
}

.empty-state p {

    margin: 0 0 4px;

    font-size: .85rem;

    font-weight: 650;
}

.empty-state small {

    font-size: .72rem;

    color: #94a3b8;
}


/* =========================================================
   CREATE REQUEST MODAL
   ========================================================= */

#createRequestModal .modal-dialog {

    width: min(900px, calc(100% - 28px));

    max-width: 900px;
}

#createRequestModal .modal-content {

    border: 1px solid #dfe6ef;

    border-radius: 14px;

    overflow: hidden;

    box-shadow:
        0 22px 60px rgba(16,38,63,.18);

    background: #f8fafc;
}

#createRequestModal .modal-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16px;

    padding: 16px 20px;

    border-bottom: 1px solid #e8edf3;

    background: #ffffff;
}

.modal-heading {

    display: flex;

    align-items: center;

    gap: 10px;
}


/* =========================================================
   MODAL TOP IMAGE ICON
   ========================================================= */

.modal-icon-box {

    width: 38px;
    height: 38px;

    flex: 0 0 38px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 9px;

    background: #ffffff;

    border: 1px solid #dbe7fb;

    overflow: hidden;
}

.modal-img-icon {

    width: 24px;
    height: 24px;

    object-fit: contain;

    display: block;
}


#createRequestModal .modal-title {

    margin: 0;

    color: var(--ink);

    font-size: 1rem;

    font-weight: 750;
}

.modal-subtitle {

    margin: 3px 0 0;

    color: var(--muted);

    font-size: .72rem;
}


/* =========================================================
   MODAL BODY
   ========================================================= */

#createRequestModal form {

    display: flex;

    flex-direction: column;

    min-height: 0;
}

#createRequestModal .modal-body {

    padding: 16px 20px;

    max-height: calc(100vh - 190px);

    overflow-y: auto;

    background: #f7f9fc;
}

#createRequestModal .modal-body::-webkit-scrollbar {

    width: 5px;
}

#createRequestModal .modal-body::-webkit-scrollbar-thumb {

    background: #cbd5e1;

    border-radius: 10px;
}


/* =========================================================
   FORM SECTIONS
   ========================================================= */

.form-section {

    margin-bottom: 12px;

    padding: 15px;

    background: #ffffff;

    border: 1px solid #e3e9f0;

    border-radius: 10px;
}

.form-section:last-child {

    margin-bottom: 0;
}

.section-label {

    display: flex;

    align-items: center;

    gap: 7px;

    margin-bottom: 11px;

    color: var(--ink);

    font-size: .72rem;

    font-weight: 750;

    text-transform: uppercase;

    letter-spacing: .045em;
}

.section-label i {

    color: var(--primary);

    font-size: .82rem;
}


/* =========================================================
   REQUEST TYPE
   ========================================================= */

.request-type-group {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 10px;
}

.type-option {

    min-width: 0;
}

.type-option .type-label {

    width: 100%;

    box-sizing: border-box;

    min-height: 52px;

    margin: 0;

    padding: 9px 13px;

    border: 1.5px solid #dce4ed;

    border-radius: 8px;

    background: #ffffff;

    color: #63748a;

    display: flex;

    align-items: center;

    gap: 10px;

    cursor: pointer;

    transition: all .18s ease;
}

.type-option .type-label:hover {

    border-color: #9db9e8;

    background: #f8fbff;
}

.type-option.active .type-label {

    background: #1558d6 !important;

    border: 2px solid #1558d6 !important;

    color: #ffffff !important;

    box-shadow:
        0 4px 12px rgba(21,88,214,.25);
}

.type-option.active .type-name {

    color: #ffffff !important;
}


/* =========================================================
   TYPE IMAGE
   ========================================================= */

.type-icon {

    width: 32px;
    height: 32px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 7px;

    background: #ffffff;

    border: 1px solid #e2e8f0;

    box-shadow:
        0 1px 2px rgba(0,0,0,.04);
}

.type-option.active .type-icon {

    background: #ffffff;

    border-color: #ffffff;
}

.type-img-icon {

    width: 24px;
    height: 24px;

    object-fit: contain;
}

.type-name {

    font-size: .8rem;

    font-weight: 700;
}


/* =========================================================
   FORM INPUTS
   ========================================================= */

.form-label {

    margin-bottom: 5px;

    color: #30465f;

    font-size: .69rem;

    font-weight: 650;
}

.form-label.required::after {

    content: " *";

    color: var(--danger);
}

.form-control,
.form-select {

    height: 39px;

    border: 1px solid #dce4ed;

    border-radius: 7px;

    padding: 0 11px;

    background: #ffffff;

    color: #263b52;

    font-size: .76rem;

    box-shadow: none;
}

.form-control:focus,
.form-select:focus {

    border-color: var(--primary);

    box-shadow:
        0 0 0 3px rgba(21,88,214,.08);
}

.input-group-text {

    height: 39px;

    background: #ffffff;

    border: 1px solid #dce4ed;

    border-right: 0;

    border-radius: 7px 0 0 7px;

    color: #8ea0b4;

    font-size: .78rem;
}

.input-group .form-control {

    border-left: 0;

    border-radius: 0 7px 7px 0;
}


/* =========================================================
   SERVICES
   ========================================================= */

.services-heading {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    margin-bottom: 9px;
}

.service-search-wrapper {

    position: relative;

    width: 230px;

    flex: 0 0 230px;
}

.service-search-wrapper i {

    position: absolute;

    left: 10px;

    top: 50%;

    transform: translateY(-50%);

    z-index: 2;

    color: #94a3b8;

    font-size: .72rem;
}

.service-search-wrapper .form-control-sm {

    height: 34px;

    padding-left: 29px !important;

    font-size: .7rem;
}

.service-actions {

    display: flex;

    align-items: center;

    gap: 6px;

    margin-bottom: 8px;
}

.service-action {

    height: 30px;

    padding: 0 10px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 5px;

    border-radius: 6px;

    font-size: .65rem;

    font-weight: 650;

    cursor: pointer;

    transition: .18s ease;
}

.select-all {

    background: #ffffff;

    border: 1px solid #9db9e8;

    color: var(--primary);
}

.select-all:hover {

    background: var(--primary);

    border-color: var(--primary);

    color: #ffffff;
}

.clear-all {

    background: #ffffff;

    border: 1px solid #cbd5e1;

    color: #64748b;
}

.clear-all:hover {

    background: #f1f5f9;

    border-color: #94a3b8;

    color: #334155;
}

.selected-count {

    margin-left: auto;

    color: #718096;

    font-size: .68rem;

    font-weight: 600;
}


/* =========================================================
   SERVICES GRID
   ========================================================= */

.services-grid {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 2px 7px;

    max-height: 250px;

    overflow-y: auto;

    padding: 6px;

    border: 1px solid #e5ebf2;

    border-radius: 8px;

    background: #fbfcfe;
}

.services-grid::-webkit-scrollbar {

    width: 5px;
}

.services-grid::-webkit-scrollbar-thumb {

    background: #cbd5e1;

    border-radius: 8px;
}

.service-item {

    display: flex;

    align-items: center;

    min-width: 0;

    padding: 6px 7px;

    border: 1px solid transparent;

    border-radius: 6px;

    transition: .15s ease;
}

.service-item:hover {

    background: #f5f8fc;

    border-color: #e3eaf3;
}

.service-item .service-input {

    width: 14px;
    height: 14px;

    flex: 0 0 14px;

    margin: 0 8px 0 0;

    accent-color: var(--primary);

    cursor: pointer;
}

.service-label {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 7px;

    min-width: 0;

    flex: 1;

    margin: 0;

    cursor: pointer;

    color: #29415d;

    font-size: .72rem;
}

.service-name {

    min-width: 0;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.service-price {

    flex: 0 0 auto;

    color: #718198;

    font-size: .64rem;

    font-weight: 550;
}


/* =========================================================
   PRIORITY
   ========================================================= */

.priority-group {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 10px;
}

.priority-label {

    width: 100%;

    min-height: 52px;

    box-sizing: border-box;

    margin: 0;

    padding: 9px 13px;

    border: 1.5px solid #dce4ed;

    border-radius: 8px;

    background: #ffffff;

    color: #63748a;

    display: flex;

    align-items: center;

    gap: 10px;

    cursor: pointer;

    transition: all .18s ease;
}

.priority-label:hover {

    border-color: #9db9e8;

    background: #f8fbff;
}

.priority-option.active .priority-label {

    background: #1558d6 !important;

    border: 2px solid #1558d6 !important;

    color: #ffffff !important;

    box-shadow:
        0 4px 12px rgba(21,88,214,.25);
}

.priority-option.active .priority-name {

    color: #ffffff;
}

.priority-option.active .priority-desc {

    color: rgba(255,255,255,.85);
}

.priority-option.active .priority-icon {

    background: #ffffff;

    border-color: #ffffff;
}


/* =========================================================
   PRIORITY IMAGE
   ========================================================= */

.priority-icon {

    width: 32px;
    height: 32px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 7px;

    background: #ffffff;

    border: 1px solid #e2e8f0;

    box-shadow:
        0 1px 2px rgba(0,0,0,.04);
}

.priority-img-icon {

    width: 20px;
    height: 20px;

    object-fit: contain;
}

.priority-text {

    display: flex;

    flex-direction: column;

    gap: 1px;
}

.priority-name {

    font-size: .8rem;

    font-weight: 700;
}

.priority-desc {

    font-size: .65rem;

    color: #8492a4;
}


/* =========================================================
   MODAL FOOTER
   ========================================================= */

#createRequestModal .modal-footer-custom {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    padding: 11px 20px;

    background: #ffffff !important;

    border-top: 1px solid #e5ebf2;
}

.modal-footer-custom .btn-group-left,
.modal-footer-custom .btn-group-right {

    display: flex;

    align-items: center;

    gap: 7px;
}

.modal-footer-custom .btn {

    height: 38px !important;

    min-height: 38px !important;

    padding: 0 13px !important;

    display: inline-flex !important;

    align-items: center !important;

    justify-content: center !important;

    gap: 6px;

    border-radius: 7px !important;

    font-size: .71rem !important;

    font-weight: 700 !important;

    white-space: nowrap;

    opacity: 1 !important;

    visibility: visible !important;

    transition: all .18s ease !important;
}

.modal-cancel-btn {

    background: #ffffff !important;

    border: 1.5px solid #cbd5e1 !important;

    color: #475467 !important;

    -webkit-text-fill-color: #475467 !important;
}

.modal-cancel-btn:hover {

    background: #f1f5f9 !important;

    border-color: #94a3b8 !important;

    color: #1f2937 !important;
}

.modal-create-btn {

    background: #1558d6 !important;

    border: 1.5px solid #1558d6 !important;

    color: #ffffff !important;

    -webkit-text-fill-color: #ffffff !important;

    box-shadow:
        0 2px 5px rgba(21,88,214,.22) !important;
}

.modal-create-btn:hover {

    background: #0e45ad !important;

    border-color: #0e45ad !important;

    color: #ffffff !important;

    -webkit-text-fill-color: #ffffff !important;
}

.modal-add-btn {

    background: #ffffff !important;

    border: 1.5px solid #1558d6 !important;

    color: #1558d6 !important;

    -webkit-text-fill-color: #1558d6 !important;
}

.modal-add-btn:hover {

    background: #1558d6 !important;

    border-color: #1558d6 !important;

    color: #ffffff !important;

    -webkit-text-fill-color: #ffffff !important;
}

.modal-view-btn {

    background: #ffffff !important;

    border: 1.5px solid #16803c !important;

    color: #16803c !important;

    -webkit-text-fill-color: #16803c !important;
}

.modal-view-btn:hover {

    background: #16803c !important;

    border-color: #16803c !important;

    color: #ffffff !important;

    -webkit-text-fill-color: #ffffff !important;
}


/* =========================================================
   VIEW MODAL
   ========================================================= */

#viewRequestModal .modal-content {

    border: 1px solid #dfe6ef;

    border-radius: 12px;

    overflow: hidden;

    box-shadow:
        0 20px 55px rgba(16,38,63,.16);
}

#viewRequestModal .modal-header {

    padding: 15px 18px;

    border-bottom: 1px solid #e8edf3;

    background: #ffffff;
}

#viewRequestModal .modal-body {

    padding: 18px;

    background: #f8fafc;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width:1100px) {

    .search-filter-row {

        grid-template-columns:
            280px 1fr;
    }

    .filter-buttons {

        justify-content: flex-start;
    }

    .requests-grid {

        grid-template-columns: 1fr;
    }
}


@media (max-width:768px) {

    .page-header {

        align-items: stretch;

        flex-direction: column;

        gap: 12px;
    }

    .header-actions {

        width: 100%;
    }

    .header-actions .btn {

        flex: 1;
    }

    .page-subtitle {

        margin-left: 0;
    }

    .search-filter-row {

        grid-template-columns: 1fr;
    }

    .search-wrapper {

        width: 100%;
    }

    .filter-buttons {

        justify-content: flex-start;
    }

    .table-card {

        padding: 13px;
    }

    .request-card {

        padding: 13px;
    }

    #createRequestModal .modal-dialog {

        width: calc(100% - 14px);

        margin: 7px auto;
    }

    #createRequestModal .modal-header {

        padding: 13px 15px;
    }

    #createRequestModal .modal-body {

        padding: 12px 13px;

        max-height: calc(100vh - 160px);
    }

    .form-section {

        padding: 12px;
    }

    .services-heading {

        align-items: flex-start;

        flex-direction: column;
    }

    .service-search-wrapper {

        width: 100%;

        flex: 1;
    }

    #createRequestModal .modal-footer-custom {

        padding: 9px 13px;
    }
}


@media (max-width:600px) {

    .request-type-group,
    .priority-group {

        grid-template-columns: 1fr 1fr;
    }

    .services-grid {

        grid-template-columns: 1fr;
    }

    .request-footer .btn-status {

        flex: 1 1 auto;
    }

    .modal-footer-custom {

        flex-wrap: wrap;
    }

    .modal-footer-custom .btn-group-left {

        width: 100%;
    }

    .modal-footer-custom .btn-group-right {

        width: 100%;

        display: grid;

        grid-template-columns: 1fr 1fr;
    }

    .modal-footer-custom .btn-group-right .modal-create-btn {

        grid-column: 1 / -1;
    }

    .modal-footer-custom .btn-group-right .modal-add-btn {

        grid-column: 1;
    }

    .modal-footer-custom .btn-group-right .modal-view-btn {

        grid-column: 2;
    }

    .modal-footer-custom .btn-group-left .btn {

        width: 100%;
    }
}


@media (max-width:420px) {

    .page-title {

        font-size: 1.12rem;
    }

    .header-actions .btn {

        font-size: .7rem !important;

        padding: 0 9px !important;
    }

    .filter-btn {

        font-size: .61rem !important;

        padding: 0 8px !important;
    }

    .request-meta {

        gap: 7px;
    }

    .request-footer .btn-status {

        font-size: .61rem !important;

        padding: 0 7px !important;
    }

    .request-type-group,
    .priority-group {

        grid-template-columns: 1fr;
    }

    .modal-footer-custom .btn-group-right {

        grid-template-columns: 1fr;
    }

    .modal-footer-custom .btn-group-right .modal-add-btn,
    .modal-footer-custom .btn-group-right .modal-view-btn {

        grid-column: 1;
    }
}

</style>


<script>

/* =========================================================
   SAVE & ADD ANOTHER
   ========================================================= */

function saveAndAddAnother() {

    const form =
        document.getElementById('createRequestForm');

    form.setAttribute(
        'action',
        '<?= base_url('receptionist/create-diagnostic-request') ?>?add_another=1'
    );

    form.submit();
}


/* =========================================================
   SAVE & VIEW
   ========================================================= */

function saveAndView() {

    const form =
        document.getElementById('createRequestForm');

    form.setAttribute(
        'action',
        '<?= base_url('receptionist/create-diagnostic-request') ?>?view=1'
    );

    form.submit();
}


/* =========================================================
   DOM READY
   ========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const modal =
            document.getElementById('createRequestModal');

        if (modal) {

            modal.addEventListener(
                'show.bs.modal',
                function () {

                    updateServiceVisibility();

                    updateSelectedCount();

                }
            );
        }

    }
);


/* =========================================================
   FILTER BUTTONS
   ========================================================= */

document
    .querySelectorAll('.filter-btn')
    .forEach(function (btn) {

        btn.addEventListener(
            'click',
            function () {

                document
                    .querySelectorAll('.filter-btn')
                    .forEach(function (b) {

                        b.classList.remove('active');

                    });

                this.classList.add('active');

                const status =
                    this.dataset.status;

                const cards =
                    document.querySelectorAll('.request-card');

                let visibleCount = 0;

                cards.forEach(function (card) {

                    if (
                        status === 'all' ||
                        card.dataset.status === status
                    ) {

                        card.classList.remove('hidden');

                        visibleCount++;

                    } else {

                        card.classList.add('hidden');

                    }

                });

                document.getElementById(
                    'visibleCount'
                ).textContent = visibleCount;

            }
        );

    });


/* =========================================================
   SEARCH
   ========================================================= */

const searchInput =
    document.getElementById('searchRequests');

if (searchInput) {

    searchInput.addEventListener(
        'input',
        function () {

            const searchTerm =
                this.value.toLowerCase().trim();

            const cards =
                document.querySelectorAll('.request-card');

            let visibleCount = 0;

            cards.forEach(function (card) {

                const searchData =
                    card.dataset.search || '';

                if (searchData.includes(searchTerm)) {

                    card.classList.remove('hidden');

                    visibleCount++;

                } else {

                    card.classList.add('hidden');

                }

            });

            document.getElementById(
                'visibleCount'
            ).textContent = visibleCount;

        }
    );
}


/* =========================================================
   REQUEST TYPE SELECTION
   ========================================================= */

document
    .querySelectorAll('.type-option')
    .forEach(function (option) {

        option.addEventListener(
            'click',
            function () {

                const radio =
                    this.querySelector(
                        'input[type="radio"]'
                    );

                if (radio) {

                    radio.checked = true;

                    document
                        .querySelectorAll('.type-option')
                        .forEach(function (o) {

                            o.classList.remove('active');

                        });

                    this.classList.add('active');

                    updateServiceVisibility();

                    updateSelectedCount();

                }

            }
        );

    });


/* =========================================================
   PRIORITY SELECTION
   ========================================================= */

document
    .querySelectorAll('.priority-option')
    .forEach(function (option) {

        option.addEventListener(
            'click',
            function () {

                const radio =
                    this.querySelector(
                        'input[type="radio"]'
                    );

                if (radio) {

                    radio.checked = true;

                    document
                        .querySelectorAll('.priority-option')
                        .forEach(function (o) {

                            o.classList.remove('active');

                        });

                    this.classList.add('active');

                }

            }
        );

    });


/* =========================================================
   SERVICE SELECTION
   ========================================================= */

document
    .querySelectorAll('.service-input')
    .forEach(function (input) {

        input.addEventListener(
            'change',
            function () {

                updateSelectedCount();

            }
        );

    });


/* =========================================================
   SERVICE SEARCH
   ========================================================= */

const serviceSearch =
    document.getElementById('serviceSearch');

if (serviceSearch) {

    serviceSearch.addEventListener(
        'input',
        function () {

            const searchTerm =
                this.value.toLowerCase().trim();

            document
                .querySelectorAll('.service-item')
                .forEach(function (item) {

                    const text =
                        item.textContent.toLowerCase();

                    const category =
                        item.dataset.category;

                    const selectedType =
                        document.querySelector(
                            'input[name="request_type"]:checked'
                        );

                    const type =
                        selectedType
                            ? selectedType.value
                            : 'lab';

                    const matchesSearch =
                        text.includes(searchTerm);

                    const matchesType =
                        category === type;

                    item.style.display =
                        matchesSearch &&
                        matchesType
                            ? 'flex'
                            : 'none';

                });

        }
    );
}


/* =========================================================
   SELECT ALL SERVICES
   ========================================================= */

window.selectAllServices = function () {

    document
        .querySelectorAll(
            '.service-item[style*="display: flex"] .service-input'
        )
        .forEach(function (input) {

            input.checked = true;

        });

    updateSelectedCount();
};


/* =========================================================
   CLEAR ALL SERVICES
   ========================================================= */

window.clearAllServices = function () {

    document
        .querySelectorAll('.service-input')
        .forEach(function (input) {

            input.checked = false;

        });

    updateSelectedCount();
};


/* =========================================================
   UPDATE SELECTED COUNT
   ========================================================= */

function updateSelectedCount() {

    const selected =
        document.querySelectorAll(
            '.service-input:checked'
        ).length;

    const counter =
        document.getElementById('selectedCount');

    if (counter) {

        counter.textContent =
            selected + ' selected';

    }
}


/* =========================================================
   UPDATE SERVICE VISIBILITY
   ========================================================= */

function updateServiceVisibility() {

    const labRadio =
        document.getElementById('requestTypeLab');

    if (!labRadio) return;

    const isLab =
        labRadio.checked;

    document
        .querySelectorAll('.service-item')
        .forEach(function (el) {

            const category =
                el.dataset.category;

            if (
                isLab &&
                category === 'lab'
            ) {

                el.style.display = 'flex';

            } else if (
                !isLab &&
                category === 'xray'
            ) {

                el.style.display = 'flex';

            } else {

                el.style.display = 'none';

            }

        });
}


/* =========================================================
   INITIAL STATE
   ========================================================= */

updateServiceVisibility();

updateSelectedCount();


/* =========================================================
   STATUS UPDATE
   ========================================================= */

document
    .querySelectorAll('.btn-status[data-id]')
    .forEach(function (btn) {

        btn.addEventListener(
            'click',
            function () {

                const id =
                    this.dataset.id;

                const type =
                    this.dataset.type;

                const status =
                    this.dataset.status;

                if (
                    !confirm(
                        `Are you sure you want to change status to ${status}?`
                    )
                ) {
                    return;
                }

                const originalHtml =
                    this.innerHTML;

                this.innerHTML =
                    '<span class="spinner-border spinner-border-sm"></span>';

                this.disabled = true;

                fetch(
                    `/polymedic/public/receptionist/update-diagnostic-status/${id}/${type}/${status}`,
                    {
                        method: 'POST',

                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest'
                        }
                    }
                )

                .then(response =>
                    response.json()
                )

                .then(data => {

                    if (data.success) {

                        location.reload();

                    } else {

                        alert(
                            'Error: ' +
                            data.message
                        );

                        this.innerHTML =
                            originalHtml;

                        this.disabled =
                            false;
                    }

                })

                .catch(error => {

                    console.error(error);

                    alert(
                        'Error updating status'
                    );

                    this.innerHTML =
                        originalHtml;

                    this.disabled =
                        false;

                });

            }
        );

    });


/* =========================================================
   VIEW REQUEST DETAILS
   ========================================================= */

document
    .querySelectorAll('.view-request')
    .forEach(function (btn) {

        btn.addEventListener(
            'click',
            function () {

                const id =
                    this.dataset.id;

                const type =
                    this.dataset.type;

                const modal =
                    new bootstrap.Modal(
                        document.getElementById(
                            'viewRequestModal'
                        )
                    );

                const content =
                    document.getElementById(
                        'viewRequestContent'
                    );

                content.innerHTML = `
                    <div class="text-center py-4">

                        <div class="spinner-border text-primary"
                             role="status">
                        </div>

                        <p class="mt-2">
                            Loading...
                        </p>

                    </div>
                `;

                modal.show();

                fetch(
                    `/polymedic/public/receptionist/get-request-details/${id}/${type}`,
                    {
                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest'
                        }
                    }
                )

                .then(response =>
                    response.json()
                )

                .then(data => {

                    if (data.success) {

                        const d =
                            data.data;

                        content.innerHTML = `

                            <div class="request-details">

                                <div class="detail-row">

                                    <span class="detail-label">
                                        Patient Name
                                    </span>

                                    <span class="detail-value">

                                        <strong>
                                            ${escapeHtml(d.patient_name)}
                                        </strong>

                                    </span>

                                </div>


                                <div class="detail-row">

                                    <span class="detail-label">
                                        Age / Gender
                                    </span>

                                    <span class="detail-value">
                                        ${d.age} yrs · ${d.gender}
                                    </span>

                                </div>


                                <div class="detail-row">

                                    <span class="detail-label">
                                        Doctor
                                    </span>

                                    <span class="detail-value">
                                        ${escapeHtml(d.doctor_name)}
                                    </span>

                                </div>


                                <div class="detail-row">

                                    <span class="detail-label">
                                        Status
                                    </span>

                                    <span class="detail-value">

                                        <span class="status-badge ${d.status}">
                                            ${d.status}
                                        </span>

                                    </span>

                                </div>


                                <div class="detail-row">

                                    <span class="detail-label">
                                        Services
                                    </span>

                                    <span class="detail-value">
                                        ${escapeHtml(d.services)}
                                    </span>

                                </div>


                                ${
                                    d.findings
                                    ? `
                                        <div class="detail-row">

                                            <span class="detail-label">
                                                Findings
                                            </span>

                                            <span class="detail-value">
                                                ${escapeHtml(d.findings)}
                                            </span>

                                        </div>
                                    `
                                    : ''
                                }


                                ${
                                    d.remarks
                                    ? `
                                        <div class="detail-row">

                                            <span class="detail-label">
                                                Remarks
                                            </span>

                                            <span class="detail-value">
                                                ${escapeHtml(d.remarks)}
                                            </span>

                                        </div>
                                    `
                                    : ''
                                }


                                <div class="detail-row">

                                    <span class="detail-label">
                                        Created
                                    </span>

                                    <span class="detail-value">
                                        ${new Date(
                                            d.created_at
                                        ).toLocaleString()}
                                    </span>

                                </div>


                                ${
                                    d.released_at
                                    ? `
                                        <div class="detail-row">

                                            <span class="detail-label">
                                                Released
                                            </span>

                                            <span class="detail-value">
                                                ${new Date(
                                                    d.released_at
                                                ).toLocaleString()}
                                            </span>

                                        </div>
                                    `
                                    : ''
                                }

                            </div>

                        `;

                    } else {

                        content.innerHTML = `
                            <div class="alert alert-danger">
                                ${data.message}
                            </div>
                        `;

                    }

                })

                .catch(error => {

                    console.error(error);

                    content.innerHTML = `
                        <div class="alert alert-danger">
                            Error loading details
                        </div>
                    `;

                });

            }
        );

    });


/* =========================================================
   DELETE REQUEST
   ========================================================= */

document
    .querySelectorAll('.delete-request')
    .forEach(function (btn) {

        btn.addEventListener(
            'click',
            function () {

                const id =
                    this.dataset.id;

                const type =
                    this.dataset.type;

                if (
                    !confirm(
                        'Are you sure you want to delete this request? This action cannot be undone.'
                    )
                ) {

                    return;

                }

                const card =
                    this.closest('.request-card');

                card.style.opacity = '.5';

                fetch(
                    `/polymedic/public/receptionist/delete-diagnostic-request/${id}/${type}`,
                    {
                        method: 'DELETE',

                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest'
                        }
                    }
                )

                .then(response =>
                    response.json()
                )

                .then(data => {

                    if (data.success) {

                        card.remove();

                        const totalCount =
                            document.getElementById(
                                'totalCount'
                            );

                        totalCount.textContent =
                            Math.max(
                                0,
                                parseInt(
                                    totalCount.textContent
                                ) - 1
                            );

                        updateVisibleCount();

                    } else {

                        alert(
                            'Error: ' +
                            data.message
                        );

                        card.style.opacity =
                            '1';

                    }

                })

                .catch(error => {

                    console.error(error);

                    alert(
                        'Error deleting request'
                    );

                    card.style.opacity =
                        '1';

                });

            }
        );

    });


/* =========================================================
   EXPORT
   ========================================================= */

window.exportRequests = function () {

    alert(
        'Export functionality coming soon!'
    );

};


/* =========================================================
   UPDATE VISIBLE COUNT
   ========================================================= */

function updateVisibleCount() {

    const visible =
        document.querySelectorAll(
            '.request-card:not(.hidden)'
        ).length;

    const counter =
        document.getElementById(
            'visibleCount'
        );

    if (counter) {

        counter.textContent =
            visible;

    }
}


/* =========================================================
   ESCAPE HTML
   ========================================================= */

function escapeHtml(text) {

    if (!text) return '';

    const div =
        document.createElement('div');

    div.textContent =
        text;

    return div.innerHTML;
}


/* =========================================================
   MODAL RESET
   ========================================================= */

const createModal =
    document.getElementById(
        'createRequestModal'
    );

if (createModal) {

    createModal.addEventListener(
        'hidden.bs.modal',
        function () {

            document
                .querySelectorAll('.is-invalid')
                .forEach(function (el) {

                    el.classList.remove(
                        'is-invalid'
                    );

                });

        }
    );

}

</script>

<?= $this->endSection() ?>