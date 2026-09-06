<?php

namespace App\Http\Controllers\Master;

use App\Enums\JenisJabatan;
use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Master jabatan (katalog struktural + fungsional) — admin saja. */
class JabatanController extends Controller
{
    public function index(Request $request): View
    {
        $query = Jabatan::query()->withCount('penempatan')->orderBy('nama');

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->string('jenis'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status') === 'aktif');
        }

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $query->where(fn ($q) => $q->where('nama', 'like', "%{$cari}%")->orWhere('kode', 'like', "%{$cari}%"));
        }

        return view('master.jabatan.index', [
            'jabatan' => $query->paginate(30)->withQueryString(),
            'jenisOptions' => JenisJabatan::cases(),
            'filters' => $request->only(['jenis', 'status', 'cari']),
            'jmlAktif' => Jabatan::where('is_active', true)->count(),
            'jmlNonaktif' => Jabatan::where('is_active', false)->count(),
        ]);
    }

    public function create(): View
    {
        return view('master.jabatan.form', ['jabatan' => new Jabatan(['is_active' => true]), 'jenisOptions' => JenisJabatan::cases()]);
    }

    public function edit(Jabatan $jabatan): View
    {
        return view('master.jabatan.form', ['jabatan' => $jabatan, 'jenisOptions' => JenisJabatan::cases()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $jabatan = Jabatan::create($this->validasi($request, null));

        return redirect()->route('master.jabatan.index')->with('status', "Jabatan \"{$jabatan->nama}\" ditambahkan.");
    }

    public function update(Request $request, Jabatan $jabatan): RedirectResponse
    {
        $jabatan->update($this->validasi($request, $jabatan));

        return redirect()->route('master.jabatan.index')->with('status', 'Jabatan diperbarui.');
    }

    public function toggleAktif(Jabatan $jabatan): RedirectResponse
    {
        $jabatan->update(['is_active' => ! $jabatan->is_active]);

        return back()->with('status', $jabatan->is_active
            ? "\"{$jabatan->nama}\" diaktifkan kembali."
            : "\"{$jabatan->nama}\" dinonaktifkan.");
    }

    /** @return array<string, mixed> */
    private function validasi(Request $request, ?Jabatan $jabatan): array
    {
        return [
            ...$request->validate([
                'nama' => ['required', 'string', 'max:150'],
                'kode' => ['nullable', 'string', 'max:50', Rule::unique('jabatan', 'kode')->ignore($jabatan?->id)],
                'jenis' => ['required', Rule::enum(JenisJabatan::class)],
                'level' => ['nullable', 'integer', 'min:0', 'max:20'],
                'eselon' => ['nullable', 'string', 'max:20'],
                'deskripsi' => ['nullable', 'string', 'max:1000'],
            ]),
            'kode' => $request->filled('kode')
                ? $request->string('kode')->upper()->value()
                : $this->kodeUnik($request->string('nama')),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function kodeUnik(string $nama): string
    {
        $dasar = Str::of($nama)->slug('_')->upper()->limit(40, '')->value();
        $kode = $dasar;
        $i = 2;
        while (Jabatan::withTrashed()->where('kode', $kode)->exists()) {
            $kode = $dasar.'_'.$i++;
        }

        return $kode;
    }
}
