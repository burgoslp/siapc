<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Banco extends Model
{
    use HasFactory;

    protected $table = 'bancos';
    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class, 'banco_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->codigo_banco
            ? "{$this->nombre} ({$this->codigo_banco})"
            : $this->nombre;
    }
}