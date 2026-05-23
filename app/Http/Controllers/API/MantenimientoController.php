<?php

namespace App\Http\Controllers\API;

use App\Models\Mantenimiento;
use App\Models\DetalleMantenimientoInsumo;
use App\Models\Detalle_mantenimiento_servicios;
use App\Models\Citas;
use App\Models\Producto;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class MantenimientoController extends Controller
{
    // LISTAR TODOS LOS MANTENIMIENTOS
    public function index()
    {
        $mantenimientos = Mantenimiento::with([
            'cliente', 
            'mecanico', 
            'cita', 
            'insumos.producto',
            'servicios'
        ])->get();
        
        return response()->json($mantenimientos, 200);
    }

    // CREAR MANTENIMIENTO CON INSUMOS Y SERVICIOS
    public function store(Request $request)
    {
        $request->validate([
            'id_cliente' => 'required|exists:clientes,id_cliente',
            'id_mecanico' => 'nullable|exists:empleados,id_empleados',
            'id_cita' => 'nullable|exists:citas,id_cita',
            'moto_modelo' => 'nullable|string|max:100',
            'moto_llegada_descripcion' => 'nullable|string',
            'trabajo_realizado' => 'nullable|string',
            'fecha_inicio' => 'nullable|date',
            'fecha_termino' => 'nullable|date',
            'estado_servicio' => 'nullable|in:En Proceso,Terminado,Entregado',
            
            // Arrays de insumos y servicios
            'insumos' => 'nullable|array',
            'insumos.*.id_producto' => 'required|exists:producto,id_producto',
            'insumos.*.insumo_cantidad' => 'required|integer|min:1',
            'insumos.*.insumo_precio_unitario' => 'required|numeric|min:0',
            
            'servicios' => 'nullable|array',
            'servicios.*.id_servicio' => 'required|exists:servicios,id_servicio',
            'servicios.*.precio_aplicado' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // 1. Validar stock disponible para todos los insumos
            if ($request->has('insumos')) {
                foreach ($request->insumos as $insumo) {
                    $producto = Producto::find($insumo['id_producto']);
                    if (!$producto || $producto->pro_stock < $insumo['insumo_cantidad']) {
                        return response()->json([
                            'message' => "Stock insuficiente para: {$producto->pro_nombre}. Disponible: {$producto->pro_stock}"
                        ], 422);
                    }
                }
            }

            // 2. Calcular total del mantenimiento
            $total_insumos = 0;
            $total_servicios = 0;

            if ($request->has('insumos')) {
                foreach ($request->insumos as $insumo) {
                    $total_insumos += $insumo['insumo_cantidad'] * $insumo['insumo_precio_unitario'];
                }
            }

            if ($request->has('servicios')) {
                foreach ($request->servicios as $servicio) {
                    $total_servicios += $servicio['precio_aplicado'];
                }
            }

            // 3. Crear el mantenimiento
            $mantenimiento = Mantenimiento::create([
                'id_cliente' => $request->id_cliente,
                'id_mecanico' => $request->id_mecanico,
                'id_cita' => $request->id_cita,
                'moto_modelo' => $request->moto_modelo,
                'moto_llegada_descripcion' => $request->moto_llegada_descripcion,
                'trabajo_realizado' => $request->trabajo_realizado,
                'fecha_inicio' => $request->fecha_inicio ?? now(),
                'fecha_termino' => $request->fecha_termino,
                'mantenimiento_total' => $total_insumos + $total_servicios,
                'estado_servicio' => $request->estado_servicio ?? 'En Proceso',
            ]);

            // 4. Registrar insumos (el descuento de stock se hace automático en el modelo)
            if ($request->has('insumos')) {
                foreach ($request->insumos as $insumo) {
                    DetalleMantenimientoInsumo::create([
                        'id_mantenimiento' => $mantenimiento->id_mantenimiento,
                        'id_producto' => $insumo['id_producto'],
                        'insumo_cantidad' => $insumo['insumo_cantidad'],
                        'insumo_precio_unitario' => $insumo['insumo_precio_unitario'],
                    ]);
                }
            }

            // 5. Registrar servicios realizados
            if ($request->has('servicios')) {
                foreach ($request->servicios as $servicio) {
                    Detalle_mantenimiento_servicios::create([
                        'id_mantenimiento' => $mantenimiento->id_mantenimiento,
                        'id_servicio' => $servicio['id_servicio'],
                        'precio_aplicado' => $servicio['precio_aplicado'],
                    ]);
                }
            }

            // 6. Actualizar estado de la cita si existe
            if ($request->has('id_cita') && $request->id_cita != null) {
                $cita = Citas::find($request->id_cita);
                if ($cita) {
                    $cita->update(['cita_estado' => 'Realizada']);
                }
            }

            DB::commit();

            // Cargar relaciones para la respuesta
            $mantenimiento->load(['cliente', 'mecanico', 'cita', 'insumos.producto', 'servicios']);

            return response()->json([
                'message' => 'Orden de mantenimiento creada exitosamente',
                'data' => $mantenimiento
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // MOSTRAR DETALLES DE UN MANTENIMIENTO ESPECÍFICO
    public function show($id)
    {
        $mantenimiento = Mantenimiento::with([
            'cliente', 
            'mecanico', 
            'cita', 
            'insumos.producto',
            'servicios'
        ])->findOrFail($id);
        
        return response()->json($mantenimiento, 200);
    }

    // ACTUALIZAR MANTENIMIENTO
    public function update(Request $request, $id)
    {
        $request->validate([
            'id_mecanico' => 'nullable|exists:empleados,id_empleados',
            'moto_modelo' => 'nullable|string|max:100',
            'moto_llegada_descripcion' => 'nullable|string',
            'trabajo_realizado' => 'nullable|string',
            'fecha_inicio' => 'nullable|date',
            'fecha_termino' => 'nullable|date',
            'estado_servicio' => 'nullable|in:En Proceso,Terminado,Entregado',
            
            // Nuevos insumos y servicios (opcional)
            'insumos' => 'nullable|array',
            'insumos.*.id_producto' => 'required|exists:producto,id_producto',
            'insumos.*.insumo_cantidad' => 'required|integer|min:1',
            'insumos.*.insumo_precio_unitario' => 'required|numeric|min:0',
            
            'servicios' => 'nullable|array',
            'servicios.*.id_servicio' => 'required|exists:servicios,id_servicio',
            'servicios.*.precio_aplicado' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $mantenimiento = Mantenimiento::findOrFail($id);
            
            // Actualizar datos básicos
            $mantenimiento->update($request->only([
                'id_mecanico', 'moto_modelo', 'moto_llegada_descripcion',
                'trabajo_realizado', 'fecha_inicio', 'fecha_termino', 'estado_servicio'
            ]));

            // Si vienen nuevos insumos, eliminar los anteriores y crear nuevos
            if ($request->has('insumos')) {
                // Eliminar insumos anteriores (el modelo devolverá el stock automáticamente)
                DetalleMantenimientoInsumo::where('id_mantenimiento', $id)->delete();
                
                // Validar stock antes de insertar
                foreach ($request->insumos as $insumo) {
                    $producto = Producto::find($insumo['id_producto']);
                    if (!$producto || $producto->pro_stock < $insumo['insumo_cantidad']) {
                        throw new \Exception("Stock insuficiente para: {$producto->pro_nombre}");
                    }
                }

                // Crear nuevos insumos
                foreach ($request->insumos as $insumo) {
                    DetalleMantenimientoInsumo::create([
                        'id_mantenimiento' => $id,
                        'id_producto' => $insumo['id_producto'],
                        'insumo_cantidad' => $insumo['insumo_cantidad'],
                        'insumo_precio_unitario' => $insumo['insumo_precio_unitario'],
                    ]);
                }
            }

            // Si vienen nuevos servicios, eliminar los anteriores y crear nuevos
            if ($request->has('servicios')) {
                Detalle_mantenimiento_servicios::where('id_mantenimiento', $id)->delete();
                
                foreach ($request->servicios as $servicio) {
                    Detalle_mantenimiento_servicios::create([
                        'id_mantenimiento' => $id,
                        'id_servicio' => $servicio['id_servicio'],
                        'precio_aplicado' => $servicio['precio_aplicado'],
                    ]);
                }
            }

            // Recalcular total
            $total_insumos = DetalleMantenimientoInsumo::where('id_mantenimiento', $id)
                ->selectRaw('SUM(insumo_cantidad * insumo_precio_unitario) as total')
                ->value('total') ?? 0;
                
            $total_servicios = Detalle_mantenimiento_servicios::where('id_mantenimiento', $id)
                ->sum('precio_aplicado') ?? 0;

            $mantenimiento->update(['mantenimiento_total' => $total_insumos + $total_servicios]);

            DB::commit();

            $mantenimiento->load(['cliente', 'mecanico', 'cita', 'insumos.producto', 'servicios']);

            return response()->json([
                'message' => 'Orden de mantenimiento actualizada',
                'data' => $mantenimiento
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ELIMINAR MANTENIMIENTO (devuelve stock automáticamente)
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $mantenimiento = Mantenimiento::findOrFail($id);
            
            // Eliminar insumos (devuelve stock automáticamente por el evento deleted del modelo)
            DetalleMantenimientoInsumo::where('id_mantenimiento', $id)->delete();
            
            // Eliminar servicios
            Detalle_mantenimiento_servicios::where('id_mantenimiento', $id)->delete();
            
            // Eliminar mantenimiento
            $mantenimiento->delete();
            
            DB::commit();

            return response()->json([
                'message' => 'Mantenimiento eliminado y stock devuelto'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}