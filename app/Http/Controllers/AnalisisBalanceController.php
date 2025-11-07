<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use App\Models\DetalleEstado;
use App\Models\CatalogoCuenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnalisisBalanceController extends Controller
{
    /**
     * Análisis vertical del Balance General.
     * - Si el solicitante es Analista, usa su empresa_id.
     * - Si es Administrador, debe enviar empresa_id.
     * Denominadores actuales por código: 1000 (ACTIVO), 2000 (PASIVO), 3000 (PATRIMONIO).
     */
    public function vertical(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'periodo_id' => 'required|exists:periodos,id',
            // empresa_id es requerido sólo para administradores (se valida más abajo)
            'seccion' => 'sometimes|in:ACTIVO,PASIVO,PATRIMONIO',
            // Nivel opcional: por defecto DETALLE
            'nivel' => 'sometimes|in:MAYOR,SUB_MAYOR,DETALLE,TODOS',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $empresaId = $this->resolverEmpresaId($request);
        if (!$empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'empresa_id es requerido para administradores o el analista no tiene empresa asignada.'
            ], 422);
        }

        $estado = Estado::with(['detalles.catalogoCuenta'])
            ->where('empresa_id', $empresaId)
            ->where('periodo_id', $request->periodo_id)
            ->where('tipo', 'BALANCE')
            ->first();

        if (!$estado) {
            return response()->json([
                'success' => false,
                'message' => 'No existe un Balance para la empresa y periodo indicados.'
            ], 404);
        }

        // Construir mapa de totales por sección usando códigos 1000, 2000, 3000
        $totalesSeccion = [
            'ACTIVO' => 0.0,
            'PASIVO' => 0.0,
            'PATRIMONIO' => 0.0,
        ];

        foreach ($estado->detalles as $detalle) {
            $cuenta = $detalle->catalogoCuenta;
            if (!$cuenta) { continue; }
            if (in_array($cuenta->codigo, ['1000','1'])) { $totalesSeccion['ACTIVO'] = (float)$detalle->monto; }
            if (in_array($cuenta->codigo, ['2000','2'])) { $totalesSeccion['PASIVO'] = (float)$detalle->monto; }
            if (in_array($cuenta->codigo, ['3000','3'])) { $totalesSeccion['PATRIMONIO'] = (float)$detalle->monto; }
        }

        $seccionFiltro = $request->input('seccion');
        $nivelFiltro = $request->input('nivel', 'TODOS');
        $resultados = [];

        foreach ($estado->detalles as $detalle) {
            $cuenta = $detalle->catalogoCuenta;
            if (!$cuenta) { continue; }

            // Limitar a balance general
            if ($cuenta->estado_financiero !== 'BALANCE_GENERAL') { continue; }

            // Determinar sección por tipo de cuenta (ACTIVO/PASIVO/PATRIMONIO)
            $seccion = $cuenta->tipo;
            if (!isset($totalesSeccion[$seccion])) { continue; }
            if ($seccionFiltro && $seccion !== $seccionFiltro) { continue; }

            // Clasificación por patrón de 4 dígitos: #000 (MAYOR), ##00 (SUB_MAYOR), ###0 (DETALLE)
            $nivel = $this->clasificarNivelCodigo($cuenta->codigo);

            // Filtro por nivel solicitado
            if ($nivelFiltro !== 'TODOS' && $nivel !== $nivelFiltro) { continue; }

            // Para vertical: por defecto (DETALLE) solo hojas no calculadas y sin denominadores
            if ($nivelFiltro === 'DETALLE') {
                if (in_array($cuenta->codigo, ['1000','2000','3000'])) { continue; }
                if ((bool)$cuenta->es_calculada) { continue; }
            }

            $denominador = (float)($totalesSeccion[$seccion] ?? 0);
            $porcentaje = $denominador != 0.0 ? ((float)$detalle->monto) / $denominador : null;

            $resultados[] = [
                'catalogo_cuenta_id' => $cuenta->id,
                'codigo' => $cuenta->codigo,
                'nombre' => $cuenta->nombre,
                'seccion' => $seccion,
                'nivel' => $nivel,
                'monto' => (float)$detalle->monto,
                'denominador' => $denominador,
                'porcentaje' => $porcentaje,
            ];
        }

        // Orden por código para una presentación consistente
        usort($resultados, function ($a, $b) {
            return strcmp($a['codigo'], $b['codigo']);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'empresa_id' => $empresaId,
                'periodo_id' => (int)$request->periodo_id,
                'totales' => $totalesSeccion,
                'lineas' => $resultados,
            ],
        ]);
    }

    /**
     * Análisis horizontal del Balance General entre dos periodos.
     */
    public function horizontal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'periodo_base_id' => 'required|exists:periodos,id',
            'periodo_comp_id' => 'required|exists:periodos,id',
            'nivel' => 'sometimes|in:MAYOR,SUB_MAYOR,DETALLE,TODOS',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $empresaId = $this->resolverEmpresaId($request);
        if (!$empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'empresa_id es requerido para administradores o el analista no tiene empresa asignada.'
            ], 422);
        }

        $estadoBase = Estado::with(['detalles.catalogoCuenta'])
            ->where('empresa_id', $empresaId)
            ->where('periodo_id', $request->periodo_base_id)
            ->where('tipo', 'BALANCE')
            ->first();

        $estadoComp = Estado::with(['detalles.catalogoCuenta'])
            ->where('empresa_id', $empresaId)
            ->where('periodo_id', $request->periodo_comp_id)
            ->where('tipo', 'BALANCE')
            ->first();

        if (!$estadoBase || !$estadoComp) {
            return response()->json([
                'success' => false,
                'message' => 'No existen Balances para ambos periodos indicados.'
            ], 404);
        }

        $nivelFiltro = $request->input('nivel', 'TODOS');

        $byCuenta = function ($estado) use ($nivelFiltro) {
            $map = [];
            foreach ($estado->detalles as $detalle) {
                $cuenta = $detalle->catalogoCuenta;
                if (!$cuenta) { continue; }
                if ($cuenta->estado_financiero !== 'BALANCE_GENERAL') { continue; }
                $nivel = $this->clasificarNivelCodigo($cuenta->codigo);
                if ($nivelFiltro !== 'TODOS' && $nivel !== $nivelFiltro) { continue; }
                // Para horizontal: por defecto (DETALLE) solo hojas no calculadas y sin denominadores
                if ($nivelFiltro === 'DETALLE') {
                    if (in_array($cuenta->codigo, ['1000','2000','3000'])) { continue; }
                    if ((bool)$cuenta->es_calculada) { continue; }
                }
                $map[$cuenta->id] = [
                    'cuenta' => $cuenta,
                    'monto' => (float)$detalle->monto,
                    'nivel' => $nivel,
                ];
            }
            return $map;
        };

        $base = $byCuenta($estadoBase);
        $comp = $byCuenta($estadoComp);

        $cuentaIds = array_unique(array_merge(array_keys($base), array_keys($comp)));

        $resultados = [];
        foreach ($cuentaIds as $cuentaId) {
            $cuenta = ($base[$cuentaId]['cuenta'] ?? $comp[$cuentaId]['cuenta'] ?? null);
            if (!$cuenta) { continue; }
            $montoBase = $base[$cuentaId]['monto'] ?? 0.0;
            $montoComp = $comp[$cuentaId]['monto'] ?? 0.0;
            $variacionAbs = $montoComp - $montoBase;
            $variacionPct = $montoBase != 0.0 ? $variacionAbs / $montoBase : null;

            $resultados[] = [
                'catalogo_cuenta_id' => $cuenta->id,
                'codigo' => $cuenta->codigo,
                'nombre' => $cuenta->nombre,
                'seccion' => $cuenta->tipo,
                'nivel' => $base[$cuentaId]['nivel'] ?? $comp[$cuentaId]['nivel'] ?? null,
                'monto_base' => $montoBase,
                'monto_comp' => $montoComp,
                'variacion_abs' => $variacionAbs,
                'variacion_pct' => $variacionPct,
            ];
        }

        usort($resultados, function ($a, $b) {
            return strcmp($a['codigo'], $b['codigo']);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'empresa_id' => $empresaId,
                'periodo_base_id' => (int)$request->periodo_base_id,
                'periodo_comp_id' => (int)$request->periodo_comp_id,
                'lineas' => $resultados,
            ],
        ]);
    }

    /**
     * Resolver empresa_id según rol del usuario.
     * - Analista: usa su empresa_id.
     * - Administrador: usa empresa_id del request.
     * Deja abierta la extensión para mapear denominadores por cuenta_concepto.
     */
    private function resolverEmpresaId(Request $request): ?int
    {
        $user = $request->user();
        if (!$user) { return null; }

        $roles = $user->roles->pluck('name')->toArray();
        $esAdmin = in_array('Administrador', $roles);
        $esAnalista = in_array('Analista Financiero', $roles) && !$esAdmin;

        if ($esAnalista) {
            return $user->empresa_id ?: null;
        }

        // Administrador u otros roles con permiso
        $empresaId = (int)($request->input('empresa_id'));
        return $empresaId ?: null;
    }

    /**
     * Clasifica un código en niveles:
     * - MAYOR: "#000" o "#" (1 dígito)
     * - SUB_MAYOR: "##00" o "#.#"
     * - DETALLE: "###0" o cualquier otra combinación (incluye formatos con punto distintos a #.#)
     * - MOVIMIENTO: cuando no calza con ninguno de los anteriores
     */
    private function clasificarNivelCodigo(?string $codigo): string
    {
        if (!$codigo) { return 'MOVIMIENTO'; }

        // Formato con punto(s)
        if (str_contains($codigo, '.')) {
            if (preg_match('/^\d\.\d$/', $codigo)) { return 'SUB_MAYOR'; }
            if (preg_match('/^\d(\.\d+)+$/', $codigo)) { return 'DETALLE'; }
            return 'MOVIMIENTO';
        }

        // Formato numérico de 1 a 4 dígitos
        if (preg_match('/^\d{1}$/', $codigo)) { return 'MAYOR'; }
        if (!preg_match('/^\d{4}$/', $codigo)) { return 'MOVIMIENTO'; }
        if (preg_match('/^\d000$/', $codigo)) { return 'MAYOR'; }
        if (preg_match('/^\d{2}00$/', $codigo)) { return 'SUB_MAYOR'; }
        if (preg_match('/^\d{3}0$/', $codigo)) { return 'DETALLE'; }
        return 'DETALLE';
    }
}


