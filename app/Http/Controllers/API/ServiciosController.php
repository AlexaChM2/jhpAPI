<?php

namespace App\Http\Controllers\API;

use App\Support\EnsureCatalogTables;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ServiciosController extends Controller
{
    // LISTAR TODOS LOS SERVICIOS
  public function index()
{
    EnsureCatalogTables::ensure();
    
    $servicios = DB::table('servicios')
        ->leftJoin('categorias', 'servicios.id_categoria', '=', 'categorias.id_categoria')
        ->select('servicios.*', 'categorias.cat_nombre as categoria_nombre')
        ->get()
        ->map(function ($item) {
            $item->categoria = (object)[
                'id_categoria' => $item->id_categoria,
                'cat_nombre' => $item->categoria_nombre ?? 'Sin categoría'
            ];
            return $item;
        });

    return response()->json([
        'success' => true,
        'data' => $servicios
    ], 200);
}

    // CREAR UN NUEVO SERVICIO
    public function store(Request $request)
    {
        EnsureCatalogTables::ensure();
        $id = DB::table('servicios')->insertGetId($request->only([
            'ser_nombre',
            'ser_descripcion',
            'ser_precio_mano_obra',
            'id_categoria',
        ]));
        $servicio = DB::table('servicios')->where('id_servicio', $id)->first();

        return response()->json([
            'message' => 'Servicio creado con éxito',
            'data' => $servicio
        ], 201);
    }

    // MOSTRAR UN SERVICIO ESPECÍFICO
    public function show($id)
{
    EnsureCatalogTables::ensure();
    
    $servicio = DB::table('servicios')
        ->leftJoin('categorias', 'servicios.id_categoria', '=', 'categorias.id_categoria')
        ->select('servicios.*', 'categorias.cat_nombre as categoria_nombre')
        ->where('id_servicio', $id)
        ->first();

    if (!$servicio) {
        return response()->json(['message' => 'Servicio no encontrado'], 404);
    }

    $servicio->categoria = (object)[
        'id_categoria' => $servicio->id_categoria,
        'cat_nombre' => $servicio->categoria_nombre ?? 'Sin categoría'
    ];

    return response()->json([
        'success' => true,
        'data' => $servicio
    ], 200);
}
    // ACTUALIZAR DATOS DEL SERVICIO
    public function update(Request $request, $id)
    {
        EnsureCatalogTables::ensure();
        DB::table('servicios')->where('id_servicio', $id)->update($request->only([
            'ser_nombre',
            'ser_descripcion',
            'ser_precio_mano_obra',
            'id_categoria',
        ]));
        $servicio = DB::table('servicios')->where('id_servicio', $id)->first();

        return response()->json([
            'message' => 'Servicio actualizado correctamente',
            'data' => $servicio
        ], 200);
    }

    // ELIMINAR SERVICIO
    public function destroy($id)
    {
        EnsureCatalogTables::ensure();
        DB::table('servicios')->where('id_servicio', $id)->delete();

        return response()->json([
            'message' => 'Servicio eliminado del catálogo'
        ], 200);
    }
}
