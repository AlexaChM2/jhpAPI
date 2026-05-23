<?php

namespace App\Http\Controllers\API;

use App\Models\Detalle_mantenimiento_servicios;
use App\Models\Mantenimiento;
use App\Models\DetalleMantenimientoInsumo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class Detalle_mantenimiento_serviciosController extends Controller
{
    // LISTAR TODOS LOS SERVICIOS REALIZADOS EN MANTENIMIENTOS
    public function index()
    {
        $detalles = Detalle_mantenimiento_servicios::with([
            'servicio', 
            'mantenimiento.cliente'
        ])->get();
        
        return response()->json([
            'success' => true,
            'data' => $detalles
        ], 200);
    }

    // REGISTRAR UN SERVICIO DENTRO DE UN MANTENIMIENTO
    public function store(Request $request)
    {
        $request->validate([
            'id_mantenimiento' => 'required|exists:mantenimiento,id_mantenimiento',
            'id_servicio' => 'required|exists:servicios,id_servicio',
            'precio_aplicado' => 'required|numeric|min:0',
        ], [
            'id_mantenimiento.required' => 'El mantenimiento es obligatorio',
            'id_mantenimiento.exists' => 'El mantenimiento no existe',
            'id_servicio.required' => 'El servicio es obligatorio',
            'id_servicio.exists' => 'El servicio no existe',
            'precio_aplicado.required' => 'El precio es obligatorio',
            'precio_aplicado.numeric' => 'El precio debe ser un número',
            'precio_aplicado.min' => 'El precio no puede ser negativo',
        ]);

        try {
            DB::beginTransaction();

            // 1. Verificar que el mantenimiento existe y no esté finalizado
            $mantenimiento = Mantenimiento::find($request->id_mantenimiento);
            
            if (!$mantenimiento) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Mantenimiento no encontrado'
                ], 404);
            }

            // 2. Verificar que no se duplique el servicio en el mismo mantenimiento
            $existeServicio = Detalle_mantenimiento_servicios::where('id_mantenimiento', $request->id_mantenimiento)
                ->where('id_servicio', $request->id_servicio)
                ->exists();

            if ($existeServicio) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Este servicio ya está registrado en este mantenimiento'
                ], 422);
            }

            // 3. Crear detalle de servicio
            $detalle = Detalle_mantenimiento_servicios::create([
                'id_mantenimiento' => $request->id_mantenimiento,
                'id_servicio' => $request->id_servicio,
                'precio_aplicado' => $request->precio_aplicado,
            ]);

            // 4. Actualizar total del mantenimiento
            $this->actualizarTotalMantenimiento($request->id_mantenimiento);

            DB::commit();

            // Cargar relaciones
            $detalle->load(['servicio', 'mantenimiento']);

            return response()->json([
                'success' => true,
                'message' => 'Servicio registrado en el mantenimiento exitosamente',
                'data' => $detalle
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar servicio: ' . $e->getMessage()
            ], 500);
        }
    }

    // MOSTRAR UN DETALLE ESPECÍFICO
    public function show($id)
    {
        $detalle = Detalle_mantenimiento_servicios::with([
            'servicio', 
            'mantenimiento.cliente'
        ])->find($id);
        
        if (!$detalle) {
            return response()->json([
                'success' => false,
                'message' => 'Detalle de servicio no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $detalle
        ], 200);
    }

    // ACTUALIZAR PRECIO O CAMBIAR EL SERVICIO
    public function update(Request $request, $id)
    {
        $request->validate([
            'id_servicio' => 'nullable|exists:servicios,id_servicio',
            'precio_aplicado' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $detalle = Detalle_mantenimiento_servicios::find($id);
            
            if (!$detalle) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Detalle de servicio no encontrado'
                ], 404);
            }

            // Verificar duplicado si cambia el servicio
            if ($request->has('id_servicio') && $request->id_servicio != $detalle->id_servicio) {
                $existeServicio = Detalle_mantenimiento_servicios::where('id_mantenimiento', $detalle->id_mantenimiento)
                    ->where('id_servicio', $request->id_servicio)
                    ->where('id_det_mant_ser', '!=', $id)
                    ->exists();

                if ($existeServicio) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Este servicio ya está registrado en este mantenimiento'
                    ], 422);
                }
            }

            // Actualizar detalle
            $detalle->update($request->only(['id_servicio', 'precio_aplicado']));

            // Actualizar total del mantenimiento
            $this->actualizarTotalMantenimiento($detalle->id_mantenimiento);

            DB::commit();

            $detalle->load(['servicio', 'mantenimiento']);

            return response()->json([
                'success' => true,
                'message' => 'Detalle de servicio actualizado correctamente',
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

    // ELIMINAR EL SERVICIO DEL MANTENIMIENTO
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $detalle = Detalle_mantenimiento_servicios::find($id);
            
            if (!$detalle) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Detalle de servicio no encontrado'
                ], 404);
            }

            $id_mantenimiento = $detalle->id_mantenimiento;

            // Eliminar detalle
            $detalle->delete();

            // Actualizar total del mantenimiento
            $this->actualizarTotalMantenimiento($id_mantenimiento);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Servicio removido del mantenimiento y total actualizado'
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
     * Actualiza el total del mantenimiento sumando todos sus servicios e insumos
     */
    private function actualizarTotalMantenimiento($id_mantenimiento)
    {
        // Sumar total de servicios
        $total_servicios = Detalle_mantenimiento_servicios::where('id_mantenimiento', $id_mantenimiento)
            ->sum('precio_aplicado') ?? 0;

        // Sumar total de insumos
        $total_insumos = DetalleMantenimientoInsumo::where('id_mantenimiento', $id_mantenimiento)
            ->selectRaw('SUM(insumo_cantidad * insumo_precio_unitario) as total')
            ->value('total') ?? 0;

        // Actualizar total en mantenimiento
        Mantenimiento::where('id_mantenimiento', $id_mantenimiento)
            ->update(['mantenimiento_total' => $total_servicios + $total_insumos]);
    }
}