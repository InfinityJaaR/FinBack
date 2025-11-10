<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarMapeoRequest;
use App\Http\Requests\VerMapeoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MapeoCatalogoController extends Controller
{

public function listas(): \Illuminate\Http\JsonResponse
{
    // 1) Trae TODOS los rubros (por si luego los vuelves a necesitar en la UI)
    $rubros = \DB::table('rubros')
        ->select('id','nombre')
        ->orderBy('nombre')
        ->get();

    // 2) Trae TODAS las empresas, sin filtrar por rubro
    $empresas = \DB::table('empresas')
        ->select('id','nombre','rubro_id')
        ->orderBy('nombre')
        ->get();

    return response()->json([
        'success'  => true,
        'rubros'   => $rubros,
        'empresas' => $empresas,
    ]);
}


    // GET: conceptos + cuentas de la empresa + mapeos existentes
    public function data(VerMapeoRequest $request, int $empresa): JsonResponse
    {
        $conceptos = DB::table('conceptos_financieros')
            ->select('id','codigo','nombre_concepto')
            ->orderBy('codigo')
            ->get();

        $cuentasEmpresa = DB::table('catalogo_cuentas as c')
            ->join('detalles_estado as d', 'c.id', '=', 'd.catalogo_cuenta_id')
            ->join('estados as e', 'd.estado_id', '=', 'e.id')
            ->where('c.empresa_id', $empresa)
            ->where('e.empresa_id', $empresa)
            ->where('d.usar_en_ratios', 1)
            ->select('c.id', 'c.codigo', 'c.nombre')
            ->distinct()
            ->orderBy('c.codigo')
            ->get();


        $mapeos = DB::table('cuenta_concepto as cc')
            ->join('catalogo_cuentas as c', 'cc.catalogo_cuenta_id', '=', 'c.id')
            ->where('c.empresa_id', $empresa)
            ->select('cc.concepto_id', 'cc.catalogo_cuenta_id')
            ->get();

        return response()->json([
            'success'        => true,
            'conceptos'      => $conceptos,
            'cuentasEmpresa' => $cuentasEmpresa,
            'mapeos'         => $mapeos,
        ]);
    }

    // POST: upsert de mapeos
    public function upsert(GuardarMapeoRequest $request): JsonResponse
    {
        $empresaId = (int) $request->input('empresa_id');
        $mapeos    = $request->input('mapeos', []);

        foreach ($mapeos as $conceptoId => $catalogoCuentaId) {
            // des-asignar si viene null (borra el mapeo de esa empresa)
            if ($catalogoCuentaId === null) {
                DB::table('cuenta_concepto')
                    ->where('concepto_id', (int)$conceptoId)
                    ->whereIn('catalogo_cuenta_id', function($q) use ($empresaId) {
                        $q->select('id')->from('catalogo_cuentas')->where('empresa_id', $empresaId);
                    })
                    ->delete();
                continue;
            }

            DB::table('cuenta_concepto')->updateOrInsert(
                [
                    'concepto_id'        => (int)$conceptoId,
                    'catalogo_cuenta_id' => (int)$catalogoCuentaId,
                ],
                [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return response()->json(['success' => true, 'message' => 'Mapeos guardados.']);
    }
}
