<?php

namespace App\Http\Controllers\API;

use App\Models\DetalleMantenimientoInsumo;
use App\Models\Mantenimiento;
use App\Models\Producto;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class Detalle_mantenimiento_insumosController extends Controller
{
    // LISTAR TODOS LOS INSUMOS UTILIZADOS EN MANTENIMIENTOS
    public function index()
    {
        $detalles = DetalleMantenimientoInsumo::with([
            'producto.marca', 
            'mantenimiento.cliente'
        ])->get();
        
        return response()->json([
            'success' => true,
            'data' => $detalles
        ], 200);
    }

    // REGISTRAR EL USO DE UN INSUMO EN UN MANTENIMIENTO
    public function store(Request $request)
    {
        $request->validate([
            'id_mantenimiento' => 'required|exists:mantenimiento,id_mantenimiento',
            'id_producto' => 'required|exists:producto,id_producto',
            'insumo_cantidad' => 'required|integer|min:1',
            'insumo_precio_unitario' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // 1. Verificar que el mantenimiento existe
            $mantenimiento = Mantenimiento::find($request->id_mantenimiento);
            if (!$mantenimiento) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Mantenimiento no encontrado'
                ], 404);
            }

            // 2. Verificar stock disponible
            $producto = Producto::find($request->id_producto);
            
            if (!$producto) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            if ($producto->pro_stock < $request->insumo_cantidad) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Stock insuficiente para: {$producto->pro_nombre}",
                    'stock_disponible' => $producto->pro_stock,
                    'cantidad_solicitada' => $request->insumo_cantidad
                ], 422);
            }

            // 3. Crear detalle de insumo (descuenta stock automáticamente por el evento created del modelo)
            $detalle = DetalleMantenimientoInsumo::create([
                'id_mantenimiento' => $request->id_mantenimiento,
                'id_producto' => $request->id_producto,
                'insumo_cantidad' => $request->insumo_cantidad,
                'insumo_precio_unitario' => $request->insumo_precio_unitario,
            ]);

            // 4. Actualizar total del mantenimiento
            $this->actualizarTotalMantenimiento($request->id_mantenimiento);

            DB::commit();

            // Cargar relaciones
            $detalle->load(['producto.marca', 'mantenimiento']);

            return response()->json([
                'success' => true,
                'message' => 'Insumo registrado en el mantenimiento correctamente',
                'data' => $detalle
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar insumo: ' . $e->getMessage()
            ], 500);
        }
    }

    // MOSTRAR DETALLE ESPECÍFICO
    public function show($id)
    {
        $detalle = DetalleMantenimientoInsumo::with([
            'producto.marca', 
            'mantenimiento.cliente'
        ])->find($id);
        
        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => 'Detalle de insumo no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $detalle
        ], 200);
    }

    // ACTUALIZAR CANTIDAD O PRECIO DEL INSUMO
    public function update(Request $request, $id)
    {
        $request->validate([
            'insumo_cantidad' => 'nullable|integer|min:1',
            'insumo_precio_unitario' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $detalle = DetalleMantenimientoInsumo::find($id);
            
            if (!$detalle) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Detalle de insumo no encontrado'
                ], 404);
            }

            // Si cambia la cantidad, verificar stock
            if ($request->has('insumo_cantidad') && $request->insumo_cantidad != $detalle->insumo_cantidad) {
                $producto = Producto::find($detalle->id_producto);
                $diferencia = $request->insumo_cantidad - $detalle->insumo_cantidad;
                
                // Si aumenta la cantidad, verificar stock adicional
                if ($diferencia > 0) {
                    if ($producto->pro_stock < $diferencia) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Stock insuficiente para: {$producto->pro_nombre}",
                            'stock_disponible' => $producto->pro_stock,
                            'stock_adicional_necesario' => $diferencia
                        ], 422);
                    }
                }
            }

            // Actualizar detalle (ajusta stock automáticamente por el evento updated del modelo)
            $detalle->update($request->only(['insumo_cantidad', 'insumo_precio_unitario']));

            // Actualizar total del mantenimiento
            $this->actualizarTotalMantenimiento($detalle->id_mantenimiento);

            DB::commit();

            $detalle->load(['producto.marca', 'mantenimiento']);

            return response()->json([
                'success' => true,
                'message' => 'Registro de insumo actualizado correctamente',
                'data' => $detalle
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    // ELIMINAR EL INSUMO DEL REGISTRO DE MANTENIMIENTO
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $detalle = DetalleMantenimientoInsumo::find($id);
            
            if (!$detalle) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Detalle de insumo no encontrado'
                ], 404);
            }

            $id_mantenimiento = $detalle->id_mantenimiento;

            // Eliminar detalle (devuelve stock automáticamente por el evento deleted del modelo)
            $detalle->delete();

            // Actualizar total del mantenimiento
            $this->actualizarTotalMantenimiento($id_mantenimiento);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Insumo eliminado del mantenimiento y stock devuelto'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualiza el total del mantenimiento sumando todos sus insumos y servicios
     */
    private function actualizarTotalMantenimiento($id_mantenimiento)
    {
        // Sumar total de insumos
        $total_insumos = DetalleMantenimientoInsumo::where('id_mantenimiento', $id_mantenimiento)
            ->selectRaw('SUM(insumo_cantidad * insumo_precio_unitario) as total')
            ->value('total') ?? 0;

        // Sumar total de servicios
        $total_servicios = \App\Models\Detalle_mantenimiento_servicios::where('id_mantenimiento', $id_mantenimiento)
            ->sum('precio_aplicado') ?? 0;

        // Actualizar total en mantenimiento
        Mantenimiento::where('id_mantenimiento', $id_mantenimiento)
            ->update(['mantenimiento_total' => $total_insumos + $total_servicios]);
    }
}