<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Marca;
use Illuminate\Http\Request;

class InventarioController extends Controller
{
    // LISTAR INVENTARIO
    public function index()
    {
        $inventario = Inventario::with(['producto.marca', 'proveedor'])
            ->orderBy('nombre_producto')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $inventario
        ], 200);
    }

    // CREAR / SUMAR INVENTARIO
    public function store(Request $request)
    {
        $request->validate([
            'id_producto' => 'nullable|exists:producto,id_producto',
            'codigo_producto' => 'required|string|max:50',
            'nombre_producto' => 'required|string|max:100',
            'marca' => 'nullable|string|max:50',
            'id_marca' => 'nullable|exists:marcas,id_marca',  // ← NUEVO
            'categoria' => 'nullable|string|max:50',
            'stock' => 'required|integer|min:1',
            'precio_unitario' => 'required|numeric|min:0',
            'iva' => 'nullable|numeric|min:0',
            'id_proveedor' => 'nullable|exists:proveedores,id_proveedor',
            'proveedor' => 'nullable|string|max:100',
        ]);

        $data = $this->normalizar($request->all());
        $inventario = $this->sumarOActualizar($data);

        return response()->json([
            'success' => true,
            'message' => 'Inventario actualizado correctamente',
            'data' => $inventario->load(['producto.marca', 'proveedor']),
        ], 201);
    }

    // MOSTRAR INVENTARIO ESPECÍFICO
    public function show($id)
    {
        $inventario = Inventario::with(['producto.marca', 'proveedor'])->find($id);
        
        if (!$inventario) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de inventario no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $inventario
        ], 200);
    }

    // ACTUALIZAR INVENTARIO
    public function update(Request $request, $id)
    {
        $request->validate([
            'codigo_producto' => 'nullable|string|max:50',
            'nombre_producto' => 'nullable|string|max:100',
            'marca' => 'nullable|string|max:50',
            'id_marca' => 'nullable|exists:marcas,id_marca',  // ← NUEVO
            'categoria' => 'nullable|string|max:50',
            'stock' => 'nullable|integer|min:0',
            'precio_unitario' => 'nullable|numeric|min:0',
            'iva' => 'nullable|numeric|min:0',
            'id_proveedor' => 'nullable|exists:proveedores,id_proveedor',
            'proveedor' => 'nullable|string|max:100',
        ]);

        $inventario = Inventario::find($id);
        
        if (!$inventario) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de inventario no encontrado'
            ], 404);
        }

        $data = $this->normalizar($request->all());
        
        // Recalcular precio_total
        $precio = $data['precio_unitario'] ?? $inventario->precio_unitario;
        $iva = $data['iva'] ?? $inventario->iva;
        $data['precio_total'] = $precio + $iva;

        $inventario->update($data);
        $this->syncProducto($inventario);

        return response()->json([
            'success' => true,
            'message' => 'Inventario actualizado correctamente',
            'data' => $inventario->fresh()->load(['producto.marca', 'proveedor']),
        ], 200);
    }

    // ELIMINAR INVENTARIO
    public function destroy($id)
    {
        $inventario = Inventario::find($id);
        
        if (!$inventario) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de inventario no encontrado'
            ], 404);
        }

        $inventario->delete();

        return response()->json([
            'success' => true,
            'message' => 'Registro de inventario eliminado'
        ], 200);
    }

    // SUMAR O ACTUALIZAR INVENTARIO (MÉTODO PÚBLICO)
    public static function sumarOActualizar(array $data): Inventario
    {
        $codigo = $data['codigo_producto'] ?? null;
        $marca = $data['marca'] ?? null;
        $categoria = $data['categoria'] ?? null;
        $id_marca = $data['id_marca'] ?? null;  // ← NUEVO

        // Buscar si ya existe
        $inventario = Inventario::where('codigo_producto', $codigo)
            ->where(function ($query) use ($marca, $id_marca) {
                if ($marca) {
                    $query->where('marca', $marca);
                }
                if ($id_marca) {
                    $query->where('id_marca', $id_marca);
                }
            })
            ->first();

        $cantidad = (int) ($data['stock'] ?? $data['cantidad'] ?? 0);
        $precio = (float) ($data['precio_unitario'] ?? 0);
        $iva = (float) ($data['iva'] ?? 0);

        if ($inventario) {
            // Actualizar existente
            $inventario->fill([
                'nombre_producto' => $data['nombre_producto'] ?? $inventario->nombre_producto,
                'stock' => $inventario->stock + $cantidad,
                'precio_unitario' => $precio ?: $inventario->precio_unitario,
                'iva' => $iva,
                'precio_total' => ($precio ?: $inventario->precio_unitario) + $iva,
                'id_producto' => $data['id_producto'] ?? $inventario->id_producto,
                'id_proveedor' => $data['id_proveedor'] ?? $inventario->id_proveedor,
                'proveedor' => $data['proveedor'] ?? $inventario->proveedor,
                'marca' => $marca ?? $inventario->marca,
                'id_marca' => $id_marca ?? $inventario->id_marca,  // ← NUEVO
            ])->save();
        } else {
            // Crear nuevo
            $inventario = Inventario::create([
                'id_producto' => $data['id_producto'] ?? null,
                'codigo_producto' => $codigo,
                'nombre_producto' => $data['nombre_producto'] ?? 'Producto',
                'marca' => $marca,
                'id_marca' => $id_marca,  // ← NUEVO
                'categoria' => $categoria,
                'stock' => $cantidad,
                'precio_unitario' => $precio,
                'iva' => $iva,
                'precio_total' => $precio + $iva,
                'id_proveedor' => $data['id_proveedor'] ?? null,
                'proveedor' => $data['proveedor'] ?? null,
            ]);
        }

        // Sincronizar con tabla producto
        (new self())->syncProducto($inventario);
        
        return $inventario;
    }

    // DESCONTAR STOCK (USADO POR VENTAS Y MANTENIMIENTO)
    public static function descontarStock(int $idProducto, int $cantidad): void
    {
        $inventario = Inventario::where('id_producto', $idProducto)
            ->lockForUpdate()
            ->first();

        // Si no existe en inventario, crearlo desde producto
        if (!$inventario) {
            $producto = Producto::find($idProducto);
            if ($producto) {
                $inventario = self::sumarOActualizar([
                    'id_producto' => $producto->id_producto,
                    'codigo_producto' => $producto->pro_codigo,
                    'nombre_producto' => $producto->pro_nombre,
                    'marca' => $producto->marca ? $producto->marca->mar_nombre : $producto->pro_marca,
                    'id_marca' => $producto->id_marca,  // ← NUEVO
                    'categoria' => $producto->categoria ? $producto->categoria->cat_nombre : null,
                    'stock' => (int) $producto->pro_stock,
                    'precio_unitario' => (float) $producto->pro_precio_venta,
                    'iva' => (float) ($producto->pro_iva ?? 0),
                ]);
            }
        }

        if (!$inventario || $inventario->stock < $cantidad) {
            abort(response()->json([
                'success' => false,
                'message' => 'Stock insuficiente para realizar la operación',
                'stock_disponible' => $inventario->stock ?? 0,
                'cantidad_solicitada' => $cantidad
            ], 422));
        }

        $inventario->decrement('stock', $cantidad);
        (new self())->syncProducto($inventario->fresh());
    }

    // NORMALIZAR DATOS DE ENTRADA
    private function normalizar(array $data): array
    {
        return [
            'id_producto' => $data['id_producto'] ?? null,
            'codigo_producto' => $data['codigo_producto'] ?? $data['pro_codigo'] ?? $data['prod_codigo'] ?? null,
            'nombre_producto' => $data['nombre_producto'] ?? $data['pro_nombre'] ?? $data['prod_nombre'] ?? null,
            'marca' => $data['marca'] ?? $data['pro_marca'] ?? $data['prod_marca'] ?? null,
            'id_marca' => $data['id_marca'] ?? $data['pro_id_marca'] ?? $data['prod_id_marca'] ?? null,  // ← NUEVO
            'categoria' => $data['categoria'] ?? $data['pro_categoria'] ?? $data['prod_categoria'] ?? null,
            'stock' => $data['stock'] ?? $data['cantidad'] ?? $data['pro_stock'] ?? $data['prod_stock'] ?? 0,
            'precio_unitario' => $data['precio_unitario'] ?? $data['pro_precio_venta'] ?? $data['prod_precio'] ?? 0,
            'iva' => $data['iva'] ?? $data['pro_iva'] ?? 0,
            'id_proveedor' => $data['id_proveedor'] ?? null,
            'proveedor' => $data['proveedor'] ?? $data['pro_proveedor'] ?? $data['prod_proveedor'] ?? null,
        ];
    }

    // SINCRONIZAR CON TABLA PRODUCTO
    private function syncProducto(Inventario $inventario): void
    {
        if (!$inventario->id_producto) {
            return;
        }

        Producto::where('id_producto', $inventario->id_producto)->update([
            'pro_stock' => $inventario->stock,
            'pro_precio_venta' => $inventario->precio_unitario,
            'pro_marca' => $inventario->marca,
            'id_marca' => $inventario->id_marca,  // ← NUEVO
            'pro_categoria' => $inventario->categoria,
            'pro_proveedor' => $inventario->proveedor,
        ]);
    }
}