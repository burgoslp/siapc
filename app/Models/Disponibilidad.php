<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disponibilidad extends Model
{
    use HasFactory;

    protected $table = 'disponibilidades';
    protected $guarded = [];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function agendaCompartida(): BelongsTo
    {
        return $this->belongsTo(AgendaCompartida::class, 'agenda_compartida_id');
    }
}