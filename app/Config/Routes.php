<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ===== AUTH ROUTES (PUBLIC) =====
$routes->get('login', 'Auth::login');
$routes->get('admin/login', 'Auth::login');
$routes->post('auth/authenticate', 'Auth::authenticate');
$routes->get('logout', 'Auth::logout');

// ===== PROTECTED ADMIN ROUTES =====
$routes->group('admin', ['filter' => 'auth:admin'], function($routes) {
    $routes->get('dashboard', 'Admin::dashboard');
    $routes->get('patients', 'Admin::patients');
    $routes->get('requests', 'Admin::requests');
    $routes->get('users', 'Admin::users');
    $routes->get('sync-xray', 'Admin::syncXrayExaminations');
    $routes->get('appointments', 'Admin::appointments');
    $routes->get('appointment/view/(:num)', 'Admin::appointmentView/$1');
    $routes->get('appointment/approve/(:num)', 'Admin::approveAppointment/$1');
    $routes->get('appointment/cancel/(:num)', 'Admin::cancelAppointment/$1');
    $routes->get('appointment/complete/(:num)', 'Admin::completeAppointment/$1');
    $routes->get('appointment/delete/(:num)', 'Admin::deleteAppointment/$1');
    $routes->get('notifications', 'NotificationController::index');
    $routes->get('notifications/fetch', 'NotificationController::fetch');
    $routes->get('notifications/mark-read/(:num)', 'NotificationController::markRead/$1');
    $routes->get('notifications/mark-all-read', 'NotificationController::markAllRead');
    $routes->get('notifications/delete/(:num)', 'NotificationController::delete/$1');
    $routes->get('services', 'Admin::services');
    $routes->post('services/create', 'Admin::createService');
    $routes->post('services/update/(:num)', 'Admin::updateService/$1');
    $routes->get('services/delete/(:num)', 'Admin::deleteService/$1');
    $routes->get('services/toggle/(:num)', 'Admin::toggleServiceStatus/$1');
    $routes->post('users/create', 'Admin::createUser');
    $routes->post('users/update/(:num)', 'Admin::updateUser/$1');
    $routes->get('users/delete/(:num)', 'Admin::deleteUser/$1');
    $routes->get('users/toggle/(:num)', 'Admin::toggleUserStatus/$1');
    $routes->get('users/data/(:num)', 'Admin::getUserData/$1');
});

// ===== PROTECTED RECEPTIONIST ROUTES =====
$routes->group('receptionist', ['filter' => 'auth:receptionist'], function($routes) {
    $routes->get('dashboard', 'Receptionist::dashboard');
    $routes->get('patients', 'Receptionist::patients');
    $routes->get('appointments', 'Receptionist::appointments');
    $routes->get('appointment/view/(:num)', 'Receptionist::appointmentView/$1');
    $routes->get('appointment/approve/(:num)', 'Receptionist::approveAppointment/$1');
    $routes->get('appointment/cancel/(:num)', 'Receptionist::cancelAppointment/$1');
    $routes->get('appointment/complete/(:num)', 'Receptionist::completeAppointment/$1');
    $routes->get('billing', 'Receptionist::billing');
    $routes->get('payments', 'Receptionist::payments');
    $routes->get('reports', 'Receptionist::reports');
    $routes->get('notifications', 'NotificationController::receptionistIndex');
    $routes->get('notifications/fetch', 'NotificationController::receptionistFetch');
    $routes->get('notifications/mark-read/(:num)', 'NotificationController::receptionistMarkRead/$1');
    $routes->get('notifications/mark-all-read', 'NotificationController::receptionistMarkAllRead');
    $routes->get('notifications/delete/(:num)', 'NotificationController::receptionistDelete/$1');
    
    // Diagnostic Requests Routes
    $routes->get('diagnostic-requests', 'Receptionist::diagnosticRequests');
    $routes->post('create-diagnostic-request', 'Receptionist::createDiagnosticRequest');
    $routes->post('update-diagnostic-status/(:num)/(:any)/(:any)', 'Receptionist::updateDiagnosticStatus/$1/$2/$3');
    $routes->delete('delete-diagnostic-request/(:num)/(:any)', 'Receptionist::deleteDiagnosticRequest/$1/$2');
    $routes->get('get-request-details/(:num)/(:any)', 'Receptionist::getRequestDetails/$1/$2');
    $routes->get('print-request/(:num)/(:any)', 'Receptionist::printRequest/$1/$2');
    
    // Patient Sync Routes
    $routes->get('sync-walk-in-patients', 'Receptionist::syncWalkInPatients');
    $routes->get('debug-patients', 'Receptionist::debugPatients');
});

// ===== PROTECTED RADIOLOGIST ROUTES =====
$routes->group('radiologist', ['filter' => 'auth:radiologist'], function($routes) {
    $routes->get('dashboard', 'Radiologist::dashboard');
    $routes->get('examinations', 'Radiologist::examinations');
    $routes->get('examination/view/(:num)', 'Radiologist::viewExamination/$1');
    $routes->post('examination/upload/(:num)', 'Radiologist::uploadImage/$1');
    $routes->post('examination/save/(:num)', 'Radiologist::saveFindings/$1');
    $routes->get('examination/release/(:num)', 'Radiologist::releaseResult/$1');
    $routes->get('examination/print/(:num)', 'Radiologist::printResult/$1');
    $routes->get('notifications', 'Radiologist::notifications');
    $routes->get('notifications/fetch', 'NotificationController::radiologistFetch');
    $routes->get('notifications/mark-read/(:num)', 'NotificationController::radiologistMarkRead/$1');
    $routes->get('notifications/mark-all-read', 'NotificationController::radiologistMarkAllRead');
    $routes->get('notifications/delete/(:num)', 'NotificationController::radiologistDelete/$1');
    $routes->get('reports', 'Radiologist::reports');
});

// ===== PROTECTED MED TECH ROUTES =====
$routes->group('medtech', ['filter' => 'auth:med_tech'], function($routes) {
    $routes->get('dashboard', 'MedTech::dashboard');
    $routes->get('requests', 'MedTech::requests');
    $routes->get('request/view/(:num)', 'MedTech::viewRequest/$1');
    $routes->post('request/save-results/(:num)', 'MedTech::saveResults/$1');
    $routes->post('request/save-findings/(:num)', 'MedTech::saveFindings/$1');
    $routes->get('request/release/(:num)', 'MedTech::releaseResult/$1');
    $routes->get('request/print/(:num)', 'MedTech::printResult/$1');
    $routes->get('reports', 'MedTech::reports');
    $routes->get('notifications', 'MedTech::notifications');
    $routes->get('notifications/fetch', 'NotificationController::medtechFetch');
    $routes->get('notifications/mark-read/(:num)', 'NotificationController::medtechMarkRead/$1');
    $routes->get('notifications/mark-all-read', 'NotificationController::medtechMarkAllRead');
    $routes->get('notifications/delete/(:num)', 'NotificationController::medtechDelete/$1');
    $routes->get('template/(:any)', 'MedTech::getTemplate/$1');
    $routes->post('request/release-directly/(:num)', 'MedTech::releaseDirectly/$1');
});

// ===== PUBLIC ROUTES (NO AUTH REQUIRED) =====
$routes->get('/', 'Appointment::index');
$routes->get('appointment/book', 'Appointment::book');
$routes->post('appointment/submit', 'Appointment::submit');
$routes->get('appointment/success/(:any)', 'Appointment::success/$1');
$routes->get('book-now', 'Appointment::book');
$routes->get('test', 'Test::index');
$routes->get('dbtest', 'DbTest::index');

// ===== FALLBACK ALIAS ROUTES =====
$routes->get('public/admin/dashboard', 'Admin::dashboard');
$routes->get('polymedic/public/admin/dashboard', 'Admin::dashboard');
$routes->get('public/admin/appointments', 'Admin::appointments');
$routes->get('polymedic/public/admin/appointments', 'Admin::appointments');
$routes->get('public/login', 'Auth::login');
$routes->get('polymedic/public/login', 'Auth::login');
$routes->get('public/logout', 'Auth::logout');
$routes->get('polymedic/public/logout', 'Auth::logout');

// ===== EXTRA ROUTES FOR COMPATIBILITY =====
$routes->get('receptionist/diagnostic-requests', 'Receptionist::diagnosticRequests');
$routes->post('receptionist/create-diagnostic-request', 'Receptionist::createDiagnosticRequest');
$routes->post('receptionist/update-diagnostic-status/(:num)/(:any)/(:any)', 'Receptionist::updateDiagnosticStatus/$1/$2/$3');
$routes->delete('receptionist/delete-diagnostic-request/(:num)/(:any)', 'Receptionist::deleteDiagnosticRequest/$1/$2');
$routes->get('receptionist/get-request-details/(:num)/(:any)', 'Receptionist::getRequestDetails/$1/$2');
$routes->get('receptionist/debug-patients', 'Receptionist::debugPatients');
$routes->get('receptionist/sync-walk-in-patients', 'Receptionist::syncWalkInPatients');

// Add these routes inside the receptionist group or at the bottom of your Routes.php
$routes->get('receptionist/sync-walk-in-patients', 'Receptionist::syncWalkInPatients');
$routes->get('receptionist/verify-sync', 'Receptionist::verifySync');
$routes->get('receptionist/debug-patients', 'Receptionist::debugPatients');
$routes->get('receptionist/print-request/(:any)/(:any)', 'Receptionist::printRequest/$1/$2');