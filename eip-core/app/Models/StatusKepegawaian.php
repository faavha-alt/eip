<?php

namespace App\Models;

use Database\Factories\StatusKepegawaianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Master status kepegawaian (PNS, Non PNS, Kontrak Profesional, Purna
 * Tugas). Nilai referensi diseed di migrasi
 * `buat_master_status_pendidikan_golongan`.
 */
#[Table('status_kepegawaian')]
#[Fillable(['kode', 'nama', 'is_active'])]
class StatusKepegawaian extends Model
{
    /** @use HasFactory<StatusKepegawaianFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(Pegawai::class);
    }
}
