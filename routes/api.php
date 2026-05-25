<?php


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
use App\Http\Controllers\API\ServiciosController;
use App\Http\Controllers\API\VentasController;
use App\Http\Controllers\API\DetalleCotizacionController;
use App\Http\Controllers\API\ReporteController;
use App\Http\Controllers\API\PasswordResetController;
use App\Models\Detalle_cotizaciones;

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

Route::post('password/forgot', [PasswordResetController::class, 'requestToken']);
Route::post('password/validate-token', [PasswordResetController::class, 'validateToken']);
Route::post('password/reset', [PasswordResetController::class, 'reset']);
Route::post('auth/password-reset/request', [PasswordResetController::class, 'requestToken']);
Route::post('auth/password-reset/validate', [PasswordResetController::class, 'validateToken']);
Route::post('auth/password-reset/reset', [PasswordResetController::class, 'reset']);


Route::apiResource('citas', CitasController::class);
Route::apiResource('clientes', ClienteController::class);

Route::apiResource('compras', ComprasController::class);

// RUTAS DE CONTROL CAJA
// Primero la ruta de consulta 

// Luego el recurso general
Route::get('control_caja/estado', [Control_cajaController::class, 'consultarEstado']);
Route::apiResource('control_caja', Control_cajaController::class);

Route::apiResource('cotizaciones', CotizacionesController::class);

Route::apiResource('detalle_cita_servicios', Detalle_cita_serviciosController::class);
Route::apiResource('detalle_compras', Detalle_comprasController::class);
Route::apiResource('detalle_mantenimiento_insumos', Detalle_mantenimiento_insumosController::class);
Route::apiResource('detalle_mantenimiento_servicios', Detalle_mantenimiento_serviciosController::class);
Route::apiResource('detalle_ventas', Detalle_ventasController::class);

Route::apiResource('empleados', EmpleadosController::class);
Route::apiResource('mantenimiento', MantenimientoController::class);

Route::apiResource('producto', ProductoController::class);
Route::apiResource('proveedores', ProveedoresController::class);
Route::apiResource('categorias', CategoriasController::class);

Route::apiResource('servicios', ServiciosController::class);
Route::apiResource('ventas',VentasController::class);
Route::apiResource('detalle_cotizaciones',DetalleCotizacionController::class);

Route::get('reportes-detallados', [ReporteController::class, 'datosGraficas']);
