<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Rubro;
use Illuminate\Http\Request;
use App\Http\Requests\RubroStoreRequest;
use App\Http\Requests\RubroUpdateRequest; 

class RubroController extends Controller
{
    /**
     * Display a listing of the resource.
     * Muestra la lista de todos los rubros.
     */
    public function index()
    {
        // Retorna todos los rubros. Podrías añadir paginación si la lista es muy grande.
        $rubros = Rubro::orderBy('nombre', 'asc')->get();
        
        // Retorna la colección en formato JSON con un código de estado 200 (OK)
        return response()->json($rubros, 200);
    }

    /**
     * Store a newly created resource in storage.
     * Almacena un nuevo rubro. Utiliza RubroStoreRequest para la validación.
     */
    public function store(RubroStoreRequest $request)
    {
        // Si el código llega aquí, la validación (RubroStoreRequest) ya pasó.
        // Usamos $request->validated() para obtener solo los campos validados.
        
        $rubro = Rubro::create($request->validated());
        
        // Retorna el rubro recién creado con un código de estado 201 (Created)
        return response()->json([
            'message' => 'Rubro creado exitosamente',
            'rubro' => $rubro
        ], 201);
    }

    /**
     * Display the specified resource.
     * Muestra un rubro específico.
     */
    public function show(Rubro $rubro)
    {
        // Laravel automáticamente resuelve el Rubro (Route Model Binding)
        // Retorna el rubro con un código de estado 200 (OK)
        return response()->json($rubro, 200);
    }

    /**
     * Update the specified resource in storage.
     * Actualiza un rubro existente. (Usaremos un Update Request similar al Store).
     */
    // *NOTA: Debes crear un RubroUpdateRequest y adaptar la regla 'unique' para ignorar el ID actual.
    public function update(RubroUpdateRequest $request, Rubro $rubro)
    {
        // La validación (RubroUpdateRequest) ya pasó.
        $rubro->update($request->validated());

        // Retorna el rubro actualizado con un código de estado 200 (OK)
        return response()->json([
            'message' => 'Rubro actualizado exitosamente',
            'rubro' => $rubro
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     * Elimina un rubro específico.
     */
    public function destroy(Rubro $rubro)
    {
        // Antes de eliminar, idealmente se debería verificar que no tenga empresas relacionadas
        // (Aunque las Foreign Keys de la DB ya lo manejan con RESTRICT/CASCADE).
        
        try {
            $rubro->delete();
            
            // Retorna un mensaje de éxito con un código de estado 204 (No Content)
            return response()->json(null, 204);

        } catch (\Illuminate\Database\QueryException $e) {
            // Manejo de error si hay registros relacionados (ej: empresas)
            return response()->json([
                'message' => 'No se puede eliminar el rubro porque tiene empresas asociadas.'
            ], 409); // Código 409: Conflict
        }
    }
}