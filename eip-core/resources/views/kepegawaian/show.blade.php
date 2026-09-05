@extends('layouts.app')

@section('title', $pegawai->nama_lengkap.' — EIP')

@php
    $bolehTulis = auth()->user()->roles->pluck('kode')->intersect(['admin', 'admin-kepegawaian'])->isNotEmpty();
    $utama = $pegawai->penempatan->firstWhere('is_posisi_utama', true);
@endphp

@section('content')
    <div class="flex items-center justify-between px-1">
        <a href="{{ route('kepegawaian.index') }}" class="text-[11px] font-semibold text-slate-400 hover:text-slate-700">&larr; Kembali ke direktori</a>
        @if ($bolehTulis)
            <a href="{{ route('kepegawaian.edit', $pegawai) }}" class="px-4 py-2 rounded-2xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition">Ubah Data</a>
        @endif
    </div>

    @if (session('status'))
        <div class="apple-glass-card rounded-2xl p-4 border border-emerald-200/60 bg-emerald-50/60 text-xs font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-1 apple-glass-card rounded-3xl p-6 flex flex-col items-center text-center">
            <div class="w-20 h-20 rounded-3xl bg-gradient-to-tr from-slate-200 to-slate-100 flex items-center justify-center font-bold text-xl text-slate-800 border border-slate-300/60 shadow-sm mb-4">
                {{ collect(explode(' ', $pegawai->nama_lengkap))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
            </div>
            <h1 class="text-base font-bold text-slate-900">{{ $pegawai->gelar_depan ? $pegawai->gelar_depan.' ' : '' }}{{ $pegawai->nama_lengkap }}{{ $pegawai->gelar_belakang ? ', '.$pegawai->gelar_belakang : '' }}</h1>
            <p class="text-xs text-slate-400 font-mono-num mt-1">{{ $pegawai->nip ?? '—' }}</p>
            <span class="mt-3 px-2.5 py-1 rounded-full border text-[10px] font-bold {{ $pegawai->is_active ? 'bg-emerald-50 text-emerald-600 border-emerald-100/60' : 'bg-slate-100 text-slate-500 border-slate-200/60' }}">
                {{ $pegawai->is_active ? 'Aktif' : 'Nonaktif' }}
            </span>

            <div class="w-full mt-6 pt-5 border-t border-slate-100 text-left space-y-3 text-xs">
                <div class="flex justify-between"><span class="text-slate-400">Unit Kerja</span><span class="font-semibold text-slate-700 text-right">{{ $utama?->unitKerja?->nama ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Jabatan</span><span class="font-semibold text-slate-700 text-right">{{ $utama?->jabatan?->nama ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Status</span><span class="font-semibold text-slate-700">{{ $pegawai->statusKepegawaian?->nama ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Jenis</span><span class="font-semibold text-slate-700">{{ $pegawai->jenis_pegawai?->value === 'tenaga_pendidik' ? 'Dosen' : ($pegawai->jenis_pegawai?->value === 'tenaga_kependidikan' ? 'Tendik' : '—') }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Pendidikan</span><span class="font-semibold text-slate-700">{{ $pegawai->pendidikanTerakhir?->nama ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Golongan</span><span class="font-semibold text-slate-700 font-mono-num">{{ $pegawai->golonganRuang?->kode ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Email</span><span class="font-semibold text-slate-700 text-right break-all">{{ $pegawai->email ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">No. HP</span><span class="font-semibold text-slate-700">{{ $pegawai->no_hp ?? '—' }}</span></div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-5">
            <div class="apple-glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-bold text-slate-900">Riwayat Penempatan</h2>
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-mono-num font-bold">{{ $pegawai->penempatan->count() }}</span>
                </div>

                <div class="space-y-2 mb-4">
                    @forelse ($pegawai->penempatan as $p)
                        <div class="flex items-center justify-between p-3 rounded-2xl {{ $p->is_posisi_utama && !$p->tgl_selesai ? 'bg-indigo-50/60 border border-indigo-100/60' : 'bg-slate-50/60' }}">
                            <div>
                                <p class="text-xs font-bold text-slate-800">{{ $p->jabatan?->nama }}</p>
                                <p class="text-[11px] text-slate-500">{{ $p->unitKerja?->nama }}</p>
                                <p class="text-[10px] text-slate-400 font-mono-num mt-0.5">{{ $p->tgl_mulai?->translatedFormat('d M Y') }} — {{ $p->tgl_selesai?->translatedFormat('d M Y') ?? 'sekarang' }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($p->is_posisi_utama)
                                    <span class="text-[10px] font-bold text-indigo-600 bg-indigo-100 px-2 py-0.5 rounded-full">Utama</span>
                                @endif
                                @if ($bolehTulis && ! $p->tgl_selesai)
                                    <form method="POST" action="{{ route('kepegawaian.penempatan.destroy', $p) }}" onsubmit="return confirm('Hapus penempatan ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-[10px] font-semibold text-rose-500 hover:text-rose-700">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">Belum ada riwayat penempatan.</p>
                    @endforelse
                </div>

                @if ($bolehTulis)
                    <form method="POST" action="{{ route('kepegawaian.penempatan.store', $pegawai) }}" class="pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2">
                        @csrf
                        <select name="unit_kerja_id" required class="text-xs bg-[#F4F4F6]/80 border border-transparent rounded-xl px-3 py-2.5 focus:outline-none focus:bg-white lg:col-span-2">
                            <option value="">Pilih unit kerja</option>
                            @foreach ($unitKerjaOptions as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->nama }}</option>
                            @endforeach
                        </select>
                        <select name="jabatan_id" required class="text-xs bg-[#F4F4F6]/80 border border-transparent rounded-xl px-3 py-2.5 focus:outline-none focus:bg-white lg:col-span-2">
                            <option value="">Pilih jabatan</option>
                            @foreach ($jabatanOptions as $jab)
                                <option value="{{ $jab->id }}">{{ $jab->nama }}</option>
                            @endforeach
                        </select>
                        <input type="date" name="tgl_mulai" required value="{{ now()->toDateString() }}" class="text-xs bg-[#F4F4F6]/80 border border-transparent rounded-xl px-3 py-2.5 focus:outline-none focus:bg-white">
                        <label class="flex items-center gap-2 text-[11px] font-semibold text-slate-500 sm:col-span-2">
                            <input type="checkbox" name="is_posisi_utama" value="1" class="w-4 h-4 rounded border-slate-300 text-indigo-600">
                            Jadikan posisi utama
                        </label>
                        <button type="submit" class="lg:col-span-3 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 transition">Tambah Penempatan</button>
                    </form>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="apple-glass-card rounded-3xl p-6">
                    <h2 class="text-sm font-bold text-slate-900 mb-3">Riwayat Pendidikan</h2>
                    @forelse ($pegawai->riwayatPendidikan as $r)
                        <div class="text-xs py-2 border-b border-slate-100 last:border-0">
                            <p class="font-semibold text-slate-700">{{ $r->pendidikan?->nama }} — {{ $r->nama_institusi ?? '—' }}</p>
                            <p class="text-[10px] text-slate-400">{{ $r->program_studi }} @if($r->tahun_lulus) · lulus {{ $r->tahun_lulus }} @endif</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">Belum ada data — menyusul.</p>
                    @endforelse
                </div>

                <div class="apple-glass-card rounded-3xl p-6">
                    <h2 class="text-sm font-bold text-slate-900 mb-3">Riwayat Pangkat/Golongan</h2>
                    @forelse ($pegawai->riwayatPangkatGolongan as $r)
                        <div class="text-xs py-2 border-b border-slate-100 last:border-0">
                            <p class="font-semibold text-slate-700 font-mono-num">{{ $r->golonganRuang?->kode }}</p>
                            <p class="text-[10px] text-slate-400">TMT {{ $r->tmt?->translatedFormat('d M Y') }} @if($r->no_sk) · SK {{ $r->no_sk }} @endif</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">Belum ada data — menyusul.</p>
                    @endforelse
                </div>

                <div class="apple-glass-card rounded-3xl p-6">
                    <h2 class="text-sm font-bold text-slate-900 mb-3">Keluarga</h2>
                    @forelse ($pegawai->keluarga as $k)
                        <div class="text-xs py-2 border-b border-slate-100 last:border-0">
                            <p class="font-semibold text-slate-700">{{ $k->nama }} <span class="text-slate-400 font-normal">({{ $k->hubungan->value }})</span></p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">Belum ada data — menyusul.</p>
                    @endforelse
                </div>

                <div class="apple-glass-card rounded-3xl p-6">
                    <h2 class="text-sm font-bold text-slate-900 mb-3">Dokumen</h2>
                    @forelse ($pegawai->dokumen as $d)
                        <div class="text-xs py-2 border-b border-slate-100 last:border-0">
                            <p class="font-semibold text-slate-700">{{ $d->jenis->value }} @if($d->nomor_dokumen) — {{ $d->nomor_dokumen }} @endif</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">Belum ada data — menyusul.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
