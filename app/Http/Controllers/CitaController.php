<?php

namespace App\Http\Controllers;

class CitaController extends Controller
{
    public function index()
    {
        return view('citas.index');
    }
}