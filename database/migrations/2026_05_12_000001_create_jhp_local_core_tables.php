<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Categorias', function (Blueprint $table) {
            $table->increments('id_categoria');
            $table->string('cat_nombre');
            $table->text('cat_descripcion')->nullable();
        });

        Schema::create('Clientes', function (Blueprint $table) {
            $table->increments('id_cliente');
            $table->string('cli_nombre');
            $table->string('cli_apaterno')->nullable();
            $table->string('cli_amaterno')->nullable();
            $table->string('cli_telefono')->nullable();
            $table->string('cli_correo')->nullable();
            $table->dateTime('cli_fecha_registro')->nullable();
        });

        Schema::create('Empleados', function (Blueprint $table) {
            $table->increments('id_empleados');
            $table->string('emp_nombre');
            $table->string('emp_apaterno')->nullable();
            $table->string('emp_amaterno')->nullable();
            $table->string('emp_telefono')->nullable();
            $table->string('emp_direccion')->nullable();
            $table->string('emp_rol')->nullable();
            $table->string('emp_usuario')->nullable();
            $table->string('emp_password')->nullable();
            $table->string('emp_estado')->default('Activo');
        });

        Schema::create('Proveedores', function (Blueprint $table) {
            $table->increments('id_proveedor');
            $table->string('prov_nombre');
            $table->string('prov_contacto')->nullable();
            $table->string('prov_telefono')->nullable();
            $table->string('prov_email')->nullable();
            $table->string('prov_direccion')->nullable();
        });

        Schema::create('Producto', function (Blueprint $table) {
            $table->increments('id_producto');
            $table->string('pro_codigo')->unique();
            $table->string('pro_nombre');
            $table->string('pro_tipo')->nullable();
            $table->string('id_marca')->nullable();
            $table->string('pro_marca')->nullable();
            $table->text('pro_descripcion')->nullable();
            $table->decimal('pro_precio_venta', 10, 2)->default(0);
            $table->integer('pro_stock')->default(0);
            $table->unsignedInteger('id_categoria')->nullable();
            $table->unsignedInteger('id_proveedor')->nullable();
        });

        Schema::create('Servicios', function (Blueprint $table) {
            $table->increments('id_servicio');
            $table->string('ser_nombre');
            $table->text('ser_descripcion')->nullable();
            $table->decimal('ser_precio_mano_obra', 10, 2)->default(0);
            $table->unsignedInteger('id_categoria')->nullable();
        });

        Schema::create('Citas', function (Blueprint $table) {
            $table->increments('id_cita');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_empleado')->nullable();
            $table->dateTime('cita_fecha_programada')->nullable();
            $table->string('cita_motivo')->nullable();
            $table->string('cita_estado')->default('Pendiente');
            $table->text('cita_notas')->nullable();
        });

        Schema::create('Control_Caja', function (Blueprint $table) {
            $table->increments('id_caja');
            $table->unsignedInteger('id_empleado')->nullable();
            $table->dateTime('fecha_apertura')->nullable();
            $table->decimal('monto_inicial', 10, 2)->default(0);
            $table->dateTime('fecha_cierre')->nullable();
            $table->decimal('monto_final_esperado', 10, 2)->nullable();
            $table->decimal('monto_real_cierre', 10, 2)->nullable();
            $table->string('estado')->default('Cerrada');
        });

        Schema::create('Compras', function (Blueprint $table) {
            $table->increments('id_compra');
            $table->unsignedInteger('id_proveedor')->nullable();
            $table->unsignedInteger('id_empleado')->nullable();
            $table->dateTime('com_fecha')->nullable();
            $table->decimal('com_total', 10, 2)->default(0);
            $table->string('com_factura_no')->nullable();
        });

        Schema::create('Detalle_Compras', function (Blueprint $table) {
            $table->increments('id_det_compra');
            $table->unsignedInteger('id_compra')->nullable();
            $table->unsignedInteger('id_producto')->nullable();
            $table->integer('det_cantidad')->default(0);
            $table->decimal('det_costo_unitario', 10, 2)->default(0);
        });

        Schema::create('Ventas', function (Blueprint $table) {
            $table->increments('id_venta');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_empleado')->nullable();
            $table->unsignedInteger('id_caja')->nullable();
            $table->dateTime('ven_fecha')->nullable();
            $table->decimal('ven_total', 10, 2)->default(0);
            $table->string('tipo_pago')->nullable();
        });

        Schema::create('Detalle_Ventas', function (Blueprint $table) {
            $table->increments('id_detalle');
            $table->unsignedInteger('id_venta')->nullable();
            $table->unsignedInteger('id_producto')->nullable();
            $table->integer('det_cantidad')->default(0);
            $table->decimal('det_precio_unitario', 10, 2)->default(0);
        });

        Schema::create('Cotizaciones', function (Blueprint $table) {
            $table->increments('id_cotizacion');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_empleado')->nullable();
            $table->dateTime('cot_fecha')->nullable();
            $table->integer('cot_vigencia_dias')->default(15);
            $table->decimal('cot_total', 10, 2)->default(0);
        });

        Schema::create('Detalle_Cotizaciones', function (Blueprint $table) {
            $table->increments('id_det_cotizacion');
            $table->unsignedInteger('id_cotizacion')->nullable();
            $table->unsignedInteger('id_producto')->nullable();
            $table->unsignedInteger('id_servicio')->nullable();
            $table->integer('det_cantidad')->default(1);
            $table->decimal('det_precio_unitario', 10, 2)->default(0);
        });

        Schema::create('Mantenimiento', function (Blueprint $table) {
            $table->increments('id_mantenimiento');
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_mecanico')->nullable();
            $table->unsignedInteger('id_cita')->nullable();
            $table->string('moto_modelo')->nullable();
            $table->text('moto_llegada_descripcion')->nullable();
            $table->text('trabajo_realizado')->nullable();
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_termino')->nullable();
            $table->decimal('mantenimiento_total', 10, 2)->default(0);
            $table->string('estado_servicio')->default('Pendiente');
        });

        Schema::create('Detalle_Cita_Servicios', function (Blueprint $table) {
            $table->increments('id_det_cita');
            $table->unsignedInteger('id_cita')->nullable();
            $table->unsignedInteger('id_servicio')->nullable();
        });

        Schema::create('Detalle_Mantenimiento_Servicios', function (Blueprint $table) {
            $table->increments('id_det_mant_ser');
            $table->unsignedInteger('id_mantenimiento')->nullable();
            $table->unsignedInteger('id_servicio')->nullable();
            $table->decimal('precio_aplicado', 10, 2)->default(0);
        });

        Schema::create('Detalle_Mantenimiento_Insumos', function (Blueprint $table) {
            $table->increments('id_det_mant');
            $table->unsignedInteger('id_mantenimiento')->nullable();
            $table->unsignedInteger('id_producto')->nullable();
            $table->integer('insumo_cantidad')->default(0);
            $table->decimal('precio_unitario', 10, 2)->default(0);
        });

        DB::table('Categorias')->insert([
            ['cat_nombre' => 'Motor', 'cat_descripcion' => 'Refacciones y servicios de motor'],
            ['cat_nombre' => 'Frenos', 'cat_descripcion' => 'Balatas, discos y liquido de frenos'],
            ['cat_nombre' => 'Mantenimiento General', 'cat_descripcion' => 'Servicios preventivos y correctivos'],
        ]);

        DB::table('Clientes')->insert([
            ['cli_nombre' => 'Cliente', 'cli_apaterno' => 'Demo', 'cli_amaterno' => '', 'cli_telefono' => '5551234567', 'cli_correo' => 'cliente@jhp.local', 'cli_fecha_registro' => now()],
        ]);

        DB::table('Empleados')->insert([
            ['emp_nombre' => 'Administrador', 'emp_apaterno' => 'Demo', 'emp_amaterno' => '', 'emp_telefono' => '5557654321', 'emp_direccion' => 'Sucursal principal', 'emp_rol' => 'Administrador', 'emp_usuario' => 'admin', 'emp_estado' => 'Activo'],
            ['emp_nombre' => 'Mecanico', 'emp_apaterno' => 'Demo', 'emp_amaterno' => '', 'emp_telefono' => '5550001111', 'emp_direccion' => 'Taller', 'emp_rol' => 'Mecanico', 'emp_usuario' => 'mecanico', 'emp_estado' => 'Activo'],
        ]);

        DB::table('Proveedores')->insert([
            ['prov_nombre' => 'Proveedor Demo', 'prov_contacto' => 'Contacto Demo', 'prov_telefono' => '5551112233', 'prov_email' => 'proveedor@jhp.local', 'prov_direccion' => 'Sucursal principal'],
        ]);

        DB::table('Producto')->insert([
            ['pro_codigo' => 'PROD-001', 'pro_nombre' => 'Balatas delanteras', 'pro_tipo' => 'Refaccion', 'pro_marca' => 'Brembo', 'pro_descripcion' => 'Balatas para moto', 'pro_precio_venta' => 250, 'pro_stock' => 20, 'id_categoria' => 2, 'id_proveedor' => 1],
            ['pro_codigo' => 'PROD-002', 'pro_nombre' => 'Aceite sintetico 10W40', 'pro_tipo' => 'Insumo', 'pro_marca' => 'Motul', 'pro_descripcion' => 'Aceite para motor 4T', 'pro_precio_venta' => 180, 'pro_stock' => 12, 'id_categoria' => 1, 'id_proveedor' => 1],
        ]);

        DB::table('Servicios')->insert([
            ['ser_nombre' => 'Cambio de aceite', 'ser_descripcion' => 'Cambio de aceite y revision general', 'ser_precio_mano_obra' => 180, 'id_categoria' => 3],
            ['ser_nombre' => 'Ajuste de frenos', 'ser_descripcion' => 'Revision y ajuste de frenos', 'ser_precio_mano_obra' => 120, 'id_categoria' => 2],
        ]);
    }

    public function down(): void
    {
        foreach ([
            'Detalle_Mantenimiento_Insumos',
            'Detalle_Mantenimiento_Servicios',
            'Detalle_Cita_Servicios',
            'Mantenimiento',
            'Detalle_Cotizaciones',
            'Cotizaciones',
            'Detalle_Ventas',
            'Ventas',
            'Detalle_Compras',
            'Compras',
            'Control_Caja',
            'Citas',
            'Servicios',
            'Producto',
            'Proveedores',
            'Empleados',
            'Clientes',
            'Categorias',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
