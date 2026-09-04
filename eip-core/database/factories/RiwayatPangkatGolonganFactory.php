<?php

namespace Database\Factories;

use App\Models\GolonganRuang;
use App\Models\Pegawai;
use App\Models\RiwayatPangkatGolongan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatPangkatGolongan>
 */
class RiwayatPangkatGolonganFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'golongan_ruang_id' => GolonganRuang::query()->inRandomOrder()->value('id') ?? GolonganRuang::factory(),
            'tmt' => fake()->dateTimeBetween('-15 years', '-1 years'),
            'no_sk' => fake()->unique()->bothify('SK-####/UN27.??/??/####'),
            'tgl_sk' => fake()->dateTimeBetween('-15 years', '-1 years'),
        ];
    }
}
