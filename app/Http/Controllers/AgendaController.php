<?php

namespace App\Http\Controllers;

class AgendaController extends Controller
{
    public function index()
    {
        return view('agenda.index');
    }
}