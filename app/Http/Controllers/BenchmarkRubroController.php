<?php
namespace App\Http\Controllers;

use App\Models\BenchmarkRubro;
use App\Models\Rubro;
use App\Models\RatioDefinicion;
use Illuminate\Http\Request;

//nuevos use que se usan para la vista de benchmark pero de sectores o rubros idk
use App\Http\Requests\BenchmarkRubroRequest;
use Illuminate\Support\Facades\DB;

class BenchmarkRubroController extends Controller
{
    /** Listar benchmarks de un rubro */
    public function index(Rubro $rubro)
    {
        $benchmarks = $rubro->benchmarks()->with('ratioDefinicion')->get();
        return response()->json($benchmarks);
    }

    /** Crear o actualizar un benchmark para un rubro y ratio */
    public function store(Request $request, Rubro $rubro)
    {
        $data = $request->validate([
            'ratio_id' => ['required','integer','exists:ratios_definiciones,id'],
            'valor_promedio' => ['required','numeric'],
            'fuente' => ['nullable','string','max:150'],
        ]);

        // updateOrCreate por par (rubro_id, ratio_id)
        $benchmark = BenchmarkRubro::updateOrCreate(
            ['rubro_id' => $rubro->id, 'ratio_id' => $data['ratio_id']],
            ['valor_promedio' => $data['valor_promedio'], 'fuente' => $data['fuente'] ?? null]
        );

        return response()->json($benchmark, 201);
    }

    /** Eliminar un benchmark (verifica pertenencia al rubro) */
    public function destroy(Rubro $rubro, BenchmarkRubro $benchmark)
    {
        if ($benchmark->rubro_id !== $rubro->id) {
            return response()->json(['message' => 'Benchmark no pertenece al rubro indicado.'], 422);
        }

        $benchmark->delete();
        return response()->json(['message' => 'Benchmark eliminado.']);
    }


    // Nuevo metodo para obtener los ratios de un rubro con su valor de referencia y las empresas que lo cumplen
    public function rubroRatios(BenchmarkRubroRequest $request)
    {
        $rubroId   = (int)$request->input('rubro_id');
        $ratioId   = (int)$request->input('ratio_id');
        $periodoId = (int)$request->input('periodo_id');

        $ratio = DB::table('ratios_definiciones')
            ->select('id','nombre','sentido')
            ->where('id', $ratioId)
            ->first();

        $ref = DB::table('benchmarks_rubro')
            ->where('rubro_id', $rubroId)
            ->where('ratio_id', $ratioId)
            ->value('valor_promedio');

        $empresas = DB::table('empresas')
            ->select('id','nombre')
            ->where('rubro_id', $rubroId)
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        $valores = DB::table('ratios_valores')
            ->select('empresa_id','valor')
            ->where('periodo_id', $periodoId)
            ->where('ratio_id', $ratioId)
            ->get()
            ->keyBy('empresa_id');

        $lista = [];
        foreach ($empresas as $e) {
            $valor  = optional($valores->get($e->id))->valor;
            $cumple = null;

            if (!is_null($valor) && !is_null($ref)) {
                switch ($ratio->sentido) {
                    case 'MAYOR_MEJOR':
                        $cumple = (float)$valor >= (float)$ref;
                        break;
                    case 'MENOR_MEJOR':
                        $cumple = (float)$valor <= (float)$ref;
                        break;
                    case 'CERCANO_A_1':
                        $cumple = abs((float)$valor - 1.0) <= abs((float)$ref - 1.0);
                        break;
                    default:
                        $cumple = null;
                }
            }

            $lista[] = [
                'empresa_id' => $e->id,
                'empresa'    => $e->nombre,
                'valor'      => is_null($valor) ? null : (float)$valor,
                'cumple'     => $cumple,
            ];
        }

        return response()->json([
            'success'           => true,
            'rubro_id'          => $rubroId,
            'ratio'             => ['id'=>$ratioId, 'nombre'=>$ratio->nombre, 'sentido'=>$ratio->sentido],
            'periodo_id'        => $periodoId,
            'valor_referencia'  => is_null($ref) ? null : (float)$ref,
            'empresas'          => $lista,
        ]);
    }
}
