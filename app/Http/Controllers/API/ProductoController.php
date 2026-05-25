<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    /**
     * Listar todos los productos
     */
    public function index(Request $request)
    {
        $query = Producto::with(['categoria', 'proveedor', 'marca']);  // ← Agregada relación marca

        // Filtro por búsqueda
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('pro_codigo', 'LIKE', "%{$search}%")
                  ->orWhere('pro_nombre', 'LIKE', "%{$search}%")
                  ->orWhere('pro_marca', 'LIKE', "%{$search}%")
                  ->orWhere('pro_tipo', 'LIKE', "%{$search}%")
                  ->orWhereHas('marca', function($qMarca) use ($search) {  // ← Buscar por nombre de marca
                      $qMarca->where('mar_nombre', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filtro por categoría
        if ($request->has('categoria') && $request->categoria != '') {
            $query->where('id_categoria', $request->categoria);
        }

        // Filtro por proveedor
        if ($request->has('proveedor') && $request->proveedor != '') {
            $query->where('id_proveedor', $request->proveedor);
        }

        // Filtro por marca (NUEVO)
        if ($request->has('marca') && $request->marca != '') {
            $query->where('id_marca', $request->marca);
        }

        $productos = $query->orderBy('pro_nombre')
                           ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $productos->items(),
            'pagination' => [
                'total' => $productos->total(),
                'per_page' => $productos->perPage(),
                'current_page' => $productos->currentPage(),
                'last_page' => $productos->lastPage(),
                'from' => $productos->firstItem(),
                'to' => $productos->lastItem()
            ]
        ], 200);
    }

    /**
     * Crear nuevo producto
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pro_codigo' => 'required|string|max:50|unique:producto,pro_codigo',
            'pro_nombre' => 'required|string|max:100',
            'pro_tipo' => 'nullable|string|max:50',
            'pro_marca' => 'nullable|string|max:50',
            'id_marca' => 'nullable|exists:marcas,id_marca',  // ← NUEVO
            'pro_descripcion' => 'nullable|string',
            'pro_precio_venta' => 'required|numeric|min:0',
            'pro_stock' => 'nullable|integer|min:0',
            'id_categoria' => 'nullable|exists:categorias,id_categoria',
            'id_proveedor' => 'nullable|exists:proveedores,id_proveedor',
        ], [
            'pro_codigo.required' => 'El código del producto es obligatorio',
            'pro_codigo.unique' => 'Este código ya existe',
            'pro_nombre.required' => 'El nombre del producto es obligatorio',
            'pro_precio_venta.required' => 'El precio de venta es obligatorio',
            'pro_precio_venta.numeric' => 'El precio debe ser un número',
            'id_categoria.exists' => 'La categoría seleccionada no existe',
            'id_proveedor.exists' => 'El proveedor seleccionado no existe',
            'id_marca.exists' => 'La marca seleccionada no existe',  // ← NUEVO
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $producto = Producto::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Producto registrado exitosamente',
            'data' => $producto->load(['categoria', 'proveedor', 'marca'])  // ← Agregada marca
        ], 201);
    }

    /**
     * Mostrar un producto específico
     */
    public function show($id)
    {
        $producto = Producto::with(['categoria', 'proveedor', 'marca'])->find($id);  // ← Agregada marca
        
        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $producto
        ], 200);
    }

    /**
     * Actualizar producto
     */
    public function update(Request $request, $id)
    {
        $producto = Producto::find($id);
        
        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'pro_codigo' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('producto', 'pro_codigo')->ignore($id, 'id_producto'),
            ],
            'pro_nombre' => 'sometimes|string|max:100',
            'pro_tipo' => 'nullable|string|max:50',
            'pro_marca' => 'nullable|string|max:50',
            'id_marca' => 'nullable|exists:marcas,id_marca',  // ← NUEVO
            'pro_descripcion' => 'nullable|string',
            'pro_precio_venta' => 'sometimes|numeric|min:0',
            'pro_stock' => 'nullable|integer|min:0',
            'id_categoria' => 'nullable|exists:categorias,id_categoria',
            'id_proveedor' => 'nullable|exists:proveedores,id_proveedor',
        ], [
            'id_marca.exists' => 'La marca seleccionada no existe',  // ← NUEVO
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $producto->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado correctamente',
            'data' => $producto->fresh(['categoria', 'proveedor', 'marca'])  // ← Agregada marca
        ], 200);
    }

    /**
     * Eliminar producto
     */
    public function destroy($id)
    {
        $producto = Producto::find($id);
        
        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        // Verificar si el producto está en ventas o mantenimientos
        $tieneVentas = \App\Models\Detalle_ventas::where('id_producto', $id)->exists();
        $tieneMantenimientos = \App\Models\DetalleMantenimientoInsumo::where('id_producto', $id)->exists();
        
        if ($tieneVentas || $tieneMantenimientos) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el producto porque tiene ventas o mantenimientos asociados. Considere desactivarlo en lugar de eliminarlo.'
            ], 422);
        }

        $producto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado del inventario'
        ], 200);
    }

    /**
     * Buscar productos por código o nombre
     */
    public function search(Request $request)
    {
        $search = $request->get('q');
        
        if (!$search) {
            return response()->json([
                'success' => false,
                'message' => 'Término de búsqueda requerido'
            ], 422);
        }

        $productos = Producto::with(['categoria', 'proveedor', 'marca'])  // ← Agregada marca
            ->where('pro_codigo', 'LIKE', "%{$search}%")
            ->orWhere('pro_nombre', 'LIKE', "%{$search}%")
            ->orWhere('pro_marca', 'LIKE', "%{$search}%")
            ->orWhereHas('marca', function($q) use ($search) {  // ← Buscar por nombre de marca
                $q->where('mar_nombre', 'LIKE', "%{$search}%");
            })
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $productos,
            'total' => $productos->count()
        ]);
    }
}