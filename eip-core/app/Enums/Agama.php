<?php

namespace App\Enums;

/** 6 agama resmi yang diakui negara — set tetap, tidak perlu tabel master. */
enum Agama: string
{
    case Islam = 'islam';
    case Kristen = 'kristen';
    case Katolik = 'katolik';
    case Hindu = 'hindu';
    case Buddha = 'buddha';
    case Konghucu = 'konghucu';
}
