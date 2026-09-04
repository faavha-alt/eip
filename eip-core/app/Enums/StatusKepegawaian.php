<?php

namespace App\Enums;

enum StatusKepegawaian: string
{
    case Pns = 'pns';
    case Honor = 'honor';
    case Kontrak = 'kontrak';
    case DosenTt = 'dosen_tt';
    case DosenYayasan = 'dosen_yayasan';
}
