<?php

namespace App\Models;

use App\Enums\JenisDokumenPegawai;
use Database\Factories\DokumenPegawaiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Arsip digital dokumen resmi pegawai (SK CPNS/PNS/Golongan/Jabatan, ijazah,
 * dst) — BKN mensyaratkan SK terverifikasi diarsipkan digital. `file_path`
 * cuma kolom referensi; mekanisme upload/storage menyusul (belum dibangun).
 */
#[Table('dokumen_pegawai')]
#[Fillable(['pegawai_id', 'jenis', 'nomor_dokumen', 'tanggal_dokumen', 'file_path', 'keterangan'])]
class DokumenPegawai extends Model
{
    /** @use HasFactory<DokumenPegawaiFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'jenis' => JenisDokumenPegawai::class,
            'tanggal_dokumen' => 'date',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }
}
