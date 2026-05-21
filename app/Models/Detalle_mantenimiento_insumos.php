<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Detalle_mantenimiento_insumos extends Model
{
    /**
     * Tabla del modelo.
     */
    protected $table = 'detalle_mantenimiento_insumos';

    /**
     * Clave primaria.
     */
    protected $primaryKey = 'id_det_mant';

    /**
     * Timestamps desactivados.
     */
    public $timestamps = false;

    /**
     * Campos habilitados para asignación masiva.
     */
    protected $fillable = [
        'id_mantenimiento',
        'id_producto',
        'insumo_cantidad',
        'insumo_precio_unitario',
    ];

    /**
     * Relaciones
     */
    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class, 'id_mantenimiento', 'id_mantenimiento');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }
}
