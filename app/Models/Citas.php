<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Citas extends Model
{
    /**
     * Tabla del modelo.
     */
    protected $table = 'citas';

    /**
     * Clave primaria.
     */
    protected $primaryKey = 'id_cita';

  
    public $timestamps = false;

 
    protected $fillable = [
        'id_cliente',
        'id_empleado',
        'cita_fecha_programada',
        'cita_motivo',
        'cita_estado',
        'cita_tipo',  
        'cita_notas',
    ];

    /**
     * Relación con el Cliente.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    /**
     * Relación con el Empleado (quien registró la cita).
     */
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleados');
    }
}
