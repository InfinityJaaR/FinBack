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
     * Ahora cada detalle incluye anio, mes y (transitoriamente) fecha_proyectada.
     */
    public function generar(Empresa $empresa, string $metodo, int $periodo, array $options = []): array
    {
        // Obtener ventas históricas usando anio/mes (orden cronológico)
        $query = VentaMensual::where('empresa_id', $empresa->id)
            ->orderBy('anio')
            ->orderBy('mes');

        // Filtros opcionales de rango
        if (! empty($options['base_periodo_inicio'])) {
            $inicio = Carbon::parse($options['base_periodo_inicio']);
            $query->where(function ($q) use ($inicio) {
                $q->where('anio', '>', $inicio->year)
                  ->orWhere(function($q2) use ($inicio) {
                      $q2->where('anio', $inicio->year)->where('mes', '>=', $inicio->month);
                  });
            });
        }
        if (! empty($options['base_periodo_fin'])) {
            $fin = Carbon::parse($options['base_periodo_fin']);
            $query->where(function ($q) use ($fin) {
                $q->where('anio', '<', $fin->year)
                  ->orWhere(function($q2) use ($fin) {
                      $q2->where('anio', $fin->year)->where('mes', '<=', $fin->month);
                  });
            });
        }

        $ventas = $query->get();

        if (! empty($options['meses_historicos']) && is_int($options['meses_historicos'])) {
            $ventas = $ventas->slice(max(0, $ventas->count() - $options['meses_historicos']))->values();
        }

        switch ($metodo) {
            case 'minimos_cuadrados':
                return $this->minimosCuadrados($ventas, $periodo, $options);
            case 'incremento_porcentual':
                return $this->incrementoPorcentual($ventas, $periodo, $options);
            case 'incremento_absoluto':
                return $this->incrementoAbsoluto($ventas, $periodo, $options);
            default:
                return [];
        }
    }

    protected function minimosCuadrados(Collection $ventas, int $periodo, array $options = []): array
    {
        $vals = $this->prepareVentas($ventas);
        $N = count($vals);
        if ($N < 2) {
            return $this->mesesDelAnoConFallback($periodo, $vals);
        }

        // x = 1..N para regresión simple
        $sumaX = $sumaY = $sumaXX = $sumaXY = 0.0;
        for ($i = 0; $i < $N; $i++) {
            $x = $i + 1;
            $y = $vals[$i]['monto'];
            $sumaX += $x;
            $sumaY += $y;
            $sumaXX += $x * $x;
            $sumaXY += $x * $y;
        }
        $den = ($N * $sumaXX - ($sumaX * $sumaX));
        if (abs($den) < 1e-12) {
            return $this->mesesDelAnoConFallback($periodo, $vals);
        }
        $b = ($N * $sumaXY - $sumaX * $sumaY) / $den;
        $a = ($sumaY - $b * $sumaX) / $N;

        $lastDate = Carbon::create($vals[$N - 1]['anio'], $vals[$N - 1]['mes'], 1);
        $startTarget = Carbon::createFromDate($periodo, 1, 1)->startOfDay();
        $monthsDiff = $this->monthsBetween($lastDate, $startTarget);
        $xStart = $N + max(1, $monthsDiff);

        $detalles = [];
        for ($k = 0; $k < 12; $k++) {
            $x = $xStart + $k;
            $yPred = $a + $b * $x;
            $fechaCarbon = Carbon::createFromDate($periodo, 1, 1)->addMonths($k);
            $detalles[] = [
                'anio' => $fechaCarbon->year,
                'mes' => $fechaCarbon->month,
                'monto_proyectado' => round(max(0, $yPred), 2),
            ];
        }
        return $detalles;
    }

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
        $useGeometric = true;
        foreach ($pcts as $p) {
            if (1 + $p <= 0) { $useGeometric = false; break; }
        }
        if ($useGeometric) {
            $prod = 1.0;
            foreach ($pcts as $p) { $prod *= (1 + $p); }
            $avgPct = pow($prod, 1 / count($pcts)) - 1;
        } else {
            $avgPct = array_sum($pcts) / count($pcts);
        }

        $last = $vals[$N - 1]['monto'];
        $detalles = [];
        $startDate = Carbon::createFromDate($periodo, 1, 1);
        for ($k = 0; $k < 12; $k++) {
            $next = $last * (1 + $avgPct);
            $fechaCarbon = $startDate->copy()->addMonths($k);
            $detalles[] = [
                'anio' => $fechaCarbon->year,
                'mes' => $fechaCarbon->month,
                'monto_proyectado' => round(max(0, $next), 2),
            ];
            $last = $next;
        }
        return $detalles;
    }

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
            $fechaCarbon = $startDate->copy()->addMonths($k);
            $detalles[] = [
                'anio' => $fechaCarbon->year,
                'mes' => $fechaCarbon->month,
                'monto_proyectado' => round(max(0, $next), 2),
            ];
            $last = $next;
        }
        return $detalles;
    }

    protected function mesesDelAnoConCero(int $ano): array
    {
        $detalles = [];
        for ($m = 1; $m <= 12; $m++) {
            $fechaCarbon = Carbon::createFromDate($ano, $m, 1);
            $detalles[] = [
                'anio' => $ano,
                'mes' => $m,
                'monto_proyectado' => 0.00,
            ];
        }
        return $detalles;
    }

    protected function prepareVentas(Collection $ventas): array
    {
        $map = [];
        foreach ($ventas as $v) {
            $anio = (int)$v->anio;
            $mes = (int)$v->mes;
            $key = sprintf('%04d-%02d', $anio, $mes);
            $monto = floatval($v->monto ?? 0);
            if (isset($map[$key])) {
                $map[$key] += $monto; // sumar duplicados por seguridad
            } else {
                $map[$key] = $monto;
            }
        }
        ksort($map);
        $out = [];
        foreach ($map as $key => $m) {
            [$anioStr, $mesStr] = explode('-', $key);
            $out[] = [
                'anio' => (int)$anioStr,
                'mes' => (int)$mesStr,
                'monto' => $m,
            ];
        }
        return $out;
    }

    protected function mesesDelAnoConFallback(int $periodo, array $vals): array
    {
        if (empty($vals)) {
            return $this->mesesDelAnoConCero($periodo);
        }
        if (count($vals) === 1) {
            $last = $vals[0]['monto'];
            $detalles = [];
            for ($m = 1; $m <= 12; $m++) {
                $fechaCarbon = Carbon::createFromDate($periodo, $m, 1);
                $detalles[] = [
                    'anio' => $periodo,
                    'mes' => $m,
                    'monto_proyectado' => round(max(0, $last), 2)
                ];
            }
            return $detalles;
        }
        $sum = 0.0;
        foreach ($vals as $v) { $sum += $v['monto']; }
        $avg = $sum / count($vals);
        $detalles = [];
        for ($m = 1; $m <= 12; $m++) {
            $fechaCarbon = Carbon::createFromDate($periodo, $m, 1);
            $detalles[] = [
                'anio' => $periodo,
                'mes' => $m,
                'monto_proyectado' => round(max(0, $avg), 2)
            ];
        }
        return $detalles;
    }

    protected function monthsBetween(Carbon $from, Carbon $to): int
    {
        $years = $to->year - $from->year;
        $months = $to->month - $from->month;
        $diff = $years * 12 + $months;
        return max(0, intval($diff));
    }
}
