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
        // El método componentes() en RatioDefinicion ahora tiene withPivot('rol', 'orden', 'requiere_promedio')
        $componentes = $ratioDefinicion->componentes()->get();

        if ($componentes->isEmpty()) {
            throw new Exception("El ratio '{$ratioDefinicion->nombre}' no tiene componentes definidos.");
        }

        $terminos_numerador = [];
        $valor_denominador = null;

        // 1. Obtener y agregar los valores de los conceptos financieros
        foreach ($componentes as $componente) {
            // El objeto $componente ya es una instancia de ConceptoFinanciero; tomarla directamente
            $concepto = $componente; // Instancia de ConceptoFinanciero
            $rol = $componente->pivot->rol;
            $requierePromedio = $componente->pivot->requiere_promedio; // NUEVO CAMPO

            // Llama al método auxiliar que maneja la lógica condicional de promedio
            $valor_concepto = $this->getConceptoValorCondicional(
                $concepto, 
                $empresa->id, 
                $periodo, 
                (bool) $requierePromedio // Casteamos a booleano
            );

            // 2. Clasificar valores según su rol en la fórmula
            if ($rol === 'NUMERADOR' || $rol === 'OPERANDO') {
                $terminos_numerador[] = [
                    'valor' => $valor_concepto,
                    'rol' => $rol 
                ];
            } elseif ($rol === 'DENOMINADOR') {
                // Solo debería haber un denominador principal por ratio
                $valor_denominador = $valor_concepto;
            }
        }
        
        // 3. Construir el Numerador final
        if (empty($terminos_numerador)) {
            throw new Exception("El numerador del ratio '{$ratioDefinicion->nombre}' no pudo ser calculado.");
        }

        // El primer elemento es el valor base del numerador (se asume que es suma)
        $numerador = $terminos_numerador[0]['valor'];
        
        // Ejecutar las operaciones subsiguientes (resta, suma)
        for ($i = 1; $i < count($terminos_numerador); $i++) {
            $termino = $terminos_numerador[$i];
            if ($termino['rol'] === 'OPERANDO') {
                $numerador -= $termino['valor']; // Asumimos OPERANDO siempre resta (ej: Inventario en Prueba Ácida)
            } else {
                $numerador += $termino['valor']; 
            }
        }

        // 4. Ejecutar la Fórmula Final (División)        
        // Si no hay denominador definido, asumimos 1.0 (para ratios como Capital de Trabajo)
        if ($valor_denominador === null) {
            $valor_denominador = 1.0;
        }

        if ($valor_denominador === 0.0) {
            throw new Exception("División por cero: El denominador del ratio '{$ratioDefinicion->nombre}' es cero o no tiene valor.");
        }

        // Retorna el resultado del cálculo
        return $numerador / $valor_denominador;

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
