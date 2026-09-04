<?php

namespace App\Models;

use App\Enums\JenisOrganisasi;
use Database\Factories\OrganisasiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('organisasi')]
#[Fillable(['parent_id', 'nama', 'kode', 'jenis', 'alamat', 'telepon', 'email', 'is_active'])]
class Organisasi extends Model
{
    /** @use HasFactory<OrganisasiFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => JenisOrganisasi::class,
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

    public function unitKerja(): HasMany
    {
        return $this->hasMany(UnitKerja::class);
    }
}
