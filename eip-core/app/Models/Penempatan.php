<?php

namespace App\Models;

use App\Enums\StatusPenempatan;
use Database\Factories\PenempatanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('penempatan')]
#[Fillable(['pegawai_id', 'unit_kerja_id', 'jabatan_id', 'tgl_mulai', 'tgl_selesai', 'is_posisi_utama', 'status'])]
class Penempatan extends Model
{
    /** @use HasFactory<PenempatanFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tgl_mulai' => 'date',
            'tgl_selesai' => 'date',
            'is_posisi_utama' => 'boolean',
            'status' => StatusPenempatan::class,
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }
}
