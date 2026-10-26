<?php

namespace App\Http\Controllers;

class ReporteController extends Controller
{
    public function financiero()
    {
        return view('reportes.financiero');
    }

    public function productividad()
    {
        return view('reportes.productividad');
    }
}