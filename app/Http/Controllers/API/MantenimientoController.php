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
    /**
     * LISTAR TODOS LOS MANTENIMIENTOS
     */
    public function index()
    {
        $mantenimientos = Mantenimiento::with([
            'cliente', 
            'mecanico', 
            'cita', 
            'insumos.producto',
            'servicios.servicio'
        ])->orderBy('id_mantenimiento', 'DESC')->get();
        
        return response()->json([
            'success' => true,
            'data' => $mantenimientos
        ], 200);
    }

    /**
     * CREAR MANTENIMIENTO - SOLO EL BACKEND DESCUENTA STOCK
     */
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
            if ($request->has('insumos') && count($request->insumos) > 0) {
                foreach ($request->insumos as $insumo) {
                    $producto = Producto::find($insumo['id_producto']);
                    if (!$producto) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Producto no encontrado: ID {$insumo['id_producto']}"
                        ], 422);
                    }
                    
                    if ($producto->pro_stock < $insumo['insumo_cantidad']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Stock insuficiente para '{$producto->pro_nombre}'. Disponible: {$producto->pro_stock}, Solicitado: {$insumo['insumo_cantidad']}"
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

            // 4. Registrar insumos y DESCONTAR stock (SOLO AQUÍ)
            if ($request->has('insumos') && count($request->insumos) > 0) {
                foreach ($request->insumos as $insumo) {
                    // Crear detalle
                    DetalleMantenimientoInsumo::create([
                        'id_mantenimiento' => $mantenimiento->id_mantenimiento,
                        'id_producto' => $insumo['id_producto'],
                        'insumo_cantidad' => $insumo['insumo_cantidad'],
                        'insumo_precio_unitario' => $insumo['insumo_precio_unitario'],
                    ]);
                    
                    // 🔥 DESCONTAR STOCK (SOLO UNA VEZ)
                    $producto = Producto::find($insumo['id_producto']);
                    $stockAnterior = $producto->pro_stock;
                    $producto->pro_stock = $stockAnterior - $insumo['insumo_cantidad'];
                    $producto->save();
                    
                    Log::info("📦 STORE: Stock descontado - Producto #{$insumo['id_producto']} ({$producto->pro_nombre}): {$stockAnterior} → {$producto->pro_stock} (-{$insumo['insumo_cantidad']})");
                }
            }

            // 5. Registrar servicios realizados
            if ($request->has('servicios') && count($request->servicios) > 0) {
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
                'message' => 'Orden de mantenimiento creada exitosamente. Stock actualizado.',
                'data' => $mantenimiento
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error en STORE mantenimiento: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * MOSTRAR DETALLES DE UN MANTENIMIENTO
     */
    public function show($id)
    {
        $mantenimiento = Mantenimiento::with([
            'cliente', 
            'mecanico', 
            'cita', 
            'insumos.producto',
            'servicios.servicio'
        ])->find($id);
        
        if (!$mantenimiento) {
            return response()->json([
                'success' => false,
                'message' => 'Mantenimiento no encontrado'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $mantenimiento
        ], 200);
    }

    /**
     * ACTUALIZAR MANTENIMIENTO - MANEJO CORRECTO DE STOCK
     */
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

            $mantenimiento = Mantenimiento::find($id);
            if (!$mantenimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mantenimiento no encontrado'
                ], 404);
            }
            
            // Actualizar datos básicos
            $mantenimiento->update($request->only([
                'id_mecanico', 'moto_modelo', 'moto_llegada_descripcion',
                'trabajo_realizado', 'fecha_inicio', 'fecha_termino', 'estado_servicio'
            ]));

            // 🔥 PROCESAR INSUMOS CON CONTROL MANUAL DE STOCK
            if ($request->has('insumos')) {
                // 1️⃣ Obtener insumos ACTUALES (antes de modificar)
                $insumosAnteriores = DetalleMantenimientoInsumo::where('id_mantenimiento', $id)->get();
                
                // 2️⃣ REPONER stock de TODOS los insumos anteriores
                foreach ($insumosAnteriores as $insumoAnterior) {
                    $producto = Producto::find($insumoAnterior->id_producto);
                    if ($producto) {
                        $stockAnterior = $producto->pro_stock;
                        $producto->pro_stock = $stockAnterior + $insumoAnterior->insumo_cantidad;
                        $producto->save();
                        
                        Log::info("🔄 UPDATE: Stock REPUESTO - Producto #{$insumoAnterior->id_producto} ({$producto->pro_nombre}): {$stockAnterior} → {$producto->pro_stock} (+{$insumoAnterior->insumo_cantidad})");
                    }
                }
                
                // 3️⃣ ELIMINAR todos los insumos anteriores
                DetalleMantenimientoInsumo::where('id_mantenimiento', $id)->delete();
                
                // 4️⃣ Validar stock para los NUEVOS insumos
                foreach ($request->insumos as $insumo) {
                    $producto = Producto::find($insumo['id_producto']);
                    if (!$producto) {
                        // Revertir reposición si hay error
                        foreach ($insumosAnteriores as $insumoAnterior) {
                            $prod = Producto::find($insumoAnterior->id_producto);
                            if ($prod) {
                                $prod->pro_stock -= $insumoAnterior->insumo_cantidad;
                                $prod->save();
                            }
                        }
                        
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Producto no encontrado: ID {$insumo['id_producto']}"
                        ], 422);
                    }
                    
                    if ($producto->pro_stock < $insumo['insumo_cantidad']) {
                        // Revertir reposición
                        foreach ($insumosAnteriores as $insumoAnterior) {
                            $prod = Producto::find($insumoAnterior->id_producto);
                            if ($prod) {
                                $prod->pro_stock -= $insumoAnterior->insumo_cantidad;
                                $prod->save();
                            }
                        }
                        
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Stock insuficiente para '{$producto->pro_nombre}'. Disponible: {$producto->pro_stock}, Solicitado: {$insumo['insumo_cantidad']}"
                        ], 422);
                    }
                }

                // 5️⃣ CREAR nuevos insumos y DESCONTAR stock
                foreach ($request->insumos as $insumo) {
                    DetalleMantenimientoInsumo::create([
                        'id_mantenimiento' => $id,
                        'id_producto' => $insumo['id_producto'],
                        'insumo_cantidad' => $insumo['insumo_cantidad'],
                        'insumo_precio_unitario' => $insumo['insumo_precio_unitario'],
                    ]);
                    
                    // DESCONTAR stock
                    $producto = Producto::find($insumo['id_producto']);
                    $stockAnterior = $producto->pro_stock;
                    $producto->pro_stock = $stockAnterior - $insumo['insumo_cantidad'];
                    $producto->save();
                    
                    Log::info("📦 UPDATE: Stock DESCONTADO - Producto #{$insumo['id_producto']} ({$producto->pro_nombre}): {$stockAnterior} → {$producto->pro_stock} (-{$insumo['insumo_cantidad']})");
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
                ->selectRaw('COALESCE(SUM(insumo_cantidad * insumo_precio_unitario), 0) as total')
                ->value('total') ?? 0;
                
            $total_servicios = Detalle_mantenimiento_servicios::where('id_mantenimiento', $id)
                ->sum('precio_aplicado') ?? 0;

            $mantenimiento->update(['mantenimiento_total' => $total_insumos + $total_servicios]);

            DB::commit();

            $mantenimiento->load(['cliente', 'mecanico', 'cita', 'insumos.producto', 'servicios.servicio']);

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento actualizado. Stock ajustado correctamente.',
                'data' => $mantenimiento
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error en UPDATE mantenimiento: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ELIMINAR MANTENIMIENTO - REPONE STOCK
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $mantenimiento = Mantenimiento::find($id);
            if (!$mantenimiento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mantenimiento no encontrado'
                ], 404);
            }
            
            // REPONER stock de todos los insumos
            $insumos = DetalleMantenimientoInsumo::where('id_mantenimiento', $id)->get();
            
            foreach ($insumos as $insumo) {
                $producto = Producto::find($insumo->id_producto);
                if ($producto) {
                    $stockAnterior = $producto->pro_stock;
                    $producto->pro_stock = $stockAnterior + $insumo->insumo_cantidad;
                    $producto->save();
                    
                    Log::info("🔄 DESTROY: Stock REPUESTO - Producto #{$insumo->id_producto} ({$producto->pro_nombre}): {$stockAnterior} → {$producto->pro_stock} (+{$insumo->insumo_cantidad})");
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
            Log::error('❌ Error en DESTROY mantenimiento: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el mantenimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}