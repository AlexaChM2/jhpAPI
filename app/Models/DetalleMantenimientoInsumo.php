<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleMantenimientoInsumo extends Model
{
    protected $table = 'detalle_mantenimiento_insumos';
    protected $primaryKey = 'id_det_mant';
    public $timestamps = false;

    protected $fillable = [
        'id_mantenimiento',
        'id_producto',
        'insumo_cantidad',
        'insumo_precio_unitario',
    ];

    // Relaciones
    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class, 'id_mantenimiento', 'id_mantenimiento');
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

        // Al CREAR insumo, descontar stock
        static::created(function ($insumo) {
            $producto = Producto::find($insumo->id_producto);
            if ($producto) {
                $producto->descontarStock($insumo->insumo_cantidad);
            }
        });

        // Al ELIMINAR insumo, devolver stock
        static::deleted(function ($insumo) {
            $producto = Producto::find($insumo->id_producto);
            if ($producto) {
                $producto->devolverStock($insumo->insumo_cantidad);
            }
        });

        // Al ACTUALIZAR cantidad, ajustar stock
        static::updated(function ($insumo) {
            if ($insumo->isDirty('insumo_cantidad')) {
                $original = $insumo->getOriginal('insumo_cantidad');
                $nueva = $insumo->insumo_cantidad;
                $diferencia = $original - $nueva;
                
                $producto = Producto::find($insumo->id_producto);
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