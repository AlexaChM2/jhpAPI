<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ventas;
use App\Models\Detalle_ventas;
use App\Models\Producto;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentasController extends Controller
{
    // LISTAR VENTAS
    public function index()
    {
        $ventas = Ventas::with(['cliente', 'detalles.producto'])
            ->orderBy('id_venta', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $ventas
        ], 200);
    }

    // CREAR VENTA
    public function store(Request $request)
    {
        $request->validate([
            'id_cliente' => 'required|exists:clientes,id_cliente',
            'id_empleado' => 'nullable|exists:empleados,id_empleados',
            'id_caja' => 'nullable|exists:control_caja,id_caja',
            'ven_total' => 'required|numeric|min:0',
            'tipo_pago' => 'required|in:Efectivo,Tarjeta,Transferencia',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_producto' => 'required|exists:producto,id_producto',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.precio' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // 1. Validar stock disponible para cada producto
            foreach ($request->detalles as $item) {
                $producto = Producto::find($item['id_producto']);
                
                if (!$producto) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Producto ID {$item['id_producto']} no encontrado"
                    ], 404);
                }

                if ($producto->pro_stock < $item['cantidad']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuficiente para: {$producto->pro_nombre}. Disponible: {$producto->pro_stock}, Solicitado: {$item['cantidad']}"
                    ], 422);
                }
            }

            // 2. Crear la venta
            $venta = Ventas::create([
                'id_cliente' => $request->id_cliente,
                'id_empleado' => $request->id_empleado ?? 1,
                'id_caja' => $request->id_caja ?? 1,
                'ven_total' => $request->ven_total,
                'tipo_pago' => $request->tipo_pago ?? 'Efectivo',
                'ven_fecha' => now(),
            ]);

            // 3. Crear detalles (el descuento de stock es automático por el modelo)
            foreach ($request->detalles as $item) {
                Detalle_ventas::create([
                    'id_venta' => $venta->id_venta,
                    'id_producto' => $item['id_producto'],
                    'det_cantidad' => $item['cantidad'],
                    'det_precio_unitario' => $item['precio'],
                ]);
            }

            DB::commit();

            // Cargar relaciones para respuesta
            $venta->load(['cliente', 'detalles.producto']);

            return response()->json([
                'success' => true,
                'message' => 'Venta registrada exitosamente',
                'data' => $venta
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la venta: ' . $e->getMessage()
            ], 500);
        }
    }

    // MOSTRAR VENTA
    public function show($id)
    {
        $venta = Ventas::with([
            'cliente', 
            'empleado', 
            'detalles.producto'
        ])->find($id);

        if (!$venta) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $venta
        ], 200);
    }

    // ACTUALIZAR VENTA
    public function update(Request $request, $id)
    {
        $request->validate([
            'ven_total' => 'nullable|numeric|min:0',
            'tipo_pago' => 'nullable|in:Efectivo,Tarjeta,Transferencia',
            'detalles' => 'nullable|array',
            'detalles.*.id_producto' => 'required|exists:producto,id_producto',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.precio' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $venta = Ventas::find($id);
            if (!$venta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venta no encontrada'
                ], 404);
            }

            // Actualizar datos básicos de la venta
            $venta->update($request->only(['ven_total', 'tipo_pago']));

            // Si se envían nuevos detalles, reemplazar los existentes
            if ($request->has('detalles')) {
                // Validar stock de los nuevos productos
                foreach ($request->detalles as $item) {
                    $producto = Producto::find($item['id_producto']);
                    if (!$producto) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Producto ID {$item['id_producto']} no encontrado"
                        ], 404);
                    }
                    if ($producto->pro_stock < $item['cantidad']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Stock insuficiente para: {$producto->pro_nombre}"
                        ], 422);
                    }
                }

                // Eliminar detalles anteriores (devuelve stock automáticamente)
                Detalle_ventas::where('id_venta', $id)->delete();

                // Crear nuevos detalles (descuenta stock automáticamente)
                foreach ($request->detalles as $item) {
                    Detalle_ventas::create([
                        'id_venta' => $id,
                        'id_producto' => $item['id_producto'],
                        'det_cantidad' => $item['cantidad'],
                        'det_precio_unitario' => $item['precio'],
                    ]);
                }
            }

            DB::commit();

            $venta->load(['cliente', 'detalles.producto']);

            return response()->json([
                'success' => true,
                'message' => 'Venta actualizada exitosamente',
                'data' => $venta
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la venta: ' . $e->getMessage()
            ], 500);
        }
    }

    // ELIMINAR VENTA
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $venta = Ventas::find($id);
            if (!$venta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venta no encontrada'
                ], 404);
            }

            // Eliminar detalles (devuelve stock automáticamente por el evento deleted)
            Detalle_ventas::where('id_venta', $id)->delete();

            // Eliminar venta
            $venta->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta eliminada y stock devuelto exitosamente'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la venta: ' . $e->getMessage()
            ], 500);
        }
    }
}