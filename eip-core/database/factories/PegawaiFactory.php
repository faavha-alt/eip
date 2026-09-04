<?php

namespace Database\Factories;

use App\Enums\Agama;
use App\Enums\JenisKelamin;
use App\Enums\JenisPegawai;
use App\Enums\StatusPerkawinan;
use App\Models\GolonganRuang;
use App\Models\Pegawai;
use App\Models\Pendidikan;
use App\Models\StatusKepegawaian;
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
            'id_sumber' => null,
            'nip' => fake()->unique()->numerify('19##########0#1###'),
            'nik' => fake()->unique()->numerify('##################'),
            'npwp' => fake()->unique()->numerify('##.###.###.#-###.###'),
            'nuptk' => fake()->unique()->numerify('################'),
            'id_simpeg' => fake()->unique()->numerify('SIMPEG#######'),
            'no_seri_kepeg' => fake()->unique()->bothify('?.######'),
            'nama_lengkap' => $jenisKelamin === JenisKelamin::LakiLaki
                ? fake()->name('male')
                : fake()->name('female'),
            'gelar_depan' => null,
            'gelar_belakang' => null,
            'jenis_kelamin' => $jenisKelamin,
            'agama' => fake()->randomElement(Agama::cases()),
            'status_perkawinan' => fake()->randomElement(StatusPerkawinan::cases()),
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->dateTimeBetween('-60 years', '-22 years'),
            'alamat_domisili' => fake()->address(),
            'email' => fake()->unique()->safeEmail(),
            'no_hp' => fake()->numerify('08##########'),
            'no_bpjs_kesehatan' => fake()->unique()->numerify('##############'),
            'no_bpjs_ketenagakerjaan' => null,
            'no_taspen' => fake()->unique()->numerify('#######'),
            // Master status/pendidikan/golongan diseed lewat migrasi; ambil
            // salah satu yg sudah ada drpd bikin baru tiap kali factory dipakai.
            'status_kepegawaian_id' => StatusKepegawaian::query()->inRandomOrder()->value('id')
                ?? StatusKepegawaian::factory(),
            'jenis_pegawai' => fake()->randomElement(JenisPegawai::cases()),
            'pendidikan_terakhir_id' => Pendidikan::query()->inRandomOrder()->value('id')
                ?? Pendidikan::factory(),
            'golongan_ruang_id' => GolonganRuang::query()->inRandomOrder()->value('id')
                ?? GolonganRuang::factory(),
            'tmt_golongan' => fake()->dateTimeBetween('-10 years', '-1 years'),
            'foto' => null,
            'tanggal_masuk' => fake()->dateTimeBetween('-20 years', '-1 years'),
            'tmt_cpns' => fake()->dateTimeBetween('-20 years', '-15 years'),
            'tmt_pns' => fake()->dateTimeBetween('-14 years', '-10 years'),
            'tanggal_keluar' => null,
            'is_active' => true,
        ];
    }
}
