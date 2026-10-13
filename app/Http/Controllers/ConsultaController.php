<?php

namespace App\Http\Controllers;

class ConsultaController extends Controller
{
    public function index()
    {
        return view('consultas.index');
    }
}