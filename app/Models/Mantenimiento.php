<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    protected $table = 'mantenimiento';
    
    
    protected $primaryKey = 'id_mantenimiento';

   
    public $timestamps = false; 

    protected $fillable = [
        'id_cliente', 'id_mecanico', 'id_cita', 'moto_modelo', 
        'moto_llegada_descripcion', 'trabajo_realizado', 'fecha_inicio', 
        'fecha_termino', 'mantenimiento_total', 'estado_servicio'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function mecanico()
    {
        return $this->belongsTo(Empleado::class, 'id_mecanico', 'id_empleados');
    }

    public function cita()
    {
        return $this->belongsTo(Citas::class, 'id_cita', 'id_cita');
    }

    public function insumos()
    {
        return $this->hasMany(
            Detalle_mantenimiento_insumos::class,
            'id_mantenimiento',
            'id_mantenimiento'
        );
    }
}
