<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Appointment::index');
$routes->get('appointment/book', 'Appointment::book');
$routes->post('appointment/submit', 'Appointment::submit');
$routes->get('appointment/success/(:any)', 'Appointment::success/$1');