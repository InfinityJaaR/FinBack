<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\Rubro;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        // Asegura que siempre haya rubros creados antes de generar empresas
        $rubro_ids = Rubro::pluck('id')->all();

        return [
            'rubro_id' => $this->faker->randomElement($rubro_ids),
            'codigo' => $this->faker->unique()->randomNumber(5),
            'nombre' => $this->faker->company() . ' S.A. de C.V.',
            'descripcion' => $this->faker->catchPhrase(),
            'activo' => true,
        ];
    }
}