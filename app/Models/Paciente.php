<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Paciente extends Model
{
    use HasFactory;

    protected $table = 'pacientes';
    protected $guarded = [];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    /**
     * Registros de alergias de ESTE paciente (tabla pivote).
     * Acceso al alérgeno: $alergia->alergeno
     */
    public function alergias(): HasMany
    {
        return $this->hasMany(AlergiaPaciente::class, 'id_paciente');
    }

    /**
     * Catálogo de alérgenos relacionados con el paciente (BelongsToMany).
     * Útil si solo quieres los alérgenos sin los datos pivote.
     * NOTA: nombre distinto para no chocar con alergias()
     */
    public function alergenos(): BelongsToMany
    {
        return $this->belongsToMany(
            Alergeno::class,
            'alergias_pacientes',
            'id_paciente',
            'id_alergeno'
        )->withPivot([
            'id',
            'tipo_reaccion',
            'severidad',
            'fecha_diagnostico',
            'observaciones',
            'estado',
        ])->withTimestamps();
    }

    /**
     * Registros de condiciones de ESTE paciente (tabla pivote).
     * Acceso a la condición: $condicion->condicion
     */
    public function condiciones(): HasMany
    {
        return $this->hasMany(CondicionPaciente::class, 'id_paciente');
    }

    /**
     * Catálogo de condiciones relacionadas con el paciente (BelongsToMany).
     * NOTA: nombre distinto para no chocar con condiciones()
     */
    public function catalogoCondiciones(): BelongsToMany
    {
        return $this->belongsToMany(
            Condicion::class,
            'condiciones_pacientes',
            'id_paciente',
            'id_condicion'
        )->withPivot([
            'id',
            'fecha_diagnostico',
            'severidad',
            'estado',
            'diagnostico_por',
            'especialidad',
            'notas',
            'fecha_ultimo_control',
            'proximo_control_sugerido',
            'activo',
        ])->withTimestamps();
    }

    /**
     * Consultas médicas del paciente.
     */
    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class, 'paciente_id');
    }


    public function getTipoDenticionAttribute(): string
    {
        if (!$this->fecha_nacimiento) return 'permanente';
        
        $edad = $this->fecha_nacimiento->age;

        // 0 a 5 años: Dentición Temporal (Infantil/Bebés)
        if ($edad <= 5) return 'infantil';
        
        // 6 a 12 años: Dentición Mixta (Tiene dientes de ambos)
        if ($edad <= 12) return 'mixto';
        
        // 13+ años: Dentición Permanente
        return 'permanente';
    }
        
}