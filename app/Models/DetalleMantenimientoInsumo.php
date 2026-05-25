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

    // DESACTIVAR TODOS LOS EVENTOS DEL MODELO
    public $dispatchesEvents = [];
    
    protected $observables = [];
    
    protected static $events = [];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class, 'id_mantenimiento', 'id_mantenimiento');
    }

    // FORZAR sin eventos
    protected static function boot()
    {
        parent::boot();
        
        // Eliminar explícitamente cualquier evento registrado
        static::flushEventListeners();
    }
    
    // Sobrescribir para que NUNCA dispare eventos
    protected static function booted()
    {
        // Vacío intencionalmente
    }
}