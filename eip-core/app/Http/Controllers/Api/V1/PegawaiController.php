<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PegawaiResource;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Satu jalur tulis data pegawai (docs/04 §6): store/update di sini HANYA
 * bisa dipanggil token dgn ability "pegawai:write" (app kepegawaian).
 * Semua konsumen lain baca saja lewat index/show.
 */
class PegawaiController extends Controller
{
    private const RELATIONS = ['statusKepegawaian', 'pendidikanTerakhir', 'golonganRuang', 'penempatan'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Pegawai::query()->with(self::RELATIONS)->orderBy('nama_lengkap');

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('unit_kerja_id')) {
            $query->whereHas('penempatan', fn ($q) => $q
                ->where('unit_kerja_id', $request->input('unit_kerja_id'))
                ->where('is_posisi_utama', true));
        }

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>=', $request->date('updated_since'));
        }

        return PegawaiResource::collection($query->paginate($request->integer('per_page', 50)));
    }

    public function show(Pegawai $pegawai): PegawaiResource
    {
        return new PegawaiResource($pegawai->load(self::RELATIONS));
    }

    public function store(Request $request): PegawaiResource
    {
        $data = $request->validate($this->rules());

        $pegawai = Pegawai::create($data)->refresh(); // kolom default DB (mis. is_active) blm ke-load tanpa ini

        return new PegawaiResource($pegawai->load(self::RELATIONS));
    }

    public function update(Request $request, Pegawai $pegawai): PegawaiResource
    {
        $data = $request->validate($this->rules($pegawai->id, sometimes: true));

        $pegawai->update($data);

        return new PegawaiResource($pegawai->load(self::RELATIONS));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?int $ignoreId = null, bool $sometimes = false): array
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
