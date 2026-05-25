<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleMantenimientoInsumo extends Model
{
    protected $table = 'detalle_mantenimiento_insumos';
    
    // 🔥 CORREGIR: La primary key real en la BD es 'id_det_mant'
    protected $primaryKey = 'id_det_mant';
    
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

    // SIN EVENTOS AUTOMÁTICOS
    protected static function boot()
    {
        parent::boot();
    }
}