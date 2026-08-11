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