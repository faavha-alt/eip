<?php

namespace App\Enums;

/** Dasar hitung tunjangan keluarga (KP4): pasangan 10%, anak 2%/anak maks 2. */
enum HubunganKeluarga: string
{
    case Pasangan = 'pasangan';
    case Anak = 'anak';
    case OrangTua = 'orang_tua';
    case Lainnya = 'lainnya';
}
