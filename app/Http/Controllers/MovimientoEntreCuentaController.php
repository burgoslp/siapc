<?php

namespace App\Http\Controllers;

class MovimientoEntreCuentaController extends Controller
{
    public function index()
    {
        return view('tesoreria.movimientos.index');
    }
}