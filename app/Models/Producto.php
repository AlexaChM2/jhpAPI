<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    /**
     * Tabla del modelo.
     */
    protected $table = 'producto';  // Coincide con tu CREATE TABLE

    /**
     * Clave primaria.
     */
    protected $primaryKey = 'id_producto';

    /**
     * Sin timestamps.
     */
    public $timestamps = false;

    /**
     * Campos asignables en masa.
     */
    protected $fillable = [
        'pro_codigo',
        'pro_nombre',
        'pro_tipo',
        'pro_marca',
        'pro_descripcion',
        'pro_precio_venta',
        'pro_stock',
        'id_categoria',
        'id_proveedor',
    ];

    /**
     * Relación con Categoría
     */
    public function categoria()
    {
        return $this->belongsTo(Categorias::class, 'id_categoria', 'id_categoria');
    }

    /**
     * Relación con Proveedor
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }
}