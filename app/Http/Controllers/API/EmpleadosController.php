<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmpleadosController extends Controller
{
    /**
     * Listar todos los empleados.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Empleado::query();

        // Filtro por búsqueda
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('emp_nombre', 'LIKE', "%{$search}%")
                  ->orWhere('emp_apaterno', 'LIKE', "%{$search}%")
                  ->orWhere('emp_amaterno', 'LIKE', "%{$search}%")
                  ->orWhere('emp_correo', 'LIKE', "%{$search}%")
                  ->orWhere('emp_telefono', 'LIKE', "%{$search}%");
            });
        }

        // Filtro por rol
        if ($request->has('rol') && $request->rol != '') {
            $query->where('emp_rol', $request->rol);
        }

        // Filtro por estado
        if ($request->has('estado') && $request->estado != '') {
            $query->where('emp_estado', $request->estado);
        }

        $empleados = $query->orderBy('emp_apaterno')
                           ->orderBy('emp_nombre')
                           ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $empleados->items(),
            'pagination' => [
                'total' => $empleados->total(),
                'per_page' => $empleados->perPage(),
                'current_page' => $empleados->currentPage(),
                'last_page' => $empleados->lastPage(),
                'from' => $empleados->firstItem(),
                'to' => $empleados->lastItem()
            ]
        ]);
    }

    /**
     * Almacenar nuevo empleado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = $this->validateEmpleado($request);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['emp_password'] = Hash::make($data['emp_password']);
        
        $empleado = Empleado::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Empleado creado exitosamente',
            'data' => $empleado
        ], 201);
    }

    /**
     * Mostrar un empleado específico.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $empleado = Empleado::find($id);
        
        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $empleado
        ]);
    }

    /**
     * Actualizar empleado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $empleado = Empleado::find($id);
        
        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado'
            ], 404);
        }

        $validator = $this->validateEmpleado($request, $id);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['emp_password', 'password_confirmation']);
        
        // Solo actualizar contraseña si se proporciona
        if ($request->filled('emp_password')) {
            $data['emp_password'] = Hash::make($request->emp_password);
        }
        
        $empleado->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Empleado actualizado exitosamente',
            'data' => $empleado->refresh()
        ]);
    }

    /**
     * Eliminar empleado.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $empleado = Empleado::find($id);
        
        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado'
            ], 404);
        }

        $empleado->delete();

        return response()->json([
            'success' => true,
            'message' => 'Empleado eliminado exitosamente'
        ]);
    }

    /**
     * Cambiar estado del empleado.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus($id)
    {
        $empleado = Empleado::find($id);
        
        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado'
            ], 404);
        }

        $nuevoEstado = $empleado->emp_estado === 'Activo' ? 'Inactivo' : 'Activo';
        $empleado->update(['emp_estado' => $nuevoEstado]);

        return response()->json([
            'success' => true,
            'message' => "Empleado {$nuevoEstado} exitosamente",
            'data' => $empleado->refresh()
        ]);
    }

    /**
     * Validar datos del empleado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|null  $id
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validateEmpleado(Request $request, $id = null)
    {
        $rules = [
            'emp_nombre' => 'required|string|max:50',
            'emp_apaterno' => 'required|string|max:50',
            'emp_amaterno' => 'nullable|string|max:50',
            'emp_telefono' => 'nullable|string|max:15',
            'emp_correo' => [
                'required',
                'email',
                'max:100',
                Rule::unique('Empleados', 'emp_correo')->ignore($id, 'id_empleados'),
            ],
            'emp_direccion' => 'nullable|string',
            'emp_rol' => 'required|in:Administrador,Vendedor,Mecanico',
            'emp_estado' => 'sometimes|in:Activo,Inactivo',
        ];

        // Reglas de contraseña solo para creación o si se proporciona
        if (!$id || $request->filled('emp_password')) {
            $rules['emp_password'] = 'required|string|min:6|confirmed';
        }

        return Validator::make($request->all(), $rules, [
            'emp_nombre.required' => 'El nombre es obligatorio',
            'emp_apaterno.required' => 'El apellido paterno es obligatorio',
            'emp_correo.required' => 'El correo electrónico es obligatorio',
            'emp_correo.unique' => 'Este correo ya está registrado',
            'emp_correo.email' => 'El formato del correo no es válido',
            'emp_rol.required' => 'El rol es obligatorio',
            'emp_rol.in' => 'Rol no válido. Use: Administrador, Vendedor o Mecanico',
            'emp_password.required' => 'La contraseña es obligatoria',
            'emp_password.min' => 'La contraseña debe tener mínimo 6 caracteres',
            'emp_password.confirmed' => 'Las contraseñas no coinciden',
        ]);
    }
}