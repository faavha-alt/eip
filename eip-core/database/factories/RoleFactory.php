<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->lexify('role-????'),
            'nama' => fake()->words(2, true),
            'deskripsi' => null,
            'is_active' => true,
        ];
    }
}
