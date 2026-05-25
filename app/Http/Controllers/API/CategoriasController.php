<?php

namespace App\Http\Controllers\API;

use App\Models\Categorias;
use App\Support\EnsureCatalogTables;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoriasController extends Controller
{
    // LISTAR TODAS LAS CATEGORÍAS
    public function index()
    {
        EnsureCatalogTables::ensure();
        return response()->json(Categorias::all(), 200);
    }

    // CREAR CATEGORÍA
    public function store(Request $request)
    {
        EnsureCatalogTables::ensure();
        // Se crean los registros usando cat_nombre y cat_descripcion definidos en el fillable
        $categoria = Categorias::create($request->all());

        return response()->json([
            'message' => 'Categoría creada con éxito',
            'data' => $categoria
        ], 201);
    }

    // MOSTRAR UNA CATEGORÍA POR ID
    public function show($id)
    {   
        // findOrFail buscará automáticamente por 'id_categoria' 
        // porque así lo definiste en el modelo.
        return response()->json(
            Categorias::findOrFail($id)
        );
    }

    // ACTUALIZAR CATEGORÍA
    public function update(Request $request, $id)
{
    $categoria = Categorias::findOrFail($id);
    $categoria->update($request->only(['cat_nombre', 'cat_descripcion']));
    
    return response()->json([
        'success' => true,
        'data' => $categoria,
        'message' => 'Categoría actualizada correctamente'
    ]);
}

    // ELIMINAR CATEGORÍA
    public function destroy($id)
    {
        Categorias::destroy($id);

        return response()->json([
            'message' => 'Categoría eliminada'
        ], 200);
    }
}
