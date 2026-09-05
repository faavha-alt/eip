<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\Penempatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PenempatanController extends Controller
{
    public function store(Request $request, Pegawai $pegawai): RedirectResponse
    {
        $data = $request->validate([
            'unit_kerja_id' => ['required', 'integer', 'exists:unit_kerja,id'],
            'jabatan_id' => ['required', 'integer', 'exists:jabatan,id'],
            'tgl_mulai' => ['required', 'date'],
            'is_posisi_utama' => ['sometimes', 'boolean'],
        ]);
        $isUtama = $request->boolean('is_posisi_utama');

        if ($isUtama) {
            // Tutup posisi utama lama dulu — cegah 2 posisi utama aktif sekaligus.
            $pegawai->penempatan()
                ->where('is_posisi_utama', true)
                ->whereNull('tgl_selesai')
                ->update(['tgl_selesai' => now(), 'is_posisi_utama' => false, 'status' => 'nonaktif']);
        }

        Penempatan::create([
            'pegawai_id' => $pegawai->id,
            'unit_kerja_id' => $data['unit_kerja_id'],
            'jabatan_id' => $data['jabatan_id'],
            'tgl_mulai' => $data['tgl_mulai'],
            'is_posisi_utama' => $isUtama,
            'status' => 'aktif',
        ]);

        return back()->with('status', 'Penempatan ditambahkan.');
    }

    public function update(Request $request, Penempatan $penempatan): RedirectResponse
    {
        $data = $request->validate([
            'tgl_selesai' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(['aktif', 'nonaktif'])],
        ]);

        $penempatan->update($data);

        return back()->with('status', 'Penempatan diperbarui.');
    }

    public function destroy(Penempatan $penempatan): RedirectResponse
    {
        $penempatan->delete();

        return back()->with('status', 'Penempatan dihapus.');
    }
}
