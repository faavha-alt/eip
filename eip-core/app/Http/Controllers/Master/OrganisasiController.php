<?php

namespace App\Http\Controllers\Master;

use App\Enums\JenisOrganisasi;
use App\Http\Controllers\Controller;
use App\Models\Organisasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Master organisasi (payung unit kerja: UNS, lembaga, dst) — admin saja. */
class OrganisasiController extends Controller
{
    public function index(Request $request): View
    {
        $query = Organisasi::query()
            ->with('parent:id,nama')
            ->withCount(['unitKerja', 'children'])
            ->orderBy('nama');

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status') === 'aktif');
        }

        if ($request->filled('cari')) {
            $cari = $request->string('cari');
            $query->where(fn ($q) => $q->where('nama', 'like', "%{$cari}%")->orWhere('kode', 'like', "%{$cari}%"));
        }

        return view('master.organisasi.index', [
            'organisasi' => $query->paginate(30)->withQueryString(),
            'filters' => $request->only(['status', 'cari']),
            'jmlAktif' => Organisasi::where('is_active', true)->count(),
            'jmlNonaktif' => Organisasi::where('is_active', false)->count(),
        ]);
    }

    public function create(): View
    {
        return view('master.organisasi.form', ['organisasi' => new Organisasi(['is_active' => true]), ...$this->opsiForm()]);
    }

    public function edit(Organisasi $organisasi): View
    {
        return view('master.organisasi.form', ['organisasi' => $organisasi, ...$this->opsiForm($organisasi)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $org = Organisasi::create($this->validasi($request, null));

        return redirect()->route('master.organisasi.index')->with('status', "Organisasi \"{$org->nama}\" ditambahkan.");
    }

    public function update(Request $request, Organisasi $organisasi): RedirectResponse
    {
        $organisasi->update($this->validasi($request, $organisasi));

        return redirect()->route('master.organisasi.index')->with('status', 'Organisasi diperbarui.');
    }

    public function toggleAktif(Organisasi $organisasi): RedirectResponse
    {
        $organisasi->update(['is_active' => ! $organisasi->is_active]);

        return back()->with('status', $organisasi->is_active
            ? "\"{$organisasi->nama}\" diaktifkan kembali."
            : "\"{$organisasi->nama}\" dinonaktifkan.");
    }

    /** @return array<string, mixed> */
    private function validasi(Request $request, ?Organisasi $org): array
    {
        return [
            ...$request->validate([
                'nama' => ['required', 'string', 'max:150'],
                'kode' => ['nullable', 'string', 'max:50', Rule::unique('organisasi', 'kode')->ignore($org?->id)],
                'jenis' => ['required', Rule::enum(JenisOrganisasi::class)],
                'parent_id' => ['nullable', 'exists:organisasi,id', Rule::notIn([$org?->id])],
                'alamat' => ['nullable', 'string', 'max:255'],
                'telepon' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:150'],
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
        while (Organisasi::withTrashed()->where('kode', $kode)->exists()) {
            $kode = $dasar.'_'.$i++;
        }

        return $kode;
    }

    /** @return array<string, mixed> */
    private function opsiForm(?Organisasi $org = null): array
    {
        return [
            'jenisOptions' => JenisOrganisasi::cases(),
            'parentOptions' => Organisasi::when($org, fn ($q) => $q->whereKeyNot($org->id))
                ->orderBy('nama')->get(['id', 'nama']),
        ];
    }
}
