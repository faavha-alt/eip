<?php

namespace App\Enums;

/**
 * Dimensi terpisah dari status_kepegawaian: dosen vs tenaga kependidikan.
 */
enum JenisPegawai: string
{
    case TenagaPendidik = 'tenaga_pendidik';
    case TenagaKependidikan = 'tenaga_kependidikan';
}
