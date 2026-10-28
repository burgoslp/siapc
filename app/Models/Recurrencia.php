<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recurrencia extends Model
{
    use HasFactory;

    protected $table = 'recurrencias';
    protected $guarded = [];

    protected $casts = [
        'monto_estimado' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_id');
    }

    /**
     * Los pagos históricos que se han hecho de esta recurrencia.
     */
    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'recurrencia_id');
    }
}