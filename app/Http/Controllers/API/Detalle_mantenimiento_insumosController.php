<?php

namespace App\Http\Controllers\API;

use App\Models\DetalleMantenimientoInsumo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class Detalle_mantenimiento_insumosController extends Controller
{
    // LISTAR TODOS LOS INSUMOS UTILIZADOS EN MANTENIMIENTOS
    public function index()
    {
        $detalles = DetalleMantenimientoInsumo::with(['producto', 'mantenimiento'])->get();
        return response()->json($detalles, 200);
    }

    // REGISTRAR EL USO DE UN INSUMO EN UN MANTENIMIENTO
    public function store(Request $request)
    {
        $detalle = DetalleMantenimientoInsumo::create($request->all());

        return response()->json([
            'message' => 'Insumo registrado en el mantenimiento',
            'data' => $detalle
        ], 201);
    }

    // MOSTRAR DETALLE ESPECÍFICO
    public function show($id)
    {
        $detalle = DetalleMantenimientoInsumo::with(['producto', 'mantenimiento'])->findOrFail($id);
        return response()->json($detalle, 200);
    }

    // ACTUALIZAR CANTIDAD O PRECIO DEL INSUMO
    public function update(Request $request, $id)
    {
        $detalle = DetalleMantenimientoInsumo::findOrFail($id);
        $detalle->update($request->all());

        return response()->json([
            'message' => 'Registro de insumo actualizado',
            'data' => $detalle
        ], 200);
    }

    // ELIMINAR EL INSUMO DEL REGISTRO DE MANTENIMIENTO
    public function destroy($id)
    {
        DetalleMantenimientoInsumo::destroy($id);

        return response()->json([
            'message' => 'Insumo eliminado del registro'
        ], 200);
    }
}