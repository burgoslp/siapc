<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgendaCompartida extends Model
{
    use HasFactory;

    protected $table = 'agendas_compartidas';
    protected $guarded = [];

    protected $casts = [
        'fecha_expiracion' => 'datetime',
        'activo' => 'boolean',
    ];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function disponibilidades(): HasMany
    {
        return $this->hasMany(Disponibilidad::class, 'agenda_compartida_id')->orderBy('fecha');
    }

    public function tarifas(): BelongsToMany
    {
        return $this->belongsToMany(Tarifa::class, 'agenda_tarifas', 'agenda_compartida_id', 'tarifa_id')
                    ->withTimestamps();
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'agenda_compartida_id');
    }
}