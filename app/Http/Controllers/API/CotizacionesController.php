<?php

namespace App\Http\Controllers\API;

use App\Models\Cotizaciones;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CotizacionesController extends Controller
{
    /**
     * LISTAR COTIZACIONES
     */
    public function index()
    {
        $cotizaciones = Cotizaciones::with(['cliente', 'detalles.producto', 'empleado'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Agregar estado calculado
        $cotizaciones->each(function ($cotizacion) {
            $fecha = $cotizacion->cot_fecha 
                ? \Carbon\Carbon::parse($cotizacion->cot_fecha) 
                : now();
            $vence = $fecha->copy()->addDays((int)($cotizacion->cot_vigencia_dias ?? 15));
            $cotizacion->cot_estado = now()->lte($vence) ? 'Vigente' : 'Vencida';
            $cotizacion->fecha_vencimiento = $vence->format('Y-m-d');
        });

        return response()->json([
            'success' => true,
            'data' => $cotizaciones
        ], 200);
    }

    /**
     * CREAR COTIZACIÓN (NO afecta inventario)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_cliente' => 'required|exists:Clientes,id_cliente',
            'cot_vigencia_dias' => 'nullable|integer|min:1|max:90',
            'cot_total' => 'required|numeric|min:0',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_producto' => 'required|exists:Producto,id_producto',
            'detalles.*.det_cantidad' => 'required|integer|min:1',
            'detalles.*.det_precio_unitario' => 'required|numeric|min:0',
        ], [
            'id_cliente.required' => 'El cliente es obligatorio',
            'id_cliente.exists' => 'El cliente no existe',
            'detalles.required' => 'Debe tener al menos un producto',
            'detalles.*.det_cantidad.min' => 'La cantidad debe ser mayor a 0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cotizacion = DB::transaction(function () use ($request) {
                // Crear cotización
                $cotizacion = Cotizaciones::create([
                    'id_cliente'        => $request->id_cliente,
                    'id_empleado'       => $request->id_empleado ?? 1,
                    'cot_fecha'         => now(),
                    'cot_vigencia_dias' => $request->cot_vigencia_dias ?? 15,
                    'cot_total'         => $request->cot_total,
                ]);

                // Crear detalles (NO descuenta inventario)
                foreach ($request->detalles as $item) {
                    $cotizacion->detalles()->create([
                        'id_producto'         => $item['id_producto'],
                        'det_cantidad'        => $item['det_cantidad'],
                        'det_precio_unitario' => $item['det_precio_unitario'],
                    ]);
                }

                return $cotizacion;
            });

            return response()->json([
                'success' => true,
                'message' => 'Cotización creada exitosamente',
                'data' => $cotizacion->load(['cliente', 'detalles.producto'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear cotización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * MOSTRAR UNA COTIZACIÓN
     */
    public function show($id)
    {
        $cotizacion = Cotizaciones::with(['cliente', 'detalles.producto', 'empleado'])->find($id);
        
        if (!$cotizacion) {
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $cotizacion
        ], 200);
    }

    /**
     * ACTUALIZAR COTIZACIÓN
     */
    public function update(Request $request, $id)
    {
        $cotizacion = Cotizaciones::find($id);
        
        if (!$cotizacion) {
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_cliente' => 'required|exists:Clientes,id_cliente',
            'cot_vigencia_dias' => 'nullable|integer|min:1|max:90',
            'cot_total' => 'required|numeric|min:0',
            'detalles' => 'required|array|min:1',
            'detalles.*.id_producto' => 'required|exists:Producto,id_producto',
            'detalles.*.det_cantidad' => 'required|integer|min:1',
            'detalles.*.det_precio_unitario' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $cotizacion) {
                // Actualizar cotización
                $cotizacion->update([
                    'id_cliente'        => $request->id_cliente,
                    'cot_vigencia_dias' => $request->cot_vigencia_dias ?? 15,
                    'cot_total'         => $request->cot_total,
                ]);

                // Eliminar detalles anteriores y crear nuevos (NO afecta inventario)
                $cotizacion->detalles()->delete();

                foreach ($request->detalles as $item) {
                    $cotizacion->detalles()->create([
                        'id_producto'         => $item['id_producto'],
                        'det_cantidad'        => $item['det_cantidad'],
                        'det_precio_unitario' => $item['det_precio_unitario'],
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Cotización actualizada correctamente',
                'data' => $cotizacion->fresh(['cliente', 'detalles.producto'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar cotización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ELIMINAR COTIZACIÓN
     */
    public function destroy($id)
    {
        $cotizacion = Cotizaciones::find($id);
        
        if (!$cotizacion) {
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada'
            ], 404);
        }

        try {
            DB::transaction(function () use ($cotizacion) {
                // Eliminar detalles primero
                $cotizacion->detalles()->delete();
                // Eliminar cotización
                $cotizacion->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Cotización eliminada correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar cotización',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}