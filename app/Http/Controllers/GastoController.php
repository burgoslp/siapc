<?php

namespace App\Http\Controllers;

class GastoController extends Controller
{
    public function index()
    {
        return view('gastos.index');
    }
}