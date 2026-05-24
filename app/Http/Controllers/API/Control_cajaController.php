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
            // Buscar caja abierta
            $caja = Control_caja::where('estado', 'Abierta')->first();

            if ($caja) {
                // Sumar ventas del día para esta caja
                $ventasHoy = $this->ventasCaja($caja->id_caja);
                
                // Formatear el resultado
                $ventasHoy = $ventasHoy ? number_format($ventasHoy, 2, '.', '') : '0.00';

                return response()->json([
                    'status' => 'success',
                    'caja_abierta' => true,
                    'monto_inicial' => $caja->monto_inicial,
                    'ventas_hoy' => $ventasHoy,
                    'id_caja' => $caja->id_caja,
                    'fecha_apertura' => $caja->fecha_apertura,
                    'data' => $caja
                ], 200);
            }

            // Si no hay caja abierta, buscar la última caja cerrada para mostrar histórico
            $ultimaCaja = Control_caja::where('estado', 'Cerrada')
                ->latest('fecha_cierre')
                ->first();

            return response()->json([
                'status' => 'success',
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
                'status' => 'error',
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

    public function store(Request $request)
    {
        try {
            // ==========================================
            // DETECTAR ACCIÓN DE MÚLTIPLES FORMAS
            // ==========================================
            
            $accion = null;
            
            // 1. Intentar desde input normal (POST form)
            if ($request->has('accion')) {
                $accion = $request->input('accion');
            }
            
            // 2. Intentar desde JSON
            if (!$accion && $request->json()->has('accion')) {
                $accion = $request->json()->get('accion');
            }
            
            // 3. Intentar desde contenido raw
            if (!$accion) {
                $content = json_decode($request->getContent(), true);
                if (isset($content['accion'])) {
                    $accion = $content['accion'];
                }
            }
            
            // 4. Intentar desde $_POST directo (x-www-form-urlencoded)
            if (!$accion && isset($_POST['accion'])) {
                $accion = $_POST['accion'];
            }
            
            // 5. Intentar desde query string (GET)
            if (!$accion && $request->query('accion')) {
                $accion = $request->query('accion');
            }
            
            // Si no hay acción, devolver error
            if (!$accion) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se recibió el campo "accion". Use "abrir" o "cerrar"',
                    'debug' => [
                        'method' => $request->method(),
                        'content_type' => $request->header('Content-Type'),
                        'all_input' => $request->all(),
                        'post' => $_POST
                    ]
                ], 400);
            }
            
            // ==========================================
            // ABRIR CAJA
            // ==========================================
            if ($accion === 'abrir') {
                // Verificar si ya hay una caja abierta
                $cajaAbiertaExistente = Control_caja::where('estado', 'Abierta')->first();
                
                if ($cajaAbiertaExistente) {
                    return response()->json([
                        'status' => 'error', 
                        'message' => 'Ya existe una caja abierta',
                        'caja_actual' => $cajaAbiertaExistente
                    ], 400);
                }
                
                // Obtener monto inicial (de múltiples formas)
                $montoInicial = null;
                
                if ($request->has('monto_inicial')) {
                    $montoInicial = $request->input('monto_inicial');
                } elseif ($request->json()->has('monto_inicial')) {
                    $montoInicial = $request->json()->get('monto_inicial');
                } elseif (isset($_POST['monto_inicial'])) {
                    $montoInicial = $_POST['monto_inicial'];
                } else {
                    $content = json_decode($request->getContent(), true);
                    if (isset($content['monto_inicial'])) {
                        $montoInicial = $content['monto_inicial'];
                    }
                }
                
                if (!$montoInicial || floatval($montoInicial) <= 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'El monto inicial es requerido y debe ser mayor a 0'
                    ], 400);
                }
                
                // Obtener id_empleado
                $idEmpleado = null;
                if ($request->has('id_empleado')) {
                    $idEmpleado = $request->input('id_empleado');
                } elseif ($request->json()->has('id_empleado')) {
                    $idEmpleado = $request->json()->get('id_empleado');
                } elseif (isset($_POST['id_empleado'])) {
                    $idEmpleado = $_POST['id_empleado'];
                }
                
                if (!$idEmpleado) {
                    $idEmpleado = 1;
                }
                
                // Crear nueva caja
                $caja = Control_caja::create([
                    'monto_inicial'  => floatval($montoInicial),
                    'id_empleado'    => $idEmpleado,
                    'fecha_apertura' => now(),
                    'estado'         => 'Abierta'
                ]);
                
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Caja abierta con éxito',
                    'data'    => $caja,
                    'ventas_hoy' => '0.00',
                    'caja_abierta' => true
                ], 201);
            }
            
            // ==========================================
            // CERRAR CAJA
            // ==========================================
            if ($accion === 'cerrar') {
                $cajaAbierta = Control_caja::where('estado', 'Abierta')->first();
                
                if (!$cajaAbierta) {
                    return response()->json([
                        'status' => 'error', 
                        'message' => 'No hay caja abierta para cerrar'
                    ], 404);
                }
                
                // Calcular ventas de esta caja
                $ventasHoy = $this->ventasCaja($cajaAbierta->id_caja);
                $ventasHoy = $ventasHoy ? (float)$ventasHoy : 0;
                
                // Calcular monto final esperado
                $montoFinalEsperado = (float)$cajaAbierta->monto_inicial + $ventasHoy;
                
                // Obtener monto real de cierre (opcional)
                $montoRealCierre = $montoFinalEsperado;
                if ($request->has('monto_real_cierre')) {
                    $montoRealCierre = $request->input('monto_real_cierre');
                } elseif ($request->json()->has('monto_real_cierre')) {
                    $montoRealCierre = $request->json()->get('monto_real_cierre');
                } elseif (isset($_POST['monto_real_cierre'])) {
                    $montoRealCierre = $_POST['monto_real_cierre'];
                }
                
                // Actualizar la caja
                $cajaAbierta->update([
                    'monto_final_esperado' => $montoFinalEsperado,
                    'monto_real_cierre'    => floatval($montoRealCierre),
                    'fecha_cierre'         => now(),
                    'estado'               => 'Cerrada'
                ]);
                
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Caja cerrada correctamente',
                    'data'    => $cajaAbierta,
                    'ventas_hoy' => number_format($ventasHoy, 2, '.', ''),
                    'monto_final_esperado' => number_format($montoFinalEsperado, 2, '.', '')
                ], 200);
            }
            
            // ==========================================
            // ACCIÓN NO VÁLIDA
            // ==========================================
            return response()->json([
                'status' => 'error', 
                'message' => 'Acción no válida. Use "abrir" o "cerrar"',
                'accion_recibida' => $accion
            ], 400);
            
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Error en el servidor: ' . $e->getMessage()
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
            
            // Si la acción es 'cerrar' o se envía estado 'Cerrada'
            if (($request->input('accion') === 'cerrar') || ($request->input('estado') === 'Cerrada')) {
                $ventasHoy = $this->ventasCaja($caja->id_caja);
                $montoFinalEsperado = (float)$caja->monto_inicial + (float)$ventasHoy;
                
                $caja->update([
                    'estado' => 'Cerrada',
                    'fecha_cierre' => now(),
                    'monto_final_esperado' => $montoFinalEsperado,
                    'monto_real_cierre' => $request->input('monto_real_cierre') ?? $montoFinalEsperado
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Caja cerrada correctamente',
                    'data' => $caja
                ], 200);
            }
            
            // Actualización normal
            $caja->update($request->all());
            return response()->json([
                'status' => 'success',
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