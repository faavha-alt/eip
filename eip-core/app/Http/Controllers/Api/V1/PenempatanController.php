<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PenempatanResource;
use App\Models\Penempatan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Satu jalur tulis penempatan (docs/04 §6) — sama spt PegawaiController,
 * hanya token ability "pegawai:write" (app kepegawaian) yg boleh ke sini.
 */
class PenempatanController extends Controller
{
    public function store(Request $request): PenempatanResource
    {
        $data = $request->validate($this->rules());

        $penempatan = Penempatan::create($data)->refresh(); // kolom default DB (mis. status) blm ke-load tanpa ini

        return new PenempatanResource($penempatan->load(['unitKerja', 'jabatan']));
    }

    public function update(Request $request, Penempatan $penempatan): PenempatanResource
    {
        $data = $request->validate($this->rules(sometimes: true));

        $penempatan->update($data);

        return new PenempatanResource($penempatan->load(['unitKerja', 'jabatan']));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $sometimes = false): array
    {
        $req = $sometimes ? 'sometimes' : 'required';

        return [
            'pegawai_id' => [$req, 'integer', 'exists:pegawai,id'],
            'unit_kerja_id' => [$req, 'integer', 'exists:unit_kerja,id'],
            'jabatan_id' => [$req, 'integer', 'exists:jabatan,id'],
            'tgl_mulai' => [$req, 'date'],
            'tgl_selesai' => ['nullable', 'date'],
            'is_posisi_utama' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['aktif', 'nonaktif'])],
        ];
    }
}
