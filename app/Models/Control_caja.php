<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Control_caja extends Model
{
    protected $table = 'control_caja';  // ← Sin S
    
    protected $primaryKey = 'id_caja';
    
    public $timestamps = false;  // ← La tabla no tiene created_at/updated_at
    
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