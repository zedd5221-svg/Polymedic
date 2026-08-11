<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// AUTH ROUTES
$routes->get('login', 'Auth::login');
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
$routes->get('admin/visits', 'Admin::visits');
$routes->get('admin/requests', 'Admin::requests');
$routes->get('admin/users', 'Admin::users');



// ===== APPOINTMENT MANAGEMENT ROUTES =====
$routes->get('admin/appointments', 'Admin::appointments');
$routes->get('admin/appointment/view/(:num)', 'Admin::appointmentView/$1');
$routes->get('admin/appointment/approve/(:num)', 'Admin::approveAppointment/$1');
$routes->get('admin/appointment/cancel/(:num)', 'Admin::cancelAppointment/$1');
$routes->get('admin/appointment/complete/(:num)', 'Admin::completeAppointment/$1');
$routes->get('admin/appointment/delete/(:num)', 'Admin::deleteAppointment/$1'); 