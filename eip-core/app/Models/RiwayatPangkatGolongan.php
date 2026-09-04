<?php

namespace App\Models;

use Database\Factories\RiwayatPangkatGolonganFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Riwayat kenaikan pangkat/golongan pegawai (tiap kenaikan = baris baru +
 * nomor SK, pola BKN). `pegawai.golongan_ruang_id` + `tmt_golongan` tetap
 * menyimpan nilai TERKINI utk query cepat.
 */
#[Table('riwayat_pangkat_golongan')]
#[Fillable(['pegawai_id', 'golongan_ruang_id', 'tmt', 'no_sk', 'tgl_sk'])]
class RiwayatPangkatGolongan extends Model
{
    /** @use HasFactory<RiwayatPangkatGolonganFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'tmt' => 'date',
            'tgl_sk' => 'date',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function golonganRuang(): BelongsTo
    {
        return $this->belongsTo(GolonganRuang::class);
    }
}
