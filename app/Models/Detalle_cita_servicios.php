<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Detalle_cita_servicios extends Model
{
    /**
     * Tabla del modelo.
     */
    protected $table = 'detalle_cita_servicios';

  
    protected $primaryKey = 'id_det_cita';

   
    public $timestamps = false;

    protected $fillable = [
        'id_cita',
        'id_servicio',
    ];
}
