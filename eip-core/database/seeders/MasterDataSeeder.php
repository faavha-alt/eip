<?php

namespace Database\Seeders;

use App\Enums\JenisJabatan;
use App\Enums\JenisOrganisasi;
use App\Enums\JenisUnitKerja;
use App\Models\Jabatan;
use App\Models\Organisasi;
use App\Models\Pegawai;
use App\Models\Penempatan;
use App\Models\UnitKerja;
use Illuminate\Database\Seeder;

/**
 * Seed contoh master data: Universitas Sebelas Maret > Fakultas MIPA > prodi,
 * jabatan dasar, dan beberapa pegawai + penempatan. Dipakai utk dev/demo
 * awal EIP Core (docs/04 §7 blocker #1: ruang lingkup = Fakultas MIPA UNS).
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $uns = Organisasi::create([
            'nama' => 'Universitas Sebelas Maret',
            'kode' => 'UNS',
            'jenis' => JenisOrganisasi::Universitas,
            'alamat' => 'Jl. Ir. Sutami No.36, Kentingan, Surakarta',
            'email' => 'humas@uns.ac.id',
            'is_active' => true,
        ]);

        $fmipa = UnitKerja::create([
            'organisasi_id' => $uns->id,
            'parent_id' => null,
            'nama' => 'Fakultas MIPA',
            'kode' => 'FMIPA',
            'jenis_unit' => JenisUnitKerja::Fakultas,
            'is_active' => true,
        ]);

        $prodi = collect([
            'Informatika' => 'FMIPA-INF',
            'Matematika' => 'FMIPA-MAT',
            'Fisika' => 'FMIPA-FIS',
            'Kimia' => 'FMIPA-KIM',
            'Biologi' => 'FMIPA-BIO',
        ])->map(fn (string $kode, string $nama) => UnitKerja::create([
            'organisasi_id' => $uns->id,
            'parent_id' => $fmipa->id,
            'nama' => "Program Studi {$nama}",
            'kode' => $kode,
            'jenis_unit' => JenisUnitKerja::Prodi,
            'is_active' => true,
        ]));

        $taFmipa = UnitKerja::create([
            'organisasi_id' => $uns->id,
            'parent_id' => $fmipa->id,
            'nama' => 'Bagian Tata Usaha FMIPA',
            'kode' => 'FMIPA-TU',
            'jenis_unit' => JenisUnitKerja::Bagian,
            'is_active' => true,
        ]);

        $jabatanDekan = Jabatan::create([
            'nama' => 'Dekan',
            'kode' => 'JBT-DEKAN',
            'jenis' => JenisJabatan::Struktural,
            'level' => 1,
            'deskripsi' => 'Pimpinan fakultas.',
            'is_active' => true,
        ]);

        $jabatanWakilDekan = Jabatan::create([
            'nama' => 'Wakil Dekan',
            'kode' => 'JBT-WADEK',
            'jenis' => JenisJabatan::Struktural,
            'level' => 2,
            'deskripsi' => 'Membantu dekan sesuai bidang.',
            'is_active' => true,
        ]);

        $jabatanKaprodi = Jabatan::create([
            'nama' => 'Ketua Program Studi',
            'kode' => 'JBT-KAPRODI',
            'jenis' => JenisJabatan::Struktural,
            'level' => 3,
            'deskripsi' => 'Pimpinan program studi.',
            'is_active' => true,
        ]);

        $jabatanDosen = Jabatan::create([
            'nama' => 'Dosen',
            'kode' => 'JBT-DOSEN',
            'jenis' => JenisJabatan::Fungsional,
            'level' => 5,
            'deskripsi' => 'Tenaga pendidik.',
            'is_active' => true,
        ]);

        $jabatanTendik = Jabatan::create([
            'nama' => 'Tenaga Kependidikan',
            'kode' => 'JBT-TENDIK',
            'jenis' => JenisJabatan::FungsionalUmum,
            'level' => 6,
            'deskripsi' => 'Staf administrasi/penunjang.',
            'is_active' => true,
        ]);

        // Dekan FMIPA
        $dekan = Pegawai::factory()->create();
        Penempatan::create([
            'pegawai_id' => $dekan->id,
            'unit_kerja_id' => $fmipa->id,
            'jabatan_id' => $jabatanDekan->id,
            'tgl_mulai' => now()->subYears(2),
            'is_posisi_utama' => true,
        ]);
        $fmipa->update(['kepala_id' => $dekan->id]);

        // Wakil Dekan
        $wadek = Pegawai::factory()->create();
        Penempatan::create([
            'pegawai_id' => $wadek->id,
            'unit_kerja_id' => $fmipa->id,
            'jabatan_id' => $jabatanWakilDekan->id,
            'tgl_mulai' => now()->subYears(2),
            'is_posisi_utama' => true,
        ]);

        // Kaprodi + beberapa dosen per prodi
        $prodi->each(function (UnitKerja $unit) use ($jabatanKaprodi, $jabatanDosen) {
            $kaprodi = Pegawai::factory()->create();
            Penempatan::create([
                'pegawai_id' => $kaprodi->id,
                'unit_kerja_id' => $unit->id,
                'jabatan_id' => $jabatanKaprodi->id,
                'tgl_mulai' => now()->subYear(),
                'is_posisi_utama' => true,
            ]);
            $unit->update(['kepala_id' => $kaprodi->id]);

            Pegawai::factory(3)->create()->each(function (Pegawai $dosen) use ($unit, $jabatanDosen) {
                Penempatan::create([
                    'pegawai_id' => $dosen->id,
                    'unit_kerja_id' => $unit->id,
                    'jabatan_id' => $jabatanDosen->id,
                    'tgl_mulai' => now()->subYears(3),
                    'is_posisi_utama' => true,
                ]);
            });
        });

        // Tenaga kependidikan di Tata Usaha
        Pegawai::factory(2)->create()->each(function (Pegawai $tendik) use ($taFmipa, $jabatanTendik) {
            Penempatan::create([
                'pegawai_id' => $tendik->id,
                'unit_kerja_id' => $taFmipa->id,
                'jabatan_id' => $jabatanTendik->id,
                'tgl_mulai' => now()->subYears(4),
                'is_posisi_utama' => true,
            ]);
        });
    }
}
