<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use App\Models\Gasto;
use App\Models\Cuenta;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $fecha_desde = '';
    public $fecha_hasta = '';

    // Modal
    public bool $modalAbierto = false;
    public ?int $gastoId = null;
    public $concepto = '';
    public $descripcion = '';
    public $monto = '';
    public $moneda = 'bs';
    public $cuenta_id = '';
    public $fecha_gasto = '';
    public $referencia = '';
    public $tasa_cambio = '';
    public $es_tasa_preferencial = false;

    public function mount(): void
    {
        $this->fecha_desde = now()->startOfMonth()->format('Y-m-d');
        $this->fecha_hasta = now()->format('Y-m-d');
    }
    
    public function updatedMoneda($value): void
    {
        // Si cambia a dólar o euro, cargamos la tasa correspondiente
        if ($value !== 'bs') {
            $this->es_tasa_preferencial = false;
            $this->actualizarTasaDesdeBD($value);
        } else {
            // Si cambia a Bolívares, limpiamos la tasa
            $this->tasa_cambio = null;
        }
    }

    public function updatedEsTasaPreferencial($value): void
    {
        if ($value) {
            // Si marca el checkbox, limpiamos el campo para que el usuario escriba la tasa
            $this->tasa_cambio = null; 
        } else {
            // Si desmarca y está en Bs, limpia el campo
            if ($this->moneda === 'bs') {
                $this->tasa_cambio = null;
            } else {
                // Si desmarca y está en dólar/euro, vuelve a cargar la tasa del sistema
                $this->actualizarTasaDesdeBD($this->moneda);
            }
        }
    }

    private function actualizarTasaDesdeBD($moneda)
    {
        $tasaActual = \App\Models\Tasa::predeterminada();
        if ($tasaActual) {
            $cambio = $tasaActual->cambioActualDe($moneda);
            $this->tasa_cambio = $cambio ? $cambio->monto : '';
        }
    }

    public function updatingSearch(): void { $this->resetPage(); }

    #[On('gasto-guardado')]
    public function refrescar(): void {}

    public function abrirModal(): void
    {
        $this->resetValidation();
        $this->reset(['gastoId', 'concepto', 'descripcion', 'monto', 'cuenta_id', 'referencia', 'tasa_cambio']);
        
        $this->moneda = 'bs';
        $this->es_tasa_preferencial = false;
        $this->fecha_gasto = now()->format('Y-m-d');
        
        $this->modalAbierto = true;
    }

    public function editar(int $id): void
    {
        $gasto = Gasto::find($id);
        if (!$gasto) return;

        $this->gastoId = $gasto->id;
        $this->concepto = $gasto->concepto;
        $this->descripcion = $gasto->descripcion;
        $this->monto = $gasto->monto;
        $this->moneda = $gasto->moneda;
        $this->cuenta_id = $gasto->cuenta_id;
        $this->fecha_gasto = $gasto->fecha_gasto->format('Y-m-d');
        $this->referencia = $gasto->referencia;
        $this->tasa_cambio = $gasto->tasa_cambio;
        $this->modalAbierto = true;
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'concepto' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'monto' => 'required|numeric|min:0.01',
            'moneda' => 'required|in:dolar,euro,bs',
            'tasa_cambio' => 'nullable|numeric', 
            'cuenta_id' => 'nullable|exists:cuentas,id',
            'fecha_gasto' => 'required|date',
            'referencia' => 'nullable|string|max:255',
        ]);

        $datos['registrado_por'] = auth()->id();

        if ($this->gastoId) {
            Gasto::find($this->gastoId)?->update($datos);
            session()->flash('success', 'Gasto actualizado correctamente.');
        } else {
            Gasto::create($datos);
            session()->flash('success', 'Gasto registrado correctamente.');
        }

        $this->modalAbierto = false;
        $this->dispatch('gasto-guardado');
    }

    public function eliminar(int $id): void
    {
        Gasto::find($id)?->delete();
        session()->flash('success', 'Gasto eliminado.');
    }

    public function with(): array
    {
        $query = Gasto::with(['cuenta', 'registrador']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('concepto', 'like', "%{$this->search}%")
                  ->orWhere('referencia', 'like', "%{$this->search}%");
            });
        }

        if ($this->fecha_desde) {
            $query->whereDate('fecha_gasto', '>=', $this->fecha_desde);
        }
        if ($this->fecha_hasta) {
            $query->whereDate('fecha_gasto', '<=', $this->fecha_hasta);
        }

        return [
            'gastos' => $query->orderByDesc('fecha_gasto')->paginate(15),
            'cuentas' => Cuenta::where('activa', true)->orderBy('nombre')->get(),
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <button wire:click="abrirModal" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-lg shadow-sm inline-flex items-center justify-center">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nuevo Gasto
        </button>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtros --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-4 bg-white p-3 rounded-xl shadow-sm border border-gray-100">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar concepto o referencia..." class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
        <input type="date" wire:model.live="fecha_desde" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
        <input type="date" wire:model.live="fecha_hasta" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
        <div class="flex items-center justify-end text-xs text-gray-500">
            Mostrando {{ $gastos->total() }} registros
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Concepto</th>
                        <th class="px-4 py-3">Cuenta</th>
                        <th class="px-4 py-3 text-right">Monto</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($gastos as $gasto)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $gasto->fecha_gasto->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $gasto->concepto }}</div>
                                <div class="text-xs text-gray-500">{{ $gasto->referencia ?: 'Sin referencia' }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $gasto->cuenta?->nombre ?? 'No asignada' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-red-600">
                                {{ $gasto->moneda === 'bs' ? 'Bs ' : ($gasto->moneda === 'dolar' ? '$ ' : '€ ') }}{{ number_format($gasto->monto, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button wire:click="editar({{ $gasto->id }})" class="p-1 text-blue-600 hover:bg-blue-50 rounded mr-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="eliminar({{ $gasto->id }})" wire:confirm="¿Eliminar este gasto?" class="p-1 text-red-600 hover:bg-red-50 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No hay gastos registrados en este período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($gastos->hasPages())
            <div class="px-4 py-3 border-t bg-gray-50">{{ $gastos->links() }}</div>
        @endif
    </div>

    {{-- MODAL --}}
    @if ($modalAbierto)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="cerrarModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-800">{{ $gastoId ? 'Editar Gasto' : 'Nuevo Gasto' }}</h3>
                    <button wire:click="cerrarModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="guardar" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Concepto *</label>
                        <input type="text" wire:model="concepto" placeholder="Ej: Pago de alquiler" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        @error('concepto') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Monto *</label>
                            <input type="number" step="0.01" wire:model="monto" placeholder="0.00" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('monto') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Moneda *</label>
                            <select wire:model.live="moneda" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="bs">Bolívares (Bs)</option>
                                <option value="dolar">Dólares (USD)</option>
                                <option value="euro">Euros (EUR)</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-gray-700">Tasa de Cambio</label>
                            
                            <label class="inline-flex items-center cursor-pointer">
                                {{-- El checkbox siempre está disponible --}}
                                <input type="checkbox" wire:model.live="es_tasa_preferencial" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                <span class="ml-1.5 text-xs text-gray-600">Usar tasa preferencial</span>
                            </label>
                        </div>

                        <div class="relative">
                            <input type="number" step="0.0001" 
                                   wire:model="tasa_cambio" 
                                   placeholder="{{ ($moneda === 'bs' && !$es_tasa_preferencial) ? 'No aplica' : '0.0000' }}" 
                                   @if($moneda === 'bs' && !$es_tasa_preferencial) disabled @endif
                                   class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600 disabled:bg-gray-100 disabled:text-gray-400">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-xs font-bold">Bs</span>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-500 mt-1">
                            @if($moneda === 'bs' && !$es_tasa_preferencial)
                                No se requiere tasa para montos en Bolívares.
                            @else
                                Tasa aplicada al momento del registro. Escribe el valor manualmente o usa la tasa del sistema.
                            @endif
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Fecha *</label>
                            <input type="date" wire:model="fecha_gasto" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('fecha_gasto') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Cuenta (Opcional)</label>
                            <select wire:model="cuenta_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="">Seleccione cuenta...</option>
                                @foreach ($cuentas as $c)
                                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Referencia / Factura</label>
                        <input type="text" wire:model="referencia" placeholder="Nro de factura o comprobante" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Descripción</label>
                        <textarea wire:model="descripcion" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrarModal" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                        <button type="submit" class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-semibold">Guardar Gasto</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>