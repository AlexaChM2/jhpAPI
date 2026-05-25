<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empleados;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function requestToken(Request $request)
    {
        if (!$request->filled('correo') && $request->filled('email')) {
            $request->merge(['correo' => $request->input('email')]);
        }

        $request->validate([
            'correo' => 'required|email',
        ]);

        $user = $this->findUserByEmail($request->correo);

        if (!$user) {
            return response()->json(['message' => 'Correo no registrado'], 404);
        }

        $token = Str::upper(Str::random(6));
        $user->forceFill([
            'reset_password' => $token,
            'reset_password_expires' => now()->addMinutes(15),
        ])->save();

        try {
            Mail::raw(
                "Tu token de recuperación JHP es: {$token}\n\nEste token vence en 15 minutos.",
                function ($message) use ($request) {
                    $message->to($request->correo)->subject('Recuperación de contraseña JHP');
                }
            );
        } catch (\Throwable $exception) {
            Log::warning('No se pudo enviar correo de recuperación.', [
                'correo' => $request->correo,
                'error' => $exception->getMessage(),
            ]);

            if (app()->environment('local')) {
                return response()->json([
                    'message' => 'Token generado. Configura Gmail para enviarlo por correo.',
                    'correo' => $request->correo,
                    'token' => $token,
                ]);
            }

            return response()->json([
                'message' => 'No se pudo enviar el correo de recuperación',
            ], 500);
        }

        return response()->json([
            'message' => 'Token enviado al correo registrado',
            'correo' => $request->correo,
        ]);
    }

    public function validateToken(Request $request)
    {
        if (!$request->filled('correo') && $request->filled('email')) {
            $request->merge(['correo' => $request->input('email')]);
        }

        $request->validate([
            'correo' => 'required|email',
            'token' => 'required|string',
        ]);

        $user = $this->findValidToken($request->correo, $request->token);

        if (!$user) {
            return response()->json(['message' => 'Token invalido o expirado'], 422);
        }

        return response()->json(['message' => 'Token valido']);
    }

    public function reset(Request $request)
    {
        if (!$request->filled('correo') && $request->filled('email')) {
            $request->merge(['correo' => $request->input('email')]);
        }

        $request->validate([
            'correo' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $user = $this->findValidToken($request->correo, $request->token);

        if (!$user) {
            return response()->json(['message' => 'Token invalido o expirado'], 422);
        }

        $passwordField = $user instanceof Cliente ? 'cli_password' : 'emp_password';

        $user->forceFill([
            $passwordField => Hash::make($request->password),
            'reset_password' => null,
            'reset_password_expires' => null,
        ])->save();

        return response()->json(['message' => 'Contraseña actualizada']);
    }

    private function findUserByEmail(string $correo): Empleados|Cliente|null
    {
        return Empleados::where('emp_correo', $correo)->first()
            ?? Cliente::where('cli_correo', $correo)->first();
    }

    private function findValidToken(string $correo, string $token): Empleados|Cliente|null
    {
        return Empleados::where('emp_correo', $correo)
            ->where('reset_password', $token)
            ->where('reset_password_expires', '>', now())
            ->first()
            ?? Cliente::where('cli_correo', $correo)
                ->where('reset_password', $token)
                ->where('reset_password_expires', '>', now())
                ->first();
    }
}
