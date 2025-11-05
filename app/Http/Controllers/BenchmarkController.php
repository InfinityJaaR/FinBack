<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Periodo;
use App\Models\RatioDefinicion;
use App\Models\RatioValor;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class BenchmarkController extends Controller
{
    public function sectorRatio(Request $request)
    {
        $data = $request->validate([
            'rubro_id'   => ['required','integer','exists:rubros,id'],
            'ratio_id'   => ['required','integer','exists:ratios_definiciones,id'],
            'periodo_id' => ['required','integer','exists:periodos,id'],
        ]);

        $rubroId   = $data['rubro_id'];
        $ratioId   = $data['ratio_id'];
        $periodoId = $data['periodo_id'];

        // Empresas del sector
        $empresas = Empresa::where('rubro_id', $rubroId)
            ->get(['id','nombre']);

        // Valores por empresa (LEFT JOIN para mostrar “Sin datos”)
        $valores = DB::table('empresas as e')
            ->leftJoin('ratios_valores as rv', function ($j) use ($ratioId, $periodoId) {
                $j->on('rv.empresa_id', '=', 'e.id')
                  ->where('rv.ratio_id', '=', $ratioId)
                  ->where('rv.periodo_id', '=', $periodoId);
            })
            ->where('e.rubro_id', $rubroId)
            ->select('e.id as empresa_id', 'e.nombre', 'rv.valor')
            ->get();

        // Promedio del sector (solo con valores no nulos)
        $promedio = DB::table('ratios_valores as rv')
            ->join('empresas as e', 'e.id', '=', 'rv.empresa_id')
            ->where('e.rubro_id', $rubroId)
            ->where('rv.ratio_id', $ratioId)
            ->where('rv.periodo_id', $periodoId)
            ->whereNotNull('rv.valor')
            ->avg('rv.valor');

        // (opcional) regla de cumplimiento: mayor-mejor o menor-mejor
        $def = RatioDefinicion::find($ratioId, ['id','nombre','codigo','sentido']); // enum: MAYOR_MEJOR | MENOR_MEJOR | CERCANO_A_1
        $sentido = $def->sentido ?? 'MAYOR_MEJOR';

        return response()->json([
            'sector'   => ['id' => $rubroId],
            'ratio'    => ['id' => $ratioId, 'codigo' => $def->codigo ?? null, 'sentido' => $sentido],
            'periodo'  => ['id' => $periodoId],
            'promedio' => $promedio, // float|null
            'empresas' => $valores,  // [{empresa_id, nombre, valor}]
        ]);
    }
}
