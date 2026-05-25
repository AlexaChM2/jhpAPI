<?php

namespace App\Http\Controllers\API;

use App\Models\Proveedor;
use App\Models\ProveedorVisita;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProveedorVisitasController extends Controller
{
    /**
     * Listar visitas de un proveedor
     */
    public function index($idProveedor)
    {
        $visitas = ProveedorVisita::where('id_proveedor', $idProveedor)
            ->orderBy('dia_semana')
            ->get()
            ->map(function ($v) {
                $v->dia_nombre = ProveedorVisita::$diasSemana[$v->dia_semana] ?? '?';
                return $v;
            });

        return response()->json([
            'success' => true,
            'data' => $visitas
        ], 200);
    }

    /**
     * Agregar un día de visita
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_proveedor' => 'required|exists:proveedores,id_proveedor',
            'dia_semana' => 'required|integer|between:0,6',
            'hora_visita' => 'required|date_format:H:i',
        ]);

        $visita = ProveedorVisita::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Día de visita agregado',
            'data' => $visita
        ], 201);
    }

    /**
     * Actualizar un día de visita
     */
    public function update(Request $request, $id)
    {
        $visita = ProveedorVisita::findOrFail($id);

        $request->validate([
            'dia_semana' => 'sometimes|integer|between:0,6',
            'hora_visita' => 'sometimes|date_format:H:i',
            'activo' => 'sometimes|boolean',
        ]);

        $visita->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Visita actualizada',
            'data' => $visita
        ], 200);
    }

    /**
     * Eliminar un día de visita
     */
    public function destroy($id)
    {
        ProveedorVisita::destroy($id);

        return response()->json([
            'success' => true,
            'message' => 'Día de visita eliminado'
        ], 200);
    }

    /**
     * Obtener la próxima visita de un proveedor
     */
    public function proximaVisita($idProveedor)
    {
        $visitas = ProveedorVisita::where('id_proveedor', $idProveedor)
            ->where('activo', 1)
            ->get();

        if ($visitas->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Sin visitas programadas'
            ]);
        }

        $hoy = now();
        $hoyDia = $hoy->dayOfWeek; // 0=Domingo
        $proximas = [];

        foreach ($visitas as $visita) {
            $diasHasta = ($visita->dia_semana - $hoyDia + 7) % 7;
            if ($diasHasta == 0) {
                // Mismo día: verificar si la hora ya pasó
                $horaVisita = explode(':', $visita->hora_visita);
                $fechaVisita = $hoy->copy()->setTime((int)$horaVisita[0], (int)$horaVisita[1], 0);
                if ($fechaVisita->lt($hoy)) {
                    $diasHasta = 7; // Ya pasó, próxima semana
                }
            }

            $fechaProxima = $hoy->copy()->addDays($diasHasta);
            $horaVisita = explode(':', $visita->hora_visita);
            $fechaProxima->setTime((int)$horaVisita[0], (int)$horaVisita[1], 0);

            $proximas[] = [
                'visita' => $visita,
                'fecha_proxima' => $fechaProxima->format('Y-m-d H:i:s'),
                'dia_nombre' => ProveedorVisita::$diasSemana[$visita->dia_semana],
                'hora' => $visita->hora_visita,
                'dias_restantes' => $diasHasta,
            ];
        }

        // Ordenar por fecha más cercana
        usort($proximas, function ($a, $b) {
            return strcmp($a['fecha_proxima'], $b['fecha_proxima']);
        });

        return response()->json([
            'success' => true,
            'data' => $proximas[0], // La más próxima
            'todas' => $proximas,
        ]);
    }
}