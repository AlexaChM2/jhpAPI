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

    /**
     * Boot: eventos del modelo
     */
    protected static function boot()
    {
        parent::boot();

        // Al CREAR un detalle, descontar stock
        static::created(function ($detalle) {
            $producto = Producto::find($detalle->id_producto);
            if ($producto) {
                $producto->descontarStock($detalle->det_cantidad);
            }
        });

        // Al ELIMINAR un detalle, devolver stock
        static::deleted(function ($detalle) {
            $producto = Producto::find($detalle->id_producto);
            if ($producto) {
                $producto->devolverStock($detalle->det_cantidad);
            }
        });

        // Al ACTUALIZAR cantidad, ajustar stock
        static::updated(function ($detalle) {
            if ($detalle->isDirty('det_cantidad')) {
                $original = $detalle->getOriginal('det_cantidad');
                $nueva = $detalle->det_cantidad;
                $diferencia = $original - $nueva;
                
                $producto = Producto::find($detalle->id_producto);
                if ($producto) {
                    if ($diferencia > 0) {
                        $producto->devolverStock(abs($diferencia));
                    } elseif ($diferencia < 0) {
                        $producto->descontarStock(abs($diferencia));
                    }
                }
            }
        });
    }
}