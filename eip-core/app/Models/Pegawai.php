<?php

namespace App\Models;

use App\Enums\JenisKelamin;
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
    'nip', 'nik', 'id_simpeg', 'nama_lengkap', 'gelar_depan', 'gelar_belakang',
    'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'email', 'no_hp',
    'status_kepegawaian', 'foto', 'tanggal_masuk', 'tanggal_keluar', 'is_active',
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
            'tanggal_lahir' => 'date',
            'tanggal_masuk' => 'date',
            'tanggal_keluar' => 'date',
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
