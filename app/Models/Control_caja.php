<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Control_caja extends Model
{
    // Cambiar a la tabla SIN S (la que tiene los datos reales)
    protected $table = 'control_caja';  // ← Importante: sin S
    
    protected $primaryKey = 'id_caja';
    
    // La tabla control_caja NO tiene created_at/updated_at
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