<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\Pendidikan;
use App\Models\RiwayatPendidikan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatPendidikan>
 */
class RiwayatPendidikanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'pendidikan_id' => Pendidikan::query()->inRandomOrder()->value('id') ?? Pendidikan::factory(),
            'nama_institusi' => fake()->company(),
            'program_studi' => fake()->words(2, true),
            'tahun_lulus' => fake()->numberBetween(1980, 2025),
            'no_ijazah' => fake()->unique()->bothify('IJZ-####-????'),
        ];
    }
}
