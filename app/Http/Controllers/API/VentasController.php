<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentasController extends Controller
{
    public function index()
    {
        $ventas = DB::table('Ventas')
            ->leftJoin('Clientes', 'Ventas.id_cliente', '=', 'Clientes.id_cliente')
            ->select('Ventas.*', 'Clientes.cli_nombre', 'Clientes.cli_apaterno')
            ->orderBy('Ventas.id_venta', 'desc')
            ->get();

        return response()->json($ventas, 200);
    }

    public function store(Request $request)
    {
        $detalles = $request->input('detalles', []);

        if (empty($detalles)) {
            return response()->json([
                'success' => false,
                'message' => 'Debe tener al menos un producto'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Insertar venta
            $idVenta = DB::table('Ventas')->insertGetId([
                'id_cliente'  => $request->id_cliente,
                'id_empleado' => $request->id_empleado ?? 1,
                'id_caja'     => $request->id_caja ?? 1,
                'ven_total'   => $request->ven_total,
                'tipo_pago'   => $request->tipo_pago ?? 'Efectivo',
                'ven_fecha'   => now()->format('Y-m-d H:i:s'),
            ]);

            // Insertar detalles
            foreach ($detalles as $item) {
                DB::table('Detalle_Ventas')->insert([
                    'id_venta'            => $idVenta,
                    'id_producto'         => $item['id_producto'],
                    'det_cantidad'        => $item['cantidad'] ?? 1,
                    'det_precio_unitario' => $item['precio'] ?? 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta registrada con éxito',
                'id_venta' => $idVenta
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
        $venta = DB::table('Ventas')->where('id_venta', $id)->first();
        
        if (!$venta) {
            return response()->json(['success' => false, 'message' => 'No encontrada'], 404);
        }

        $detalles = DB::table('Detalle_Ventas')
            ->leftJoin('Producto', 'Detalle_Ventas.id_producto', '=', 'Producto.id_producto')
            ->where('Detalle_Ventas.id_venta', $id)
            ->select('Detalle_Ventas.*', 'Producto.pro_nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'id_venta' => $venta->id_venta,
                'ven_fecha' => $venta->ven_fecha,
                'ven_total' => $venta->ven_total,
                'tipo_pago' => $venta->tipo_pago,
                'cliente' => ['cli_nombre' => $venta->cli_nombre],
                'detalles' => $detalles
            ]
        ], 200);
    }

    public function update(Request $request, $id)
    {
        DB::table('Ventas')->where('id_venta', $id)->update([
            'ven_total' => $request->ven_total,
            'tipo_pago' => $request->tipo_pago,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Venta actualizada'
        ], 200);
    }

    public function destroy($id)
    {
        DB::table('Detalle_Ventas')->where('id_venta', $id)->delete();
        DB::table('Ventas')->where('id_venta', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Venta eliminada'
        ], 200);
    }
}