<?php

namespace Database\Factories;

use App\Models\StatusKepegawaian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatusKepegawaian>
 */
class StatusKepegawaianFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->lexify('status_????'),
            'nama' => fake()->words(2, true),
            'is_active' => true,
        ];
    }
}
