<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\Cita;
use App\Models\Medico;

new class extends Component
{
    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $medicoId = '';

    public bool $cajaVencida = false;
    public bool $cajaDisponible = false;

    public function mount(bool $cajaVencida = false, bool $cajaDisponible = false): void
    {
        $this->cajaVencida = $cajaVencida;
        $this->cajaDisponible = $cajaDisponible;
    }

    public function updatingSearch(): void {}

    #[On('pago-registrado')]
    #[On('pago-anulado')]
    #[On('pago-actualizado')]
    #[On('cobrar-cita')]
    public function refrescar(): void {}

    public function with(): array
    {
        $user = auth()->user();
        $esMedicoPuro = $user->esMedico() && !$user->puedeVerTodo();

        $query = Cita::with(['paciente', 'medico.especialidades'])
            ->whereIn('estado', ['CONFIRMADA', 'COMPLETADA'])
            ->whereIn('estado_pago', ['PENDIENTE', 'PARCIAL'])
            ->orderBy('fecha', 'asc');

        if ($esMedicoPuro) {
            $query->where('medico_id', $user->medico->id);
        }

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                // 1. Buscar por número de cita (ej: CIT-0001)
                $q->where('numero_cita', 'like', "%{$term}%")
                  // 2. O buscar por datos del paciente
                  ->orWhereHas('paciente', function ($pac) use ($term) {
                      $pac->where('nombre', 'like', "%{$term}%")
                          ->orWhere('apellido', 'like', "%{$term}%")
                          ->orWhere('ci', 'like', "%{$term}%")
                          ->orWhereRaw("CONCAT(nombre, ' ', apellido) LIKE ?", ["%{$term}%"]);
                  });
            });
        }

        if ($this->medicoId !== '') {
            $query->where('medico_id', $this->medicoId);
        }

        $citas = $query->limit(50)->get();

        return [
            'citasPorCobrar' => $citas,
            'totalPendienteUsd' => $citas->sum(fn($c) => $c->saldo_pendiente_usd),
            'medicos' => Medico::where('estado', 'ACTIVO')->orderBy('apellido')->get(),
            'esMedicoPuro' => $esMedicoPuro,
        ];
    }
};
?>

<div>
    {{-- ============================================================ --}}
    {{-- HEADER --}}
    {{-- ============================================================ --}}
    <div class="bg-white rounded-t-2xl border border-gray-100 border-b-0 px-6 py-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-gray-800">Citas Pendientes por Cobrar</h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    Pacientes con citas confirmadas o completadas con saldo pendiente
                </p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">
                    Total Pendiente
                </p>
                <p class="text-lg font-bold text-amber-600">
                    ${{ number_format($totalPendienteUsd, 2) }}
                </p>
            </div>
        </div>

        {{-- Buscador --}}
        <div class="flex flex-col sm:flex-row gap-3 mt-4">
            <div class="relative flex-1">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Buscar por cita, cédula, nombre o apellido..."
                       class="w-full pl-9 pr-9 py-2 border border-gray-300 rounded-lg text-sm">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                @if ($search !== '')
                    <button wire:click="$set('search', '')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                @endif
            </div>

            @if (!$esMedicoPuro)
                <select wire:model.live="medicoId"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm sm:w-64">
                    <option value="">Todos los médicos</option>
                    @foreach ($medicos as $m)
                        <option value="{{ $m->id }}">{{ $m->nombre }} {{ $m->apellido }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        {{-- Loading condicional: solo si hay texto o filtro y está cargando --}}
        @if ($search !== '' || $medicoId !== '')
            <div wire:loading.delay.200ms wire:target="search, medicoId" class="text-xs text-teal-600 font-medium mt-2">
                Buscando...
            </div>
        @endif
    </div>

    {{-- ============================================================ --}}
    {{-- TABLA --}}
    {{-- ============================================================ --}}
    <div class="bg-white rounded-b-2xl border border-gray-100 overflow-hidden">
        @if ($citasPorCobrar->isEmpty())
            <div class="py-12 text-center text-sm text-gray-400">
                @if ($search !== '' || $medicoId !== '')
                    No se encontraron citas con los filtros actuales.
                @else
                    No hay citas pendientes por cobrar.
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                            <th class="px-4 py-3">Nro. Cita</th>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Paciente</th>
                            <th class="px-4 py-3">Médico</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Pagado</th>
                            <th class="px-4 py-3 text-right">Saldo</th>
                            <th class="px-4 py-3 text-center">Estado</th>
                            <th class="px-4 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @foreach ($citasPorCobrar as $cita)
                            <tr wire:key="cita-{{ $cita->id }}" class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 font-mono text-xs font-semibold text-teal-700 whitespace-nowrap">
                                    {{ $cita->numero_cita ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-800">
                                        {{ $cita->fecha->format('d/m/Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $cita->fecha->format('h:i A') }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800">
                                        {{ $cita->paciente?->nombre }} {{ $cita->paciente?->apellido }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $cita->paciente?->lci }}-{{ $cita->paciente?->ci }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    Dr(a). {{ $cita->medico?->nombre }} {{ $cita->medico?->apellido }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                    ${{ number_format((float) $cita->total, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">
                                    ${{ number_format($cita->total_pagado_usd, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-amber-600">
                                    ${{ number_format($cita->saldo_pendiente_usd, 2) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($cita->estado_pago === 'PARCIAL')
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Parcial
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            Pendiente
                                        </span>
                                    @endif
                                </td>
                               <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if ($cajaVencida)
                                        <button type="button"
                                                onclick="alert('Debes cerrar la caja del día anterior antes de registrar pagos.')"
                                                class="px-3 py-1.5 bg-gray-300 text-gray-500 rounded-md text-xs font-medium cursor-not-allowed inline-flex items-center"
                                                title="Cierra la caja del día anterior para poder cobrar">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            Bloqueado
                                        </button>
                                    @elseif (!$cajaDisponible)
                                        <button type="button"
                                                onclick="Livewire.dispatch('abrir-caja')"
                                                class="px-3 py-1.5 bg-amber-500 text-white rounded-md text-xs font-medium hover:bg-amber-600 inline-flex items-center">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                            </svg>
                                            Abrir Caja
                                        </button>
                                    @else
                                        <button type="button"
                                                onclick="Livewire.dispatch('cobrar-cita', { citaId: {{ $cita->id }} })"
                                                class="px-3 py-1.5 bg-teal-600 text-white rounded-md text-xs font-medium hover:bg-teal-700 inline-flex items-center">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            Cobrar
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>