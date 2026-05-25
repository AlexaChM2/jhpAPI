<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleMantenimientoInsumo extends Model
{
    protected $table = 'detalle_mantenimiento_insumos';
    protected $primaryKey = 'id_detalle_mantenimiento_insumos';
    public $timestamps = false;

    protected $fillable = [
        'id_mantenimiento',
        'id_producto',
        'insumo_cantidad',
        'insumo_precio_unitario'
    ];

    // Relación con producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    // Relación con mantenimiento
    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class, 'id_mantenimiento', 'id_mantenimiento');
    }

    // ❌ ELIMINAR O COMENTAR EL BOOT SI TIENE EVENTOS AUTOMÁTICOS
    // Ya que ahora manejamos el stock manualmente en el controller
    /*
    protected static function boot()
    {
        parent::boot();
        
        static::created(function ($detalle) {
            $producto = Producto::find($detalle->id_producto);
            if ($producto) {
                $producto->decrement('pro_stock', $detalle->insumo_cantidad);
            }
        });
        
        static::deleted(function ($detalle) {
            $producto = Producto::find($detalle->id_producto);
            if ($producto) {
                $producto->increment('pro_stock', $detalle->insumo_cantidad);
            }
        });
    }
    */
}