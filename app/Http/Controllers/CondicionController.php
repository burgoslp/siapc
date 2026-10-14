<?php

namespace App\Http\Controllers;

use App\Models\Condicion;
use Illuminate\Http\Request;

class CondicionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('parametros.condiciones.index');
    }

    
}
