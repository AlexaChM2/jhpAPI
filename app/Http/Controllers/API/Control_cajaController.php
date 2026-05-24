<?php

namespace App\Http\Controllers\API;

use App\Models\Control_caja;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Exception;

class Control_cajaController extends Controller
{
    private function asegurarTablasCaja(): void
    {
        if (!Schema::hasTable('control_cajas')) {
            Schema::create('control_cajas', function (Blueprint $table) {
                $table->increments('id_caja');
                $table->unsignedInteger('id_empleado')->nullable();
                $table->dateTime('fecha_apertura')->nullable();
                $table->decimal('monto_inicial', 10, 2);
                $table->dateTime('fecha_cierre')->nullable();
                $table->decimal('monto_final_esperado', 10, 2)->nullable();
                $table->decimal('monto_real_cierre', 10, 2)->nullable();
                $table->enum('estado', ['Abierta', 'Cerrada'])->default('Abierta');
                $table->timestamps();
                $table->index('estado');
            });
        }

        if (!Schema::hasTable('ventas')) {
            Schema::create('ventas', function (Blueprint $table) {
                $table->increments('id_venta');
                $table->unsignedInteger('id_cliente')->nullable();
                $table->unsignedInteger('id_empleado')->nullable();
                $table->unsignedInteger('id_caja')->nullable();
                $table->timestamp('ven_fecha')->nullable();
                $table->decimal('ven_total', 10, 2)->default(0);
                $table->string('tipo_pago', 30)->nullable();
                $table->timestamps();
                $table->index('ven_fecha');
                $table->index('id_caja');
            });
        }

        if (!Schema::hasTable('detalle_ventas')) {
            Schema::create('detalle_ventas', function (Blueprint $table) {
                $table->increments('id_detalle');
                $table->unsignedInteger('id_venta')->nullable();
                $table->unsignedInteger('id_producto')->nullable();
                $table->integer('det_cantidad')->default(1);
                $table->decimal('det_precio_unitario', 10, 2)->default(0);
                $table->timestamps();
                $table->index('id_venta');
                $table->index('id_producto');
            });
        }
    }

    private function ventasCaja($idCaja): float
    {
        if (!Schema::hasTable('ventas')) {
            return 0;
        }

        return (float) DB::table('ventas')
            ->where('id_caja', $idCaja)
            ->sum('ven_total');
    }

    /**
     * Verifica si hay una caja abierta actualmente.
     */
    public function consultarEstado()
    {
        try {
            $caja = Control_caja::where('estado', 'Abierta')->first();

            if ($caja) {
                $ventasHoy = $this->ventasCaja($caja->id_caja);
                $ventasHoy = $ventasHoy ? number_format($ventasHoy, 2, '.', '') : '0.00';

                return response()->json([
                    'success' => true,
                    'caja_abierta' => true,
                    'monto_inicial' => $caja->monto_inicial,
                    'ventas_hoy' => $ventasHoy,
                    'id_caja' => $caja->id_caja,
                    'fecha_apertura' => $caja->fecha_apertura,
                    'data' => $caja
                ], 200);
            }

            $ultimaCaja = Control_caja::where('estado', 'Cerrada')
                ->latest('fecha_cierre')
                ->first();

            return response()->json([
                'success' => true,
                'caja_abierta' => false,
                'ventas_hoy' => '0.00',
                'ultima_caja' => $ultimaCaja ? [
                    'id_caja' => $ultimaCaja->id_caja,
                    'fecha_cierre' => $ultimaCaja->fecha_cierre,
                    'total_ventas' => $ultimaCaja->monto_final_esperado - $ultimaCaja->monto_inicial
                ] : null
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        try {
            $controles = Control_caja::with('empleado')->get();
            return response()->json($controles, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint SIMPLE para abrir caja
     * POST /api/caja/abrir
     */
    public function abrirCaja(Request $request)
    {
        try {
            // Verificar si ya hay caja abierta
            $cajaAbierta = Control_caja::where('estado', 'Abierta')->first();
            
            if ($cajaAbierta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una caja abierta',
                    'caja_actual' => $cajaAbierta
                ], 400);
            }
            
            // Validar monto
            $monto = $request->input('monto');
            if (!$monto || $monto <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'El monto inicial es requerido y debe ser mayor a 0'
                ], 400);
            }
            
            // Obtener id_empleado
            $empleadoId = $request->input('empleado_id', 1);
            
            // Crear caja
            $caja = Control_caja::create([
                'monto_inicial' => floatval($monto),
                'id_empleado' => $empleadoId,
                'fecha_apertura' => now(),
                'estado' => 'Abierta'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Caja abierta correctamente',
                'data' => $caja,
                'caja_abierta' => true,
                'monto_inicial' => $caja->monto_inicial
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al abrir caja: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint SIMPLE para cerrar caja
     * POST /api/caja/cerrar
     */
    public function cerrarCaja(Request $request)
    {
        try {
            $cajaAbierta = Control_caja::where('estado', 'Abierta')->first();
            
            if (!$cajaAbierta) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay caja abierta para cerrar'
                ], 400);
            }
            
            // Calcular ventas
            $ventas = $this->ventasCaja($cajaAbierta->id_caja);
            $totalFinal = (float)$cajaAbierta->monto_inicial + (float)$ventas;
            
            // Obtener monto real de cierre (opcional)
            $montoReal = $request->input('monto_real', $totalFinal);
            
            // Cerrar caja
            $cajaAbierta->update([
                'estado' => 'Cerrada',
                'fecha_cierre' => now(),
                'monto_final_esperado' => $totalFinal,
                'monto_real_cierre' => floatval($montoReal)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Caja cerrada correctamente',
                'data' => $cajaAbierta,
                'ventas' => number_format($ventas, 2, '.', ''),
                'total' => number_format($totalFinal, 2, '.', '')
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar caja: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $accion = $request->input('accion');
            
            if ($accion === 'abrir') {
                return $this->abrirCaja($request);
            }
            
            if ($accion === 'cerrar') {
                return $this->cerrarCaja($request);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Acción no válida. Use "abrir" o "cerrar"'
            ], 400);
            
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $caja = Control_caja::with('empleado')->findOrFail($id);
            return response()->json($caja, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $caja = Control_caja::findOrFail($id);
            $caja->update($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Caja actualizada correctamente',
                'data' => $caja
            ], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $caja = Control_caja::find($id);
            if (!$caja) {
                return response()->json(['error' => 'Registro no encontrado'], 404);
            }
            
            $caja->delete();
            return response()->json(['message' => 'Registro eliminado'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}