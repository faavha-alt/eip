<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use App\Models\DokumenPegawai;
use App\Models\Pegawai;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Arsip dokumen pegawai (SK, ijazah, KTP, dst) — file disimpan di disk
 * "local" (storage/app/private, TIDAK ter-symlink ke public/) krn isinya
 * PII. Diunduh lewat route ber-otentikasi, bukan URL publik langsung.
 */
class DokumenController extends Controller
{
    private const JENIS = ['sk_cpns', 'sk_pns', 'sk_golongan', 'sk_jabatan', 'ijazah', 'ktp', 'kartu_keluarga', 'npwp', 'lainnya'];

    public function store(Request $request, Pegawai $pegawai): RedirectResponse
    {
        $data = $request->validate([
            'jenis' => ['required', Rule::in(self::JENIS)],
            'nomor_dokumen' => ['nullable', 'string', 'max:255'],
            'tanggal_dokumen' => ['nullable', 'date'],
            'keterangan' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('dokumen-pegawai/'.$pegawai->id, 'local');
        }

        $pegawai->dokumen()->create([
            ...collect($data)->except('file')->all(),
            'file_path' => $filePath,
        ]);

        return back()->with('status', 'Dokumen ditambahkan.');
    }

    public function download(DokumenPegawai $dokumen): StreamedResponse
    {
        abort_unless($dokumen->file_path && Storage::disk('local')->exists($dokumen->file_path), 404);

        return Storage::disk('local')->download($dokumen->file_path);
    }

    public function destroy(DokumenPegawai $dokumen): RedirectResponse
    {
        if ($dokumen->file_path) {
            Storage::disk('local')->delete($dokumen->file_path);
        }
        $dokumen->delete();

        return back()->with('status', 'Dokumen dihapus.');
    }
}
