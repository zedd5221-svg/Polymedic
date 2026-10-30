<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ============================================================
// AUTH ROUTES (PUBLIC)
// ============================================================
$routes->get('login', 'Auth::login');
$routes->get('admin/login', 'Auth::login');
$routes->post('auth/authenticate', 'Auth::authenticate');
$routes->get('logout', 'Auth::logout');


// ============================================================
// PROTECTED ADMIN ROUTES
// ============================================================
$routes->group('admin', ['filter' => 'auth:admin'], function ($routes) {
    $routes->get('dashboard', 'Admin::dashboard');
    $routes->get('patients', 'Admin::patients');

    // Patient detail view (used by the eye icon on the patients list)
    $routes->get('patient/view/(:num)', 'Admin::viewPatient/$1');
    $routes->post('approve-patient/(:num)', 'Admin::approvePatient/$1');

    // Old link target; redirects to diagnostic-requests
    $routes->get('requests', 'Admin::requests');
    $routes->get('users', 'Admin::users');
    $routes->post('sync-xray', 'Admin::syncXrayExaminations');

    // Appointments
    $routes->get('appointments', 'Admin::appointments');
    $routes->get('appointment/view/(:num)', 'Admin::appointmentView/$1');
    $routes->post('appointment/approve/(:num)', 'Admin::approveAppointment/$1');
    $routes->post('appointment/cancel/(:num)', 'Admin::cancelAppointment/$1');
    $routes->post('appointment/complete/(:num)', 'Admin::completeAppointment/$1');
    $routes->post('appointment/delete/(:num)', 'Admin::deleteAppointment/$1');

    // Diagnostic requests
    $routes->get('diagnostic-requests', 'Admin::diagnosticRequests');
    $routes->post('create-diagnostic-request', 'Admin::createDiagnosticRequest');
    $routes->post('update-diagnostic-status/(:num)/(:any)/(:any)', 'Admin::updateDiagnosticStatus/$1/$2/$3');
    $routes->get('assignment-options/(:num)/(:any)', 'Admin::assignmentOptions/$1/$2');
    $routes->delete('delete-diagnostic-request/(:num)/(:any)', 'Admin::deleteDiagnosticRequest/$1/$2');
    $routes->get('get-request-details/(:num)/(:any)', 'Admin::getRequestDetails/$1/$2');
    $routes->get('print-request/(:num)/(:any)', 'Admin::printRequest/$1/$2');

    // ✅ NEW — Payments page + AJAX helpers
    $routes->get('payments',                   'Admin::payments');
    $routes->get('get-payment-details/(:num)', 'Admin::getPaymentDetails/$1');
    $routes->get('print-receipt/(:num)',       'Admin::printReceipt/$1');
    $routes->post('refund-payment/(:num)',     'Admin::refundPayment/$1');

    // Notifications
    $routes->get('notifications', 'NotificationController::index');
    $routes->get('notifications/fetch', 'NotificationController::fetch');
    $routes->get('notifications/mark-read/(:num)', 'NotificationController::markRead/$1');
    $routes->get('notifications/mark-all-read', 'NotificationController::markAllRead');
    $routes->get('notifications/delete/(:num)', 'NotificationController::delete/$1');
    $routes->post('notifications/delete-batch', 'NotificationController::deleteBatch');

    // Services
    $routes->get('services', 'Admin::services');
    $routes->post('services/create', 'Admin::createService');
    $routes->post('services/update/(:num)', 'Admin::updateService/$1');
    $routes->post('services/delete/(:num)', 'Admin::deleteService/$1');
    $routes->post('services/toggle/(:num)', 'Admin::toggleServiceStatus/$1');

    // Users
    $routes->post('users/create', 'Admin::createUser');
    $routes->post('users/update/(:num)', 'Admin::updateUser/$1');
    $routes->post('users/delete/(:num)', 'Admin::deleteUser/$1');
    $routes->post('users/toggle/(:num)', 'Admin::toggleUserStatus/$1');
    $routes->get('users/data/(:num)', 'Admin::getUserData/$1');
});


// ============================================================
// PROTECTED RECEPTIONIST ROUTES
// ============================================================
$routes->group('receptionist', ['filter' => 'auth:receptionist'], function ($routes) {
    $routes->get('dashboard', 'Receptionist::dashboard');
    $routes->get('patients', 'Receptionist::patients');

    $routes->get('patient/view/(:num)', 'Receptionist::viewPatient/$1');

    // Appointments
    $routes->get('appointments', 'Receptionist::appointments');
    $routes->get('appointment/view/(:num)', 'Receptionist::appointmentView/$1');
    $routes->post('appointment/approve/(:num)', 'Receptionist::approveAppointment/$1');
    $routes->post('appointment/cancel/(:num)', 'Receptionist::cancelAppointment/$1');
    $routes->post('appointment/complete/(:num)', 'Receptionist::completeAppointment/$1');

    // ⬅ EDIT #1 — the old placeholder route pointed at Receptionist::billing.
    //            Now it points at the shared Billing controller so this URL
    //            serves the real invoice list instead of "No bills found".
    $routes->get('billing', 'Billing::index');

    $routes->get('payments', 'Receptionist::payments');
    $routes->get('reports', 'Receptionist::reports');
    $routes->get('reports/revenue', 'Receptionist::revenueReport');

    // Notifications
    $routes->get('notifications', 'NotificationController::receptionistIndex');
    $routes->get('notifications/fetch', 'NotificationController::receptionistFetch');
    $routes->get('notifications/mark-read/(:num)', 'NotificationController::receptionistMarkRead/$1');
    $routes->get('notifications/mark-all-read', 'NotificationController::receptionistMarkAllRead');
    $routes->get('notifications/delete/(:num)', 'NotificationController::receptionistDelete/$1');
    $routes->post('notifications/delete-batch', 'NotificationController::deleteBatch');

    // Diagnostic requests
    $routes->get('diagnostic-requests', 'Receptionist::diagnosticRequests');
    $routes->post('create-diagnostic-request', 'Receptionist::createDiagnosticRequest');
    $routes->post('update-diagnostic-status/(:num)/(:any)/(:any)', 'Receptionist::updateDiagnosticStatus/$1/$2/$3');
    $routes->get('assignment-options/(:num)/(:any)', 'Receptionist::assignmentOptions/$1/$2');
    $routes->delete('delete-diagnostic-request/(:num)/(:any)', 'Receptionist::deleteDiagnosticRequest/$1/$2');
    $routes->get('get-request-details/(:num)/(:any)', 'Receptionist::getRequestDetails/$1/$2');
    $routes->get('print-request/(:num)/(:any)', 'Receptionist::printRequest/$1/$2');

    // Payments AJAX
    $routes->get('get-payment-details/(:num)', 'Receptionist::getPaymentDetails/$1');
    $routes->get('print-receipt/(:num)',       'Receptionist::printReceipt/$1');
    // Refunds are admin-only (see the billing block below)

    // Patient sync (manual tool, POST only)
    $routes->post('sync-walk-in-patients', 'Receptionist::syncWalkInPatients');
});


// ============================================================
// PROTECTED RADIOLOGIST ROUTES
// ============================================================
$routes->group('radiologist', ['filter' => 'auth:radiologist'], function ($routes) {
    $routes->get('dashboard', 'Radiologist::dashboard');
    $routes->get('reports', 'Radiologist::reports');
    $routes->get('reports/print', 'Radiologist::reportPaper');

    // Examinations
    $routes->get('examinations', 'Radiologist::examinations');
    $routes->get('examination/view/(:num)', 'Radiologist::viewExamination/$1');
    $routes->post('examination/upload/(:num)', 'Radiologist::uploadImage/$1');
    $routes->post('examination/remove-image/(:num)', 'Radiologist::removeImage/$1');
    $routes->post('examination/save/(:num)', 'Radiologist::saveFindings/$1');
    $routes->post('examination/save-draft/(:num)', 'Radiologist::saveDraft/$1');
    $routes->post('examination/release/(:num)', 'Radiologist::releaseResult/$1');
    $routes->get('examination/print/(:num)', 'Radiologist::printResult/$1');

    // Patient transfers (radiologist to radiologist)
    $routes->post('transfer/create', 'Radiologist::createTransfer');
    $routes->post('transfer/respond/(:num)', 'Radiologist::respondTransfer/$1');
    $routes->post('transfer/cancel/(:num)', 'Radiologist::cancelTransfer/$1');

    // Notifications
    $routes->get('notifications', 'NotificationController::radiologistIndex');
    $routes->get('notifications/fetch', 'NotificationController::radiologistFetch');
    $routes->get('notifications/mark-read/(:num)', 'NotificationController::radiologistMarkRead/$1');
    $routes->get('notifications/mark-all-read', 'NotificationController::radiologistMarkAllRead');
    $routes->get('notifications/delete/(:num)', 'NotificationController::radiologistDelete/$1');
    $routes->post('notifications/delete-batch', 'NotificationController::deleteBatch');
});


// ============================================================
// PROTECTED MED TECH ROUTES
// ============================================================
$routes->group('medtech', ['filter' => 'auth:med_tech'], function ($routes) {
    $routes->get('dashboard', 'MedTech::dashboard');
    $routes->get('reports', 'MedTech::reports');
    $routes->get('reports/print', 'MedTech::reportPaper');

    // Requests
    $routes->get('requests', 'MedTech::requests');
    $routes->get('request/view/(:num)', 'MedTech::viewRequest/$1');
    $routes->post('request/save-results/(:num)', 'MedTech::saveResults/$1');
    $routes->post('request/save-findings/(:num)', 'MedTech::saveFindings/$1');
    $routes->post('request/release/(:num)', 'MedTech::releaseResult/$1');
    $routes->post('request/release-directly/(:num)', 'MedTech::releaseDirectly/$1');
    $routes->get('request/print/(:num)', 'MedTech::printResult/$1');
    $routes->get('template/(:any)', 'MedTech::getTemplate/$1');

    // Patient transfers (med tech to med tech)
    $routes->post('transfer/create', 'MedTech::createTransfer');
    $routes->post('transfer/respond/(:num)', 'MedTech::respondTransfer/$1');
    $routes->post('transfer/cancel/(:num)', 'MedTech::cancelTransfer/$1');

    // Notifications
    $routes->get('notifications', 'NotificationController::medtechIndex');
    $routes->get('notifications/fetch', 'NotificationController::medtechFetch');
    $routes->get('notifications/mark-read/(:num)', 'NotificationController::medtechMarkRead/$1');
    $routes->get('notifications/mark-all-read', 'NotificationController::medtechMarkAllRead');
    $routes->get('notifications/delete/(:num)', 'NotificationController::medtechDelete/$1');
    $routes->post('notifications/delete-batch', 'NotificationController::deleteBatch');
});


// ============================================================
// REPORT PDF EMAIL — any logged-in staff role (checked in the controller)
// ============================================================
$routes->post('report/email-pdf', 'ReportMail::send');


// ============================================================
// PUBLIC ROUTES (NO AUTH REQUIRED)
// ============================================================
$routes->get('/', 'Appointment::index');
$routes->get('appointment/book', 'Appointment::book');
$routes->post('appointment/submit', 'Appointment::submit');
$routes->get('appointment/success/(:any)', 'Appointment::success/$1');
$routes->get('book-now', 'Appointment::book');


// ============================================================
// AVAILABILITY (APPEND-ONLY, ADDED BY FEATURE)
// ============================================================

// Public slots endpoint (used by the booking wizard).
$routes->get('appointment/slots', 'Appointment::slots');

// Admin — availability management.
$routes->group('admin', ['filter' => 'auth:admin'], function ($routes) {
    $routes->get('availability',           'Admin::availability');
    $routes->get('availability/day',       'Admin::availabilityDay');
    $routes->post('availability/save',     'Admin::saveAvailabilitySettings');
    $routes->post('availability/block',    'Admin::blockAvailabilitySlot');
    $routes->post('availability/unblock',  'Admin::unblockAvailabilitySlot');
});

// Receptionist — availability check + override booking.
$routes->group('receptionist', ['filter' => 'auth:receptionist'], function ($routes) {
    $routes->get('appointment/slots',          'Receptionist::appointmentSlots');
    $routes->post('appointment/override-book', 'Receptionist::overrideBook');
});


// ============================================================
// BILLING — shared controller, role-scoped inside the class
// ============================================================
//
// The controller (App\Controllers\Billing) re-checks the session
// role on every request. The route filters below are the outer
// guard; the inner check is what stops a hand-typed URL from
// reaching admin-only actions when a receptionist is logged in.
//
// Admin gets:    index, create, store, view, pay, void, refund,
//                receipt, reports
// Receptionist:  index, create, store, view, pay, receipt
//
// ⬅ EDIT #2 — entire new block, appended here.
// ============================================================

$routes->group('admin/billing', ['filter' => 'auth:admin'], function ($routes) {
    $routes->get('/',              'Billing::index');
    $routes->get('create',         'Billing::create');
    $routes->post('store',         'Billing::store');
    $routes->get('view/(:num)',    'Billing::view/$1');
    $routes->post('pay/(:num)',    'Billing::recordPayment/$1');
    $routes->post('void/(:num)',   'Billing::void/$1');
    $routes->post('refund/(:num)', 'Billing::refundPayment/$1');
    $routes->post('sync-request/(:num)', 'Billing::syncRequest/$1');   // ← NEW
    $routes->get('receipt/(:num)', 'Billing::receipt/$1');
    $routes->get('reports',        'Billing::reports');
    $routes->get('reports/revenue', 'Billing::revenueReport');
});

$routes->group('receptionist/billing', ['filter' => 'auth:receptionist'], function ($routes) {
    $routes->get('/',              'Billing::index');
    $routes->get('create',         'Billing::create');
    $routes->post('store',         'Billing::store');
    $routes->get('view/(:num)',    'Billing::view/$1');
    $routes->post('pay/(:num)',    'Billing::recordPayment/$1');
    $routes->get('receipt/(:num)', 'Billing::receipt/$1');
    // Intentionally NOT registered: void, refund, reports
});


// ============================================================
// MY PROFILE — shared controller, one set of routes per role
// ============================================================
//
// Each role gets its own prefix so the role's auth filter applies
// and the page renders inside that role's layout.

foreach ([
    'admin'        => 'admin',
    'receptionist' => 'receptionist',
    'radiologist'  => 'radiologist',
    'medtech'      => 'med_tech',
] as $prefix => $role) {
    $routes->group($prefix, ['filter' => 'auth:' . $role], function ($routes) {
        $routes->get('profile',                 'Profile::index');
        $routes->post('profile/update',         'Profile::update');
        $routes->post('profile/password',       'Profile::password');
        $routes->post('profile/avatar',         'Profile::avatar');
        $routes->post('profile/avatar/remove',  'Profile::removeAvatar');
    });
}

// ============================================================
// REMOVED - READ THIS BEFORE PUTTING ANY OF IT BACK
// ============================================================
// Two blocks were deleted from the bottom of the old file.
//
// 1. The "FALLBACK ALIAS" routes:
//        $routes->get('public/admin/dashboard', 'Admin::dashboard');
//        $routes->get('polymedic/public/admin/dashboard', 'Admin::dashboard');
//        $routes->get('public/admin/appointments', 'Admin::appointments');
//        ... and the login/logout variants
//
//    These sat OUTSIDE the admin group, so they carried no filter at
//    all. Anyone who typed /public/admin/dashboard reached the admin
//    dashboard without logging in. They are not needed: base_url()
//    already produces the correct path from App.php.
//
// 2. The "EXTRA ROUTES FOR COMPATIBILITY" block, which re-declared
//    receptionist/diagnostic-requests, receptionist/debug-patients,
//    receptionist/print-request and others WITHOUT the
//    'filter' => 'auth:receptionist' option. CodeIgniter keys routes
//    by their path, so a later declaration replaces the earlier one -
//    those duplicates were stripping the auth filter off routes the
//    group had already protected. Every one of them now lives inside
//    the receptionist group above.
//
// The dev-only test endpoints were dropped too:
//        $routes->get('test', 'Test::index');
//        $routes->get('dbtest', 'DbTest::index');
//    Add them back while developing if you still use them, but keep
//    them out of anything you demo or deploy.