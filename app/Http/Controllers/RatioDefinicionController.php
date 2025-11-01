<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RatioDefinicion;
use App\Models\ConceptoFinanciero;
use App\Http\Requests\StoreRatioDefinicionRequest;
use App\Http\Requests\UpdateRatioDefinicionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB; // Importar para transacciones
use Exception;

class RatioDefinicionController extends Controller
{
    /**
     * Muestra una lista paginada de recursos (Ratios Definiciones).
     */
    public function index(): JsonResponse
    {
        try {
            // Se carga la relación 'componentes' con sus datos pivote para información completa.
            // Cargamos la relación Concepto dentro del componente para evitar N+1
            $ratios = RatioDefinicion::with('componentes')->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => $ratios
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de definiciones de ratios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Devuelve los datos necesarios para el formulario de creación.
     */
    public function create(): JsonResponse
    {
        // Útil para poblar el formulario con los conceptos que pueden ser componentes del ratio
        $conceptos = ConceptoFinanciero::select('id', 'nombre_concepto')->orderBy('nombre_concepto')->get();
        
        return response()->json([
            'success' => true,
            'conceptos_disponibles' => $conceptos
        ]);
    }

    /**
     * Almacena un recurso recién creado en la base de datos, incluyendo sus componentes.
     */
    public function store(StoreRatioDefinicionRequest $request): JsonResponse
    {
        // Usamos una transacción para asegurar que la definición y sus componentes se guarden juntos.
        DB::beginTransaction();

        try {
            // 1. Crear la Definición de Ratio (excluyendo el array 'componentes' para la creación inicial)
            $ratio = RatioDefinicion::create($request->except(['componentes']));
            
            // 2. Preparar y Adjuntar los Componentes
            // Mapear los componentes para la tabla pivote (concepto_id => ['rol' => '...', 'orden' => '...'])
            $componentesData = collect($request->input('componentes'))->mapWithKeys(function ($item) {
                return [
                    $item['concepto_id'] => [
                        'rol' => $item['rol'],
                        'orden' => $item['orden']
                    ]
                ];
            })->toArray();
            
            // Usamos sync() para asociar los componentes al ratio recién creado
            $ratio->componentes()->sync($componentesData); 

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Definición de Ratio y componentes creados exitosamente.',
                'data' => $ratio->load('componentes') // Cargar componentes para la respuesta
            ], 201); 

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la definición de ratio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Muestra el recurso especificado.
     */
    public function show(RatioDefinicion $ratioDefinicion): JsonResponse
    {
        // Carga los componentes, sus conceptos y los benchmarks relacionados
    $ratioDefinicion->load(['componentes', 'benchmarks']); 
        
        return response()->json([
            'success' => true,
            'data' => $ratioDefinicion
        ]);
    }

    /**
     * Devuelve el recurso para su edición.
     */
    public function edit(RatioDefinicion $ratioDefinicion): JsonResponse
    {
        $conceptos = ConceptoFinanciero::select('id', 'nombre_concepto')->orderBy('nombre_concepto')->get();
        
        // Carga los componentes actuales del ratio y su concepto relacionado
    $ratioDefinicion->load('componentes');
        
        return response()->json([
            'success' => true,
            'ratio_definicion' => $ratioDefinicion,
            'conceptos_disponibles' => $conceptos
        ]);
    }

    /**
     * Actualiza el recurso especificado en la base de datos, incluyendo sus componentes.
     */
    public function update(UpdateRatioDefinicionRequest $request, RatioDefinicion $ratioDefinicion): JsonResponse
    {
        DB::beginTransaction();

        try {
            // 1. Actualizar la Definición de Ratio
            $ratioDefinicion->update($request->except(['componentes']));
            
            // 2. Sincronizar los Componentes (Crear, Actualizar o Eliminar relaciones en la tabla pivote)
            $componentesData = collect($request->input('componentes'))->mapWithKeys(function ($item) {
                return [
                    $item['concepto_id'] => [
                        'rol' => $item['rol'],
                        'orden' => $item['orden']
                    ]
                ];
            })->toArray();
            
            // Usa sync() para asegurar que solo los componentes enviados permanezcan
            $ratioDefinicion->componentes()->sync($componentesData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Definición de Ratio actualizada exitosamente.',
                'data' => $ratioDefinicion->load('componentes')
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la definición de ratio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina el recurso especificado de la base de datos.
     */
    public function destroy(RatioDefinicion $ratioDefinicion): JsonResponse
    {
        try {
            // Laravel debería eliminar automáticamente los registros en 'ratio_componentes' 
            // gracias a la clave foránea en la tabla pivote.
            $ratioDefinicion->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Definición de Ratio eliminada exitosamente.'
            ], 200);

        } catch (Exception $e) {
            // Manejar errores de restricciones de clave foránea que la BD podría tener
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la definición de ratio. Revise sus dependencias (ej: valores de ratios ya calculados): ' . $e->getMessage()
            ], 500);
        }
    }
}
