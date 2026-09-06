@extends('layouts.app')
@section('title', ($unitKerja->exists ? 'Ubah' : 'Tambah').' Unit Kerja — EIP')

@php
    $inp = 'w-full text-xs bg-[#F4F4F6]/80 border border-transparent focus:border-slate-200 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/5 rounded-xl px-3 py-2.5 transition-all';
    $lbl = 'block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1.5';
    $aksi = $unitKerja->exists ? route('master.unit-kerja.update', $unitKerja) : route('master.unit-kerja.store');
@endphp

@section('content')
    <div class="px-1">
        <a href="{{ route('master.unit-kerja.index') }}" class="text-xs font-semibold text-slate-400 hover:text-slate-600">&larr; Unit Kerja</a>
        <h1 class="text-lg font-bold text-slate-900 mt-1">{{ $unitKerja->exists ? 'Ubah Unit Kerja' : 'Tambah Unit Kerja' }}</h1>
    </div>

    @include('master._flash')

    <form method="POST" action="{{ $aksi }}" class="apple-glass-card rounded-3xl p-6 space-y-5 max-w-2xl">
        @csrf
        @if ($unitKerja->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="{{ $lbl }}">Nama unit</label>
                <input type="text" name="nama" required maxlength="150" value="{{ old('nama', $unitKerja->nama) }}" class="{{ $inp }}" placeholder="mis. S-1 Farmasi">
            </div>
            <div>
                <label class="{{ $lbl }}">Kode <span class="text-slate-300 normal-case font-medium">— kosongkan = otomatis</span></label>
                <input type="text" name="kode" maxlength="50" value="{{ old('kode', $unitKerja->kode) }}" class="{{ $inp }} font-mono-num uppercase" placeholder="S_1_FARMASI">
            </div>
            <div>
                <label class="{{ $lbl }}">Jenis unit</label>
                <select name="jenis_unit" required class="{{ $inp }}">
                    @foreach ($jenisOptions as $j)
                        <option value="{{ $j->value }}" @selected(old('jenis_unit', $unitKerja->jenis_unit?->value) === $j->value)>{{ ucfirst($j->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">Organisasi</label>
                <select name="organisasi_id" required class="{{ $inp }}">
                    <option value="">— pilih —</option>
                    @foreach ($organisasiOptions as $o)
                        <option value="{{ $o->id }}" @selected(old('organisasi_id', $unitKerja->organisasi_id) == $o->id)>{{ $o->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">Unit induk <span class="text-slate-300 normal-case font-medium">— opsional</span></label>
                <select name="parent_id" class="{{ $inp }}">
                    <option value="">— tidak ada —</option>
                    @foreach ($parentOptions as $p)
                        <option value="{{ $p->id }}" @selected(old('parent_id', $unitKerja->parent_id) == $p->id)>{{ $p->nama }} ({{ $p->jenis_unit->value }})</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="{{ $lbl }}">Kepala unit <span class="text-slate-300 normal-case font-medium">— opsional</span></label>
                <select name="kepala_id" class="{{ $inp }}">
                    <option value="">— tidak ditetapkan —</option>
                    @foreach ($pegawaiOptions as $peg)
                        <option value="{{ $peg->id }}" @selected(old('kepala_id', $unitKerja->kepala_id) == $peg->id)>{{ $peg->nama_lengkap }}{{ $peg->nip ? ' — '.$peg->nip : '' }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $unitKerja->is_active ?? true)) class="rounded border-slate-300">
            Unit aktif (muncul di picker &amp; API)
        </label>

        <div class="flex gap-2 pt-1">
            <button class="px-4 py-2.5 rounded-2xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 shadow-md shadow-slate-900/10 transition">{{ $unitKerja->exists ? 'Simpan perubahan' : 'Tambah' }}</button>
            <a href="{{ route('master.unit-kerja.index') }}" class="px-4 py-2.5 rounded-2xl text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition">Batal</a>
        </div>
    </form>
@endsection
