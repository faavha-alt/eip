<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\Pendidikan;
use App\Models\RiwayatPendidikan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RiwayatPendidikanController extends Controller
{
    public function store(Request $request, Pegawai $pegawai): RedirectResponse
    {
        $data = $request->validate([
            'pendidikan_id' => ['required', 'integer', 'exists:pendidikan,id'],
            'nama_institusi' => ['nullable', 'string', 'max:255'],
            'program_studi' => ['nullable', 'string', 'max:255'],
            'tahun_lulus' => ['nullable', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
            'no_ijazah' => ['nullable', 'string', 'max:255'],
        ]);

        $pegawai->riwayatPendidikan()->create($data);

        // Jenjang baru jadi "pendidikan terkini" kalau setara/lebih tinggi
        // drpd yg tercatat — pegawai.pendidikan_terakhir_id tetap nilai
        // TERKINI, riwayat_pendidikan simpan histori lengkap (docs/04).
        $baru = Pendidikan::find($data['pendidikan_id']);
        $sekarang = $pegawai->pendidikanTerakhir;
        if ($baru && (! $sekarang || $baru->jenjang >= $sekarang->jenjang)) {
            $pegawai->update(['pendidikan_terakhir_id' => $baru->id]);
        }

        return back()->with('status', 'Riwayat pendidikan ditambahkan.');
    }

    public function destroy(RiwayatPendidikan $riwayatPendidikan): RedirectResponse
    {
        $riwayatPendidikan->delete();

        return back()->with('status', 'Riwayat pendidikan dihapus.');
    }
}
