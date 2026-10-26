<?= $this->extend('Layouts/AdminLayout') ?>

<?= $this->section('pageTitle') ?>Availability<?= $this->endSection() ?>

<?= $this->section('adminContent') ?>

<?php
    $s              = $settings ?? [];
    $closedWeekdays = array_map('intval', $s['closed_weekdays'] ?? []);
    $holidays       = $s['holidays'] ?? [];
    $intervals      = $slot_intervals ?? [60];
    $weekdayLabels  = $weekday_labels ?? [
        0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
        4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
    ];
    $preview      = $preview ?? [];
    $holidayLines = implode("\n", $holidays);

    $openTime  = $s['clinic_open_time']  ?? '08:00';
    $closeTime = $s['clinic_close_time'] ?? '17:00';
    $lunchFrom = $s['lunch_start']       ?? '12:00';
    $lunchTo   = $s['lunch_end']         ?? '13:00';
    $interval  = (int) ($s['slot_interval'] ?? 60);
    $horizon   = (int) ($s['booking_horizon'] ?? 90);

    $pretty = static function ($hhmm) {
        $ts = strtotime((string) $hhmm);
        return $ts ? date('g:i A', $ts) : (string) $hhmm;
    };

    $shortDay = static function ($label) {
        return mb_substr($label, 0, 3);
    };

    $openDays = count($weekdayLabels) - count($closedWeekdays);

    $closedNames = [];
    foreach ($weekdayLabels as $num => $label) {
        if (in_array((int) $num, $closedWeekdays, true)) {
            $closedNames[] = $label;
        }
    }

    $openCount = $holidayCount = $closedCount = 0;
    foreach ($preview as $day) {
        if (! empty($day['holiday'])) {
            $holidayCount++;
        } elseif (empty($day['working'])) {
            $closedCount++;
        } else {
            $openCount++;
        }
    }
?>

<style>
/* =========================================================
   CLINIC AVAILABILITY
   Namespaced under .av-shell. Theme aligned with AdminLayout blue.
   ========================================================= */

.av-shell {
    --av-ink:        #0f172a;
    --av-text:       #334155;
    --av-muted:      #64748b;
    --av-faint:      #94a3b8;
    --av-line:       #e5e9f0;
    --av-line-soft:  #f1f5f9;
    --av-subtle:     #f8fafc;

    /* === Aligned with layout blue === */
    --av-accent:     #1976d2;
    --av-accent-dark:#1565c0;
    --av-accent-soft:#e3f2fd;

    --av-danger:     #dc2626;
    --av-danger-soft:#fef2f2;
    --av-amber:      #b45309;
    --av-blue:       #1976d2;

    --av-radius:     14px;
    --av-radius-sm:  9px;
    --av-shadow:     0 1px 2px rgba(15, 23, 42, .04), 0 1px 3px rgba(15, 23, 42, .03);
    --av-mono:       ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;

    color: var(--av-text);
    font-size: .875rem;
    line-height: 1.55;
    padding-bottom: 5rem;
}

.av-shell *:focus-visible {
    outline: 2px solid var(--av-accent);
    outline-offset: 2px;
    border-radius: 4px;
}

/* ---------------------------------------------------------
   PAGE HEADER
   --------------------------------------------------------- */

.av-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
    padding-bottom: 1rem;
    margin-bottom: 1.25rem;
    border-bottom: 1px solid var(--av-line);
}

.av-title {
    margin: 0;
    font-size: 1.1875rem;
    font-weight: 650;
    letter-spacing: -.02em;
    color: var(--av-ink);
}

.av-lede {
    margin: .2rem 0 0;
    font-size: .82rem;
    color: var(--av-muted);
    max-width: 58ch;
}

/* ---------------------------------------------------------
   SUMMARY STRIP
   --------------------------------------------------------- */

.av-summary {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0;
    background: #fff;
    border: 1px solid var(--av-line);
    border-radius: var(--av-radius);
    box-shadow: var(--av-shadow);
    overflow: hidden;
    margin-bottom: 1.25rem;
}

.av-sum-item {
    padding: .85rem 1rem;
    border-right: 1px solid var(--av-line);
    min-width: 0;
}

.av-sum-item:last-child { border-right: none; }

.av-sum-item dt {
    display: flex;
    align-items: center;
    gap: .35rem;
    font-size: .65rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--av-faint);
    margin-bottom: .2rem;
}

.av-sum-item dt i { font-size: .8rem; }

.av-sum-item dd {
    margin: 0;
    font-size: .92rem;
    font-weight: 650;
    color: var(--av-ink);
    line-height: 1.3;
}

.av-sum-item dd small {
    display: block;
    margin-top: .1rem;
    font-size: .7rem;
    font-weight: 500;
    color: var(--av-faint);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ---------------------------------------------------------
   ALERTS
   --------------------------------------------------------- */

.av-alert {
    display: flex;
    align-items: flex-start;
    gap: .55rem;
    padding: .75rem 1rem;
    margin-bottom: 1rem;
    border-radius: var(--av-radius-sm);
    font-size: .82rem;
    border: 1px solid transparent;
}

.av-alert--success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
.av-alert--error   { background: var(--av-danger-soft); border-color: #fecaca; color: #991b1b; }

/* ---------------------------------------------------------
   MAIN GRID
   --------------------------------------------------------- */

.av-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
    gap: 1.25rem;
    align-items: start;
}

.av-col {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    min-width: 0;
}

/* ---------------------------------------------------------
   CARDS
   --------------------------------------------------------- */

.av-card {
    background: #fff;
    border: 1px solid var(--av-line);
    border-radius: var(--av-radius);
    box-shadow: var(--av-shadow);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.av-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .85rem 1.15rem;
    border-bottom: 1px solid var(--av-line-soft);
    background: linear-gradient(180deg, #fcfdfe, #fff);
    flex-shrink: 0;
}

.av-card-head h3 {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin: 0;
    font-size: .8125rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: var(--av-ink);
}

.av-card-head h3 i { color: var(--av-accent); font-size: .95rem; }

.av-card-head p {
    margin: .2rem 0 0;
    font-size: .76rem;
    font-weight: 400;
    letter-spacing: 0;
    text-transform: none;
    color: var(--av-muted);
}

.av-card-body {
    padding: 1.1rem 1.15rem;
    display: flex;
    flex-direction: column;
    flex: 1;
}

/* ---------------------------------------------------------
   FORM FIELDS
   --------------------------------------------------------- */

.av-row {
    display: flex;
    flex-direction: column;
    gap: .3rem;
    margin-bottom: .9rem;
    min-width: 0;
}

.av-shell [hidden] { display: none !important; }
.av-row:last-child { margin-bottom: 0; }

.av-row > label {
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--av-muted);
}

.av-input, .av-select, .av-textarea {
    width: 100%;
    padding: .55rem .7rem;
    font-size: .875rem;
    font-family: inherit;
    color: var(--av-ink);
    background: #fff;
    border: 1px solid var(--av-line);
    border-radius: var(--av-radius-sm);
    transition: border-color .15s ease, box-shadow .15s ease;
}

.av-input:hover, .av-select:hover, .av-textarea:hover { border-color: #cbd5e1; }

.av-input:focus, .av-select:focus, .av-textarea:focus {
    outline: none;
    border-color: var(--av-accent);
    box-shadow: 0 0 0 3px rgba(25, 118, 210, .15);
}

.av-textarea {
    min-height: 6rem;
    font-family: var(--av-mono);
    font-size: .8rem;
    line-height: 1.7;
}

.av-hint { margin: .3rem 0 0; font-size: .73rem; color: var(--av-faint); }
.av-hint code { background: var(--av-subtle); border-radius: 4px; padding: .05rem .3rem; color: var(--av-accent-dark); }

.av-field-error {
    display: none;
    margin: .3rem 0 0;
    font-size: .75rem;
    font-weight: 500;
    color: var(--av-danger);
}

.av-field-error.show { display: block; }

.av-inline {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: .85rem;
    align-items: start;
}

.av-pair {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    align-items: end;
    gap: .5rem;
}

.av-pair-sep {
    padding-bottom: .55rem;
    font-size: .75rem;
    color: var(--av-faint);
}

.av-fieldset {
    border: 1px solid var(--av-line-soft);
    border-radius: var(--av-radius-sm);
    padding: .85rem .9rem;
    margin-bottom: 1rem;
}

.av-fieldset:last-child { margin-bottom: 0; }

.av-fieldset > legend {
    float: none;
    width: auto;
    padding: 0 .35rem;
    margin: 0 0 0 -.35rem;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--av-muted);
}

/* ---------------------------------------------------------
   WEEKDAY CHIPS
   --------------------------------------------------------- */

.av-weekdays { display: flex; flex-wrap: wrap; gap: .4rem; }

.av-weekday {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .4rem .7rem;
    border: 1px solid var(--av-line);
    border-radius: 99px;
    background: #fff;
    font-size: .8rem;
    font-weight: 600;
    color: var(--av-text);
    cursor: pointer;
    user-select: none;
    transition: background .15s ease, border-color .15s ease, color .15s ease;
}

.av-weekday:hover { border-color: #cbd5e1; background: var(--av-subtle); }

.av-weekday input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.av-weekday i { font-size: .8rem; color: var(--av-faint); }

.av-weekday:has(input:checked) {
    background: var(--av-danger-soft);
    border-color: #fecaca;
    color: #b91c1c;
}

.av-weekday:has(input:checked) i { color: #dc2626; }

.av-weekday:has(input:focus-visible) {
    outline: 2px solid var(--av-accent);
    outline-offset: 2px;
}

/* ---------------------------------------------------------
   HOLIDAYS
   --------------------------------------------------------- */

.av-holiday-add {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: .5rem;
    align-items: end;
}

.av-chips {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    margin-top: .75rem;
    min-height: 1.5rem;
}

.av-chip {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .28rem .4rem .28rem .65rem;
    border-radius: 99px;
    background: var(--av-subtle);
    border: 1px solid var(--av-line);
    font-size: .76rem;
    font-weight: 600;
    color: var(--av-ink);
}

.av-chip.is-past { opacity: .55; }

.av-chip small { font-weight: 500; color: var(--av-faint); }

.av-chip button {
    display: grid;
    place-items: center;
    width: 18px;
    height: 18px;
    border: none;
    border-radius: 50%;
    background: transparent;
    color: var(--av-faint);
    font-size: .7rem;
    cursor: pointer;
    transition: background .15s ease, color .15s ease;
}

.av-chip button:hover { background: #fee2e2; color: var(--av-danger); }

.av-chips-empty { font-size: .78rem; color: var(--av-faint); }

.av-disclosure {
    margin-top: .85rem;
    border-top: 1px solid var(--av-line-soft);
    padding-top: .7rem;
}

.av-disclosure summary {
    font-size: .76rem;
    font-weight: 600;
    color: var(--av-muted);
    cursor: pointer;
    list-style: none;
}

.av-disclosure summary::-webkit-details-marker { display: none; }

.av-disclosure summary::before {
    content: '\F285';
    font-family: 'bootstrap-icons';
    margin-right: .35rem;
    font-size: .7rem;
    display: inline-block;
    transition: transform .15s ease;
}

.av-disclosure[open] summary::before { transform: rotate(90deg); }

/* ---------------------------------------------------------
   SEGMENTED CONTROL
   --------------------------------------------------------- */

.av-segment {
    display: inline-flex;
    padding: 3px;
    gap: 2px;
    background: var(--av-subtle);
    border: 1px solid var(--av-line);
    border-radius: 99px;
    flex-shrink: 0;
}

.av-segment button {
    border: none;
    background: transparent;
    border-radius: 99px;
    padding: .3rem .85rem;
    font-size: .76rem;
    font-weight: 650;
    font-family: inherit;
    color: var(--av-muted);
    cursor: pointer;
    transition: background .15s ease, color .15s ease;
}

.av-segment button[aria-pressed="true"] {
    background: #fff;
    color: var(--av-ink);
    box-shadow: 0 1px 2px rgba(15, 23, 42, .12);
}

/* ---------------------------------------------------------
   BUTTONS
   --------------------------------------------------------- */

.av-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    height: 38px;
    padding: 0 1rem;
    font-size: .82rem;
    font-weight: 650;
    font-family: inherit;
    border-radius: var(--av-radius-sm);
    border: 1px solid var(--av-line);
    background: #fff;
    color: var(--av-text);
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease, transform .12s ease;
}

.av-btn:hover { background: var(--av-subtle); border-color: #cbd5e1; color: var(--av-ink); }
.av-btn:active { transform: translateY(1px); }

.av-btn--primary {
    background: var(--av-accent);
    border-color: var(--av-accent);
    color: #fff;
    box-shadow: 0 1px 2px rgba(25, 118, 210, .35);
}

.av-btn--primary:hover {
    background: var(--av-accent-dark);
    border-color: var(--av-accent-dark);
    color: #fff;
    box-shadow: 0 4px 12px rgba(25, 118, 210, .28);
}

.av-btn--danger {
    background: var(--av-danger);
    border-color: var(--av-danger);
    color: #fff;
}

.av-btn--danger:hover { background: #b91c1c; border-color: #b91c1c; color: #fff; }

.av-actions {
    display: flex;
    justify-content: flex-end;
    gap: .5rem;
    margin-top: 1.15rem;
    padding-top: 1rem;
    border-top: 1px solid var(--av-line-soft);
}

/* ---------------------------------------------------------
   STICKY SAVE BAR
   --------------------------------------------------------- */

.av-savebar {
    position: sticky;
    bottom: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin: 0;
    padding: .7rem 1.1rem;
    background: rgba(255, 255, 255, .95);
    backdrop-filter: blur(8px);
    border: 1px solid var(--av-line);
    border-radius: var(--av-radius);
    box-shadow: 0 -2px 14px rgba(15, 23, 42, .07);
}

.av-savebar-note {
    display: flex;
    align-items: center;
    gap: .45rem;
    font-size: .78rem;
    color: var(--av-faint);
}

.av-savebar.is-dirty { border-color: #fcd34d; background: #fffdf5; }
.av-savebar.is-dirty .av-savebar-note { color: var(--av-amber); font-weight: 600; }

/* ---------------------------------------------------------
   14-DAY PREVIEW
   --------------------------------------------------------- */

.av-legend {
    display: flex;
    flex-wrap: wrap;
    gap: .9rem;
    font-size: .73rem;
    color: var(--av-muted);
    padding-bottom: .9rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid var(--av-line-soft);
}

.av-legend span { display: inline-flex; align-items: center; gap: .35rem; }

.av-legend i {
    width: 9px;
    height: 9px;
    border-radius: 3px;
    display: inline-block;
}

.av-preview-scroll {
    max-height: 34rem;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: .35rem;
    margin-right: -.35rem;

    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.av-preview-scroll::-webkit-scrollbar { width: 6px; }
.av-preview-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}
.av-preview-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
.av-preview-scroll::-webkit-scrollbar-track { background: transparent; }

.av-preview-scroll .av-week-label {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #ffffff;
    padding: .35rem 0 .5rem;
    margin-top: 0;
}

.av-preview-scroll .av-week-label:not(:first-child) {
    margin-top: .55rem;
}

.av-week-label {
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--av-faint);
    margin: 0 0 .5rem;
}

.av-week-label:not(:first-child) { margin-top: 1.1rem; }

.av-preview {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .55rem;
}

.av-day {
    position: relative;
    padding: .65rem .75rem;
    border: 1px solid var(--av-line);
    border-radius: var(--av-radius-sm);
    background: #fff;
    transition: border-color .15s ease, box-shadow .15s ease;
}

.av-day:hover { border-color: #cbd5e1; box-shadow: var(--av-shadow); }

.av-day.is-closed  { background: var(--av-subtle); }
.av-day.is-holiday { background: var(--av-danger-soft); border-color: #fecaca; }

.av-day.is-today {
    border-color: var(--av-accent);
    box-shadow: 0 0 0 3px rgba(25, 118, 210, .14);
}

.av-day-top {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: .4rem;
    margin-bottom: .45rem;
}

.av-day-label {
    font-size: .82rem;
    font-weight: 650;
    color: var(--av-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
}

.av-day-tag {
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    padding: .05rem .4rem;
    border-radius: 99px;
    flex-shrink: 0;
}

.av-day-tag--today   { background: var(--av-accent-soft); color: var(--av-accent-dark); }
.av-day-tag--closed  { background: #e2e8f0; color: #475569; }
.av-day-tag--holiday { background: #fee2e2; color: #b91c1c; }
.av-day-tag--full    { background: #fef3c7; color: #92400e; }

/* capacity bars */
.av-bar-row {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr) auto;
    align-items: center;
    gap: .4rem;
    font-size: .7rem;
    color: var(--av-muted);
}

.av-bar-row + .av-bar-row { margin-top: .3rem; }

.av-bar {
    height: 6px;
    border-radius: 99px;
    background: #e8edf4;
    overflow: hidden;
}

.av-bar span {
    display: block;
    height: 100%;
    border-radius: 99px;
    background: var(--av-bar-color, var(--av-accent));
    transition: width .35s ease;
}

.av-bar-row--lab  { --av-bar-color: #1976d2; }
.av-bar-row--xray { --av-bar-color: #7c3aed; }

.av-bar-row b { color: var(--av-ink); font-weight: 650; font-variant-numeric: tabular-nums; }

.av-bar-row.is-full .av-bar span { background: #d97706; }

.av-day-closed-note {
    font-size: .73rem;
    color: var(--av-muted);
    font-weight: 500;
    margin: 0;
}

.av-empty {
    text-align: center;
    padding: 2.25rem 1rem;
    color: var(--av-faint);
    font-size: .82rem;
}

/* ---------------------------------------------------------
   RESPONSIVE
   --------------------------------------------------------- */

@media (max-width: 1200px) {
    .av-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .av-sum-item:nth-child(3n) { border-right: none; }
    .av-sum-item:nth-child(-n+3) { border-bottom: 1px solid var(--av-line); }
}

@media (max-width: 1100px) {
    .av-grid { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 640px) {
    .av-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .av-sum-item:nth-child(3n) { border-right: 1px solid var(--av-line); }
    .av-sum-item:nth-child(2n) { border-right: none; }
    .av-sum-item:nth-child(-n+4) { border-bottom: 1px solid var(--av-line); }

    .av-pair { grid-template-columns: 1fr; }
    .av-pair-sep { display: none; padding: 0; text-align: center; }

    .av-preview { grid-template-columns: minmax(0, 1fr); }

    .av-preview-scroll { max-height: 26rem; }

    .av-savebar { flex-direction: column; align-items: stretch; }
    .av-savebar .av-btn { width: 100%; }

    .av-actions { flex-direction: column; }
    .av-actions .av-btn { width: 100%; }
}

@media (prefers-reduced-motion: reduce) {
    .av-shell *, .av-shell *::before { transition: none !important; }
}
</style>

<div class="av-shell">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="av-alert av-alert--success" role="status">
            <i class="bi bi-check-circle-fill"></i>
            <span><?= esc(session()->getFlashdata('success')) ?></span>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="av-alert av-alert--error" role="alert">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
        </div>
    <?php endif; ?>

    <header class="av-head">
        <div>
            <h2 class="av-title">Clinic Availability</h2>
            <p class="av-lede">
                Operating hours, closed weekdays, holidays and the booking horizon.
                Changes apply to future bookings only — appointments already booked are unaffected.
            </p>
        </div>
    </header>

    <dl class="av-summary">
        <div class="av-sum-item">
            <dt><i class="bi bi-sunrise"></i> Open hours</dt>
            <dd><?= esc($pretty($openTime)) ?> – <?= esc($pretty($closeTime)) ?></dd>
        </div>
        <div class="av-sum-item">
            <dt><i class="bi bi-cup-hot"></i> Lunch</dt>
            <dd><?= esc($pretty($lunchFrom)) ?> – <?= esc($pretty($lunchTo)) ?></dd>
        </div>
        <div class="av-sum-item">
            <dt><i class="bi bi-stopwatch"></i> Slot length</dt>
            <dd><?= $interval ?><small>minutes per slot</small></dd>
        </div>
        <div class="av-sum-item">
            <dt><i class="bi bi-calendar-range"></i> Horizon</dt>
            <dd><?= $horizon ?><small>days ahead</small></dd>
        </div>
        <div class="av-sum-item">
            <dt><i class="bi bi-calendar-week"></i> Open days</dt>
            <dd>
                <?= $openDays ?> / 7
                <small><?= $closedNames ? 'Closed ' . esc(implode(', ', array_map($shortDay, $closedNames))) : 'Open every day' ?></small>
            </dd>
        </div>
        <div class="av-sum-item">
            <dt><i class="bi bi-calendar-x"></i> Holidays</dt>
            <dd><?= count($holidays) ?><small>dates on file</small></dd>
        </div>
    </dl>

    <div class="av-grid">

        <div class="av-col">

            <form method="post" action="<?= base_url('admin/availability/save') ?>" id="settingsForm" novalidate>

                <?= csrf_field() ?>

                <section class="av-card">
                    <header class="av-card-head">
                        <div>
                            <h3><i class="bi bi-clock-history"></i> Operating hours</h3>
                            <p>When the clinic accepts appointments each working day.</p>
                        </div>
                    </header>

                    <div class="av-card-body">

                        <fieldset class="av-fieldset">
                            <legend>Clinic hours</legend>
                            <div class="av-pair">
                                <div class="av-row">
                                    <label for="clinic_open_time">Opens at</label>
                                    <input type="time" id="clinic_open_time" name="clinic_open_time"
                                           class="av-input" value="<?= esc($openTime, 'attr') ?>" required>
                                </div>
                                <span class="av-pair-sep">to</span>
                                <div class="av-row">
                                    <label for="clinic_close_time">Closes at</label>
                                    <input type="time" id="clinic_close_time" name="clinic_close_time"
                                           class="av-input" value="<?= esc($closeTime, 'attr') ?>" required>
                                </div>
                            </div>
                            <p class="av-field-error" id="hoursError" role="alert"></p>
                        </fieldset>

                        <fieldset class="av-fieldset">
                            <legend>Lunch break</legend>
                            <div class="av-pair">
                                <div class="av-row">
                                    <label for="lunch_start">Starts</label>
                                    <input type="time" id="lunch_start" name="lunch_start"
                                           class="av-input" value="<?= esc($lunchFrom, 'attr') ?>" required>
                                </div>
                                <span class="av-pair-sep">to</span>
                                <div class="av-row">
                                    <label for="lunch_end">Ends</label>
                                    <input type="time" id="lunch_end" name="lunch_end"
                                           class="av-input" value="<?= esc($lunchTo, 'attr') ?>" required>
                                </div>
                            </div>
                            <p class="av-hint">No slots are generated during this window.</p>
                            <p class="av-field-error" id="lunchError" role="alert"></p>
                        </fieldset>

                        <div class="av-inline">
                            <div class="av-row">
                                <label for="slot_interval">Slot interval</label>
                                <select id="slot_interval" name="slot_interval" class="av-select">
                                    <?php foreach ($intervals as $i): ?>
                                        <option value="<?= (int) $i ?>" <?= $interval === (int) $i ? 'selected' : '' ?>>
                                            <?= (int) $i ?> minutes
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="av-hint" id="slotEstimate"></p>
                            </div>

                            <div class="av-row">
                                <label for="booking_horizon">Booking horizon</label>
                                <input type="number" id="booking_horizon" name="booking_horizon"
                                       class="av-input" min="1" max="365"
                                       value="<?= $horizon ?>" required>
                                <p class="av-hint">Days ahead patients may book — <code>1</code> to <code>365</code>.</p>
                                <p class="av-field-error" id="horizonError" role="alert"></p>
                            </div>
                        </div>

                    </div>
                </section>

                <section class="av-card" style="margin-top:1.25rem;">
                    <header class="av-card-head">
                        <div>
                            <h3><i class="bi bi-calendar-week"></i> Closed weekdays</h3>
                            <p>Tap a day to close it. Highlighted days are closed every week.</p>
                        </div>
                    </header>

                    <div class="av-card-body">
                        <div class="av-weekdays" id="weekdayChips">
                            <?php foreach ($weekdayLabels as $num => $label): ?>
                                <label class="av-weekday">
                                    <input type="checkbox" name="closed_weekdays[]" value="<?= (int) $num ?>"
                                           <?= in_array((int) $num, $closedWeekdays, true) ? 'checked' : '' ?>>
                                    <i class="bi bi-x-circle" aria-hidden="true"></i>
                                    <?= esc($label) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="av-hint" id="weekdaySummary"></p>
                    </div>
                </section>

                <section class="av-card" style="margin-top:1.25rem;">
                    <header class="av-card-head">
                        <div>
                            <h3><i class="bi bi-calendar-x"></i> Holidays</h3>
                            <p>One-off closures. Patients cannot book on these dates.</p>
                        </div>
                    </header>

                    <div class="av-card-body">
                        <div class="av-holiday-add">
                            <div class="av-row" style="margin-bottom:0;">
                                <label for="holidayPicker">Add a date</label>
                                <input type="date" id="holidayPicker" class="av-input">
                            </div>
                            <button type="button" class="av-btn" id="holidayAdd" style="height:38px;">
                                <i class="bi bi-plus-lg"></i> Add
                            </button>
                        </div>

                        <p class="av-field-error" id="holidayError" role="alert"></p>

                        <div class="av-chips" id="holidayChips"></div>

                        <details class="av-disclosure">
                            <summary>Edit as text (one <code>YYYY-MM-DD</code> per line)</summary>
                            <div class="av-row" style="margin-top:.6rem;">
                                <label for="holidays" class="visually-hidden">Holidays</label>
                                <textarea id="holidays" name="holidays" class="av-textarea"
                                          placeholder="2026-12-25&#10;2026-12-31"><?= esc($holidayLines) ?></textarea>
                                <p class="av-hint">The list above updates when you leave this box.</p>
                            </div>
                        </details>
                    </div>
                </section>

                <div class="av-savebar" id="saveBar" style="margin-top:1.25rem;">
                    <span class="av-savebar-note" id="saveNote">
                        <i class="bi bi-check2-circle"></i> All changes saved
                    </span>
                    <div style="display:flex; gap:.5rem;">
                        <button type="button" class="av-btn" id="resetBtn">Discard changes</button>
                        <button type="submit" class="av-btn av-btn--primary">
                            <i class="bi bi-save"></i> Save settings
                        </button>
                    </div>
                </div>

            </form>

        </div>

        <div class="av-col">

            <section class="av-card">
                <header class="av-card-head">
                    <div>
                        <h3><i class="bi bi-slash-circle"></i> Slot availability</h3>
                        <p>Close a single slot for equipment downtime, or restore one.</p>
                    </div>
                    <div class="av-segment" role="group" aria-label="Mode">
                        <button type="button" id="modeBlock" aria-pressed="true">Block</button>
                        <button type="button" id="modeUnblock" aria-pressed="false">Unblock</button>
                    </div>
                </header>

                <form class="av-card-body" method="post"
                      id="slotForm"
                      action="<?= base_url('admin/availability/block') ?>"
                      data-block-url="<?= base_url('admin/availability/block') ?>"
                      data-unblock-url="<?= base_url('admin/availability/unblock') ?>">
                    <?= csrf_field() ?>

                    <div class="av-inline" style="grid-template-columns: repeat(3, minmax(0,1fr));">
                        <div class="av-row">
                            <label for="slot_date">Date</label>
                            <input type="date" id="slot_date" name="slot_date" class="av-input"
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="av-row">
                            <label for="slot_time">Time</label>
                            <input type="time" id="slot_time" name="slot_time" class="av-input" required>
                        </div>
                        <div class="av-row">
                            <label for="department">Department</label>
                            <select id="department" name="department" class="av-select" required>
                                <option value="lab">Laboratory</option>
                                <option value="xray">X-Ray</option>
                            </select>
                        </div>
                    </div>

                    <div class="av-row" id="reasonRow">
                        <label for="blocked_reason">Reason <span style="text-transform:none; font-weight:500;">(optional)</span></label>
                        <input type="text" id="blocked_reason" name="blocked_reason" class="av-input"
                               maxlength="255" placeholder="e.g. X-Ray machine servicing">
                        <p class="av-hint">Shown to staff only, never to patients.</p>
                    </div>

                    <div class="av-actions">
                        <button type="submit" class="av-btn av-btn--danger" id="slotSubmit">
                            <i class="bi bi-slash-circle"></i> Block slot
                        </button>
                    </div>
                </form>
            </section>

            <section class="av-card">
                <header class="av-card-head">
                    <div>
                        <h3><i class="bi bi-calendar3"></i> Next 14 days</h3>
                        <p>
                            <?php if ($preview): ?>
                                <?= $openCount ?> open · <?= $closedCount ?> closed · <?= $holidayCount ?> holiday<?= $holidayCount === 1 ? '' : 's' ?>
                            <?php else: ?>
                                Read-only capacity preview.
                            <?php endif; ?>
                        </p>
                    </div>
                </header>

                <div class="av-card-body">
                    <?php if (empty($preview)): ?>
                        <p class="av-empty">
                            <i class="bi bi-calendar-x d-block mb-2" style="font-size:1.4rem;"></i>
                            No preview data available.
                        </p>
                    <?php else: ?>

                        <div class="av-legend">
                            <span><i style="background:#1976d2;"></i> Laboratory</span>
                            <span><i style="background:#7c3aed;"></i> X-Ray</span>
                            <span><i style="background:#d97706;"></i> Fully booked</span>
                            <span><i style="background:#fecaca;"></i> Holiday</span>
                        </div>

                        <?php
                            $chunks = array_chunk($preview, 7);
                            $weekNames = ['This week', 'Next week', 'Later'];
                        ?>

                        <div class="av-preview-scroll">
                            <?php foreach ($chunks as $w => $week): ?>
                                <p class="av-week-label"><?= esc($weekNames[$w] ?? 'Later') ?></p>
                                <div class="av-preview">
                                    <?php foreach ($week as $day): ?>
                                        <?php
                                            $isHoliday = ! empty($day['holiday']);
                                            $isWorking = ! empty($day['working']);
                                            $isToday   = ! empty($day['is_today']);

                                            $labTotal   = (int) ($day['lab_total'] ?? 0);
                                            $labBooked  = (int) ($day['lab_booked'] ?? 0);
                                            $xrayTotal  = (int) ($day['xray_total'] ?? 0);
                                            $xrayBooked = (int) ($day['xray_booked'] ?? 0);

                                            $labPct  = $labTotal  > 0 ? min(100, round($labBooked  / $labTotal  * 100)) : 0;
                                            $xrayPct = $xrayTotal > 0 ? min(100, round($xrayBooked / $xrayTotal * 100)) : 0;

                                            $labFull  = $labTotal  > 0 && $labBooked  >= $labTotal;
                                            $xrayFull = $xrayTotal > 0 && $xrayBooked >= $xrayTotal;

                                            $cls = 'av-day';
                                            if (! $isWorking) { $cls .= ' is-closed'; }
                                            if ($isHoliday)   { $cls .= ' is-holiday'; }
                                            if ($isToday)     { $cls .= ' is-today'; }
                                        ?>
                                        <div class="<?= esc($cls, 'attr') ?>">
                                            <div class="av-day-top">
                                                <span class="av-day-label"><?= esc($day['label'] ?? '') ?></span>

                                                <?php if ($isToday): ?>
                                                    <span class="av-day-tag av-day-tag--today">Today</span>
                                                <?php elseif ($isHoliday): ?>
                                                    <span class="av-day-tag av-day-tag--holiday">Holiday</span>
                                                <?php elseif (! $isWorking): ?>
                                                    <span class="av-day-tag av-day-tag--closed">Closed</span>
                                                <?php elseif ($labFull && $xrayFull): ?>
                                                    <span class="av-day-tag av-day-tag--full">Full</span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (! $isWorking): ?>
                                                <p class="av-day-closed-note">
                                                    <?= $isHoliday ? 'Closed for a holiday' : 'Not a working day' ?>
                                                </p>
                                            <?php else: ?>
                                                <div class="av-bar-row av-bar-row--lab<?= $labFull ? ' is-full' : '' ?>">
                                                    <span>Lab</span>
                                                    <span class="av-bar"><span style="width: <?= $labPct ?>%;"></span></span>
                                                    <span><b><?= $labBooked ?></b>/<?= $labTotal ?></span>
                                                </div>
                                                <div class="av-bar-row av-bar-row--xray<?= $xrayFull ? ' is-full' : '' ?>">
                                                    <span>X-Ray</span>
                                                    <span class="av-bar"><span style="width: <?= $xrayPct ?>%;"></span></span>
                                                    <span><b><?= $xrayBooked ?></b>/<?= $xrayTotal ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>
                </div>
            </section>

        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    const $ = function(id) { return document.getElementById(id); };

    const holidayBox    = $('holidays');
    const holidayChips  = $('holidayChips');
    const holidayPicker = $('holidayPicker');
    const holidayError  = $('holidayError');

    const ISO = /^\d{4}-\d{2}-\d{2}$/;

    function readHolidays() {
        return holidayBox.value
            .split('\n')
            .map(function(line) { return line.trim(); })
            .filter(function(line) { return line !== ''; });
    }

    function writeHolidays(list) {
        const unique = list.filter(function(v, i) { return list.indexOf(v) === i; });
        unique.sort();
        holidayBox.value = unique.join('\n');
        renderChips();
        markDirty();
    }

    function prettyDate(iso) {
        const parts = iso.split('-');
        const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        if (isNaN(d.getTime())) return null;
        return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    }

    function renderChips() {
        const list = readHolidays();
        holidayChips.innerHTML = '';

        if (list.length === 0) {
            const empty = document.createElement('span');
            empty.className = 'av-chips-empty';
            empty.textContent = 'No holidays set.';
            holidayChips.appendChild(empty);
            return;
        }

        const today = new Date().toISOString().slice(0, 10);

        list.forEach(function(iso) {
            const chip = document.createElement('span');
            chip.className = 'av-chip' + (iso < today ? ' is-past' : '');

            const label = document.createElement('span');
            const nice = ISO.test(iso) ? prettyDate(iso) : null;

            if (nice) {
                label.innerHTML = nice + ' <small>' + iso + '</small>';
            } else {
                label.textContent = iso + ' (invalid)';
                chip.style.borderColor = '#fecaca';
            }

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.setAttribute('aria-label', 'Remove ' + iso);
            remove.innerHTML = '<i class="bi bi-x-lg"></i>';
            remove.addEventListener('click', function() {
                writeHolidays(readHolidays().filter(function(v) { return v !== iso; }));
            });

            chip.appendChild(label);
            chip.appendChild(remove);
            holidayChips.appendChild(chip);
        });
    }

    $('holidayAdd').addEventListener('click', function() {
        const value = holidayPicker.value;

        if (!ISO.test(value)) {
            holidayError.textContent = 'Pick a date first.';
            holidayError.classList.add('show');
            return;
        }
        if (readHolidays().indexOf(value) !== -1) {
            holidayError.textContent = 'That date is already on the list.';
            holidayError.classList.add('show');
            return;
        }

        holidayError.classList.remove('show');
        writeHolidays(readHolidays().concat([value]));
        holidayPicker.value = '';
    });

    holidayBox.addEventListener('blur', renderChips);
    renderChips();

    const weekdayChips = $('weekdayChips');
    const weekdaySummary = $('weekdaySummary');

    function updateWeekdaySummary() {
        const checked = weekdayChips.querySelectorAll('input:checked');
        const names = Array.prototype.map.call(checked, function(input) {
            return input.parentNode.textContent.trim();
        });

        weekdaySummary.textContent = names.length === 0
            ? 'The clinic is open all 7 days.'
            : 'Closed every ' + names.join(', ') + ' — open ' + (7 - names.length) + ' day' + (7 - names.length === 1 ? '' : 's') + ' a week.';
    }

    weekdayChips.addEventListener('change', function() {
        updateWeekdaySummary();
        updateSlotEstimate();
    });

    updateWeekdaySummary();

    const openEl    = $('clinic_open_time');
    const closeEl   = $('clinic_close_time');
    const lunchFrom = $('lunch_start');
    const lunchTo   = $('lunch_end');
    const intervalEl = $('slot_interval');
    const horizonEl  = $('booking_horizon');

    function minutes(hhmm) {
        if (!hhmm) return null;
        const p = hhmm.split(':');
        return (Number(p[0]) * 60) + Number(p[1]);
    }

    function setError(id, message) {
        const box = $(id);
        box.textContent = message || '';
        box.classList.toggle('show', Boolean(message));
        return !message;
    }

    function validateTimes() {
        const o = minutes(openEl.value);
        const c = minutes(closeEl.value);
        const ls = minutes(lunchFrom.value);
        const le = minutes(lunchTo.value);

        let ok = true;

        if (o === null || c === null) {
            ok = setError('hoursError', 'Both opening and closing times are required.') && ok;
        } else if (c <= o) {
            ok = setError('hoursError', 'Closing time must be later than the opening time.') && ok;
        } else {
            setError('hoursError', '');
        }

        if (ls !== null && le !== null) {
            if (le <= ls) {
                ok = setError('lunchError', 'Lunch must end after it starts.') && ok;
            } else if (o !== null && c !== null && (ls < o || le > c)) {
                ok = setError('lunchError', 'The lunch break has to fall inside the clinic hours.') && ok;
            } else {
                setError('lunchError', '');
            }
        }

        const horizon = parseInt(horizonEl.value, 10);
        if (isNaN(horizon) || horizon < 1 || horizon > 365) {
            ok = setError('horizonError', 'Enter a number between 1 and 365.') && ok;
        } else {
            setError('horizonError', '');
        }

        return ok;
    }

    function updateSlotEstimate() {
        const o = minutes(openEl.value);
        const c = minutes(closeEl.value);
        const ls = minutes(lunchFrom.value);
        const le = minutes(lunchTo.value);
        const step = parseInt(intervalEl.value, 10) || 60;
        const estimate = $('slotEstimate');

        if (o === null || c === null || c <= o) {
            estimate.textContent = '';
            return;
        }

        let span = c - o;
        if (ls !== null && le !== null && le > ls) span -= Math.max(0, le - ls);

        const perDay = Math.max(0, Math.floor(span / step));
        const openDays = 7 - weekdayChips.querySelectorAll('input:checked').length;

        estimate.textContent = 'About ' + perDay + ' slot' + (perDay === 1 ? '' : 's') +
                               ' per day, ' + (perDay * openDays) + ' per week.';
    }

    [openEl, closeEl, lunchFrom, lunchTo, intervalEl, horizonEl].forEach(function(el) {
        el.addEventListener('change', function() {
            validateTimes();
            updateSlotEstimate();
        });
        el.addEventListener('input', updateSlotEstimate);
    });

    updateSlotEstimate();

    const form     = $('settingsForm');
    const saveBar  = $('saveBar');
    const saveNote = $('saveNote');

    let dirty = false;

    function markDirty() {
        if (dirty) return;
        dirty = true;
        saveBar.classList.add('is-dirty');
        saveNote.innerHTML = '<i class="bi bi-exclamation-circle"></i> Unsaved changes';
    }

    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);

    $('resetBtn').addEventListener('click', function() {
        if (dirty && !confirm('Discard your unsaved changes?')) return;
        form.reset();
        dirty = false;
        saveBar.classList.remove('is-dirty');
        saveNote.innerHTML = '<i class="bi bi-check2-circle"></i> All changes saved';
        renderChips();
        updateWeekdaySummary();
        updateSlotEstimate();
        validateTimes();
    });

    form.addEventListener('submit', function(e) {
        if (!validateTimes()) {
            e.preventDefault();
            const firstError = document.querySelector('.av-field-error.show');
            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        dirty = false;
    });

    window.addEventListener('beforeunload', function(e) {
        if (!dirty) return;
        e.preventDefault();
        e.returnValue = '';
    });

    const slotForm   = $('slotForm');
    const slotSubmit = $('slotSubmit');
    const reasonRow  = $('reasonRow');
    const btnBlock   = $('modeBlock');
    const btnUnblock = $('modeUnblock');

    function setMode(blocking) {
        btnBlock.setAttribute('aria-pressed', blocking ? 'true' : 'false');
        btnUnblock.setAttribute('aria-pressed', blocking ? 'false' : 'true');

        slotForm.action = blocking ? slotForm.dataset.blockUrl : slotForm.dataset.unblockUrl;
        reasonRow.hidden = !blocking;

        slotSubmit.className = 'av-btn ' + (blocking ? 'av-btn--danger' : 'av-btn--primary');
        slotSubmit.innerHTML = blocking
            ? '<i class="bi bi-slash-circle"></i> Block slot'
            : '<i class="bi bi-unlock"></i> Unblock slot';
    }

    btnBlock.addEventListener('click', function() { setMode(true); });
    btnUnblock.addEventListener('click', function() { setMode(false); });

    slotForm.addEventListener('submit', function(e) {
        const blocking = btnBlock.getAttribute('aria-pressed') === 'true';
        const label = blocking ? 'Block' : 'Unblock';
        const dept = $('department').options[$('department').selectedIndex].text;

        if (!confirm(label + ' the ' + $('slot_time').value + ' ' + dept +
                     ' slot on ' + $('slot_date').value + '?')) {
            e.preventDefault();
        }
    });

    validateTimes();
}());
</script>

<?= $this->endSection() ?>