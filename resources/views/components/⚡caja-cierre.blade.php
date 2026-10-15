<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Caja;

new class extends Component
{
    public bool $abierto = false;
    public ?Caja $caja = null;

    public $observaciones_cierre = '';

    #[On('cerrar-caja')]
    public function abrir(): void
    {
        $this->caja = Caja::abierta();

        if (!$this->caja) {
            session()->flash('error', 'No hay caja abierta para cerrar.');
            return;
        }

        $this->reset('observaciones_cierre');
        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    public function confirmarCierre(): void
    {
        if (!$this->caja) {
            return;
        }

        // Verificar de nuevo que sigue abierta
        $caja = Caja::find($this->caja->id);
        if (!$caja || $caja->estado !== 'ABIERTA') {
            session()->flash('error', 'La caja ya no está abierta.');
            return;
        }

        // Calcular totales desde los pagos
        $totales = $caja->totalesPorFormaPago();
        $totalGeneral = array_sum($totales);

        // Guardar snapshot de totales
        $caja->update([
            'cerrada_por' => auth()->id(),
            'cerrada_en' => now(),
            'total_efectivo' => $totales['EFECTIVO'] ?? 0,
            'total_transferencia' => $totales['TRANSFERENCIA'] ?? 0,
            'total_pago_movil' => $totales['PAGO_MOVIL'] ?? 0,
            'total_tarjeta_debito' => $totales['TARJETA_DEBITO'] ?? 0,
            'total_tarjeta_credito' => $totales['TARJETA_CREDITO'] ?? 0,
            'total_zelle' => $totales['ZELLE'] ?? 0,
            'total_binance' => $totales['BINANCE'] ?? 0,
            'total_otros' => $totales['OTRO'] ?? 0,
            'total_general' => $totalGeneral,
            'observaciones_cierre' => $this->observaciones_cierre ?: null,
            'estado' => 'CERRADA',
        ]);

        $this->dispatch('caja-cerrada');
        session()->flash('success', "Caja {$caja->codigo} cerrada correctamente.");
        $this->abierto = false;
        $this->redirect(route('facturacion.index'), navigate: false);
    }

    public function with(): array
    {
        return [
            'totales' => $this->caja?->totalesPorFormaPago() ?? [],
        ];
    }
};
?>

<div>
    @if ($abierto && $caja)
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

                {{-- Encabezado --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Cerrar Caja</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                <span class="font-mono">{{ $caja->codigo }}</span>
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-5">

                    {{-- Aviso --}}
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <p class="font-semibold">Al cerrar la caja no podrás registrar más pagos en ella.</p>
                                <p class="mt-0.5">Los totales a continuación se guardarán como snapshot del cierre.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Resumen de totales --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-700 uppercase tracking-wider mb-3">
                            Resumen del Día
                        </h4>

                        @php
                            $formas = [
                                'EFECTIVO'        => ['label' => 'Efectivo',          'icon' => 'cash'],
                                'TRANSFERENCIA'   => ['label' => 'Transferencia',     'icon' => 'bank'],
                                'PAGO_MOVIL'      => ['label' => 'Pago Móvil',        'icon' => 'phone'],
                                'TARJETA_DEBITO'  => ['label' => 'Tarjeta Débito',    'icon' => 'card'],
                                'TARJETA_CREDITO' => ['label' => 'Tarjeta Crédito',   'icon' => 'card'],
                                'ZELLE'           => ['label' => 'Zelle',             'icon' => 'dollar'],
                                'BINANCE'         => ['label' => 'Binance',           'icon' => 'crypto'],
                                'OTRO'            => ['label' => 'Otros',             'icon' => 'dots'],
                            ];
                        @endphp

                        <div class="space-y-1.5">
                            @foreach ($formas as $key => $info)
                                @if (($totales[$key] ?? 0) > 0)
                                    <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-gray-50">
                                        <span class="text-sm text-gray-700">{{ $info['label'] }}</span>
                                        <span class="text-sm font-bold text-gray-800">
                                            Bs {{ number_format($totales[$key], 2) }}
                                        </span>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        {{-- Total general --}}
                        <div class="mt-4 pt-4 border-t-2 border-gray-200">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                                    Total General
                                </span>
                                <span class="text-2xl font-bold text-teal-700">
                                    Bs {{ number_format(array_sum($totales), 2) }}
                                </span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                                <span>Monto inicial ({{ $caja->moneda_inicial === 'bs' ? 'Bs' : ($caja->moneda_inicial === 'dolar' ? 'USD' : 'EUR') }})</span>
                                <span class="font-semibold">
                                    {{ number_format($caja->monto_inicial, 2) }}
                                </span>
                            </div>
                            <div class="mt-1 flex items-center justify-between text-xs text-gray-500">
                                <span>Total transacciones</span>
                                <span class="font-semibold">{{ $caja->pagos()->count() }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Observaciones --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                            Observaciones del cierre (opcional)
                        </label>
                        <textarea wire:model="observaciones_cierre" rows="3"
                                  placeholder="Ej: Cierre sin novedades, cuadre exacto."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"></textarea>
                    </div>

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrar"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="button" wire:click="confirmarCierre"
                                wire:confirm="¿Estás seguro de cerrar la caja? No podrás registrar más pagos en ella."
                                class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Confirmar Cierre
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>