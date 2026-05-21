<?php

namespace App\Http\Controllers\API;

use App\Models\Detalle_cotizaciones;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class DetalleCotizacionController extends Controller
{
    /**
     * Listar todos los detalles de cotizaciones.
     */
    public function index()
    {
        $detalles = Detalle_cotizaciones::with(['cotizacion', 'producto'])->get();

        return response()->json([
            'success' => true,
            'data' => $detalles
        ], 200);
    }

    /**
     * Crear un detalle de cotización.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_cotizacion' => 'required|exists:cotizaciones,id_cotizacion',
            'id_producto' => 'required|exists:Producto,id_producto',
            'det_cantidad' => 'required|integer|min:1',
            'det_precio_unitario' => 'required|numeric|min:0',
        ], [
            'id_cotizacion.required' => 'La cotización es obligatoria',
            'id_producto.required' => 'El producto es obligatorio',
            'det_cantidad.min' => 'La cantidad debe ser mayor a 0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $detalle = Detalle_cotizaciones::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Detalle agregado exitosamente',
            'data' => $detalle->load(['cotizacion', 'producto'])
        ], 201);
    }

    /**
     * Mostrar un detalle específico.
     */
    public function show($id)
    {
        $detalle = Detalle_cotizaciones::with(['cotizacion', 'producto'])->find($id);

        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => 'Detalle no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $detalle
        ], 200);
    }

    /**
     * Actualizar un detalle.
     */
    public function update(Request $request, $id)
    {
        $detalle = Detalle_cotizaciones::find($id);

        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => 'Detalle no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'det_cantidad' => 'sometimes|integer|min:1',
            'det_precio_unitario' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $detalle->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Detalle actualizado correctamente',
            'data' => $detalle->fresh(['cotizacion', 'producto'])
        ], 200);
    }

    /**
     * Eliminar un detalle.
     */
    public function destroy($id)
    {
        $detalle = Detalle_cotizaciones::find($id);

        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => 'Detalle no encontrado'
            ], 404);
        }

        $detalle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Detalle eliminado correctamente'
        ], 200);
    }
}