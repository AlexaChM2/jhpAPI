<?php

namespace App\Http\Controllers\API;

use App\Models\Mantenimiento;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MantenimientoController extends Controller
{
    // LISTAR TODOS LOS MANTENIMIENTOS
    public function index()
    {
        // Cargamos todas las relaciones para ver quién es el cliente, el mecánico y qué insumos se usaron
        $mantenimientos = Mantenimiento::with(['cliente', 'mecanico', 'cita', 'insumos'])->get();
        return response()->json($mantenimientos, 200);
    }

  
public function store(Request $request)
{
    // 1. Crear el Mantenimiento
    $mantenimiento = Mantenimiento::create($request->all());

    // 2. Si viene vinculado a una cita, actualizar el estado de la cita
    if ($request->has('id_cita') && $request->id_cita != null) {
        $cita = \App\Models\Citas::find($request->id_cita);
        if ($cita) {
            $cita->update(['cita_estado' => 'Realizada']);
        }
    }

    return response()->json([
        'message' => 'Orden de mantenimiento creada y cita actualizada',
        'data' => $mantenimiento
    ], 201);
}
    // MOSTRAR DETALLES DE UN MANTENIMIENTO ESPECÍFICO
    public function show($id)
    {
        $mantenimiento = Mantenimiento::with(['cliente', 'mecanico', 'cita', 'insumos.producto'])->findOrFail($id);
        return response()->json($mantenimiento, 200);
    }

    // ACTUALIZAR EL PROGRESO O FINALIZAR EL MANTENIMIENTO
    public function update(Request $request, $id)
    {
        $mantenimiento = Mantenimiento::findOrFail($id);
        $mantenimiento->update($request->all());

        return response()->json([
            'message' => 'Orden de mantenimiento actualizada',
            'data' => $mantenimiento
        ], 200);
    }

    // ELIMINAR REGISTRO DE MANTENIMIENTO
    public function destroy($id)
    {
        Mantenimiento::destroy($id);

        return response()->json([
            'message' => 'Registro de mantenimiento eliminado'
        ], 200);
    }
}