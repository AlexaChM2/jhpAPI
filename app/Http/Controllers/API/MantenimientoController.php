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
    public function index()
    {
        $mantenimientos = Mantenimiento::with([
            'cliente', 'mecanico', 'cita',
            'insumos.producto', 'servicios.servicio'
        ])->orderBy('id_mantenimiento', 'DESC')->get();

        return response()->json([
            'success' => true,
            'data' => $mantenimientos
        ], 200);
    }

    public function show($id)
    {
        $mantenimiento = Mantenimiento::with([
            'cliente', 'mecanico', 'cita',
            'insumos.producto', 'servicios.servicio'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $mantenimiento
        ], 200);
    }

    /**
     * CREAR - Stock se descuenta AQUÍ (UNA SOLA VEZ)
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

            // 1. Validar stock
            if ($request->has('insumos') && count($request->insumos) > 0) {
                foreach ($request->insumos as $insumo) {
                    $producto = Producto::find($insumo['id_producto']);
                    if (!$producto) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Producto #{$insumo['id_producto']} no encontrado"
                        ], 404);
                    }
                    if ($producto->pro_stock < $insumo['insumo_cantidad']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Stock insuficiente para '{$producto->pro_nombre}'. Stock: {$producto->pro_stock}, Solicitado: {$insumo['insumo_cantidad']}"
                        ], 422);
                    }
                }
            }

            // 2. Calcular totales
            $total_insumos = 0;
            if ($request->has('insumos')) {
                foreach ($request->insumos as $i) {
                    $total_insumos += $i['insumo_cantidad'] * $i['insumo_precio_unitario'];
                }
            }

            $total_servicios = 0;
            if ($request->has('servicios')) {
                foreach ($request->servicios as $s) {
                    $total_servicios += $s['precio_aplicado'];
                }
            }

            // 3. Crear mantenimiento
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

            // 4. Registrar insumos y DESCONTAR stock (UNA VEZ)
            if ($request->has('insumos') && count($request->insumos) > 0) {
                foreach ($request->insumos as $insumo) {
                    DetalleMantenimientoInsumo::create([
                        'id_mantenimiento' => $mantenimiento->id_mantenimiento,
                        'id_producto' => $insumo['id_producto'],
                        'insumo_cantidad' => $insumo['insumo_cantidad'],
                        'insumo_precio_unitario' => $insumo['insumo_precio_unitario'],
                    ]);

                    // DESCONTAR STOCK (única vez)
                    Producto::where('id_producto', $insumo['id_producto'])
                        ->decrement('pro_stock', $insumo['insumo_cantidad']);
                }
            }

            // 5. Registrar servicios
            if ($request->has('servicios') && count($request->servicios) > 0) {
                foreach ($request->servicios as $servicio) {
                    Detalle_mantenimiento_servicios::create([
                        'id_mantenimiento' => $mantenimiento->id_mantenimiento,
                        'id_servicio' => $servicio['id_servicio'],
                        'precio_aplicado' => $servicio['precio_aplicado'],
                    ]);
                }
            }

            // 6. Actualizar cita
            if ($request->filled('id_cita')) {
                Citas::where('id_cita', $request->id_cita)
                    ->update(['cita_estado' => 'Realizada']);
            }

            DB::commit();

            $mantenimiento->load(['cliente', 'mecanico', 'cita', 'insumos.producto', 'servicios.servicio']);

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento creado. Stock actualizado.',
                'data' => $mantenimiento
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('STORE Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ACTUALIZAR - Repone stock viejo, descuenta stock nuevo
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'id_mecanico' => 'nullable|exists:empleados,id_empleados',
            'moto_modelo' => 'nullable|string|max:100',
            'moto_llegada_descripcion' => 'nullable|string',
            'trabajo_realizado' => 'nullable|string',
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

            // Procesar insumos (si vienen en el request)
            if ($request->has('insumos')) {
                // 1. REPONER stock de insumos viejos
                $insumosViejos = DetalleMantenimientoInsumo::where('id_mantenimiento', $id)->get();
                foreach ($insumosViejos as $viejo) {
                    Producto::where('id_producto', $viejo->id_producto)
                        ->increment('pro_stock', $viejo->insumo_cantidad);
                }

                // 2. Eliminar insumos viejos
                DetalleMantenimientoInsumo::where('id_mantenimiento', $id)->delete();

                // 3. Validar stock para nuevos
                foreach ($request->insumos as $insumo) {
                    $producto = Producto::find($insumo['id_producto']);
                    if ($producto && $producto->pro_stock < $insumo['insumo_cantidad']) {
                        // Revertir reposición
                        foreach ($insumosViejos as $viejo) {
                            Producto::where('id_producto', $viejo->id_producto)
                                ->decrement('pro_stock', $viejo->insumo_cantidad);
                        }
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Stock insuficiente para '{$producto->pro_nombre}'. Stock: {$producto->pro_stock}"
                        ], 422);
                    }
                }

                // 4. Crear nuevos y descontar
                foreach ($request->insumos as $insumo) {
                    DetalleMantenimientoInsumo::create([
                        'id_mantenimiento' => $id,
                        'id_producto' => $insumo['id_producto'],
                        'insumo_cantidad' => $insumo['insumo_cantidad'],
                        'insumo_precio_unitario' => $insumo['insumo_precio_unitario'],
                    ]);

                    Producto::where('id_producto', $insumo['id_producto'])
                        ->decrement('pro_stock', $insumo['insumo_cantidad']);
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
                'message' => 'Mantenimiento actualizado. Stock ajustado.',
                'data' => $mantenimiento
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UPDATE Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ELIMINAR - Repone stock
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Reponer stock
            $insumos = DetalleMantenimientoInsumo::where('id_mantenimiento', $id)->get();
            foreach ($insumos as $insumo) {
                Producto::where('id_producto', $insumo->id_producto)
                    ->increment('pro_stock', $insumo->insumo_cantidad);
            }

            DetalleMantenimientoInsumo::where('id_mantenimiento', $id)->delete();
            Detalle_mantenimiento_servicios::where('id_mantenimiento', $id)->delete();
            Mantenimiento::destroy($id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento eliminado. Stock devuelto.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}