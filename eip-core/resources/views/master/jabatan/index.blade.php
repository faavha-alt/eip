@extends('layouts.app')
@section('title', 'Jabatan — EIP')

@section('content')
    <div class="flex items-center justify-between px-1">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Jabatan</h1>
            <p class="text-xs text-slate-400 font-medium mt-0.5">{{ $jabatan->total() }} jabatan · {{ $jmlAktif }} aktif · {{ $jmlNonaktif }} nonaktif</p>
        </div>
        <a href="{{ route('master.jabatan.create') }}" class="px-4 py-2.5 rounded-2xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 shadow-md shadow-slate-900/10 transition">+ Tambah Jabatan</a>
    </div>

    @include('master._flash')

    <form method="GET" class="apple-glass-card rounded-3xl p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input type="text" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari nama / kode jabatan..." class="lg:col-span-2 w-full text-xs bg-[#F4F4F6]/80 border border-transparent focus:border-slate-200 focus:bg-white focus:outline-none rounded-xl px-3 py-2.5 transition-all">
        <select name="jenis" class="text-xs bg-[#F4F4F6]/80 border border-transparent rounded-xl px-3 py-2.5 focus:outline-none focus:bg-white">
            <option value="">Semua jenis</option>
            @foreach ($jenisOptions as $j)
                <option value="{{ $j->value }}" @selected(($filters['jenis'] ?? '') === $j->value)>{{ ucwords(str_replace('_', ' ', $j->value)) }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <select name="status" class="w-full text-xs bg-[#F4F4F6]/80 border border-transparent rounded-xl px-3 py-2.5 focus:outline-none focus:bg-white">
                <option value="">Aktif &amp; nonaktif</option>
                <option value="aktif" @selected(($filters['status'] ?? '') === 'aktif')>Aktif saja</option>
                <option value="nonaktif" @selected(($filters['status'] ?? '') === 'nonaktif')>Nonaktif saja</option>
            </select>
            <button class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition shrink-0">Cari</button>
        </div>
    </form>

    <div class="apple-glass-card rounded-3xl overflow-hidden border border-white/90">
        <div class="overflow-x-auto custom-scroll">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/60 text-slate-400 uppercase text-[10px] tracking-wider font-bold border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Nama</th>
                        <th class="px-6 py-3.5">Jenis</th>
                        <th class="px-6 py-3.5 text-right">Level</th>
                        <th class="px-6 py-3.5">Eselon</th>
                        <th class="px-6 py-3.5 text-right">Dipakai</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse ($jabatan as $j)
                        <tr class="hover:bg-white/80 transition-all {{ $j->is_active ? '' : 'opacity-60' }}">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900">{{ $j->nama }}</p>
                                <p class="text-[10px] text-slate-400 font-mono-num">{{ $j->kode }}</p>
                            </td>
                            <td class="px-6 py-4">{{ ucwords(str_replace('_', ' ', $j->jenis->value)) }}</td>
                            <td class="px-6 py-4 text-right font-mono-num">{{ $j->level ?? '—' }}</td>
                            <td class="px-6 py-4">{{ $j->eselon ?? '—' }}</td>
                            <td class="px-6 py-4 text-right font-mono-num">{{ $j->penempatan_count }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full border text-[10px] font-bold {{ $j->is_active ? 'bg-emerald-50 text-emerald-600 border-emerald-100/60' : 'bg-slate-100 text-slate-500 border-slate-200/60' }}">
                                    {{ $j->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1.5 justify-end">
                                    <a href="{{ route('master.jabatan.edit', $j) }}" class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition">Ubah</a>
                                    <form method="POST" action="{{ route('master.jabatan.aktif', $j) }}" onsubmit="return confirm('{{ $j->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $j->nama }}?')">
                                        @csrf @method('PATCH')
                                        <button class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold border transition {{ $j->is_active ? 'text-rose-600 bg-white border-rose-200 hover:bg-rose-50' : 'text-emerald-600 bg-white border-emerald-200 hover:bg-emerald-50' }}">
                                            {{ $j->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-12 text-center text-slate-400">Tidak ada jabatan untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $jabatan->links() }}</div>
@endsection
