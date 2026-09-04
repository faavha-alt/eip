<?php

namespace App\Enums;

enum JenisDokumenPegawai: string
{
    case SkCpns = 'sk_cpns';
    case SkPns = 'sk_pns';
    case SkGolongan = 'sk_golongan';
    case SkJabatan = 'sk_jabatan';
    case Ijazah = 'ijazah';
    case Ktp = 'ktp';
    case KartuKeluarga = 'kartu_keluarga';
    case Npwp = 'npwp';
    case Lainnya = 'lainnya';
}
