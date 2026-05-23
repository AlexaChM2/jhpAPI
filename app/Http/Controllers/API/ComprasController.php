<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Compras;
use App\Models\Detalle_compras;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComprasController extends Controller
{
    public function index()
    {
        return response()->json(
            Compras::with(['proveedor', 'empleado', 'detalles.producto'])
                ->orderBy('id_compra', 'desc')
                ->get(),
            200
        );
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Crear la compra
            $compra = Compras::create([
                'id_proveedor'   => $request->id_proveedor,
                'id_empleado'    => $request->id_empleado ?? 1,
                'com_fecha'      => now(),
                'com_total'      => $request->com_total ?? 0,
                'com_factura_no' => $request->com_factura_no,
            ]);

            // Crear detalles
            $detalles = $request->input('detalles', []);
            foreach ($detalles as $item) {
                Detalle_compras::create([
                    'id_compra'          => $compra->id_compra,
                    'id_producto'        => $item['id_producto'],
                    'det_cantidad'       => $item['det_cantidad'] ?? $item['cantidad'] ?? 1,
                    'det_costo_unitario' => $item['det_costo_unitario'] ?? $item['costo'] ?? 0,
                ]);

                // Actualizar stock del producto
                if (isset($item['id_producto'])) {
                    DB::table('producto')
                        ->where('id_producto', $item['id_producto'])
                        ->increment('pro_stock', $item['det_cantidad'] ?? $item['cantidad'] ?? 1);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compra registrada correctamente',
                'data' => $compra->load(['proveedor', 'detalles.producto'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $compra = Compras::with(['proveedor', 'empleado', 'detalles.producto'])->find($id);
        
        if (!$compra) {
            return response()->json([
                'success' => false,
                'message' => 'Compra no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $compra
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $compra = Compras::findOrFail($id);
        $compra->update($request->only(['com_total', 'com_factura_no', 'id_proveedor']));

        return response()->json([
            'success' => true,
            'message' => 'Compra actualizada',
            'data' => $compra
        ], 200);
    }

    public function destroy($id)
    {
        $compra = Compras::find($id);
        
        if (!$compra) {
            return response()->json(['success' => false, 'message' => 'No encontrada'], 404);
        }

        Detalle_compras::where('id_compra', $id)->delete();
        $compra->delete();

        return response()->json([
            'success' => true,
            'message' => 'Compra eliminada'
        ], 200);
    }
}