<?php

namespace Database\Factories;

use App\Enums\JenisDokumenPegawai;
use App\Models\DokumenPegawai;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DokumenPegawai>
 */
class DokumenPegawaiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'jenis' => fake()->randomElement(JenisDokumenPegawai::cases()),
            'nomor_dokumen' => fake()->unique()->bothify('DOK-####/????'),
            'tanggal_dokumen' => fake()->dateTimeBetween('-15 years', 'now'),
            'file_path' => null,
            'keterangan' => null,
        ];
    }
}
