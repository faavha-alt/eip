<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PegawaiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_sumber' => $this->id_sumber,
            'nip' => $this->nip,
            'nik' => $this->nik,
            'npwp' => $this->npwp,
            'nuptk' => $this->nuptk,
            'id_simpeg' => $this->id_simpeg,
            'no_seri_kepeg' => $this->no_seri_kepeg,
            'nama_lengkap' => $this->nama_lengkap,
            'gelar_depan' => $this->gelar_depan,
            'gelar_belakang' => $this->gelar_belakang,
            'jenis_kelamin' => $this->jenis_kelamin?->value,
            'agama' => $this->agama?->value,
            'status_perkawinan' => $this->status_perkawinan?->value,
            'tempat_lahir' => $this->tempat_lahir,
            'tanggal_lahir' => $this->tanggal_lahir?->toDateString(),
            'alamat_domisili' => $this->alamat_domisili,
            'email' => $this->email,
            'no_hp' => $this->no_hp,
            'status_kepegawaian' => $this->whenLoaded('statusKepegawaian', fn () => [
                'kode' => $this->statusKepegawaian?->kode,
                'nama' => $this->statusKepegawaian?->nama,
            ]),
            'jenis_pegawai' => $this->jenis_pegawai?->value,
            'pendidikan_terakhir' => $this->whenLoaded('pendidikanTerakhir', fn () => $this->pendidikanTerakhir ? [
                'kode' => $this->pendidikanTerakhir->kode,
                'nama' => $this->pendidikanTerakhir->nama,
            ] : null),
            'golongan_ruang' => $this->whenLoaded('golonganRuang', fn () => $this->golonganRuang?->kode),
            'tmt_golongan' => $this->tmt_golongan?->toDateString(),
            'no_bpjs_kesehatan' => $this->no_bpjs_kesehatan,
            'no_bpjs_ketenagakerjaan' => $this->no_bpjs_ketenagakerjaan,
            'no_taspen' => $this->no_taspen,
            'foto' => $this->foto,
            'tanggal_masuk' => $this->tanggal_masuk?->toDateString(),
            'tmt_cpns' => $this->tmt_cpns?->toDateString(),
            'tmt_pns' => $this->tmt_pns?->toDateString(),
            'tanggal_keluar' => $this->tanggal_keluar?->toDateString(),
            'is_active' => $this->is_active,
            'penempatan_utama' => $this->whenLoaded('penempatan', function () {
                $utama = $this->penempatan->firstWhere('is_posisi_utama', true);

                return $utama ? new PenempatanResource($utama) : null;
            }),
            // Semua jabatan yg dipegang (dosen + rangkap struktural, dst) —
            // penempatan_utama saja tidak cukup krn jabatan struktural
            // (kaprodi/kalab) biasanya BUKAN posisi utama.
            'jabatan' => $this->whenLoaded('penempatan', fn () => $this->penempatan
                ->filter(fn ($p) => $p->jabatan !== null)
                ->map(fn ($p) => [
                    'id' => $p->jabatan->id,
                    'kode' => $p->jabatan->kode,
                    'nama' => $p->jabatan->nama,
                    'jenis' => $p->jabatan->jenis->value,
                    'unit_kerja_id' => $p->unit_kerja_id,
                    'is_posisi_utama' => (bool) $p->is_posisi_utama,
                ])->values()),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
