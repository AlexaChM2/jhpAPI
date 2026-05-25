<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProveedorVisita extends Model
{
    protected $table = 'proveedor_visitas';
    protected $primaryKey = 'id_visita';
    public $timestamps = false;

    protected $fillable = [
        'id_proveedor',
        'dia_semana',
        'hora_visita',
        'activo',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
        'activo' => 'boolean',
    ];

    // Días de la semana en español
    public static $diasSemana = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }

    public function getDiaNombreAttribute()
    {
        return self::$diasSemana[$this->dia_semana] ?? 'Desconocido';
    }
}