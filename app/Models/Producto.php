<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'producto';
    protected $primaryKey = 'id_producto';
    public $timestamps = false;

    protected $fillable = [
        'pro_codigo',
        'pro_nombre',
        'pro_tipo',
        'id_marca',        // ← NUEVO
        'pro_marca',       // ← Mantén este si aún lo usas
        'pro_descripcion',
        'pro_precio_venta',
        'pro_stock',
        'id_categoria',
        'id_proveedor',
    ];

    // Relación con Categoría
    public function categoria()
    {
        return $this->belongsTo(Categorias::class, 'id_categoria', 'id_categoria');
    }

    // Relación con Proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }

    // NUEVA Relación con Marca
    public function marca()
    {
        return $this->belongsTo(Marca::class, 'id_marca', 'id_marca');
    }

    /**
     * Descontar stock (venta o mantenimiento)
     */
    public function descontarStock(int $cantidad)
    {
        if ($this->pro_stock >= $cantidad) {
            $this->decrement('pro_stock', $cantidad);
            return true;
        }
        return false;
    }

    /**
     * Devolver stock (cancelación)
     */
    public function devolverStock(int $cantidad)
    {
        $this->increment('pro_stock', $cantidad);
    }
}