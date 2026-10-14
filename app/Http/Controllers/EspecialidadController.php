<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EspecialidadController extends Controller
{
    public function index()
    {
        return view('parametros.especialidades.index');
    }    

}
