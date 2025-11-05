<?php

namespace App\Services;

use App\Models\RatioDefinicion;
use App\Models\ConceptoFinanciero;
use App\Models\Empresa;
use App\Models\Periodo;
use Illuminate\Support\Facades\DB;
use Exception;

class RatioCalculator
{
    /**
     * Calcula el valor de un ratio específico para una empresa y periodo dados.
     * * @param RatioDefinicion $ratioDefinicion La definición del ratio a calcular.
     * @param Empresa $empresa La instancia de la empresa.
     * @param Periodo $periodo La instancia del periodo actual.
     * @return float El valor calculado del ratio.
     * @throws Exception Si faltan datos clave o hay división por cero.
     */
    public function calculateRatio(RatioDefinicion $ratioDefinicion, Empresa $empresa, Periodo $periodo): float
    {
        // Recuperar componentes con su pivot (operacion, factor, requiere_promedio, rol, orden)
        $componentes = $ratioDefinicion->componentes()->get();

        if ($componentes->isEmpty()) {
            throw new Exception("El ratio '{$ratioDefinicion->nombre}' no tiene componentes definidos.");
        }

        // Agrupar por rol: NUMERADOR, DENOMINADOR, OPERANDO
        $groups = $componentes->groupBy(fn($c) => $c->pivot->rol ?? 'OPERANDO');

        $computeGroup = function ($items) use ($empresa, $periodo) {
            // items: collection de ConceptoFinanciero con pivot
            $acc = null;
            foreach ($items->sortBy('pivot.orden') as $c) {
                $concepto = $c; // ConceptoFinanciero
                $requierePromedio = (bool)($c->pivot->requiere_promedio ?? false);

                // Obtener el valor del concepto (aplica promedio si corresponde)
                $valor_concepto = $this->getConceptoValorCondicional($concepto, $empresa->id, $periodo, $requierePromedio);

                // Aplicar factor por componente si existe
                $factor = $c->pivot->factor ?? null;
                $valorComp = $valor_concepto;
                if (! is_null($factor)) {
                    $valorComp = $valorComp * (float)$factor;
                }

                $oper = strtoupper($c->pivot->operacion ?? 'ADD');

                if (is_null($acc)) {
                    // Inicializar accumulator con el primer valor (considerando operacion)
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
                            if (abs($valorComp) < 1e-9) {
                                throw new Exception('División por cero en componente para concepto ' . $concepto->id);
                            }
                            $acc = $acc / $valorComp;
                            break;
                        default:
                            $acc += $valorComp;
                    }
                }
            }

            return $acc ?? 0.0;
        };

        // Calculamos numerador, denominador y operando
        $numerador = $computeGroup($groups->get('NUMERADOR', collect()));
        $denominador = $computeGroup($groups->get('DENOMINADOR', collect()));
        $operando = $computeGroup($groups->get('OPERANDO', collect()));

        // Aplicar multiplicadores por bloque si existen
        if (! is_null($ratioDefinicion->multiplicador_numerador)) {
            $numerador = $numerador * (float)$ratioDefinicion->multiplicador_numerador;
        }
        if (! is_null($ratioDefinicion->multiplicador_denominador)) {
            $denominador = $denominador * (float)$ratioDefinicion->multiplicador_denominador;
        }

        // Si no hay denominador definido, asumimos 1.0
        if (abs($denominador) < 1e-9) {
            throw new Exception("División por cero: El denominador del ratio '{$ratioDefinicion->nombre}' es cero o no tiene valor.");
        }

        $valor = ($numerador + $operando) / $denominador;

        // aplicar multiplicador_resultado si existe
        if (! is_null($ratioDefinicion->multiplicador_resultado)) {
            $valor = $valor * (float)$ratioDefinicion->multiplicador_resultado;
        }

        return $valor;

    }
    
    /**
     * Lógica condicional: Obtiene el valor de un concepto, aplicando el promedio si se requiere.
     * Es el corazón de la lógica de "Inventario vs. Inventario Promedio".
     *
     * @param ConceptoFinanciero $concepto
     * @param int $empresaId
     * @param Periodo $periodoActual
     * @param bool $requierePromedio
     * @return float
     */
    protected function getConceptoValorCondicional(ConceptoFinanciero $concepto, int $empresaId, Periodo $periodoActual, bool $requierePromedio): float
    {
        // 1. Obtener el valor del concepto en el PERÍODO ACTUAL (Cierre)
        $valorActual = $this->queryValorEnPeriodo($concepto->id, $empresaId, $periodoActual->id);

        if (!$requierePromedio) {
            // Caso 1: No requiere promedio (ej: Costo de Ventas o Activo Corriente en Prueba Ácida)
            return $valorActual; 
        }

        // --- LÓGICA DE PROMEDIO (SOLO SI REQUIERE PROMEDIO ES TRUE) ---

        // 2. Buscar el periodo anterior (N-1)
        $periodoAnterior = $this->findPeriodoAnterior($periodoActual);

        // 3. Si NO existe un periodo anterior (solo hay un punto de datos):
        if (!$periodoAnterior) {
            // Usar la regla de negocio: si no se puede promediar, se usa el valor del cierre del periodo actual.
            return $valorActual; 
        }
        
        // 4. Si SÍ existe el periodo anterior (dos puntos para promediar):
        $valorAnterior = $this->queryValorEnPeriodo($concepto->id, $empresaId, $periodoAnterior->id);

        // Aplicar la fórmula del promedio: (Cierre + Apertura) / 2
        return ($valorActual + $valorAnterior) / 2.0;
    }
    
    /**
     * Auxiliar de Consulta: Suma los montos de todas las cuentas mapeadas a un Concepto Financiero en un periodo dado.
     * Esta es la consulta compleja (4 JOINS).
     *
     * @param int $conceptoId
     * @param int $empresaId
     * @param int $periodoId
     * @return float
     */
    protected function queryValorEnPeriodo(int $conceptoId, int $empresaId, int $periodoId): float
    {
        $total = DB::table('detalles_estado')
            // Filtrar por el Contexto: Empresa y Período
            ->join('estados', function ($join) use ($empresaId, $periodoId) {
                $join->on('detalles_estado.estado_id', '=', 'estados.id')
                     ->where('estados.empresa_id', $empresaId)
                     ->where('estados.periodo_id', $periodoId);
            })
            // Filtrar por el Mapeo: Cuenta -> Concepto
            ->join('cuenta_concepto', 'detalles_estado.catalogo_cuenta_id', '=', 'cuenta_concepto.catalogo_cuenta_id')
            ->where('cuenta_concepto.concepto_id', $conceptoId)
            ->sum('detalles_estado.monto');
            
        return (float) $total;
    }

    /**
     * Auxiliar: Busca el Periodo inmediatamente anterior al actual.
     * * @param Periodo $periodoActual
     * @return Periodo|null
     */
    protected function findPeriodoAnterior(Periodo $periodoActual): ?Periodo
    {
        // Busca el periodo con el año exactamente anterior al actual.
        return Periodo::where('anio', $periodoActual->anio - 1)
                      ->first();
    }
}
