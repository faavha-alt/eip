<?php

namespace Database\Seeders;

use App\Enums\JenisOrganisasi;
use App\Enums\JenisUnitKerja;
use App\Models\Organisasi;
use App\Models\UnitKerja;
use Illuminate\Database\Seeder;

/**
 * Kerangka organisasi TERVERIFIKASI (bukan tebakan): Universitas Sebelas
 * Maret > Fakultas MIPA. Ini satu-satunya bagian yang aman diseed langsung
 * (fakta publik, bukan PII).
 *
 * Prodi, unit kerja lain, dan jabatan yang SEBENARNYA dibentuk otomatis oleh
 * `php artisan pegawai:import` dari data nyata (docs/data_pegawai.xlsx, tidak
 * ikut git) — supaya nama unit/jabatan persis sama dengan sumbernya, bukan
 * hasil tebakan terpisah yang berisiko tidak cocok (mis. seed awal sempat
 * menyebut "Informatika" yang ternyata tidak ada di FMIPA UNS).
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $uns = Organisasi::firstOrCreate(
            ['kode' => 'UNS'],
            [
                'nama' => 'Universitas Sebelas Maret',
                'jenis' => JenisOrganisasi::Universitas,
                'alamat' => 'Jl. Ir. Sutami No.36, Kentingan, Surakarta',
                'email' => 'humas@uns.ac.id',
                'is_active' => true,
            ]
        );

        UnitKerja::firstOrCreate(
            ['kode' => 'FMIPA'],
            [
                'organisasi_id' => $uns->id,
                'parent_id' => null,
                'nama' => 'Fakultas MIPA',
                'jenis_unit' => JenisUnitKerja::Fakultas,
                'is_active' => true,
            ]
        );
    }
}
