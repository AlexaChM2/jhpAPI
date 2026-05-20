<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Empleados extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'Empleados';
    protected $primaryKey = 'id_empleados';
    public $timestamps = false;

    protected $fillable = [
        'emp_nombre',
        'emp_apaterno',
        'emp_amaterno',
        'emp_telefono',
        'emp_correo',
        'emp_direccion',
        'emp_rol',
        'emp_password',
        'emp_estado',
    ];

    protected $hidden = [
        'emp_password',
        'remember_token',
    ];

    protected $casts = [
        'emp_rol' => 'string',
        'emp_estado' => 'string',
    ];

    public function getNombreCompletoAttribute(): string
    {
        $nombreCompleto = $this->emp_nombre . ' ' . $this->emp_apaterno;
        
        if ($this->emp_amaterno) {
            $nombreCompleto .= ' ' . $this->emp_amaterno;
        }
        
        return $nombreCompleto;
    }

    public function isActivo(): bool
    {
        return $this->emp_estado === 'Activo';
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->emp_rol, $roles);
        }
        
        return $this->emp_rol === $roles;
    }

    public function scopeActivos($query)
    {
        return $query->where('emp_estado', 'Activo');
    }

    public function scopePorRol($query, string $rol)
    {
        return $query->where('emp_rol', $rol);
    }
}