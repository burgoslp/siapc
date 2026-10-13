<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlergiaPaciente extends Model
{
    use HasFactory;

    protected $table = 'alergias_pacientes';

    protected $guarded = [];

    protected $casts = [
        'fecha_diagnostico' => 'datetime',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function alergeno(): BelongsTo
    {
        return $this->belongsTo(Alergeno::class, 'id_alergeno');
    }
}
