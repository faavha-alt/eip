@extends('layouts.app')

@section('title', $pegawai->nama_lengkap.' — EIP')

@php
    $bolehTulis = auth()->user()->roles->pluck('kode')->intersect(['admin', 'admin-kepegawaian'])->isNotEmpty();
    $utama = $pegawai->penempatan->firstWhere('is_posisi_utama', true);
    $miniInput = 'w-full text-[11px] bg-[#F4F4F6]/80 border border-transparent focus:border-slate-200 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/5 rounded-xl px-2.5 py-2 transition-all';
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
                {{-- Riwayat Pendidikan --}}
                <div class="apple-glass-card rounded-3xl p-6">
                    <h2 class="text-sm font-bold text-slate-900 mb-3">Riwayat Pendidikan</h2>
                    <div class="space-y-1 mb-3">
                        @forelse ($pegawai->riwayatPendidikan as $r)
                            <div class="flex items-start justify-between text-xs py-2 border-b border-slate-100 last:border-0">
                                <div>
                                    <p class="font-semibold text-slate-700">{{ $r->pendidikan?->nama }} — {{ $r->nama_institusi ?? '—' }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $r->program_studi }} @if($r->tahun_lulus) · lulus {{ $r->tahun_lulus }} @endif</p>
                                </div>
                                @if ($bolehTulis)
                                    <form method="POST" action="{{ route('kepegawaian.riwayat-pendidikan.destroy', $r) }}" onsubmit="return confirm('Hapus riwayat ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-[10px] font-semibold text-rose-500 hover:text-rose-700 shrink-0">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">Belum ada data.</p>
                        @endforelse
                    </div>
                    @if ($bolehTulis)
                        <form method="POST" action="{{ route('kepegawaian.riwayat-pendidikan.store', $pegawai) }}" class="pt-3 border-t border-slate-100 grid grid-cols-2 gap-2">
                            @csrf
                            <select name="pendidikan_id" required class="{{ $miniInput }} col-span-2">
                                <option value="">Jenjang</option>
                                @foreach ($pendidikanOptions as $opt)
                                    <option value="{{ $opt->id }}">{{ $opt->nama }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="nama_institusi" placeholder="Institusi" class="{{ $miniInput }} col-span-2">
                            <input type="text" name="program_studi" placeholder="Program studi" class="{{ $miniInput }}">
                            <input type="number" name="tahun_lulus" placeholder="Tahun lulus" class="{{ $miniInput }}">
                            <button type="submit" class="col-span-2 px-3 py-2 rounded-xl text-[11px] font-bold text-white bg-slate-900 hover:bg-slate-800 transition">Tambah</button>
                        </form>
                    @endif
                </div>

                {{-- Riwayat Pangkat/Golongan --}}
                <div class="apple-glass-card rounded-3xl p-6">
                    <h2 class="text-sm font-bold text-slate-900 mb-3">Riwayat Pangkat/Golongan</h2>
                    <div class="space-y-1 mb-3">
                        @forelse ($pegawai->riwayatPangkatGolongan as $r)
                            <div class="flex items-start justify-between text-xs py-2 border-b border-slate-100 last:border-0">
                                <div>
                                    <p class="font-semibold text-slate-700 font-mono-num">{{ $r->golonganRuang?->kode }}</p>
                                    <p class="text-[10px] text-slate-400">TMT {{ $r->tmt?->translatedFormat('d M Y') }} @if($r->no_sk) · SK {{ $r->no_sk }} @endif</p>
                                </div>
                                @if ($bolehTulis)
                                    <form method="POST" action="{{ route('kepegawaian.riwayat-pangkat.destroy', $r) }}" onsubmit="return confirm('Hapus riwayat ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-[10px] font-semibold text-rose-500 hover:text-rose-700 shrink-0">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">Belum ada data.</p>
                        @endforelse
                    </div>
                    @if ($bolehTulis)
                        <form method="POST" action="{{ route('kepegawaian.riwayat-pangkat.store', $pegawai) }}" class="pt-3 border-t border-slate-100 grid grid-cols-2 gap-2">
                            @csrf
                            <select name="golongan_ruang_id" required class="{{ $miniInput }} col-span-2">
                                <option value="">Golongan/Ruang</option>
                                @foreach ($golonganOptions as $opt)
                                    <option value="{{ $opt->id }}">{{ $opt->kode }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="tmt" required class="{{ $miniInput }}">
                            <input type="text" name="no_sk" placeholder="No. SK" class="{{ $miniInput }}">
                            <button type="submit" class="col-span-2 px-3 py-2 rounded-xl text-[11px] font-bold text-white bg-slate-900 hover:bg-slate-800 transition">Tambah</button>
                        </form>
                    @endif
                </div>

                {{-- Keluarga --}}
                <div class="apple-glass-card rounded-3xl p-6">
                    <h2 class="text-sm font-bold text-slate-900 mb-3">Keluarga</h2>
                    <div class="space-y-1 mb-3">
                        @forelse ($pegawai->keluarga as $k)
                            <div class="flex items-start justify-between text-xs py-2 border-b border-slate-100 last:border-0">
                                <p class="font-semibold text-slate-700">{{ $k->nama }} <span class="text-slate-400 font-normal">({{ ['pasangan' => 'Pasangan', 'anak' => 'Anak', 'orang_tua' => 'Orang Tua', 'lainnya' => 'Lainnya'][$k->hubungan->value] }})</span></p>
                                @if ($bolehTulis)
                                    <form method="POST" action="{{ route('kepegawaian.keluarga.destroy', $k) }}" onsubmit="return confirm('Hapus data keluarga ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-[10px] font-semibold text-rose-500 hover:text-rose-700 shrink-0">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">Belum ada data.</p>
                        @endforelse
                    </div>
                    @if ($bolehTulis)
                        <form method="POST" action="{{ route('kepegawaian.keluarga.store', $pegawai) }}" class="pt-3 border-t border-slate-100 grid grid-cols-2 gap-2">
                            @csrf
                            <select name="hubungan" required class="{{ $miniInput }}">
                                <option value="pasangan">Pasangan</option>
                                <option value="anak">Anak</option>
                                <option value="orang_tua">Orang Tua</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                            <input type="text" name="nama" placeholder="Nama" required class="{{ $miniInput }}">
                            <input type="date" name="tanggal_lahir" class="{{ $miniInput }}">
                            <input type="text" name="no_hp" placeholder="No. HP" class="{{ $miniInput }}">
                            <button type="submit" class="col-span-2 px-3 py-2 rounded-xl text-[11px] font-bold text-white bg-slate-900 hover:bg-slate-800 transition">Tambah</button>
                        </form>
                    @endif
                </div>

                {{-- Dokumen --}}
                <div class="apple-glass-card rounded-3xl p-6">
                    <h2 class="text-sm font-bold text-slate-900 mb-3">Dokumen</h2>
                    <div class="space-y-1 mb-3">
                        @forelse ($pegawai->dokumen as $d)
                            <div class="flex items-start justify-between text-xs py-2 border-b border-slate-100 last:border-0">
                                <div>
                                    <p class="font-semibold text-slate-700">{{ ucwords(str_replace('_', ' ', $d->jenis->value)) }} @if($d->nomor_dokumen) — {{ $d->nomor_dokumen }} @endif</p>
                                    @if ($d->file_path)
                                        <a href="{{ route('kepegawaian.dokumen.download', $d) }}" class="text-[10px] text-indigo-600 hover:underline">Unduh berkas</a>
                                    @endif
                                </div>
                                @if ($bolehTulis)
                                    <form method="POST" action="{{ route('kepegawaian.dokumen.destroy', $d) }}" onsubmit="return confirm('Hapus dokumen ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-[10px] font-semibold text-rose-500 hover:text-rose-700 shrink-0">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">Belum ada data.</p>
                        @endforelse
                    </div>
                    @if ($bolehTulis)
                        <form method="POST" action="{{ route('kepegawaian.dokumen.store', $pegawai) }}" enctype="multipart/form-data" class="pt-3 border-t border-slate-100 grid grid-cols-2 gap-2">
                            @csrf
                            <select name="jenis" required class="{{ $miniInput }} col-span-2">
                                <option value="sk_cpns">SK CPNS</option>
                                <option value="sk_pns">SK PNS</option>
                                <option value="sk_golongan">SK Golongan</option>
                                <option value="sk_jabatan">SK Jabatan</option>
                                <option value="ijazah">Ijazah</option>
                                <option value="ktp">KTP</option>
                                <option value="kartu_keluarga">Kartu Keluarga</option>
                                <option value="npwp">NPWP</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                            <input type="text" name="nomor_dokumen" placeholder="Nomor dokumen" class="{{ $miniInput }}">
                            <input type="date" name="tanggal_dokumen" class="{{ $miniInput }}">
                            <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="{{ $miniInput }} col-span-2 py-1.5">
                            <button type="submit" class="col-span-2 px-3 py-2 rounded-xl text-[11px] font-bold text-white bg-slate-900 hover:bg-slate-800 transition">Tambah</button>
                        </form>
                    @endif
                </div>

                {{-- Riwayat Jabatan (arsip SIMPEG, read-only) --}}
                <div class="apple-glass-card rounded-3xl p-6 sm:col-span-2">
                    <h2 class="text-sm font-bold text-slate-900 mb-1">Riwayat Jabatan</h2>
                    <p class="text-[10px] text-slate-400 mb-3">Arsip dari SIMPEG, hanya baca.</p>
                    <div class="space-y-1 max-h-64 overflow-y-auto">
                        @forelse ($pegawai->riwayatJabatan as $j)
                            <div class="flex items-start justify-between gap-3 text-xs py-2 border-b border-slate-100 last:border-0">
                                <div>
                                    <p class="font-semibold text-slate-700">{{ $j->jabatan_nama }} @if($j->jabatan_detail) — {{ $j->jabatan_detail }} @endif</p>
                                    <p class="text-[10px] text-slate-400">
                                        {{ $j->jenis === \App\Enums\JenisRiwayatJabatan::Struktural ? 'Struktural' : 'Fungsional' }}
                                        · TMT {{ $j->tmt_awal?->translatedFormat('d M Y') ?? '—' }}
                                        s/d {{ $j->tmt_akhir?->translatedFormat('d M Y') ?? 'sekarang' }}
                                        @if($j->no_sk) · SK {{ $j->no_sk }} @endif
                                        @if($j->status) · {{ $j->status }} @endif
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">Belum ada data.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
