<?php

namespace App\Http\Controllers;

class GastoController extends Controller
{
    public function variables()
    {
        return view('gastos.variables.index');
    }

    public function fijos()
    {
        return view('gastos.fijos.index');
    }
}