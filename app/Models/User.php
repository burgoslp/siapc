<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['username', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    // ---------- Helpers de roles ----------

    public function tieneRol(string $nombre): bool
    {
        return $this->roles->contains('nombre', $nombre);
    }

    public function tieneAlgunRol(array $nombres): bool
    {
        return $this->roles->whereIn('nombre', $nombres)->isNotEmpty();
    }

    public function esAdmin(): bool
    {
        return $this->tieneRol('admin');
    }

    public function esMedico(): bool
    {
        return $this->tieneRol('medico') && $this->medico !== null;
    }

    public function esRecepcion(): bool
    {
        return $this->tieneRol('recepcion');
    }

    /**
     * ¿Puede ver todas las citas/consultas? (admin y recepción)
     */
    public function puedeVerTodo(): bool
    {
        return $this->tieneAlgunRol(['admin', 'recepcion']);
    }

    public function medico(): HasOne
    {
        return $this->hasOne(Medico::class, 'user_id');
    }

    // Accessors
    public function getNombreMostrarAttribute(): string
    {
        // Si es médico, muestra "Dr. Carlos Mendoza"
        if ($this->medico) {
            return 'Dr. ' . $this->medico->nombre . ' ' . $this->medico->apellido;
        }

        // Si no, muestra el username o un texto genérico
        return ucfirst($this->username);
    }

    public function getRolMostrarAttribute(): string
    {
        return $this->roles->pluck('etiqueta')->join(' · ') ?: 'Sin rol';
    }


}
