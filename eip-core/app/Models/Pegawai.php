<?php

namespace App\Models;

use App\Enums\Agama;
use App\Enums\JenisKelamin;
use App\Enums\JenisPegawai;
use App\Enums\StatusPerkawinan;
use Database\Factories\PegawaiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('pegawai')]
#[Fillable([
    'id_sumber', 'nip', 'nik', 'npwp', 'nuptk', 'id_simpeg', 'no_seri_kepeg',
    'nama_lengkap', 'gelar_depan', 'gelar_belakang',
    'jenis_kelamin', 'agama', 'status_perkawinan',
    'tempat_lahir', 'tanggal_lahir', 'alamat_domisili', 'email', 'no_hp',
    'status_kepegawaian_id', 'jenis_pegawai', 'pendidikan_terakhir_id',
    'golongan_ruang_id', 'tmt_golongan',
    'no_bpjs_kesehatan', 'no_bpjs_ketenagakerjaan', 'no_taspen',
    'foto', 'tanggal_masuk', 'tmt_cpns', 'tmt_pns', 'tanggal_keluar', 'is_active',
])]
class Pegawai extends Model
{
    /** @use HasFactory<PegawaiFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis_kelamin' => JenisKelamin::class,
            'agama' => Agama::class,
            'status_perkawinan' => StatusPerkawinan::class,
            'jenis_pegawai' => JenisPegawai::class,
            'tanggal_lahir' => 'date',
            'tanggal_masuk' => 'date',
            'tmt_cpns' => 'date',
            'tmt_pns' => 'date',
            'tanggal_keluar' => 'date',
            'tmt_golongan' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Riwayat penempatan pegawai (unit_kerja + jabatan dari waktu ke waktu).
     */
    public function penempatan(): HasMany
    {
        return $this->hasMany(Penempatan::class);
    }

    /**
     * Unit kerja yang dipimpin pegawai ini (relasi unit_kerja.kepala_id).
     */
    public function unitKerjaDipimpin(): HasMany
    {
        return $this->hasMany(UnitKerja::class, 'kepala_id');
    }

    public function statusKepegawaian(): BelongsTo
    {
        return $this->belongsTo(StatusKepegawaian::class);
    }

    public function pendidikanTerakhir(): BelongsTo
    {
        return $this->belongsTo(Pendidikan::class, 'pendidikan_terakhir_id');
    }

    public function golonganRuang(): BelongsTo
    {
        return $this->belongsTo(GolonganRuang::class);
    }

    /** Histori jenjang pendidikan (S1 -> S2 -> S3, dst). */
    public function riwayatPendidikan(): HasMany
    {
        return $this->hasMany(RiwayatPendidikan::class);
    }

    /** Histori kenaikan pangkat/golongan. */
    public function riwayatPangkatGolongan(): HasMany
    {
        return $this->hasMany(RiwayatPangkatGolongan::class);
    }

    /** Pasangan/anak/tanggungan — dasar tunjangan keluarga (KP4). */
    public function keluarga(): HasMany
    {
        return $this->hasMany(KeluargaPegawai::class);
    }

    /** Histori jabatan struktural & fungsional (arsip SIMPEG). */
    public function riwayatJabatan(): HasMany
    {
        return $this->hasMany(RiwayatJabatan::class);
    }

    /** Arsip dokumen resmi (SK, ijazah, dst). */
    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenPegawai::class);
    }
}
