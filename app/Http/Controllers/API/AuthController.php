<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Control_Caja;
use App\Models\Empleados;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request, JwtService $jwt)
    {
        $request->validate([
            'correo' => 'required|email',
            'password' => 'required|string',
        ]);

        $empleado = Empleados::where('emp_correo', $request->correo)->first();

        if ($empleado && $this->passwordMatches($request->password, $empleado->emp_password)) {
            if ($empleado->emp_estado && $empleado->emp_estado !== 'Activo') {
                return response()->json(['message' => 'Usuario inactivo'], 403);
            }

            $this->rehashPasswordIfNeeded($empleado, 'emp_password', $request->password);
            $this->closeOpenCashBoxes();

            return response()->json($this->sessionPayload($empleado, $jwt));
        }

        $cliente = Cliente::where('cli_correo', $request->correo)->first();

        if ($cliente && $this->passwordMatches($request->password, $cliente->cli_password)) {
            if ($cliente->cli_estado && $cliente->cli_estado !== 'Activo') {
                return response()->json(['message' => 'Usuario inactivo'], 403);
            }

            $this->rehashPasswordIfNeeded($cliente, 'cli_password', $request->password);
            return response()->json($this->clientSessionPayload($cliente, $jwt));
        }

        return response()->json(['message' => 'Credenciales incorrectas'], 401);
    }

    public function me(Request $request, JwtService $jwt)
    {
        $claims = $this->claimsFromRequest($request, $jwt);

        if (!$claims) {
            return response()->json(['message' => 'Token invalido o expirado'], 401);
        }

        if (($claims['tipo'] ?? '') === 'cliente') {
            $cliente = Cliente::find($claims['sub'] ?? null);

            if (!$cliente) {
                return response()->json(['message' => 'Usuario no encontrado'], 404);
            }

            return response()->json([
                'usuario' => $this->clientePayload($cliente),
            ]);
        }

        $empleado = Empleados::find($claims['sub'] ?? null);

        if (!$empleado) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json([
            'usuario' => $this->usuarioPayload($empleado),
        ]);
    }

    private function claimsFromRequest(Request $request, JwtService $jwt): ?array
    {
        $header = (string) $request->header('Authorization');

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        return $jwt->verify($matches[1]);
    }

    private function passwordMatches(string $plainPassword, ?string $storedPassword): bool
    {
        if (!$storedPassword) {
            return false;
        }

        if (Hash::check($plainPassword, $storedPassword)) {
            return true;
        }

        return hash_equals($storedPassword, $plainPassword)
            || hash_equals($storedPassword, md5($plainPassword));
    }

    private function rehashPasswordIfNeeded($user, string $field, string $plainPassword): void
    {
        $storedPassword = (string) ($user->{$field} ?? '');

        if (!$storedPassword || Hash::needsRehash($storedPassword)) {
            $user->forceFill([$field => Hash::make($plainPassword)])->save();
        }
    }

    private function sessionPayload(Empleados $empleado, JwtService $jwt): array
    {
        $usuario = $this->usuarioPayload($empleado);

        return [
            'token_type' => 'Bearer',
            'expires_in' => (int) env('JWT_TTL', 120) * 60,
            'token' => $jwt->make([
                'sub' => $empleado->getKey(),
                'correo' => $empleado->emp_correo,
                'rol' => $empleado->emp_rol,
                'tipo' => 'empleado',
            ]),
            'usuario' => $usuario,
            'user' => $usuario,
        ];
    }

    private function clientSessionPayload(Cliente $cliente, JwtService $jwt): array
    {
        $usuario = $this->clientePayload($cliente);

        return [
            'token_type' => 'Bearer',
            'expires_in' => (int) env('JWT_TTL', 120) * 60,
            'token' => $jwt->make([
                'sub' => $cliente->getKey(),
                'correo' => $cliente->cli_correo,
                'rol' => 'Cliente',
                'tipo' => 'cliente',
            ]),
            'usuario' => $usuario,
            'user' => $usuario,
        ];
    }

    private function closeOpenCashBoxes(): void
    {
        Control_Caja::where('estado', 'Abierta')->get()->each(function (Control_Caja $caja) {
            $montoInicial = (float) $caja->monto_inicial;

            $caja->forceFill([
                'fecha_cierre' => now(),
                'monto_final_esperado' => $caja->monto_final_esperado ?? $montoInicial,
                'monto_real_cierre' => $caja->monto_real_cierre ?? $montoInicial,
                'estado' => 'Cerrada',
            ])->save();
        });
    }

    private function usuarioPayload(Empleados $empleado): array
    {
        return [
            'id' => $empleado->id_empleados,
            'id_empleados' => $empleado->id_empleados,
            'nombre' => trim("{$empleado->emp_nombre} {$empleado->emp_apaterno} {$empleado->emp_amaterno}"),
            'emp_nombre' => $empleado->emp_nombre,
            'emp_apaterno' => $empleado->emp_apaterno,
            'emp_amaterno' => $empleado->emp_amaterno,
            'correo' => $empleado->emp_correo,
            'emp_correo' => $empleado->emp_correo,
            'rol' => $empleado->emp_rol,
            'emp_rol' => $empleado->emp_rol,
            'estado' => $empleado->emp_estado,
            'emp_estado' => $empleado->emp_estado,
        ];
    }

    private function clientePayload(Cliente $cliente): array
    {
        return [
            'id' => $cliente->id_cliente,
            'id_cliente' => $cliente->id_cliente,
            'nombre' => trim("{$cliente->cli_nombre} {$cliente->cli_apaterno} {$cliente->cli_amaterno}"),
            'cli_nombre' => $cliente->cli_nombre,
            'cli_apaterno' => $cliente->cli_apaterno,
            'cli_amaterno' => $cliente->cli_amaterno,
            'telefono' => $cliente->cli_telefono,
            'cli_telefono' => $cliente->cli_telefono,
            'correo' => $cliente->cli_correo,
            'cli_correo' => $cliente->cli_correo,
            'rol' => 'Cliente',
            'tipo_usuario' => 3,
            'estado' => $cliente->cli_estado,
            'cli_estado' => $cliente->cli_estado,
        ];
    }
}
