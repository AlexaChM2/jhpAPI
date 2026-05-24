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
        //$this->asegurarTablasCaja();

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
    // DIAGNÓSTICO: Ver qué está llegando
    \Log::info('Datos recibidos en store:', [
        'all' => $request->all(),
        'json' => $request->json()->all(),
        'content' => $request->getContent()
    ]);
    
    // Intentar obtener accion de diferentes formas
    $accion = $request->input('accion');
    
    // Si no viene por input, intentar del JSON
    if (!$accion) {
        $accion = $request->json()->get('accion');
    }
    
    // Si aún no, intentar del contenido raw
    if (!$accion) {
        $content = json_decode($request->getContent(), true);
        $accion = $content['accion'] ?? null;
    }
    
    \Log::info('Acción detectada:', ['accion' => $accion]);
    
    try {
        // LÓGICA PARA ABRIR CAJA
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

            // Validar monto inicial
            $montoInicial = $request->input('monto_inicial') ?? $request->json()->get('monto_inicial');
            if (!$montoInicial || $montoInicial <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El monto inicial es requerido y debe ser válido'
                ], 400);
            }

            // Crear nueva caja
            $caja = Control_caja::create([
                'monto_inicial'  => $montoInicial,
                'id_empleado'    => $request->input('id_empleado') ?? $request->json()->get('id_empleado') ?? 1,
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

        // LÓGICA PARA CERRAR CAJA
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

            // Actualizar la caja
            $cajaAbierta->update([
                'monto_final_esperado' => $montoFinalEsperado,
                'monto_real_cierre' => $request->input('monto_real_cierre') ?? $montoFinalEsperado,
                'fecha_cierre'      => now(),
                'estado'            => 'Cerrada'
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Caja cerrada correctamente',
                'data'    => $cajaAbierta,
                'ventas_hoy' => number_format($ventasHoy, 2, '.', ''),
                'monto_final_esperado' => number_format($montoFinalEsperado, 2, '.', '')
            ], 200);
        }

        return response()->json([
            'status' => 'error', 
            'message' => 'Acción no válida. Use "abrir" o "cerrar"'
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
