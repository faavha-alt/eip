<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\RiwayatPangkatGolongan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RiwayatPangkatGolonganController extends Controller
{
    public function store(Request $request, Pegawai $pegawai): RedirectResponse
    {
        $data = $request->validate([
            'golongan_ruang_id' => ['required', 'integer', 'exists:golongan_ruang,id'],
            'tmt' => ['required', 'date'],
            'no_sk' => ['nullable', 'string', 'max:255'],
            'tgl_sk' => ['nullable', 'date'],
        ]);

        $pegawai->riwayatPangkatGolongan()->create($data);

        // Golongan/ruang & TMT "terkini" di pegawai ikut sinkron kalau TMT
        // baru >= yg tercatat — riwayat_pangkat_golongan simpan histori
        // lengkap, pegawai.golongan_ruang_id tetap nilai TERKINI (docs/04).
        if (! $pegawai->tmt_golongan || Carbon::parse($data['tmt'])->gte($pegawai->tmt_golongan)) {
            $pegawai->update([
                'golongan_ruang_id' => $data['golongan_ruang_id'],
                'tmt_golongan' => $data['tmt'],
            ]);
        }

        return back()->with('status', 'Riwayat pangkat/golongan ditambahkan.');
    }

    public function destroy(RiwayatPangkatGolongan $riwayatPangkatGolongan): RedirectResponse
    {
        $riwayatPangkatGolongan->delete();

        return back()->with('status', 'Riwayat pangkat/golongan dihapus.');
    }
}
