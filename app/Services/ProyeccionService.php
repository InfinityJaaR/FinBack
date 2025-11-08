<?php
namespace App\Services;

use App\Models\Empresa;
use App\Models\VentaMensual;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProyeccionService
{
    /**
     * Genera un array de 12 detalles proyectados para el año dado.
     * Retorna un array listo para createMany: [['fecha_proyectada'=> 'YYYY-MM-DD', 'monto_proyectado' => 0.00], ...]
     *
     * Por ahora devuelve stubs (0.00) con las fechas mensuales del año objetivo.
     * Implementa los métodos privados para cada algoritmo después.
     */
    public function generar(Empresa $empresa, string $metodo, int $periodo, array $options = []): array
    {
        // Obtener ventas históricas — pasarlas a los métodos específicos más adelante
            $query = VentaMensual::where('empresa_id', $empresa->id)->orderBy('fecha');

            if (! empty($options['base_periodo_inicio'])) {
                $query->where('fecha', '>=', Carbon::parse($options['base_periodo_inicio'])->startOfDay());
            }
            if (! empty($options['base_periodo_fin'])) {
                $query->where('fecha', '<=', Carbon::parse($options['base_periodo_fin'])->endOfDay());
            }

            $ventas = $query->get();

            // limitar a últimos N meses si se pide
            if (! empty($options['meses_historicos']) && is_int($options['meses_historicos'])) {
                $ventas = $ventas->slice(max(0, $ventas->count() - $options['meses_historicos']))->values();
            }

        switch ($metodo) {
            case 'minimos_cuadrados':
                $result = $this->minimosCuadrados($ventas, $periodo, $options);
                break;
            case 'incremento_porcentual':
                $result = $this->incrementoPorcentual($ventas, $periodo, $options);
                break;
            case 'incremento_absoluto':
                $result = $this->incrementoAbsoluto($ventas, $periodo, $options);
                break;
            default:
                // método desconocido -> devolver stub vacío
                $result = [];
        }

        return $result;
    }

    /**
     * Stub: mínimos cuadrados (implementar la lógica real después).
     * Recibe colección de ventas y retorna array de 12 detalles.
     */
    protected function minimosCuadrados(Collection $ventas, int $periodo, array $options = []): array
    {
            // Preparar datos
            $vals = $this->prepareVentas($ventas);
            $N = count($vals);
            if ($N < 2) {
                return $this->mesesDelAnoConFallback($periodo, $vals);
            }

            // x = 1..N
            $sumaX = 0.0;
            $sumaY = 0.0;
            $sumaXX = 0.0;
            $sumaXY = 0.0;
            for ($i = 0; $i < $N; $i++) {
                $x = $i + 1;
                $y = $vals[$i]['monto'];
                $sumaX += $x;
                $sumaY += $y;
                $sumaXX += ($x * $x);
                $sumaXY += ($x * $y);
            }

            $den = ($N * $sumaXX - ($sumaX * $sumaX));
            if (abs($den) < 1e-12) {
                return $this->mesesDelAnoConFallback($periodo, $vals);
            }

            $b = ($N * $sumaXY - $sumaX * $sumaY) / $den;
            $a = ($sumaY - $b * $sumaX) / $N;

            // Fechas
            $lastDate = Carbon::parse($vals[$N - 1]['fecha']);
            $startTarget = Carbon::createFromDate($periodo, 1, 1)->startOfDay();
            $monthsDiff = $this->monthsBetween($lastDate, $startTarget);
            $xStart = $N + max(1, $monthsDiff);

            $detalles = [];
            for ($k = 0; $k < 12; $k++) {
                $x = $xStart + $k;
                $yPred = $a + $b * $x;
                $fecha = Carbon::createFromDate($periodo, 1, 1)->addMonths($k)->toDateString();
                $detalles[] = [
                    'fecha_proyectada' => $fecha,
                    'monto_proyectado' => round(max(0, $yPred), 2),
                ];
            }

            return $detalles;
    }

    /**
     * Stub: incremento porcentual.
     */
    protected function incrementoPorcentual(Collection $ventas, int $periodo, array $options = []): array
    {
            $vals = $this->prepareVentas($ventas);
            $N = count($vals);
            if ($N < 2) {
                return $this->mesesDelAnoConFallback($periodo, $vals);
            }

            $eps = 1e-6;
            $pcts = [];
            for ($i = 1; $i < $N; $i++) {
                $prev = $vals[$i - 1]['monto'];
                $curr = $vals[$i]['monto'];
                $den = max($eps, $prev);
                $pcts[] = ($curr - $prev) / $den;
            }

            // Prefer geometric mean when possible
            $useGeometric = true;
            foreach ($pcts as $p) {
                if (1 + $p <= 0) { $useGeometric = false; break; }
            }

            if ($useGeometric) {
                $prod = 1.0;
                foreach ($pcts as $p) $prod *= (1 + $p);
                $avgPct = pow($prod, 1 / count($pcts)) - 1;
            } else {
                $avgPct = array_sum($pcts) / count($pcts);
            }

            // Generate
            $last = $vals[$N - 1]['monto'];
            $detalles = [];
            $startDate = Carbon::createFromDate($periodo, 1, 1);
            for ($k = 0; $k < 12; $k++) {
                $next = $last * (1 + $avgPct);
                $fecha = $startDate->copy()->addMonths($k)->toDateString();
                $detalles[] = [
                    'fecha_proyectada' => $fecha,
                    'monto_proyectado' => round(max(0, $next), 2),
                ];
                $last = $next;
            }

            return $detalles;
    }

    /**
     * Stub: incremento absoluto.
     */
    protected function incrementoAbsoluto(Collection $ventas, int $periodo, array $options = []): array
    {
            $vals = $this->prepareVentas($ventas);
            $N = count($vals);
            if ($N < 2) {
                return $this->mesesDelAnoConFallback($periodo, $vals);
            }

            $diffs = [];
            for ($i = 1; $i < $N; $i++) {
                $diffs[] = $vals[$i]['monto'] - $vals[$i - 1]['monto'];
            }
            $avgDiff = array_sum($diffs) / count($diffs);

            $last = $vals[$N - 1]['monto'];
            $detalles = [];
            $startDate = Carbon::createFromDate($periodo, 1, 1);
            for ($k = 0; $k < 12; $k++) {
                $next = $last + $avgDiff;
                $fecha = $startDate->copy()->addMonths($k)->toDateString();
                $detalles[] = [
                    'fecha_proyectada' => $fecha,
                    'monto_proyectado' => round(max(0, $next), 2),
                ];
                $last = $next;
            }

            return $detalles;
    }

    /**
     * Utilidad: genera 12 entradas con monto 0 para el año indicado (YYYY)
     */
    protected function mesesDelAnoConCero(int $ano): array
    {
        $detalles = [];
        for ($m = 1; $m <= 12; $m++) {
            $fecha = Carbon::createFromDate($ano, $m, 1)->toDateString();
            $detalles[] = [
                'fecha_proyectada' => $fecha,
                'monto_proyectado' => 0.00,
            ];
        }
        return $detalles;
    }

    /**
     * Prepara la colección de ventas en un array ordenado por fecha con claves 'fecha' y 'monto'.
     * Elimina duplicados por fecha manteniendo la suma (si existieran) y asegura formato consistente.
     * @param Collection $ventas
     * @return array
     */
    protected function prepareVentas(Collection $ventas): array
    {
        $map = [];
        foreach ($ventas as $v) {
            $fecha = Carbon::parse($v->fecha)->toDateString();
            $monto = floatval($v->monto ?? 0);
            if (isset($map[$fecha])) {
                // si hay duplicados sumamos los montos (política conservadora)
                $map[$fecha] += $monto;
            } else {
                $map[$fecha] = $monto;
            }
        }

        ksort($map);

        $out = [];
        foreach ($map as $f => $m) {
            $out[] = ['fecha' => $f, 'monto' => $m];
        }

        return $out;
    }

    /**
     * Fallback cuando no hay suficientes datos: si no hay datos devuelve ceros; si hay 1 devuelve 12 meses con ese último monto;
     * si hay más de 1 devuelve 12 meses con la media histórica.
     * @param int $periodo
     * @param array $vals
     * @return array
     */
    protected function mesesDelAnoConFallback(int $periodo, array $vals): array
    {
        if (empty($vals)) {
            return $this->mesesDelAnoConCero($periodo);
        }

        if (count($vals) === 1) {
            $last = $vals[0]['monto'];
            $detalles = [];
            for ($m = 1; $m <= 12; $m++) {
                $fecha = Carbon::createFromDate($periodo, $m, 1)->toDateString();
                $detalles[] = ['fecha_proyectada' => $fecha, 'monto_proyectado' => round(max(0, $last), 2)];
            }
            return $detalles;
        }

        // >1: usar promedio simple
        $sum = 0.0;
        foreach ($vals as $v) { $sum += $v['monto']; }
        $avg = $sum / count($vals);
        $detalles = [];
        for ($m = 1; $m <= 12; $m++) {
            $fecha = Carbon::createFromDate($periodo, $m, 1)->toDateString();
            $detalles[] = ['fecha_proyectada' => $fecha, 'monto_proyectado' => round(max(0, $avg), 2)];
        }
        return $detalles;
    }

    /**
     * Número de meses completos entre dos fechas (from -> to). Si la fecha objetivo es anterior, retorna 0.
     * @param Carbon $from
     * @param Carbon $to
     * @return int
     */
    protected function monthsBetween(Carbon $from, Carbon $to): int
    {
        $years = $to->year - $from->year;
        $months = $to->month - $from->month;
        $diff = $years * 12 + $months;
        return max(0, intval($diff));
    }
}
