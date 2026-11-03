<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AgendaCompartida;
use App\Models\Medico;
use Livewire\Attributes\On;

new class extends Component
{
    use WithPagination;

    #[On('agenda-actualizada')]
    public function refrescar(): void {}

    public function with(): array
    {
        $user = auth()->user();
        $query = AgendaCompartida::with(['medico', 'creador'])->orderByDesc('created_at');

        // Si es médico y no puede ver todo, solo ve sus agendas
        if ($user->esMedico() && !$user->puedeVerTodo()) {
            $query->where('medico_id', $user->medico->id);
        }

        $agendas = $query->paginate(10);

        return [
            'agendas' => $agendas,
        ];
    }
};
?>

<div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                        <th class="px-6 py-4">Enlace</th>
                        <th class="px-6 py-4">Médico</th>
                        <th class="px-6 py-4">Rango de Fechas</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($agendas as $agenda)
                        @php
                            $enlace = route('public.agenda', ['token' => $agenda->token]);
                            $estaActivo = $agenda->activo;
                            $estaExpirado = $agenda->fecha_expiracion->isPast();
                            
                            // Solo puedes copiar si está activo y no expiró
                            $sePuedeUsar = $estaActivo && !$estaExpirado;
                        @endphp
                        <tr wire:key="agenda-{{ $agenda->id }}" class="hover:bg-gray-50 transition">
                            
                            {{-- Enlace --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <code class="text-[10px] bg-slate-100 text-slate-600 px-2 py-1 rounded">
                                        /agendar/{{ \Str::limit($agenda->token, 10) }}...
                                    </code>
                                </div>
                            </td>

                            {{-- Médico --}}
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-800">Dr(a). {{ $agenda->medico?->apellido }}, {{ $agenda->medico?->nombre }}</div>
                                <div class="text-xs text-gray-500">Creado por {{ $agenda->creador?->nombre_mostrar ?? 'N/A' }}</div>
                            </td>

                            {{-- Fechas --}}
                            <td class="px-6 py-4 text-xs text-gray-600">
                                Desde: {{ $agenda->created_at->format('d/m/Y') }}<br>
                                Expira: <span class="font-semibold">{{ $agenda->fecha_expiracion->format('d/m/Y') }}</span>
                            </td>

                            {{-- Estado --}}
                            <td class="px-6 py-4">
                                @if(!$estaActivo)
                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 uppercase">Desactivado</span>
                                @elseif($estaExpirado)
                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 uppercase">Expirado</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 uppercase">Activo</span>
                                @endif
                            </td>

                            {{-- Acciones --}}
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                @if($sePuedeUsar)
                                    <button onclick="navigator.clipboard.writeText('{{ $enlace }}'); alert('Enlace copiado al portapapeles');" 
                                            class="px-3 py-1.5 bg-siapc-blue text-white rounded text-xs font-semibold hover:bg-siapc-indigo transition inline-flex items-center mr-1">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                        Copiar
                                    </button>
                                    <button wire:click="$dispatch('editar-agenda', { agendaId: {{ $agenda->id }} })"
                                            title="Editar enlace"
                                            class="px-2 py-1.5 text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition inline-flex items-center mr-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <a href="{{ $enlace }}" target="_blank" 
                                       class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded text-xs font-semibold hover:bg-slate-200 transition inline-flex items-center">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Ver
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400 italic">No disponible</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                No has creado ningún enlace de agenda todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($agendas->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">{{ $agendas->links() }}</div>
        @endif
    </div>

    <livewire:agenda-editar-form />
</div>