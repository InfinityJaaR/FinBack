<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ProyeccionDetalle;
use App\Models\Proyeccion;

class ProyeccionDetalleFactory extends Factory
{
    protected $model = ProyeccionDetalle::class;

    public function definition()
    {
        return [
            'proyeccion_id' => Proyeccion::factory(),
            'fecha_proyectada' => $this->faker->dateTimeBetween('-1 years', '+1 years')->format('Y-m-d'),
            'monto_proyectado' => $this->faker->randomFloat(2, 0, 100000),
        ];
    }
}
