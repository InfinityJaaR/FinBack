<?php

namespace App\Http\Controllers;

use App\Models\RatioDefinicion;
use App\Models\ConceptoFinanciero; // Necesario si se desea relacionar componentes en create/edit
use App\Http\Requests\StoreRatioDefinicionRequest;
use App\Http\Requests\UpdateRatioDefinicionRequest;
use Illuminate\Http\JsonResponse;
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
     * Muestra el formulario para crear un nuevo recurso.
     * (Simulamos los datos necesarios: Conceptos disponibles)
     */
    public function create(): JsonResponse
    {
        // Útil para poblar el formulario con los conceptos que pueden ser componentes del ratio
        $conceptos = ConceptoFinanciero::select('id', 'nombre_concepto')->get();
        
        return response()->json([
            'success' => true,
            'conceptos_disponibles' => $conceptos
        ]);
    }

    /**
     * Almacena un recurso recién creado en la base de datos.
     *
     * @param \App\Http\Requests\StoreRatioDefinicionRequest $request
     */
    public function store(StoreRatioDefinicionRequest $request): JsonResponse
    {
        try {
            // La validación ocurre automáticamente
            $ratio = RatioDefinicion::create($request->validated());
            
            // Nota: Aquí se necesitaría lógica adicional para asociar los 'componentes'
            // con la tabla pivote 'ratio_componentes', que no está incluida en este request base.

            return response()->json([
                'success' => true,
                'message' => 'Definición de Ratio creada exitosamente.',
                'data' => $ratio
            ], 201); // 201 Created

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la definición de ratio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Muestra el recurso especificado.
     *
     * @param \App\Models\RatioDefinicion $ratioDefinicion
     */
    public function show(RatioDefinicion $ratioDefinicion): JsonResponse
    {
        // Carga los componentes y los benchmarks relacionados
        $ratioDefinicion->load(['componentes', 'benchmarks']); 
        
        return response()->json([
            'success' => true,
            'data' => $ratioDefinicion
        ]);
    }

    /**
     * Muestra el formulario para editar el recurso especificado.
     * (Simulamos los datos necesarios para la vista de edición)
     *
     * @param \App\Models\RatioDefinicion $ratioDefinicion
     */
    public function edit(RatioDefinicion $ratioDefinicion): JsonResponse
    {
        $conceptos = ConceptoFinanciero::select('id', 'nombre_concepto')->get();
        
        // Carga los componentes actuales del ratio
        $ratioDefinicion->load('componentes');
        
        return response()->json([
            'success' => true,
            'ratio_definicion' => $ratioDefinicion,
            'conceptos_disponibles' => $conceptos
        ]);
    }

    /**
     * Actualiza el recurso especificado en la base de datos.
     *
     * @param \App\Http\Requests\UpdateRatioDefinicionRequest $request
     * @param \App\Models\RatioDefinicion $ratioDefinicion
     */
    public function update(UpdateRatioDefinicionRequest $request, RatioDefinicion $ratioDefinicion): JsonResponse
    {
        try {
            // La validación ocurre automáticamente
            $ratioDefinicion->update($request->validated());

            // Nota: Aquí se necesitaría lógica adicional para actualizar/sincronizar los 'componentes'.

            return response()->json([
                'success' => true,
                'message' => 'Definición de Ratio actualizada exitosamente.',
                'data' => $ratioDefinicion
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la definición de ratio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina el recurso especificado de la base de datos.
     *
     * @param \App\Models\RatioDefinicion $ratioDefinicion
     */
    public function destroy(RatioDefinicion $ratioDefinicion): JsonResponse
    {
        try {
            $ratioDefinicion->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Definición de Ratio eliminada exitosamente.'
            ], 200);

        } catch (Exception $e) {
            // La eliminación en cascada debería manejar 'benchmarks_rubro' y 'ratios_valores'
            // pero si hay otras restricciones no consideradas, fallaría.
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la definición de ratio. Revise sus dependencias: ' . $e->getMessage()
            ], 500);
        }
    }
}