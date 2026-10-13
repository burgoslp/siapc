<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
    /**
     * Muestra el listado de pacientes con búsqueda y paginación.
     */
    public function index()
    {
        return view('pacientes.index');
    }


    public function show(Paciente $paciente)
    {
        // Carga las condiciones y alérgenos asociados al paciente
        $paciente->load(['alergias.alergeno', 'condiciones.condicion']);
        return view('pacientes.show', compact('paciente'));
    }

    

    public function destroy(Paciente $paciente)
    {
        $paciente->delete();

        return redirect()->route('pacientes.index')
            ->with('success', 'Paciente eliminado correctamente.');
    }
}
