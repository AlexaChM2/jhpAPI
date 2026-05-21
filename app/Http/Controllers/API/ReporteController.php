<?php

namespace App\Http\Controllers\API; // Coincide con tu carpeta API

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReporteController extends Controller
{
    public function datosGraficas(Request $request)
    {
        try {
            $periodo = $request->query('periodo', 'semana');
            $fechaInicio = ($periodo === 'semana') ? Carbon::now()->startOfWeek() : Carbon::now()->startOfMonth();

            // vents
            $ventas = DB::table('mantenimiento')
                ->select(
                    DB::raw('DATE(fecha_inicio) as fecha'), 
                    DB::raw('SUM(mantenimiento_total) as total')
                )
                ->where('estado_servicio', '=', 'Terminado')
                ->where('fecha_inicio', '>=', $fechaInicio)
                ->groupBy('fecha')
                ->orderBy('fecha', 'asc')
                ->get();

            // 2. Top 5 Productos
            $productos = DB::table('detalle_mantenimiento_insumos')
                ->join('Producto', 'detalle_mantenimiento_insumos.id_producto', '=', 'Producto.id_producto')
                ->select(
                    'Producto.pro_nombre as nombre', 
                    DB::raw('SUM(detalle_mantenimiento_insumos.insumo_cantidad) as total_vendido')
                )
                ->groupBy('Producto.pro_nombre')
                ->orderBy('total_vendido', 'desc')
                ->limit(5)
                ->get();

            // 3. Totales 
            $manoObra = DB::table('detalle_mantenimiento_servicios')
                ->join('Mantenimiento', 'detalle_mantenimiento_mervicios.id_mantenimiento', '=', 'Mantenimiento.id_mantenimiento')
                ->where('Mantenimiento.estado_servicio', '=', 'Terminado')
                ->where('Mantenimiento.fecha_inicio', '>=', $fechaInicio)
                ->sum('detalle_mantenimiento_servicios.precio_aplicado') ?: 0;

            $ingresoTotal = DB::table('mantenimiento')
                ->where('estado_servicio', '=', 'Terminado')
                ->where('fecha_inicio', '>=', $fechaInicio)
                ->sum('mantenimiento_total') ?: 0;

            return response()->json([
                'ventas' => $ventas,
                'productos' => $productos,
                'totales' => [
                    'ingreso_total' => (float)$ingresoTotal,
                    'mano_obra' => (float)$manoObra,
                    'refacciones' => (float)($ingresoTotal - $manoObra)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Fallo en la consulta de reportes',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }
}