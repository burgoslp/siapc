<?php

namespace App\Http\Controllers;

use App\Models\Alergeno;
use Illuminate\Http\Request;

class AlergenoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('parametros.alergenos.index');
    }

    
}
