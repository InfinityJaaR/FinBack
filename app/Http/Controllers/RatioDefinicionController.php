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

use App\Http\Requests\VerRatiosEmpresaRequest;
use App\Http\Requests\GenerarRatiosEmpresaRequest;


use App\Models\Empresa;
use App\Models\Periodo;
use App\Models\RatioValor;
use App\Models\DetalleEstado;
use App\Models\CuentaConcepto;
use App\Services\RatioCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
                        'orden' => $item['orden'],
                        'requiere_promedio' => $item['requiere_promedio'] ?? false,
                        'operacion' => $item['operacion'] ?? 'ADD',
                        'factor' => $item['factor'] ?? null,
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
                        'orden' => $item['orden'],
                        'requiere_promedio' => $item['requiere_promedio'] ?? false,
                        'operacion' => $item['operacion'] ?? 'ADD',
                        'factor' => $item['factor'] ?? null,
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

    /**
     * Calcula un único ratio para una empresa y periodo y devuelve el breakdown.
     * Permiso: calcular_ratios (analistas/administradores)
     */
    public function calculate(RatioDefinicion $ratioDefinicion, Request $request): JsonResponse
    {
        $empresaId = $request->input('empresa_id') ?? $request->query('empresa_id');
        $periodoInput = $request->input('periodo_id') ?? $request->query('periodo_id');
        if (! $empresaId || ! $periodoInput) {
            return response()->json(['success' => false, 'message' => 'Se requiere empresa_id y periodo_id'], 422);
        }

        $empresa = Empresa::find($empresaId);
        if (! $empresa) return response()->json(['success' => false, 'message' => 'Empresa no encontrada'], 404);

        $periodoId = $this->resolvePeriodoId($periodoInput);
        if (! $periodoId) return response()->json(['success' => false, 'message' => 'Periodo inválido'], 422);

        // Montos por concepto para la empresa y periodo
        $montosPorConcepto = DB::table('detalles_estado as de')
            ->join('estados as e', function ($j) use ($empresa, $periodoId) {
                $j->on('e.id', '=', 'de.estado_id')
                    ->where('e.empresa_id', '=', $empresa->id)
                    ->where('e.periodo_id', '=', $periodoId);
            })
            ->join('cuenta_concepto as cc', 'cc.catalogo_cuenta_id', '=', 'de.catalogo_cuenta_id')
            ->select('cc.concepto_id', DB::raw('SUM(de.monto) AS suma'))
            ->groupBy('cc.concepto_id')
            ->get()
            ->pluck('suma', 'concepto_id')
            ->toArray();

        // Calcular según componentes ordenados
        $components = $ratioDefinicion->componentes()->orderBy('ratio_componentes.orden')->get();

        $groups = $components->groupBy(fn($c) => $c->pivot->rol ?? 'OPERANDO');

        $breakdown = [];

        $computeGroup = function ($items) use ($montosPorConcepto, &$breakdown) {
            // items: collection of ConceptoFinanciero with pivot
            $acc = null;
            foreach ($items->sortBy('pivot.orden') as $c) {
                $conceptoId = $c->id;
                $monto = (float)($montosPorConcepto[$conceptoId] ?? 0);

                // Aplicar factor por componente si existe
                $factor = $c->pivot->factor ?? null;
                $valorComp = $monto;
                if (! is_null($factor)) {
                    $valorComp = $valorComp * (float)$factor;
                }

                $oper = strtoupper($c->pivot->operacion ?? 'ADD');

                // Reducir según la operación
                if (is_null($acc)) {
                    // Inicializar accumulator según la primera operación
                    if ($oper === 'SUB') {
                        $acc = -$valorComp;
                    } else {
                        $acc = $valorComp;
                    }
                } else {
                    switch ($oper) {
                        case 'ADD':
                            $acc += $valorComp;
                            break;
                        case 'SUB':
                            $acc -= $valorComp;
                            break;
                        case 'MUL':
                            $acc *= $valorComp;
                            break;
                        case 'DIV':
                            $acc = (abs($valorComp) < 1e-9) ? null : ($acc / $valorComp);
                            break;
                        default:
                            $acc += $valorComp;
                    }
                }

                $breakdown[] = [
                    'concepto_id' => $conceptoId,
                    'concepto' => $c->nombre_concepto,
                    'rol' => $c->pivot->rol ?? null,
                    'monto' => $monto,
                    'operacion' => $oper,
                    'factor' => $factor,
                    'contribucion' => $valorComp,
                ];
            }

            return $acc ?? 0.0;
        };

        // Numerador
        $num = $computeGroup($groups->get('NUMERADOR', collect()));
        // Denominador
        $den = $computeGroup($groups->get('DENOMINADOR', collect()));
        // Operandos/otros
        $op = $computeGroup($groups->get('OPERANDO', collect()));

        // Aplicar multiplicadores por bloque si existen
        if (! is_null($ratioDefinicion->multiplicador_numerador)) {
            $num = $num * (float)$ratioDefinicion->multiplicador_numerador;
        }
        if (! is_null($ratioDefinicion->multiplicador_denominador)) {
            $den = $den * (float)$ratioDefinicion->multiplicador_denominador;
        }

        if (abs($den) < 1e-9) {
            $valor = null;
        } else {
            $valor = ($num + $op) / $den;
            // aplicar multiplicador_resultado
            if (! is_null($ratioDefinicion->multiplicador_resultado)) {
                $valor = $valor * (float)$ratioDefinicion->multiplicador_resultado;
            }
        }

        return response()->json([
            'success' => true,
            'empresa_id' => $empresa->id,
            'periodo_id' => $periodoId,
            'ratio' => [
                'codigo' => $ratioDefinicion->codigo,
                'nombre' => $ratioDefinicion->nombre,
                'categoria' => $ratioDefinicion->categoria,
                'multiplicador_numerador' => $ratioDefinicion->multiplicador_numerador,
                'multiplicador_denominador' => $ratioDefinicion->multiplicador_denominador,
                'multiplicador_resultado' => $ratioDefinicion->multiplicador_resultado,
            ],
            'breakdown' => $breakdown,
            'num' => round($num, 6),
            'den' => round($den, 6),
            'op' => round($op, 6),
            'valor' => is_null($valor) ? null : round($valor, 6),
        ]);
    }
    /* ======================
         * 0) Helper de periodo
     * ====================== */
    private function resolvePeriodoId($periodoInput): ?int
    {
        // Si viene 2024, lo tomamos como año y buscamos su id
        if ($periodoInput >= 1900) {
            $row = DB::table('periodos')->where('anio', $periodoInput)->first();
            return $row ? (int)$row->id : null;
        }
        // Si viene un id (1,2,3...), lo devolvemos tal cual
        return $periodoInput ? (int)$periodoInput : null;
    }

    public function valoresPorPeriodo(Empresa $empresa, VerRatiosEmpresaRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $periodoInput = (int) ($validated['periodo_id'] ?? $request->query('periodo_id'));
        $periodoId = $this->resolvePeriodoId($periodoInput);
        if (!$periodoId) {
            return response()->json(['success' => false, 'message' => 'periodo_id inválido'], 422);
        }


        $valores = RatioValor::with(['ratioDefinicion:id,codigo,nombre'])
            ->where('empresa_id', $empresa->id)
            ->where('periodo_id', $periodoId)
            ->get()
            ->map(fn($rv) => [
                'codigo'     => $rv->ratioDefinicion->codigo,
                'nombre'     => $rv->ratioDefinicion->nombre,
                'valor'      => is_null($rv->valor) ? null : (float)$rv->valor,
                'updated_at' => $rv->updated_at,
            ]);

        return response()->json([
            'success'   => true,
            'empresaId' => $empresa->id,
            'periodoId' => $periodoId,
            'valores'   => $valores,
        ]);
    }

    public function generarPorPeriodo(Empresa $empresa, GenerarRatiosEmpresaRequest $request): JsonResponse
    {
        try {
            $periodoId = $this->resolvePeriodoId((int)$request->validated()['periodo_id']);
            if (!$periodoId) {
                return response()->json(['success' => false, 'message' => 'periodo_id inválido'], 422);
            }

            // Trae el modelo Periodo requerido por el servicio
            $periodo = \App\Models\Periodo::find($periodoId);
            if (!$periodo) {
                return response()->json(['success' => false, 'message' => 'Periodo no encontrado'], 404);
            }

            /** @var RatioCalculator $calc */
            $calc   = app(RatioCalculator::class);
            $ratios = \App\Models\RatioDefinicion::with('componentes')->get();

            $guardados  = 0;
            $saltados   = [];
            $resultados = [];

            foreach ($ratios as $ratio) {
                try {
                    $valor = $calc->calculateRatio($ratio, $empresa, $periodo); // <- método correcto

                    if ($valor !== null) {
                        \App\Models\RatioValor::updateOrCreate(
                            ['empresa_id' => $empresa->id, 'periodo_id' => $periodo->id, 'ratio_id' => $ratio->id],
                            ['valor' => round($valor, 2), 'fuente' => 'CALCULADO']
                        );
                        $guardados++;
                    } else {
                        $saltados[] = $ratio->codigo;
                    }

                    $resultados[] = [
                        'codigo' => $ratio->codigo,
                        'nombre' => $ratio->nombre,
                        'valor'  => $valor !== null ? round($valor, 2) : null,
                    ];
                } catch (\Throwable $ex) {
                    // Deja huella de por qué se saltó (división por cero, sin componentes, etc.)
                    $saltados[] = $ratio->codigo;
                    $resultados[] = [
                        'codigo' => $ratio->codigo,
                        'nombre' => $ratio->nombre,
                        'valor'  => null,
                        'error'  => $ex->getMessage(),
                    ];
                    Log::warning('[ratios] fallo calculando ' . $ratio->codigo, ['err' => $ex->getMessage()]);
                }
            }

            return response()->json([
                'success'   => true,
                'empresaId' => $empresa->id,
                'periodoId' => $periodo->id,
                'guardados' => $guardados,
                'saltados'  => $saltados,
                'valores'   => $resultados,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ratios] generarPorPeriodo', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
