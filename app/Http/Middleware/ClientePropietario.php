<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ClientePropietario
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $clienteId = $request->route('id');
        
        // Si es administrador, permitir acceso total
        if ($user && isset($user->emp_rol) && $user->emp_rol === 'Administrador') {
            return $next($request);
        }
        
        // Si es cliente, solo puede acceder a su propio perfil
        if ($user && isset($user->id_cliente) && $user->id_cliente == $clienteId) {
            return $next($request);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'No tienes permiso para acceder a este recurso'
        ], 403);
    }
}