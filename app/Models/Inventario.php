<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $table = 'inventarios';
    protected $primaryKey = 'id_inventario';
    public $timestamps = true;  // ← Ahora sí tiene timestamps

    protected $fillable = [
        'id_producto',
        'codigo_producto',
        'nombre_producto',
        'marca',
        'categoria',
        'stock',
        'precio_unitario',
        'iva',
        'precio_total',
        'id_proveedor',
        'proveedor',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'iva' => 'decimal:2',
        'precio_total' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }
}