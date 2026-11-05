<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AgendaCompartida;
use App\Models\Medico;
use Livewire\Attributes\On;

new class extends Component
{
    use WithPagination;

    // Propiedades para el modal de compartir
    public bool $modalCompartirAbierto = false;
    public ?string $enlaceCompartir = null;
    public ?string $qrBase64Compartir = null;
    public ?string $nombreMedicoCompartir = null;

    public $logo_clinica = null;
    public $empresa = [];

    #[On('agenda-actualizada')]
    public function refrescar(): void {}

    public function mount() {
    $empresaDb = \App\Models\Empresa::all();
    foreach ($empresaDb as $e) {
        $this->empresa[$e->clave] = $e->valor;
    }
    $this->logo_clinica = !empty($this->empresa['logo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->empresa['logo'])
        ? \Illuminate\Support\Facades\Storage::url($this->empresa['logo'])
        : asset('images/logo/logo2.png');
    }


    public function compartirAgenda(int $agendaId): void
    {
        $agenda = AgendaCompartida::with('medico')->find($agendaId);
        
        if (!$agenda) return;

        $this->enlaceCompartir = route('public.agenda', ['token' => $agenda->token]);
        $this->nombreMedicoCompartir = $agenda->medico?->nombre . ' ' . $agenda->medico?->apellido;
        
        // Generar QR en Base64
        $this->qrBase64Compartir = base64_encode(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(300)
                ->generate($this->enlaceCompartir)
        );

        $this->modalCompartirAbierto = true;
    }

    public function cerrarCompartir(): void
    {
        $this->modalCompartirAbierto = false;
        $this->reset(['enlaceCompartir', 'qrBase64Compartir', 'nombreMedicoCompartir']);
    }

    public function desactivar(int $agendaId): void
    {
        $agenda = AgendaCompartida::find($agendaId);
        
        if ($agenda) {
            $agenda->update(['activo' => false]);
            session()->flash('success', 'El enlace ha sido desactivado y ya no funcionará.');
        }
    }

    public function activar(int $agendaId): void
    {
        $agenda = AgendaCompartida::find($agendaId);
        
        if ($agenda) {
            // Si la fecha de expiración ya pasó, no permitimos reactivar sin cambiar la fecha
            if ($agenda->fecha_expiracion->isPast()) {
                session()->flash('error', 'No se puede reactivar porque ya expiró. Edita la fecha primero.');
                return;
            }

            $agenda->update(['activo' => true]);
            session()->flash('success', 'El enlace ha sido reactivado.');
        }
    }

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
                                    <button wire:click="compartirAgenda({{ $agenda->id }})"
                                            class="px-3 py-1.5 bg-siapc-blue text-white rounded text-xs font-semibold hover:bg-siapc-indigo transition inline-flex items-center mr-1">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                                        Compartir
                                    </button>
                                    <button wire:click="$dispatch('editar-agenda', { agendaId: {{ $agenda->id }} })"
                                            title="Editar enlace"
                                            class="px-2 py-1.5 text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition inline-flex items-center mr-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <a href="{{ $enlace }}" target="_blank" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded text-xs font-semibold hover:bg-slate-200 transition inline-flex items-center mr-1">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Ver
                                    </a>
                                    
                                    {{-- 👇 NUEVO BOTÓN DE DESACTIVAR 👇 --}}
                                    <button wire:click="desactivar({{ $agenda->id }})"
                                            wire:confirm="¿Estás seguro de desactivar este enlace? Los pacientes ya no podrán agendar citas con él."
                                            title="Desactivar enlace"
                                            class="px-2 py-1.5 text-rose-600 bg-rose-50 rounded hover:bg-rose-100 transition inline-flex items-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>

                                @elseif($estaExpirado)
                                    <span class="text-xs text-amber-600 font-medium italic mr-2">Expirado</span>
                                    <button wire:click="$dispatch('editar-agenda', { agendaId: {{ $agenda->id }} })"
                                            title="Extender fecha"
                                            class="px-2 py-1.5 text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition inline-flex items-center">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                @else
                                    {{-- Si está inactiva (desactivada manualmente) --}}
                                    <span class="text-xs text-gray-400 italic mr-2">Desactivada</span>
                                    <button wire:click="activar({{ $agenda->id }})"
                                            wire:confirm="¿Reactivar este enlace para que los pacientes puedan agendar?"
                                            title="Reactivar enlace"
                                            class="px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded text-xs font-semibold hover:bg-emerald-200 transition inline-flex items-center">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Reactivar
                                    </button>
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
    {{-- MODAL DE COMPARTIR --}}
    @if($modalCompartirAbierto)
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrarCompartir"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:click.self="cerrarCompartir">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col">
                
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Compartir Enlace</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Descarga la imagen o copia el enlace.</p>
                    </div>
                    <button type="button" wire:click="cerrarCompartir" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    {{-- Enlace de texto --}}
                    <div class="flex items-center bg-slate-50 border border-slate-200 rounded-lg p-2 mb-6">
                        <input type="text" value="{{ $enlaceCompartir }}" readonly class="flex-1 bg-transparent border-none text-sm text-slate-700 focus:ring-0 px-2">
                        <button onclick="navigator.clipboard.writeText('{{ $enlaceCompartir }}'); alert('Enlace copiado');" type="button" class="px-4 py-2 bg-siapc-blue text-white rounded-md text-xs font-bold hover:bg-siapc-indigo transition">
                            Copiar
                        </button>
                    </div>

                    {{-- Tarjeta Visual --}}
                    <div class="flex flex-col items-center justify-center">
                        <div id="card-compartir-{{ $this->getId() }}" class="w-full max-w-[320px] aspect-[9/16] bg-gradient-to-b from-white to-slate-50 p-6 flex flex-col items-center justify-between rounded-2xl border border-slate-200 shadow-xl relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-full h-32 bg-siapc-blue/5 rounded-b-full"></div>

                            <div class="relative z-10 mt-4 flex flex-col items-center">
                                <img src="{{ $logo_clinica ?? asset('images/logo/logo2.png') }}" alt="Logo Clínica" class="h-16 object-contain mb-2">
                                <p class="text-xs font-bold text-siapc-blue uppercase tracking-widest">{{ $this->empresa['nombre_clinica'] ?? 'SIAPC' }}</p>
                            </div>

                            <div class="relative z-10 flex flex-col items-center text-center w-full">
                                <h4 class="text-2xl font-extrabold text-slate-900 leading-tight mb-2">Agenda tu cita</h4>
                                <p class="text-sm text-slate-500 mb-6">Escanea el código para elegir tu horario.</p>
                                
                                <div class="w-48 h-48 bg-white rounded-2xl p-3 shadow-md border border-slate-100 mb-4">
                                    <img src="data:image/svg+xml;base64,{{ $qrBase64Compartir }}" alt="QR Code" class="w-full h-full object-contain">
                                </div>

                                <p class="text-xs font-bold text-slate-700 mb-1">Dr(a). {{ $nombreMedicoCompartir }}</p>
                                <p class="text-[10px] text-slate-400">Odontología de vanguardia</p>
                            </div>

                            <div class="relative z-10 w-full text-center">
                                <p class="text-[10px] text-slate-400 break-all px-2">{{ $enlaceCompartir }}</p>
                            </div>
                        </div>

                        <button type="button" onclick="descargarImagen('card-compartir-{{ $this->getId() }}', 'agenda-{{ Str::slug($nombreMedicoCompartir) }}')" class="mt-6 px-6 py-3 bg-siapc-blue hover:bg-siapc-indigo text-white text-sm font-bold rounded-xl shadow-lg transition flex items-center gap-2 w-full max-w-[320px] justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Descargar Imagen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <livewire:agenda-editar-form />
</div>
@push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/html-to-image@1.11.11/dist/html-to-image.min.js"></script>
        <script>
            // Si la función no está definida (porque este componente se carga antes que el otro), la definimos aquí
            if (typeof descargarImagen === 'undefined') {
                function descargarImagen(elementoId, nombreArchivo) {
                    const nodo = document.getElementById(elementoId);
                    if (!nodo) return;

                    const boton = event.target;
                    const textoOriginal = boton.innerHTML;
                    boton.innerHTML = 'Generando...';
                    boton.disabled = true;

                    htmlToImage.toPng(nodo, { quality: 0.95, pixelRatio: 2 })
                        .then(function (dataUrl) {
                            const link = document.createElement('a');
                            link.download = `SIAPC-${nombreArchivo}.png`;
                            link.href = dataUrl;
                            link.click();
                            
                            boton.innerHTML = '¡Descargado!';
                            setTimeout(() => {
                                boton.innerHTML = textoOriginal;
                                boton.disabled = false;
                            }, 2000);
                        })
                        .catch(function (error) {
                            console.error('Error al generar la imagen:', error);
                            alert('Hubo un error al descargar la imagen. Intenta de nuevo.');
                            boton.innerHTML = textoOriginal;
                            boton.disabled = false;
                        });
                }
            }
        </script>
@endpush