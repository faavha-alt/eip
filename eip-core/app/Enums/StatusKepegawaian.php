<?php

namespace App\Enums;

/**
 * Status kepegawaian sesuai kategori nyata FMIPA UNS (bukan asumsi awal).
 */
enum StatusKepegawaian: string
{
    case Pns = 'pns';
    case NonPns = 'non_pns';
    case KontrakProfesional = 'kontrak_profesional';
    case PurnaTugas = 'purna_tugas';
}
