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

            // Buscar el usuario en Clientes o Empleados
            $usuario = Cliente::where('cli_correo', $request->correo)->first();
            $tipo = 'cliente';
            
            if (!$usuario) {
                $usuario = Empleado::where('emp_correo', $request->correo)->first();
                $tipo = 'empleado';
            }

            if (!$usuario) {
                return response()->json([
                    'success' => true,
                    'message' => 'Si el correo está registrado, recibirás instrucciones de recuperación',
                ], 200);
            }

            // Obtener datos del usuario
            $userId = ($tipo === 'cliente') ? $usuario->id_cliente : $usuario->id_empleados;
            $email = ($tipo === 'cliente') ? $usuario->cli_correo : $usuario->emp_correo;
            $nombre = ($tipo === 'cliente') ? $usuario->cli_nombre : $usuario->emp_nombre;

            // Crear token
            $token = hash('sha256', \Illuminate\Support\Str::random(60));

            // Guardar en la tabla password_resets
            $passwordReset = PasswordReset::create([
                'id_usuario' => $userId,
                'token' => $token,
                'correo' => $email,
                'fecha_solicitud' => now(),
                'fecha_expiracion' => now()->addHours(24),
                'utilizado' => false,
                'ip_solicitud' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // ==========================================
            // ENVIAR CORREO ELECTRÓNICO
            // ==========================================
            try {
                Mail::to($email)->send(new PasswordResetMail($usuario, $token, $nombre, $email));
                
                \Log::info('Correo de recuperación enviado a: ' . $email);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Hemos enviado las instrucciones de recuperación a tu correo electrónico',
                ], 200);
                
            } catch (\Exception $mailException) {
                \Log::error('Error enviando email: ' . $mailException->getMessage());
                
                // Si falla el envío, devolvemos el token para pruebas (modo desarrollo)
                if (config('app.debug')) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Token generado pero no se pudo enviar el correo',
                        'data' => [
                            'token' => $token,
                            'correo' => $email,
                        ]
                    ], 200);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error al enviar el correo. Por favor intenta más tarde.',
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Error en requestReset: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error en el servidor',
            ], 500);
        }
    }

    // Resto de métodos (resetPassword, validateToken, changePassword) se mantienen igual...
}