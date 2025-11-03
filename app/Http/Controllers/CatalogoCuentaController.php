<?php

namespace App\Http\Controllers;

use App\Models\CatalogoCuenta;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CatalogoCuentaController extends Controller
{
    /**
     * Obtener el catálogo de cuentas de una empresa específica
     */
    public function index($empresaId)
    {
        try {
            $user = auth()->user();
            
            // Si es Analista Financiero, solo puede ver la empresa asociada
            if ($this->esAnalistaFinanciero($user)) {
                if (!$user->empresa_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes una empresa asociada'
                    ], 403);
                }
                
                if ($user->empresa_id != $empresaId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para ver el catálogo de esta empresa'
                    ], 403);
                }
            }
            
            // Verificar que la empresa existe
            $empresa = Empresa::findOrFail($empresaId);

            // Obtener todas las cuentas de la empresa
            $cuentas = CatalogoCuenta::where('empresa_id', $empresaId)
                ->orderBy('codigo')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'empresa' => [
                        'id' => $empresa->id,
                        'nombre' => $empresa->nombre,
                        'ruc' => $empresa->ruc,
                    ],
                    'cuentas' => $cuentas,
                    'total' => $cuentas->count()
                ],
                'message' => 'Catálogo de cuentas obtenido exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el catálogo de cuentas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cargar/reemplazar catálogo completo de una empresa
     */
    public function store(Request $request)
    {
        // Validación de datos
        $validator = Validator::make($request->all(), [
            'empresa_id' => 'required|exists:empresas,id',
            'cuentas' => 'required|array|min:1',
            'cuentas.*.codigo' => 'required|string|max:50',
            'cuentas.*.nombre' => 'required|string|max:150',
            'cuentas.*.tipo' => [
                'required',
                Rule::in(['ACTIVO', 'PASIVO', 'PATRIMONIO', 'INGRESO', 'GASTO'])
            ],
            'cuentas.*.es_calculada' => 'sometimes|boolean'
        ], [
            'empresa_id.required' => 'El ID de la empresa es obligatorio',
            'empresa_id.exists' => 'La empresa especificada no existe',
            'cuentas.required' => 'Debe proporcionar al menos una cuenta',
            'cuentas.*.codigo.required' => 'El código de la cuenta es obligatorio',
            'cuentas.*.codigo.max' => 'El código no puede exceder 50 caracteres',
            'cuentas.*.nombre.required' => 'El nombre de la cuenta es obligatorio',
            'cuentas.*.nombre.max' => 'El nombre no puede exceder 150 caracteres',
            'cuentas.*.tipo.required' => 'El tipo de cuenta es obligatorio',
            'cuentas.*.tipo.in' => 'El tipo debe ser: ACTIVO, PASIVO, PATRIMONIO, INGRESO o GASTO'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $empresaId = $request->empresa_id;
        
        // Si es Analista Financiero, solo puede cargar catálogo de su empresa asociada
        if ($this->esAnalistaFinanciero($user)) {
            if (!$user->empresa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes una empresa asociada'
                ], 403);
            }
            
            if ($user->empresa_id != $empresaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para cargar el catálogo de esta empresa'
                ], 403);
            }
        }

        DB::beginTransaction();

        try {
            $cuentas = $request->cuentas;

            // Verificar que no haya códigos duplicados en el request
            $codigos = array_column($cuentas, 'codigo');
            if (count($codigos) !== count(array_unique($codigos))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Existen códigos de cuenta duplicados en el archivo'
                ], 422);
            }

            // Eliminar todas las cuentas existentes de esta empresa
            CatalogoCuenta::where('empresa_id', $empresaId)->delete();

            // Insertar las nuevas cuentas
            $cuentasCreadas = [];
            foreach ($cuentas as $cuenta) {
                $nuevaCuenta = CatalogoCuenta::create([
                    'empresa_id' => $empresaId,
                    'codigo' => $cuenta['codigo'],
                    'nombre' => $cuenta['nombre'],
                    'tipo' => $cuenta['tipo'],
                    'es_calculada' => $cuenta['es_calculada'] ?? false
                ]);
                $cuentasCreadas[] = $nuevaCuenta;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'cuentas_creadas' => count($cuentasCreadas),
                    'cuentas' => $cuentasCreadas
                ],
                'message' => 'Catálogo de cuentas cargado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el catálogo de cuentas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una cuenta específica
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'codigo' => 'sometimes|required|string|max:50',
            'nombre' => 'sometimes|required|string|max:150',
            'tipo' => [
                'sometimes',
                'required',
                Rule::in(['ACTIVO', 'PASIVO', 'PATRIMONIO', 'INGRESO', 'GASTO'])
            ],
            'es_calculada' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cuenta = CatalogoCuenta::findOrFail($id);
            
            $user = auth()->user();
            
            // Si es Analista Financiero, solo puede actualizar cuentas de su empresa asociada
            if ($this->esAnalistaFinanciero($user)) {
                if (!$user->empresa_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes una empresa asociada'
                    ], 403);
                }
                
                if ($user->empresa_id != $cuenta->empresa_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para actualizar cuentas de esta empresa'
                    ], 403);
                }
            }
            
            // Si se está cambiando el código, verificar que no exista otro con ese código en la misma empresa
            if ($request->has('codigo') && $request->codigo !== $cuenta->codigo) {
                $existe = CatalogoCuenta::where('empresa_id', $cuenta->empresa_id)
                    ->where('codigo', $request->codigo)
                    ->where('id', '!=', $id)
                    ->exists();
                
                if ($existe) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ya existe una cuenta con ese código en esta empresa'
                    ], 422);
                }
            }

            $cuenta->update($request->only(['codigo', 'nombre', 'tipo', 'es_calculada']));

            return response()->json([
                'success' => true,
                'data' => $cuenta,
                'message' => 'Cuenta actualizada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cuenta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una cuenta específica
     */
    public function destroy($id)
    {
        try {
            $cuenta = CatalogoCuenta::findOrFail($id);
            
            $user = auth()->user();
            
            // Si es Analista Financiero, solo puede eliminar cuentas de su empresa asociada
            if ($this->esAnalistaFinanciero($user)) {
                if (!$user->empresa_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes una empresa asociada'
                    ], 403);
                }
                
                if ($user->empresa_id != $cuenta->empresa_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para eliminar cuentas de esta empresa'
                    ], 403);
                }
            }
            
            $cuenta->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cuenta eliminada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cuenta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener lista de empresas con su información de catálogo
     */
    public function empresasConCatalogo()
    {
        try {
            $user = auth()->user();
            
            // Si es Analista Financiero, solo puede ver su empresa asociada
            if ($this->esAnalistaFinanciero($user)) {
                if (!$user->empresa_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes una empresa asociada'
                    ], 403);
                }
                
                $empresa = Empresa::withCount('catalogoCuentas')
                    ->where('id', $user->empresa_id)
                    ->first();
                
                if (!$empresa) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Empresa no encontrada'
                    ], 404);
                }
                
                $empresas = collect([[
                    'id' => $empresa->id,
                    'nombre' => $empresa->nombre,
                    'ruc' => $empresa->ruc,
                    'tiene_catalogo' => $empresa->catalogo_cuentas_count > 0,
                    'total_cuentas' => $empresa->catalogo_cuentas_count,
                ]]);
            } else {
                // Administrador puede ver todas las empresas
                $empresas = Empresa::withCount('catalogoCuentas')
                    ->get()
                    ->map(function ($empresa) {
                        return [
                            'id' => $empresa->id,
                            'nombre' => $empresa->nombre,
                            'ruc' => $empresa->ruc,
                            'tiene_catalogo' => $empresa->catalogo_cuentas_count > 0,
                            'total_cuentas' => $empresa->catalogo_cuentas_count,
                        ];
                    });
            }

            return response()->json([
                'success' => true,
                'data' => $empresas,
                'message' => 'Empresas obtenidas exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las empresas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verificar si el usuario es Analista Financiero
     */
    private function esAnalistaFinanciero($user)
    {
        $roles = $user->roles->pluck('name')->toArray();
        return in_array('Analista Financiero', $roles) && !in_array('Administrador', $roles);
    }
}
