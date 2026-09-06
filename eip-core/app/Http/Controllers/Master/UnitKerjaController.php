<?php

namespace App\Http\Controllers\Master;

use App\Enums\JenisUnitKerja;
use App\Http\Controllers\Controller;
use App\Models\Organisasi;
use App\Models\Pegawai;
use App\Models\UnitKerja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Master unit kerja (struktur organisasi FMIPA) — admin saja
 * (routes/web.php `role:admin`). Penonaktifan (is_active=false) dipakai
 * saat sebuah unit mis. prodi pindah/keluar fakultas: datanya tetap utk
 * histori penempatan, tapi tak lagi muncul di picker & API default.
 */
class UnitKerjaController extends Controller
{
    private const RELASI_HITUNG = ['penempatan', 'children'];

    public function index(Request $request): View
    {
        $query = UnitKerja::query()
            ->with(['parent:id,nama', 'organisasi:id,nama', 'kepala:id,nama_lengkap'])
            ->withCount(self::RELASI_HITUNG)
            ->orderBy('nama');

        if ($request->filled('jenis_unit')) {
            $query->where('jenis_unit', $request->string('jenis_unit'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status') === 'aktif');
        }

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $query->where(fn ($q) => $q->where('nama', 'like', "%{$cari}%")->orWhere('kode', 'like', "%{$cari}%"));
        }

        return view('master.unit-kerja.index', [
            'unitKerja' => $query->paginate(30)->withQueryString(),
            'jenisOptions' => JenisUnitKerja::cases(),
            'filters' => $request->only(['jenis_unit', 'status', 'cari']),
            'jmlAktif' => UnitKerja::where('is_active', true)->count(),
            'jmlNonaktif' => UnitKerja::where('is_active', false)->count(),
        ]);
    }

    public function create(): View
    {
        return view('master.unit-kerja.form', ['unitKerja' => new UnitKerja(['is_active' => true]), ...$this->opsiForm()]);
    }

    public function edit(UnitKerja $unitKerja): View
    {
        return view('master.unit-kerja.form', ['unitKerja' => $unitKerja, ...$this->opsiForm($unitKerja)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request, null);
        $unit = UnitKerja::create($data);

        return redirect()->route('master.unit-kerja.index')->with('status', "Unit kerja \"{$unit->nama}\" ditambahkan.");
    }

    public function update(Request $request, UnitKerja $unitKerja): RedirectResponse
    {
        $unitKerja->update($this->validasi($request, $unitKerja));

        return redirect()->route('master.unit-kerja.index')->with('status', 'Unit kerja diperbarui.');
    }

    public function toggleAktif(UnitKerja $unitKerja): RedirectResponse
    {
        $unitKerja->update(['is_active' => ! $unitKerja->is_active]);

        $pesan = $unitKerja->is_active
            ? "\"{$unitKerja->nama}\" diaktifkan kembali."
            : "\"{$unitKerja->nama}\" dinonaktifkan — tidak lagi muncul di picker & API.";

        $penempatanAktif = $unitKerja->penempatan()->whereNull('tgl_selesai')->count();
        if (! $unitKerja->is_active && $penempatanAktif > 0) {
            $pesan .= " Catatan: masih ada {$penempatanAktif} penempatan aktif di unit ini.";
        }

        return back()->with('status', $pesan);
    }

    /** @return array<string, mixed> */
    private function validasi(Request $request, ?UnitKerja $unit): array
    {
        return [
            ...$request->validate([
                'nama' => ['required', 'string', 'max:150'],
                'kode' => ['nullable', 'string', 'max:50', Rule::unique('unit_kerja', 'kode')->ignore($unit?->id)],
                'jenis_unit' => ['required', Rule::enum(JenisUnitKerja::class)],
                'organisasi_id' => ['required', 'exists:organisasi,id'],
                'parent_id' => ['nullable', 'exists:unit_kerja,id', Rule::notIn([$unit?->id])],
                'kepala_id' => ['nullable', 'exists:pegawai,id'],
            ]),
            'kode' => $request->filled('kode') ? $request->string('kode')->upper()->value() : $this->kodeUnik($request->string('nama')),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function kodeUnik(string $nama): string
    {
        $dasar = Str::of($nama)->slug('_')->upper()->limit(40, '')->value();
        $kode = $dasar;
        $i = 2;
        while (UnitKerja::withTrashed()->where('kode', $kode)->exists()) {
            $kode = $dasar.'_'.$i++;
        }

        return $kode;
    }

    /** @return array<string, mixed> */
    private function opsiForm(?UnitKerja $unit = null): array
    {
        return [
            'jenisOptions' => JenisUnitKerja::cases(),
            'organisasiOptions' => Organisasi::orderBy('nama')->get(['id', 'nama']),
            'parentOptions' => UnitKerja::when($unit, fn ($q) => $q->whereKeyNot($unit->id))
                ->orderBy('nama')->get(['id', 'nama', 'jenis_unit']),
            'pegawaiOptions' => Pegawai::where('is_active', true)->orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'nip']),
        ];
    }
}
