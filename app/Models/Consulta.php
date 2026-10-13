<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consulta extends Model
{
    use HasFactory;

    protected $table = 'consultas';
    protected $guarded = [];

    protected $casts = [
        'reposo_fecha_inicio' => 'date',
        'reposo_fecha_fin' => 'date',
    ];

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    /**
     * IMC calculado (peso / altura²)
     */
    public function getImcAttribute(): ?float
    {
        if (!$this->peso || !$this->altura) return null;

        return round($this->peso / ($this->altura ** 2), 2);
    }

    /**
     * Clasificación del IMC según la OMS
     */
    public function getImcClasificacionAttribute(): ?string
    {
        $imc = $this->imc;
        if ($imc === null) return null;

        return match (true) {
            $imc < 18.5 => 'Bajo peso',
            $imc < 25   => 'Normal',
            $imc < 30   => 'Sobrepeso',
            $imc < 35   => 'Obesidad grado I',
            $imc < 40   => 'Obesidad grado II',
            default     => 'Obesidad grado III',
        };
    }

    /**
     * Tensión arterial formateada: "120/80 mmHg"
     */
    public function getTensionFormateadaAttribute(): ?string
    {
        if (!$this->tension_sistolica || !$this->tension_diastolica) {
            return null;
        }

        return "{$this->tension_sistolica}/{$this->tension_diastolica} mmHg";
    }

    /**
     * Clasificación de la tensión (según guías AHA)
     */
    public function getTensionClasificacionAttribute(): ?string
    {
        $sis = $this->tension_sistolica;
        $dia = $this->tension_diastolica;

        if (!$sis || !$dia) return null;

        return match (true) {
            $sis >= 180 || $dia >= 120 => 'Crisis hipertensiva',
            $sis >= 140 || $dia >= 90  => 'Hipertensión etapa 2',
            $sis >= 130 || $dia >= 80  => 'Hipertensión etapa 1',
            $sis >= 120                => 'Elevada',
            $sis < 90  || $dia < 60    => 'Hipotensión',
            default                    => 'Normal',
        };
    }

    /**
     * Temperatura clasificada
     */
    public function getTemperaturaClasificacionAttribute(): ?string
    {
        $temp = $this->temperatura;
        if ($temp === null) return null;

        return match (true) {
            $temp < 35     => 'Hipotermia',
            $temp < 37.5   => 'Normal',
            $temp < 38.5   => 'Febrícula',
            $temp < 39.5   => 'Fiebre',
            default        => 'Fiebre alta',
        };
    }
}