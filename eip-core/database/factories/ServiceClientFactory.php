<?php

namespace Database\Factories;

use App\Models\ServiceClient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceClient>
 */
class ServiceClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->lexify('svc-????'),
            'nama' => fake()->company(),
            'is_active' => true,
        ];
    }
}
