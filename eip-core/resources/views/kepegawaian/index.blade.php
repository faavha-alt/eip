@extends('layouts.app')

@section('title', 'Direktori Pegawai — EIP')

@section('content')
    <div class="flex items-center justify-between px-1">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Direktori Pegawai</h1>
            <p class="text-xs text-slate-400 font-medium mt-0.5">{{ $pegawai->total() }} pegawai terdaftar di master EIP</p>
        </div>
        @auth
            @if (auth()->user()->roles->pluck('kode')->intersect(['admin', 'admin-kepegawaian'])->isNotEmpty())
                <a href="{{ route('kepegawaian.create') }}" class="px-4 py-2.5 rounded-2xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 shadow-md shadow-slate-900/10 transition">+ Tambah Pegawai</a>
            @endif
        @endauth
    </div>

    <form method="GET" action="{{ route('kepegawaian.index') }}" class="apple-glass-card rounded-3xl p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="lg:col-span-2">
            <input type="text" name="cari" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari nama, NIP, atau email..." class="w-full text-xs bg-[#F4F4F6]/80 border border-transparent focus:border-slate-200 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/5 rounded-xl px-3 py-2.5 transition-all">
        </div>
        <select name="unit_kerja_id" class="text-xs bg-[#F4F4F6]/80 border border-transparent rounded-xl px-3 py-2.5 focus:outline-none focus:bg-white">
            <option value="">Semua Unit Kerja</option>
            @foreach ($unitKerjaOptions as $unit)
                <option value="{{ $unit->id }}" @selected(($filters['unit_kerja_id'] ?? null) == $unit->id)>{{ $unit->nama }}</option>
            @endforeach
        </select>
        <select name="status_kepegawaian_id" class="text-xs bg-[#F4F4F6]/80 border border-transparent rounded-xl px-3 py-2.5 focus:outline-none focus:bg-white">
            <option value="">Semua Status</option>
            @foreach ($statusKepegawaianOptions as $status)
                <option value="{{ $status->id }}" @selected(($filters['status_kepegawaian_id'] ?? null) == $status->id)>{{ $status->nama }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <select name="is_active" class="w-full text-xs bg-[#F4F4F6]/80 border border-transparent rounded-xl px-3 py-2.5 focus:outline-none focus:bg-white">
                <option value="">Aktif &amp; Nonaktif</option>
                <option value="1" @selected(($filters['is_active'] ?? null) === '1')>Aktif saja</option>
                <option value="0" @selected(($filters['is_active'] ?? null) === '0')>Nonaktif saja</option>
            </select>
            <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition shrink-0">Cari</button>
        </div>
    </form>

    <div class="apple-glass-card rounded-3xl overflow-hidden border border-white/90">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/60 text-slate-400 uppercase text-[10px] tracking-wider font-bold border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Nama</th>
                        <th class="px-6 py-3.5">Unit Kerja</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Jenis</th>
                        <th class="px-6 py-3.5">Kondisi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse ($pegawai as $p)
                        @php $utama = $p->penempatan->first(); @endphp
                        <tr class="hover:bg-white/80 transition-all cursor-pointer" onclick="window.location='{{ route('kepegawaian.show', $p) }}'">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900">{{ $p->gelar_depan ? $p->gelar_depan.' ' : '' }}{{ $p->nama_lengkap }}{{ $p->gelar_belakang ? ', '.$p->gelar_belakang : '' }}</p>
                                <p class="text-[10px] text-slate-400 font-mono-num">{{ $p->nip ?? $p->email ?? '—' }}</p>
                            </td>
                            <td class="px-6 py-4">{{ $utama?->unitKerja?->nama ?? '—' }}</td>
                            <td class="px-6 py-4">{{ $p->statusKepegawaian?->nama ?? '—' }}</td>
                            <td class="px-6 py-4">{{ $p->jenis_pegawai?->value === 'tenaga_pendidik' ? 'Dosen' : ($p->jenis_pegawai?->value === 'tenaga_kependidikan' ? 'Tendik' : '—') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full border text-[10px] font-bold {{ $p->is_active ? 'bg-emerald-50 text-emerald-600 border-emerald-100/60' : 'bg-slate-100 text-slate-500 border-slate-200/60' }}">
                                    {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400">Tidak ada pegawai yang cocok dgn filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 bg-slate-50/50 border-t border-slate-100">
            {{ $pegawai->links() }}
        </div>
    </div>
@endsection
