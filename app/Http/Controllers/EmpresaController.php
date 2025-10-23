<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Rubro; // Necesario para 'create' o 'edit'
use App\Http\Requests\StoreEmpresaRequest;
use App\Http\Requests\UpdateEmpresaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class EmpresaController extends Controller
{
    /**
     * Muestra una lista paginada de recursos (Empresas).
     */
    public function index(): JsonResponse
    {
        try {
            // Carga la relación 'rubro' para evitar N+1
            $empresas = Empresa::with('rubro')->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => $empresas
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de empresas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Muestra el formulario para crear un nuevo recurso.
     * (Usualmente devuelve una vista, aquí simulamos los datos necesarios)
     */
    public function create(): JsonResponse
    {
        // Se devuelven los rubros disponibles para el formulario de creación
        $rubros = Rubro::select('id', 'nombre')->get();
        
        return response()->json([
            'success' => true,
            'rubros_disponibles' => $rubros
        ]);
    }

    /**
     * Almacena un recurso recién creado en la base de datos.
     *
     * @param \App\Http\Requests\StoreEmpresaRequest $request
     */
    public function store(StoreEmpresaRequest $request): JsonResponse
    {
        try {
            // La validación ocurre automáticamente gracias al StoreEmpresaRequest
            $empresa = Empresa::create($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Empresa creada exitosamente.',
                'data' => $empresa
            ], 201); // 201 Created

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la empresa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Muestra el recurso especificado.
     *
     * @param \App\Models\Empresa $empresa - Inyección de modelo (Route Model Binding)
     */
    public function show(Empresa $empresa): JsonResponse
    {
        // Carga la relación rubro para mostrar información completa
        $empresa->load('rubro'); 
        
        return response()->json([
            'success' => true,
            'data' => $empresa
        ]);
    }

    /**
     * Muestra el formulario para editar el recurso especificado.
     * (Simulamos los datos necesarios para la vista de edición)
     *
     * @param \App\Models\Empresa $empresa
     */
    public function edit(Empresa $empresa): JsonResponse
    {
        $rubros = Rubro::select('id', 'nombre')->get();
        
        return response()->json([
            'success' => true,
            'empresa' => $empresa,
            'rubros_disponibles' => $rubros
        ]);
    }

    /**
     * Actualiza el recurso especificado en la base de datos.
     *
     * @param \App\Http\Requests\UpdateEmpresaRequest $request
     * @param \App\Models\Empresa $empresa
     */
    public function update(UpdateEmpresaRequest $request, Empresa $empresa): JsonResponse
    {
        try {
            // La validación ocurre automáticamente gracias al UpdateEmpresaRequest
            $empresa->update($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Empresa actualizada exitosamente.',
                'data' => $empresa
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la empresa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina el recurso especificado de la base de datos.
     *
     * @param \App\Models\Empresa $empresa
     */
    public function destroy(Empresa $empresa): JsonResponse
    {
        try {
            $empresa->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Empresa eliminada exitosamente.'
            ], 200);

        } catch (Exception $e) {
             // Si hay restricciones de clave foránea (ON DELETE RESTRICT), esto fallará
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la empresa. Podría tener datos asociados (cuentas, estados, etc.): ' . $e->getMessage()
            ], 500);
        }
    }
}
