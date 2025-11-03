<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use App\Models\DetalleEstado;
use App\Models\CatalogoCuenta;
use App\Models\Empresa;
use App\Models\Periodo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EstadoFinancieroController extends Controller
{
    /**
     * Obtener estados financieros de una empresa por periodo
     */
    public function index(Request $request)
    {
        try {
            $empresaId = $request->query('empresa_id');
            $periodoId = $request->query('periodo_id');
            $tipo = $request->query('tipo'); // BALANCE o RESULTADOS

            $query = Estado::with(['empresa', 'periodo', 'detalles.catalogoCuenta']);

            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }

            if ($periodoId) {
                $query->where('periodo_id', $periodoId);
            }

            if ($tipo) {
                $query->where('tipo', $tipo);
            }

            $estados = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $estados,
                'message' => 'Estados financieros obtenidos exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estados financieros',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un estado financiero específico con todos sus detalles
     */
    public function show($id)
    {
        try {
            $estado = Estado::with(['empresa', 'periodo', 'detalles.catalogoCuenta'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $estado,
                'message' => 'Estado financiero obtenido exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Estado financiero no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Crear un nuevo estado financiero con sus detalles
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'empresa_id' => 'required|exists:empresas,id',
            'periodo_id' => 'required|exists:periodos,id',
            'tipo' => ['required', Rule::in(['BALANCE', 'RESULTADOS'])],
            'detalles' => 'required|array|min:1',
            'detalles.*.catalogo_cuenta_id' => 'required|exists:catalogo_cuentas,id',
            'detalles.*.monto' => 'required|numeric',
        ], [
            'empresa_id.required' => 'El ID de la empresa es obligatorio',
            'empresa_id.exists' => 'La empresa especificada no existe',
            'periodo_id.required' => 'El ID del periodo es obligatorio',
            'periodo_id.exists' => 'El periodo especificado no existe',
            'tipo.required' => 'El tipo de estado es obligatorio',
            'tipo.in' => 'El tipo debe ser BALANCE o RESULTADOS',
            'detalles.required' => 'Debe proporcionar al menos un detalle',
            'detalles.*.catalogo_cuenta_id.required' => 'El ID de la cuenta es obligatorio',
            'detalles.*.catalogo_cuenta_id.exists' => 'La cuenta especificada no existe',
            'detalles.*.monto.required' => 'El monto es obligatorio',
            'detalles.*.monto.numeric' => 'El monto debe ser un valor numérico',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Verificar que no exista ya un estado del mismo tipo para esta empresa y periodo
            $existeEstado = Estado::where('empresa_id', $request->empresa_id)
                ->where('periodo_id', $request->periodo_id)
                ->where('tipo', $request->tipo)
                ->exists();

            if ($existeEstado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un estado financiero de este tipo para esta empresa y periodo'
                ], 422);
            }

            // Crear el estado financiero
            $estado = Estado::create([
                'empresa_id' => $request->empresa_id,
                'periodo_id' => $request->periodo_id,
                'tipo' => $request->tipo,
            ]);

            // Crear los detalles
            foreach ($request->detalles as $detalle) {
                DetalleEstado::create([
                    'estado_id' => $estado->id,
                    'catalogo_cuenta_id' => $detalle['catalogo_cuenta_id'],
                    'monto' => $detalle['monto'],
                ]);
            }

            DB::commit();

            // Cargar las relaciones
            $estado->load(['empresa', 'periodo', 'detalles.catalogoCuenta']);

            return response()->json([
                'success' => true,
                'data' => $estado,
                'message' => 'Estado financiero creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el estado financiero',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un estado financiero existente
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'detalles' => 'sometimes|array|min:1',
            'detalles.*.catalogo_cuenta_id' => 'required|exists:catalogo_cuentas,id',
            'detalles.*.monto' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $estado = Estado::findOrFail($id);

            // Si se proporcionan nuevos detalles, reemplazar los existentes
            if ($request->has('detalles')) {
                // Eliminar detalles existentes
                DetalleEstado::where('estado_id', $estado->id)->delete();

                // Crear nuevos detalles
                foreach ($request->detalles as $detalle) {
                    DetalleEstado::create([
                        'estado_id' => $estado->id,
                        'catalogo_cuenta_id' => $detalle['catalogo_cuenta_id'],
                        'monto' => $detalle['monto'],
                    ]);
                }
            }

            DB::commit();

            // Cargar las relaciones
            $estado->load(['empresa', 'periodo', 'detalles.catalogoCuenta']);

            return response()->json([
                'success' => true,
                'data' => $estado,
                'message' => 'Estado financiero actualizado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado financiero',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un estado financiero
     */
    public function destroy($id)
    {
        try {
            $estado = Estado::findOrFail($id);
            $estado->delete(); // Los detalles se eliminan automáticamente por cascade

            return response()->json([
                'success' => true,
                'message' => 'Estado financiero eliminado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el estado financiero',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar plantilla CSV con las cuentas según el tipo de estado
     */
    public function descargarPlantilla(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'empresa_id' => 'required|exists:empresas,id',
            'tipo' => ['required', Rule::in(['BALANCE', 'RESULTADOS'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $empresaId = $request->empresa_id;
            $tipo = $request->tipo;

            // Determinar el estado_financiero según el tipo
            $estadoFinanciero = $tipo === 'BALANCE' ? 'BALANCE_GENERAL' : 'ESTADO_RESULTADOS';

            // Obtener SOLO las cuentas más internas (no calculadas) del catálogo
            $cuentas = CatalogoCuenta::where('empresa_id', $empresaId)
                ->where('estado_financiero', $estadoFinanciero)
                ->where('es_calculada', false)
                ->orderBy('codigo')
                ->get(['codigo', 'nombre', 'tipo']);

            if ($cuentas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron cuentas para este tipo de estado financiero. Asegúrese de tener un catálogo de cuentas cargado con la clasificación correcta.'
                ], 404);
            }

            // Agrupar cuentas por categoría principal
            $categorias = [
                '1' => 'ACTIVO',
                '2' => 'PASIVO',
                '3' => 'PATRIMONIO',
                '4' => 'INGRESOS',
                '5' => 'COSTOS',
                '6' => 'GASTOS',
                '7' => 'OTROS RESULTADOS',
            ];

            // Generar el CSV con agrupación
            $csvContent = "Codigo,Nombre de Cuenta,Monto\n";
            
            $categoriaActual = null;
            foreach ($cuentas as $cuenta) {
                $primerDigito = substr($cuenta->codigo, 0, 1);
                
                // Si cambia la categoría, agregar un separador
                if ($primerDigito !== $categoriaActual) {
                    $categoriaActual = $primerDigito;
                    $nombreCategoria = $categorias[$primerDigito] ?? 'OTROS';
                    $csvContent .= "\n\"=== {$nombreCategoria} ===\",\"\",\"\"\n";
                }
                
                $csvContent .= "\"{$cuenta->codigo}\",\"{$cuenta->nombre}\",0\n";
            }

            $empresa = Empresa::find($empresaId);
            $filename = "plantilla_" . strtolower($tipo) . "_{$empresa->nombre}_{$empresaId}.csv";

            return response($csvContent, 200)
                ->header('Content-Type', 'text/csv; charset=utf-8')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar la plantilla',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener periodos disponibles
     */
    public function obtenerPeriodos()
    {
        try {
            $periodos = Periodo::orderBy('anio', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $periodos,
                'message' => 'Periodos obtenidos exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener periodos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener empresas con catálogo de cuentas
     */
    public function obtenerEmpresas()
    {
        try {
            $user = auth()->user();

            // Si es Analista Financiero, solo su empresa
            if ($this->esAnalistaFinanciero($user)) {
                if (!$user->empresa_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes una empresa asociada'
                    ], 403);
                }

                $empresas = Empresa::where('id', $user->empresa_id)
                    ->withCount('catalogoCuentas')
                    ->get()
                    ->map(function ($empresa) {
                        return [
                            'id' => $empresa->id,
                            'nombre' => $empresa->nombre,
                            'ruc' => $empresa->ruc,
                            'tiene_catalogo' => $empresa->catalogo_cuentas_count > 0,
                        ];
                    });
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
