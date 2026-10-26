<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\Banco;
use App\Models\Comision;

new class extends Component
{
    public ?int $cuentaId = null;
    public bool $abierto = false;

    // Datos de la cuenta
    public $nombre = '';
    public $tipo = 'BANCO';
    public $moneda = 'bs';
    public $banco_id = '';
    public $lci = 'J';
    public $ci = '';
    public $titular = '';
    public $numero_cuenta = '';
    public $saldo_inicial = 0;
    public $activa = true;
    public $es_predeterminada = false;
    public $notas = '';

    // Comisiones (array de comisiones en línea)
    public array $comisiones = [];

    #[On('nueva-cuenta')]
    public function abrirNueva(): void
    {
        $this->reset();
        $this->tipo = 'BANCO';
        $this->moneda = 'bs';
        $this->lci = 'J';
        $this->activa = true;
        $this->comisiones = [];
        $this->cuentaId = null;
        $this->abierto = true;
    }

    #[On('editar-cuenta')]
    public function abrirEdicion(int $id): void
    {
        $cuenta = Cuenta::with('comisiones')->find($id);
        if (!$cuenta) return;

        $this->cuentaId = $cuenta->id;
        $this->nombre = $cuenta->nombre;
        $this->tipo = $cuenta->tipo;
        $this->moneda = $cuenta->moneda;
        $this->banco_id = $cuenta->banco_id ?? '';
        $this->lci = $cuenta->lci;
        $this->ci = $cuenta->ci;
        $this->titular = $cuenta->titular ?? '';
        $this->numero_cuenta = $cuenta->numero_cuenta ?? '';
        $this->saldo_inicial = $cuenta->saldo_inicial;
        $this->activa = $cuenta->activa;
        $this->es_predeterminada = $cuenta->es_predeterminada;
        $this->notas = $cuenta->notas ?? '';

        // Cargar comisiones existentes
        $this->comisiones = $cuenta->comisiones->map(fn($c) => [
            'id' => $c->id,
            'forma_pago' => $c->forma_pago,
            'porcentaje_ingreso' => $c->porcentaje_ingreso,
            'monto_fijo_ingreso' => $c->monto_fijo_ingreso,
            'porcentaje_egreso' => $c->porcentaje_egreso,
            'monto_fijo_egreso' => $c->monto_fijo_egreso,
            'activo' => $c->activo,
        ])->toArray();

        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    // ============================================================
    // GESTIÓN DE COMISIONES
    // ============================================================

    public function agregarComision(): void
    {
        $this->comisiones[] = [
            'id' => null,
            'forma_pago' => 'EFECTIVO',
            'porcentaje_ingreso' => 0,
            'monto_fijo_ingreso' => 0,
            'porcentaje_egreso' => 0,
            'monto_fijo_egreso' => 0,
            'activo' => true,
        ];
    }

    public function quitarComision(int $index): void
    {
        unset($this->comisiones[$index]);
        $this->comisiones = array_values($this->comisiones);
    }

    // ============================================================
    // GUARDAR
    // ============================================================

    public function guardar(): void
    {
        // Validar cuenta
        $datos = $this->validate([
            'nombre' => 'required|string|max:100',
            'tipo' => 'required|in:EFECTIVO,BANCO,PLATAFORMA DIGITAL',
            'moneda' => 'required|in:dolar,euro,bs',
            'banco_id' => 'nullable|exists:bancos,id',
            'lci' => 'required|in:V,J',
            'ci' => 'required|string|max:20',
            'titular' => 'nullable|string|max:150',
            'numero_cuenta' => 'nullable|string|max:50',
            'saldo_inicial' => 'required|numeric|min:0',
            'activa' => 'boolean',
            'es_predeterminada' => 'boolean',
            'notas' => 'nullable|string',
        ]);

        // Validar comisiones (básicas)
        foreach ($this->comisiones as $i => $comision) {
            if (empty($comision['forma_pago'])) {
                $this->addError("comisiones.{$i}.forma_pago", 'La forma de pago es obligatoria.');
                return;
            }
        }

        \DB::transaction(function () use ($datos) {
            // Si se marca como predeterminada, desmarcar las demás
            if ($datos['es_predeterminada']) {
                Cuenta::where('id', '!=', $this->cuentaId)->update(['es_predeterminada' => false]);
            }

            $datos['banco_id'] = $this->banco_id ?: null;

            // Crear o actualizar cuenta
            if ($this->cuentaId) {
                $cuenta = Cuenta::find($this->cuentaId);
                $cuenta->update($datos);
            } else {
                $cuenta = Cuenta::create($datos);
            }

            // Sincronizar comisiones
            $idsExistentes = [];

            foreach ($this->comisiones as $comision) {
                if (!empty($comision['id'])) {
                    // Actualizar existente
                    $c = Comision::find($comision['id']);
                    if ($c && $c->cuenta_id === $cuenta->id) {
                        $c->update([
                            'forma_pago' => $comision['forma_pago'],
                            'porcentaje_ingreso' => $comision['porcentaje_ingreso'] ?: 0,
                            'monto_fijo_ingreso' => $comision['monto_fijo_ingreso'] ?: 0,
                            'porcentaje_egreso' => $comision['porcentaje_egreso'] ?: 0,
                            'monto_fijo_egreso' => $comision['monto_fijo_egreso'] ?: 0,
                            'activo' => $comision['activo'] ?? true,
                        ]);
                        $idsExistentes[] = $c->id;
                    }
                } else {
                    // Crear nueva
                    $c = Comision::create([
                        'cuenta_id' => $cuenta->id,
                        'forma_pago' => $comision['forma_pago'],
                        'porcentaje_ingreso' => $comision['porcentaje_ingreso'] ?: 0,
                        'monto_fijo_ingreso' => $comision['monto_fijo_ingreso'] ?: 0,
                        'porcentaje_egreso' => $comision['porcentaje_egreso'] ?: 0,
                        'monto_fijo_egreso' => $comision['monto_fijo_egreso'] ?: 0,
                        'activo' => $comision['activo'] ?? true,
                    ]);
                    $idsExistentes[] = $c->id;
                }
            }

            // Eliminar comisiones que ya no están en el array
            Comision::where('cuenta_id', $cuenta->id)
                ->whereNotIn('id', $idsExistentes)
                ->delete();
        });

        session()->flash('success', $this->cuentaId ? 'Cuenta actualizada.' : 'Cuenta creada.');
        $this->dispatch('cuenta-guardada');
        $this->abierto = false;
    }

    public function with(): array
    {
        return [
            'bancos' => Banco::where('activo', true)->orderBy('nombre')->get(),
            'formasPago' => [
                'EFECTIVO'        => 'Efectivo',
                'TRANSFERENCIA'   => 'Transferencia',
                'PAGO_MOVIL'      => 'Pago Móvil',
                'TARJETA_DEBITO'  => 'Tarjeta Débito',
                'TARJETA_CREDITO' => 'Tarjeta Crédito',
                'ZELLE'           => 'Zelle',
                'BINANCE'         => 'Binance',
                'OTRO'            => 'Otro',
            ],
        ];
    }
};
?>

<div>
    @if ($abierto)
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[95vh] overflow-y-auto">

                {{-- Encabezado --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-bold text-gray-800">
                        {{ $cuentaId ? 'Editar Cuenta' : 'Nueva Cuenta' }}
                    </h3>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="guardar" class="px-6 py-5 space-y-6">

                    {{-- SECCIÓN: Datos de la cuenta --}}
                    <div>
                        <h4 class="text-sm font-bold text-teal-700 uppercase tracking-wide mb-3">
                            Datos de la Cuenta
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre *</label>
                                <input type="text" wire:model="nombre"
                                       placeholder="Ej: Banesco Principal Bs"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('nombre') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tipo *</label>
                                <select wire:model.live="tipo" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="EFECTIVO">Efectivo</option>
                                    <option value="BANCO">Banco</option>
                                    <option value="PLATAFORMA DIGITAL">Plataforma Digital</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Moneda *</label>
                                <select wire:model="moneda" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="bs">Bolívares</option>
                                    <option value="dolar">Dólares</option>
                                    <option value="euro">Euros</option>
                                </select>
                            </div>

                            @if ($tipo === 'BANCO')
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Banco</label>
                                    <select wire:model="banco_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="">Seleccione un banco</option>
                                        @foreach ($bancos as $b)
                                            <option value="{{ $b->id }}">{{ $b->nombre_completo }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Número de Cuenta</label>
                                    <input type="text" wire:model="numero_cuenta" placeholder="0134-XXXX-XX-XXXXXXXXXX"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                            @endif

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Titular</label>
                                <input type="text" wire:model="titular" placeholder="Clínica San Rafael"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">RIF *</label>
                                <div class="flex gap-2">
                                    <select wire:model="lci" class="w-1/4 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="J">J</option>
                                        <option value="V">V</option>
                                    </select>
                                    <input type="text" wire:model="ci" placeholder="12345678-9"
                                           class="w-3/4 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                @error('ci') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Saldo Inicial</label>
                                <input type="number" step="0.01" wire:model="saldo_inicial"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            </div>

                            <div class="flex items-end">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="activa"
                                           class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                    <span class="ml-2 text-sm text-gray-700">Cuenta activa</span>
                                </label>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="es_predeterminada"
                                           class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                    <span class="ml-2 text-sm text-gray-700">
                                        Marcar como predeterminada
                                        <span class="block text-xs text-gray-500">Se usará por defecto al cobrar</span>
                                    </span>
                                </label>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Notas</label>
                                <textarea wire:model="notas" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- SECCIÓN: Comisiones --}}
                    <div class="pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h4 class="text-sm font-bold text-teal-700 uppercase tracking-wide">
                                    Comisiones
                                </h4>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Comisiones aplicadas al recibir (ingreso) o enviar (egreso) dinero por cada forma de pago.
                                </p>
                            </div>

                            <button type="button" wire:click="agregarComision"
                                    class="px-3 py-1.5 bg-teal-600 text-white rounded-lg text-xs font-medium hover:bg-teal-700 inline-flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Agregar comisión
                            </button>
                        </div>

                        @if (empty($comisiones))
                            <div class="p-4 bg-gray-50 border border-dashed border-gray-300 rounded-lg text-center text-sm text-gray-500">
                                No hay comisiones configuradas. Puedes agregarlas ahora o después editando la cuenta.
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($comisiones as $i => $comision)
                                    <div wire:key="comision-{{ $i }}" class="border border-gray-200 rounded-lg p-3 bg-gray-50/50">
                                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 items-end">
                                            {{-- Forma de pago --}}
                                            <div class="col-span-2 sm:col-span-1 lg:col-span-1">
                                                <label class="block text-[10px] font-semibold text-gray-600 uppercase mb-1">Forma</label>
                                                <select wire:model="comisiones.{{ $i }}.forma_pago"
                                                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                                    @foreach ($formasPago as $key => $label)
                                                        <option value="{{ $key }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            {{-- % Ingreso --}}
                                            <div>
                                                <label class="block text-[10px] font-semibold text-green-700 uppercase mb-1">% Ingreso</label>
                                                <input type="number" step="0.01" min="0"
                                                       wire:model="comisiones.{{ $i }}.porcentaje_ingreso"
                                                       class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                            </div>

                                            {{-- Fijo Ingreso --}}
                                            <div>
                                                <label class="block text-[10px] font-semibold text-green-700 uppercase mb-1">Fijo Ingreso</label>
                                                <input type="number" step="0.01" min="0"
                                                       wire:model="comisiones.{{ $i }}.monto_fijo_ingreso"
                                                       class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                            </div>

                                            {{-- % Egreso --}}
                                            <div>
                                                <label class="block text-[10px] font-semibold text-red-700 uppercase mb-1">% Egreso</label>
                                                <input type="number" step="0.01" min="0"
                                                       wire:model="comisiones.{{ $i }}.porcentaje_egreso"
                                                       class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                            </div>

                                            {{-- Fijo Egreso --}}
                                            <div>
                                                <label class="block text-[10px] font-semibold text-red-700 uppercase mb-1">Fijo Egreso</label>
                                                <input type="number" step="0.01" min="0"
                                                       wire:model="comisiones.{{ $i }}.monto_fijo_egreso"
                                                       class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                            </div>

                                            {{-- Quitar --}}
                                            <div>
                                                <button type="button" wire:click="quitarComision({{ $i }})"
                                                        class="w-full px-2 py-1.5 bg-red-50 text-red-600 border border-red-200 rounded text-xs font-medium hover:bg-red-100">
                                                    Quitar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrar"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium">
                            {{ $cuentaId ? 'Guardar Cambios' : 'Crear Cuenta' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>