<?php

use Livewire\Component;
use App\Models\Odontograma;

new class extends Component
{
    public $pacienteId;
    public $consultaId = null;
    public bool $soloLectura = false;

    public string $tipoOdontograma = 'permanente';
    public $datosOdontograma = [];
    public $datosGuardados = null;

    // Variables para el panel de registro
    public $dienteSeleccionado = '';
    public $superficieSeleccionada = 'vestibular';
    public $estadoSeleccionado = 'SANO';
    public $modoDienteCompleto = false;
    public $notaSeleccionada = '';
    public $notaGeneral = '';
       
    public array $estadosSuperficie = [
        'SANO' => 'Sano',
        'CARIES' => 'Caries',
        'OBTURADO' => 'Obturado',
        'FRACTURA' => 'Fractura',
        'EXTRACCION_INDICADA' => 'Extracción indicada',
    ];

    public array $estadosGenerales = [
        'SANO' => 'Sano',
        'CORONA' => 'Corona',
        'ENDODONCIA' => 'Endodoncia',
        'IMPLANTE' => 'Implante',
        'EXTRAIDO' => 'Extraído',
        'PUENTE' => 'Puente',
        'EXTRACCION_INDICADA' => 'Extracción indicada',
    ];

    public array $superficies = ['vestibular', 'mesial', 'distal', 'lingual', 'oclusal'];

    // Cuadrantes FDI
    public array $cuadrantes = [
        // Permanentes
        'superior_derecha'  => [18, 17, 16, 15, 14, 13, 12, 11],
        'superior_izquierda'=> [21, 22, 23, 24, 25, 26, 27, 28],
        'inferior_derecha'  => [48, 47, 46, 45, 44, 43, 42, 41],
        'inferior_izquierda'=> [31, 32, 33, 34, 35, 36, 37, 38],
        
        // Temporales (Niños)
        'sup_der_inf' => [55, 54, 53, 52, 51],
        'sup_izq_inf' => [61, 62, 63, 64, 65],
        'inf_izq_inf' => [81, 82, 83, 84, 85], 
        'inf_der_inf' => [75, 74, 73, 72, 71],
    ];

  
    // Mapeo de nombres de dientes para las imágenes
    public array $nombresDientes = [
        18 => 'tercer_molar', 17 => 'segundo_molar', 16 => 'primer_molar',
        15 => 'segundo_premolar', 14 => 'primer_premolar', 13 => 'canino',
        12 => 'incisivo_lateral', 11 => 'incisivo_central',
        21 => 'incisivo_central', 22 => 'incisivo_lateral', 23 => 'canino',
        24 => 'primer_premolar', 25 => 'segundo_premolar', 26 => 'primer_molar',
        27 => 'segundo_molar', 28 => 'tercer_molar',
        48 => 'tercer_molar', 47 => 'segundo_molar', 46 => 'primer_molar',
        45 => 'segundo_premolar', 44 => 'primer_premolar', 43 => 'canino',
        42 => 'incisivo_lateral', 41 => 'incisivo_central',
        31 => 'incisivo_central', 32 => 'incisivo_lateral', 33 => 'canino',
        34 => 'primer_premolar', 35 => 'segundo_premolar', 36 => 'primer_molar',
        37 => 'segundo_molar', 38 => 'tercer_molar',
    ];

    public function mount($pacienteId, $consultaId = null, $soloLectura = false): void
    {
        $this->pacienteId = $pacienteId;
        $this->consultaId = $consultaId;
        $this->soloLectura = $soloLectura;
        $paciente = \App\Models\Paciente::find($pacienteId);
        $this->tipoOdontograma = $paciente->tipo_denticion ?? 'permanente';

            

        $registro = null;

        if ($consultaId) {
            $registro = Odontograma::where('paciente_id', $pacienteId)
                ->where('consulta_id', $consultaId)
                ->latest()
                ->first();
        }

        if (!$registro) {
            $registro = Odontograma::where('paciente_id', $pacienteId)
                ->latest()
                ->first();
        }

        if ($registro) {
            $this->datosGuardados = $registro->datos;
            $this->datosOdontograma = $registro->datos;
            $this->tipoOdontograma = $registro->tipo ?? $this->tipoOdontograma;
        } else {
            $this->inicializarOdontograma();
        }

       
    }

    // Método para cambiar de vista manualmente
    public function cambiarVista(string $tipo): void
    {
        $this->tipoOdontograma = $tipo;
        
        // Inicializar dientes que no existan en el array (por si pasa de adulto a niño)
        $this->inicializarOdontograma(); 
    }

    private function inicializarOdontograma(): void
    {
        // Juntamos todos los dientes posibles (adultos y niños)
        $dientes = [
            18, 17, 16, 15, 14, 13, 12, 11,
            21, 22, 23, 24, 25, 26, 27, 28,
            48, 47, 46, 45, 44, 43, 42, 41,
            31, 32, 33, 34, 35, 36, 37, 38,
            55, 54, 53, 52, 51, 61, 62, 63, 64, 65,
            85, 84, 83, 82, 81, 71, 72, 73, 74, 75,
        ];

        foreach ($dientes as $diente) {
            // Solo inicializamos si no existe, para no borrar datos al cambiar de vista
            if (!isset($this->datosOdontograma[$diente])) {
                $this->datosOdontograma[$diente] = [
                    'general' => null,
                    'superficies' => array_fill_keys($this->superficies, ['estado' => 'SANO', 'nota' => '']),
                ];
            }
        }
    }
    
    /**
     * Genera la ruta de la imagen basada en la nomenclatura exacta
     */
    public function getRutaImagen(int $diente, string $superficie): string
    {
        // 1. Determinar el cuadrante
        $cuadrante = 'cuadrante_1';
        if ($diente >= 20 && $diente <= 28) $cuadrante = 'cuadrante_2';
        if ($diente >= 30 && $diente <= 38) $cuadrante = 'cuadrante_3';
        if ($diente >= 40 && $diente <= 48) $cuadrante = 'cuadrante_4';
        
        // Cuadrantes infantiles
        if ($diente >= 50 && $diente <= 55) $cuadrante = 'cuadrante_5';
        if ($diente >= 60 && $diente <= 65) $cuadrante = 'cuadrante_6';
        if ($diente >= 80 && $diente <= 85) $cuadrante = 'cuadrante_8'; // Ojo: 8 es abajo izq
        if ($diente >= 70 && $diente <= 75) $cuadrante = 'cuadrante_7'; // Ojo: 7 es abajo der

        // 2. Obtener el nombre del diente (Agregamos los nombres infantiles)
        $nombresInfantiles = [
            55 => 'segundo_molar', 54 => 'primer_molar', 53 => 'canino', 52 => 'incisivo_lateral', 51 => 'incisivo_central',
            65 => 'segundo_molar', 64 => 'primer_molar', 63 => 'canino', 62 => 'incisivo_lateral', 61 => 'incisivo_central',
            85 => 'segundo_molar', 84 => 'primer_molar', 83 => 'canino', 82 => 'incisivo_lateral', 81 => 'incisivo_central',
            75 => 'segundo_molar', 74 => 'primer_molar', 73 => 'canino', 72 => 'incisivo_lateral', 71 => 'incisivo_central',
        ];

        $nombreDiente = $this->nombresDientes[$diente] ?? $nombresInfantiles[$diente] ?? 'desconocido';

        // 3. Armar el nombre del archivo
        $nombreArchivo = "{$diente}_{$nombreDiente}_{$superficie}.png";

        // 4. Retornar ruta completa
        return "images/odontograma/dientes/{$cuadrante}/{$diente}/{$nombreArchivo}";
    }

    // ============================================================
    // CLIC DIRECTO EN EL MAPA (NUEVO)
    // ============================================================

    public function aplicarSuperficie(int $diente, string $superficie): void
    {
        if ($this->soloLectura) return;

        if (in_array($superficie, $this->superficies) && isset($this->datosOdontograma[$diente])) {
            $this->datosOdontograma[$diente]['superficies'][$superficie] = [
                'estado' => $this->estadoSeleccionado,
                'nota' => $this->notaSeleccionada
            ];
            
            if ($this->datosOdontograma[$diente]['general'] === 'EXTRAIDO' || $this->datosOdontograma[$diente]['general'] === 'SANO') {
                $this->datosOdontograma[$diente]['general'] = null;
            }
            
            $this->notificarAlPadre();
        }
    }

    public function aplicarDienteCompleto(int $diente): void
    {
        if ($this->soloLectura) return;
        if (!isset($this->datosOdontograma[$diente])) return;

        $this->datosOdontograma[$diente]['general'] = [
            'estado' => $this->estadoSeleccionado,
            'nota' => $this->notaGeneral
        ];

        if (in_array($this->estadoSeleccionado, ['SANO', 'EXTRAIDO'])) {
            foreach ($this->superficies as $sup) {
                $this->datosOdontograma[$diente]['superficies'][$sup] = ['estado' => 'SANO', 'nota' => ''];
            }
        }

        $this->notificarAlPadre();
    }

    // ============================================================
    // APLICAR DESDE EL PANEL DE REGISTRO
    // ============================================================

    public function aplicar(): void
    {
        if ($this->soloLectura) return;

        if (empty($this->dienteSeleccionado)) {
            session()->flash('error', 'Selecciona un diente primero.');
            return;
        }

        $diente = $this->dienteSeleccionado;

        if ($this->modoDienteCompleto) {
            $this->aplicarDienteCompleto((int)$diente);
        } else {
            $this->aplicarSuperficie((int)$diente, $this->superficieSeleccionada);
        }
    }

    public function aplicarATodasLasSuperficies(): void
    {
        if ($this->soloLectura) return;
        if (empty($this->dienteSeleccionado)) {
            session()->flash('error', 'Selecciona un diente primero.');
            return;
        }

        $diente = $this->dienteSeleccionado;

        foreach ($this->superficies as $sup) {
            $this->datosOdontograma[$diente]['superficies'][$sup] = $this->estadoSeleccionado;
        }

        $this->datosOdontograma[$diente]['general'] = null;
        $this->notificarAlPadre();
    }

    // ============================================================
    // QUITAR HALLAZGOS
    // ============================================================

    public function quitarHallazgo(int $diente, string $superficie): void
    {
        if ($this->soloLectura) return;
        if (isset($this->datosOdontograma[$diente]['superficies'][$superficie])) {
            $this->datosOdontograma[$diente]['superficies'][$superficie] = ['estado' => 'SANO', 'nota' => ''];
            $this->notificarAlPadre();
        }
    }

    public function quitarEstadoGeneral(int $diente): void
    {
        if ($this->soloLectura) return;
        if (isset($this->datosOdontograma[$diente])) {
            $this->datosOdontograma[$diente]['general'] = null;
            $this->notificarAlPadre();
        }
    }

    public function limpiarDiente(int $diente): void
    {
        if ($this->soloLectura) return;
        if (isset($this->datosOdontograma[$diente])) {
            $this->datosOdontograma[$diente] = [
                'general' => null,
                'superficies' => array_fill_keys($this->superficies, ['estado' => 'SANO', 'nota' => '']),
            ];
            $this->notificarAlPadre();
        }
    }

    private function notificarAlPadre(): void
    {
        $this->dispatch('odontograma-sincronizado', 
            datos: $this->datosOdontograma,
            tipo: $this->tipoOdontograma // <--- Agregar esto
        )->to('consultas-form');
    }

    // ============================================================
    // HELPERS VISUALES
    // ============================================================

    public function colorEstado(string $estado): string
    {
        return match($estado) {
            'SANO' => 'transparent',
            'CARIES' => 'rgba(239, 68, 68, 0.7)',
            'OBTURADO' => 'rgba(59, 130, 246, 0.7)',
            'FRACTURA' => 'rgba(249, 115, 22, 0.7)',
            'EXTRACCION_INDICADA' => 'rgba(234, 179, 8, 0.7)',
            'CORONA' => 'rgba(168, 85, 247, 0.8)',
            'ENDODONCIA' => 'rgba(139, 92, 246, 0.8)',
            'IMPLANTE' => 'rgba(14, 165, 233, 0.8)',
            'EXTRAIDO' => 'rgba(55, 65, 81, 0.9)',
            'PUENTE' => 'rgba(34, 197, 94, 0.8)',
            default => 'transparent',
        };
    }

    // ============================================================
    // LISTADO DE HALLAZGOS
    // ============================================================

    public function getHallazgosProperty(): array
    {
        $hallazgos = [];

        foreach ($this->datosOdontograma as $diente => $dato) {
            // Validar general (compatibilidad con JSON antiguo que era string)
            $g = $dato['general'] ?? null;
            $estadoGeneral = is_array($g) ? ($g['estado'] ?? null) : $g;
            $notaGeneral = is_array($g) ? ($g['nota'] ?? '') : '';

            if (!empty($estadoGeneral) && $estadoGeneral !== 'SANO') {
                $hallazgos[] = [
                    'tipo' => 'general', 'diente' => (int) $diente, 'superficie' => null,
                    'estado' => $estadoGeneral, 'nota' => $notaGeneral,
                ];
                continue;
            }

            // Validar superficies (compatibilidad)
            foreach ($dato['superficies'] ?? [] as $sup => $val) {
                $estado = is_array($val) ? ($val['estado'] ?? 'SANO') : $val;
                $nota = is_array($val) ? ($val['nota'] ?? '') : '';

                if ($estado !== 'SANO') {
                    $hallazgos[] = [
                        'tipo' => 'superficie', 'diente' => (int) $diente, 'superficie' => $sup,
                        'estado' => $estado, 'nota' => $nota,
                    ];
                }
            }
        }
        usort($hallazgos, fn($a, $b) => $a['diente'] <=> $b['diente']);
        return $hallazgos;
    }

    public function with(): array
    {
        $hallazgos = $this->hallazgos;

        // Obtenemos los dientes según la vista actual
        $dientesVista = [];
        if ($this->tipoOdontograma === 'infantil') {
            $dientesVista = [55, 54, 53, 52, 51, 61, 62, 63, 64, 65, 85, 84, 83, 82, 81, 71, 72, 73, 74, 75];
        } elseif ($this->tipoOdontograma === 'permanente') {
            $dientesVista = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28, 48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38];
        } else {
            // Mixto: todos
            $dientesVista = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28, 48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38, 55, 54, 53, 52, 51, 61, 62, 63, 64, 65, 85, 84, 83, 82, 81, 71, 72, 73, 74, 75];
        }

        return [
            'dientes' => $dientesVista,
            'hallazgos' => $hallazgos,
            'totalHallazgos' => count($hallazgos),
        ];
    }
};
?>

<div class="space-y-4">

    {{-- ============================================ --}}
    {{-- ODONTOGRAMA VISUAL CON IMÁGENES --}}
    {{-- ============================================ --}}
    <div class="flex justify-end mb-2">
        <div class="flex bg-gray-100 p-1 rounded-lg text-xs">
            <button wire:click="cambiarVista('infantil')" 
                    class="px-3 py-1 rounded-md {{ $tipoOdontograma === 'infantil' ? 'bg-white shadow text-teal-700 font-bold' : 'text-gray-500' }}">
                Temporal
            </button>
            <button wire:click="cambiarVista('mixto')" 
                    class="px-3 py-1 rounded-md {{ $tipoOdontograma === 'mixto' ? 'bg-white shadow text-teal-700 font-bold' : 'text-gray-500' }}">
                Mixto
            </button>
            <button wire:click="cambiarVista('permanente')" 
                    class="px-3 py-1 rounded-md {{ $tipoOdontograma === 'permanente' ? 'bg-white shadow text-teal-700 font-bold' : 'text-gray-500' }}">
                Permanente
            </button>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        
        {{-- ARCADA SUPERIOR PERMANENTE --}}
        @if($tipoOdontograma === 'permanente' || $tipoOdontograma === 'mixto')
        <div class="flex justify-center gap-2 {{ $tipoOdontograma === 'mixto' ? 'mb-2' : 'mb-4' }} min-w-max">
            <div class="flex gap-1">
                @foreach ($cuadrantes['superior_derecha'] as $diente)
                    @include('components.partials.diente-mapa', ['diente' => $diente, 'espejo' => false])
                @endforeach
            </div>
            <div class="w-px bg-gray-300 mx-2"></div>
            <div class="flex gap-1">
                @foreach ($cuadrantes['superior_izquierda'] as $diente)
                    @include('components.partials.diente-mapa', ['diente' => $diente, 'espejo' => true])
                @endforeach
            </div>
        </div>
        @endif

        {{-- ARCADA SUPERIOR TEMPORAL (Solo si es infantil o mixto) --}}
        @if($tipoOdontograma === 'infantil' || $tipoOdontograma === 'mixto')
        <div class="flex justify-center gap-2 mb-4 min-w-max opacity-80">
            <div class="flex gap-1">
                @foreach ($cuadrantes['sup_der_inf'] as $diente)
                    @include('components.partials.diente-mapa', ['diente' => $diente, 'espejo' => false])
                @endforeach
            </div>
            <div class="w-px bg-gray-300 mx-2"></div>
            <div class="flex gap-1">
                @foreach ($cuadrantes['sup_izq_inf'] as $diente)
                    @include('components.partials.diente-mapa', ['diente' => $diente, 'espejo' => true])
                @endforeach
            </div>
        </div>
        @endif

        {{-- ARCADA INFERIOR TEMPORAL (Solo si es infantil o mixto) --}}
        @if($tipoOdontograma === 'infantil' || $tipoOdontograma === 'mixto')
        <div class="flex justify-center gap-2 mb-2 min-w-max opacity-80">
            <div class="flex gap-1">
                @foreach ($cuadrantes['inf_der_inf'] as $diente)
                    @include('components.partials.diente-mapa', ['diente' => $diente, 'espejo' => false])
                @endforeach
            </div>
            <div class="w-px bg-gray-300 mx-2"></div>
            <div class="flex gap-1">
                @foreach ($cuadrantes['inf_izq_inf'] as $diente)
                    @include('components.partials.diente-mapa', ['diente' => $diente, 'espejo' => true])
                @endforeach
            </div>
        </div>
        @endif

        {{-- ARCADA INFERIOR PERMANENTE --}}
        @if($tipoOdontograma === 'permanente' || $tipoOdontograma === 'mixto')
        <div class="flex justify-center gap-2 min-w-max">
            <div class="flex gap-1">
                @foreach ($cuadrantes['inferior_derecha'] as $diente)
                    @include('components.partials.diente-mapa', ['diente' => $diente, 'espejo' => false])
                @endforeach
            </div>
            <div class="w-px bg-gray-300 mx-2"></div>
            <div class="flex gap-1">
                @foreach ($cuadrantes['inferior_izquierda'] as $diente)
                    @include('components.partials.diente-mapa', ['diente' => $diente, 'espejo' => true])
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ============================================ --}}
    {{-- LISTADO DE HALLAZGOS --}}
    {{-- ============================================ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <h3 class="text-sm font-bold text-gray-800">Hallazgos Detectados</h3>
                <span class="text-xs text-gray-500">({{ $totalHallazgos }})</span>
            </div>
        </div>

        @if ($totalHallazgos === 0)
            <div class="px-4 py-8 text-center">
                <p class="text-sm text-gray-500">Todos los dientes están sanos.</p>
            </div>
        @else
            <div class="overflow-x-auto max-h-64 overflow-y-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold sticky top-0">
                        <tr>
                            <th class="px-4 py-2 w-20">Diente</th>
                            <th class="px-4 py-2 w-28">Superficie</th>
                            <th class="px-4 py-2">Nota</th>
                            <th class="px-4 py-2">Estado</th>
                            <th class="px-4 py-2 text-right w-24">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($hallazgos as $h)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 font-mono font-semibold text-gray-800">{{ $h['diente'] }}</td>
                                <td class="px-4 py-2 text-gray-600">
                                    {{ $h['tipo'] === 'general' ? 'Diente completo' : ucfirst($h['superficie']) }}
                                </td>
                                <td class="px-4 py-2 text-gray-600 text-xs italic max-w-xs truncate" title="{{ $h['nota'] }}">
                                    {{ $h['nota'] ?: '—' }}
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 rounded text-xs font-medium text-white"
                                          style="background: {{ $this->colorEstado($h['estado']) }}; color: #fff; text-shadow: 0 1px 1px rgba(0,0,0,0.3);">
                                        {{ \App\Models\Odontograma::estadosLegibles()[$h['estado']] ?? $h['estado'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    @unless ($soloLectura)
                                        <button wire:click="{{ $h['tipo'] === 'general' ? 'quitarEstadoGeneral('. $h['diente'] .')' : 'quitarHallazgo('. $h['diente'] .", '". $h['superficie'] ."')" }}"
                                                class="p-1 text-red-600 hover:bg-red-50 rounded">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ============================================ --}}
    {{-- PANEL DE REGISTRO DE HALLAZGOS --}}
    {{-- ============================================ --}}
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 {{ $soloLectura ? 'opacity-60 pointer-events-none' : '' }}">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-bold text-gray-800">Registrar Hallazgo</h3>
                <p class="text-xs text-gray-500 mt-0.5">Selecciona los datos y pulsa "Aplicar", o haz clic directo en el diente del mapa.</p>
            </div>
            {{-- Indicador de qué color se aplicará al hacer clic directo --}}
            <div class="hidden sm:flex items-center gap-2 text-xs">
                <span class="text-gray-500">Aplicando:</span>
                <span class="px-2.5 py-1 rounded-md font-medium text-white shadow-sm"
                      style="background: {{ $this->colorEstado($estadoSeleccionado) }}; text-shadow: 0 1px 1px rgba(0,0,0,0.2);">
                    {{ $modoDienteCompleto ? ($estadosGenerales[$estadoSeleccionado] ?? 'Sano') : ($estadosSuperficie[$estadoSeleccionado] ?? 'Sano') }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Diente combobox --}}
            <div x-data="{
                    abierto: false, 
                    busqueda: '', 
                    seleccionado: @entangle('dienteSeleccionado'), 
                    dientes: @js($dientes),
                    get filtrados() { return this.busqueda === '' ? this.dientes : this.dientes.filter(d => String(d).includes(this.busqueda)); },
                    seleccionar(d) { this.seleccionado = String(d); this.busqueda = ''; this.abierto = false; }
                }" @click.away="abierto = false" class="relative">
                
                <label class="block text-xs font-semibold text-gray-700 mb-1">Diente</label>
                <button type="button" @click="abierto = !abierto" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-left flex items-center justify-between bg-white hover:border-gray-400 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition">
                    <span x-text="seleccionado ? 'Diente ' + seleccionado : 'Nro diente'" class="text-gray-700"></span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                
                <div x-show="abierto" x-cloak x-transition class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-hidden">
                    <div class="p-2 border-b border-gray-100">
                        <input type="text" x-model="busqueda" placeholder="Buscar diente..." class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-teal-500">
                    </div>
                    <div class="max-h-44 overflow-y-auto">
                        <template x-for="d in filtrados" :key="d">
                            <button type="button" @click="seleccionar(d)" class="w-full px-3 py-2 text-left text-sm hover:bg-teal-50 transition" :class="seleccionado == d ? 'bg-teal-50 text-teal-700 font-semibold' : 'text-gray-700'" x-text="'Diente ' + d"></button>
                        </template>
                        <template x-if="filtrados.length === 0">
                            <p class="px-3 py-2 text-xs text-gray-500 text-center">Sin resultados</p>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Aplicar a -->
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Aplicar a</label>
                <select wire:model.live="modoDienteCompleto" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 transition">
                    <option value="0">Superficie</option>
                    <option value="1">Diente completo</option>
                </select>
            </div>

            <!-- Superficie -->
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Superficie</label>
                <select wire:model="superficieSeleccionada" @if($modoDienteCompleto) disabled @endif class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-100 focus:ring-2 focus:ring-teal-500 transition">
                    @foreach ($superficies as $sup)
                        <option value="{{ $sup }}">{{ ucfirst($sup) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Estado -->
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Estado</label>
                <select wire:model.live="estadoSeleccionado" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 transition">
                    @if ($modoDienteCompleto)
                        @foreach ($estadosGenerales as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    @else
                        @foreach ($estadosSuperficie as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Nota (Nuevo campo) -->
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Nota (Opcional)</label>
                <input type="text" 
                    wire:model="{{ $modoDienteCompleto ? 'notaGeneral' : 'notaSeleccionada' }}" 
                    placeholder="Ej: Revisar en 6 meses..." 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 transition">
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 mt-5 pt-4 border-t border-gray-100">
            <button wire:click="aplicar" type="button" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg text-sm font-semibold hover:bg-teal-700 shadow-sm transition flex items-center justify-center sm:w-auto w-full">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Aplicar a {{ $modoDienteCompleto ? 'Diente Completo' : 'Superficie' }}
            </button>
            
            @if (!$modoDienteCompleto)
                <button wire:click="aplicarATodasLasSuperficies" type="button" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-200 transition flex items-center justify-center sm:w-auto w-full border border-slate-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Aplicar a todas las superficies
                </button>
            @endif
        </div>
    </div>
</div>