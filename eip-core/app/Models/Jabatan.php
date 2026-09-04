<?php

namespace App\Models;

use App\Enums\JenisJabatan;
use Database\Factories\JabatanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('jabatan')]
#[Fillable(['nama', 'kode', 'jenis', 'level', 'eselon', 'deskripsi', 'is_active'])]
class Jabatan extends Model
{
    /** @use HasFactory<JabatanFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => JenisJabatan::class,
            'level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function penempatan(): HasMany
    {
        return $this->hasMany(Penempatan::class);
    }
}
