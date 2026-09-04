<?php

namespace App\Enums;

enum StatusPerkawinan: string
{
    case BelumKawin = 'belum_kawin';
    case Kawin = 'kawin';
    case CeraiHidup = 'cerai_hidup';
    case CeraiMati = 'cerai_mati';
}
