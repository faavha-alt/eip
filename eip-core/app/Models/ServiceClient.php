<?php

namespace App\Models;

use Database\Factories\ServiceClientFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

/**
 * Aplikasi konsumen EIP Core API (kepegawaian, gaji, aset, logistik,
 * wa-blast, dst) — otentikasi service-to-service via token Sanctum, TERPISAH
 * dari login manusia (Google OIDC di `User`). Satu ServiceClient = satu app.
 * Implement Authenticatable (bukan login manusia) krn guard Sanctum
 * mensyaratkan kontrak ini utk model apa pun yg dipakai `HasApiTokens`.
 */
#[Fillable(['kode', 'nama', 'is_active'])]
class ServiceClient extends Model implements AuthenticatableContract
{
    /** @use HasFactory<ServiceClientFactory> */
    use Authenticatable, HasApiTokens, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
