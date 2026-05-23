<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Detalle_mantenimiento_servicios extends Model
{
    protected $table = 'detalle_mantenimiento_servicios';
    protected $primaryKey = 'id_det_mant_ser';
    public $timestamps = false;

    protected $fillable = [
        'id_mantenimiento',
        'id_servicio',
        'precio_aplicado',
    ];

    
    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class, 'id_mantenimiento', 'id_mantenimiento');
    }

    
    public function servicio()
    {
        return $this->belongsTo(servicios::class, 'id_servicio', 'id_servicio');
    }
}