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
        'reposo_fecha_fin'    => 'date',
        'editada_en'          => 'datetime',
    ];

    /**
     * Ventana de edición en horas (configurable).
     */
    public const VENTANA_EDICION_HORAS = 24;

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

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editada_por');
    }

    // ============================================================
    // HELPERS DE AUDITORÍA
    // ============================================================

    /**
     * ¿La consulta está dentro de la ventana de edición?
     */
    public function getDentroDeVentanaEdicionAttribute(): bool
    {
        return $this->created_at->diffInHours(now()) < self::VENTANA_EDICION_HORAS;
    }

    /**
     * ¿La consulta ya fue editada alguna vez?
     */
    public function getFueEditadaAttribute(): bool
    {
        return $this->editada_en !== null;
    }

    /**
     * ¿El usuario actual puede editar esta consulta?
     * - Admin: siempre puede.
     * - Médico: solo el creador Y dentro de la ventana.
     * - Otros: no.
     */
    public function puedeSerEditadaPor(?User $user): bool
    {
        if (!$user) return false;

        if ($user->esAdmin()) return true;

        if ($user->esMedico() && $user->medico?->id === $this->medico_id) {
            return $this->dentro_de_ventana_edicion;
        }

        return false;
    }

    // ============================================================
    // ACCESORES CLÍNICOS (existentes)
    // ============================================================

    public function getImcAttribute(): ?float
    {
        if (!$this->peso || !$this->altura) return null;
        return round($this->peso / ($this->altura ** 2), 2);
    }

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

    public function getTensionFormateadaAttribute(): ?string
    {
        if (!$this->tension_sistolica || !$this->tension_diastolica) {
            return null;
        }
        return "{$this->tension_sistolica}/{$this->tension_diastolica} mmHg";
    }

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