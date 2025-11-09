<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\VentaMensual;
use App\Http\Requests\StoreVentasMensualesRequest;
use App\Http\Requests\UpdateVentaMensualRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VentaMensualController extends Controller
{
    /**
     * Listar ventas mensuales de una empresa.
     * Param opcional year=YYYY, per_page para paginar.
     */
    public function index(Request $request, Empresa $empresa): JsonResponse
    {
        $query = VentaMensual::where('empresa_id', $empresa->id)
            ->orderBy('anio')
            ->orderBy('mes');
        if ($year = $request->query('year')) {
            $query->where('anio', (int)$year);
        }
        $perPage = (int)$request->query('per_page', 0);
        if ($perPage > 0) {
            $perPage = max(1, min(100, $perPage));
            $ventas = $query->paginate($perPage);
        } else {
            $ventas = $query->get();
        }
        return response()->json(['success' => true, 'data' => $ventas]);
    }

    /**
     * Bulk upsert de ventas mensuales.
     * Cada item: {fecha: YYYY-MM-DD, monto: number}. Se normaliza a primer día del mes.
     */
    public function store(StoreVentasMensualesRequest $request, Empresa $empresa): JsonResponse
    {
        $ventas = $request->validated()['ventas'];
        $result = [];
        DB::transaction(function() use ($ventas, $empresa, &$result) {
            foreach ($ventas as $v) {
                $anio = (int)$v['anio'];
                $mes = (int)$v['mes'];
                $vm = VentaMensual::updateOrCreate(
                    ['empresa_id' => $empresa->id, 'anio' => $anio, 'mes' => $mes],
                    ['monto' => $v['monto']]
                );
                $result[] = $vm;
            }
        });
        return response()->json(['success' => true, 'data' => $result], 201);
    }

    /**
     * Actualizar una venta mensual individual.
     */
    public function update(UpdateVentaMensualRequest $request, Empresa $empresa, VentaMensual $ventaMensual): JsonResponse
    {
        if ($ventaMensual->empresa_id !== $empresa->id) {
            return response()->json(['message' => 'Registro no corresponde a la empresa'], 404);
        }
        $data = $request->validated();
        if (isset($data['anio']) || isset($data['mes'])) {
            $anio = isset($data['anio']) ? (int)$data['anio'] : $ventaMensual->anio;
            $mes = isset($data['mes']) ? (int)$data['mes'] : $ventaMensual->mes;
            // Verificar colisión
            $exists = VentaMensual::where('empresa_id', $empresa->id)
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->where('id', '!=', $ventaMensual->id)
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'Ya existe un registro para ese mes.'], 422);
            }
            $data['anio'] = $anio;
            $data['mes'] = $mes;
        }
        $ventaMensual->update($data);
        return response()->json(['success' => true, 'data' => $ventaMensual->fresh()]);
    }

    /**
     * Eliminar una venta mensual.
     */
    public function destroy(Empresa $empresa, VentaMensual $ventaMensual): JsonResponse
    {
        if ($ventaMensual->empresa_id !== $empresa->id) {
            return response()->json(['message' => 'Registro no corresponde a la empresa'], 404);
        }
        $ventaMensual->delete();
        return response()->json(null, 204);
    }
}
