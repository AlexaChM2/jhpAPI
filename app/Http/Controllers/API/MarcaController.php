<?php

namespace App\Http\Controllers\API;

use App\Models\Marca;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class MarcaController extends Controller
{
    /**
     * Listar todas las marcas
     */
    public function index(Request $request)
    {
        $query = Marca::query();

        // Filtro por estado
        if ($request->has('estado') && $request->estado != '') {
            $query->where('mar_estado', $request->estado);
        }

        // Búsqueda por nombre
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('mar_nombre', 'LIKE', "%{$search}%")
                  ->orWhere('mar_descripcion', 'LIKE', "%{$search}%");
            });
        }

        $marcas = $query->orderBy('mar_nombre')
                        ->withCount('productos')  // Contar productos asociados
                        ->get();

        return response()->json([
            'success' => true,
            'data' => $marcas,
            'total' => $marcas->count()
        ], 200);
    }

    /**
     * Listar solo marcas activas (para dropdowns)
     */
    public function activas()
    {
        $marcas = Marca::where('mar_estado', 'Activo')
                       ->orderBy('mar_nombre')
                       ->get(['id_marca', 'mar_nombre']);

        return response()->json([
            'success' => true,
            'data' => $marcas
        ], 200);
    }

    /**
     * Crear nueva marca
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mar_nombre' => 'required|string|max:50|unique:marcas,mar_nombre',
            'mar_descripcion' => 'nullable|string|max:500',
            'mar_estado' => 'nullable|in:Activo,Inactivo',
        ], [
            'mar_nombre.required' => 'El nombre de la marca es obligatorio',
            'mar_nombre.max' => 'El nombre no debe exceder 50 caracteres',
            'mar_nombre.unique' => 'Esta marca ya existe',
            'mar_descripcion.max' => 'La descripción no debe exceder 500 caracteres',
            'mar_estado.in' => 'Estado no válido, use Activo o Inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $marca = Marca::create([
            'mar_nombre' => $request->mar_nombre,
            'mar_descripcion' => $request->mar_descripcion,
            'mar_estado' => $request->mar_estado ?? 'Activo',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Marca creada exitosamente',
            'data' => $marca
        ], 201);
    }

    /**
     * Mostrar una marca específica
     */
    public function show($id)
    {
        $marca = Marca::withCount('productos')
                      ->with(['productos' => function($query) {
                          $query->select('id_producto', 'pro_codigo', 'pro_nombre', 'pro_stock', 'id_marca')
                                ->orderBy('pro_nombre');
                      }])
                      ->find($id);
        
        if (!$marca) {
            return response()->json([
                'success' => false,
                'message' => 'Marca no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $marca
        ], 200);
    }

    /**
     * Actualizar marca
     */
    public function update(Request $request, $id)
    {
        $marca = Marca::find($id);
        
        if (!$marca) {
            return response()->json([
                'success' => false,
                'message' => 'Marca no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'mar_nombre' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('marcas', 'mar_nombre')->ignore($id, 'id_marca'),
            ],
            'mar_descripcion' => 'nullable|string|max:500',
            'mar_estado' => 'nullable|in:Activo,Inactivo',
        ], [
            'mar_nombre.unique' => 'Esta marca ya existe',
            'mar_nombre.max' => 'El nombre no debe exceder 50 caracteres',
            'mar_descripcion.max' => 'La descripción no debe exceder 500 caracteres',
            'mar_estado.in' => 'Estado no válido, use Activo o Inactivo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $marca->update($request->only(['mar_nombre', 'mar_descripcion', 'mar_estado']));

        return response()->json([
            'success' => true,
            'message' => 'Marca actualizada correctamente',
            'data' => $marca->fresh()->loadCount('productos')
        ], 200);
    }

    /**
     * Eliminar marca
     */
    public function destroy($id)
    {
        $marca = Marca::withCount('productos')->find($id);
        
        if (!$marca) {
            return response()->json([
                'success' => false,
                'message' => 'Marca no encontrada'
            ], 404);
        }

        // Verificar si tiene productos asociados
        if ($marca->productos_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar la marca porque tiene {$marca->productos_count} producto(s) asociado(s). Desvincule o elimine los productos primero."
            ], 422);
        }

        $marca->delete();

        return response()->json([
            'success' => true,
            'message' => 'Marca eliminada correctamente'
        ], 200);
    }

    /**
     * Cambiar estado (Activar/Desactivar)
     */
    public function toggleEstado($id)
    {
        $marca = Marca::find($id);
        
        if (!$marca) {
            return response()->json([
                'success' => false,
                'message' => 'Marca no encontrada'
            ], 404);
        }

        $nuevoEstado = $marca->mar_estado === 'Activo' ? 'Inactivo' : 'Activo';
        $marca->update(['mar_estado' => $nuevoEstado]);

        return response()->json([
            'success' => true,
            'message' => "Marca " . strtolower($nuevoEstado === 'Activo' ? 'activada' : 'desactivada') . " correctamente",
            'data' => $marca
        ], 200);
    }
}