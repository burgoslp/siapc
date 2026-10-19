<?php

namespace App\Http\Controllers;

class CuentaController extends Controller
{
    public function index()
    {
        return view('tesoreria.cuentas.index');
    }
}