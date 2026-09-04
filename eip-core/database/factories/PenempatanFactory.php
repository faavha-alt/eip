<?php

namespace Database\Factories;

use App\Enums\StatusPenempatan;
use App\Models\Jabatan;
use App\Models\Pegawai;
use App\Models\Penempatan;
use App\Models\UnitKerja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penempatan>
 */
class PenempatanFactory extends Factory
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
            'unit_kerja_id' => UnitKerja::factory(),
            'jabatan_id' => Jabatan::factory(),
            'tgl_mulai' => fake()->dateTimeBetween('-10 years', '-1 years'),
            'tgl_selesai' => null,
            'is_posisi_utama' => true,
            'status' => StatusPenempatan::Aktif,
        ];
    }
}
