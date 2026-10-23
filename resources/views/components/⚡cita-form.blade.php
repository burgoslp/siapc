<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Medico;

new class extends Component
{
    public ?int $citaId = null;
    public bool $abierto = false;

    public $paciente_id = '';
    public $medico_id = '';
    public $fecha = '';
    public $hora = '';
    public $motivo = '';
    public $estado = 'PENDIENTE';
    public $tarifa_id = '';
    public $precio = '';
    public $descuento = 0;
    public $total = '';
    public $tasa_id = '';
    public $moneda = 'dolar';
    public $cambio_id = null;
    public $monto_tasa_cambio = null;
    public $especialidad_id = null;
    

    #[On('nueva-cita')]
    public function abrirNueva(): void
    {
        $this->reset();
        $this->estado = 'PENDIENTE';
        $this->fecha = now()->format('Y-m-d');
        $this->hora = now()->addHour()->format('H:i');
        $this->citaId = null;
        $this->moneda = 'dolar';

        // Autoselecciona la tasa predeterminada
        $tasaPredeterminada = \App\Models\Tasa::predeterminada();
        if ($tasaPredeterminada) {
            $this->tasa_id = $tasaPredeterminada->id;
            $this->cargarCambioDeTasa();
        }

        $this->abierto = true;
    }
    
    #[On('nueva-cita-en')]
    public function abrirNuevaConFecha(string $fecha, string $hora = '09:00'): void
    {
        $this->reset();
        $this->estado = 'PENDIENTE';
        $this->fecha = $fecha;
        $this->hora = $hora;
        $this->citaId = null;
        $this->moneda = 'dolar';

        $tasaPredeterminada = \App\Models\Tasa::predeterminada();
        if ($tasaPredeterminada) {
            $this->tasa_id = $tasaPredeterminada->id;
            $this->cargarCambioDeTasa();
        }

        $this->abierto = true;
    }

    #[On('editar-cita')]
    public function abrirEdicion(int $id): void
    {
        $cita = Cita::find($id);
        if (!$cita) return;

        $this->citaId = $cita->id;
        $this->paciente_id = $cita->paciente_id;
        $this->medico_id = $cita->medico_id;
        $this->fecha = $cita->fecha->format('Y-m-d');
        $this->hora = $cita->fecha->format('H:i');
        $this->motivo = $cita->motivo ?? '';
        $this->estado = $cita->estado;

        // Facturación
        $this->tarifa_id = $cita->tarifa_id ?? '';
        $this->precio = $cita->precio ?? '';
        $this->descuento = $cita->descuento ?? 0;
        $this->total = $cita->total ?? '';
        $this->tasa_id = $cita->tasa_id ?? '';
        $this->cambio_id = $cita->cambio_id;
        $this->monto_tasa_cambio = $cita->monto_tasa_cambio;

        // Detecta la moneda del cambio guardado
        if ($cita->cambio_id) {
            $this->moneda = \App\Models\Cambio::find($cita->cambio_id)?->moneda ?? 'dolar';
        } else {
            $this->moneda = 'dolar';
        }

        $this->abierto = true;
    }

    #[On('paciente-seleccionado')]
    public function setPaciente(?int $id): void
    {
        $this->paciente_id = $id ?? '';
    }

    #[On('medico-seleccionado')]
    public function setMedico(?int $id): void
    {
        $this->medico_id = $id ?? '';
        
        // Disparar la lógica de autoselección de tarifa y tasa
        if ($id) {
            $this->updatedMedicoId();
        }
    }

    #[On('tarifa-seleccionada')]
    public function setTarifa(?int $id): void
    {
        $this->tarifa_id = $id ?? '';
        if ($id) {
            $this->updatedTarifaId();
        }
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'medico_id' => 'required|exists:medicos,id',
            'fecha' => 'required|date',
            'hora' => 'required|date_format:H:i',
            'motivo' => 'nullable|string',
            'estado' => 'required|in:PENDIENTE,CONFIRMADA,CANCELADA,NO_ASISTIO',
            'tarifa_id' => 'nullable|exists:tarifas,id',
            'precio' => 'nullable|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'tasa_id' => 'nullable|exists:tasas,id',
            'moneda' => 'nullable|in:dolar,euro',
            'cambio_id' => 'nullable|exists:cambios,id',
            'monto_tasa_cambio' => 'nullable|numeric|min:0',
        ]);

        // Combinar fecha + hora
        $fechaHora = \Carbon\Carbon::parse("{$this->fecha} {$this->hora}");

        // Validar conflicto de horario
        if ($this->estado !== 'CANCELADA') {
            $hayConflicto = Cita::hayConflicto(
                (int) $this->medico_id,
                $fechaHora->toDateTimeString(),
                $this->citaId
            );

            if ($hayConflicto) {
                $this->addError('hora', 'El médico ya tiene una cita a esa hora. Elige otro horario.');
                return;
            }
        }

         $data = [
            'paciente_id' => $this->paciente_id,
            'medico_id' => $this->medico_id,
            'fecha' => $fechaHora,
            'motivo' => $this->motivo ?: null,
            'estado' => $this->estado,

            // Facturación
            'tarifa_id' => $this->tarifa_id ?: null,
            'precio' => $this->precio ?: null,
            'descuento' => $this->descuento ?: 0,
            'total' => $this->total ?: null,
            'tasa_id' => $this->tasa_id ?: null,
            'cambio_id' => $this->cambio_id ?: null,
            'monto_tasa_cambio' => $this->monto_tasa_cambio ?: null,
        ];

        if ($this->citaId) {
            Cita::find($this->citaId)?->update($data);
            session()->flash('success', 'Cita actualizada correctamente.');
        } else {
            Cita::create($data);
            session()->flash('success', 'Cita registrada correctamente.');
        }

        $this->dispatch('cita-guardada');
        $this->abierto = false;
    }

    public function updatedMedicoId(): void
    {
        if (!$this->medico_id) {
            $this->especialidad_id = null;
            return;
        }

        // ============================
        // Obtener la especialidad del médico
        // ============================
        $medico = \App\Models\Medico::with('especialidades')->find($this->medico_id);
        if ($medico) {
            // Tomamos la primera especialidad del médico (o null si no tiene)
            $this->especialidad_id = $medico->especialidades->first()?->id;
        }
       

        // ============================
        // Autoseleccionar tasa predeterminada
        // ============================
        $tasaPredeterminada = \App\Models\Tasa::predeterminada();

        if ($tasaPredeterminada) {
            $this->tasa_id = $tasaPredeterminada->id;
            $this->cargarCambioDeTasa();
        }
    }

    public function updatedTarifaId(): void
    {
        if (!$this->tarifa_id) return;

        $tarifa = \App\Models\Tarifa::find($this->tarifa_id);
        if ($tarifa) {
            $this->precio = $tarifa->precio_usd;
            $this->recalcularTotal();
        }
    }

    public function updatedPrecio(): void
    {
        $this->recalcularTotal();
    }

    public function updatedDescuento(): void
    {
        $this->recalcularTotal();
    }

    private function recalcularTotal(): void
    {
        $precio = (float) ($this->precio ?: 0);
        $descuento = (float) ($this->descuento ?: 0);
        $this->total = max(0, $precio - $descuento);
    }

    public function updatedTasaId(): void
    {
        $this->cargarCambioDeTasa();
    }

    public function updatedMoneda(): void
    {
        $this->cargarCambioDeTasa();
    }

    private function cargarCambioDeTasa(): void
    {
        if (!$this->tasa_id) {
            $this->cambio_id = null;
            $this->monto_tasa_cambio = null;
            return;
        }

        $tasa = \App\Models\Tasa::find($this->tasa_id);
        if (!$tasa) return;

        $cambio = $tasa->cambioActualDe($this->moneda);

        if ($cambio) {
            $this->cambio_id = $cambio->id;
            $this->monto_tasa_cambio = $cambio->monto;
        } else {
            $this->cambio_id = null;
            $this->monto_tasa_cambio = null;
            $this->addError('tasa_id', "La tasa {$tasa->etiqueta} no tiene cotización en {$this->moneda}.");
        }
    }
};
?>

<div>
    @if ($abierto)
        {{-- Fondo --}}
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        {{-- Modal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">

                {{-- Encabezado --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800">
                        {{ $citaId ? 'Editar Cita' : 'Nueva Cita' }}
                    </h3>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Formulario --}}
                <form wire:submit="guardar" class="px-6 py-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Paciente --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Paciente *</label>
                            
                            <livewire:paciente-select :value="$paciente_id ?: null" :key="'paciente-select-' . ($citaId ?? 'nuevo') . '-' . $paciente_id"/>
                            
                            @error('paciente_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Médico --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Médico *</label>
                            
                            <livewire:medico-select :value="$medico_id ?: null" :key="'medico-select-' . ($citaId ?? 'nuevo') . '-' . $medico_id"/>
                            
                            @error('medico_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Fecha --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Fecha *</label>
                            <input type="date" wire:model="fecha"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('fecha') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Hora --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Hora *</label>
                            <input type="time" wire:model="hora"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('hora') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Estado --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Estado *</label>
                            <select wire:model="estado"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="PENDIENTE">Pendiente</option>
                                <option value="CONFIRMADA">Confirmada</option>
                                <option value="CANCELADA">Cancelada</option>
                                <option value="NO_ASISTIO">No asistió</option>
                            </select>
                            @error('estado') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Motivo --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Motivo</label>
                            <textarea wire:model="motivo" rows="3"
                                      placeholder="Motivo de la consulta..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"></textarea>
                            @error('motivo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    {{-- ============================================ --}}
                    {{-- SECCIÓN: FACTURACIÓN --}}
                    {{-- ============================================ --}}
                    <div class="sm:col-span-2 pt-4 mt-2 border-t border-gray-100">
                        <h4 class="text-sm font-bold text-teal-700 uppercase tracking-wide mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Facturación
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                            {{-- Tarifa --}}
                            <div class="sm:col-span-3">
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tarifa</label>
                                
                                <livewire:tarifa-select 
                                    :value="$tarifa_id ?: null" 
                                    :especialidadId="$especialidad_id" 
                                    :key="'tarifa-select-' . ($citaId ?? 'nuevo') . '-' . $especialidad_id . '-' . $tarifa_id" 
                                />
                                
                                @error('tarifa_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            {{-- Precio --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Precio (USD)</label>
                                <input type="number" step="0.01" wire:model.live="precio"
                                    placeholder="0.00"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('precio') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            {{-- Descuento --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Descuento (USD)</label>
                                <input type="number" step="0.01" wire:model.live="descuento"
                                    placeholder="0.00"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('descuento') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            {{-- Total USD --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Total (USD)</label>
                                <input type="text" wire:model="total" readonly
                                    class="w-full px-3 py-2 border border-teal-200 bg-teal-50 rounded-lg text-sm font-bold text-teal-800">
                            </div>
                        </div>

                        {{-- Subsección: Tasa y moneda --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4 pt-4 border-t border-gray-100">

                            {{-- Tasa --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tasa aplicada</label>
                                <select wire:model.live="tasa_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="">Sin tasa asignada</option>
                                    @foreach (\App\Models\Tasa::where('activa', true)->orderByDesc('predeterminado')->orderBy('nombre')->get() as $t)
                                        <option value="{{ $t->id }}">
                                            {{ $t->etiqueta }}
                                            @if ($t->predeterminado) (predeterminada) @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('tasa_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            {{-- Moneda --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Moneda</label>
                                <select wire:model.live="moneda"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="dolar">Dólar (USD)</option>
                                    <option value="euro">Euro (EUR)</option>
                                </select>
                                @error('moneda') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Resumen del cálculo --}}
                        @if ($total && $monto_tasa_cambio)
                            @php
                                $totalBs = (float) $total * (float) $monto_tasa_cambio;
                            @endphp
                            <div class="mt-3 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-600">
                                        Tasa aplicada
                                        ({{ $moneda === 'dolar' ? 'USD' : 'EUR' }}):
                                    </span>
                                    <span class="font-semibold text-gray-800">
                                        Bs {{ number_format($monto_tasa_cambio, 4) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-sm mt-1">
                                    <span class="font-semibold text-gray-700">Total en Bolívares:</span>
                                    <span class="font-bold text-teal-700 text-lg">
                                        Bs {{ number_format($totalBs, 2) }}
                                    </span>
                                </div>
                            </div>
                        @elseif ($total && !$monto_tasa_cambio)
                            <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                La tasa seleccionada no tiene cotización para esta moneda. Se guardará el total sin equivalencia en Bs.
                            </div>
                        @endif
                    </div>

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrar"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium">
                            {{ $citaId ? 'Guardar Cambios' : 'Registrar Cita' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>