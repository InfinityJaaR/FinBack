<?php

namespace Database\Factories;

use App\Models\VentaMensual;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class VentaMensualFactory extends Factory
{
    protected $model = VentaMensual::class;

    public function definition(): array
    {
        // Genera fechas consecutivas en el pasado
        static $date;
        $date = $date ?: now()->subMonths(12)->startOfMonth();
        $date->addMonth();

        // Asegura que haya empresas creadas
        $empresa_ids = Empresa::pluck('id')->all();

        return [
            'empresa_id' => $this->faker->randomElement($empresa_ids),
            'fecha' => $date->copy()->format('Y-m-d'),
            // Montos de ventas que varían ligeramente para la proyección
            'monto' => $this->faker->randomFloat(2, 50000, 200000),
        ];
    }
}