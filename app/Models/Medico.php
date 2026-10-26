<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    public function getCiCompletoAttribute(): string
    {
        return "{$this->lci}-{$this->ci}";
    }

    /**
     * Devuelve la URL pública de la firma del médico.
     * Retorna null si no tiene firma cargada.
     */
    public function getFirmaUrlAttribute(): ?string
    {
        if ($this->firma_path && Storage::disk('public')->exists($this->firma_path)) {
            return Storage::url($this->firma_path); 
        }
        return null;
    }

    /**
     * Devuelve la ruta absoluta en el servidor de la firma.
     * Útil para DomPDF (que requiere rutas físicas, no URLs).
     */
    public function getFirmaPathAbsolutoAttribute(): ?string
    {
        if ($this->firma_path && Storage::disk('public')->exists($this->firma_path)) {
            return Storage::disk('public')->path($this->firma_path);
        }
        return null;
    }

    // ============================================================
    // SCOPES
    // ============================================================

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
}