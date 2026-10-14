<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Tasa;

new class extends Component
{
    #[On('tasa-guardada')]
    public function refrescar(): void {}

    #[On('cambio-guardado')]
    public function refrescarCambio(): void {}

    public function with(): array
    {
        return [
            'tasas' => Tasa::with(['cambios' => fn($q) => $q->orderByDesc('created_at')])
                ->orderByDesc('predeterminado')
                ->orderBy('nombre')
                ->get(),
        ];
    }

    public function eliminar(int $id): void
    {
        $tasa = Tasa::find($id);
        if (!$tasa) return;

        // No permitir eliminar si está siendo usada en citas
        if ($tasa->citas()->exists()) {
            $tasa->update(['activa' => false]);
            session()->flash('success', 'La tasa tiene citas asociadas. Se desactivó en lugar de eliminar.');
            return;
        }

        // Si es la predeterminada, no permitir eliminar sin asignar otra
        if ($tasa->predeterminado) {
            session()->flash('error', 'No puedes eliminar la tasa predeterminada. Marca otra como predeterminada primero.');
            return;
        }

        // Eliminar la tasa y sus cambios (cascade en BD)
        $tasa->delete();
        session()->flash('success', 'Tasa eliminada correctamente.');
    }

    public function toggleActiva(int $id): void
    {
        $tasa = Tasa::find($id);
        if (!$tasa) return;

        $tasa->update(['activa' => !$tasa->activa]);
        session()->flash('success', 'Estado de la tasa actualizado.');
    }

    public function marcarPredeterminada(int $id): void
    {
        $tasa = Tasa::find($id);
        if (!$tasa) return;

        // Desmarcar todas las demás
        Tasa::where('id', '!=', $id)->update(['predeterminado' => false]);

        // Marcar esta
        $tasa->update(['predeterminado' => true, 'activa' => true]);

        session()->flash('success', "Tasa '{$tasa->etiqueta}' marcada como predeterminada.");
    }
};
?>

<div class="space-y-6">
    @if (session('success'))
        <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @forelse ($tasas as $tasa)
        @php
            $cambioDolar = $tasa->cambioActualDe('dolar');
            $cambioEuro = $tasa->cambioActualDe('euro');
        @endphp

        <div wire:key="tasa-{{ $tasa->id }}"
             class="bg-white rounded-xl shadow-sm border overflow-hidden
                    {{ $tasa->predeterminado ? 'border-teal-300 ring-1 ring-teal-100' : 'border-gray-100' }}">

            {{-- Encabezado --}}
            <div class="flex items-center justify-between px-6 py-4 border-b
                        {{ $tasa->predeterminado ? 'bg-teal-50/50 border-teal-100' : 'bg-gray-50/50 border-gray-100' }}">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg
                                {{ $tasa->activa ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ strtoupper(substr($tasa->etiqueta, 0, 2)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-base font-bold text-gray-800">{{ $tasa->etiqueta }}</h3>
                            <code class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 font-mono">
                                {{ $tasa->nombre }}
                            </code>
                            @if ($tasa->predeterminado)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                                    Predeterminada
                                </span>
                            @endif
                            @if (!$tasa->activa)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    Inactiva
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $tasa->descripcion ?? 'Sin descripción' }}
                        </p>
                    </div>
                </div>

                {{-- Cotizaciones actuales --}}
                <div class="flex items-center gap-6">
                    @if ($cambioDolar)
                        <div class="text-right">
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-400">Dólar</p>
                            <p class="text-lg font-bold text-gray-800">
                                Bs {{ number_format($cambioDolar->monto, 2) }}
                            </p>
                            <p class="text-[10px] text-gray-400">
                                {{ $cambioDolar->created_at->diffForHumans() }}
                            </p>
                        </div>
                    @else
                        <div class="text-right">
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-400">Dólar</p>
                            <p class="text-xs text-gray-400 italic">Sin cotización</p>
                        </div>
                    @endif

                    @if ($cambioEuro)
                        <div class="text-right">
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-400">Euro</p>
                            <p class="text-lg font-bold text-gray-800">
                                Bs {{ number_format($cambioEuro->monto, 2) }}
                            </p>
                            <p class="text-[10px] text-gray-400">
                                {{ $cambioEuro->created_at->diffForHumans() }}
                            </p>
                        </div>
                    @else
                        <div class="text-right">
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-400">Euro</p>
                            <p class="text-xs text-gray-400 italic">Sin cotización</p>
                        </div>
                    @endif

                    {{-- Botones --}}
                    <div class="flex flex-col gap-1">
                        @if (!$tasa->predeterminado)
                            <button wire:click="marcarPredeterminada({{ $tasa->id }})"
                                    title="Marcar como predeterminada"
                                    class="p-1.5 text-slate-500 hover:bg-slate-100 rounded-md">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        @endif

                        <button wire:click="$dispatch('editar-tasa', { id: {{ $tasa->id }} })"
                                title="Editar tasa"
                                class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>

                        <button wire:click="$dispatch('nuevo-cambio', { tasaId: {{ $tasa->id }} })"
                                title="Registrar nuevo cambio"
                                class="p-1.5 text-teal-600 hover:bg-teal-50 rounded-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Historial --}}
            @if ($tasa->cambios->count() > 0)
                <div class="px-6 py-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                        Últimos cambios
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                        @foreach ($tasa->cambios->take(8) as $cambio)
                            <div class="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-md
                                        {{ $loop->first ? 'bg-teal-50 border border-teal-100' : 'bg-gray-50' }}">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[9px] uppercase font-bold tracking-wider
                                                {{ $cambio->moneda === 'dolar' ? 'text-blue-600' : 'text-purple-600' }}">
                                        {{ $cambio->moneda_etiqueta }}
                                    </span>
                                    <span class="font-semibold text-gray-800">
                                        {{ number_format($cambio->monto, 2) }}
                                    </span>
                                </div>
                                <span class="text-[10px] text-gray-400">
                                    {{ $cambio->created_at->format('d/m') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    @if ($tasa->cambios->count() > 8)
                        <p class="text-xs text-gray-400 mt-2">
                            ... y {{ $tasa->cambios->count() - 8 }} cambios más
                        </p>
                    @endif
                </div>
            @else
                <div class="px-6 py-6 text-center text-sm text-gray-400 italic">
                    Sin cotizaciones registradas. Usa el botón <span class="font-semibold text-teal-600">+</span> para agregar la primera.
                </div>
            @endif

            {{-- Footer con acciones secundarias --}}
            <div class="px-6 py-3 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <button wire:click="toggleActiva({{ $tasa->id }})"
                        class="text-xs font-medium {{ $tasa->activa ? 'text-gray-500 hover:text-red-600' : 'text-teal-600 hover:text-teal-700' }}">
                    {{ $tasa->activa ? 'Desactivar tasa' : 'Activar tasa' }}
                </button>

                @if (!$tasa->predeterminado)
                    <button wire:click="eliminar({{ $tasa->id }})"
                            wire:confirm="¿Eliminar la tasa {{ $tasa->etiqueta }}? Se eliminarán todos sus cambios."
                            class="text-xs font-medium text-red-600 hover:text-red-700">
                        Eliminar tasa
                    </button>
                @endif
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center text-gray-500">
            No hay tasas registradas.
        </div>
    @endforelse
</div>