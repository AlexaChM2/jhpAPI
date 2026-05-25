<?php

namespace App\Http\Controllers\API;

use App\Models\Mantenimiento;
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

            // Validar stock
            if ($request->has('insumos')) {
                foreach ($request->insumos as $insumo) {
                    $producto = DB::table('producto')->where('id_producto', $insumo['id_producto'])->first();
                    if (!$producto) {
                        DB::rollBack();
                        return response()->json(['success' => false, 'message' => "Producto no encontrado"], 404);
                    }
                    if ($producto->pro_stock < $insumo['insumo_cantidad']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Stock insuficiente para '{$producto->pro_nombre}'. Stock: {$producto->pro_stock}"
                        ], 422);
                    }
                }
            }

            // Calcular totales
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

            // Crear mantenimiento
            $idMant = DB::table('mantenimiento')->insertGetId([
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

            // Registrar insumos y DESCONTAR stock
            if ($request->has('insumos')) {
                foreach ($request->insumos as $insumo) {
                    // Insertar detalle
                    DB::table('detalle_mantenimiento_insumos')->insert([
                        'id_mantenimiento' => $idMant,
                        'id_producto' => $insumo['id_producto'],
                        'insumo_cantidad' => $insumo['insumo_cantidad'],
                        'insumo_precio_unitario' => $insumo['insumo_precio_unitario'],
                    ]);

                    // Descontar stock - UNA SOLA VEZ
                    DB::table('producto')
                        ->where('id_producto', $insumo['id_producto'])
                        ->decrement('pro_stock', $insumo['insumo_cantidad']);
                }
            }

            // Registrar servicios
            if ($request->has('servicios')) {
                foreach ($request->servicios as $servicio) {
                    DB::table('detalle_mantenimiento_servicios')->insert([
                        'id_mantenimiento' => $idMant,
                        'id_servicio' => $servicio['id_servicio'],
                        'precio_aplicado' => $servicio['precio_aplicado'],
                    ]);
                }
            }

            // Actualizar cita
            if ($request->filled('id_cita')) {
                DB::table('citas')->where('id_cita', $request->id_cita)
                    ->update(['cita_estado' => 'Realizada']);
            }

            DB::commit();

            $mantenimiento = Mantenimiento::with(['cliente', 'mecanico', 'cita', 'insumos.producto', 'servicios.servicio'])
                ->find($idMant);

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento creado.',
                'data' => $mantenimiento
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('STORE Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

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

            // Actualizar datos básicos
            DB::table('mantenimiento')->where('id_mantenimiento', $id)->update($request->only([
                'id_mecanico', 'moto_modelo', 'moto_llegada_descripcion',
                'trabajo_realizado', 'fecha_inicio', 'fecha_termino', 'estado_servicio'
            ]));

            if ($request->has('insumos')) {
                // REPONER stock viejo
                $insumosViejos = DB::table('detalle_mantenimiento_insumos')
                    ->where('id_mantenimiento', $id)->get();
                
                foreach ($insumosViejos as $viejo) {
                    DB::table('producto')
                        ->where('id_producto', $viejo->id_producto)
                        ->increment('pro_stock', $viejo->insumo_cantidad);
                }

                // Eliminar insumos viejos
                DB::table('detalle_mantenimiento_insumos')
                    ->where('id_mantenimiento', $id)->delete();

                // Validar stock para nuevos
                foreach ($request->insumos as $insumo) {
                    $producto = DB::table('producto')->where('id_producto', $insumo['id_producto'])->first();
                    if ($producto && $producto->pro_stock < $insumo['insumo_cantidad']) {
                        // Revertir
                        foreach ($insumosViejos as $viejo) {
                            DB::table('producto')->where('id_producto', $viejo->id_producto)
                                ->decrement('pro_stock', $viejo->insumo_cantidad);
                        }
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Stock insuficiente para '{$producto->pro_nombre}'"
                        ], 422);
                    }
                }

                // Crear nuevos y descontar
                foreach ($request->insumos as $insumo) {
                    DB::table('detalle_mantenimiento_insumos')->insert([
                        'id_mantenimiento' => $id,
                        'id_producto' => $insumo['id_producto'],
                        'insumo_cantidad' => $insumo['insumo_cantidad'],
                        'insumo_precio_unitario' => $insumo['insumo_precio_unitario'],
                    ]);

                    DB::table('producto')
                        ->where('id_producto', $insumo['id_producto'])
                        ->decrement('pro_stock', $insumo['insumo_cantidad']);
                }
            }

            if ($request->has('servicios')) {
                DB::table('detalle_mantenimiento_servicios')->where('id_mantenimiento', $id)->delete();
                foreach ($request->servicios as $servicio) {
                    DB::table('detalle_mantenimiento_servicios')->insert([
                        'id_mantenimiento' => $id,
                        'id_servicio' => $servicio['id_servicio'],
                        'precio_aplicado' => $servicio['precio_aplicado'],
                    ]);
                }
            }

            // Recalcular total
            $total_insumos = DB::table('detalle_mantenimiento_insumos')
                ->where('id_mantenimiento', $id)
                ->selectRaw('COALESCE(SUM(insumo_cantidad * insumo_precio_unitario), 0) as total')
                ->value('total') ?? 0;

            $total_servicios = DB::table('detalle_mantenimiento_servicios')
                ->where('id_mantenimiento', $id)
                ->sum('precio_aplicado') ?? 0;

            DB::table('mantenimiento')->where('id_mantenimiento', $id)
                ->update(['mantenimiento_total' => $total_insumos + $total_servicios]);

            DB::commit();

            $mantenimiento = Mantenimiento::with(['cliente', 'mecanico', 'cita', 'insumos.producto', 'servicios.servicio'])
                ->find($id);

            return response()->json([
                'success' => true,
                'message' => 'Mantenimiento actualizado.',
                'data' => $mantenimiento
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UPDATE Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $insumos = DB::table('detalle_mantenimiento_insumos')->where('id_mantenimiento', $id)->get();
            foreach ($insumos as $insumo) {
                DB::table('producto')->where('id_producto', $insumo->id_producto)
                    ->increment('pro_stock', $insumo->insumo_cantidad);
            }

            DB::table('detalle_mantenimiento_insumos')->where('id_mantenimiento', $id)->delete();
            DB::table('detalle_mantenimiento_servicios')->where('id_mantenimiento', $id)->delete();
            DB::table('mantenimiento')->where('id_mantenimiento', $id)->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Eliminado.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}