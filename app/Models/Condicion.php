<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Condicion extends Model
{
    use HasFactory;

    protected $table = 'condiciones';

    protected $guarded = [];

    protected $casts = [
        'requiere_seguimiento' => 'boolean',
        'activo' => 'boolean',
    ];

    /**
     * Relación directa con la tabla intermedia.
     */
    public function condicionesPacientes(): HasMany
    {
        return $this->hasMany(CondicionPaciente::class, 'id_condicion');
    }

    /**
     * Pacientes diagnosticados con esta condición médica.
     */
    public function pacientes(): BelongsToMany
    {
        return $this->belongsToMany(
            Paciente::class,
            'condiciones_pacientes',
            'id_condicion',
            'id_paciente'
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
            'activo'
        ])->withTimestamps();
    }
}
