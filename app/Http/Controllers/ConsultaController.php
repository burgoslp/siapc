<?php
namespace App\Http\Controllers;

use App\Models\Consulta;
use App\Models\Empresa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ConsultaController extends Controller
{
    public function index()
    {
        return view('consultas.index');
    }

    public function pdf(Consulta $consulta){

        $consulta->load(['paciente', 'medico.especialidades', 'cita', 'editor']);

        $empresa = Empresa::pluck('valor', 'clave')->toArray();

        $logoPath = null;
        if (!empty($empresa['logo']) && Storage::disk('public')->exists($empresa['logo'])) {
            $logoPath = Storage::disk('public')->path($empresa['logo']);
        }

        $pdf = Pdf::loadView('pdf.consulta', compact('consulta', 'empresa', 'logoPath'));

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream('Receta_' . $consulta->paciente->ci . '_' . $consulta->created_at->format('Ymd') . '.pdf');
    }
}