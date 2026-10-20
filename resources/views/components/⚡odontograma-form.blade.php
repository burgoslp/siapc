<?php

use Livewire\Component;
use App\Models\Odontograma;

new class extends Component
{
    public $pacienteId;
    public $consultaId = null;
    public bool $soloLectura = false;

    public $datosOdontograma = [];
    public $datosGuardados = null;

    public $dienteSeleccionado = '';
    public $superficieSeleccionada = 'vestibular';
    public $estadoSeleccionado = 'SANO';
    public $modoDienteCompleto = false;

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

    public function mount($pacienteId, $consultaId = null, $soloLectura = false): void
    {
        $this->pacienteId = $pacienteId;
        $this->consultaId = $consultaId;
        $this->soloLectura = $soloLectura;

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
        } else {
            $this->inicializarOdontograma();
        }
    }

    private function inicializarOdontograma(): void
    {
        $dientes = [
            18, 17, 16, 15, 14, 13, 12, 11,
            21, 22, 23, 24, 25, 26, 27, 28,
            48, 47, 46, 45, 44, 43, 42, 41,
            31, 32, 33, 34, 35, 36, 37, 38,
        ];

        foreach ($dientes as $diente) {
            $this->datosOdontograma[$diente] = [
                'general' => null,
                'superficies' => array_fill_keys($this->superficies, 'SANO'),
            ];
        }
    }

    // ============================================================
    // APLICAR HALLAZGOS
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
            $this->datosOdontograma[$diente]['general'] = $this->estadoSeleccionado;
            if ($this->estadoSeleccionado === 'SANO') {
                foreach ($this->superficies as $sup) {
                    $this->datosOdontograma[$diente]['superficies'][$sup] = 'SANO';
                }
            }
        } else {
            $this->datosOdontograma[$diente]['superficies'][$this->superficieSeleccionada] = $this->estadoSeleccionado;
            $this->datosOdontograma[$diente]['general'] = null;
        }

        $this->dispatch('odontograma-actualizado',
            datos: $this->datosOdontograma,
            elementId: "odontograma-{$this->pacienteId}"
        );

        $this->notificarAlPadre();
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

        $this->dispatch('odontograma-actualizado',
            datos: $this->datosOdontograma,
            elementId: "odontograma-{$this->pacienteId}"
        );

        $this->notificarAlPadre();
    }

    // ============================================================
    // QUITAR HALLAZGOS (NUEVO)
    // ============================================================

    /**
     * Quita un hallazgo de una superficie específica (la vuelve a SANO).
     */
    public function quitarHallazgo(int $diente, string $superficie): void
    {
        if ($this->soloLectura) return;

        if (isset($this->datosOdontograma[$diente]['superficies'][$superficie])) {
            $this->datosOdontograma[$diente]['superficies'][$superficie] = 'SANO';

            // Si ya no queda ningún hallazgo en el diente, limpiamos el estado general
            if (empty($this->datosOdontograma[$diente]['general'])
                && $this->dienteEstaSano((int) $diente)) {
                $this->datosOdontograma[$diente]['general'] = null;
            }

            $this->sincronizarVisual();
        }
    }

    /**
     * Quita el estado general de un diente (corona, endodoncia, etc.).
     */
    public function quitarEstadoGeneral(int $diente): void
    {
        if ($this->soloLectura) return;

        if (isset($this->datosOdontograma[$diente])) {
            $this->datosOdontograma[$diente]['general'] = null;
            $this->sincronizarVisual();
        }
    }

    /**
     * Quita TODOS los hallazgos de un diente (lo deja completamente sano).
     */
    public function limpiarDiente(int $diente): void
    {
        if ($this->soloLectura) return;

        if (isset($this->datosOdontograma[$diente])) {
            $this->datosOdontograma[$diente] = [
                'general' => null,
                'superficies' => array_fill_keys($this->superficies, 'SANO'),
            ];

            $this->sincronizarVisual();
        }
    }

    private function dienteEstaSano(int $diente): bool
    {
        $dato = $this->datosOdontograma[$diente] ?? null;
        if (!$dato) return true;

        if (!empty($dato['general']) && $dato['general'] !== 'SANO') {
            return false;
        }

        foreach ($dato['superficies'] ?? [] as $estado) {
            if ($estado !== 'SANO') return false;
        }

        return true;
    }

    private function sincronizarVisual(): void
    {
        $this->dispatch('odontograma-actualizado',
            datos: $this->datosOdontograma,
            elementId: "odontograma-{$this->pacienteId}"
        );

        $this->notificarAlPadre();
    }

    private function notificarAlPadre(): void
    {
        $this->dispatch('odontograma-sincronizado', datos: $this->datosOdontograma)->to('consultas-form');
    }

    // ============================================================
    // LISTADO DE HALLAZGOS (derivado del JSON)
    // ============================================================

    /**
     * Devuelve un array plano de hallazgos para renderizar en la tabla.
     * Formato: [
     *   ['tipo' => 'general', 'diente' => 11, 'superficie' => null, 'estado' => 'CORONA'],
     *   ['tipo' => 'superficie', 'diente' => 12, 'superficie' => 'vestibular', 'estado' => 'CARIES'],
     * ]
     */
    public function getHallazgosProperty(): array
    {
        $hallazgos = [];

        foreach ($this->datosOdontograma as $diente => $dato) {
            // Estados generales (corona, endodoncia, extraído, etc.)
            if (!empty($dato['general']) && $dato['general'] !== 'SANO') {
                $hallazgos[] = [
                    'tipo' => 'general',
                    'diente' => (int) $diente,
                    'superficie' => null,
                    'estado' => $dato['general'],
                ];
                continue; // Si hay estado general, no mostrar superficies
            }

            // Estados por superficie
            foreach ($dato['superficies'] ?? [] as $sup => $estado) {
                if ($estado !== 'SANO') {
                    $hallazgos[] = [
                        'tipo' => 'superficie',
                        'diente' => (int) $diente,
                        'superficie' => $sup,
                        'estado' => $estado,
                    ];
                }
            }
        }

        // Ordenar por diente ascendente
        usort($hallazgos, fn($a, $b) => $a['diente'] <=> $b['diente']);

        return $hallazgos;
    }

    public function getTotalHallazgosProperty(): int
    {
        return count($this->hallazgos);
    }

    public function with(): array
    {
        return [
            'dientes' => [
                18, 17, 16, 15, 14, 13, 12, 11,
                21, 22, 23, 24, 25, 26, 27, 28,
                48, 47, 46, 45, 44, 43, 42, 41,
                31, 32, 33, 34, 35, 36, 37, 38,
            ],
            'hallazgos' => $this->hallazgos,
            'totalHallazgos' => $this->totalHallazgos,
        ];
    }
};
?>

<div
    class="space-y-4"
    x-data="{
        pacienteId: {{ $pacienteId }},
        datosLivewire: @js($datosOdontograma),
        init() {
            this.$nextTick(() => {
                setTimeout(() => {
                    const el = document.getElementById('odontograma-' + this.pacienteId);
                    if (el) {
                        window.initOdontograma('odontograma-' + this.pacienteId, this.datosLivewire);
                    }
                }, 200);
            });
        }
    }"
    x-init="init()"
    @odontograma-actualizado.window="
        if ($event.detail.elementId === 'odontograma-' + pacienteId || !$event.detail.elementId) {
            window.refrescarOdontograma('odontograma-' + pacienteId, $event.detail.datos);
        }
    "
>

    {{-- ============================================ --}}
    {{-- 1. ODONTOGRAMA VISUAL --}}
    {{-- ============================================ --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="text-sm font-bold text-gray-800">Odontograma</h3>
                <p class="text-xs text-gray-500">
                    @if ($soloLectura)
                        Solo lectura
                    @else
                        Haz clic en las superficies del diente para marcarlas
                    @endif
                </p>
            </div>
            @if ($totalHallazgos > 0)
                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-teal-100 text-teal-700">
                    {{ $totalHallazgos }} {{ $totalHallazgos === 1 ? 'hallazgo' : 'hallazgos' }}
                </span>
            @endif
        </div>

        <div wire:ignore class="flex justify-center" style="overflow-x: auto;">
            <div id="odontograma-container-{{ $pacienteId }}"
                 style="zoom: 0.7; min-width: 900px;">
                <og-odontogram
                    id="odontograma-{{ $pacienteId }}"
                    mode="adult"
                    notation="fdi"
                    style="width: 100%; min-height: 200px; display: block;">
                </og-odontogram>
            </div>
        </div>
    </div>

    {{-- ============================================ --}}
    {{-- 2. LISTADO DE HALLAZGOS (NUEVO) --}}
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
                <svg class="w-10 h-10 mx-auto text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-gray-500">Sin hallazgos registrados. Todos los dientes están sanos.</p>
            </div>
        @else
            <div class="overflow-x-auto max-h-64 overflow-y-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold sticky top-0">
                        <tr>
                            <th class="px-4 py-2 w-20">Diente</th>
                            <th class="px-4 py-2 w-28">Superficie</th>
                            <th class="px-4 py-2">Estado</th>
                            <th class="px-4 py-2 text-right w-24">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($hallazgos as $h)
                            <tr wire:key="hallazgo-{{ $h['diente'] }}-{{ $h['tipo'] }}-{{ $h['superficie'] ?? 'gral' }}" class="hover:bg-gray-50">
                                <td class="px-4 py-2 font-mono font-semibold text-gray-800">
                                    {{ $h['diente'] }}
                                </td>
                                <td class="px-4 py-2 text-gray-600">
                                    @if ($h['tipo'] === 'general')
                                        <span class="italic text-gray-400">(Diente completo)</span>
                                    @else
                                        {{ ucfirst($h['superficie']) }}
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    @php
                                        $colores = [
                                            'CARIES'              => 'bg-red-100 text-red-700',
                                            'OBTURADO'            => 'bg-blue-100 text-blue-700',
                                            'FRACTURA'            => 'bg-orange-100 text-orange-700',
                                            'EXTRACCION_INDICADA' => 'bg-yellow-100 text-yellow-700',
                                            'CORONA'              => 'bg-purple-100 text-purple-700',
                                            'ENDODONCIA'          => 'bg-violet-100 text-violet-700',
                                            'IMPLANTE'            => 'bg-sky-100 text-sky-700',
                                            'EXTRAIDO'            => 'bg-gray-200 text-gray-700',
                                            'PUENTE'              => 'bg-green-100 text-green-700',
                                        ];
                                        $clase = $colores[$h['estado']] ?? 'bg-gray-100 text-gray-700';
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $clase }}">
                                        {{ \App\Models\Odontograma::estadosLegibles()[$h['estado']] ?? $h['estado'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    @unless ($soloLectura)
                                        <div class="inline-flex gap-1">
                                            @if ($h['tipo'] === 'general')
                                                <button wire:click="quitarEstadoGeneral({{ $h['diente'] }})"
                                                        title="Quitar estado general"
                                                        class="p-1 text-red-600 hover:bg-red-50 rounded">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            @else
                                                <button wire:click="quitarHallazgo({{ $h['diente'] }}, '{{ $h['superficie'] }}')"
                                                        title="Quitar hallazgo"
                                                        class="p-1 text-red-600 hover:bg-red-50 rounded">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            @endif

                                            <button wire:click="limpiarDiente({{ $h['diente'] }})"
                                                    title="Limpiar todo el diente"
                                                    class="p-1 text-amber-600 hover:bg-amber-50 rounded">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
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
    {{-- 3. PANEL DE REGISTRO DE HALLAZGOS --}}
    {{-- ============================================ --}}
    <div class="grid grid-row-1 lg:grid-row-3 gap-4">

        {{-- registro --}}
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 {{ $soloLectura ? 'opacity-60 pointer-events-none' : '' }}">
            <h3 class="text-sm font-bold text-gray-800 mb-4">Registrar Nuevo Hallazgo</h3>

            <div class="space-y-3">
                {{-- Diente: combobox con búsqueda --}}
                <div
                    x-data="{
                        abierto: false,
                        busqueda: '',
                        seleccionado: @entangle('dienteSeleccionado'),
                        dientes: @js($dientes),
                        get filtrados() {
                            if (this.busqueda === '') return this.dientes;
                            return this.dientes.filter(d => String(d).includes(this.busqueda));
                        },
                        seleccionar(d) {
                            this.seleccionado = String(d);
                            this.busqueda = '';
                            this.abierto = false;
                        },
                        init() {
                            this.$watch('seleccionado', () => { this.abierto = false; });
                        }
                    }"
                    @click.away="abierto = false"
                    class="relative"
                >
                    <label class="block text-[10px] font-semibold text-gray-600 uppercase mb-1">Diente</label>

                    <button type="button"
                            @click="abierto = !abierto"
                            @if($soloLectura) disabled @endif
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-left flex items-center justify-between bg-white hover:border-gray-400 disabled:bg-gray-100">
                        <span x-text="seleccionado ? 'Diente ' + seleccionado : 'Selecciona un diente'" class="text-gray-700"></span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="abierto"
                        x-cloak
                        x-transition
                        class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-hidden">

                        <div class="p-2 border-b border-gray-100">
                            <input type="text"
                                x-model="busqueda"
                                placeholder="Buscar diente..."
                                class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-teal-500">
                        </div>

                        <div class="max-h-44 overflow-y-auto">
                            <template x-for="d in filtrados" :key="d">
                                <button type="button"
                                        @click="seleccionar(d)"
                                        :class="seleccionado == d ? 'bg-teal-50 text-teal-700 font-semibold' : 'text-gray-700 hover:bg-gray-50'"
                                        class="w-full px-3 py-2 text-left text-sm">
                                    Diente <span x-text="d"></span>
                                </button>
                            </template>
                            <template x-if="filtrados.length === 0">
                                <p class="px-3 py-2 text-xs text-gray-500 text-center">Sin resultados</p>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Aplicar a --}}
                <div>
                    <label class="block text-[10px] font-semibold text-gray-600 uppercase mb-1">Aplicar a</label>
                    <select wire:model.live="modoDienteCompleto"
                            @if($soloLectura) disabled @endif
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-100">
                        <option value="0">Superficie</option>
                        <option value="1">Todo el diente</option>
                    </select>
                </div>

                {{-- Superficie --}}
                <div>
                    <label class="block text-[10px] font-semibold text-gray-600 uppercase mb-1">Superficie</label>
                    <select wire:model="superficieSeleccionada"
                            @if($modoDienteCompleto || $soloLectura) disabled @endif
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-100 disabled:text-gray-400">
                        @foreach ($superficies as $sup)
                            <option value="{{ $sup }}">{{ ucfirst($sup) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Estado --}}
                <div>
                    <label class="block text-[10px] font-semibold text-gray-600 uppercase mb-1">Estado</label>
                    <select wire:model="estadoSeleccionado"
                            @if($soloLectura) disabled @endif
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-100">
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
            </div>

            @unless ($soloLectura)
                <div class="flex flex-col gap-2 mt-4 pt-3 border-t border-gray-100">
                    <button wire:click="aplicar" type="button"
                            class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700 inline-flex items-center justify-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Aplicar {{ $modoDienteCompleto ? 'al diente' : 'a la superficie' }}
                    </button>

                    @if (!$modoDienteCompleto)
                        <button wire:click="aplicarATodasLasSuperficies" type="button"
                                class="w-full px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600 inline-flex items-center justify-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                            Aplicar a TODAS las superficies
                        </button>
                    @endif
                </div>
            @endunless
        </div>

        <div class="lg:col-span-1 bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs font-semibold text-gray-600 uppercase mb-3">Leyenda</p>
            <div class="space-y-1.5 text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded bg-red-500"></span>
                    <span class="text-gray-600">Superficie con hallazgo</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded bg-gray-200"></span>
                    <span class="text-gray-600">Superficie sana</span>
                </div>
            </div>

            <p class="text-[10px] text-gray-500 mt-3 italic">
                @if ($soloLectura)
                    Esta consulta ya no puede editarse.
                @elseif ($consultaId)
                    Al guardar, se actualizará el odontograma de esta consulta.
                @else
                    Al guardar la consulta, se creará el odontograma histórico. Se parte del último registro del paciente.
                @endif
            </p>
        </div>
    </div>
</div>