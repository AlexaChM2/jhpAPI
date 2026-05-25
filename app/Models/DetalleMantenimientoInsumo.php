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

    // ❌ DESACTIVAR EVENTOS AUTOMÁTICOS
    // El stock se maneja MANUALMENTE en MantenimientoController
    /*
    protected static function boot()
    {
        parent::boot();
        
        static::created(function ($detalle) {
            // NO hacer nada - el controller ya descuenta stock
        });
        
        static::deleted(function ($detalle) {
            // NO hacer nada - el controller ya repone stock
        });
    }
    */
}