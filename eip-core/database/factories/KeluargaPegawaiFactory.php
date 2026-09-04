<?php

namespace Database\Factories;

use App\Enums\HubunganKeluarga;
use App\Enums\JenisKelamin;
use App\Models\KeluargaPegawai;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KeluargaPegawai>
 */
class KeluargaPegawaiFactory extends Factory
{
    public function definition(): array
    {
        $jenisKelamin = fake()->randomElement(JenisKelamin::cases());

        return [
            'pegawai_id' => Pegawai::factory(),
            'hubungan' => fake()->randomElement(HubunganKeluarga::cases()),
            'nama' => $jenisKelamin === JenisKelamin::LakiLaki ? fake()->name('male') : fake()->name('female'),
            'jenis_kelamin' => $jenisKelamin,
            'tanggal_lahir' => fake()->dateTimeBetween('-70 years', '-1 years'),
            'pekerjaan' => fake()->jobTitle(),
            'status_tanggungan' => true,
            'no_hp' => fake()->numerify('08##########'),
            'keterangan' => null,
        ];
    }
}
