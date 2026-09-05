<?php

namespace App\Models;

use App\Enums\JenisRiwayatJabatan;
use Database\Factories\RiwayatJabatanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Riwayat jabatan struktural (Kepala Bagian, Koordinator, dst) & fungsional
 * (Lektor, Analis Anggaran, dst) pegawai dari waktu ke waktu, disalin dari
 * SIMPEG. Murni histori/arsip — TIDAK disinkronkan ke `pegawai`/`penempatan`
 * (posisi struktural aktif tetap dikelola via tabel `penempatan`).
 */
#[Table('riwayat_jabatan')]
#[Fillable(['pegawai_id', 'jenis', 'jabatan_nama', 'jabatan_detail', 'no_sk', 'tmt_awal', 'tmt_akhir', 'status'])]
class RiwayatJabatan extends Model
{
    /** @use HasFactory<RiwayatJabatanFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'jenis' => JenisRiwayatJabatan::class,
            'tmt_awal' => 'date',
            'tmt_akhir' => 'date',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }
}
