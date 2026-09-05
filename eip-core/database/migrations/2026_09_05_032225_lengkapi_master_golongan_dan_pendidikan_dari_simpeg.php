<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rekap SIMPEG (docs/rekap_pegawai.xlsx) memuat riwayat golongan & pendidikan
 * yang mencakup jenjang di luar seed awal `buat_master_status_pendidikan_golongan`
 * (yang cuma mulai dari II/c & SMA/SLTA, krn saat itu cuma dilihat dari
 * kolom "terkini" 190 pegawai aktif). Riwayat lengkap menunjukkan sebagian
 * pernah di I/a-I/d, II/a-II/b, atau punya ijazah SD/SMP/D4. Migrasi ini
 * cuma menambah baris master yang belum ada + menata ulang urutan
 * tingkat/jenjang (nilai lama tetap konsisten relatif, hanya referensi
 * numeriknya bergeser krn ada jenjang baru di bawah/antara).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('golongan_ruang')->insert(collect([
            'I/a' => null, 'I/b' => null, 'I/c' => null, 'I/d' => null,
            'II/a' => null, 'II/b' => null,
        ])->map(fn ($nama, $kode) => [
            'kode' => $kode, 'nama' => $nama, 'tingkat' => 0,
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ])->values()->all());

        $urutanGolongan = [
            'I/a' => 1, 'I/b' => 2, 'I/c' => 3, 'I/d' => 4,
            'II/a' => 5, 'II/b' => 6, 'II/c' => 7, 'II/d' => 8,
            'III/a' => 9, 'III/b' => 10, 'III/c' => 11, 'III/d' => 12,
            'IV/a' => 13, 'IV/b' => 14, 'IV/c' => 15, 'IV/d' => 16, 'IV/e' => 17,
        ];
        foreach ($urutanGolongan as $kode => $tingkat) {
            DB::table('golongan_ruang')->where('kode', $kode)->update(['tingkat' => $tingkat]);
        }

        DB::table('pendidikan')->insert(collect([
            'sd' => 'SD',
            'smp' => 'SMP/SLTP',
            'd4' => 'D4',
        ])->map(fn ($nama, $kode) => [
            'kode' => $kode, 'nama' => $nama, 'jenjang' => 0,
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ])->values()->all());

        // D4 setara S1 (jenjang sama) sesuai penyetaraan BKN.
        $urutanPendidikan = [
            'sd' => 1, 'smp' => 2, 'sma_slta' => 3, 'd3' => 4,
            'd4' => 5, 's1' => 5, 'profesi' => 6, 's2' => 7, 's3' => 8,
        ];
        foreach ($urutanPendidikan as $kode => $jenjang) {
            DB::table('pendidikan')->where('kode', $kode)->update(['jenjang' => $jenjang]);
        }
    }

    public function down(): void
    {
        DB::table('golongan_ruang')->whereIn('kode', ['I/a', 'I/b', 'I/c', 'I/d', 'II/a', 'II/b'])->delete();
        DB::table('pendidikan')->whereIn('kode', ['sd', 'smp', 'd4'])->delete();

        foreach (['II/c' => 1, 'II/d' => 2, 'III/a' => 3, 'III/b' => 4, 'III/c' => 5, 'III/d' => 6, 'IV/a' => 7, 'IV/b' => 8, 'IV/c' => 9, 'IV/d' => 10, 'IV/e' => 11] as $kode => $tingkat) {
            DB::table('golongan_ruang')->where('kode', $kode)->update(['tingkat' => $tingkat]);
        }
        foreach (['sma_slta' => 1, 'd3' => 2, 's1' => 3, 'profesi' => 4, 's2' => 5, 's3' => 6] as $kode => $jenjang) {
            DB::table('pendidikan')->where('kode', $kode)->update(['jenjang' => $jenjang]);
        }
    }
};
