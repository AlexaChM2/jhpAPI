<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Detalle_mantenimiento_servicios extends Model
{
    /**
     * Tabla del modelo.
     */
    protected $table = 'detalle_Mantenimiento_Servicios';

    /**
     * Clave primaria.
     */
    protected $primaryKey = 'id_det_mant_ser';

    /**
     * Desactivar timestamps.
     */
    public $timestamps = false;

    
    protected $fillable = [
        'id_mantenimiento',
        'id_servicio',
        'precio_aplicado',
    ];
}
