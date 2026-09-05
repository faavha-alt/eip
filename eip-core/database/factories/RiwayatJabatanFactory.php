<?php

namespace Database\Factories;

use App\Enums\JenisRiwayatJabatan;
use App\Models\Pegawai;
use App\Models\RiwayatJabatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatJabatan>
 */
class RiwayatJabatanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'jenis' => fake()->randomElement(JenisRiwayatJabatan::cases()),
            'jabatan_nama' => fake()->jobTitle(),
            'jabatan_detail' => fake()->words(3, true),
            'no_sk' => fake()->bothify('###/UN27/KP/####'),
            'tmt_awal' => fake()->dateTimeBetween('-10 years', '-1 years'),
            'tmt_akhir' => null,
            'status' => null,
        ];
    }
}
