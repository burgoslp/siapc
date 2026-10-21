@php
    $dato = $datosOdontograma[$diente] ?? ['general' => null, 'superficies' => []];
    $generalData = $dato['general'] ?? null;
    $general = is_array($generalData) ? ($generalData['estado'] ?? null) : $generalData;
    $superficiesData = $dato['superficies'] ?? [];
    $superficies = [];
    foreach ($superficiesData as $sup => $val) {
        $superficies[$sup] = is_array($val) ? ($val['estado'] ?? 'SANO') : $val;
    }
    
    $imgOclusal = asset($this->getRutaImagen($diente, 'oclusal'));
    $imgVestibular = asset($this->getRutaImagen($diente, 'vestibular'));
    $imgLingual = asset($this->getRutaImagen($diente, 'lingual'));
    
    $esFrontal = in_array($diente, [13, 12, 11, 21, 22, 23, 33, 32, 31, 41, 42, 43]);
    $mostrarOclusal = !$esFrontal;

    // Variables de control clínico
    $esExtraido = $general === 'EXTRAIDO';
    $tieneCorona = $general === 'CORONA';
    $tieneEndodoncia = $general === 'ENDODONCIA' || $general === 'IMPLANTE';
@endphp

<div class="flex flex-col items-center" wire:key="diente-{{ $diente }}">
    <span class="text-[10px] font-bold text-gray-500 mb-0.5">{{ $diente }}</span>

    {{-- VISTA 1: OCLUSAL --}}
    @if($mostrarOclusal)
        <div class="relative bg-slate-50 rounded border border-slate-200 overflow-hidden" style="width: 45px; height: 45px;">
            <img src="{{ $imgOclusal }}" 
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                 class="absolute inset-0 w-full h-full object-contain pointer-events-none {{ $espejo ? 'scale-x-[-1]' : '' }}" 
                 alt="Oclusal {{ $diente }}">
            <div class="absolute inset-0 hidden items-center justify-center text-[8px] text-slate-400 pointer-events-none">Ocl.</div>

            @if($esExtraido || $tieneCorona)
                {{-- Pintar todo de gris (Extraído) o morado (Corona) y bloquear --}}
                <div class="absolute inset-0 cursor-not-allowed mix-blend-multiply" style="background: {{ $this->colorEstado($general) }};"></div>
            @else
                {{-- Superficies clickeables normales --}}
                <button type="button" wire:click="aplicarSuperficie({{ $diente }}, 'vestibular')" class="absolute top-0 left-1/4 w-1/2 h-1/4 hover:opacity-70 transition mix-blend-multiply" style="background: {{ $this->colorEstado($superficies['vestibular'] ?? 'SANO') }};"></button>
                <button type="button" wire:click="aplicarSuperficie({{ $diente }}, 'mesial')" class="absolute top-1/4 left-0 w-1/4 h-1/2 hover:opacity-70 transition mix-blend-multiply" style="background: {{ $this->colorEstado($superficies['mesial'] ?? 'SANO') }};"></button>
                <button type="button" wire:click="aplicarSuperficie({{ $diente }}, 'distal')" class="absolute top-1/4 right-0 w-1/4 h-1/2 hover:opacity-70 transition mix-blend-multiply" style="background: {{ $this->colorEstado($superficies['distal'] ?? 'SANO') }};"></button>
                <button type="button" wire:click="aplicarSuperficie({{ $diente }}, 'lingual')" class="absolute bottom-0 left-1/4 w-1/2 h-1/4 hover:opacity-70 transition mix-blend-multiply" style="background: {{ $this->colorEstado($superficies['lingual'] ?? 'SANO') }};"></button>
                <button type="button" wire:click="aplicarSuperficie({{ $diente }}, 'oclusal')" class="absolute top-1/4 left-1/4 w-1/2 h-1/2 hover:opacity-70 transition mix-blend-multiply" style="background: {{ $this->colorEstado($superficies['oclusal'] ?? 'SANO') }};"></button>
            @endif
        </div>
    @endif

    {{-- VISTAS LATERALES --}}
    <div class="flex flex-col gap-1 w-full {{ $mostrarOclusal ? 'mt-1' : '' }}">
        
        {{-- VESTIBULAR --}}
        <div class="relative bg-slate-50 rounded border border-slate-200 overflow-hidden" style="width: 45px; height: {{ $esFrontal ? '55px' : '35px' }};">
            <img src="{{ $imgVestibular }}" 
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                 class="absolute inset-0 w-full h-full object-contain pointer-events-none {{ $espejo ? 'scale-x-[-1]' : '' }}" 
                 alt="Vestibular {{ $diente }}">
            <div class="absolute inset-0 hidden items-center justify-center text-[7px] text-slate-400 pointer-events-none">Vest.</div>
            
            {{-- Si es frontal, permitimos click en la superficie vestibular si no está extraído ni tiene corona --}}
            @if($esFrontal && !$esExtraido && !$tieneCorona)
                <button type="button" wire:click="aplicarSuperficie({{ $diente }}, 'vestibular')" class="absolute inset-0 hover:opacity-70 transition mix-blend-multiply" style="background: {{ $this->colorEstado($superficies['vestibular'] ?? 'SANO') }};"></button>
            @endif

            {{-- Estados Generales --}}
            @if($esExtraido || $tieneCorona)
                <div class="absolute inset-0 cursor-not-allowed mix-blend-multiply" wire:click="aplicarDienteCompleto({{ $diente }})" style="background: {{ $this->colorEstado($general) }};"></div>
            @elseif($tieneEndodoncia)
                {{-- Endodoncia pinta la raíz (abajo) --}}
                <div class="absolute bottom-0 w-full h-1/2 hover:opacity-70 transition cursor-pointer mix-blend-multiply" wire:click="aplicarDienteCompleto({{ $diente }})" style="background: {{ $this->colorEstado($general) }};"></div>
            @endif
        </div>

        {{-- LINGUAL --}}
        <div class="relative bg-slate-50 rounded border border-slate-200 overflow-hidden" style="width: 45px; height: {{ $esFrontal ? '55px' : '35px' }};">
            <img src="{{ $imgLingual }}" 
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                 class="absolute inset-0 w-full h-full object-contain pointer-events-none {{ $espejo ? 'scale-x-[-1]' : '' }}" 
                 alt="Lingual {{ $diente }}">
            <div class="absolute inset-0 hidden items-center justify-center text-[7px] text-slate-400 pointer-events-none">Ling.</div>

            {{-- Si es frontal, permitimos click en la superficie lingual si no está extraído ni tiene corona --}}
            @if($esFrontal && !$esExtraido && !$tieneCorona)
                <button type="button" wire:click="aplicarSuperficie({{ $diente }}, 'lingual')" class="absolute inset-0 hover:opacity-70 transition mix-blend-multiply" style="background: {{ $this->colorEstado($superficies['lingual'] ?? 'SANO') }};"></button>
            @endif
            
            {{-- Estados Generales --}}
            @if($esExtraido || $tieneCorona)
                <div class="absolute inset-0 cursor-not-allowed mix-blend-multiply" wire:click="aplicarDienteCompleto({{ $diente }})" style="background: {{ $this->colorEstado($general) }};"></div>
            @elseif($tieneEndodoncia)
                {{-- Endodoncia pinta la raíz (abajo) --}}
                <div class="absolute bottom-0 w-full h-1/2 hover:opacity-70 transition cursor-pointer mix-blend-multiply" wire:click="aplicarDienteCompleto({{ $diente }})" style="background: {{ $this->colorEstado($general) }};"></div>
            @endif
        </div>
    </div>
</div>