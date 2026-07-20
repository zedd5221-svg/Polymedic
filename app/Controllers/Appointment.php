<?php

namespace App\Controllers;

class Appointment extends BaseController
{
    public function index(): string
    {
        return view('Appointment/index');
    }
}
