<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'correo' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Buscar usuario
            $resultadoAuth = $this->findAuthUser($request->correo);

            if (!$resultadoAuth) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ], 401);
            }

            $usuario = $resultadoAuth['usuario'];
            $tipoUsuario = $resultadoAuth['tipo'];

            // Verificar contraseña
            if (!$this->verifyPassword($usuario, $request->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ], 401);
            }

            // Preparar datos de respuesta
            $rol = 'Cliente';
            $nombreCompleto = $usuario->cli_nombre;
            $userId = $usuario->id_cliente;

            if ($tipoUsuario === 'empleado') {
                $rol = $usuario->emp_rol;
                $nombreCompleto = $usuario->nombre_completo;
                $userId = $usuario->id_empleados;
            }

            // Crear token - ESTA ES LA LÍNEA IMPORTANTE
            $token = $usuario->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'data' => [
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'usuario' => [
                        'id' => $userId,
                        'nombre' => $nombreCompleto,
                        'correo' => $tipoUsuario === 'empleado' ? $usuario->emp_correo : $usuario->cli_correo,
                        'rol' => $rol,
                        'tipo' => $tipoUsuario
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    private function findAuthUser(string $correo)
    {
        // Buscar empleado activo
        $empleado = Empleado::where('emp_correo', $correo)
            ->where('emp_estado', 'Activo')
            ->first();

        if ($empleado) {
            return ['usuario' => $empleado, 'tipo' => 'empleado'];
        }

        // Buscar cliente
        $cliente = Cliente::where('cli_correo', $correo)->first();

        if ($cliente) {
            return ['usuario' => $cliente, 'tipo' => 'cliente'];
        }

        return null;
    }

    private function verifyPassword($usuario, string $password): bool
    {
        $stored = $usuario->emp_password ?? $usuario->cli_password ?? null;

        if (!$stored) {
            return false;
        }

        // Si está hasheado
        if (password_get_info($stored)['algo'] > 0) {
            return Hash::check($password, $stored);
        }

        // Si está en texto plano, hashearlo
        if ($stored === $password) {
            $campo = isset($usuario->emp_password) ? 'emp_password' : 'cli_password';
            $usuario->$campo = Hash::make($password);
            $usuario->save();
            return true;
        }

        return false;
    }

    public function logout(Request $request)
    {
        try {
            if ($request->user()) {
                $request->user()->currentAccessToken()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión'
            ], 500);
        }
    }
}