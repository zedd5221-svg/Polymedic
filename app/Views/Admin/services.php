<?= $this->extend('Layouts/AdminLayout') ?>

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

$activeCategory = strtolower(trim((string) ($active_category ?? 'all')));
if (!in_array($activeCategory, ['all', 'laboratory', 'xray'], true)) {
    $activeCategory = 'all';
}

$categoryMeta = [
    'laboratory' => ['label' => 'Laboratory', 'tone' => 'lab',  'icon' => 'flask'],
    'xray'       => ['label' => 'X-Ray',      'tone' => 'xray', 'icon' => 'xray'],
];

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

$tabUrl = static function (string $key): string {
    $base = 'admin/services';
    $qs   = $key === 'all' ? '' : ('?category=' . rawurlencode($key));
    return base_url($base . $qs);
};
?>

<div class="sv" id="svRoot">

    <!-- ===== PAGE HEADER ===== -->
    <header class="sv-head">

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


    <!-- ===== CATALOGUE SUMMARY ===== -->
    <?php
    $svShare = static fn(int $n): string => ($total > 0 ? (int) round($n / $total * 100) : 0) . '% of catalogue';
    ?>
    <?= view('partials/kpi_strip', [
        'id'    => 'services',
        'cards' => [
            ['key' => 'total', 'label' => 'Total services',    'value' => $total,     'context' => 'all categories'],
            ['key' => 'lab',   'label' => 'Laboratory',        'value' => $labCount,  'context' => $svShare($labCount)],
            ['key' => 'xray',  'label' => 'X-Ray & imaging',   'value' => $xrayCount, 'context' => $svShare($xrayCount)],
        ],
    ]) ?>


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

            <div class="sv-list-head" aria-hidden="true">
                <span>Service</span>
                <span>Category</span>
                <span>Price</span>
                <span>Status</span>
                <span>Added</span>
                <span></span>
            </div>

            <ul class="sv-list" id="svList" role="list">
                <?php foreach ($serviceList as $service):
                    $category = $normaliseCategory($service['category'] ?? '');
                    $meta     = $categoryMeta[$category] ?? $categoryMeta['laboratory'];

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

                        <div class="sv-row-identity">
                            <div class="sv-row-name-line">
                                <span class="sv-name"><?= esc($name) ?></span>
                                <span class="sv-code" title="Service code"><?= esc($code) ?></span>
                            </div>

                            <p class="sv-row-desc">
                                <?php if ($desc !== ''): ?>
                                    <?= esc($desc) ?>
                                <?php else: ?>
                                    <span class="sv-muted">No description</span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="sv-row-category">
                            <span class="sv-cat sv-cat--<?= esc($meta['tone'], 'attr') ?>"><?= esc($meta['label']) ?></span>
                        </div>

                        <div class="sv-row-price">
                            <span class="sv-price">&#8369;<?= number_format($charge, 2) ?></span>
                        </div>

                        <div class="sv-row-status">
                            <span class="sv-status sv-status--<?= $isActive ? 'active' : 'inactive' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
                        </div>

                        <div class="sv-row-created">
                            <?php if ($created): ?>
                                <time datetime="<?= esc(date('c', $created), 'attr') ?>"><?= esc(date('M j, Y', $created)) ?></time>
                            <?php else: ?>
                                <span class="sv-muted">&mdash;</span>
                            <?php endif; ?>
                        </div>

                        <div class="sv-row-actions">
                            <button type="button"
                                    class="sv-icon-btn edit-service"
                                    title="Edit"
                                    aria-label="Edit <?= esc($name, 'attr') ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editServiceModal">
                                <i class="bi bi-pencil" aria-hidden="true"></i>
                            </button>

                            <div class="dropdown">
                                <button type="button"
                                        class="sv-icon-btn"
                                        data-bs-toggle="dropdown"
                                        data-bs-popper-config='{"strategy":"fixed"}'
                                        aria-expanded="false"
                                        aria-label="More actions for <?= esc($name, 'attr') ?>">
                                    <i class="bi bi-three-dots" aria-hidden="true"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end sv-menu">
                                    <li>
                                        <a class="dropdown-item"
                                           data-post href="<?= base_url('admin/services/toggle/' . (int) ($service['id'] ?? 0)) ?>">
                                            <i class="bi bi-<?= $isActive ? 'pause-circle' : 'play-circle' ?>" aria-hidden="true"></i>
                                            <?= $isActive ? 'Deactivate' : 'Activate' ?>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item sv-menu-danger"
                                           data-post href="<?= base_url('admin/services/delete/' . (int) ($service['id'] ?? 0)) ?>"
                                           onclick="return confirm('Delete <?= esc(addslashes($name), 'attr') ?>? This cannot be undone.')">
                                            <i class="bi bi-trash3" aria-hidden="true"></i>
                                            Delete
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                    </li>
                <?php endforeach; ?>
            </ul>

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
   Theme aligned with AdminLayout blue palette.
   ========================================================= */

/* The modals and toast live outside .sv, so they need the tokens too
   (without them every var(--sv-*) in the modal was empty: no input
   borders, no focus ring, no footer background). */
.sv,
.sv-modal,
.sv-toast {
    --sv-ink:         var(--pm-text, #10202E);
    --sv-text:        var(--pm-text-soft, #3B4C5E);
    --sv-muted:       var(--pm-text-muted, #5D6E80);
    --sv-faint:       var(--pm-text-muted);
    --sv-line:        var(--pm-line, #DDE4EC);
    --sv-line-soft:   var(--pm-line);
    --sv-surface:     var(--pm-surface);
    --sv-subtle:      var(--pm-subtle);

    /* === Aligned with layout blue === */
    --sv-accent:      var(--pm-accent);
    --sv-accent-dark: var(--pm-accent-text);
    --sv-accent-soft: var(--pm-accent-soft);

    --sv-lab:         #175563;
    --sv-lab-soft:    var(--pm-accent-soft);
    --sv-xray:         #0E8A7E;
    --sv-xray-soft:    var(--pm-tint-sea);
    --sv-row-hover:    var(--pm-subtle);

    --sv-danger:      #B42318;
    --sv-danger-dark: #912018;
    --sv-danger-soft: var(--pm-tint-red);

    --sv-radius:      8px;
    --sv-radius-sm:   8px;
    --sv-radius-xs:   6px;
    --sv-mono:        var(--pm-mono, ui-monospace, monospace);

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
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.sv-btn:hover { background: var(--sv-subtle); border-color: var(--pm-line); color: var(--sv-ink); }
.sv-btn i { font-size: 0.9em; }

.sv-btn--primary,
.sv-btn--primary:hover { color: #ffffff; }
.sv-btn--primary { background: var(--sv-accent); border-color: var(--sv-accent); }
.sv-btn--primary:hover { background: var(--sv-accent-dark); border-color: var(--sv-accent-dark); }

/* ---------- Panel ---------- */

.sv-panel {
    background: var(--sv-surface);
    border: 1px solid var(--sv-line);
    border-radius: var(--sv-radius);
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
    border-radius: 4px;
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
    box-shadow: 0 0 0 3px rgba(23, 85, 99, 0.14);
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

.sv-list-head,
.sv-row {
    display: grid;
    grid-template-columns:
        minmax(0, 2.6fr)   /* service  */
        120px              /* category */
        110px              /* price    */
        100px              /* status   */
        110px              /* added    */
        72px;              /* actions  */
    align-items: center;
    column-gap: 1rem;
    padding: 0 1rem;
}

.sv-list-head {
    height: 36px;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--sv-muted);
    background: var(--sv-subtle);
    border-top: 1px solid var(--sv-line);
    border-bottom: 1px solid var(--sv-line);
}

.sv-list-head > :nth-child(3) { text-align: right; }

.sv-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.sv-row {
    position: relative;
    min-height: 60px;
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
    border-bottom: 1px solid var(--sv-line-soft);
    transition: background-color 0.12s ease;
}

.sv-row:last-child { border-bottom: 0; }
.sv-row:hover { background: var(--sv-row-hover); }

.sv-row.is-hidden { display: none; }

/* Inactive services stay readable; the status column says why they look quieter */
.sv-row.is-inactive .sv-name,
.sv-row.is-inactive .sv-price { color: var(--sv-muted); }

/* ---- service ---- */

.sv-row-identity { min-width: 0; }

.sv-row-name-line {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    min-width: 0;
}

.sv-name {
    min-width: 0;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--sv-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sv-code {
    flex-shrink: 0;
    font-family: var(--sv-mono);
    font-size: 0.72rem;
    color: var(--sv-muted);
}

.sv-row-desc {
    margin: 0.1rem 0 0;
    font-size: 0.76rem;
    color: var(--sv-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ---- category: dot label ---- */

.sv-cat {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--sv-text);
    white-space: nowrap;
}

.sv-cat::before {
    content: "";
    width: 7px;
    height: 7px;
    border-radius: 2px;
    background: var(--cat, var(--sv-faint));
}

.sv-cat--lab  { --cat: #175563; }
.sv-cat--xray { --cat: #0E8A7E; }

/* ---- price ---- */

.sv-row-price { text-align: right; }

.sv-price {
    font-size: 0.84rem;
    font-weight: 600;
    color: var(--sv-ink);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

/* ---- status: dot + text ---- */

.sv-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--sv-text);
    white-space: nowrap;
}

.sv-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--dot, #98A2B3);
}

.sv-status--active   { --dot: #17B26A; }
.sv-status--inactive { --dot: #98A2B3; color: var(--sv-muted); }

/* ---- added ---- */

.sv-row-created {
    font-size: 0.78rem;
    color: var(--sv-muted);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

/* ---- actions ---- */

.sv-row-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.15rem;
}

.sv-icon-btn {
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    color: var(--sv-muted);
    background: transparent;
    border: 1px solid transparent;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.sv-icon-btn:hover,
.sv-icon-btn[aria-expanded="true"] { background: var(--sv-line-soft); color: var(--sv-ink); }

.sv-menu {
    min-width: 11rem;
    padding: 0.3rem;
    font-size: 0.8125rem;
    border: 1px solid var(--sv-line);
    border-radius: 8px;
}

.sv-menu .dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.45rem 0.6rem;
    color: var(--sv-text);
    border-radius: 6px;
}

.sv-menu .dropdown-item i { font-size: 0.9rem; color: var(--sv-faint); }
.sv-menu .dropdown-item:hover,
.sv-menu .dropdown-item:focus { background: var(--sv-line-soft); color: var(--sv-ink); }
.sv-menu .dropdown-divider { margin: 0.3rem 0; border-color: var(--sv-line-soft); }
.sv-menu .sv-menu-danger,
.sv-menu .sv-menu-danger i { color: var(--sv-danger); }
.sv-menu .sv-menu-danger:hover { background: var(--sv-danger-soft); color: var(--sv-danger-dark); }

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

.sv-page:hover { background: var(--sv-subtle); border-color: var(--pm-line); color: var(--sv-ink); }
.sv-page.is-active { color: var(--sv-accent); background: var(--sv-accent-soft); border-color: var(--pm-dk-edge-blue, #bcd6f3); }
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
    border: 1px solid var(--pm-accent-line);
    border-radius: 10px;
}

.sv-modal-icon--edit { color: var(--sv-accent); background: var(--sv-accent-soft); border-color: var(--pm-accent-line); }

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

.sv-modal .req { color: var(--pm-dk-fg-red, #b91c1c); font-weight: 700; }

.sv-modal .form-control,
.sv-modal .form-select {
    background-color: var(--sv-surface);
    min-height: 40px;
    border: 1px solid var(--pm-line-strong, var(--sv-line));
    border-radius: var(--sv-radius-xs);
    font-size: 0.875rem;
    padding: 0.55rem 0.75rem;
    color: var(--sv-ink);
}

.sv-modal .form-control::placeholder { color: var(--sv-faint); }
.sv-modal textarea.form-control { min-height: 84px; resize: vertical; }

.sv-modal .form-control:focus,
.sv-modal .form-select:focus {
    background-color: var(--sv-surface);
    border-color: var(--sv-accent);
    box-shadow: 0 0 0 3px rgba(23, 85, 99, 0.12);
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
    .sv-list-head,
    .sv-row {
        grid-template-columns: minmax(0, 2.6fr) 120px 110px 100px 72px;
    }

    .sv-list-head > :nth-child(5),
    .sv-row-created { display: none; }
}

@media (max-width: 768px) {
    .sv-head { flex-direction: column; align-items: stretch; }
    .sv-head-actions .sv-btn { flex: 1; }


    .sv-filters .sv-search { max-width: none; }

    .sv-list-head { display: none; }

    .sv-row {
        grid-template-columns: minmax(0, 1fr) auto;
        grid-template-areas:
            "identity actions"
            "category price"
            "status   status";
        row-gap: 0.45rem;
        padding: 0.85rem 1rem;
    }

    .sv-row-identity { grid-area: identity; }
    .sv-row-actions  { grid-area: actions; align-self: start; }
    .sv-row-category { grid-area: category; }
    .sv-row-price    { grid-area: price; }
    .sv-row-status   { grid-area: status; }

    .sv-name     { white-space: normal; }
    .sv-row-desc { white-space: normal; }

    .sv-foot { flex-direction: column; align-items: center; }
}

@media (max-width: 576px) {

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