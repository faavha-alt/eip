<?php

namespace App\Models;

use Database\Factories\PendidikanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Master jenjang pendidikan (SMA/SLTA..D3..S1..Profesi..S2..S3). `jenjang`
 * = urutan tingkat utk sortir. Nilai referensi diseed di migrasi
 * `buat_master_status_pendidikan_golongan`.
 */
#[Table('pendidikan')]
#[Fillable(['kode', 'nama', 'jenjang', 'is_active'])]
class Pendidikan extends Model
{
    /** @use HasFactory<PendidikanFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'jenjang' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** Pegawai dgn pendidikan terakhir ini. */
    public function pegawai(): HasMany
    {
        return $this->hasMany(Pegawai::class, 'pendidikan_terakhir_id');
    }
}
