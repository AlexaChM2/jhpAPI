<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    /**
     * Listar todos los clientes
     */
    public function index(Request $request)
    {
        $query = Cliente::query();

        // Filtro por búsqueda
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('cli_nombre', 'LIKE', "%{$search}%")
                  ->orWhere('cli_apaterno', 'LIKE', "%{$search}%")
                  ->orWhere('cli_amaterno', 'LIKE', "%{$search}%")
                  ->orWhere('cli_correo', 'LIKE', "%{$search}%")
                  ->orWhere('cli_telefono', 'LIKE', "%{$search}%");
            });
        }

        // Filtro por estado
        if ($request->has('estado') && $request->estado != '') {
            $query->where('cli_estado', $request->estado);
        }

        $clientes = $query->orderBy('cli_apaterno')
                          ->orderBy('cli_nombre')
                          ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $clientes->items(),
            'pagination' => [
                'total' => $clientes->total(),
                'per_page' => $clientes->perPage(),
                'current_page' => $clientes->currentPage(),
                'last_page' => $clientes->lastPage(),
                'from' => $clientes->firstItem(),
                'to' => $clientes->lastItem()
            ]
        ]);
    }

    /**
     * Registrar nuevo cliente
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cli_nombre' => 'required|string|max:100',
            'cli_apaterno' => 'required|string|max:50',
            'cli_amaterno' => 'nullable|string|max:50',
            'cli_telefono' => 'nullable|string|max:15',
            'cli_correo' => 'required|email|unique:clientes,cli_correo',
            'cli_direccion' => 'nullable|string',
            'cli_password' => 'required|string|min:6|confirmed',
        ], [
            'cli_nombre.required' => 'El nombre es obligatorio',
            'cli_apaterno.required' => 'El apellido paterno es obligatorio',
            'cli_correo.required' => 'El correo es obligatorio',
            'cli_correo.unique' => 'Este correo ya está registrado',
            'cli_password.required' => 'La contraseña es obligatoria',
            'cli_password.min' => 'La contraseña debe tener al menos 6 caracteres',
            'cli_password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['cli_password'] = Hash::make($request->cli_password);
        $data['tipo_usuario'] = 3;
        
        $cliente = Cliente::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Cliente registrado exitosamente',
            'data' => $cliente
        ], 201);
    }

    /**
     * Mostrar un cliente específico
     */
    public function show($id)
    {
        $cliente = Cliente::find($id);
        
        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $cliente
        ]);
    }

    /**
     * Actualizar cliente
     */
    public function update(Request $request, $id)
    {
        $cliente = Cliente::find($id);
        
        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'cli_nombre' => 'sometimes|string|max:100',
            'cli_apaterno' => 'sometimes|string|max:50',
            'cli_amaterno' => 'nullable|string|max:50',
            'cli_telefono' => 'nullable|string|max:15',
            'cli_correo' => [
                'sometimes',
                'email',
                Rule::unique('clientes', 'cli_correo')->ignore($id, 'id_cliente'),
            ],
            'cli_direccion' => 'nullable|string',
            'cli_password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['cli_password', 'cli_password_confirmation']);
        
        if ($request->filled('cli_password')) {
            $data['cli_password'] = Hash::make($request->cli_password);
        }
        
        $cliente->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cliente actualizado exitosamente',
            'data' => $cliente->fresh()
        ]);
    }

    /**
     * Eliminar cliente
     */
    public function destroy($id)
    {
        $cliente = Cliente::find($id);
        
        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        $cliente->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado exitosamente'
        ]);
    }

    /**
     * Cambiar estado del cliente
     */
    public function toggleStatus($id)
    {
        $cliente = Cliente::find($id);
        
        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        $nuevoEstado = $cliente->cli_estado === 'Activo' ? 'Inactivo' : 'Activo';
        $cliente->update(['cli_estado' => $nuevoEstado]);

        return response()->json([
            'success' => true,
            'message' => "Cliente {$nuevoEstado} exitosamente",
            'data' => $cliente->fresh()
        ]);
    }
}