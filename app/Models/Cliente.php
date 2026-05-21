<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Cliente extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'clientes';
    protected $primaryKey = 'id_cliente';
    public $timestamps = true;

    protected $fillable = [
        'cli_nombre',
        'cli_apaterno',
        'cli_amaterno',
        'cli_telefono',
        'cli_correo',
        'cli_direccion',
        'cli_password',
        'cli_telefonos_extra',
        'tipo_usuario',
        'cli_estado',
    ];

    protected $hidden = [
        'cli_password',
        'remember_token',
    ];

    protected $casts = [
        'tipo_usuario' => 'integer',
        'cli_estado' => 'string',
    ];

    /**
     * Obtener nombre completo del cliente
     */
    public function getNombreCompletoAttribute(): string
    {
        $nombre = $this->cli_nombre . ' ' . $this->cli_apaterno;
        
        if ($this->cli_amaterno) {
            $nombre .= ' ' . $this->cli_amaterno;
        }
        
        return $nombre;
    }

    /**
     * Verificar si el cliente está activo
     */
    public function isActivo(): bool
    {
        return $this->cli_estado === 'Activo';
    }

    /**
     * Scope para clientes activos
     */
    public function scopeActivos($query)
    {
        return $query->where('cli_estado', 'Activo');
    }
}