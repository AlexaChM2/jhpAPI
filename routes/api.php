<?php


use App\Http\Controllers\API\ClimaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\CategoriasController;
use App\Http\Controllers\API\CitasController;
use App\Http\Controllers\API\ClienteController;
use App\Http\Controllers\API\ComprasController;
use App\Http\Controllers\API\Control_cajaController;
use App\Http\Controllers\API\CotizacionesController;
use App\Http\Controllers\API\Detalle_cita_serviciosController;
use App\Http\Controllers\API\Detalle_comprasController;
use App\Http\Controllers\API\Detalle_mantenimiento_insumosController;
use App\Http\Controllers\API\Detalle_mantenimiento_serviciosController;
use App\Http\Controllers\API\Detalle_ventasController;
use App\Http\Controllers\API\EmpleadosController;
use App\Http\Controllers\API\MantenimientoController;
use App\Http\Controllers\API\ProductoController;
use App\Http\Controllers\API\ProveedoresController;
use App\Http\Controllers\API\ReporteController;
use App\Http\Controllers\API\ServiciosController;
use App\Http\Controllers\API\VentasController;
use App\Http\Controllers\API\DetalleCotizacionController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PasswordResetController;
use App\Http\Controllers\API\UsuarioController;
use App\Http\Controllers\API\InventarioController;
use App\Http\Controllers\API\ProveedorVisitasController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json([
        'status' => 'API funcionando correctamente'
    ]);
});

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación (Públicas)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});

/*
|--------------------------------------------------------------------------
| Rutas de Recuperación de Contraseña (Públicas)
|--------------------------------------------------------------------------
*/
Route::prefix('password-reset')->group(function () {
    Route::post('request', [PasswordResetController::class, 'requestReset']);
    Route::post('validate-token', [PasswordResetController::class, 'validateToken']);
    Route::post('reset', [PasswordResetController::class, 'resetPassword']);
    Route::post('change', [PasswordResetController::class, 'changePassword'])->middleware('auth:sanctum');
});


Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('usuarios', UsuarioController::class);
    Route::patch('usuarios/{id}/estado', [UsuarioController::class, 'cambiarEstado']);
});

Route::apiResource('citas', CitasController::class);
Route::apiResource('clientes', ClienteController::class);

Route::apiResource('compras', ComprasController::class);


// control de cajas abierto o cerrado :D

// Rutas específicas para caja (MÁS SIMPLES)
Route::post('/caja/abrir', [Control_cajaController::class, 'abrirCaja']);
Route::post('/caja/cerrar', [Control_cajaController::class, 'cerrarCaja']);
Route::get('/caja/estado', [Control_cajaController::class, 'consultarEstado']);
Route::get('/caja', [Control_cajaController::class, 'index']);

// Mantén las rutas originales si las necesitas
Route::get('control_caja/estado', [Control_cajaController::class, 'consultarEstado']);
Route::apiResource('control_caja', Control_cajaController::class);


//fin  xd

Route::apiResource('cotizaciones', CotizacionesController::class);
Route::apiResource('detalle_cotizaciones', DetalleCotizacionController::class);

Route::apiResource('detalle_cita_servicios', Detalle_cita_serviciosController::class);
Route::apiResource('detalle_compras', Detalle_comprasController::class);
Route::apiResource('detalle_mantenimiento_insumos', Detalle_mantenimiento_insumosController::class);
Route::apiResource('detalle_mantenimiento_servicios', Detalle_mantenimiento_serviciosController::class);
Route::apiResource('detalle_ventas', Detalle_ventasController::class);

Route::apiResource('empleados', EmpleadosController::class);
Route::apiResource('mantenimiento', MantenimientoController::class);

Route::apiResource('producto', ProductoController::class);



Route::apiResource('inventario', InventarioController::class);
Route::apiResource('inventarios', InventarioController::class);

// ==========================================
// RUTAS DE VISITAS (DEBEN IR PRIMERO)
// ==========================================
Route::get('/proveedores/{idProveedor}/visitas', [ProveedorVisitasController::class, 'index']);
Route::get('/proveedores/{idProveedor}/proxima-visita', [ProveedorVisitasController::class, 'proximaVisita']);
Route::post('/proveedor-visitas', [ProveedorVisitasController::class, 'store']);
Route::put('/proveedor-visitas/{id}', [ProveedorVisitasController::class, 'update']);
Route::delete('/proveedor-visitas/{id}', [ProveedorVisitasController::class, 'destroy']);

// ==========================================
// RUTAS DE PROVEEDORES (DESPUÉS)
// ==========================================
Route::get('/proveedores', [ProveedoresController::class, 'index']);
Route::post('/proveedores', [ProveedoresController::class, 'store']);
Route::get('/proveedores/{id}', [ProveedoresController::class, 'show']);
Route::put('/proveedores/{id}', [ProveedoresController::class, 'update']);
Route::delete('/proveedores/{id}', [ProveedoresController::class, 'destroy']);

Route::apiResource('proveedores', ProveedoresController::class);
Route::apiResource('categorias', CategoriasController::class);

Route::prefix('clima')->group(function () {
   
    Route::get('/', function () {
        return response()->json([
            'endpoints' => [
                'actual' => url('/api/clima/actual'),
                'pronostico' => url('/api/clima/pronostico'),
            ],
            'mensaje' => 'Usa /api/clima/actual o /api/clima/pronostico'
        ]);
    });
    
    Route::get('/actual', [ClimaController::class, 'actual']);
    Route::get('/pronostico', [ClimaController::class, 'pronostico']);
});

Route::apiResource('servicios', ServiciosController::class);
Route::apiResource('ventas',VentasController::class);
Route::apiResource('detalle_cotizaciones',DetalleCotizacionController::class);

Route::get('reportes-detallados', [ReporteController::class, 'datosGraficas']);

use App\Http\Controllers\API\MarcaController;

Route::get('marcas/activas', [MarcaController::class, 'activas']);  // ← PRIMERO
Route::apiResource('marcas', MarcaController::class);              // ← DESPUÉS
Route::patch('marcas/{id}/toggle', [MarcaController::class, 'toggleEstado']);



// Visitas de proveedores
Route::get('/proveedores/{idProveedor}/visitas', [ProveedorVisitasController::class, 'index']);
Route::post('/proveedor-visitas', [ProveedorVisitasController::class, 'store']);
Route::put('/proveedor-visitas/{id}', [ProveedorVisitasController::class, 'update']);
Route::delete('/proveedor-visitas/{id}', [ProveedorVisitasController::class, 'destroy']);
Route::get('/proveedores/{idProveedor}/proxima-visita', [ProveedorVisitasController::class, 'proximaVisita']);