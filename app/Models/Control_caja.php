<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Control_caja extends Model
{
    // Usar el nombre REAL de la tabla (sin S)
    protected $table = 'control_caja';  // ← Importante: sin S
    
    protected $primaryKey = 'id_caja';
    
    // Esta tabla NO tiene created_at/updated_at
    public $timestamps = false;
    
    protected $fillable = [
        'id_empleado',
        'fecha_apertura',
        'monto_inicial',
        'fecha_cierre',
        'monto_final_esperado',
        'monto_real_cierre',
        'estado',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleados');
    }
}