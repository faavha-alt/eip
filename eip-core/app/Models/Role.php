<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Peran RBAC (mis. admin-gaji, approver-pengadaan, operator-aset) —
 * ditetapkan manual per akun oleh admin EIP (docs/03), bukan otomatis dari
 * jabatan/unit_kerja.
 */
#[Fillable(['kode', 'nama', 'deskripsi', 'is_active'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
