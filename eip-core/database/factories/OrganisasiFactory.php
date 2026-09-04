<?php

namespace Database\Factories;

use App\Enums\JenisOrganisasi;
use App\Models\Organisasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organisasi>
 */
class OrganisasiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'nama' => fake()->company(),
            'kode' => strtoupper(fake()->unique()->lexify('???')),
            'jenis' => JenisOrganisasi::Universitas,
            'alamat' => fake()->address(),
            'telepon' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'is_active' => true,
        ];
    }
}
