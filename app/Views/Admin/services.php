<?= $this->extend('layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Service Management<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<?php
/* ------------------------------------------------------------------
   View-local helpers only. No controller or query logic is changed.
   ------------------------------------------------------------------ */
$serviceList = (isset($services) && is_array($services)) ? $services : [];

$total       = (int) ($total ?? count($serviceList));
$labCount    = (int) ($lab_count ?? 0);
$xrayCount   = (int) ($xray_count ?? 0);

$currentPage = (int) ($currentPage ?? 1);
$totalPages  = (int) ($totalPages ?? 1);
$startRow    = (int) ($startRow ?? 0);
$endRow      = (int) ($endRow ?? 0);

/* Which category filter is active, if the controller has been
   wired to accept one. Falls back to 'all' so the view keeps
   working while the controller is still unfiltered. */
$activeCategory = strtolower(trim((string) ($active_category ?? 'all')));
if (!in_array($activeCategory, ['all', 'laboratory', 'xray'], true)) {
    $activeCategory = 'all';
}

/* Category display metadata. */
$categoryMeta = [
    'laboratory' => ['label' => 'Laboratory', 'tone' => 'lab',  'icon' => 'flask'],
    'xray'       => ['label' => 'X-Ray',      'tone' => 'xray', 'icon' => 'xray'],
];

/* PNG icon filenames for the list rows, inside public/assets/images/.
   Change either of these strings to point at your own files. */
$categoryIcons = [
    'laboratory' => 'flask (2).png',
    'xray'       => 'bones (1).png',
];

/* Same PNGs, used by the stat cards. They can be the same files as
   above; they are declared separately so you can swap one set
   without touching the other. */
$statIcons = [
    'lab'   => 'flask (2).png',
    'xray'  => 'bones (1).png',
    'total' => 'paper.png',
];

/* Normalise whatever the database holds. Handles 'xray', 'x-ray',
   'X-Ray', and similar. Anything that is not laboratory or xray
   falls through to laboratory so no row is orphaned. */
$normaliseCategory = static function ($value): string {
    $v = strtolower(trim((string) $value));
    $v = str_replace([' ', '-', '_'], '', $v);

    if ($v === 'xray' || $v === 'xrays') { return 'xray'; }

    return 'laboratory';
};

$tabs = [
    'all'        => ['label' => 'All',        'count' => $total],
    'laboratory' => ['label' => 'Laboratory', 'count' => $labCount],
    'xray'       => ['label' => 'X-Ray',      'count' => $xrayCount],
];

/* Build a tab URL. */
$tabUrl = static function (string $key): string {
    $base = 'admin/services';
    $qs   = $key === 'all' ? '' : ('?category=' . rawurlencode($key));
    return base_url($base . $qs);
};
?>

<div class="sv" id="svRoot">

    <!-- ===== PAGE HEADER ===== -->
    <header class="sv-head">
        <div>
            <h2 class="sv-title">Service management</h2>
        </div>

        <div class="sv-head-actions">
            <button type="button" class="sv-btn" id="svExport">
                <i class="bi bi-download" aria-hidden="true"></i>
                Export CSV
            </button>
            <button type="button"
                    class="sv-btn sv-btn--primary"
                    data-bs-toggle="modal"
                    data-bs-target="#addServiceModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                Add service
            </button>
        </div>
    </header>


    <!-- ===== STATS ===== -->
    <div class="sv-stats">

        <div class="sv-stat sv-stat--lab">
            <div class="sv-stat-icon">
                <img src="<?= esc(base_url('assets/images/' . $statIcons['lab']), 'attr') ?>"
                     alt=""
                     class="sv-stat-img"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="sv-stat-body">
                <div class="sv-stat-value"><?= number_format($labCount) ?></div>
                <div class="sv-stat-label">Laboratory</div>
                <div class="sv-stat-sub">Blood, urine, chemistry tests</div>
            </div>
        </div>

        <div class="sv-stat sv-stat--xray">
            <div class="sv-stat-icon">
                <img src="<?= esc(base_url('assets/images/' . $statIcons['xray']), 'attr') ?>"
                     alt=""
                     class="sv-stat-img"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="sv-stat-body">
                <div class="sv-stat-value"><?= number_format($xrayCount) ?></div>
                <div class="sv-stat-label">X-Ray &amp; imaging</div>
                <div class="sv-stat-sub">Radiography and imaging</div>
            </div>
        </div>

        <div class="sv-stat sv-stat--total">
            <div class="sv-stat-icon">
                <img src="<?= esc(base_url('assets/images/' . $statIcons['total']), 'attr') ?>"
                     alt=""
                     class="sv-stat-img"
                     loading="lazy"
                     decoding="async">
            </div>
            <div class="sv-stat-body">
                <div class="sv-stat-value"><?= number_format($total) ?></div>
                <div class="sv-stat-label">Total services</div>
                <div class="sv-stat-sub">All categories</div>
            </div>
        </div>

    </div>


    <!-- ===== PANEL ===== -->
    <section class="sv-panel">

        <nav class="sv-tabs" role="tablist" aria-label="Filter by category">
            <?php foreach ($tabs as $key => $tab): ?>
                <a class="sv-tab<?= $activeCategory === $key ? ' is-active' : '' ?>"
                   href="<?= esc($tabUrl($key), 'attr') ?>"
                   role="tab"
                   aria-selected="<?= $activeCategory === $key ? 'true' : 'false' ?>">
                    <?= esc($tab['label']) ?>
                    <span class="sv-tab-count"><?= (int) $tab['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sv-filters">
            <div class="sv-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label class="visually-hidden" for="searchServices">Search services</label>
                <input type="search"
                       id="searchServices"
                       class="sv-input"
                       placeholder="Search by name or code"
                       autocomplete="off">
                <button type="button" class="sv-search-clear" id="svSearchClear" hidden aria-label="Clear search">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <?php if ($total > 0): ?>

            <ul class="sv-list" id="svList" role="list">
                <?php foreach ($serviceList as $service):
                    $category = $normaliseCategory($service['category'] ?? '');
                    $meta     = $categoryMeta[$category] ?? $categoryMeta['laboratory'];
                    $png      = $categoryIcons[$category] ?? $categoryIcons['laboratory'];

                    $code     = (string) ($service['service_code'] ?? '');
                    $name     = (string) ($service['service_name'] ?? '');
                    $desc     = trim((string) ($service['description'] ?? ''));
                    $charge   = (float)  ($service['charge'] ?? 0);
                    $isActive = !empty($service['is_active']);
                    $created  = !empty($service['created_at']) ? strtotime($service['created_at']) : false;

                    $haystack = strtolower($code . ' ' . $name . ' ' . $desc);
                ?>
                    <li class="sv-row sv-row--<?= esc($meta['tone'], 'attr') ?><?= $isActive ? '' : ' is-inactive' ?>"
                        data-category="<?= esc($category, 'attr') ?>"
                        data-id="<?= esc((string) ($service['id'] ?? ''), 'attr') ?>"
                        data-code="<?= esc($code, 'attr') ?>"
                        data-name="<?= esc($name, 'attr') ?>"
                        data-description="<?= esc($desc, 'attr') ?>"
                        data-charge="<?= esc((string) $charge, 'attr') ?>"
                        data-active="<?= $isActive ? '1' : '0' ?>"
                        data-search="<?= esc($haystack, 'attr') ?>">

                        <!-- Category icon rail -->
                        <div class="sv-row-icon">
                            <span class="sv-cat-icon sv-cat-icon--<?= esc($meta['tone'], 'attr') ?>" aria-hidden="true">
                                <img src="<?= esc(base_url('assets/images/' . $png), 'attr') ?>"
                                     alt=""
                                     class="sv-cat-img"
                                     loading="lazy"
                                     decoding="async">
                            </span>
                        </div>

                        <!-- Identity -->
                        <div class="sv-row-identity">
                            <div class="sv-row-name-line">
                                <span class="sv-name"><?= esc($name) ?></span>
                                <span class="sv-code" title="Service code"><?= esc($code) ?></span>
                            </div>

                            <p class="sv-row-desc">
                                <?php if ($desc !== ''): ?>
                                    <?= esc($desc) ?>
                                <?php else: ?>
                                    <span class="sv-muted">No description provided.</span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <!-- Category -->
                        <div class="sv-row-category">
                            <span class="sv-cat-pill sv-cat-pill--<?= esc($meta['tone'], 'attr') ?>">
                                <?= esc($meta['label']) ?>
                            </span>
                        </div>

                        <!-- Status -->
                        <div class="sv-row-status">
                            <span class="sv-status sv-status--<?= $isActive ? 'active' : 'inactive' ?>">
                                <?= $isActive ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>

                        <!-- Price -->
                        <div class="sv-row-price">
                            <span class="sv-price">&#8369;<?= number_format($charge, 2) ?></span>
                        </div>

                        <!-- Created -->
                        <div class="sv-row-created">
                            <?php if ($created): ?>
                                <time datetime="<?= esc(date('c', $created), 'attr') ?>"
                                      title="Added <?= esc(date('M j, Y', $created), 'attr') ?>">
                                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                                    <?= esc(date('M j, Y', $created)) ?>
                                </time>
                            <?php else: ?>
                                <span class="sv-muted">&mdash;</span>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <div class="sv-row-actions">
                            <button type="button"
                                    class="sv-row-btn edit-service"
                                    title="Edit"
                                    aria-label="Edit <?= esc($name, 'attr') ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editServiceModal">
                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                <span>Edit</span>
                            </button>

                            <a class="sv-row-btn"
                               href="<?= base_url('admin/services/toggle/' . (int) ($service['id'] ?? 0)) ?>"
                               title="<?= $isActive ? 'Deactivate' : 'Activate' ?>"
                               aria-label="<?= $isActive ? 'Deactivate' : 'Activate' ?> <?= esc($name, 'attr') ?>">
                                <i class="bi bi-<?= $isActive ? 'toggle-on' : 'toggle-off' ?>" aria-hidden="true"></i>
                                <span><?= $isActive ? 'Deactivate' : 'Activate' ?></span>
                            </a>

                            <a class="sv-row-btn sv-row-btn--danger"
                               href="<?= base_url('admin/services/delete/' . (int) ($service['id'] ?? 0)) ?>"
                               title="Delete"
                               aria-label="Delete <?= esc($name, 'attr') ?>"
                               onclick="return confirm('Delete <?= esc(addslashes($name), 'attr') ?>? This cannot be undone.')">
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                                <span>Delete</span>
                            </a>
                        </div>

                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Shown only when the search matches nothing on this page -->
            <div class="sv-empty sv-empty--filter" id="svNoMatch" hidden>
                <div class="sv-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></div>
                <h3>No matching services on this page</h3>
                <p>Search only filters the services already loaded. Clear the search to see the full page, or use the tabs to change the category.</p>
                <button type="button" class="sv-btn" id="svClearFilters">Clear search</button>
            </div>

        <?php else: ?>

            <div class="sv-empty">
                <div class="sv-empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></div>
                <h3>No services yet</h3>
                <p>Add the first laboratory or x-ray service so it can be requested.</p>
                <button type="button"
                        class="sv-btn sv-btn--primary"
                        data-bs-toggle="modal"
                        data-bs-target="#addServiceModal">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    Add service
                </button>
            </div>

        <?php endif; ?>

        <!-- ===== PAGINATION ===== -->
        <?php if ($total > 0 && $totalPages > 1): ?>
            <footer class="sv-foot">
                <span class="sv-foot-text">
                    Showing <strong><?= number_format($startRow) ?></strong>
                    to <strong><?= number_format($endRow) ?></strong>
                    of <strong><?= number_format($total) ?></strong> service<?= $total === 1 ? '' : 's' ?>
                </span>

                <nav class="sv-pager" aria-label="Pagination">
                    <?php if ($currentPage > 1): ?>
                        <a class="sv-page"
                           href="<?= base_url('admin/services?page=' . ($currentPage - 1)) ?>"
                           aria-label="Previous page">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        </a>
                    <?php else: ?>
                        <span class="sv-page is-disabled" aria-disabled="true">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        </span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i === $currentPage): ?>
                            <span class="sv-page is-active" aria-current="page"><?= $i ?></span>

                        <?php elseif ($i <= 2 || $i > $totalPages - 2 || abs($i - $currentPage) <= 1): ?>
                            <a class="sv-page" href="<?= base_url('admin/services?page=' . $i) ?>"><?= $i ?></a>

                        <?php elseif ($i === 3 && $currentPage > 4): ?>
                            <span class="sv-page sv-page--gap" aria-hidden="true">…</span>

                        <?php elseif ($i === $totalPages - 2 && $currentPage < $totalPages - 3): ?>
                            <span class="sv-page sv-page--gap" aria-hidden="true">…</span>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a class="sv-page"
                           href="<?= base_url('admin/services?page=' . ($currentPage + 1)) ?>"
                           aria-label="Next page">
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    <?php else: ?>
                        <span class="sv-page is-disabled" aria-disabled="true">
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </span>
                    <?php endif; ?>
                </nav>
            </footer>
        <?php endif; ?>

    </section>

</div>


<!-- =========================================================
     ADD SERVICE MODAL
     ========================================================= -->
<div class="modal fade admin-modal sv-modal"
     id="addServiceModal"
     tabindex="-1"
     aria-labelledby="addServiceLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">

            <header class="modal-header sv-modal-head">
                <div class="sv-modal-heading">
                    <span class="sv-modal-icon" aria-hidden="true">
                        <i class="bi bi-plus-lg"></i>
                    </span>
                    <div>
                        <h5 class="modal-title" id="addServiceLabel">Add new service</h5>
                        <p class="sv-modal-sub">Create a laboratory or x-ray service.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </header>

            <form action="<?= base_url('admin/services/create') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="modal-body sv-modal-body">

                    <section class="sv-section">
                        <h6 class="sv-section-title">Identification</h6>

                        <div class="sv-grid-2">
                            <div class="sv-field">
                                <label class="form-label" for="add_service_code">
                                    Service code <span class="req" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="add_service_code"
                                       name="service_code"
                                       placeholder="e.g. LAB-CBC"
                                       required>
                                <small class="field-hint">Short, unique. Uppercase is enforced.</small>
                            </div>

                            <div class="sv-field">
                                <label class="form-label" for="add_service_category">
                                    Category <span class="req" aria-hidden="true">*</span>
                                </label>
                                <select class="form-select" id="add_service_category" name="category" required>
                                    <option value="">Select a category</option>
                                    <option value="laboratory">Laboratory</option>
                                    <option value="xray">X-Ray</option>
                                </select>
                            </div>

                            <div class="sv-field sv-field--full">
                                <label class="form-label" for="add_service_name">
                                    Service name <span class="req" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="add_service_name"
                                       name="service_name"
                                       placeholder="e.g. Complete Blood Count (CBC)"
                                       required>
                            </div>

                            <div class="sv-field sv-field--full">
                                <label class="form-label" for="add_service_description">Description</label>
                                <textarea class="form-control"
                                          id="add_service_description"
                                          name="description"
                                          rows="2"
                                          placeholder="Optional. What the service covers, preparation, turnaround."></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="sv-section">
                        <h6 class="sv-section-title">Pricing &amp; availability</h6>

                        <div class="sv-grid-2">
                            <div class="sv-field">
                                <label class="form-label" for="add_service_charge">Price (₱)</label>
                                <input type="number"
                                       class="form-control"
                                       id="add_service_charge"
                                       name="charge"
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00">
                                <small class="field-hint">Leave blank or zero for a free service.</small>
                            </div>

                            <div class="sv-field">
                                <label class="form-label" for="add_service_status">Status</label>
                                <select class="form-select" id="add_service_status" name="is_active">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                                <small class="field-hint">Inactive services cannot be requested.</small>
                            </div>
                        </div>
                    </section>

                </div>

                <footer class="modal-footer sv-modal-foot">
                    <span class="sv-modal-foot-note">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        The code cannot be changed later from this form.
                    </span>
                    <div class="sv-modal-foot-actions">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2" aria-hidden="true"></i>
                            Create service
                        </button>
                    </div>
                </footer>
            </form>

        </div>
    </div>
</div>


<!-- =========================================================
     EDIT SERVICE MODAL
     ========================================================= -->
<div class="modal fade admin-modal sv-modal"
     id="editServiceModal"
     tabindex="-1"
     aria-labelledby="editServiceLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">

            <header class="modal-header sv-modal-head">
                <div class="sv-modal-heading">
                    <span class="sv-modal-icon sv-modal-icon--edit" aria-hidden="true">
                        <i class="bi bi-pencil"></i>
                    </span>
                    <div>
                        <h5 class="modal-title" id="editServiceLabel">Edit service</h5>
                        <p class="sv-modal-sub">Update the name, category, price, or availability.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </header>

            <form id="editServiceForm" action="" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="service_id" id="edit_service_id">

                <div class="modal-body sv-modal-body">

                    <section class="sv-section">
                        <h6 class="sv-section-title">Identification</h6>

                        <div class="sv-grid-2">
                            <div class="sv-field">
                                <label class="form-label" for="edit_service_code">
                                    Service code <span class="req" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="edit_service_code"
                                       name="service_code"
                                       required>
                            </div>

                            <div class="sv-field">
                                <label class="form-label" for="edit_service_category">
                                    Category <span class="req" aria-hidden="true">*</span>
                                </label>
                                <select class="form-select" id="edit_service_category" name="category" required>
                                    <option value="laboratory">Laboratory</option>
                                    <option value="xray">X-Ray</option>
                                </select>
                            </div>

                            <div class="sv-field sv-field--full">
                                <label class="form-label" for="edit_service_name">
                                    Service name <span class="req" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="edit_service_name"
                                       name="service_name"
                                       required>
                            </div>

                            <div class="sv-field sv-field--full">
                                <label class="form-label" for="edit_service_description">Description</label>
                                <textarea class="form-control"
                                          id="edit_service_description"
                                          name="description"
                                          rows="2"></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="sv-section">
                        <h6 class="sv-section-title">Pricing &amp; availability</h6>

                        <div class="sv-grid-2">
                            <div class="sv-field">
                                <label class="form-label" for="edit_service_charge">Price (₱)</label>
                                <input type="number"
                                       class="form-control"
                                       id="edit_service_charge"
                                       name="charge"
                                       step="0.01"
                                       min="0">
                            </div>

                            <div class="sv-field">
                                <label class="form-label" for="edit_service_active">Status</label>
                                <select class="form-select" id="edit_service_active" name="is_active">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </section>

                </div>

                <footer class="modal-footer sv-modal-foot">
                    <span class="sv-modal-foot-note">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        Changes take effect immediately for new requests.
                    </span>
                    <div class="sv-modal-foot-actions">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2" aria-hidden="true"></i>
                            Save changes
                        </button>
                    </div>
                </footer>
            </form>

        </div>
    </div>
</div>

<div class="sv-toast" id="svToast" role="status" aria-live="polite" hidden></div>


<style>
/* =========================================================
   SERVICE MANAGEMENT - LIST LAYOUT
   ========================================================= */

.sv {
    --sv-ink:         #0f172a;
    --sv-text:        #334155;
    --sv-muted:       #64748b;
    --sv-faint:       #94a3b8;
    --sv-line:        #e2e8f0;
    --sv-line-soft:   #f1f5f9;
    --sv-surface:     #ffffff;
    --sv-subtle:      #f8fafc;
    --sv-accent:      #0d9488;
    --sv-accent-dark: #0f766e;
    --sv-accent-soft: #e6f7f7;
    --sv-lab:         #0d9488;
    --sv-lab-soft:    #e6f7f7;
    --sv-xray:        #dd00b1;
    --sv-xray-soft:   #f8f1f7;
    --sv-danger:      #dc2626;
    --sv-danger-dark: #b91c1c;
    --sv-danger-soft: #fef2f2;
    --sv-radius:      12px;
    --sv-radius-sm:   8px;
    --sv-radius-xs:   6px;
    --sv-mono:        ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;

    color: var(--sv-text);
}

.sv *:focus-visible { outline: 2px solid var(--sv-accent); outline-offset: 2px; }

.sv-muted { color: var(--sv-faint); }

/* ---------- Header ---------- */

.sv-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
}

.sv-title {
    margin: 0 0 0.2rem;
    font-size: 1.25rem;
    font-weight: 650;
    letter-spacing: -0.015em;
    color: var(--sv-ink);
}

.sv-lede {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--sv-muted);
}

.sv-lede strong { color: var(--sv-ink); font-weight: 600; }
.sv-lede span { margin: 0 0.2rem; color: var(--sv-faint); }

.sv-head-actions { display: flex; gap: 0.5rem; }

/* ---------- Buttons ---------- */

.sv-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    height: 36px;
    padding: 0 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    color: var(--sv-text);
    background: var(--sv-surface);
    border: 1px solid var(--sv-line);
    border-radius: var(--sv-radius-xs);
    box-shadow: 0 1px 1px rgba(15, 23, 42, 0.03);
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.sv-btn:hover { background: var(--sv-subtle); border-color: #cbd5e1; color: var(--sv-ink); }
.sv-btn i { font-size: 0.9em; }

.sv-btn--primary,
.sv-btn--primary:hover { color: #ffffff; }
.sv-btn--primary { background: var(--sv-accent); border-color: var(--sv-accent); }
.sv-btn--primary:hover { background: var(--sv-accent-dark); border-color: var(--sv-accent-dark); }

/* ---------- Stats ---------- */

.sv-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.9rem;
    margin-bottom: 1.25rem;
}

.sv-stat {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    min-width: 0;
    padding: 0.9rem 1rem;
    background: var(--sv-surface);
    border: 1px solid var(--sv-line);
    border-radius: var(--sv-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.sv-stat-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 10px;
}

.sv-stat--lab   .sv-stat-icon { background: transparent; }
.sv-stat--xray  .sv-stat-icon { background: transparent; }
.sv-stat--total .sv-stat-icon { background: transparent; }

.sv-stat-img {
    width: 32px;
    height: 32px;
    object-fit: contain;
    display: block;
}

.sv-stat-body { min-width: 0; }

.sv-stat-value {
    font-size: 1.35rem;
    font-weight: 700;
    line-height: 1.1;
    letter-spacing: -0.02em;
    color: var(--sv-ink);
    font-variant-numeric: tabular-nums;
}

.sv-stat-label {
    margin-top: 0.15rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--sv-text);
}

.sv-stat-sub {
    font-size: 0.72rem;
    color: var(--sv-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* ---------- Panel ---------- */

.sv-panel {
    background: var(--sv-surface);
    border: 1px solid var(--sv-line);
    border-radius: var(--sv-radius);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

/* ---------- Tabs ---------- */

.sv-tabs {
    display: flex;
    gap: 0.25rem;
    padding: 0 0.75rem;
    border-bottom: 1px solid var(--sv-line);
    overflow-x: auto;
    scrollbar-width: none;
}

.sv-tabs::-webkit-scrollbar { display: none; }

.sv-tab {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.85rem 0.6rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--sv-muted);
    background: none;
    border: 0;
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: color 0.15s ease;
}

.sv-tab::after {
    content: "";
    position: absolute;
    left: 0.4rem;
    right: 0.4rem;
    bottom: -1px;
    height: 2px;
    border-radius: 2px 2px 0 0;
    background: transparent;
}

.sv-tab:hover { color: var(--sv-ink); }
.sv-tab.is-active { color: var(--sv-ink); }
.sv-tab.is-active::after { background: var(--sv-accent); }

.sv-tab-count {
    min-width: 1.4rem;
    padding: 0.05rem 0.4rem;
    font-size: 0.7rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    text-align: center;
    color: var(--sv-muted);
    background: var(--sv-line-soft);
    border-radius: 999px;
}

.sv-tab.is-active .sv-tab-count { color: var(--sv-accent); background: var(--sv-accent-soft); }

/* ---------- Filters ---------- */

.sv-filters {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    padding: 0.75rem 1rem;
}

.sv-search { position: relative; flex: 1 1 280px; max-width: 420px; }

.sv-search > i {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.8rem;
    color: var(--sv-faint);
    pointer-events: none;
}

.sv-input {
    width: 100%;
    height: 36px;
    padding: 0 2.1rem 0 2.1rem;
    font-size: 0.8125rem;
    color: var(--sv-ink);
    background-color: var(--sv-surface);
    border: 1px solid var(--sv-line);
    border-radius: var(--sv-radius-sm);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.sv-input::placeholder { color: var(--sv-faint); }

.sv-input:focus {
    outline: none;
    border-color: var(--sv-accent);
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.14);
}

.sv-search-clear {
    position: absolute;
    right: 0.35rem;
    top: 50%;
    transform: translateY(-50%);
    width: 26px;
    height: 26px;
    display: inline-grid;
    place-items: center;
    color: var(--sv-muted);
    background: transparent;
    border: 0;
    border-radius: 5px;
    cursor: pointer;
}

.sv-search-clear:hover { background: var(--sv-line-soft); color: var(--sv-ink); }

/* ---------- List ---------- */

.sv-list {
    list-style: none;
    margin: 0;
    padding: 0;
    border-top: 1px solid var(--sv-line);
}

.sv-row {
    position: relative;
    display: grid;
    grid-template-columns:
        44px
        minmax(0, 2.6fr)
        minmax(0, 0.9fr)
        minmax(0, 0.7fr)
        minmax(0, 0.7fr)
        minmax(0, 0.9fr)
        auto;
    align-items: center;
    gap: 1rem;
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--sv-line-soft);
    transition: background-color 0.12s ease;
}

.sv-row:last-child { border-bottom: 0; }
.sv-row:hover { background: #fafbfd; }

.sv-row::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--rail, transparent);
}

.sv-row--lab  { --rail: #0d9488; }
.sv-row--xray { --rail: #a23ee4; }

.sv-row.is-inactive { opacity: 0.72; }
.sv-row.is-hidden { display: none; }

.sv-row-icon { display: flex; align-items: center; justify-content: center; }

.sv-cat-icon {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    border-radius: 10px;
    flex-shrink: 0;
    overflow: hidden;
    background: transparent;
}

.sv-cat-img {
    width: 30px;
    height: 30px;
    object-fit: contain;
    display: block;
}

.sv-row-identity { min-width: 0; }

.sv-row-name-line {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    flex-wrap: wrap;
    margin-bottom: 0.2rem;
    min-width: 0;
}

.sv-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--sv-ink);
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

.sv-code {
    padding: 0.1rem 0.45rem;
    font-family: var(--sv-mono);
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    color: var(--sv-ink);
    background: var(--sv-line-soft);
    border-radius: 5px;
    white-space: nowrap;
}

.sv-row-desc {
    margin: 0;
    font-size: 0.78rem;
    color: var(--sv-muted);
    line-height: 1.4;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

.sv-row-category,
.sv-row-status { display: flex; align-items: center; }

.sv-cat-pill {
    display: inline-flex;
    align-items: center;
    padding: 0.15rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    border-radius: 999px;
    white-space: nowrap;
}

.sv-cat-pill--lab  { color: var(--sv-lab);  background: var(--sv-lab-soft);  }
.sv-cat-pill--xray { color: var(--sv-xray); background: var(--sv-xray-soft); }

.sv-status {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.15rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 600;
    border-radius: 999px;
    white-space: nowrap;
}

.sv-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.sv-status--active   { color: #15803d; background: #e7f6ec; }
.sv-status--inactive { color: #b91c1c; background: #fdeaea; }

.sv-row-price {
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.sv-price {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--sv-ink);
    white-space: nowrap;
}

.sv-row-created {
    font-size: 0.78rem;
    color: var(--sv-muted);
    white-space: nowrap;
}

.sv-row-created time {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-variant-numeric: tabular-nums;
}

.sv-row-created i { color: var(--sv-faint); font-size: 0.85em; }

.sv-row-actions {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.3rem;
    flex-wrap: nowrap;
}

.sv-row-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    height: 30px;
    padding: 0 0.7rem;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    color: var(--sv-text);
    background: var(--sv-surface);
    border: 1px solid var(--sv-line);
    border-radius: var(--sv-radius-xs);
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.sv-row-btn:hover {
    background: var(--sv-subtle);
    border-color: #cbd5e1;
    color: var(--sv-ink);
}

.sv-row-btn--danger { color: var(--sv-danger); }
.sv-row-btn--danger:hover {
    background: var(--sv-danger-soft);
    border-color: #fecaca;
    color: var(--sv-danger-dark);
}

/* ---------- Empty states ---------- */

.sv-empty {
    padding: 3.5rem 1rem;
    text-align: center;
}

.sv-empty--filter { border-top: 1px solid var(--sv-line); }

.sv-empty-icon {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    margin: 0 auto 0.85rem;
    font-size: 1.2rem;
    color: var(--sv-faint);
    background: var(--sv-line-soft);
    border-radius: 10px;
}

.sv-empty h3 { margin: 0 0 0.25rem; font-size: 0.95rem; font-weight: 600; color: var(--sv-ink); }
.sv-empty p { max-width: 32rem; margin: 0 auto 1rem; font-size: 0.8125rem; color: var(--sv-muted); }

/* ---------- Pagination ---------- */

.sv-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding: 0.7rem 1rem;
    font-size: 0.78rem;
    color: var(--sv-muted);
    border-top: 1px solid var(--sv-line);
    background: var(--sv-subtle);
}

.sv-foot-text strong { color: var(--sv-ink); font-weight: 600; font-variant-numeric: tabular-nums; }

.sv-pager { display: inline-flex; align-items: center; gap: 0.25rem; }

.sv-page {
    min-width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.45rem;
    font-size: 0.78rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--sv-text);
    background: var(--sv-surface);
    border: 1px solid var(--sv-line);
    border-radius: var(--sv-radius-xs);
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.sv-page:hover { background: var(--sv-subtle); border-color: #cbd5e1; color: var(--sv-ink); }
.sv-page.is-active { color: var(--sv-accent); background: var(--sv-accent-soft); border-color: #bcd6f3; }
.sv-page.is-disabled { opacity: 0.45; cursor: not-allowed; }
.sv-page--gap { border: none; background: transparent; cursor: default; color: var(--sv-faint); }

/* ---------- Modal ---------- */

.sv-modal .modal-content {
    border: 1px solid var(--sv-line);
    border-radius: var(--sv-radius);
    box-shadow: 0 16px 40px rgba(10, 43, 78, 0.16);
}

.sv-modal-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.05rem 1.35rem;
    border-bottom: 1px solid var(--sv-line-soft);
}

.sv-modal-heading { display: flex; align-items: center; gap: 0.85rem; min-width: 0; }

.sv-modal-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    font-size: 1rem;
    color: var(--sv-accent);
    background: var(--sv-accent-soft);
    border: 1px solid #cfeeee;
    border-radius: 10px;
}

.sv-modal-icon--edit { color: #b45309; background: #fff4e5; border-color: #f4e2c4; }

.sv-modal .modal-title {
    font-size: 1rem;
    font-weight: 650;
    color: var(--sv-ink);
    line-height: 1.25;
    margin: 0;
}

.sv-modal-sub {
    margin: 0.15rem 0 0;
    font-size: 0.8rem;
    color: var(--sv-muted);
    line-height: 1.4;
}

.sv-modal-body { padding: 0.5rem 1.35rem 1rem; }

.sv-section {
    padding: 1rem 0;
    border-top: 1px solid var(--sv-line-soft);
}

.sv-section:first-child { border-top: 0; padding-top: 0.6rem; }

.sv-section-title {
    margin: 0 0 0.75rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--sv-faint);
}

.sv-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem 1.25rem;
}

.sv-field { min-width: 0; }
.sv-field--full { grid-column: 1 / -1; }

.sv-modal .form-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--sv-text);
    margin-bottom: 0.35rem;
}

.sv-modal .req { color: #b91c1c; font-weight: 700; }

.sv-modal .form-control,
.sv-modal .form-select {
    border: 1px solid var(--sv-line);
    border-radius: var(--sv-radius-xs);
    font-size: 0.875rem;
    padding: 0.55rem 0.75rem;
    color: var(--sv-ink);
}

.sv-modal .form-control:focus,
.sv-modal .form-select:focus {
    border-color: var(--sv-accent);
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
}

.field-hint {
    display: block;
    margin-top: 0.3rem;
    font-size: 0.74rem;
    color: var(--sv-faint);
}

.sv-modal-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
    flex-wrap: wrap;
    padding: 0.85rem 1.35rem;
    background: var(--sv-subtle);
    border-top: 1px solid var(--sv-line-soft);
}

.sv-modal-foot-note {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.74rem;
    color: var(--sv-muted);
}

.sv-modal-foot-note i { font-size: 0.82rem; color: var(--sv-faint); }

.sv-modal-foot-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
}

/* ---------- Toast ---------- */

.sv-toast {
    position: fixed;
    right: 1.25rem;
    bottom: 1.25rem;
    z-index: 1090;
    display: flex;
    align-items: center;
    gap: 0.55rem;
    max-width: min(26rem, calc(100vw - 2.5rem));
    padding: 0.7rem 1rem;
    font-size: 0.8125rem;
    color: #f8fafc;
    background: #0f172a;
    border-radius: 9px;
    box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.45);
}

.sv-toast i { color: #34d399; }
.sv-toast.is-error i { color: #f87171; }

/* ---------- Responsive ---------- */

@media (max-width: 1200px) {
    .sv-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .sv-row {
        grid-template-columns:
            44px
            minmax(0, 1fr)
            minmax(0, 0.7fr)
            minmax(0, 0.7fr)
            auto;
        gap: 0.85rem;
    }
    .sv-row-created { display: none; }
}

@media (max-width: 992px) {
    .sv-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .sv-row {
        grid-template-columns:
            44px
            minmax(0, 1fr)
            minmax(0, 0.7fr)
            auto;
    }
    .sv-row-category { display: none; }
}

@media (max-width: 768px) {
    .sv-head { flex-direction: column; align-items: stretch; }
    .sv-head-actions .sv-btn { flex: 1; }

    .sv-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }

    .sv-filters .sv-search { max-width: none; }

    .sv-row {
        grid-template-columns: 44px minmax(0, 1fr);
        padding: 0.9rem 1rem;
        row-gap: 0.5rem;
    }

    .sv-row-icon     { grid-column: 1; grid-row: 1; }
    .sv-row-identity { grid-column: 2; grid-row: 1; }

    .sv-row-status,
    .sv-row-price {
        grid-column: 1 / -1;
        justify-content: flex-start;
        text-align: left;
    }

    .sv-row-status { grid-row: 2; }
    .sv-row-price  { grid-row: 3; }

    .sv-row-actions {
        grid-column: 1 / -1;
        grid-row: 4;
        justify-content: flex-start;
        flex-wrap: wrap;
    }

    .sv-row-btn { flex: 1 1 auto; }

    .sv-row-name-line { align-items: flex-start; }
    .sv-name          { white-space: normal; }
    .sv-row-desc      { white-space: normal; }

    .sv-foot { flex-direction: column; align-items: center; }
}

@media (max-width: 576px) {
    .sv-stats { grid-template-columns: minmax(0, 1fr); gap: 0.6rem; }

    .sv-grid-2 { grid-template-columns: minmax(0, 1fr); }
    .sv-modal-head  { padding: 1rem; }
    .sv-modal-body  { padding: 0.35rem 1rem 0.85rem; }
    .sv-modal-foot  { padding: 0.75rem 1rem; }
    .sv-modal-foot-note    { width: 100%; }
    .sv-modal-foot-actions { width: 100%; }
    .sv-modal-foot-actions .btn { flex: 1; }

    #svToast { left: 12px; right: 12px; bottom: 16px; max-width: none; }
}

@media (prefers-reduced-motion: reduce) {
    .sv * { transition: none !important; }
}
</style>

<script>
(function () {
    'use strict';

    var list    = document.getElementById('svList');
    var toastEl = document.getElementById('svToast');

    if (!list) { return; }

    var rows        = Array.prototype.slice.call(list.querySelectorAll('.sv-row'));
    var noMatch     = document.getElementById('svNoMatch');
    var search      = document.getElementById('searchServices');
    var searchClear = document.getElementById('svSearchClear');
    var clearBtn    = document.getElementById('svClearFilters');

    function applySearch() {
        var term    = search ? search.value.toLowerCase().trim() : '';
        var visible = 0;

        rows.forEach(function (row) {
            var matches = term === '' || (row.dataset.search || '').indexOf(term) !== -1;
            row.classList.toggle('is-hidden', !matches);
            if (matches) { visible++; }
        });

        if (noMatch) {
            noMatch.hidden = !(rows.length > 0 && visible === 0 && term !== '');
        }

        if (searchClear) {
            searchClear.hidden = term === '';
        }
    }

    if (search) {
        search.addEventListener('input', applySearch);
        search.addEventListener('search', applySearch);
    }

    if (searchClear) {
        searchClear.addEventListener('click', function () {
            if (search) { search.value = ''; }
            applySearch();
            if (search) { search.focus(); }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (search) { search.value = ''; }
            applySearch();
        });
    }

    /* ============================================
       EDIT MODAL
       ============================================ */

    document.querySelectorAll('.edit-service').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = this.closest('.sv-row');
            if (!row) { return; }

            var id       = row.dataset.id;
            var code     = row.dataset.code;
            var name     = row.dataset.name;
            var category = row.dataset.category;
            var desc     = row.dataset.description || '';
            var charge   = row.dataset.charge || 0;
            var active   = row.dataset.active === '1' ? '1' : '0';

            var setValue = function (elId, value) {
                var el = document.getElementById(elId);
                if (el) { el.value = value; }
            };

            setValue('edit_service_id', id);
            setValue('edit_service_code', code);
            setValue('edit_service_name', name);
            setValue('edit_service_category', category);
            setValue('edit_service_description', desc);
            setValue('edit_service_charge', charge);
            setValue('edit_service_active', active);

            var form = document.getElementById('editServiceForm');
            if (form) {
                form.action = '<?= base_url('admin/services/update') ?>/' + encodeURIComponent(id);
            }
        });
    });

    /* ============================================
       EXPORT CSV
       ============================================ */

    var exportBtn = document.getElementById('svExport');

    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            var data = rows
                .filter(function (row) { return !row.classList.contains('is-hidden'); })
                .map(function (row) {
                    return {
                        code:     row.dataset.code,
                        name:     row.dataset.name,
                        category: row.dataset.category,
                        charge:   row.dataset.charge,
                        active:   row.dataset.active === '1' ? 'Active' : 'Inactive',
                        desc:     row.dataset.description
                    };
                });

            if (!data.length) {
                showToast('There are no services in the current view to export.', true);
                return;
            }

            var header = ['Code', 'Name', 'Category', 'Price', 'Status', 'Description'];

            var csvCell = function (value) {
                var v = value == null ? '' : String(value);
                if (/^[=@\t\r]/.test(v) || /^[+\-](?![\d\s()]+$)/.test(v)) { v = "'" + v; }
                return '"' + v.replace(/"/g, '""') + '"';
            };

            var lines = [header.map(csvCell).join(',')];
            data.forEach(function (r) {
                lines.push([
                    r.code,
                    r.name,
                    r.category,
                    r.charge,
                    r.active,
                    r.desc
                ].map(csvCell).join(','));
            });

            var blob = new Blob([String.fromCharCode(0xFEFF) + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'services-' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(function () { URL.revokeObjectURL(link.href); }, 1000);

            showToast('Exported ' + data.length + ' service' + (data.length === 1 ? '' : 's'));
        });
    }

    /* ============================================
       TOAST
       ============================================ */

    var toastTimer = null;

    function showToast(message, isError) {
        if (!toastEl) { return; }
        toastEl.className = 'sv-toast' + (isError ? ' is-error' : '');
        toastEl.innerHTML =
            '<i class="bi ' + (isError ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill') + '" aria-hidden="true"></i>' +
            '<span>' + String(message).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            }) + '</span>';
        toastEl.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toastEl.hidden = true; }, 4000);
    }

    /* ============================================
       START
       ============================================ */

    applySearch();
})();
</script>

<?= $this->endSection() ?>