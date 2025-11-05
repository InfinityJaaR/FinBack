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
            'detalles.*.usar_en_ratios' => 'sometimes|boolean',
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

            // Obtener todas las cuentas del catálogo de esta empresa
            $catalogoCompleto = CatalogoCuenta::where('empresa_id', $request->empresa_id)
                ->get()
                ->keyBy('id');

            // Calcular cuentas agregadas (padres) - esto también crea cuentas faltantes en el catálogo
            $detallesConCalculadas = $this->calcularCuentasAgregadas(
                $request->detalles, 
                $catalogoCompleto
            );

            // Recargar el catálogo después de crear cuentas nuevas
            $catalogoCompleto = CatalogoCuenta::where('empresa_id', $request->empresa_id)
                ->get()
                ->keyBy('id');

            // Crear el estado financiero
            $estado = Estado::create([
                'empresa_id' => $request->empresa_id,
                'periodo_id' => $request->periodo_id,
                'tipo' => $request->tipo,
            ]);

            // Crear los detalles (incluye cuentas base + calculadas)
            foreach ($detallesConCalculadas as $detalle) {
                DetalleEstado::create([
                    'estado_id' => $estado->id,
                    'catalogo_cuenta_id' => $detalle['catalogo_cuenta_id'],
                    'monto' => $detalle['monto'],
                    'usar_en_ratios' => $detalle['usar_en_ratios'] ?? false,
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
            
            // Log del error para debugging
            \Log::error('Error al crear estado financiero', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el estado financiero',
                'error' => $e->getMessage(),
                'debug' => [
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile())
                ]
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
            'detalles.*.usar_en_ratios' => 'sometimes|boolean',
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

                // Obtener todas las cuentas del catálogo de esta empresa
                $catalogoCompleto = CatalogoCuenta::where('empresa_id', $estado->empresa_id)
                    ->get()
                    ->keyBy('id');

                // Calcular cuentas agregadas (padres)
                $detallesConCalculadas = $this->calcularCuentasAgregadas(
                    $request->detalles, 
                    $catalogoCompleto
                );

                // Crear nuevos detalles (incluye cuentas base + calculadas)
                foreach ($detallesConCalculadas as $detalle) {
                    DetalleEstado::create([
                        'estado_id' => $estado->id,
                        'catalogo_cuenta_id' => $detalle['catalogo_cuenta_id'],
                        'monto' => $detalle['monto'],
                        'usar_en_ratios' => $detalle['usar_en_ratios'] ?? false,
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
            // Agregar BOM UTF-8 para que Excel detecte correctamente las tildes
            $csvContent = "\xEF\xBB\xBF"; // BOM UTF-8
            $csvContent .= "Codigo,Nombre de Cuenta,Monto\n";
            
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

    /**
     * Calcular cuentas agregadas (padres) basándose en las cuentas hijas
     * Ejemplo: 1.1 = suma de todas las cuentas 1.1.XX
     *          1 = suma de todas las cuentas 1.X.XX
     * 
     * Para Estados de Resultados también calcula:
     * - Utilidad Bruta (Ingresos - Costos)
     * - Utilidad Operacional (Utilidad Bruta - Gastos Operacionales)
     * - Utilidad Antes de Impuestos (Utilidad Operacional + Otros Resultados)
     * - Utilidad Neta (Utilidad Antes de Impuestos - Impuestos)
     */
    private function calcularCuentasAgregadas($detallesBase, $catalogoCompleto)
    {
        // Crear mapa de IDs a códigos para trabajar más fácil
        $idACodigo = [];
        $codigoAId = [];
        $codigoACuenta = [];
        foreach ($catalogoCompleto as $cuenta) {
            $idACodigo[$cuenta->id] = $cuenta->codigo;
            $codigoAId[$cuenta->codigo] = $cuenta->id;
            $codigoACuenta[$cuenta->codigo] = $cuenta;
        }

        // Convertir detalles base a formato código => monto
        $montoPorCodigo = [];
        foreach ($detallesBase as $detalle) {
            $codigo = $idACodigo[$detalle['catalogo_cuenta_id']] ?? null;
            if ($codigo) {
                $montoPorCodigo[$codigo] = $detalle['monto'];
            }
        }

        // Función para obtener todos los códigos padre de un código
        $obtenerCodigosPadre = function($codigo) {
            $padres = [];
            $partes = explode('.', $codigo);
            
            // Generar códigos padre: "1.1.01" -> ["1.1", "1"]
            for ($i = count($partes) - 1; $i > 0; $i--) {
                $codigoPadre = implode('.', array_slice($partes, 0, $i));
                $padres[] = $codigoPadre;
            }
            
            return $padres;
        };

        // Recopilar todos los códigos padre únicos que necesitamos calcular
        $codigosPadreNecesarios = [];
        foreach (array_keys($montoPorCodigo) as $codigo) {
            $padres = $obtenerCodigosPadre($codigo);
            foreach ($padres as $padre) {
                // Solo agregar si la cuenta padre existe en el catálogo
                if (isset($codigoAId[$padre])) {
                    $codigosPadreNecesarios[$padre] = true;
                }
            }
        }
        $codigosPadreNecesarios = array_keys($codigosPadreNecesarios);

        // Ordenar códigos padre por profundidad (más profundos primero)
        usort($codigosPadreNecesarios, function($a, $b) {
            $nivelA = substr_count($a, '.');
            $nivelB = substr_count($b, '.');
            return $nivelB - $nivelA; // Mayor nivel primero
        });

        // Calcular cada código padre
        $montosCalculados = [];
        
        // Obtener empresa_id para crear cuentas si es necesario
        $empresaId = $catalogoCompleto->first()->empresa_id ?? null;
        
        foreach ($codigosPadreNecesarios as $codigoPadre) {
            $montoTotal = 0;
            
            // Sumar todos los montos de cuentas que son hijas directas
            foreach ($montoPorCodigo as $codigo => $monto) {
                if ($this->esHijaDirecta($codigo, $codigoPadre)) {
                    $montoTotal += $monto;
                }
            }
            
            // Sumar montos ya calculados de padres intermedios
            foreach ($montosCalculados as $codigoCalc => $montoCalc) {
                if ($this->esHijaDirecta($codigoCalc, $codigoPadre)) {
                    $montoTotal += $montoCalc;
                }
            }
            
            $montosCalculados[$codigoPadre] = $montoTotal;
            
            // Si la cuenta padre no existe en el catálogo, crearla automáticamente
            if (!isset($codigoAId[$codigoPadre]) && $empresaId) {
                $nombreCuenta = $this->obtenerNombreCuentaPorCodigo($codigoPadre);
                $tipoCuenta = $this->obtenerTipoCuentaPorCodigo($codigoPadre);
                $estadoFinanciero = $this->inferirEstadoFinancieroPorCodigo($codigoPadre);
                
                $nuevaCuenta = CatalogoCuenta::create([
                    'empresa_id' => $empresaId,
                    'codigo' => $codigoPadre,
                    'nombre' => $nombreCuenta,
                    'tipo' => $tipoCuenta,
                    'es_calculada' => true,
                    'estado_financiero' => $estadoFinanciero
                ]);
                
                $codigoAId[$codigoPadre] = $nuevaCuenta->id;
                $catalogoCompleto[$nuevaCuenta->id] = $nuevaCuenta;
            }
        }

        // Calcular utilidades para Estados de Resultados
        // Buscar si hay cuentas de tipo 4, 5, 6, 7 (Estado de Resultados)
        $esEstadoResultados = false;
        foreach ($montoPorCodigo as $codigo => $monto) {
            $primerDigito = substr($codigo, 0, 1);
            if (in_array($primerDigito, ['4', '5', '6', '7'])) {
                $esEstadoResultados = true;
                break;
            }
        }

        if ($esEstadoResultados) {
            // Obtener totales de cada categoría (usar calculados si existen, sino calcular)
            $totalIngresos = $montosCalculados['4'] ?? $this->calcularTotalPorDigito($montoPorCodigo, $montosCalculados, '4');
            $totalCostos = $montosCalculados['5'] ?? $this->calcularTotalPorDigito($montoPorCodigo, $montosCalculados, '5');
            $totalGastos = $montosCalculados['6'] ?? $this->calcularTotalPorDigito($montoPorCodigo, $montosCalculados, '6');
            $totalOtros = $montosCalculados['7'] ?? $this->calcularTotalPorDigito($montoPorCodigo, $montosCalculados, '7');

            // Calcular utilidades
            $utilidadBruta = $totalIngresos - $totalCostos;
            $utilidadOperacional = $utilidadBruta - $totalGastos;
            $utilidadAntesImpuestos = $utilidadOperacional + $totalOtros;
            
            // Buscar si hay una cuenta específica para impuestos
            $totalImpuestos = 0;
            foreach ($montoPorCodigo as $codigo => $monto) {
                $cuenta = $codigoACuenta[$codigo] ?? null;
                if ($cuenta && stripos($cuenta->nombre, 'impuesto') !== false) {
                    $totalImpuestos += $monto;
                }
            }
            
            $utilidadNetaCalculada = $utilidadAntesImpuestos - $totalImpuestos;

            // Verificar si existe "Utilidad del Ejercicio" en el catálogo
            $existeUtilidadEjercicio = false;
            foreach ($catalogoCompleto as $cuenta) {
                if (stripos($cuenta->nombre, 'utilidad') !== false && 
                    stripos($cuenta->nombre, 'ejercicio') !== false) {
                    $existeUtilidadEjercicio = true;
                    break;
                }
            }

            // Definir las cuentas de utilidades a crear/guardar
            $utilidadesAGuardar = [
                ['codigo' => '8.1', 'nombre' => 'Utilidad Bruta', 'monto' => $utilidadBruta, 'crear' => true],
                ['codigo' => '8.2', 'nombre' => 'Utilidad Operacional', 'monto' => $utilidadOperacional, 'crear' => true],
                ['codigo' => '8.3', 'nombre' => 'Utilidad Antes de Impuestos', 'monto' => $utilidadAntesImpuestos, 'crear' => true],
            ];
            
            // Solo crear/guardar Utilidad Neta (8.4) si NO existe "Utilidad del Ejercicio" en el catálogo
            // Si existe "Utilidad del Ejercicio", ya viene en la plantilla con su monto, no la calculamos
            if (!$existeUtilidadEjercicio) {
                $utilidadesAGuardar[] = ['codigo' => '8.4', 'nombre' => 'Utilidad Neta', 'monto' => $utilidadNetaCalculada, 'crear' => true];
            }

            // Obtener empresa_id de la primera cuenta del catálogo
            $empresaId = $catalogoCompleto->first()->empresa_id ?? null;

            foreach ($utilidadesAGuardar as $utilidad) {
                $cuentaId = $codigoAId[$utilidad['codigo']] ?? null;
                
                // Si la cuenta no existe en el catálogo, crearla automáticamente
                if (!$cuentaId && $empresaId && $utilidad['crear']) {
                    $nuevaCuenta = CatalogoCuenta::create([
                        'empresa_id' => $empresaId,
                        'codigo' => $utilidad['codigo'],
                        'nombre' => $utilidad['nombre'],
                        'tipo' => 'INGRESO', // Las utilidades son técnicamente resultados
                        'es_calculada' => true,
                        'estado_financiero' => 'ESTADO_RESULTADOS'
                    ]);
                    
                    $cuentaId = $nuevaCuenta->id;
                    $codigoAId[$utilidad['codigo']] = $cuentaId;
                    
                    // Agregar al catálogo en memoria para futuras referencias
                    $catalogoCompleto[$cuentaId] = $nuevaCuenta;
                }
                
                if ($cuentaId) {
                    $montosCalculados[$utilidad['codigo']] = $utilidad['monto'];
                }
            }
        }

        // Combinar detalles base con calculados
        $todosLosDetalles = [];
        
        // Agregar SOLO detalles base que NO sean calculados (hojas del árbol)
        foreach ($detallesBase as $detalle) {
            $codigo = $idACodigo[$detalle['catalogo_cuenta_id']] ?? null;
            // Solo agregar si no es una cuenta calculada (no está en montosCalculados)
            if ($codigo && !isset($montosCalculados[$codigo])) {
                $todosLosDetalles[] = $detalle;
            }
        }
        
        // Agregar detalles calculados (sin usar_en_ratios, default false en BD)
        foreach ($montosCalculados as $codigo => $monto) {
            $cuentaId = $codigoAId[$codigo] ?? null;
            if ($cuentaId) {
                $todosLosDetalles[] = [
                    'catalogo_cuenta_id' => $cuentaId,
                    'monto' => $monto,
                    'usar_en_ratios' => false, // Cuentas calculadas no se usan en ratios por defecto
                ];
            }
        }

        return $todosLosDetalles;
    }

    /**
     * Calcular el total de todas las cuentas que empiezan con un dígito específico
     */
    private function calcularTotalPorDigito($montoPorCodigo, $montosCalculados, $digito)
    {
        $total = 0;
        
        // Sumar de montos base
        foreach ($montoPorCodigo as $codigo => $monto) {
            if (substr($codigo, 0, 1) === $digito) {
                $total += $monto;
            }
        }
        
        // Sumar de montos calculados (excepto el total principal)
        foreach ($montosCalculados as $codigo => $monto) {
            if ($codigo !== $digito && substr($codigo, 0, 1) === $digito) {
                $total += $monto;
            }
        }
        
        return $total;
    }

    /**
     * Determinar si una cuenta es hija directa de otra
     * Ejemplo: "1.1.01" es hija directa de "1.1" pero NO de "1"
     *          "1.1" es hija directa de "1"
     */
    private function esHijaDirecta($codigoHijo, $codigoPadre)
    {
        // El hijo debe empezar con el código del padre seguido de un punto
        if (!str_starts_with($codigoHijo, $codigoPadre . '.')) {
            return false;
        }
        
        // Verificar que sea hija directa (no nieta)
        $resto = substr($codigoHijo, strlen($codigoPadre) + 1);
        $niveles = explode('.', $resto);
        
        // Si solo hay un nivel después del padre, es hija directa
        return count($niveles) === 1;
    }

    /**
     * Obtener nombre genérico para una cuenta según su código
     */
    private function obtenerNombreCuentaPorCodigo($codigo)
    {
        $mapeo = [
            '1' => 'ACTIVO',
            '1.1' => 'ACTIVO CORRIENTE',
            '1.2' => 'ACTIVO NO CORRIENTE',
            '2' => 'PASIVO',
            '2.1' => 'PASIVO CORRIENTE',
            '2.2' => 'PASIVO NO CORRIENTE',
            '3' => 'PATRIMONIO',
            '4' => 'INGRESOS',
            '5' => 'COSTOS DE VENTAS',
            '6' => 'GASTOS OPERACIONALES',
            '7' => 'OTROS RESULTADOS',
        ];
        
        return $mapeo[$codigo] ?? "Cuenta Agregada {$codigo}";
    }

    /**
     * Obtener tipo de cuenta según su código
     */
    private function obtenerTipoCuentaPorCodigo($codigo)
    {
        $primerDigito = substr($codigo, 0, 1);
        
        $mapeo = [
            '1' => 'ACTIVO',
            '2' => 'PASIVO',
            '3' => 'PATRIMONIO',
            '4' => 'INGRESO',
            '5' => 'GASTO',
            '6' => 'GASTO',
            '7' => 'INGRESO',
        ];
        
        return $mapeo[$primerDigito] ?? 'ACTIVO';
    }

    /**
     * Inferir estado financiero basándose en el primer dígito del código
     */
    private function inferirEstadoFinancieroPorCodigo($codigo)
    {
        $primerDigito = substr($codigo, 0, 1);
        
        $mapeo = [
            '1' => 'BALANCE_GENERAL',
            '2' => 'BALANCE_GENERAL',
            '3' => 'BALANCE_GENERAL',
            '4' => 'ESTADO_RESULTADOS',
            '5' => 'ESTADO_RESULTADOS',
            '6' => 'ESTADO_RESULTADOS',
            '7' => 'ESTADO_RESULTADOS',
            '8' => 'ESTADO_RESULTADOS',
        ];
        
        return $mapeo[$primerDigito] ?? 'NINGUNO';
    }
}
