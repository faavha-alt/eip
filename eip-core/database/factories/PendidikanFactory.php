<?php

namespace Database\Factories;

use App\Models\Pendidikan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pendidikan>
 */
class PendidikanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->lexify('jenjang_????'),
            'nama' => fake()->words(2, true),
            'jenjang' => fake()->numberBetween(1, 6),
            'is_active' => true,
        ];
    }
}
