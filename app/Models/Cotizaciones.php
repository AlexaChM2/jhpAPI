<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizaciones extends Model
{
    protected $table = 'cotizaciones';  // ← Coincide con la tabla
    protected $primaryKey = 'id_cotizacion';
    public $timestamps = true;  // ← Ahora sí tiene timestamps

    protected $fillable = [
        'id_cliente',
        'id_empleado',
        'cot_fecha',
        'cot_vigencia_dias',
        'cot_estado',
        'cot_total',
    ];

    protected $casts = [
        'cot_fecha' => 'datetime',
        'cot_total' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleados');
    }

    public function detalles()
    {
        return $this->hasMany(Detalle_cotizaciones::class, 'id_cotizacion', 'id_cotizacion');
    }
}