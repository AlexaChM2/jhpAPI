<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;

class PasswordResetController extends Controller
{
    /**
     * Solicitar recuperación de contraseña - CON ENVÍO DE CORREO
     */
    public function requestReset(Request $request)
    {
        try {
            \Log::info('requestReset llamado', ['correo' => $request->correo]);
            
            $validator = Validator::make($request->all(), [
                'correo' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Buscar el usuario
            $usuario = Cliente::where('cli_correo', $request->correo)->first();
            $tipo = 'cliente';
            $nombre = '';
            
            if (!$usuario) {
                $usuario = Empleado::where('emp_correo', $request->correo)->first();
                $tipo = 'empleado';
            }

            if (!$usuario) {
                return response()->json([
                    'success' => true,
                    'message' => 'Si el correo está registrado, recibirá instrucciones',
                ], 200);
            }

            // Obtener datos
            $userId = ($tipo === 'cliente') ? $usuario->id_cliente : $usuario->id_empleados;
            $email = ($tipo === 'cliente') ? $usuario->cli_correo : $usuario->emp_correo;
            $nombre = ($tipo === 'cliente') ? $usuario->cli_nombre : $usuario->emp_nombre;

            // Crear token
            $token = hash('sha256', \Illuminate\Support\Str::random(60));

            // Guardar en la tabla
            PasswordReset::create([
                'id_usuario' => $userId,
                'token' => $token,
                'correo' => $email,
                'fecha_solicitud' => now(),
                'fecha_expiracion' => now()->addHours(24),
                'utilizado' => false,
            ]);

            // ==========================================
            // ENVIAR CORREO ELECTRÓNICO
            // ==========================================
            try {
                Mail::to($email)->send(new PasswordResetMail($usuario, $token, $nombre, $email));
                \Log::info('Correo enviado a: ' . $email);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Hemos enviado las instrucciones de recuperación a tu correo electrónico',
                ], 200);
                
            } catch (\Exception $mailException) {
                \Log::error('Error enviando email: ' . $mailException->getMessage());
                
                // Si falla el envío, devolvemos el token para pruebas
                return response()->json([
                    'success' => true,
                    'message' => 'Token generado pero no se pudo enviar el correo',
                    'data' => [
                        'token' => $token,
                        'correo' => $email,
                    ]
                ], 200);
            }

        } catch (\Exception $e) {
            \Log::error('Error en requestReset: ' . $e->getMessage() . ' - Línea: ' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // El resto de métodos (resetPassword, validateToken, changePassword) se mantienen igual...


    /**
     * Resetear contraseña
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
                'password' => 'required|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $passwordReset = PasswordReset::where('token', $request->token)
                ->where('utilizado', false)
                ->where('fecha_expiracion', '>', now())
                ->first();

            if (!$passwordReset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido o expirado',
                ], 400);
            }

            // Buscar y actualizar usuario
            $usuario = Cliente::where('cli_correo', $passwordReset->correo)->first();
            if ($usuario) {
                $usuario->cli_password = Hash::make($request->password);
                $usuario->save();
            } else {
                $empleado = Empleado::where('emp_correo', $passwordReset->correo)->first();
                if ($empleado) {
                    $empleado->emp_password = Hash::make($request->password);
                    $empleado->save();
                }
            }

            $passwordReset->update([
                'utilizado' => true,
                'fecha_uso' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validar token
     */
    public function validateToken(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $passwordReset = PasswordReset::where('token', $request->token)
                ->where('utilizado', false)
                ->where('fecha_expiracion', '>', now())
                ->first();

            if (!$passwordReset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido o expirado',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token válido',
                'data' => [
                    'correo' => $passwordReset->correo,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambiar contraseña (usuario autenticado)
     */
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'password_actual' => 'required|string',
                'password_nueva' => 'required|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado',
                ], 401);
            }

            $model = null;
            $currentPassword = null;
            
            $cliente = Cliente::where('id_cliente', $user->id)->first();
            if ($cliente) {
                $model = $cliente;
                $currentPassword = $cliente->cli_password;
            } else {
                $empleado = Empleado::where('id_empleados', $user->id)->first();
                if ($empleado) {
                    $model = $empleado;
                    $currentPassword = $empleado->emp_password;
                }
            }
            
            if (!$model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                ], 404);
            }

            if (!Hash::check($request->password_actual, $currentPassword)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta',
                ], 400);
            }

            $passwordField = ($model instanceof Cliente) ? 'cli_password' : 'emp_password';
            $model->$passwordField = Hash::make($request->password_nueva);
            $model->save();

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}