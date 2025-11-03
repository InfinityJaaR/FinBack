<?php
// app/Http/Controllers/PeriodoController.php
namespace App\Http\Controllers;

use App\Models\Periodo;
use Illuminate\Http\JsonResponse;

class PeriodoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Periodo::select('id', 'anio')->orderBy('anio', 'desc')->get());
    }
}
