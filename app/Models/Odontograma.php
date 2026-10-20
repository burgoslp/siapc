<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Odontograma extends Model
{
    use HasFactory;

    protected $table = 'odontogramas';
    protected $guarded = [];

    protected $casts = [
        'datos' => 'array',
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeDelPaciente($query, int $pacienteId)
    {
        return $query->where('paciente_id', $pacienteId);
    }

    public function scopeDeLaConsulta($query, int $consultaId)
    {
        return $query->where('consulta_id', $consultaId);
    }

    /**
     * Devuelve el odontograma más reciente del paciente (para uso evolutivo).
     */
    public function scopeUltimoDelPaciente($query, int $pacienteId)
    {
        return $query->where('paciente_id', $pacienteId)->latest('id')->first();
    }

    // ============================================================
    // HELPERS
    // ============================================================

    public function getEstadoSuperficie(int $diente, string $superficie): ?string
    {
        return $this->datos[$diente]['superficies'][$superficie] ?? null;
    }

    public function getEstadoGeneral(int $diente): ?string
    {
        return $this->datos[$diente]['general'] ?? null;
    }

    public function dienteTieneHallazgos(int $diente): bool
    {
        $dato = $this->datos[$diente] ?? null;
        if (!$dato) return false;

        if (!empty($dato['general']) && $dato['general'] !== 'SANO') {
            return true;
        }

        foreach ($dato['superficies'] ?? [] as $estado) {
            if ($estado !== 'SANO') return true;
        }

        return false;
    }

    public function getTotalDientesAfectadosAttribute(): int
    {
        $total = 0;
        foreach ($this->datos ?? [] as $diente => $dato) {
            if ($this->dienteTieneHallazgos((int) $diente)) {
                $total++;
            }
        }
        return $total;
    }

    public function getDientesAfectadosAttribute(): array
    {
        $afectados = [];
        foreach ($this->datos ?? [] as $diente => $dato) {
            if ($this->dienteTieneHallazgos((int) $diente)) {
                $afectados[] = (int) $diente;
            }
        }
        return $afectados;
    }

    public static function estadosLegibles(): array
    {
        return [
            'SANO'                => 'Sano',
            'CARIES'              => 'Caries',
            'OBTURADO'            => 'Obturado',
            'FRACTURA'            => 'Fractura',
            'EXTRACCION_INDICADA' => 'Extracción indicada',
            'CORONA'              => 'Corona',
            'ENDODONCIA'          => 'Endodoncia',
            'IMPLANTE'            => 'Implante',
            'EXTRAIDO'            => 'Extraído',
            'PUENTE'              => 'Puente',
        ];
    }

    public static function coloresEstados(): array
    {
        return [
            'SANO'                => '#f3f4f6',
            'CARIES'              => '#ef4444',
            'OBTURADO'            => '#3b82f6',
            'FRACTURA'            => '#f97316',
            'EXTRACCION_INDICADA' => '#eab308',
            'CORONA'              => '#a855f7',
            'ENDODONCIA'          => '#8b5cf6',
            'IMPLANTE'            => '#0ea5e9',
            'EXTRAIDO'            => '#374151',
            'PUENTE'              => '#22c55e',
        ];
    }
}