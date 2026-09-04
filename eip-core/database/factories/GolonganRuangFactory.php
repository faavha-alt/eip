<?php

namespace Database\Factories;

use App\Models\GolonganRuang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GolonganRuang>
 */
class GolonganRuangFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->lexify('GOL_????'),
            'nama' => null,
            'tingkat' => fake()->numberBetween(1, 11),
            'is_active' => true,
        ];
    }
}
