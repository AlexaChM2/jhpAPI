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

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class, 'id_mantenimiento', 'id_mantenimiento');
    }

    // ❌ SIN EVENTOS AUTOMÁTICOS - Stock se maneja en Controller
    protected static function boot()
    {
        parent::boot();
    }
}