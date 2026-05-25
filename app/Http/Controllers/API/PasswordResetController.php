<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Empleado;
use Illuminate\Support\Facades\Schema;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Mail\PasswordResetMail;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Solicitar recuperación de contraseña
     * POST /api/password-reset/request
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

            $usuario = $this->findPasswordResetUser($request->correo);

            // No revelar si el correo existe o no (seguridad)
            if (!$usuario) {
                return response()->json([
                    'success' => true,
                    'message' => 'Si el correo está registrado, recibirá instrucciones de recuperación',
                ], 200);
            }

            // Obtener el email según el tipo de usuario
            $recipientEmail = $this->getUserEmail($usuario);

            // Obtener el ID del usuario
            $userId = $this->getUserId($usuario);

            $passwordReset = PasswordReset::crearSolicitud(
                $userId,
                $recipientEmail,
                $request
            );

            // Enviar email con token
            try {
                Mail::to($recipientEmail)
                    ->send(new PasswordResetMail($usuario, $passwordReset->token));
            } catch (\Exception $mailException) {
                $mailError = $mailException->getMessage();
                \Log::error('Error enviando email de recuperación: ' . $mailError);

                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo enviar el correo de recuperación. Verifica la configuración SMTP del servidor.',
                    'debug_info' => config('app.debug') ? [
                        'token_created' => true,
                        'mail_sent' => false,
                        'mail_error' => $mailError,
                    ] : null,
                ], 500);
            }

            $response = [
                'success' => true,
                'message' => 'Si el correo está registrado, recibirá instrucciones de recuperación',
            ];

            if (config('app.debug')) {
                $response['debug_info'] = [
                    'token' => $passwordReset->token,
                    'correo' => $recipientEmail,
                ];
            }

            return response()->json($response, 200);

        } catch (\Exception $e) {
            \Log::error('Error en solicitud de recuperación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error en el servidor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buscar usuario para recuperación en clientes, empleados, usuarios o users
     */
    private function findPasswordResetUser(string $correo)
    {
        // 1. Buscar en clientes
        $cliente = Cliente::where('cli_correo', $correo)->first();
        if ($cliente) {
            return $cliente;
        }

        // 2. Buscar en empleados
        $empleado = Empleado::where('emp_correo', $correo)->first();
        if ($empleado) {
            return $empleado;
        }

        // 3. Buscar en tabla usuarios
        $usuario = Usuario::where('correo', $correo)->first();
        if ($usuario) {
            return $usuario;
        }

        // 4. Buscar en tabla users (Laravel default)
        $userQuery = User::where('email', $correo);
        if (Schema::hasColumn('users', 'correo')) {
            $userQuery->orWhere('correo', $correo);
        }

        return $userQuery->first();
    }

    /**
     * Obtener el email del usuario según su tipo
     */
    private function getUserEmail($usuario)
    {
        if ($usuario instanceof Cliente) {
            return $usuario->cli_correo;
        }
        if ($usuario instanceof Empleado) {
            return $usuario->emp_correo;
        }
        if ($usuario instanceof Usuario) {
            return $usuario->correo;
        }
        if ($usuario instanceof User) {
            return $usuario->email;
        }
        return null;
    }

    /**
     * Obtener el ID del usuario según su tipo
     */
    private function getUserId($usuario)
    {
        if ($usuario instanceof Cliente) {
            return $usuario->id_cliente;
        }
        if ($usuario instanceof Empleado) {
            return $usuario->id_empleados;
        }
        if ($usuario instanceof Usuario) {
            return $usuario->id_usuario;
        }
        if ($usuario instanceof User) {
            return $usuario->id;
        }
        return null;
    }

    /**
     * Validar token de recuperación
     * POST /api/password-reset/validate-token
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

            $passwordReset = PasswordReset::obtenerPorToken($request->token);

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
                    'fecha_expiracion' => $passwordReset->fecha_expiracion,
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
     * Resetear contraseña
     * POST /api/password-reset/reset
     */
    // Agrega esta validación para aceptar email o correo
public function resetPassword(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validator->errors(),
            ], 422);
        }

        $passwordReset = PasswordReset::obtenerPorToken($request->token);

        if (!$passwordReset) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado',
            ], 400);
        }

        // Validar que no sea expirado
        if (!$passwordReset->esValido()) {
            return response()->json([
                'success' => false,
                'message' => 'El token ha expirado',
            ], 400);
        }

        // Obtener el correo del token
        $correo = $passwordReset->correo;
        
        // Si el request tiene email, usarlo para verificar coincidencia
        if ($request->has('email') && $request->email !== $correo) {
            return response()->json([
                'success' => false,
                'message' => 'El correo no coincide con el token',
            ], 400);
        }
        
        $usuarioModel = $this->findPasswordResetUser($correo);

        if (!$usuarioModel) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        // Actualizar contraseña
        try {
            $hashedPassword = Hash::make($request->password);
            
            if ($usuarioModel instanceof Cliente) {
                $usuarioModel->cli_password = $hashedPassword;
                $usuarioModel->save();
            } elseif ($usuarioModel instanceof Empleado) {
                $usuarioModel->emp_password = $hashedPassword;
                $usuarioModel->save();
            } elseif ($usuarioModel instanceof Usuario) {
                $usuarioModel->password = $hashedPassword;
                $usuarioModel->save();
            } elseif ($usuarioModel instanceof User) {
                $usuarioModel->password = $hashedPassword;
                $usuarioModel->save();
            }
        } catch (\Exception $e) {
            \Log::error('Error actualizando contraseña: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error actualizando la contraseña',
            ], 500);
        }

        $passwordReset->marcarUtilizado();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada exitosamente',
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Error en resetPassword: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
        ], 500);
    }
}

    /**
     * Cambiar contraseña (usuario autenticado)
     * POST /api/password-reset/change
     */
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'password_actual' => 'required|string',
                'password_nueva' => 'required|string|min:6|confirmed',
            ], [
                'password_nueva.confirmed' => 'Las contraseñas no coinciden',
                'password_nueva.min' => 'La contraseña debe tener al menos 6 caracteres',
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

            // Buscar el usuario en las tablas correspondientes
            $model = null;
            $passwordField = null;
            $currentPassword = null;
            
            // Buscar en clientes
            $cliente = Cliente::where('id_cliente', $user->id)->first();
            if ($cliente) {
                $model = $cliente;
                $passwordField = 'cli_password';
                $currentPassword = $cliente->cli_password;
            }
            
            // Buscar en empleados
            if (!$model) {
                $empleado = Empleado::where('id_empleados', $user->id)->first();
                if ($empleado) {
                    $model = $empleado;
                    $passwordField = 'emp_password';
                    $currentPassword = $empleado->emp_password;
                }
            }
            
            // Buscar en usuarios
            if (!$model) {
                $usuario = Usuario::where('id_usuario', $user->id)->first();
                if ($usuario) {
                    $model = $usuario;
                    $passwordField = 'password';
                    $currentPassword = $usuario->password;
                }
            }
            
            // Buscar en users
            if (!$model) {
                $userModel = User::where('id', $user->id)->first();
                if ($userModel) {
                    $model = $userModel;
                    $passwordField = 'password';
                    $currentPassword = $userModel->password;
                }
            }
            
            if (!$model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                ], 404);
            }

            // Verificar contraseña actual
            if (!Hash::check($request->password_actual, $currentPassword)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta',
                ], 400);
            }

            // Actualizar contraseña
            $model->$passwordField = Hash::make($request->password_nueva);
            $model->save();

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente',
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error en changePassword: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}