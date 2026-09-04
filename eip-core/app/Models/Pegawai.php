<?php

namespace App\Models;

use App\Enums\JenisKelamin;
use App\Enums\JenisPegawai;
use App\Enums\PendidikanTerakhir;
use App\Enums\StatusKepegawaian;
use Database\Factories\PegawaiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('pegawai')]
#[Fillable([
    'id_sumber', 'nip', 'nik', 'npwp', 'id_simpeg', 'no_seri_kepeg',
    'nama_lengkap', 'gelar_depan', 'gelar_belakang',
    'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'email', 'no_hp',
    'status_kepegawaian', 'jenis_pegawai', 'pendidikan_terakhir',
    'golongan_ruang', 'tmt_golongan',
    'foto', 'tanggal_masuk', 'tanggal_keluar', 'is_active',
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
            'status_kepegawaian' => StatusKepegawaian::class,
            'jenis_pegawai' => JenisPegawai::class,
            'pendidikan_terakhir' => PendidikanTerakhir::class,
            'tanggal_lahir' => 'date',
            'tanggal_masuk' => 'date',
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
}
