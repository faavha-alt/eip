<?php

namespace App\Models;

use Database\Factories\GolonganRuangFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Master golongan/ruang PNS (II/c..IV/e). `tingkat` = urutan pangkat utk
 * sortir. Nilai referensi diseed di migrasi
 * `buat_master_status_pendidikan_golongan`. Nilai sumber "X" (placeholder
 * non-PNS) sengaja tidak diseed di sini.
 */
#[Table('golongan_ruang')]
#[Fillable(['kode', 'nama', 'tingkat', 'is_active'])]
class GolonganRuang extends Model
{
    /** @use HasFactory<GolonganRuangFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'tingkat' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(Pegawai::class);
    }
}
