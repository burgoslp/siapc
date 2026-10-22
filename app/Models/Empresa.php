<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresas';
    protected $guarded = [];

    /**
     * Helper para obtener un valor fácilmente desde cualquier parte del sistema.
     * Uso: Empresa::get('nombre_clinica')
     */
    public static function get(string $clave, $default = null)
    {
        $registro = self::where('clave', $clave)->first();
        return $registro ? $registro->valor : $default;
    }

    /**
     * Helper para guardar o actualizar un valor.
     * Uso: Empresa::set('nombre_clinica', 'Mi Clínica')
     */
    public static function set(string $clave, $valor, string $grupo = 'general')
    {
        return self::updateOrCreate(
            ['clave' => $clave],
            ['valor' => $valor, 'grupo' => $grupo]
        );
    }
}