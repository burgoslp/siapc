<?php

use Livewire\Component;
use App\Models\Tarifa;

new class extends Component
{
    public ?int $value = null;
    public ?int $especialidadId = null;

    public function mount(?int $value = null, ?int $especialidadId = null): void
    {
        $this->value = $value;
        $this->especialidadId = $especialidadId;
    }

    public function with(): array
    {
        // Filtramos: Tarifas de esta especialidad + Tarifas genéricas (sin especialidad)
        $query = Tarifa::with('especialidad')
            ->where('activo', true)
            ->where(function ($q) {
                $q->whereNull('especialidad_id');
                if ($this->especialidadId) {
                    $q->orWhere('especialidad_id', $this->especialidadId);
                }
            });

        return [
            'tarifas' => $query->orderBy('nombre')->get()
        ];
    }
};
?>

<div 
    x-data="{
        abierto: false,
        busqueda: '',
        tarifaId: @entangle('value'),
        tarifas: @js($tarifas->map(fn($t) => [
            'id' => $t->id, 
            'texto' => $t->nombre . ' — $' . number_format($t->precio_usd, 2),
            'especialidad' => $t->especialidad?->nombre ?? 'Todas',
            'tipo' => $t->tipo_etiqueta
        ])),
        get filtrados() {
            if (this.busqueda === '') return this.tarifas;
            return this.tarifas.filter(t => 
                t.texto.toLowerCase().includes(this.busqueda.toLowerCase()) || 
                t.especialidad.toLowerCase().includes(this.busqueda.toLowerCase())
            );
        },
        get textoSeleccionado() {
            if (!this.tarifaId) return 'Seleccione una tarifa...';
            const t = this.tarifas.find(x => x.id == this.tarifaId);
            return t ? t.texto : 'Seleccione una tarifa...';
        },
        seleccionar(id) {
            this.tarifaId = id;
            this.abierto = false;
            this.busqueda = '';
            $wire.dispatch('tarifa-seleccionada', { id: id });
        },
        limpiar() {
            this.tarifaId = null;
            this.abierto = false;
            this.busqueda = '';
            $wire.dispatch('tarifa-seleccionada', { id: null });
        }
    }"
    @click.away="abierto = false"
    class="relative"
>
    {{-- Select oculto --}}
    <select x-model="tarifaId" class="hidden">
        <option value="">Seleccione una tarifa...</option>
        <template x-for="t in tarifas" :key="t.id">
            <option :value="t.id" x-text="t.texto"></option>
        </template>
    </select>

    {{-- Botón que simula el select --}}
    <button type="button" 
            @click="abierto = !abierto; if(abierto) { $nextTick(() => $refs.searchInput.focus()) }" 
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-left flex items-center justify-between bg-white hover:border-gray-400 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition">
        <span x-text="textoSeleccionado" :class="tarifaId ? 'text-gray-800' : 'text-gray-500'"></span>
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    {{-- Botón de limpiar --}}
    <button type="button" x-show="tarifaId" @click="limpiar" class="absolute right-8 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>

    {{-- Panel flotante --}}
    <div x-show="abierto" 
         x-cloak 
         x-transition
         class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-xl overflow-hidden">
        
        {{-- Buscador --}}
        <div class="p-2 border-b border-gray-100 bg-gray-50">
            <input type="text" 
                   x-model="busqueda" 
                   x-ref="searchInput"
                   placeholder="Buscar tarifa..." 
                   class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-teal-500">
        </div>

        {{-- Lista de resultados con scroll (altura fija de ~3 elementos) --}}
        <div class="max-h-48 overflow-y-auto">
            <template x-for="t in filtrados" :key="t.id">
                <button type="button" 
                        @click="seleccionar(t.id)"
                        class="w-full text-left px-3 py-2 hover:bg-teal-50 border-b border-gray-50 last:border-0 transition">
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-medium text-gray-800" x-text="t.texto"></span>
                    </div>
                    <div class="text-[10px] text-gray-500 mt-0.5" x-text="t.especialidad + ' · ' + t.tipo"></div>
                </button>
            </template>
            
            <template x-if="filtrados.length === 0">
                <div class="px-3 py-3 text-xs text-gray-500 text-center">No se encontraron tarifas.</div>
            </template>
        </div>
    </div>
</div>