<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Empresa;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public $empresa = []; // Array para guardar los valores clave => valor
    public $logoFile; // Para subir el logo
    public $logoActual;

    public function mount(): void
    {
        // Cargamos todas las configuraciones en un solo array
        $registros = Empresa::all();
        foreach ($registros as $reg) {
            $this->empresa[$reg->clave] = $reg->valor;
        }

        $this->logoActual = $this->empresa['logo'] ?? null;
    }

    public function guardar(): void
    {
        $this->validate([
            'empresa.nombre_clinica' => 'required|string|max:255',
            'empresa.rif' => 'nullable|string|max:50',
            'empresa.telefono_1' => 'nullable|string|max:50',
            'empresa.telefono_2' => 'nullable|string|max:50',
            'empresa.whatsapp' => 'nullable|string|max:50',
            'empresa.email' => 'nullable|email|max:255',
            'logoFile' => 'nullable|image|max:2048', // máx 2MB
        ]);

        // Procesar el logo si se subió uno nuevo
        if ($this->logoFile) {
            // Borrar el logo anterior si existe
            if ($this->logoActual && Storage::disk('public')->exists($this->logoActual)) {
                Storage::disk('public')->delete($this->logoActual);
            }

            $ruta = $this->logoFile->store('empresa', 'public');
            $this->empresa['logo'] = $ruta;
            $this->logoActual = $ruta;
            $this->logoFile = null;
        }

        // Guardar todos los valores en la base de datos
        foreach ($this->empresa as $clave => $valor) {
            // Asignamos un grupo según la clave
            $grupo = 'general';
            if (in_array($clave, ['nombre_clinica', 'rif', 'direccion', 'logo'])) $grupo = 'identidad';
            if (in_array($clave, ['telefono_1', 'telefono_2', 'whatsapp', 'email', 'sitio_web'])) $grupo = 'contacto';
            if (in_array($clave, ['instagram', 'facebook', 'twitter'])) $grupo = 'redes';

            Empresa::updateOrCreate(
                ['clave' => $clave],
                ['valor' => $valor, 'grupo' => $grupo]
            );
        }

        session()->flash('success', 'Datos de la empresa actualizados correctamente.');
    }
};
?>

<div class="max-w-4xl mx-auto space-y-6">
    
    @if (session('success'))
        <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="guardar" class="space-y-6">
        
        {{-- IDENTIDAD --}}
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center">
                <svg class="w-4 h-4 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Identidad de la Clínica
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Nombre de la Clínica *</label>
                    <input type="text" wire:model="empresa.nombre_clinica" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500">
                    @error('empresa.nombre_clinica') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">RIF</label>
                    <input type="text" wire:model="empresa.rif" placeholder="J-12345678-9" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Dirección</label>
                    <textarea wire:model="empresa.direccion" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 resize-none"></textarea>
                </div>

                {{-- Logo --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 mb-2">Logo de la Clínica</label>
                    <div class="flex items-center gap-4">
                        @if ($logoFile)
                            <img src="{{ $logoFile->temporaryUrl() }}" class="h-16 w-16 object-contain bg-gray-50 rounded border border-gray-200 p-1">
                        @elseif ($logoActual)
                            <img src="{{ Storage::url($logoActual) }}" class="h-16 w-16 object-contain bg-gray-50 rounded border border-gray-200 p-1">
                        @else
                            <div class="h-16 w-16 flex items-center justify-center bg-gray-50 rounded border border-gray-200 text-gray-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        @endif
                        <div class="flex-1">
                            <input type="file" wire:model="logoFile" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 transition">
                            <div wire:loading wire:target="logoFile" class="text-xs text-teal-600 mt-1">Subiendo imagen...</div>
                        </div>
                    </div>
                    @error('logoFile') <span class="text-xs text-red-500 block mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- CONTACTO --}}
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center">
                <svg class="w-4 h-4 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                Datos de Contacto
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Teléfono Fijo / Principal</label>
                    <input type="text" wire:model="empresa.telefono_1" placeholder="0212-1234567" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Teléfono Alternativo</label>
                    <input type="text" wire:model="empresa.telefono_2" placeholder="0212-7654321" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1 text-green-700 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        WhatsApp de la Clínica
                    </label>
                    <input type="text" wire:model="empresa.whatsapp" placeholder="0414-1234567" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 border-green-200">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Correo Electrónico</label>
                    <input type="email" wire:model="empresa.email" placeholder="contacto@clinica.com" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500">
                    @error('empresa.email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Sitio Web</label>
                    <input type="text" wire:model="empresa.sitio_web" placeholder="www.clinica.com" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500">
                </div>
            </div>
        </div>

        {{-- REDES SOCIALES --}}
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center">
                <svg class="w-4 h-4 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                Redes Sociales
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Instagram</label>
                    <input type="text" wire:model="empresa.instagram" placeholder="@tuclinica" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-pink-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Facebook</label>
                    <input type="text" wire:model="empresa.facebook" placeholder="/tuclinica" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" 
                    wire:loading.attr="disabled"
                    class="px-6 py-2.5 bg-teal-600 text-white rounded-lg text-sm font-semibold hover:bg-teal-700 shadow-sm transition inline-flex items-center">
                <svg wire:loading wire:target="guardar" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <svg wire:loading.remove wire:target="guardar" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Guardar Configuración
            </button>
        </div>
    </form>
</div>