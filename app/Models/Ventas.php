<?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    class Ventas extends Model
    {
        protected $table = 'Ventas';  // ← Coincide con tu tabla
        protected $primaryKey = 'id_venta';
        public $timestamps = false;  // ← Si tu tabla no tiene created_at/updated_at

        protected $fillable = [
            'id_cliente',
            'id_empleado',
            'id_caja',
            'ven_fecha',
            'ven_total',
            'tipo_pago',
        ];

        public function cliente()
        {
            return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
        }

        public function empleado()
        {
            return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleados');
        }

        public function caja()
        {
            return $this->belongsTo(Control_caja::class, 'id_caja', 'id_caja');
        }

        public function detalles()
        {
            return $this->hasMany(Detalle_ventas::class, 'id_venta', 'id_venta');
        }
    }