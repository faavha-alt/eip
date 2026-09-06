@extends('layouts.app')
@section('title', ($organisasi->exists ? 'Ubah' : 'Tambah').' Organisasi — EIP')

@php
    $inp = 'w-full text-xs bg-[#F4F4F6]/80 border border-transparent focus:border-slate-200 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/5 rounded-xl px-3 py-2.5 transition-all';
    $lbl = 'block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1.5';
    $aksi = $organisasi->exists ? route('master.organisasi.update', $organisasi) : route('master.organisasi.store');
@endphp

@section('content')
    <div class="px-1">
        <a href="{{ route('master.organisasi.index') }}" class="text-xs font-semibold text-slate-400 hover:text-slate-600">&larr; Organisasi</a>
        <h1 class="text-lg font-bold text-slate-900 mt-1">{{ $organisasi->exists ? 'Ubah Organisasi' : 'Tambah Organisasi' }}</h1>
    </div>

    @include('master._flash')

    <form method="POST" action="{{ $aksi }}" class="apple-glass-card rounded-3xl p-6 space-y-5 max-w-2xl">
        @csrf
        @if ($organisasi->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="{{ $lbl }}">Nama organisasi</label>
                <input type="text" name="nama" required maxlength="150" value="{{ old('nama', $organisasi->nama) }}" class="{{ $inp }}" placeholder="mis. Universitas Sebelas Maret">
            </div>
            <div>
                <label class="{{ $lbl }}">Kode <span class="text-slate-300 normal-case font-medium">— kosongkan = otomatis</span></label>
                <input type="text" name="kode" maxlength="50" value="{{ old('kode', $organisasi->kode) }}" class="{{ $inp }} font-mono-num uppercase">
            </div>
            <div>
                <label class="{{ $lbl }}">Jenis</label>
                <select name="jenis" required class="{{ $inp }}">
                    @foreach ($jenisOptions as $j)
                        <option value="{{ $j->value }}" @selected(old('jenis', $organisasi->jenis?->value) === $j->value)>{{ ucfirst($j->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="{{ $lbl }}">Induk <span class="text-slate-300 normal-case font-medium">— opsional</span></label>
                <select name="parent_id" class="{{ $inp }}">
                    <option value="">— tidak ada —</option>
                    @foreach ($parentOptions as $p)
                        <option value="{{ $p->id }}" @selected(old('parent_id', $organisasi->parent_id) == $p->id)>{{ $p->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">Telepon <span class="text-slate-300 normal-case font-medium">— opsional</span></label>
                <input type="text" name="telepon" maxlength="30" value="{{ old('telepon', $organisasi->telepon) }}" class="{{ $inp }}">
            </div>
            <div>
                <label class="{{ $lbl }}">Email <span class="text-slate-300 normal-case font-medium">— opsional</span></label>
                <input type="email" name="email" maxlength="150" value="{{ old('email', $organisasi->email) }}" class="{{ $inp }}">
            </div>
            <div class="sm:col-span-2">
                <label class="{{ $lbl }}">Alamat <span class="text-slate-300 normal-case font-medium">— opsional</span></label>
                <input type="text" name="alamat" maxlength="255" value="{{ old('alamat', $organisasi->alamat) }}" class="{{ $inp }}">
            </div>
        </div>

        <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $organisasi->is_active ?? true)) class="rounded border-slate-300">
            Organisasi aktif (muncul di picker &amp; API)
        </label>

        <div class="flex gap-2 pt-1">
            <button class="px-4 py-2.5 rounded-2xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 shadow-md shadow-slate-900/10 transition">{{ $organisasi->exists ? 'Simpan perubahan' : 'Tambah' }}</button>
            <a href="{{ route('master.organisasi.index') }}" class="px-4 py-2.5 rounded-2xl text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition">Batal</a>
        </div>
    </form>
@endsection
