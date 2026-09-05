<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use App\Models\GolonganRuang;
use App\Models\Jabatan;
use App\Models\Pegawai;
use App\Models\Pendidikan;
use App\Models\StatusKepegawaian;
use App\Models\UnitKerja;
use App\Support\PegawaiRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Modul Kepegawaian — DI DALAM EIP Core (bukan app terpisah, lihat CLAUDE.md
 * §1 revisi 2026-09-05). Penulis tunggal `pegawai`/`penempatan`, langsung
 * via Eloquent (satu app/DB, tanpa panggil API HTTP internal). Akses tulis
 * dibatasi middleware `role:admin-kepegawaian` di routes/web.php.
 */
class PegawaiController extends Controller
{
    private const RELATIONS = ['statusKepegawaian', 'pendidikanTerakhir', 'golonganRuang'];

    public function index(Request $request): View
    {
        $query = Pegawai::query()
            ->with([...self::RELATIONS, 'penempatan' => fn ($q) => $q->where('is_posisi_utama', true)->with('unitKerja')])
            ->withCount('penempatan')
            ->orderBy('nama_lengkap');

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $query->where(fn ($q) => $q->where('nama_lengkap', 'like', "%{$cari}%")
                ->orWhere('nip', 'like', "%{$cari}%")
                ->orWhere('email', 'like', "%{$cari}%"));
        }

        if ($request->filled('unit_kerja_id')) {
            $query->whereHas('penempatan', fn ($q) => $q
                ->where('unit_kerja_id', $request->input('unit_kerja_id'))
                ->where('is_posisi_utama', true));
        }

        if ($request->filled('status_kepegawaian_id')) {
            $query->where('status_kepegawaian_id', $request->input('status_kepegawaian_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return view('kepegawaian.index', [
            'pegawai' => $query->paginate(20)->withQueryString(),
            'unitKerjaOptions' => UnitKerja::orderBy('nama')->get(['id', 'nama']),
            'statusKepegawaianOptions' => StatusKepegawaian::orderBy('nama')->get(['id', 'nama']),
            'filters' => $request->only(['cari', 'unit_kerja_id', 'status_kepegawaian_id', 'is_active']),
        ]);
    }

    public function show(Pegawai $pegawai): View
    {
        $pegawai->load([
            ...self::RELATIONS,
            'penempatan' => fn ($q) => $q->with(['unitKerja', 'jabatan'])->orderByDesc('tgl_mulai'),
            'riwayatPendidikan.pendidikan',
            'riwayatPangkatGolongan.golonganRuang',
            'keluarga',
            'dokumen',
        ]);

        return view('kepegawaian.show', [
            'pegawai' => $pegawai,
            'unitKerjaOptions' => UnitKerja::orderBy('nama')->get(['id', 'nama']),
            'jabatanOptions' => Jabatan::orderBy('nama')->get(['id', 'nama']),
            'pendidikanOptions' => Pendidikan::orderBy('jenjang')->get(['id', 'nama']),
            'golonganOptions' => GolonganRuang::orderBy('tingkat')->get(['id', 'kode']),
        ]);
    }

    public function create(): View
    {
        return view('kepegawaian.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(PegawaiRules::make());
        $data['is_active'] = $request->boolean('is_active', true);

        $pegawai = Pegawai::create($data);

        return redirect()->route('kepegawaian.show', $pegawai)->with('status', 'Pegawai berhasil ditambahkan.');
    }

    public function edit(Pegawai $pegawai): View
    {
        return view('kepegawaian.edit', array_merge(['pegawai' => $pegawai], $this->formOptions()));
    }

    public function update(Request $request, Pegawai $pegawai): RedirectResponse
    {
        $data = $request->validate(PegawaiRules::make($pegawai->id, sometimes: true));
        $data['is_active'] = $request->boolean('is_active');

        $pegawai->update($data);

        return redirect()->route('kepegawaian.show', $pegawai)->with('status', 'Data pegawai diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'statusKepegawaianOptions' => StatusKepegawaian::orderBy('nama')->get(),
            'pendidikanOptions' => Pendidikan::orderBy('jenjang')->get(),
            'golonganOptions' => GolonganRuang::orderBy('tingkat')->get(),
        ];
    }
}
