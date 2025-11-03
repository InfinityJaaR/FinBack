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
                        'sentido' => $item['sentido'] ?? 1,
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
                        'sentido' => $item['sentido'] ?? 1,
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

        $num = 0.0; $den = 0.0; $op = 0.0; $tieneDen = false;
        $breakdown = [];

        foreach ($components as $c) {
            $conceptoId = $c->id;
            $monto = (float)($montosPorConcepto[$conceptoId] ?? 0);
            $signo = (int)($c->pivot->sentido ?? 1);
            $rol = $c->pivot->rol ?? null;

            $contrib = $signo * $monto;
            if ($rol === 'NUMERADOR') {
                $num += $contrib;
            } elseif ($rol === 'DENOMINADOR') {
                $den += $contrib;
                $tieneDen = true;
            } else {
                $op += $contrib;
            }

            $breakdown[] = [
                'concepto_id' => $conceptoId,
                'concepto' => $c->nombre_concepto,
                'rol' => $rol,
                'monto' => $monto,
                'signo' => $signo,
                'contribucion' => $contrib,
            ];
        }

        if (! $tieneDen) $den = 1.0;
        $valor = (abs($den) < 1e-9) ? null : ($num + $op) / $den;

        // aplicar multiplicador
        if (! is_null($valor)) {
            $valor = $valor * ($ratioDefinicion->multiplicador ?? 1.0);
        }

        return response()->json([
            'success' => true,
            'empresa_id' => $empresa->id,
            'periodo_id' => $periodoId,
            'ratio' => [
                'codigo' => $ratioDefinicion->codigo,
                'nombre' => $ratioDefinicion->nombre,
                'categoria' => $ratioDefinicion->categoria,
                'multiplicador' => $ratioDefinicion->multiplicador,
                'is_protected' => $ratioDefinicion->is_protected,
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
            $validated    = $request->validated();
            $periodoInput = (int) $validated['periodo_id'];                 // viene del body (o query por prepareForValidation)
            $periodoId    = $this->resolvePeriodoId($periodoInput);         // acepta año o id
            if (!$periodoId) {
                return response()->json(['success' => false, 'message' => 'periodo_id inválido'], 422);
            }

            Log::info('[ratios] generarPorPeriodo', ['empresa' => $empresa->id, 'periodo' => $periodoId]);
            DB::connection()->disableQueryLog();

            // 1) Montos por CONCEPTO (JOIN correcto con catalogo_cuenta_id)
            // MONTOS POR CONCEPTO => devuelve array: [concepto_id => suma]
            $montosPorConcepto = DB::table('detalles_estado as de')
                ->join('estados as e', function ($j) use ($empresa, $periodoId) {
                    $j->on('e.id', '=', 'de.estado_id')
                        ->where('e.empresa_id', '=', $empresa->id)
                        ->where('e.periodo_id', '=', $periodoId);
                })
                ->join('cuenta_concepto as cc', 'cc.catalogo_cuenta_id', '=', 'de.catalogo_cuenta_id')
                ->select('cc.concepto_id', DB::raw('SUM(de.monto) AS suma'))
                ->groupBy('cc.concepto_id')
                ->get() // ← colección de stdClass { concepto_id, suma }
                ->pluck('suma', 'concepto_id') // ← convierte a array: [concepto_id => suma]
                ->toArray();


            // 2) Ratios + componentes
            // Ordenar por la columna de la tabla pivote
            $ratios = RatioDefinicion::with(['componentes' => fn($q) => $q->orderBy('ratio_componentes.orden')])->get();

            // 3) Calcular y guardar
            $guardados = 0;
            $saltados = [];
            $resultados = [];

            foreach ($ratios as $ratio) {
                $num = 0.0;
                $den = 0.0;
                $op = 0.0;
                $tieneDenominador = false;

                foreach ($ratio->componentes as $c) {
                    // $c es un ConceptoFinanciero; los atributos del pivote están en $c->pivot
                    $conceptoId = $c->id;
                    $monto = (float)($montosPorConcepto[$conceptoId] ?? 0);
                    $signo = (int)($c->pivot->sentido ?? 1);
                    $rol = $c->pivot->rol ?? null;

                    if ($rol === 'NUMERADOR') {
                        $num += $signo * $monto;
                    } elseif ($rol === 'DENOMINADOR') {
                        $den += $signo * $monto;
                        $tieneDenominador = true;
                    } else {
                        $op  += $signo * $monto;
                    }
                }

                if (!$tieneDenominador) {
                    $den = 1.0;
                }

                $valor = (abs($den) < 1e-9) ? null : ($num + $op) / $den;

                // Opción B: NO guardar si valor es null, pero devolvemos diagnóstico
                if (is_null($valor)) {
                    $saltados[] = $ratio->codigo;
                } else {
                    // aplicar multiplicador si existe
                    $valor = $valor * ($ratio->multiplicador ?? 1.0);
                    \App\Models\RatioValor::updateOrCreate(
                        ['empresa_id' => $empresa->id, 'periodo_id' => $periodoId, 'ratio_id' => $ratio->id],
                        ['valor' => $valor, 'fuente' => 'CALCULADO']
                    );
                    $guardados++;
                }

                // Agrega diagnóstico para UI/logs
                $resultados[] = [
                    'codigo' => $ratio->codigo,
                    'nombre' => $ratio->nombre,
                    'num'    => round($num, 6),
                    'den'    => round($den, 6),
                    'op'     => round($op, 6),
                    'valor'  => is_null($valor) ? null : round($valor, 6),
                ];
            }

            return response()->json([
                'success'    => true,
                'empresaId'  => $empresa->id,
                'periodoId'  => $periodoId,
                'guardados'  => $guardados,
                'saltados'   => $saltados,   // <- verás cuáles ratios no se calcularon
                'valores'    => $resultados, // <- incluye diagnostico num/den/op
            ]);
        } catch (\Throwable $e) {
            Log::error('Generar ratios falló', [
                'empresa_id' => $empresa->id,
                'periodo_id' => $request->input('periodo_id'),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al generar ratios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Devuelve la lista de categorías permitidas para classificiar ratios.
     * Permiso: ver_ratios
     */
    public function categorias(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'categorias' => RatioDefinicion::CATEGORIES,
        ]);
    }
}
