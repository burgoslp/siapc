<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Alergeno extends Model
{
    use HasFactory;

    protected $table = 'alergenos';

    protected $guarded = [];

    /**
     * Relación directa con la tabla intermedia.
     */
    public function alergiasPacientes(): HasMany
    {
        return $this->hasMany(AlergiaPaciente::class, 'id_alergeno');
    }

    /**
     * Pacientes que reaccionan a este alérgeno.
     */
    public function pacientes(): BelongsToMany
    {
        return $this->belongsToMany(
            Paciente::class,
            'alergias_pacientes',
            'id_alergeno',
            'id_paciente'
        )->withPivot([
            'id',
            'tipo_reaccion',
            'severidad',
            'fecha_diagnostico',
            'observaciones',
            'estado'
        ])->withTimestamps();
    }
}
