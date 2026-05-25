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
use Illuminate\Support\Facades\Log;

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
            'servicios.servicio'
        ])->get();
        
        return response()->json([
            'success' => true,
            'data' => $mantenimientos
        ], 200);
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
                            'success' => false,
                            'message' => "Stock insuficiente para: {$producto->pro_nombre}. Disponible: {$producto->pro_stock}, Solicitado: {$insumo['insumo_cantidad']}"
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

            // 4. Registrar insumos y DESCONTAR stock
            if ($request->has('insumos')) {
                foreach ($request->insumos as $insumo) {
                    // Crear detalle
                    DetalleMantenimientoInsumo::create([
                        'id_mantenimiento' => $mantenimiento->id_mantenimiento,
                        'id_producto' => $insumo['id_producto'],
                        'insumo_cantidad' => $insumo['insumo_cantidad'],
                        'insumo_precio_unitario' => $insumo['insumo_precio_unitario'],
                    ]);
                    
                    // DESCONTAR STOCK MANUALMENTE
                    $producto = Producto::find($insumo['id_producto']);
                    $producto->decrement('pro_stock', $insumo['insumo_cantidad']);
                    
                    Log::info("Stock descontado: Producto #{$insumo['id_producto']} -{$insumo['insumo_cantidad']} unidades. Nuevo stock: " . ($producto->pro_stock - $insumo['insumo_cantidad']));
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
            $mantenimiento->load(['cliente', 'mecanico', 'cita', 'insumos.producto', 'servicios.servicio']);

            return response()->json([
                'success' => true,
                'message' => 'Orden de mantenimiento creada exitosamente',
                'data' => $mantenimiento
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear mantenimiento: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
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
            'servicios.servicio'
        ])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $mantenimiento
        ], 200);
    }

    // ACTUALIZAR MANTENIMIENTO (CORREGIDO)
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

            // 🔥 PROCESAR INSUMOS CON MANEJO CORRECTO DE STOCK
            if ($request->has('insumos')) {
                // Obtener insumos actuales (antes de modificar)
                $insumosActuales = DetalleMantenimientoInsumo::where('id_mantenimiento', $id)->get();
                
                // 1. REPONER stock de TODOS los insumos actuales
                foreach ($insumosActuales as $insumoActual) {
                    $producto = Producto::find($insumoActual->id_producto);
                    if ($producto) {
                        $producto->increment('pro_stock', $insumoActual->insumo_cantidad);
                        Log::info("Stock repuesto: Producto #{$insumoActual->id_producto} +{$insumoActual->insumo_cantidad} unidades");
                    }
                }
                
                // 2. ELIMINAR todos los insumos anteriores
                DetalleMantenimientoInsumo::where('id_mantenimiento', $id)->delete();
                
                // 3. Validar stock para los NUEVOS insumos
                foreach ($request->insumos as $insumo) {
                    $producto = Producto::find($insumo['id_producto']);
                    if (!$producto || $producto->pro_stock < $insumo['insumo_cantidad']) {
                        // Si no hay stock suficiente, REPONER lo que ya se repuso y cancelar
                        foreach ($insumosActuales as $insumoActual) {
                            $prod = Producto::find($insumoActual->id_producto);
                            if ($prod) {
                                $prod->decrement('pro_stock', $insumoActual->insumo_cantidad);
                            }
                        }
                        
                        throw new \Exception("Stock insuficiente para: {$producto->pro_nombre}. Disponible: {$producto->pro_stock}, Solicitado: {$insumo['insumo_cantidad']}");
                    }
                }

                // 4. CREAR nuevos insumos y DESCONTAR stock
                foreach ($request->insumos as $insumo) {
                    DetalleMantenimientoInsumo::create([
                        'id_mantenimiento' => $id,
                        'id_producto' => $insumo['id_producto'],
                        'insumo_cantidad' => $insumo['insumo_cantidad'],
                        'insumo_precio_unitario' => $insumo['insumo_precio_unitario'],
                    ]);
                    
                    // DESCONTAR stock del nuevo insumo
                    $producto = Producto::find($insumo['id_producto']);
                    $producto->decrement('pro_stock', $insumo['insumo_cantidad']);
                    
                    Log::info("Stock descontado en update: Producto #{$insumo['id_producto']} -{$insumo['insumo_cantidad']} unidades");
                }
            }

            // Procesar servicios
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

            $mantenimiento->load(['cliente', 'mecanico', 'cita', 'insumos.producto', 'servicios.servicio']);

            return response()->json([
                'success' => true,
                'message' => 'Orden de mantenimiento actualizada correctamente',
                'data' => $mantenimiento
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar mantenimiento: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ELIMINAR MANTENIMIENTO (devuelve stock)
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $mantenimiento = Mantenimiento::findOrFail($id);
            
            // REPONER stock de todos los insumos antes de eliminar
            $insumos = DetalleMantenimientoInsumo::where('id_mantenimiento', $id)->get();
            
            foreach ($insumos as $insumo) {
                $producto = Producto::find($insumo->id_producto);
                if ($producto) {
                    $producto->increment('pro_stock', $insumo->insumo_cantidad);
                    Log::info("Stock repuesto por eliminación: Producto #{$insumo->id_producto} +{$insumo->insumo_cantidad} unidades");
                }
            }
            
            // Eliminar insumos
            DetalleMantenimientoInsumo::where('id_mantenimiento', $id)->delete();
            
            // Eliminar servicios
            Detalle_mantenimiento_servicios::where('id_mantenimiento', $id)->delete();
            
            // Eliminar mantenimiento
            $mantenimiento->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento eliminado y stock devuelto correctamente'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar mantenimiento: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}