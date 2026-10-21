@php
    $tipo = $mapaDientes[$diente] ?? 'primer_molar';
    $dato = $datosOdontograma[$diente] ?? ['general' => null, 'superficies' => []];
    $estadoGeneral = $dato['general'] ?? null;
    $superficies = $dato['superficies'] ?? [];
    $esSuperior = $vista === 'superior';
@endphp

<div class="flex flex-col items-center {{ $espejo ? 'scale-x-[-1]' : '' }}" wire:key="diente-{{ $diente }}">
    {{-- Número del diente --}}
    <span class="text-[9px] font-bold text-gray-500 mb-0.5">{{ $diente }}</span>

    {{-- VISTA OCLUSAL (interactiva en 5 zonas) --}}
    <div class="relative bg-slate-50 rounded border border-slate-200"
         style="width: 46px; height: 46px;"
         x-data="{ hover: null }">
        {{-- Placeholder: cuando tengas imágenes, cambia esto por la <img> real --}}
        <div class="absolute inset-0 flex items-center justify-center text-[8px] text-slate-400 pointer-events-none">
            Oclusal
        </div>

        {{-- Zonas clickeables (5 superficies) --}}
        <button type="button"
                wire:click="aplicarSuperficie({{ $diente }}, 'vestibular')"
                title="Vestibular: {{ $superficies['vestibular'] ?? 'SANO' }}"
                class="absolute top-0 left-1/4 w-1/2 h-1/4 hover:opacity-70 transition rounded-t-sm"
                style="background: {{ $this->colorEstado($superficies['vestibular'] ?? 'SANO') }};">
        </button>
        <button type="button"
                wire:click="aplicarSuperficie({{ $diente }}, 'mesial')"
                title="Mesial: {{ $superficies['mesial'] ?? 'SANO' }}"
                class="absolute top-1/4 left-0 w-1/4 h-1/2 hover:opacity-70 transition"
                style="background: {{ $this->colorEstado($superficies['mesial'] ?? 'SANO') }};">
        </button>
        <button type="button"
                wire:click="aplicarSuperficie({{ $diente }}, 'distal')"
                title="Distal: {{ $superficies['distal'] ?? 'SANO' }}"
                class="absolute top-1/4 right-0 w-1/4 h-1/2 hover:opacity-70 transition"
                style="background: {{ $this->colorEstado($superficies['distal'] ?? 'SANO') }};">
        </button>
        <button type="button"
                wire:click="aplicarSuperficie({{ $diente }}, 'lingual')"
                title="Lingual: {{ $superficies['lingual'] ?? 'SANO' }}"
                class="absolute bottom-0 left-1/4 w-1/2 h-1/4 hover:opacity-70 transition rounded-b-sm"
                style="background: {{ $this->colorEstado($superficies['lingual'] ?? 'SANO') }};">
        </button>
        <button type="button"
                wire:click="aplicarSuperficie({{ $diente }}, 'oclusal')"
                title="Oclusal: {{ $superficies['oclusal'] ?? 'SANO' }}"
                class="absolute top-1/4 left-1/4 w-1/2 h-1/2 hover:opacity-70 transition"
                style="background: {{ $this->colorEstado($superficies['oclusal'] ?? 'SANO') }};">
        </button>
    </div>

    {{-- VISTAS LATERALES (vestibular arriba, lingual abajo) --}}
    <div class="mt-0.5 flex flex-col gap-0.5">
        {{-- Vestibular --}}
        <div class="relative bg-slate-50 rounded border border-slate-200"
             style="width: 46px; height: 30px;">
            <div class="absolute inset-0 flex items-center justify-center text-[7px] text-slate-400 pointer-events-none">
                Vest.
            </div>
            @if ($estadoGeneral)
                <div class="absolute inset-0 rounded"
                     style="background: {{ $this->colorEstado($estadoGeneral) }};"></div>
            @endif
        </div>

        {{-- Lingual --}}
        <div class="relative bg-slate-50 rounded border border-slate-200"
             style="width: 46px; height: 30px;">
            <div class="absolute inset-0 flex items-center justify-center text-[7px] text-slate-400 pointer-events-none">
                Ling.
            </div>
            @if ($estadoGeneral)
                <div class="absolute inset-0 rounded"
                     style="background: {{ $this->colorEstado($estadoGeneral) }};"></div>
            @endif
        </div>
    </div>

    {{-- Estado general (badge) --}}
    @if ($estadoGeneral)
        <span class="text-[8px] font-semibold mt-0.5 px-1 rounded"
              style="background: {{ $this->colorEstado($estadoGeneral) }}; color: white;">
            {{ substr($estadoGeneral, 0, 3) }}
        </span>
    @endif
</div>