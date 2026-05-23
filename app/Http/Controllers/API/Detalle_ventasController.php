<?php

namespace App\Http\Controllers\API;

use App\Models\Detalle_ventas;
use App\Models\Ventas;
use App\Models\Producto;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class Detalle_ventasController extends Controller
{
    // LISTAR TODOS LOS DETALLES DE VENTAS
    public function index()
    {
        $detalles = Detalle_ventas::with(['producto.marca', 'venta.cliente'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $detalles
        ], 200);
    }

    // AGREGAR UN PRODUCTO A UNA VENTA
    public function store(Request $request)
    {
        $request->validate([
            'id_venta' => 'required|exists:ventas,id_venta',
            'id_producto' => 'required|exists:producto,id_producto',
            'det_cantidad' => 'required|integer|min:1',
            'det_precio_unitario' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // 1. Verificar stock disponible
            $producto = Producto::find($request->id_producto);
            
            if (!$producto) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            if ($producto->pro_stock < $request->det_cantidad) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Stock insuficiente para: {$producto->pro_nombre}",
                    'stock_disponible' => $producto->pro_stock,
                    'cantidad_solicitada' => $request->det_cantidad
                ], 422);
            }

            // 2. Verificar que la venta existe
            $venta = Ventas::find($request->id_venta);
            if (!$venta) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Venta no encontrada'
                ], 404);
            }

            // 3. Crear detalle (descuenta stock automáticamente por el evento created del modelo)
            $detalle = Detalle_ventas::create([
                'id_venta' => $request->id_venta,
                'id_producto' => $request->id_producto,
                'det_cantidad' => $request->det_cantidad,
                'det_precio_unitario' => $request->det_precio_unitario,
            ]);

            // 4. Actualizar total de la venta
            $this->actualizarTotalVenta($request->id_venta);

            DB::commit();

            // Cargar relaciones
            $detalle->load(['producto', 'venta']);

            return response()->json([
                'success' => true,
                'message' => 'Producto agregado a la venta correctamente',
                'data' => $detalle
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar producto: ' . $e->getMessage()
            ], 500);
        }
    }

    // MOSTRAR UN DETALLE ESPECÍFICO
    public function show($id)
    {
        $detalle = Detalle_ventas::with(['producto.marca', 'venta.cliente'])->find($id);
        
        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => 'Detalle de venta no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $detalle
        ], 200);
    }

    // ACTUALIZAR CANTIDAD O PRECIO DE UN ÍTEM VENDIDO
    public function update(Request $request, $id)
    {
        $request->validate([
            'det_cantidad' => 'nullable|integer|min:1',
            'det_precio_unitario' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $detalle = Detalle_ventas::find($id);
            
            if (!$detalle) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Detalle de venta no encontrado'
                ], 404);
            }

            // Si cambia la cantidad, verificar stock
            if ($request->has('det_cantidad') && $request->det_cantidad != $detalle->det_cantidad) {
                $producto = Producto::find($detalle->id_producto);
                $diferencia = $request->det_cantidad - $detalle->det_cantidad;
                
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
            $detalle->update($request->only(['det_cantidad', 'det_precio_unitario']));

            // Actualizar total de la venta
            $this->actualizarTotalVenta($detalle->id_venta);

            DB::commit();

            $detalle->load(['producto', 'venta']);

            return response()->json([
                'success' => true,
                'message' => 'Línea de venta actualizada',
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

    // ELIMINAR UN PRODUCTO DE LA VENTA
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $detalle = Detalle_ventas::find($id);
            
            if (!$detalle) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Detalle de venta no encontrado'
                ], 404);
            }

            $id_venta = $detalle->id_venta;

            // Eliminar detalle (devuelve stock automáticamente por el evento deleted del modelo)
            $detalle->delete();

            // Actualizar total de la venta
            $this->actualizarTotalVenta($id_venta);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado de la venta y stock devuelto'
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
     * Actualiza el total de la venta sumando todos sus detalles
     */
    private function actualizarTotalVenta($id_venta)
    {
        $total = Detalle_ventas::where('id_venta', $id_venta)
            ->selectRaw('SUM(det_cantidad * det_precio_unitario) as total')
            ->value('total') ?? 0;

        Ventas::where('id_venta', $id_venta)->update(['ven_total' => $total]);
    }
}