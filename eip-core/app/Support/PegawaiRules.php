<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Aturan validasi `pegawai` dipakai bersama oleh API (Http/Controllers/Api/V1)
 * dan modul Kepegawaian (Http/Controllers/Kepegawaian) — satu jalur tulis,
 * satu aturan, supaya tidak ada celah beda validasi antar pintu masuk.
 */
class PegawaiRules
{
    /**
     * @return array<string, mixed>
     */
    public static function make(?int $ignoreId = null, bool $sometimes = false): array
    {
        $req = $sometimes ? 'sometimes' : 'required';
        $nullable = $sometimes ? 'sometimes|nullable' : 'nullable';

        return [
            'id_sumber' => [$nullable, 'string', Rule::unique('pegawai')->ignore($ignoreId)],
            'nip' => [$nullable, 'string', Rule::unique('pegawai')->ignore($ignoreId)],
            'nik' => [$nullable, 'string', Rule::unique('pegawai')->ignore($ignoreId)],
            'npwp' => [$nullable, 'string'],
            'nuptk' => [$nullable, 'string', Rule::unique('pegawai')->ignore($ignoreId)],
            'id_simpeg' => [$nullable, 'string', Rule::unique('pegawai')->ignore($ignoreId)],
            'no_seri_kepeg' => [$nullable, 'string'],
            'nama_lengkap' => [$req, 'string', 'max:255'],
            'gelar_depan' => [$nullable, 'string'],
            'gelar_belakang' => [$nullable, 'string'],
            'jenis_kelamin' => [$nullable, Rule::in(['L', 'P'])],
            'agama' => [$nullable, Rule::in(['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu'])],
            'status_perkawinan' => [$nullable, Rule::in(['belum_kawin', 'kawin', 'cerai_hidup', 'cerai_mati'])],
            'tempat_lahir' => [$nullable, 'string'],
            'tanggal_lahir' => [$nullable, 'date'],
            'alamat_domisili' => [$nullable, 'string'],
            'email' => [$nullable, 'email', Rule::unique('pegawai')->ignore($ignoreId)],
            'no_hp' => [$nullable, 'string'],
            'status_kepegawaian_id' => [$nullable, 'integer', 'exists:status_kepegawaian,id'],
            'jenis_pegawai' => [$nullable, Rule::in(['tenaga_pendidik', 'tenaga_kependidikan'])],
            'pendidikan_terakhir_id' => [$nullable, 'integer', 'exists:pendidikan,id'],
            'golongan_ruang_id' => [$nullable, 'integer', 'exists:golongan_ruang,id'],
            'tmt_golongan' => [$nullable, 'date'],
            'no_bpjs_kesehatan' => [$nullable, 'string'],
            'no_bpjs_ketenagakerjaan' => [$nullable, 'string'],
            'no_taspen' => [$nullable, 'string'],
            'foto' => [$nullable, 'string'],
            'tanggal_masuk' => [$sometimes ? 'sometimes' : 'required', 'date'],
            'tmt_cpns' => [$nullable, 'date'],
            'tmt_pns' => [$nullable, 'date'],
            'tanggal_keluar' => [$nullable, 'date'],
            'is_active' => [$nullable, 'boolean'],
        ];
    }
}
