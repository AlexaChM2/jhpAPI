<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Detalle_cotizaciones extends Model
{
    protected $table = 'detalle_cotizaciones';
    protected $primaryKey = 'id_detalle_cotizacion';
    public $timestamps = false;

    protected $fillable = [
        'id_cotizacion',
        'id_producto',
        'det_cantidad',
        'det_precio_unitario',
    ];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizaciones::class, 'id_cotizacion', 'id_cotizacion');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }
}