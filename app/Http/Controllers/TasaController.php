<?php

namespace App\Http\Controllers;

class TasaController extends Controller
{
    public function index()
    {
        return view('parametros.tasas.index');
    }
}