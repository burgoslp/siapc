<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use App\Models\Recurrencia;
use App\Models\Cuenta;
use App\Models\Gasto;
use App\Models\Tasa;
use Carbon\Carbon;

new class extends Component
{
    use WithPagination;

    public $search = '';

    // Modal Recurrencia
    public bool $modalAbierto = false;
    public ?int $recurrenciaId = null;
    public $concepto = '';
    public $monto_estimado = '';
    public $moneda = 'bs';
    public $frecuencia = 'MENSUAL';
    public $fecha_pago = ''; 
    public $dia_semana = ''; 
    public $cuenta_id = '';
    public $notas = '';
    public $activo = true;

    // Modal Pago
    public bool $modalPagoAbierto = false;
    public ?int $recurrenciaPagoId = null;
    public $monto_pago = '';
    public $moneda_pago = '';
    public $cuenta_pago_id = '';
    public $fecha_pago_transaccion = '';
    public $tasa_pago = '';
    public $es_tasa_preferencial_pago = false;
    //lista desplegable
    public $recurrenciaExpandida = null;

    public function updatingSearch(): void { $this->resetPage(); }

    #[On('recurrencia-guardada')]
    public function refrescar(): void {}

    public function toggleExpandir(int $id): void
    {
        if ($this->recurrenciaExpandida === $id) {
            $this->recurrenciaExpandida = null; // Se cierra
        } else {
            $this->recurrenciaExpandida = $id; // Se abre
        }
    }

    // ==========================================
    // MODAL RECURRENCIA (CREAR/EDITAR)
    // ==========================================
    
    // 👇 ESTE ES EL MÉTODO QUE FALTABA 👇
    public function abrirModal(): void
    {
        $this->resetValidation();
        $this->reset(['recurrenciaId', 'concepto', 'monto_estimado', 'moneda', 'frecuencia', 'fecha_pago', 'dia_semana', 'cuenta_id', 'notas', 'activo']);
        $this->moneda = 'bs';
        $this->frecuencia = 'MENSUAL';
        $this->fecha_pago = now()->format('Y-m-d');
        $this->activo = true;
        $this->modalAbierto = true;
    }
    // 👆 FIN DEL MÉTODO QUE FALTABA 👆

    public function editar(int $id): void
    {
        $rec = Recurrencia::find($id);
        if (!$rec) return;

        $this->recurrenciaId = $rec->id;
        $this->concepto = $rec->concepto;
        $this->monto_estimado = $rec->monto_estimado;
        $this->moneda = $rec->moneda;
        $this->frecuencia = $rec->frecuencia;
        $this->fecha_pago = $rec->fecha_pago ? $rec->fecha_pago->format('Y-m-d') : '';
        $this->dia_semana = $rec->dia_semana ?? '';
        $this->cuenta_id = $rec->cuenta_id;
        $this->notas = $rec->notas;
        $this->activo = $rec->activo;
        
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
            'monto_estimado' => 'required|numeric|min:0.01',
            'moneda' => 'required|in:bs,dolar,euro',
            'frecuencia' => 'required|in:MENSUAL,SEMANAL,ANUAL',
            'cuenta_id' => 'nullable|exists:cuentas,id',
            'notas' => 'nullable|string',
            'activo' => 'boolean',
            
            'fecha_pago' => 'required_unless:frecuencia,SEMANAL|nullable|date',
            'dia_semana' => 'required_if:frecuencia,SEMANAL|nullable|in:LUNES,MARTES,MIERCOLES,JUEVES,VIERNES,SABADO,DOMINGO',
        ]);

        if ($this->frecuencia === 'SEMANAL') {
            $datos['fecha_pago'] = null;
        } else {
            $datos['dia_semana'] = null;
        }

        if ($this->recurrenciaId) {
            Recurrencia::find($this->recurrenciaId)?->update($datos);
            session()->flash('success', 'Gasto fijo actualizado correctamente.');
        } else {
            Recurrencia::create($datos);
            session()->flash('success', 'Gasto fijo registrado correctamente.');
        }

        $this->modalAbierto = false;
        $this->dispatch('recurrencia-guardada');
    }

    public function toggleActivo(int $id): void
    {
        $rec = Recurrencia::find($id);
        if ($rec) {
            $rec->update(['activo' => !$rec->activo]);
            session()->flash('success', 'Estado actualizado.');
        }
    }

    // ==========================================
    // MODAL PAGO
    // ==========================================
    public function abrirModalPago(int $id): void
    {
        $this->resetValidation();
        $rec = Recurrencia::find($id);
        if (!$rec) return;

        $this->recurrenciaPagoId = $rec->id;
        $this->monto_pago = $rec->monto_estimado;
        $this->moneda_pago = $rec->moneda;
        $this->cuenta_pago_id = $rec->cuenta_id;
        $this->fecha_pago_transaccion = now()->format('Y-m-d');
        
        $this->es_tasa_preferencial_pago = false; 
        $this->tasa_pago = '';
        
        if ($rec->moneda !== 'bs') {
            $tasa = Tasa::predeterminada();
            $cambio = $tasa?->cambioActualDe($rec->moneda);
            $this->tasa_pago = $cambio?->monto;
        }

        $this->modalPagoAbierto = true;
    }

    public function updatedEsTasaPreferencialPago($value): void
    {
        if ($value) {
            $this->tasa_pago = null;
        } else {
            if ($this->moneda_pago !== 'bs') {
                $tasa = Tasa::predeterminada();
                $cambio = $tasa?->cambioActualDe($this->moneda_pago);
                $this->tasa_pago = $cambio?->monto;
            }
        }
    }

    public function cerrarModalPago(): void
    {
        $this->modalPagoAbierto = false;
    }

    public function pagar(): void
    {
        $this->validate([
            'monto_pago' => 'required|numeric|min:0.01',
            'fecha_pago_transaccion' => 'required|date',
            'tasa_pago' => 'nullable|numeric|min:0',
            'cuenta_pago_id' => 'required|exists:cuentas,id',
        ]);

        $rec = Recurrencia::find($this->recurrenciaPagoId);
        if (!$rec) return;

        \DB::transaction(function () use ($rec) {
            $gasto = Gasto::create([
                'tipo_gasto' => 'FIJO',
                'recurrencia_id' => $rec->id,
                'concepto' => $rec->concepto . ' - ' . now()->translatedFormat('F Y'),
                'monto' => $this->monto_pago,
                'moneda' => $this->moneda_pago,
                'tasa_cambio' => $this->tasa_pago ?: null,
                'cuenta_id' => $this->cuenta_pago_id,
                'fecha_gasto' => $this->fecha_pago_transaccion,
                'registrado_por' => auth()->id(),
            ]);

            $cuenta = Cuenta::find($this->cuenta_pago_id);
            if ($cuenta) {
                $montoEnCuenta = (float) $this->monto_pago;
                if ($cuenta->moneda !== $this->moneda_pago) {
                    if ($cuenta->moneda === 'bs' && in_array($this->moneda_pago, ['dolar', 'euro'])) {
                        $montoEnCuenta = (float) $this->monto_pago * (float) $this->tasa_pago;
                    }
                }

                $cuenta->movimientos()->create([
                    'tipo' => 'EGRESO',
                    'origen_tipo' => 'GASTO',
                    'origen_id' => $gasto->id,
                    'monto' => $montoEnCuenta,
                    'saldo_despues' => $cuenta->saldo_actual - $montoEnCuenta,
                    'fecha' => now(),
                    'descripcion' => "Pago Fijo: {$rec->concepto}",
                ]);
            }
        });

        session()->flash('success', 'Pago registrado correctamente y descontado de la tesorería.');
        $this->modalPagoAbierto = false;
    }

    // ==========================================
    // VISTA
    // ==========================================
    public function with(): array
    {
        $query = Recurrencia::with(['cuenta']);

        if ($this->search) {
            $query->where('concepto', 'like', "%{$this->search}%");
        }

        $recurrencias = $query->orderByDesc('activo')->orderBy('id')->paginate(15);

        $recurrencias->getCollection()->transform(function ($rec) {
            $hoy = Carbon::today();
            
            $ultimoGasto = Gasto::where('recurrencia_id', $rec->id)
                ->orderByDesc('fecha_gasto')
                ->first();
                
            $rec->ultimo_pago = $ultimoGasto ? $ultimoGasto->fecha_gasto : null;

            $proximoPago = $hoy->copy();

            if ($rec->frecuencia === 'MENSUAL') {
                $dia = $rec->fecha_pago ? Carbon::parse($rec->fecha_pago)->day : 1;
                $proximoPago->day($dia);
                if ($proximoPago->isPast()) $proximoPago->addMonth();
                
                if ($ultimoGasto && $ultimoGasto->fecha_gasto >= $hoy->copy()->day($dia)) {
                    $proximoPago->addMonth();
                }
            } elseif ($rec->frecuencia === 'SEMANAL') {
                $diasMap = ['LUNES'=>1, 'MARTES'=>2, 'MIERCOLES'=>3, 'JUEVES'=>4, 'VIERNES'=>5, 'SABADO'=>6, 'DOMINGO'=>7];
                $proximoPago->dayOfWeek($diasMap[$rec->dia_semana] ?? 1);
                if ($proximoPago->isPast()) $proximoPago->addWeek();
                
                if ($ultimoGasto && $ultimoGasto->fecha_gasto >= $hoy->copy()->startOfWeek()) {
                    $proximoPago->addWeek();
                }
            } elseif ($rec->frecuencia === 'ANUAL') {
                $mes = $rec->fecha_pago ? Carbon::parse($rec->fecha_pago)->month : 1;
                $dia = $rec->fecha_pago ? Carbon::parse($rec->fecha_pago)->day : 1;
                $proximoPago->setDate($hoy->year, $mes, $dia);
                if ($proximoPago->isPast()) $proximoPago->addYear();
                
                if ($ultimoGasto && $ultimoGasto->fecha_gasto >= $hoy->copy()->setDate($hoy->year, $mes, $dia)) {
                    $proximoPago->addYear();
                }
            }

            if ($ultimoGasto) {
                $pagosCiclo = Gasto::where('recurrencia_id', $rec->id)
                    ->where('fecha_gasto', '>=', $hoy->copy()->subMonth())
                    ->count();
                
                if ($pagosCiclo > 1) {
                    if ($rec->frecuencia === 'MENSUAL') $proximoPago->addMonths($pagosCiclo - 1);
                    if ($rec->frecuencia === 'SEMANAL') $proximoPago->addWeeks($pagosCiclo - 1);
                    if ($rec->frecuencia === 'ANUAL') $proximoPago->addYears($pagosCiclo - 1);
                }
            }

            $rec->proximo_pago = $proximoPago;
            $rec->dias_restantes = $hoy->diffInDays($proximoPago, false); 

            return $rec;
        });

        return [
            'recurrencias' => $recurrencias,
            'cuentas' => Cuenta::where('activa', true)->orderBy('nombre')->get(),
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div class="relative w-full max-w-md">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar gasto fijo..." class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <button wire:click="abrirModal" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-lg shadow-sm inline-flex items-center justify-center">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nuevo Gasto Fijo
        </button>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3">Concepto</th>
                        <th class="px-4 py-3 text-right">Monto Base</th>
                        <th class="px-4 py-3">Frecuencia</th>
                        <th class="px-4 py-3">Próximo Vencimiento</th>
                        <th class="px-4 py-3">Cuenta por defecto</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recurrencias as $rec)
                        <tr class="hover:bg-gray-50 {{ !$rec->activo ? 'opacity-60 bg-gray-50' : '' }}">
                            
                            {{-- Concepto --}}
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $rec->concepto }}</div>
                                @if($rec->notas)
                                    <div class="text-xs text-gray-500 truncate max-w-xs">{{ $rec->notas }}</div>
                                @endif
                            </td>

                            {{-- Monto --}}
                            <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                {{ $rec->moneda === 'bs' ? 'Bs ' : ($rec->moneda === 'dolar' ? '$ ' : '€ ') }}{{ number_format($rec->monto_estimado, 2) }}
                            </td>

                            {{-- Frecuencia --}}
                            <td class="px-4 py-3 text-gray-600">
                                {{ ucfirst(strtolower($rec->frecuencia)) }}
                                <div class="text-[10px] text-gray-500">
                                    @if($rec->frecuencia === 'SEMANAL')
                                        Cada {{ ucfirst(strtolower($rec->dia_semana)) }}
                                    @else
                                        Día {{ $rec->fecha_pago ? \Carbon\Carbon::parse($rec->fecha_pago)->day : '' }}
                                    @endif
                                </div>
                            </td>

                            {{-- Próximo Vencimiento --}}
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-800">
                                    {{ $rec->proximo_pago->format('d/m/Y') }}
                                </div>
                                @if($rec->dias_restantes < 0)
                                    <span class="text-xs font-semibold text-red-600">Vencido hace {{ abs($rec->dias_restantes) }} días</span>
                                @elseif($rec->dias_restantes == 0)
                                    <span class="text-xs font-semibold text-orange-500">¡Vence Hoy!</span>
                                @elseif($rec->dias_restantes <= 5)
                                    <span class="text-xs font-semibold text-amber-500">Faltan {{ $rec->dias_restantes }} días</span>
                                @else
                                    <span class="text-xs text-gray-500">Faltan {{ $rec->dias_restantes }} días</span>
                                @endif
                                @if($rec->ultimo_pago)
                                    <div class="text-[10px] text-gray-400 mt-0.5">Último pago: {{ $rec->ultimo_pago->format('d/m/Y') }}</div>
                                @endif
                            </td>

                            {{-- Cuenta --}}
                            <td class="px-4 py-3 text-gray-600">{{ $rec->cuenta?->nombre ?? 'No asignada' }}</td>

                            {{-- Estado --}}
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $rec->activo ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                                    {{ $rec->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>

                            {{-- Acciones --}}
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                {{-- 👇 NUEVO BOTÓN DE EXPANDIR 👇 --}}
                                <button wire:click="toggleExpandir({{ $rec->id }})" class="p-1 text-slate-500 hover:bg-slate-100 rounded mr-1 transition-transform" title="Ver pagos">
                                    <svg class="w-4 h-4 transition-transform duration-200 {{ $recurrenciaExpandida === $rec->id ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>

                                <button wire:click="abrirModalPago({{ $rec->id }})" class="p-1 text-emerald-600 hover:bg-emerald-50 rounded mr-1" title="Registrar Pago">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </button>
                                <button wire:click="editar({{ $rec->id }})" class="p-1 text-blue-600 hover:bg-blue-50 rounded mr-1" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button wire:click="toggleActivo({{ $rec->id }})" class="p-1 {{ $rec->activo ? 'text-amber-600 hover:bg-amber-50' : 'text-green-600 hover:bg-green-50' }} rounded" title="{{ $rec->activo ? 'Desactivar' : 'Activar' }}">
                                    @if($rec->activo)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </button>
                            </td>
                        </tr>

                        {{-- 👇 FILA EXPANDIBLE CON EL HISTORIAL DE PAGOS 👇 --}}
                        @if ($recurrenciaExpandida === $rec->id)
                            <tr class="bg-slate-50 border-b-2 border-slate-200">
                                <td colspan="7" class="px-6 py-4">
                                    <h4 class="text-xs font-bold text-slate-600 uppercase mb-3 text-right">Últimos 10 Pagos Registrados</h4>
                                    
                                    @php
                                        $pagosRec = $rec->gastos()->with('cuenta')->orderByDesc('fecha_gasto')->limit(10)->get();
                                    @endphp

                                    @if($pagosRec->isEmpty())
                                        <p class="text-sm text-slate-400 italic">No hay pagos registrados aún para este gasto fijo.</p>
                                    @else
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left text-xs">
                                                <thead class="text-slate-500 uppercase border-b border-slate-200">
                                                    <tr>
                                                        <th class="pb-2 pl-2">Fecha</th>
                                                        <th class="pb-2">Cuenta</th>
                                                        <th class="pb-2 text-center">Moneda</th>
                                                        <th class="pb-2 text-right">Monto</th>
                                                        <th class="pb-2 text-right">Tasa</th>
                                                        <th class="pb-2 pl-4">Concepto</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-200">
                                                    @foreach($pagosRec as $p)
                                                        <tr class="hover:bg-slate-100/50 transition">
                                                            <td class="py-2 pl-2 text-slate-700 whitespace-nowrap">{{ $p->fecha_gasto->format('d/m/Y') }}</td>
                                                            <td class="py-2 text-slate-600 whitespace-nowrap">{{ $p->cuenta?->nombre ?? 'N/A' }}</td>
                                                            
                                                            {{-- Moneda --}}
                                                            <td class="py-2 text-center">
                                                                <span class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-700 font-bold text-[10px]">
                                                                    {{ strtoupper($p->moneda) }}
                                                                </span>
                                                            </td>
                                                            
                                                            {{-- Monto --}}
                                                            <td class="py-2 text-right font-semibold text-slate-800 whitespace-nowrap">
                                                                {{ $p->moneda === 'bs' ? 'Bs ' : ($p->moneda === 'dolar' ? '$ ' : '€ ') }}{{ number_format($p->monto, 2) }}
                                                            </td>
                                                            
                                                            {{-- Tasa de Cambio --}}
                                                            <td class="py-2 text-right text-slate-600 whitespace-nowrap">
                                                                @if($p->tasa_cambio)
                                                                    {{ number_format($p->tasa_cambio, 2) }}
                                                                @else
                                                                    <span class="text-slate-400 italic">—</span>
                                                                @endif
                                                            </td>
                                                            
                                                            <td class="py-2 pl-4 text-slate-500 truncate max-w-[200px]" title="{{ $p->concepto }}">{{ $p->concepto }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        {{-- Enlace para ir a ver todos --}}
                                        <div class="mt-3 text-right">
                                            <a href="{{ route('gastos.variables.index', ['search' => $rec->concepto]) }}" class="text-xs font-semibold text-teal-600 hover:text-teal-700 inline-flex items-center">
                                                Ver historial completo
                                                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                            </a>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endif
                        {{-- FIN FILA EXPANDIBLE --}}

                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No hay gastos fijos configurados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($recurrencias->hasPages())
            <div class="px-4 py-3 border-t bg-gray-50">{{ $recurrencias->links() }}</div>
        @endif
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL CREAR/EDITAR RECURRENCIA --}}
    {{-- ============================================================ --}}
    @if ($modalAbierto)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="cerrarModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-800">{{ $recurrenciaId ? 'Editar Gasto Fijo' : 'Nuevo Gasto Fijo' }}</h3>
                    <button wire:click="cerrarModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="guardar" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Concepto *</label>
                        <input type="text" wire:model="concepto" placeholder="Ej: Alquiler del Local" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        @error('concepto') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Monto Base *</label>
                            <input type="number" step="0.01" wire:model="monto_estimado" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('monto_estimado') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Moneda *</label>
                            <select wire:model="moneda" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="bs">Bolívares (Bs)</option>
                                <option value="dolar">Dólares (USD)</option>
                                <option value="euro">Euros (EUR)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Frecuencia *</label>
                            <select wire:model.live="frecuencia" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="MENSUAL">Mensual</option>
                                <option value="SEMANAL">Semanal</option>
                                <option value="ANUAL">Anual</option>
                            </select>
                        </div>

                        {{-- Campos dinámicos --}}
                        @if ($frecuencia === 'SEMANAL')
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Día de la semana *</label>
                                <select wire:model="dia_semana" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="">Seleccione día...</option>
                                    <option value="LUNES">Lunes</option>
                                    <option value="MARTES">Martes</option>
                                    <option value="MIERCOLES">Miércoles</option>
                                    <option value="JUEVES">Jueves</option>
                                    <option value="VIERNES">Viernes</option>
                                    <option value="SABADO">Sábado</option>
                                    <option value="DOMINGO">Domingo</option>
                                </select>
                                @error('dia_semana') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        @else
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Fecha de Pago *</label>
                                <input type="date" wire:model="fecha_pago" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <p class="text-[10px] text-gray-500 mt-1">Se repetirá este día en el futuro.</p>
                                @error('fecha_pago') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Cuenta por defecto</label>
                        <select wire:model="cuenta_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Seleccione cuenta...</option>
                            @foreach ($cuentas as $c)
                                <option value="{{ $c->id }}">{{ $c->nombre }} ({{ strtoupper($c->moneda) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="activo" id="activo" class="rounded border-gray-300 text-teal-600">
                        <label for="activo" class="text-sm text-gray-700 cursor-pointer">Gasto activo (genera avisos)</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrarModal" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                        <button type="submit" class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-semibold">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- MODAL DE PAGO RÁPIDO
    {{-- ============================================================ --}}
    @if ($modalPagoAbierto)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="cerrarModalPago">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-800">Registrar Pago de Gasto Fijo</h3>
                    <button wire:click="cerrarModalPago" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="pagar" class="p-6 space-y-4">
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Monto a Pagar *</label>
                        <div class="flex gap-2">
                            <input type="number" step="0.01" wire:model="monto_pago" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm font-bold">
                            <span class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm font-semibold text-gray-600">
                                {{ strtoupper($moneda_pago) }}
                            </span>
                        </div>
                        @error('monto_pago') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- 👇 AQUÍ ESTÁ EL CAMPO DE TASA Y EL CHECKBOX 👇 --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-gray-700">Tasa de Cambio</label>
                            
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.live="es_tasa_preferencial_pago" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                <span class="ml-1.5 text-xs text-gray-600">Usar tasa preferencial</span>
                            </label>
                        </div>

                        <div class="relative">
                            <input type="number" step="0.0001" 
                                   wire:model="tasa_pago" 
                                   placeholder="{{ ($moneda_pago === 'bs' && !$es_tasa_preferencial_pago) ? 'No aplica' : '0.0000' }}" 
                                   @if($moneda_pago === 'bs' && !$es_tasa_preferencial_pago) disabled @endif
                                   class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600 disabled:bg-gray-100 disabled:text-gray-400">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-xs font-bold">Bs</span>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-500 mt-1">
                            @if($moneda_pago === 'bs' && !$es_tasa_preferencial_pago)
                                No se requiere tasa para montos en Bolívares.
                            @else
                                Tasa aplicada al momento del registro.
                            @endif
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Pagar desde la Cuenta *</label>
                        <select wire:model="cuenta_pago_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Seleccione cuenta...</option>
                            @foreach ($cuentas as $c)
                                <option value="{{ $c->id }}">{{ $c->nombre }} ({{ strtoupper($c->moneda) }})</option>
                            @endforeach
                        </select>
                        @error('cuenta_pago_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Fecha del Pago *</label>
                        <input type="date" wire:model="fecha_pago_transaccion" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        @error('fecha_pago_transaccion') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrarModalPago" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancelar</button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Confirmar Pago
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>