<?php
namespace App\Http\Controllers;

use App\Models\BenchmarkRubro;
use App\Models\Rubro;
use App\Models\RatioDefinicion;
use Illuminate\Http\Request;

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
}
