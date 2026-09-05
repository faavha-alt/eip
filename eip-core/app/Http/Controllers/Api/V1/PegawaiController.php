<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PegawaiResource;
use App\Models\Pegawai;
use App\Support\PegawaiRules;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Satu jalur tulis data pegawai (docs/04 §6): store/update di sini HANYA
 * bisa dipanggil token dgn ability "pegawai:write" (app konsumen eksternal —
 * modul Kepegawaian sendiri, di dalam EIP Core, menulis langsung via
 * Eloquent, lihat Http/Controllers/Kepegawaian).
 */
class PegawaiController extends Controller
{
    private const RELATIONS = [
        'statusKepegawaian', 'pendidikanTerakhir', 'golonganRuang',
        'penempatan.unitKerja', 'penempatan.jabatan',
    ];

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
        $data = $request->validate(PegawaiRules::make());

        $pegawai = Pegawai::create($data)->refresh(); // kolom default DB (mis. is_active) blm ke-load tanpa ini

        return new PegawaiResource($pegawai->load(self::RELATIONS));
    }

    public function update(Request $request, Pegawai $pegawai): PegawaiResource
    {
        $data = $request->validate(PegawaiRules::make($pegawai->id, sometimes: true));

        $pegawai->update($data);

        return new PegawaiResource($pegawai->load(self::RELATIONS));
    }
}
