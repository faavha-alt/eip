<?php

namespace Database\Factories;

use App\Enums\JenisJabatan;
use App\Models\Jabatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jabatan>
 */
class JabatanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->jobTitle(),
            'kode' => strtoupper(fake()->unique()->lexify('JBT???')),
            'jenis' => fake()->randomElement(JenisJabatan::cases()),
            'level' => fake()->numberBetween(1, 10),
            'eselon' => null,
            'deskripsi' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
