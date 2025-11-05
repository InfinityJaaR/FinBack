<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Rubro; // Necesario para 'create' o 'edit'
use App\Http\Requests\StoreEmpresaRequest;
use App\Http\Requests\UpdateEmpresaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function destroy(Request $request, Empresa $empresa): JsonResponse
    {
        // Contar dependencias relevantes
        $cuentasCount = $empresa->catalogoCuentas()->count();
        $estadosCount = $empresa->estados()->count();
        $ratiosCount = $empresa->ratiosValores()->count();
        $ventasCount = $empresa->ventasMensuales()->count();

        // detalles_estado puede venir tanto por estados como por cuentas
        $estadoIds = $empresa->estados()->pluck('id')->toArray();
        $catalogoCuentaIds = $empresa->catalogoCuentas()->pluck('id')->toArray();

        $detallesPorEstados = 0;
        $detallesPorCuentas = 0;
        if (!empty($estadoIds)) {
            $detallesPorEstados = DB::table('detalles_estado')->whereIn('estado_id', $estadoIds)->count();
        }
        if (!empty($catalogoCuentaIds)) {
            $detallesPorCuentas = DB::table('detalles_estado')->whereIn('catalogo_cuenta_id', $catalogoCuentaIds)->count();
        }

        $totalDependencias = $cuentasCount + $estadosCount + $ratiosCount + $ventasCount + $detallesPorEstados + $detallesPorCuentas;

        // Si no hay dependencias, proceder con el borrado normal
        // Si se solicitó borrado forzado pero la empresa sigue activa, rechazar.
        if ($request->boolean('force', false) && $empresa->activo) {
            return response()->json([
                'success' => false,
                'message' => 'Borrado forzado rechazado: desactiva la empresa antes de eliminarla.'
            ], 403);
        }

        if ($totalDependencias === 0 || $request->boolean('force', false)) {
            try {
                // Si se solicita borrado forzado, eliminar dependencias en el orden correcto
                DB::beginTransaction();

                if ($request->boolean('force', false)) {
                    // 1) Borrar detalles por estados
                    if (!empty($estadoIds)) {
                        DB::table('detalles_estado')->whereIn('estado_id', $estadoIds)->delete();
                    }

                    // 2) Borrar detalles por cuentas (por si hay detalles que no dependan de estados)
                    if (!empty($catalogoCuentaIds)) {
                        DB::table('detalles_estado')->whereIn('catalogo_cuenta_id', $catalogoCuentaIds)->delete();
                    }

                    // 3) Borrar cuenta_concepto mapeos para las cuentas de la empresa
                    if (!empty($catalogoCuentaIds)) {
                        DB::table('cuenta_concepto')->whereIn('catalogo_cuenta_id', $catalogoCuentaIds)->delete();
                    }

                    // 4) Borrar estados
                    if (!empty($estadoIds)) {
                        DB::table('estados')->whereIn('id', $estadoIds)->delete();
                    }

                    // 5) Borrar catalogo_cuentas
                    if (!empty($catalogoCuentaIds)) {
                        DB::table('catalogo_cuentas')->whereIn('id', $catalogoCuentaIds)->delete();
                    }

                    // 6) Borrar ratios_valores y ventas_mensuales
                    DB::table('ratios_valores')->where('empresa_id', $empresa->id)->delete();
                    DB::table('ventas_mensuales')->where('empresa_id', $empresa->id)->delete();
                }

                // Finalmente borrar la empresa
                $empresa->delete();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Empresa eliminada exitosamente.'
                ], 200);

            } catch (Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar la empresa: ' . $e->getMessage()
                ], 500);
            }
        }

        // Si llegamos aquí, existen dependencias y no se pidió force: devolver conteo y sugerir desactivación
        return response()->json([
            'success' => false,
            'message' => 'No se puede eliminar la empresa porque tiene datos asociados. Puedes desactivarla o ejecutar el borrado forzado.',
            'details' => [
                'catalogo_cuentas' => $cuentasCount,
                'estados' => $estadosCount,
                'detalles_por_estados' => $detallesPorEstados,
                'detalles_por_cuentas' => $detallesPorCuentas,
                'ratios_valores' => $ratiosCount,
                'ventas_mensuales' => $ventasCount,
            ]
        ], 409);
    }

    /**
     * Desactivar o activar una empresa sin eliminar datos.
     * Si se envía `action=enable` en la request se activará la empresa,
     * por defecto la acción deja la empresa desactivada.
     */
    public function disable(Request $request, Empresa $empresa): JsonResponse
    {
        $action = $request->input('action', 'disable');

        try {
            if ($action === 'enable') {
                $empresa->update(['activo' => true]);
                $message = 'Empresa activada exitosamente.';
            } else {
                $empresa->update(['activo' => false]);
                $message = 'Empresa desactivada exitosamente.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $empresa->fresh()
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estado de la empresa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar usuarios asociados a una empresa.
     *
     * @param \App\Models\Empresa $empresa
     */
    public function usuarios(Empresa $empresa): JsonResponse
    {
        try {
            // Obtener usuarios con sus roles
            $usuarios = $empresa->usuarios()->with('roles')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'empresa' => [
                        'id' => $empresa->id,
                        'nombre' => $empresa->nombre,
                        'codigo' => $empresa->codigo
                    ],
                    'usuarios' => $usuarios,
                    'total_usuarios' => $usuarios->count()
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios de la empresa: ' . $e->getMessage()
            ], 500);
        }
    }

        public function porRubro(\App\Models\Rubro $rubro)
    {
        return $rubro->empresas()->get(['id','nombre']);
    }

}
