<?php

namespace Database\Factories;

use App\Enums\JenisUnitKerja;
use App\Models\Organisasi;
use App\Models\UnitKerja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnitKerja>
 */
class UnitKerjaFactory extends Factory
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
            'organisasi_id' => Organisasi::factory(),
            'nama' => fake()->unique()->words(3, true),
            'kode' => strtoupper(fake()->unique()->lexify('????')),
            'jenis_unit' => fake()->randomElement(JenisUnitKerja::cases()),
            'kepala_id' => null,
            'is_active' => true,
        ];
    }
}
