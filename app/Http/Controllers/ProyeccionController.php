<?php
namespace App\Http\Controllers;

use App\Http\Requests\GenerarProyeccionRequest;
use App\Models\Empresa;
use App\Models\Proyeccion;
use App\Services\ProyeccionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProyeccionController extends Controller
{
    protected ProyeccionService $service;

    public function __construct(ProyeccionService $service)
    {
        $this->service = $service;
        // la ruta puede aplicar middleware de auth/permiso desde routes/api.php
    }

    /**
     * Genera o actualiza (upsert) la proyección para una empresa, método y año.
     */
    public function generar(GenerarProyeccionRequest $request, Empresa $empresa): JsonResponse
    {
        $data = $request->validated();
        $metodo = $data['metodo_usado'];
        $periodo = (int) $data['periodo_proyectado'];
        $options = $data['options'] ?? [];

    // Generar los 12 meses proyectados (array de ['anio' => AAAA, 'mes' => M, 'monto_proyectado' => float])
        $detalles = $this->service->generar($empresa, $metodo, $periodo, $options);

        // Guardar en DB: upsert maestro y reemplazar detalles en transacción
        $proyeccion = null;
        DB::transaction(function () use ($empresa, $metodo, $periodo, $detalles, &$proyeccion) {
            // Mantener al creador original: sólo asignar user_id si es creación
            $proyeccion = Proyeccion::firstOrNew([
                'empresa_id' => $empresa->id,
                'metodo_usado' => $metodo,
                'periodo_proyectado' => $periodo,
            ]);

            if (! $proyeccion->exists) {
                // Asignar creador si hay usuario autenticado
                try {
                    $proyeccion->user_id = auth()->id();
                } catch (\Throwable $e) {
                    // si no hay auth, dejar nulo
                }
                $proyeccion->save();
            } else {
                // Opcional: actualizar timestamp
                $proyeccion->touch();
            }

            // Borrar detalles previos y crear los nuevos (solo anio/mes + monto)
            $proyeccion->detalles()->delete();
            if (! empty($detalles)) {
                $proyeccion->detalles()->createMany($detalles);
            }
        });

        // Recargar con detalles
        $proyeccion->load('detalles');

        return response()->json([
            'success' => true,
            'data' => [
                'proyeccion' => $proyeccion,
                'detalles' => $proyeccion->detalles,
            ],
        ]);
    }

    /**
     * Listar proyecciones de una empresa.
     */
    public function index(Empresa $empresa): JsonResponse
    {
        $proyecciones = Proyeccion::where('empresa_id', $empresa->id)
            ->orderByDesc('periodo_proyectado')
            ->get(['id', 'metodo_usado', 'periodo_proyectado', 'created_at', 'updated_at']);

        return response()->json([
            'success' => true,
            'data' => $proyecciones,
        ]);
    }

    /**
     * Mostrar una proyección con sus detalles.
     */
    public function show(Empresa $empresa, Proyeccion $proyeccion): JsonResponse
    {
        if ($proyeccion->empresa_id !== $empresa->id) {
            return response()->json(['message' => 'Proyección no encontrada para la empresa indicada'], 404);
        }

        $proyeccion->load('detalles');

        return response()->json([
            'success' => true,
            'data' => [
                'proyeccion' => $proyeccion,
                'detalles' => $proyeccion->detalles,
            ],
        ]);
    }

    /**
     * Eliminar una proyección (y sus detalles por cascade).
     */
    public function destroy(Empresa $empresa, Proyeccion $proyeccion): JsonResponse
    {
        if ($proyeccion->empresa_id !== $empresa->id) {
            return response()->json(['message' => 'Proyección no encontrada para la empresa indicada'], 404);
        }

        $proyeccion->delete();

        return response()->json(null, 204);
    }
}
