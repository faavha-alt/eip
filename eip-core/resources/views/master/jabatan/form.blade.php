@extends('layouts.app')
@section('title', ($jabatan->exists ? 'Ubah' : 'Tambah').' Jabatan — EIP')

@php
    $inp = 'w-full text-xs bg-[#F4F4F6]/80 border border-transparent focus:border-slate-200 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/5 rounded-xl px-3 py-2.5 transition-all';
    $lbl = 'block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1.5';
    $aksi = $jabatan->exists ? route('master.jabatan.update', $jabatan) : route('master.jabatan.store');
@endphp

@section('content')
    <div class="px-1">
        <a href="{{ route('master.jabatan.index') }}" class="text-xs font-semibold text-slate-400 hover:text-slate-600">&larr; Jabatan</a>
        <h1 class="text-lg font-bold text-slate-900 mt-1">{{ $jabatan->exists ? 'Ubah Jabatan' : 'Tambah Jabatan' }}</h1>
    </div>

    @include('master._flash')

    <form method="POST" action="{{ $aksi }}" class="apple-glass-card rounded-3xl p-6 space-y-5 max-w-2xl">
        @csrf
        @if ($jabatan->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="{{ $lbl }}">Nama jabatan</label>
                <input type="text" name="nama" required maxlength="150" value="{{ old('nama', $jabatan->nama) }}" class="{{ $inp }}" placeholder="mis. Ketua Program Studi S1 Farmasi">
            </div>
            <div>
                <label class="{{ $lbl }}">Kode <span class="text-slate-300 normal-case font-medium">— kosongkan = otomatis</span></label>
                <input type="text" name="kode" maxlength="50" value="{{ old('kode', $jabatan->kode) }}" class="{{ $inp }} font-mono-num uppercase">
            </div>
            <div>
                <label class="{{ $lbl }}">Jenis</label>
                <select name="jenis" required class="{{ $inp }}">
                    @foreach ($jenisOptions as $j)
                        <option value="{{ $j->value }}" @selected(old('jenis', $jabatan->jenis?->value) === $j->value)>{{ ucwords(str_replace('_', ' ', $j->value)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">Level <span class="text-slate-300 normal-case font-medium">— opsional, utk hierarki approval</span></label>
                <input type="number" name="level" min="0" max="20" value="{{ old('level', $jabatan->level) }}" class="{{ $inp }} font-mono-num">
            </div>
            <div>
                <label class="{{ $lbl }}">Eselon <span class="text-slate-300 normal-case font-medium">— opsional</span></label>
                <input type="text" name="eselon" maxlength="20" value="{{ old('eselon', $jabatan->eselon) }}" class="{{ $inp }}">
            </div>
            <div class="sm:col-span-2">
                <label class="{{ $lbl }}">Deskripsi <span class="text-slate-300 normal-case font-medium">— opsional</span></label>
                <textarea name="deskripsi" rows="2" maxlength="1000" class="{{ $inp }}">{{ old('deskripsi', $jabatan->deskripsi) }}</textarea>
            </div>
        </div>

        <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $jabatan->is_active ?? true)) class="rounded border-slate-300">
            Jabatan aktif (muncul di picker &amp; API)
        </label>

        <div class="flex gap-2 pt-1">
            <button class="px-4 py-2.5 rounded-2xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 shadow-md shadow-slate-900/10 transition">{{ $jabatan->exists ? 'Simpan perubahan' : 'Tambah' }}</button>
            <a href="{{ route('master.jabatan.index') }}" class="px-4 py-2.5 rounded-2xl text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition">Batal</a>
        </div>
    </form>
@endsection
