<?= $this->extend('layouts/AppointmentLayout') ?>

<?= $this->section('AppointmentContent') ?>

<?php
/*
 * Test menu. Edit this array to add, remove or rename tests —
 * the services list, the search and the counts all update from it.
 */
$services = [
    'lab' => [
        'title'    => 'Clinical laboratory',
        'subtitle' => 'Blood chemistry, hematology, microscopy and serology',
        'groups'   => [
            ['name' => 'Clinical chemistry', 'tests' => ['Glucose (RBS / FBS)', 'HbA1c', 'Cholesterol', 'Triglycerides', 'HDL / LDL']],
            ['name' => 'Kidney function', 'tests' => ['Creatinine', 'Blood uric acid', 'Blood urea nitrogen (BUN)']],
            ['name' => 'Liver function', 'tests' => ['SGPT / ALT', 'SGOT / AST']],
            ['name' => 'Electrolytes', 'tests' => ['Sodium (Na)', 'Potassium (K)', 'Chloride (Cl)', 'Calcium (Ca)']],
            ['name' => 'Clinical microscopy', 'tests' => ['Urinalysis', 'Fecalysis', 'Semen analysis']],
            ['name' => 'Hematology', 'tests' => ['Complete blood count (CBC)', 'Platelet count', 'ESR', 'Hemoglobin (Hgb)', 'Hematocrit (Hct)', 'Blood typing']],
            ['name' => 'Thyroid panel', 'tests' => ['T3', 'T4', 'FT3', 'FT4', 'TSH']],
            ['name' => 'Rapid card tests', 'tests' => ['HBsAg (Hepatitis B)', 'hCG (Pregnancy test)', 'HIV (IgG / IgM)', 'Salmonella typhi IgG / IgM', 'Syphilis (T. pallidum)']],
        ],
    ],
    'xray' => [
        'title'    => 'X-ray and imaging',
        'subtitle' => 'Chest and full skeletal series',
        'groups'   => [
            ['name' => 'Chest', 'tests' => ['Chest AP', 'Chest APL', 'Thoracic bony cage (TBC)']],
            ['name' => 'Upper extremities', 'tests' => ['Shoulder AP / APL', 'Humerus AP / APL', 'Arm AP only', 'Elbow AP / APL', 'Forearm AP / APL', 'Radius / Ulna AP', 'Wrist AP / APL', 'Hand AP / Metacarpal only', 'Hand AP / Oblique / Hand APD', 'Finger']],
            ['name' => 'Skull and face', 'tests' => ['Skull AP / APL', 'Skull APL / Towne', 'Orbit AP / APL', 'Nasal AP / APL', 'Paranasal (Waters only)', 'Paranasal (Waters, lateral, Caldwell) / PNS', 'Neck / Cervical APL', 'Neck / Cervical APL / Oblique']],
            ['name' => 'Spine and pelvis', 'tests' => ['Cervical APL', 'Thoracic vertebrae AP / APL', 'Thoracolumbar AP / APL', 'Thoracolumbar spine APL / Thigh', 'Lumbosacral AP / APL', 'Lumbosacral APL / Lumbar vertebrae APL', 'Pelvis AP / APL', 'Frog-leg view', 'Whole spine APL']],
            ['name' => 'Lower extremities and abdomen', 'tests' => ['Leg APL / Knee', 'Knee APL', 'One foot AP', 'Foot APL', 'Ankle APL', 'Abdomen plain', 'Abdomen APL / Upright', 'Abdomen upright only']],
        ],
    ],
];

$bookUrl = '/polymedic/public/index.php/appointment/book';
$mapUrl  = 'https://www.google.com/maps/place/Cotabato+Polymedic+and+Diagnostic+Center/@7.1978084,124.241251,17z/data=!3m1!4b1!4m6!3m5!1s0x32563a20c200452b:0x2ec643833068de46!8m2!3d7.1978031!4d124.2438313!16s%2Fg%2F1tltjwz7?entry=ttu&g_ep=EgoyMDI2MDcyNy4wIKXMDSoASAFQAw%3D%3D';

$countTests = static function (array $panel): int {
    return array_sum(array_map(static fn ($g) => count($g['tests']), $panel['groups']));
};
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* =========================================================
       Design tokens — change colours here and the whole page follows
       ========================================================= */
    .pm {
        --pm-ink: #0a2b4e;          /* headings, footer */
        --pm-blue: #0148ca;         /* primary buttons, links, active states */
        --pm-blue-hover: #0139a3;
        --pm-blue-press: #012f86;
        --pm-blue-soft: #e8effc;    /* tinted backgrounds */
        --pm-teal: #04ccab;         /* accent only: bullets, highlights */
        --pm-teal-soft: #d8f7f0;
        --pm-text: #243b53;
        --pm-muted: #5b6b7f;
        --pm-line: #e3e8ef;
        --pm-page: #f5f7fa;
        --pm-white: #ffffff;
        --pm-radius: 8px;
        --pm-nav-h: 68px;
        --pm-bar-h: 76px;
        font-family: "Public Sans", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        color: var(--pm-text);
        -webkit-font-smoothing: antialiased;
        -webkit-tap-highlight-color: transparent;
    }
    .pm *, .pm *::before, .pm *::after { box-sizing: border-box; }
    .pm img { max-width: 100%; height: auto; }
    .pm h1, .pm h2, .pm h3, .pm h4 { color: var(--pm-ink); }
    .pm a { color: var(--pm-blue); }
    @media (prefers-reduced-motion: no-preference) { html { scroll-behavior: smooth; } }
    @media (max-width: 767.98px) { .pm { --pm-nav-h: 60px; } }

    /* =========================================================
       Buttons
       primary  = solid brand blue (main action on light backgrounds)
       outline  = white with blue text (secondary on light backgrounds)
       inverse  = white with blue text (main action on the dark hero)
       ghost    = transparent white outline (secondary on the dark hero)
       ========================================================= */
    .pm .pm-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        min-height: 48px;
        padding: 0 1.375rem;
        border: 1px solid transparent;
        border-radius: var(--pm-radius);
        font-family: inherit;
        font-size: 1rem;
        font-weight: 600;
        line-height: 1;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
        user-select: none;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
    }
    .pm .pm-btn svg { width: 18px; height: 18px; flex-shrink: 0; }
    .pm .pm-btn:focus-visible { outline: 3px solid var(--pm-teal); outline-offset: 2px; }

    .pm .pm-btn-primary { background: var(--pm-blue); color: #fff; box-shadow: 0 1px 2px rgba(1,72,202,.25); }
    .pm .pm-btn-primary:hover { background: var(--pm-blue-hover); color: #fff; }
    .pm .pm-btn-primary:active { background: var(--pm-blue-press); }

    .pm .pm-btn-outline { background: var(--pm-white); color: var(--pm-blue); border-color: #c9d6ea; }
    .pm .pm-btn-outline:hover { background: var(--pm-blue-soft); border-color: var(--pm-blue); color: var(--pm-blue); }

    .pm .pm-btn-inverse { background: #fff; color: var(--pm-blue); }
    .pm .pm-btn-inverse:hover { background: var(--pm-blue-soft); color: var(--pm-blue-hover); }

    .pm .pm-btn-ghost { background: rgba(255,255,255,.06); color: #fff; border-color: rgba(255,255,255,.5); }
    .pm .pm-btn-ghost:hover { background: rgba(255,255,255,.14); border-color: #fff; color: #fff; }

    .pm .pm-btn-sm { min-height: 40px; padding: 0 1rem; font-size: .9375rem; }
    .pm .pm-btn-block { width: 100%; }

    .pm .pm-link {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        min-height: 44px;
        font-weight: 600;
        text-decoration: none;
    }
    .pm .pm-link:hover { text-decoration: underline; text-underline-offset: 4px; }
    .pm .pm-link svg { width: 16px; height: 16px; }

    /* =========================================================
       Navbar
       ========================================================= */
    .pm-nav {
        position: sticky;
        top: 0;
        z-index: 1030;
        background: rgba(255,255,255,.94);
        -webkit-backdrop-filter: saturate(180%) blur(10px);
        backdrop-filter: saturate(180%) blur(10px);
        border-bottom: 1px solid var(--pm-line);
    }
    .pm-nav .pm-nav-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        min-height: var(--pm-nav-h);
    }
    .pm-nav .pm-brand {
        display: flex;
        align-items: center;
        gap: .75rem;
        min-width: 0;
        text-decoration: none;
    }
    .pm-nav .pm-brand img { width: 42px; height: 42px; object-fit: contain; flex-shrink: 0; }
    .pm-nav .pm-brand-name { display: block; font-size: 1.125rem; font-weight: 700; line-height: 1.2; color: var(--pm-ink); }
    .pm-nav .pm-brand-tag {
        display: block;
        font-size: .8125rem;
        line-height: 1.3;
        color: var(--pm-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pm-nav .pm-nav-links { display: flex; align-items: center; gap: .25rem; }
    .pm-nav .pm-nav-links a:not(.pm-btn) {
        display: inline-flex;
        align-items: center;
        min-height: 40px;
        padding: 0 .875rem;
        border-radius: var(--pm-radius);
        font-size: .9375rem;
        font-weight: 500;
        color: var(--pm-text);
        text-decoration: none;
    }
    .pm-nav .pm-nav-links a:not(.pm-btn):hover { background: var(--pm-page); color: var(--pm-ink); }
    .pm-nav .pm-nav-links .pm-btn { margin-left: .5rem; }
    @media (max-width: 767.98px) {
        .pm-nav .pm-nav-links { display: none; }
        .pm-nav .pm-brand img { width: 36px; height: 36px; }
    }

    /* =========================================================
       Hero
       ========================================================= */
    .pm-hero .pm-hero-inner {
        max-width: 660px;
        margin: 0 auto;
        padding: clamp(3rem, 10vw, 6rem) 0;
        text-align: center;
    }
    .pm-hero h1 {
        margin: 0 0 1rem;
        font-size: clamp(2rem, 5.5vw, 3.25rem);
        font-weight: 700;
        line-height: 1.1;
        letter-spacing: -.02em;
        color: #fff;
        text-wrap: balance;
    }
    .pm-hero .pm-hero-lead {
        margin: 0 auto 1.25rem;
        max-width: 34em;
        font-size: clamp(1rem, 2.2vw, 1.1875rem);
        line-height: 1.55;
        color: rgba(255,255,255,.85);
    }
    .pm-hero .pm-address {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        min-height: 44px;
        margin-bottom: 1.5rem;
        padding: 0 .875rem;
        border-radius: 999px;
        background: rgba(255,255,255,.1);
        font-size: .9375rem;
        color: #fff;
        text-decoration: none;
    }
    .pm-hero .pm-address:hover { background: rgba(255,255,255,.18); color: #fff; }
    .pm-hero .pm-address svg { width: 16px; height: 16px; }
    .pm-hero .pm-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; }
    .pm-hero .pm-hero-link { margin-top: 1rem; }
    .pm-hero .pm-hero-link a { color: #fff; }
    @media (max-width: 575.98px) {
        .pm-hero .pm-actions { flex-direction: column; }
        .pm-hero .pm-actions .pm-btn { width: 100%; }
    }

    /* =========================================================
       Services
       ========================================================= */
    .pm-services {
        background: var(--pm-page);
        padding: clamp(3rem, 8vw, 5.5rem) 0;
        scroll-margin-top: var(--pm-nav-h);
    }
    .pm-section-head { max-width: 640px; margin-bottom: 1.75rem; }
    .pm-section-head h2,
    .pm-howto h2 {
        margin: 0 0 .5rem;
        font-size: clamp(1.75rem, 4vw, 2.5rem);
        font-weight: 700;
        letter-spacing: -.015em;
        line-height: 1.15;
    }
    .pm-section-head p { margin: 0; font-size: 1.0625rem; line-height: 1.6; color: var(--pm-muted); }

    .pm-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin: 0 -1rem 1rem;
        padding: .75rem 1rem;
        background: var(--pm-page);
    }
    @media (min-width: 768px) {
        .pm-toolbar {
            position: sticky;
            top: var(--pm-nav-h);
            z-index: 5;
        }
    }

    .pm-tabs {
        display: inline-flex;
        padding: 4px;
        gap: 4px;
        background: #e4eaf2;
        border-radius: 10px;
    }
    .pm-tab {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        min-height: 42px;
        padding: 0 1.1rem;
        border: 0;
        border-radius: 7px;
        background: transparent;
        font: inherit;
        font-size: .9375rem;
        font-weight: 600;
        color: var(--pm-muted);
        cursor: pointer;
        transition: background-color .15s ease, color .15s ease;
    }
    .pm-tab:hover { color: var(--pm-ink); }
    .pm-tab[aria-selected="true"] { background: var(--pm-white); color: var(--pm-blue); box-shadow: 0 1px 3px rgba(10,43,78,.12); }
    .pm-tab:focus-visible { outline: 3px solid var(--pm-teal); outline-offset: 1px; }
    .pm-tab svg { width: 18px; height: 18px; }
    .pm-count {
        min-width: 1.75rem;
        padding: .15rem .45rem;
        border-radius: 999px;
        background: rgba(255,255,255,.7);
        font-size: .75rem;
        font-variant-numeric: tabular-nums;
        text-align: center;
    }
    .pm-tab[aria-selected="true"] .pm-count { background: var(--pm-blue-soft); }

    .pm-search { position: relative; flex: 0 1 340px; margin: 0; }
    .pm-search input {
        width: 100%;
        min-height: 48px;
        padding: 0 2.75rem;
        border: 1px solid #d3dce8;
        border-radius: var(--pm-radius);
        background: var(--pm-white);
        font: inherit;
        font-size: 1rem; /* 16px stops iOS from zooming on focus */
        color: var(--pm-ink);
        -webkit-appearance: none;
        appearance: none;
    }
    .pm-search input::-webkit-search-cancel-button { -webkit-appearance: none; }
    .pm-search input::placeholder { color: #8a97a8; }
    .pm-search input:focus { outline: none; border-color: var(--pm-blue); box-shadow: 0 0 0 3px var(--pm-blue-soft); }
    .pm-search .pm-search-icon {
        position: absolute; left: 1rem; top: 50%;
        width: 18px; height: 18px;
        transform: translateY(-50%);
        color: #8a97a8;
        pointer-events: none;
    }
    .pm-search .pm-search-clear {
        position: absolute; right: 4px; top: 50%;
        display: grid; place-items: center;
        width: 40px; height: 40px;
        transform: translateY(-50%);
        border: 0; border-radius: 6px;
        background: transparent;
        color: var(--pm-muted);
        cursor: pointer;
    }
    .pm-search .pm-search-clear:hover { background: var(--pm-page); color: var(--pm-ink); }
    .pm-search .pm-search-clear:focus-visible { outline: 3px solid var(--pm-teal); }
    .pm-search .pm-search-clear[hidden] { display: none; }
    .pm-search .pm-search-clear svg { width: 16px; height: 16px; }

    @media (max-width: 767.98px) {
        .pm-toolbar { flex-direction: column; align-items: stretch; margin-bottom: .5rem; padding-top: 0; }
        .pm-tabs { display: flex; }
        .pm-tab { flex: 1; padding: 0 .5rem; }
        .pm-search { flex-basis: auto; }
    }

    .pm-panel {
        background: var(--pm-white);
        border: 1px solid var(--pm-line);
        border-radius: 12px;
        overflow: hidden;
    }
    .pm-panel[hidden] { display: none; }
    .pm-panel-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .5rem 1rem;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--pm-line);
        background: linear-gradient(var(--pm-blue-soft), var(--pm-blue-soft)) left / 4px 100% no-repeat, var(--pm-white);
    }
    .pm-panel-head h3 { margin: 0 0 .125rem; font-size: 1.25rem; font-weight: 700; }
    .pm-panel-head p { margin: 0; font-size: .9375rem; color: var(--pm-muted); }
    .pm-panel-tools { display: none; }

    /* Category rows: label left, tests right on tablet/desktop */
    .pm-group {
        display: grid;
        grid-template-columns: 230px 1fr;
        gap: .75rem 2rem;
        padding: 1.25rem 1.5rem;
        border-top: 1px solid var(--pm-line);
    }
    .pm-panel-head + .pm-group { border-top: 0; }
    .pm-group[hidden] { display: none; }
    .pm-group h4 { margin: 0; font-size: 1rem; font-weight: 600; line-height: 1.4; }
    .pm-group-toggle {
        display: block;
        width: 100%;
        padding: 0;
        border: 0;
        background: none;
        font: inherit;
        color: inherit;
        text-align: left;
        cursor: default;
    }
    .pm-group-toggle .pm-group-count {
        display: block;
        margin-top: .125rem;
        font-size: .8125rem;
        font-weight: 400;
        color: var(--pm-muted);
        font-variant-numeric: tabular-nums;
    }
    .pm-group-toggle .pm-chevron { display: none; }

    .pm-tests {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: .5rem 1.5rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .pm-tests li { position: relative; padding-left: 1rem; font-size: .96875rem; line-height: 1.45; }
    .pm-tests li::before {
        content: "";
        position: absolute; left: 0; top: .6em;
        width: 6px; height: 6px;
        border-radius: 50%;
        background: var(--pm-teal);
    }
    .pm-tests li[hidden] { display: none; }
    .pm-tests mark { padding: 0 2px; background: var(--pm-teal-soft); color: var(--pm-ink); border-radius: 3px; font-weight: 600; }

    /* Phones: categories become an accordion to cut scrolling */
    @media (max-width: 767.98px) {
        .pm-panel-head { padding: 1rem 1.125rem; }
        .pm-panel-tools { display: flex; gap: .25rem; }
        .pm-panel-tools button {
            min-height: 36px;
            padding: 0 .625rem;
            border: 0;
            border-radius: 6px;
            background: transparent;
            font: inherit;
            font-size: .875rem;
            font-weight: 600;
            color: var(--pm-blue);
            cursor: pointer;
        }
        .pm-panel-tools button:hover { background: var(--pm-blue-soft); }
        .pm-group { display: block; padding: 0; }
        .pm-group-toggle {
            display: flex;
            align-items: center;
            gap: .75rem;
            min-height: 56px;
            padding: .75rem 1.125rem;
            cursor: pointer;
        }
        .pm-group-toggle:focus-visible { outline: 3px solid var(--pm-teal); outline-offset: -3px; }
        .pm-group-toggle:active { background: var(--pm-page); }
        .pm-group-toggle .pm-group-name { flex: 1; }
        .pm-group-toggle .pm-group-count {
            display: inline-block;
            margin: 0;
            padding: .15rem .5rem;
            border-radius: 999px;
            background: var(--pm-page);
            font-size: .75rem;
        }
        .pm-group-toggle .pm-chevron {
            display: block;
            width: 18px; height: 18px;
            color: var(--pm-muted);
            transition: transform .2s ease;
        }
        .pm-group:not(.is-collapsed) .pm-chevron { transform: rotate(180deg); }
        .pm-tests {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: .625rem 1rem;
            padding: 0 1.125rem 1.125rem;
        }
        .pm-group.is-collapsed .pm-tests { display: none; }
        .pm-panel.is-searching .pm-group.is-collapsed .pm-tests { display: grid; }
        .pm-panel.is-searching .pm-chevron { visibility: hidden; }
    }
    @media (max-width: 359.98px) {
        .pm-tests { grid-template-columns: 1fr; }
    }

    .pm-empty { padding: 2.5rem 1.5rem; text-align: center; color: var(--pm-muted); }
    .pm-empty[hidden] { display: none; }
    .pm-empty strong { display: block; margin-bottom: .25rem; font-size: 1.0625rem; color: var(--pm-ink); }
    .pm-empty p { margin: 0 0 1rem; }

    .pm-cta-band {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem 1.5rem;
        margin-top: 1.5rem;
        padding: 1.25rem 1.5rem;
        border-radius: 12px;
        background: var(--pm-ink);
        color: rgba(255,255,255,.82);
    }
    .pm-cta-band strong { display: block; margin-bottom: .125rem; font-size: 1.0625rem; color: #fff; }
    .pm-cta-band p { margin: 0; max-width: 38em; font-size: .9375rem; line-height: 1.5; }
    @media (max-width: 575.98px) {
        .pm-cta-band { padding: 1.25rem; }
        .pm-cta-band .pm-btn { width: 100%; }
    }

    /* =========================================================
       How it works
       ========================================================= */
    .pm-howto { padding: clamp(3rem, 8vw, 5.5rem) 0; background: var(--pm-white); scroll-margin-top: var(--pm-nav-h); }
    .pm-howto .pm-intro { margin: 0 0 1.75rem; max-width: 36em; font-size: 1.0625rem; line-height: 1.6; color: var(--pm-muted); }
    .pm-steps { margin: 0 0 1.75rem; padding: 0; list-style: none; counter-reset: step; }
    .pm-steps li { position: relative; padding: 0 0 1.5rem 3.25rem; counter-increment: step; line-height: 1.55; }
    .pm-steps li::before {
        content: counter(step);
        position: absolute; left: 0; top: -.15rem;
        display: grid; place-items: center;
        width: 2.125rem; height: 2.125rem;
        border-radius: 50%;
        background: var(--pm-blue);
        color: #fff;
        font-weight: 700;
        font-size: .9375rem;
    }
    .pm-steps li:not(:last-child)::after {
        content: "";
        position: absolute; left: 1.0625rem; top: 2.25rem; bottom: .25rem;
        width: 2px;
        background: var(--pm-blue-soft);
    }
    .pm-steps strong { display: block; font-weight: 600; color: var(--pm-ink); }
    .pm-steps span { font-size: .96875rem; color: var(--pm-muted); }
    .pm-howto .pm-illustration { display: block; width: 100%; max-width: 420px; margin: 0 auto; border-radius: 50%; }
    @media (max-width: 991.98px) { .pm-howto .pm-illustration { max-width: 280px; } }
    @media (max-width: 575.98px) { .pm-howto .pm-actions-row .pm-btn { width: 100%; } }

    /* =========================================================
       Footer
       ========================================================= */
    .pm-footer { padding: 2.5rem 0 1.5rem; background: var(--pm-ink); color: rgba(255,255,255,.75); font-size: .9375rem; }
    .pm-footer .pm-footer-grid {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 1.5rem 3rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,.12);
    }
    .pm-footer .pm-footer-name { display: block; margin-bottom: .25rem; font-size: 1.0625rem; font-weight: 700; color: #fff; }
    .pm-footer a { color: #fff; text-decoration: none; }
    .pm-footer a:hover { text-decoration: underline; text-underline-offset: 3px; }
    .pm-footer ul { display: flex; flex-wrap: wrap; gap: .25rem 1.5rem; margin: 0; padding: 0; list-style: none; }
    .pm-footer ul a { display: inline-flex; align-items: center; min-height: 44px; }
    .pm-footer .pm-legal { margin: 1.25rem 0 0; font-size: .8125rem; color: rgba(255,255,255,.6); }

    /* =========================================================
       Mobile bottom booking bar
       ========================================================= */
    .pm-mobile-bar { display: none; }
    @media (max-width: 767.98px) {
        .pm { padding-bottom: calc(var(--pm-bar-h) + env(safe-area-inset-bottom, 0px)); }
        .pm-mobile-bar {
            position: fixed;
            left: 0; right: 0; bottom: 0;
            z-index: 1040;
            display: block;
            padding: .75rem 1rem calc(.75rem + env(safe-area-inset-bottom, 0px));
            background: rgba(255,255,255,.96);
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
            border-top: 1px solid var(--pm-line);
            box-shadow: 0 -4px 16px rgba(10,43,78,.06);
            transition: transform .25s ease;
        }
        .pm-mobile-bar.is-hidden { transform: translateY(110%); }
    }

    @media (prefers-reduced-motion: reduce) {
        .pm *, .pm *::before, .pm *::after { transition: none !important; }
    }
</style>

<?php
/* Small inline icons so the page doesn't depend on an icon font version */
$icon = [
    'pin'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>',
    'cal'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>',
    'down'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>',
    'search'  => '<svg class="pm-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
    'x'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>',
    'flask'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 3h6M10 3v6L4.5 18.5A2 2 0 0 0 6.2 21.5h11.6a2 2 0 0 0 1.7-3L14 9V3"/><path d="M7 15h10"/></svg>',
    'scan'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3"/><path d="M12 8v8M9 10h6M9 14h6"/></svg>',
];
$tabIcon  = ['lab' => $icon['flask'], 'xray' => $icon['scan']];
$tabLabel = ['lab' => 'Laboratory', 'xray' => 'X-ray'];
?>

<div class="pm">

    <!-- Navbar -->
    <nav class="pm-nav" aria-label="Main">
        <div class="container pm-nav-inner">
            <a class="pm-brand" href="<?= base_url() ?>">
                <img src="/polymedic/public/assets/images/logo4.png" alt="">
                <span>
                    <span class="pm-brand-name">PolyMedic</span>
                    <span class="pm-brand-tag">Diagnostic &amp; Laboratory Center</span>
                </span>
            </a>
            <div class="pm-nav-links">
                <a href="#Services">Tests</a>
                <a href="#Tutorial">How it works</a>
                <a class="pm-btn pm-btn-primary pm-btn-sm" href="<?= $bookUrl ?>">Book appointment</a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="Hero pm-hero">
        <header class="hero-header">
            <div class="container">
                <div class="pm-hero-inner">
                    <h1>Book your lab test or X-ray online</h1>
                    <p class="pm-hero-lead">
                        Pick a date that suits you and skip the wait at the counter.
                        Walk in on your scheduled day and we'll take it from there.
                    </p>
                    <a href="<?= $mapUrl ?>" target="_blank" rel="noopener" class="pm-address">
                        <?= $icon['pin'] ?>
                        Governor Gutierrez Ave, Cotabato City 9600
                    </a>
                    <div class="pm-actions">
                        <a class="pm-btn pm-btn-inverse" href="<?= $bookUrl ?>"><?= $icon['cal'] ?>Book appointment</a>
                        <a class="pm-btn pm-btn-ghost" href="#Services">View tests</a>
                    </div>
                    <div class="pm-hero-link">
                        <a class="pm-link" href="#Tutorial">How booking works</a>
                    </div>
                </div>
            </div>
        </header>
    </section>

    <!-- Services -->
    <section id="Services" class="pm-services">
        <div class="container">
            <div class="pm-section-head">
                <h2>Tests and services</h2>
                <p>Every laboratory test and X-ray we offer. Search by name or abbreviation, like CBC or chest.</p>
            </div>

            <div class="pm-toolbar">
                <div class="pm-tabs" role="tablist" aria-label="Service type">
                    <?php $first = true; foreach ($services as $key => $panel): ?>
                        <button type="button" class="pm-tab" role="tab"
                                id="tab-<?= $key ?>"
                                aria-controls="panel-<?= $key ?>"
                                aria-selected="<?= $first ? 'true' : 'false' ?>"
                                tabindex="<?= $first ? '0' : '-1' ?>">
                            <?= $tabIcon[$key] ?>
                            <?= $tabLabel[$key] ?>
                            <span class="pm-count" data-count-for="<?= $key ?>"><?= $countTests($panel) ?></span>
                        </button>
                    <?php $first = false; endforeach; ?>
                </div>

                <div class="pm-search">
                    <label for="pmTestSearch" class="visually-hidden">Search tests</label>
                    <?= $icon['search'] ?>
                    <input type="search" id="pmTestSearch" placeholder="Search tests" autocomplete="off" enterkeyhint="search">
                    <button type="button" class="pm-search-clear" aria-label="Clear search" hidden><?= $icon['x'] ?></button>
                </div>
            </div>

            <?php $first = true; foreach ($services as $key => $panel): ?>
                <div class="pm-panel" id="panel-<?= $key ?>" role="tabpanel" aria-labelledby="tab-<?= $key ?>" data-key="<?= $key ?>" <?= $first ? '' : 'hidden' ?>>
                    <div class="pm-panel-head">
                        <div>
                            <h3><?= esc($panel['title']) ?></h3>
                            <p><?= esc($panel['subtitle']) ?></p>
                        </div>
                        <div class="pm-panel-tools">
                            <button type="button" data-expand="all">Expand all</button>
                            <button type="button" data-expand="none">Collapse all</button>
                        </div>
                    </div>

                    <?php foreach ($panel['groups'] as $i => $group):
                        $gid = $key . '-g' . $i;
                        $n   = count($group['tests']); ?>
                        <div class="pm-group<?= $i === 0 ? '' : ' is-collapsed' ?>">
                            <h4>
                                <button type="button" class="pm-group-toggle" aria-controls="<?= $gid ?>">
                                    <span class="pm-group-name"><?= esc($group['name']) ?></span>
                                    <span class="pm-group-count" data-group-count><?= $n ?> <?= $n === 1 ? 'test' : 'tests' ?></span>
                                    <span class="pm-chevron"><?= $icon['down'] ?></span>
                                </button>
                            </h4>
                            <ul class="pm-tests" id="<?= $gid ?>">
                                <?php foreach ($group['tests'] as $test): ?>
                                    <li data-name="<?= esc($test) ?>"><?= esc($test) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>

                    <div class="pm-empty" hidden>
                        <strong>No matching tests</strong>
                        <p>Check the spelling or try a shorter term.</p>
                        <button type="button" class="pm-btn pm-btn-outline pm-btn-sm pm-empty-switch" hidden></button>
                    </div>
                </div>
            <?php $first = false; endforeach; ?>

            <div class="pm-cta-band">
                <div>
                    <strong>Don't see your test?</strong>
                    <p>Bring your doctor's request on your visit and our staff will confirm if we can run it.</p>
                </div>
                <a class="pm-btn pm-btn-inverse" href="<?= $bookUrl ?>"><?= $icon['cal'] ?>Book appointment</a>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section id="Tutorial" class="Tutorial pm-howto">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6 order-lg-2">
                    <img class="pm-illustration" src="/polymedic/public/assets/images/Appointment2.png" alt="Patient booking an appointment on a phone" loading="lazy">
                </div>
                <div class="col-lg-6 order-lg-1">
                    <h2>How to book an appointment</h2>
                    <p class="pm-intro">It takes about two minutes. You'll get a confirmation once your booking is saved.</p>
                    <ol class="pm-steps">
                        <li><strong>Open the booking page</strong><span>Tap Book appointment anywhere on this page.</span></li>
                        <li><strong>Choose your date</strong><span>Pick the day that works best for you.</span></li>
                        <li><strong>Enter your details</strong><span>Add your personal information and any special requests.</span></li>
                        <li><strong>Review and confirm</strong><span>Check everything is correct, then confirm your booking.</span></li>
                    </ol>
                    <div class="pm-actions-row">
                        <a class="pm-btn pm-btn-primary" href="<?= $bookUrl ?>"><?= $icon['cal'] ?>Book appointment</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="pm-footer">
        <div class="container">
            <div class="pm-footer-grid">
                <div>
                    <span class="pm-footer-name">PolyMedic</span>
                    Diagnostic &amp; Laboratory Center<br>
                    <a href="<?= $mapUrl ?>" target="_blank" rel="noopener">Governor Gutierrez Ave, Cotabato City 9600</a>
                </div>
                <ul>
                    <li><a href="#Services">Tests and services</a></li>
                    <li><a href="#Tutorial">How it works</a></li>
                    <li><a href="<?= $bookUrl ?>">Book appointment</a></li>
                </ul>
            </div>
            <p class="pm-legal">&copy; <?= date('Y') ?> PolyMedic Diagnostic &amp; Laboratory Center. All rights reserved.</p>
        </div>
    </footer>

    <!-- Phones only: booking button always within thumb reach -->
    <div class="pm-mobile-bar" id="pmMobileBar">
        <a class="pm-btn pm-btn-primary pm-btn-block" href="<?= $bookUrl ?>"><?= $icon['cal'] ?>Book appointment</a>
    </div>

</div>

<script>
(function () {
    var $  = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

    var tabs   = $$('.pm-tab');
    var panels = $$('.pm-panel');
    var search = $('#pmTestSearch');
    var clear  = $('.pm-search-clear');
    var mobile = window.matchMedia('(max-width: 767.98px)');
    var label  = { lab: 'Laboratory', xray: 'X-ray' };
    if (!tabs.length || !search) return;

    /* ---------- Tabs ---------- */
    function selectTab(tab, focus) {
        tabs.forEach(function (t) {
            var on = t === tab;
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
            document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
        });
        if (focus) tab.focus();
    }
    tabs.forEach(function (tab, i) {
        tab.addEventListener('click', function () { selectTab(tab); });
        tab.addEventListener('keydown', function (e) {
            var next = null;
            if (e.key === 'ArrowRight') next = tabs[(i + 1) % tabs.length];
            if (e.key === 'ArrowLeft')  next = tabs[(i - 1 + tabs.length) % tabs.length];
            if (next) { e.preventDefault(); selectTab(next, true); }
        });
    });

    /* ---------- Accordion (phones only) ---------- */
    var groups = $$('.pm-group');
    function syncAccordion() {
        groups.forEach(function (g) {
            var btn = $('.pm-group-toggle', g);
            if (mobile.matches) {
                btn.removeAttribute('tabindex');
                btn.setAttribute('aria-expanded', g.classList.contains('is-collapsed') ? 'false' : 'true');
            } else {
                btn.setAttribute('tabindex', '-1');
                btn.removeAttribute('aria-expanded');
            }
        });
    }
    function setGroup(g, open) {
        g.classList.toggle('is-collapsed', !open);
        if (mobile.matches) $('.pm-group-toggle', g).setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    groups.forEach(function (g) {
        $('.pm-group-toggle', g).addEventListener('click', function () {
            if (!mobile.matches || search.value.trim()) return;
            setGroup(g, g.classList.contains('is-collapsed'));
        });
    });
    $$('[data-expand]').forEach(function (b) {
        b.addEventListener('click', function () {
            var open = b.getAttribute('data-expand') === 'all';
            $$('.pm-group', b.closest('.pm-panel')).forEach(function (g) { setGroup(g, open); });
        });
    });
    if (mobile.addEventListener) mobile.addEventListener('change', syncAccordion);
    else mobile.addListener(syncAccordion);
    syncAccordion();

    /* ---------- Search ---------- */
    function escapeHtml(s) {
        return s.replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function filter() {
        var q = search.value.trim().toLowerCase();
        var totals = {};
        clear.hidden = !search.value;

        panels.forEach(function (panel) {
            var total = 0;
            panel.classList.toggle('is-searching', !!q);
            $$('.pm-group', panel).forEach(function (g) {
                var shown = 0;
                $$('li', g).forEach(function (li) {
                    var name = li.getAttribute('data-name');
                    var at   = name.toLowerCase().indexOf(q);
                    var hit  = !q || at !== -1;
                    li.hidden = !hit;
                    if (hit) {
                        shown++;
                        li.innerHTML = q
                            ? escapeHtml(name.slice(0, at)) + '<mark>' + escapeHtml(name.slice(at, at + q.length)) + '</mark>' + escapeHtml(name.slice(at + q.length))
                            : escapeHtml(name);
                    }
                });
                g.hidden = shown === 0;
                $('[data-group-count]', g).textContent = shown + (shown === 1 ? ' test' : ' tests');
                total += shown;
            });
            totals[panel.getAttribute('data-key')] = total;
            $('[data-count-for="' + panel.getAttribute('data-key') + '"]').textContent = total;
        });

        panels.forEach(function (panel) {
            var key   = panel.getAttribute('data-key');
            var empty = $('.pm-empty', panel);
            var sw    = $('.pm-empty-switch', panel);
            var other = Object.keys(totals).filter(function (k) { return k !== key && totals[k] > 0; })[0];
            empty.hidden = totals[key] !== 0;
            sw.hidden = !other;
            if (other) {
                sw.textContent = 'Show ' + totals[other] + ' ' + (totals[other] === 1 ? 'match' : 'matches') + ' in ' + label[other];
                sw.onclick = function () { selectTab(document.getElementById('tab-' + other), true); };
            }
        });
    }
    search.addEventListener('input', filter);
    search.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { search.value = ''; filter(); }
        if (e.key === 'Enter') search.blur(); /* closes the phone keyboard */
    });
    clear.addEventListener('click', function () { search.value = ''; filter(); search.focus(); });

    /* ---------- Mobile booking bar: hide while another Book button is on screen ---------- */
    var bar = $('#pmMobileBar');
    if (bar && 'IntersectionObserver' in window) {
        var visible = new Set();
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) { en.isIntersecting ? visible.add(en.target) : visible.delete(en.target); });
            bar.classList.toggle('is-hidden', visible.size > 0);
        });
        $$('.pm-hero .pm-btn-inverse, .pm-cta-band .pm-btn, .pm-howto .pm-btn, .pm-footer').forEach(function (el) { io.observe(el); });
    }
})();
</script>

<?= $this->endSection() ?>