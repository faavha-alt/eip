<?php

namespace App\Models;

use App\Enums\HubunganKeluarga;
use App\Enums\JenisKelamin;
use Database\Factories\KeluargaPegawaiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Data pasangan/anak/tanggungan pegawai — dasar hitung tunjangan keluarga
 * (KP4: 10% pasangan, 2%/anak maks 2) sekaligus bisa dipakai sbg kontak
 * darurat (`no_hp`).
 */
#[Table('keluarga_pegawai')]
#[Fillable(['pegawai_id', 'hubungan', 'nama', 'jenis_kelamin', 'tanggal_lahir', 'pekerjaan', 'status_tanggungan', 'no_hp', 'keterangan'])]
class KeluargaPegawai extends Model
{
    /** @use HasFactory<KeluargaPegawaiFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'hubungan' => HubunganKeluarga::class,
            'jenis_kelamin' => JenisKelamin::class,
            'tanggal_lahir' => 'date',
            'status_tanggungan' => 'boolean',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }
}
