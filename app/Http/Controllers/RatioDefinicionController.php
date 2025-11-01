<?php

namespace App\Http\Controllers;

use App\Models\RatioDefinicion;
use App\Models\ConceptoFinanciero; // Necesario si se desea relacionar componentes en create/edit
use App\Http\Requests\StoreRatioDefinicionRequest;
use App\Http\Requests\UpdateRatioDefinicionRequest;
use Illuminate\Http\JsonResponse;
use Exception;

use App\Http\Requests\VerRatiosEmpresaRequest;
use App\Http\Requests\GenerarRatiosEmpresaRequest;


use App\Models\Empresa;
use App\Models\Periodo;
use App\Models\RatioValor;
use App\Models\DetalleEstado;
use App\Models\CuentaConcepto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        return response()->json(['success'=>false,'message'=>'periodo_id inválido'], 422);
    }


    $valores = RatioValor::with(['ratioDefinicion:id,codigo,nombre'])
        ->where('empresa_id', $empresa->id)
        ->where('periodo_id', $periodoId)
        ->get()
        ->map(fn($rv)=>[
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
            return response()->json(['success'=>false,'message'=>'periodo_id inválido'], 422);
        }

        \Log::info('[ratios] generarPorPeriodo', ['empresa'=>$empresa->id,'periodo'=>$periodoId]);
        \DB::connection()->disableQueryLog();

        // 1) Montos por CONCEPTO (JOIN correcto con catalogo_cuenta_id)
        // MONTOS POR CONCEPTO => devuelve array: [concepto_id => suma]
        $montosPorConcepto = \DB::table('detalles_estado as de')
            ->join('estados as e', function ($j) use ($empresa, $periodoId) {
            $j->on('e.id', '=', 'de.estado_id')
            ->where('e.empresa_id', '=', $empresa->id)
            ->where('e.periodo_id', '=', $periodoId);
         })
            ->join('cuenta_concepto as cc', 'cc.catalogo_cuenta_id', '=', 'de.catalogo_cuenta_id')
            ->select('cc.concepto_id', \DB::raw('SUM(de.monto) AS suma'))
            ->groupBy('cc.concepto_id')
            ->get() // ← colección de stdClass { concepto_id, suma }
            ->pluck('suma', 'concepto_id') // ← convierte a array: [concepto_id => suma]
            ->toArray();


        // 2) Ratios + componentes
        $ratios = RatioDefinicion::with(['componentes' => fn($q) => $q->orderBy('orden')])->get();

        // 3) Calcular y guardar
        $guardados = 0;
        $saltados = [];
        $resultados = [];

    foreach ($ratios as $ratio) {
        $num = 0.0; $den = 0.0; $op = 0.0;
        $tieneDenominador = false;

        foreach ($ratio->componentes as $c) {
            $monto = (float)($montosPorConcepto[$c->concepto_id] ?? 0);
            $signo = (int)($c->sentido ?? 1);

            if ($c->rol === 'NUMERADOR')       $num += $signo * $monto;
            elseif ($c->rol === 'DENOMINADOR'){
                $den += $signo * $monto;
                $tieneDenominador = true;
            } else{
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
            \App\Models\RatioValor::updateOrCreate(
                ['empresa_id'=>$empresa->id,'periodo_id'=>$periodoId,'ratio_id'=>$ratio->id],
                ['valor'=>$valor,'fuente'=>'CALCULADO']
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
        \Log::error('Generar ratios falló', [
            'empresa_id'=>$empresa->id,
            'periodo_id'=>$req->input('periodo_id'),
            'error'=>$e->getMessage(),
        ]);
        return response()->json([
            'success'=>false,
            'message'=>'Error al generar ratios: '.$e->getMessage()
        ], 500);
    }
}

}