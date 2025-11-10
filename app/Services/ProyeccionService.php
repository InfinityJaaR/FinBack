<?php
namespace App\Services;

use App\Models\Empresa;
use App\Models\VentaMensual;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProyeccionService
{
    /**
     * Genera un array de 12 detalles proyectados para el año dado.
     * Ahora cada detalle incluye anio, mes y (transitoriamente) fecha_proyectada.
     */
    public function generar(Empresa $empresa, string $metodo, int $periodo, array $options = []): array
    {
        // Año inmediatamente anterior requerido
        $anioBase = $periodo - 1;
        // Tomar SOLO los 12 meses del año anterior
        $ventas = VentaMensual::where('empresa_id', $empresa->id)
            ->where('anio', $anioBase)
            ->orderBy('mes')
            ->get();

        // Log de control: valores base usados
        try {
            $datosBase = $this->prepareVentas($ventas);
            Log::info('ProyeccionService.datos_base', [
                'empresa_id' => $empresa->id,
                'periodo_proyectado' => $periodo,
                'metodo_usado' => $metodo,
                'anio_base' => $anioBase,
                'total_registros' => count($datosBase),
                'datos' => $datosBase,
            ]);
        } catch (\Throwable $e) {}

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

    // Como usamos exactamente el año anterior, la serie termina en mes 12 de (periodo-1)
    // Continuamos la secuencia x en enero del año proyectado como N+1, N+2, ... N+12
    $xStart = $N + 1;

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

        // 1. Variaciones porcentuales simples entre meses consecutivos
        $eps = 1e-6; // para evitar división por cero
        $variaciones = [];
        for ($i = 1; $i < $N; $i++) {
            $prev = $vals[$i - 1]['monto'];
            $curr = $vals[$i]['monto'];
            $den = max($eps, $prev);
            $variaciones[] = ($curr - $prev) / $den; // (ventaActual - ventaAnterior) / ventaAnterior
        }

        // 2. Promedio aritmético de las variaciones
        $promedioVariacion = empty($variaciones) ? 0.0 : array_sum($variaciones) / count($variaciones);

        // 3. Proyección encadenada: cada nuevo mes = anterior + anterior * promedioVariacion
        $ventaAnterior = $vals[$N - 1]['monto']; // última venta histórica
        $startDate = Carbon::createFromDate($periodo, 1, 1);
        $detalles = [];
        for ($k = 0; $k < 12; $k++) {
            $ventaProyectada = $ventaAnterior + ($ventaAnterior * $promedioVariacion);
            $fechaCarbon = $startDate->copy()->addMonths($k);
            $detalles[] = [
                'anio' => $fechaCarbon->year,
                'mes' => $fechaCarbon->month,
                'monto_proyectado' => round(max(0, $ventaProyectada), 2),
            ];
            $ventaAnterior = $ventaProyectada; // encadenar para siguiente iteración
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
