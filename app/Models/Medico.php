<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Medico extends Model
{
    use HasFactory;

    protected $table = 'medicos';
    protected $guarded = [];

    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(
            Especialidad::class,
            'especialidades_medicos',
            'medico_id',
            'especialidad_id'
        )->withTimestamps();
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'medico_id');
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class, 'medico_id');
    }

    // Accessors
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    public function getCiCompletoAttribute(): string
    {
        return "{$this->lci}-{$this->ci}";
    }

    // Scopes
    public function scopeActivos($q)
    {
        return $q->where('estado', 'ACTIVO');
    }

    public function scopeBuscar($q, ?string $term)
    {
        if (blank($term)) return $q;

        return $q->where(function ($w) use ($term) {
            $w->where('nombre', 'like', "%{$term}%")
              ->orWhere('apellido', 'like', "%{$term}%")
              ->orWhere('ci', 'like', "%{$term}%")
              ->orWhere('licencia_medica', 'like', "%{$term}%");
        });
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}