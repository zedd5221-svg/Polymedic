<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// AUTH ROUTES
$routes->get('login', 'Auth::login');
$routes->get('admin/login', 'Auth::login');
$routes->post('auth/authenticate', 'Auth::authenticate');
$routes->get('logout', 'Auth::logout');

// PUBLIC ROUTES
$routes->get('/', 'Appointment::index');
$routes->get('appointment/book', 'Appointment::book');
$routes->post('appointment/submit', 'Appointment::submit');
$routes->get('appointment/success/(:any)', 'Appointment::success/$1');
$routes->get('book-now', 'Appointment::book');
$routes->get('test', 'Test::index');
$routes->get('dbtest', 'DbTest::index');

// ADMIN ROUTES
$routes->get('admin/dashboard', 'Admin::dashboard');
$routes->get('admin/patients', 'Admin::patients');
$routes->get('admin/requests', 'Admin::requests');
$routes->get('admin/users', 'Admin::users');
$routes->get('admin/sync-xray', 'Admin::syncXrayExaminations'); // ← ADD THIS

// ===== APPOINTMENT MANAGEMENT ROUTES =====
$routes->get('admin/appointments', 'Admin::appointments');
$routes->get('admin/appointment/view/(:num)', 'Admin::appointmentView/$1');
$routes->get('admin/appointment/approve/(:num)', 'Admin::approveAppointment/$1');
$routes->get('admin/appointment/cancel/(:num)', 'Admin::cancelAppointment/$1');
$routes->get('admin/appointment/complete/(:num)', 'Admin::completeAppointment/$1');
$routes->get('admin/appointment/delete/(:num)', 'Admin::deleteAppointment/$1'); 

// ===== NOTIFICATION MANAGEMENT ROUTES =====
$routes->get('admin/notifications', 'NotificationController::index');
$routes->get('admin/notifications/fetch', 'NotificationController::fetch');
$routes->get('admin/notifications/mark-read/(:num)', 'NotificationController::markRead/$1');
$routes->get('admin/notifications/mark-all-read', 'NotificationController::markAllRead');
$routes->get('admin/notifications/delete/(:num)', 'NotificationController::delete/$1');

// ===== FALLBACK ALIAS ROUTES FOR PUBLIC PATH PREFIXES =====
$routes->get('public/admin/dashboard', 'Admin::dashboard');
$routes->get('polymedic/public/admin/dashboard', 'Admin::dashboard');
$routes->get('public/admin/appointments', 'Admin::appointments');
$routes->get('polymedic/public/admin/appointments', 'Admin::appointments');
$routes->get('public/login', 'Auth::login');
$routes->get('polymedic/public/login', 'Auth::login');
$routes->get('public/logout', 'Auth::logout');
$routes->get('polymedic/public/logout', 'Auth::logout');

// ===== RECEPTIONIST ROUTES =====
$routes->get('receptionist/dashboard', 'Receptionist::dashboard');
$routes->get('receptionist/patients', 'Receptionist::patients');
$routes->get('receptionist/appointments', 'Receptionist::appointments');
$routes->get('receptionist/appointment/view/(:num)', 'Receptionist::appointmentView/$1');
$routes->get('receptionist/appointment/approve/(:num)', 'Receptionist::approveAppointment/$1');
$routes->get('receptionist/appointment/cancel/(:num)', 'Receptionist::cancelAppointment/$1');
$routes->get('receptionist/appointment/complete/(:num)', 'Receptionist::completeAppointment/$1');
$routes->get('receptionist/billing', 'Receptionist::billing');
$routes->get('receptionist/payments', 'Receptionist::payments');
$routes->get('receptionist/reports', 'Receptionist::reports');

// Receptionist Appointment View
$routes->get('receptionist/appointment/view/(:num)', 'Receptionist::appointmentView/$1');

// ===== RECEPTIONIST NOTIFICATION ROUTES =====
$routes->get('receptionist/notifications', 'NotificationController::receptionistIndex');
$routes->get('receptionist/notifications/fetch', 'NotificationController::receptionistFetch');
$routes->get('receptionist/notifications/mark-read/(:num)', 'NotificationController::receptionistMarkRead/$1');
$routes->get('receptionist/notifications/mark-all-read', 'NotificationController::receptionistMarkAllRead');
$routes->get('receptionist/notifications/delete/(:num)', 'NotificationController::receptionistDelete/$1');

// ===== ADMIN USER MANAGEMENT CRUD ROUTES =====
$routes->post('admin/users/create', 'Admin::createUser');
$routes->post('admin/users/update/(:num)', 'Admin::updateUser/$1');
$routes->get('admin/users/delete/(:num)', 'Admin::deleteUser/$1');
$routes->get('admin/users/toggle/(:num)', 'Admin::toggleUserStatus/$1');
$routes->get('admin/users/data/(:num)', 'Admin::getUserData/$1');

// ===== RADIOLOGIST ROUTES =====
$routes->get('radiologist/dashboard', 'Radiologist::dashboard');
$routes->get('radiologist/examinations', 'Radiologist::examinations');
$routes->get('radiologist/examination/view/(:num)', 'Radiologist::viewExamination/$1');
$routes->post('radiologist/examination/upload/(:num)', 'Radiologist::uploadImage/$1');
$routes->post('radiologist/examination/save/(:num)', 'Radiologist::saveFindings/$1');
$routes->get('radiologist/examination/release/(:num)', 'Radiologist::releaseResult/$1');
$routes->get('radiologist/examination/print/(:num)', 'Radiologist::printResult/$1');

// ===== RADIOLOGIST NOTIFICATION ROUTES =====
$routes->get('radiologist/notifications', 'Radiologist::notifications');
$routes->get('radiologist/notifications/fetch', 'NotificationController::radiologistFetch');
$routes->get('radiologist/notifications/mark-read/(:num)', 'NotificationController::radiologistMarkRead/$1');
$routes->get('radiologist/notifications/mark-all-read', 'NotificationController::radiologistMarkAllRead');
$routes->get('radiologist/notifications/delete/(:num)', 'NotificationController::radiologistDelete/$1');

// ===== RADIOLOGIST REPORTS =====
$routes->get('radiologist/reports', 'Radiologist::reports');

// ===== SERVICE MANAGEMENT ROUTES =====
$routes->get('admin/services', 'Admin::services');
$routes->post('admin/services/create', 'Admin::createService');
$routes->post('admin/services/update/(:num)', 'Admin::updateService/$1');
$routes->get('admin/services/delete/(:num)', 'Admin::deleteService/$1');
$routes->get('admin/services/toggle/(:num)', 'Admin::toggleServiceStatus/$1');

