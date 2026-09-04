<?php

namespace App\Models;

use Database\Factories\RiwayatPendidikanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Riwayat jenjang pendidikan pegawai dari waktu ke waktu (S1 -> S2 -> S3,
 * dst). `pegawai.pendidikan_terakhir_id` tetap menyimpan nilai TERKINI utk
 * query cepat; tabel ini menyimpan histori lengkapnya (pola BKN/SISTER).
 */
#[Table('riwayat_pendidikan')]
#[Fillable(['pegawai_id', 'pendidikan_id', 'nama_institusi', 'program_studi', 'tahun_lulus', 'no_ijazah'])]
class RiwayatPendidikan extends Model
{
    /** @use HasFactory<RiwayatPendidikanFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['tahun_lulus' => 'integer'];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function pendidikan(): BelongsTo
    {
        return $this->belongsTo(Pendidikan::class);
    }
}
