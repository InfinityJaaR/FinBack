<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Proyeccion;
use App\Models\Empresa;
use App\Models\User;

class ProyeccionFactory extends Factory
{
    protected $model = Proyeccion::class;

    public function definition()
    {
        return [
            'empresa_id' => Empresa::factory(),
            'user_id' => User::factory(),
            'metodo_usado' => $this->faker->randomElement(['minimos_cuadrados','incremento_porcentual','incremento_absoluto']),
            'periodo_proyectado' => $this->faker->year(),
        ];
    }
}
