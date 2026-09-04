<?php

namespace App\Models;

use App\Enums\JenisUnitKerja;
use Database\Factories\UnitKerjaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('unit_kerja')]
#[Fillable(['parent_id', 'organisasi_id', 'nama', 'kode', 'jenis_unit', 'kepala_id', 'is_active'])]
class UnitKerja extends Model
{
    /** @use HasFactory<UnitKerjaFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis_unit' => JenisUnitKerja::class,
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function organisasi(): BelongsTo
    {
        return $this->belongsTo(Organisasi::class);
    }

    public function kepala(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'kepala_id');
    }

    public function penempatan(): HasMany
    {
        return $this->hasMany(Penempatan::class);
    }
}
