<?php

namespace Database\Factories;

use App\Enums\JenisKelamin;
use App\Enums\StatusKepegawaian;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pegawai>
 */
class PegawaiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenisKelamin = fake()->randomElement(JenisKelamin::cases());

        return [
            'nip' => fake()->unique()->numerify('19##########0#1###'),
            'nik' => fake()->unique()->numerify('##################'),
            'id_simpeg' => fake()->unique()->numerify('SIMPEG#######'),
            'nama_lengkap' => $jenisKelamin === JenisKelamin::LakiLaki
                ? fake()->name('male')
                : fake()->name('female'),
            'gelar_depan' => null,
            'gelar_belakang' => null,
            'jenis_kelamin' => $jenisKelamin,
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->dateTimeBetween('-60 years', '-22 years'),
            'email' => fake()->unique()->safeEmail(),
            'no_hp' => fake()->numerify('08##########'),
            'status_kepegawaian' => fake()->randomElement(StatusKepegawaian::cases()),
            'foto' => null,
            'tanggal_masuk' => fake()->dateTimeBetween('-20 years', '-1 years'),
            'tanggal_keluar' => null,
            'is_active' => true,
        ];
    }
}
