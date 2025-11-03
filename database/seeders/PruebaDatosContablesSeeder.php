<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;
use App\Models\Periodo;
use App\Models\Estado;
use App\Models\DetalleEstado;
use App\Models\CatalogoCuenta;
use Carbon\Carbon;

class PruebaDatosContablesSeeder extends Seeder
{
    /**
     * Seeder de datos contables de ejemplo para pruebas del RatioCalculator.
     * Este seeder intenta ser conservador: solo inserta datos si existen las entidades necesarias.
     */
    public function run()
    {
        $empresa = Empresa::first();
        if (! $empresa) {
            $this->command->warn('No se encontró ninguna Empresa. Crear una empresa antes de ejecutar este seeder.');
            return;
        }

        // buscar o crear un periodo actual simple
        $anio = date('Y');
        $periodo = Periodo::firstOrCreate([
            'anio' => $anio,
            'mes' => 12,
        ], [
            'nombre' => "Periodo {$anio}",
        ]);

        // crear un estado contable (balance y resultado) para la empresa/periodo
        $estado = Estado::create([
            'empresa_id' => $empresa->id,
            'periodo_id' => $periodo->id,
            'tipo' => 'BALANCE', // ajustar según enum si aplica
            'fecha' => Carbon::now()->toDateString(),
        ]);

        // Intentar insertar detalles simples: buscar cuentas ejemplo y asignar valores
        $costoVentas = CatalogoCuenta::where('nombre', 'like', '%Costo%')->first();
        $inventario = CatalogoCuenta::where('nombre', 'like', '%Inventario%')->first();
        $ventas = CatalogoCuenta::where('nombre', 'like', '%Venta%')->first();
        $cxc = CatalogoCuenta::where('nombre', 'like', '%Clientes%')->first();

        $samples = [];
        if ($costoVentas) $samples[] = ['catalogo_cuenta_id' => $costoVentas->id, 'monto' => 100000.0];
        if ($inventario) $samples[] = ['catalogo_cuenta_id' => $inventario->id, 'monto' => 25000.0];
        if ($ventas) $samples[] = ['catalogo_cuenta_id' => $ventas->id, 'monto' => 300000.0];
        if ($cxc) $samples[] = ['catalogo_cuenta_id' => $cxc->id, 'monto' => 80000.0];

        if (empty($samples)) {
            $this->command->warn('No se encontraron cuentas relevantes en catalogo_cuentas para poblar DetalleEstado. Revisar catálogo.');
            return;
        }

        foreach ($samples as $s) {
            DetalleEstado::create([
                'estado_id' => $estado->id,
                'catalogo_cuenta_id' => $s['catalogo_cuenta_id'],
                'monto' => $s['monto'],
            ]);
            $this->command->info('DetalleEstado creado para cuenta id ' . $s['catalogo_cuenta_id']);
        }

        $this->command->info('Datos contables de prueba sembrados (si las cuentas fueron encontradas).');
    }
}
