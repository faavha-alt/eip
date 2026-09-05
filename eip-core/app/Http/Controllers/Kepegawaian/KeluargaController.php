<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use App\Models\KeluargaPegawai;
use App\Models\Pegawai;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KeluargaController extends Controller
{
    public function store(Request $request, Pegawai $pegawai): RedirectResponse
    {
        $data = $request->validate([
            'hubungan' => ['required', Rule::in(['pasangan', 'anak', 'orang_tua', 'lainnya'])],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['nullable', Rule::in(['L', 'P'])],
            'tanggal_lahir' => ['nullable', 'date'],
            'pekerjaan' => ['nullable', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:50'],
        ]);
        $data['status_tanggungan'] = $request->boolean('status_tanggungan', true);

        $pegawai->keluarga()->create($data);

        return back()->with('status', 'Data keluarga ditambahkan.');
    }

    public function destroy(KeluargaPegawai $keluarga): RedirectResponse
    {
        $keluarga->delete();

        return back()->with('status', 'Data keluarga dihapus.');
    }
}
