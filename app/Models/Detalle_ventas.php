<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Detalle_ventas extends Model
{
    protected $table = 'detalle_ventas';
    protected $primaryKey = 'id_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_venta',
        'id_producto',
        'det_cantidad',
        'det_precio_unitario',
    ];

    // Relaciones
    public function venta()
    {
        return $this->belongsTo(Ventas::class, 'id_venta', 'id_venta');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    // ❌ SIN EVENTOS - Stock manejado en VentasController
    protected static function boot()
    {
        parent::boot();
        // NADA aquí - el controller maneja el stock con SQL crudo
    }
}