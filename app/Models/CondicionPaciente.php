<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CondicionPaciente extends Model
{
   use HasFactory;

    protected $table = 'condiciones_pacientes';

    protected $guarded = [];

    protected $casts = [
        'fecha_diagnostico' => 'datetime',
        'fecha_ultimo_control' => 'datetime',
        'proximo_control_sugerido' => 'date',
        'activo' => 'boolean',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function condicion(): BelongsTo
    {
        return $this->belongsTo(Condicion::class, 'id_condicion');
    }
}
