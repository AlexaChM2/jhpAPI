<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Detalle_ventas extends Model
{
    /**
     * Tabla del modelo.
     */
    protected $table = 'Detalle_Ventas';  // ← Coincide con tu CREATE TABLE

    /**
     * Clave primaria.
     */
    protected $primaryKey = 'id_detalle';  // ← Coincide con tu tabla

    /**
     * SIN timestamps - CORREGIDO (antes estaba true)
     */
    public $timestamps = false;  // ← ESTE ERA EL ERROR

    /**
     * Campos asignables.
     */
    protected $fillable = [
        'id_venta',
        'id_producto',
        'det_cantidad',
        'det_precio_unitario',
    ];

    /**
     * Relación con Venta.
     */
    public function venta()
    {
        return $this->belongsTo(Ventas::class, 'id_venta', 'id_venta');
    }

    /**
     * Relación con Producto.
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }
}