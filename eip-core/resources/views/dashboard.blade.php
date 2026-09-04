@extends('layouts.app')

@section('title', 'Ringkasan — EIP Fakultas MIPA UNS')

@section('content')

    <!-- Sapaan -->
    <div class="flex items-center justify-between px-1">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Halo, {{ explode(' ', $user->name)[0] }} 👋</h1>
            <p class="text-xs text-slate-400 font-medium mt-0.5">
                {{ $user->pegawai?->nama_lengkap ? 'Terhubung ke pegawai: '.$user->pegawai->nama_lengkap : 'Akun belum terhubung ke data pegawai' }}
            </p>
        </div>
        <div class="flex gap-1.5">
            @forelse ($user->roles as $role)
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-indigo-600 bg-indigo-50 border border-indigo-100/80 px-2.5 py-1 rounded-full">{{ $role->kode }}</span>
            @empty
                <span class="text-[10px] font-semibold text-slate-400 bg-slate-100 px-2.5 py-1 rounded-full">tanpa peran</span>
            @endforelse
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 1. KPI CARDS                                    -->
    <!-- ============================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($metrics as $m)
            @php
                $badgeCls = match ($m['badge_color']) {
                    'emerald' => 'text-emerald-600 bg-emerald-50 border-emerald-100/80',
                    'indigo' => 'text-indigo-600 bg-indigo-50 border-indigo-100/80',
                    'amber' => 'text-amber-600 bg-amber-50 border-amber-100/80',
                    'rose' => 'text-rose-600 bg-rose-50 border-rose-100/80',
                    default => 'text-slate-600 bg-slate-50 border-slate-100/80',
                };
            @endphp
            <div class="apple-glass-card apple-interactive-card p-5 rounded-3xl flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                        <span class="font-semibold text-slate-500">{{ $m['label'] }}</span>
                        <span class="{{ $badgeCls }} border px-2 py-0.5 rounded-full font-bold text-[10px] font-mono-num">{{ $m['badge'] }}</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <p class="text-3xl font-extrabold tracking-tight text-slate-900 font-mono-num">{{ $m['value'] }}</p>
                        @if (isset($m['unit']))
                            <span class="text-sm font-semibold text-slate-400">{{ $m['unit'] }}</span>
                        @endif
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400 font-medium">
                    <span>{{ $m['foot_left'] }}</span>
                    <span class="font-mono-num text-slate-600 font-semibold">{{ $m['foot_right'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- ============================================== -->
    <!-- 2. CHART + KELENGKAPAN DATA                     -->
    <!-- ============================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        <div class="lg:col-span-8 apple-glass-card p-6 rounded-3xl flex flex-col justify-between">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Pegawai per Unit Kerja</h2>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">10 unit dengan jumlah pegawai terbanyak (posisi utama)</p>
                </div>
                <div class="flex gap-1 bg-[#F4F4F6] p-1 rounded-2xl text-xs font-semibold self-start sm:self-auto border border-slate-200/50">
                    <button data-chart-filter="Semua" class="px-3 py-1 rounded-xl bg-white text-slate-900 shadow-xs font-bold transition-all">Semua</button>
                    <button data-chart-filter="Dosen" class="px-3 py-1 rounded-xl text-slate-500 hover:text-slate-900 transition-all">Dosen</button>
                    <button data-chart-filter="Tendik" class="px-3 py-1 rounded-xl text-slate-500 hover:text-slate-900 transition-all">Tendik</button>
                </div>
            </div>

            <div class="h-64 w-full relative">
                <canvas id="unitChart"></canvas>
                <script type="application/json" id="unit-chart-data">{!! json_encode($chartDatasets) !!}</script>
            </div>
        </div>

        <div class="lg:col-span-4 apple-glass-card p-6 rounded-3xl flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-bold text-slate-900">Kelengkapan Data</h2>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-indigo-600 bg-indigo-50 border border-indigo-100/80 px-2 py-0.5 rounded-full font-mono-num">{{ $totalPegawai }} pegawai</span>
                </div>

                <div class="space-y-4">
                    @foreach ($completeness as $item)
                        @php $pct = $totalPegawai > 0 ? round($item['count'] / $totalPegawai * 100) : 0; @endphp
                        <div class="text-xs">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-semibold text-slate-700">{{ $item['label'] }}</span>
                                <span class="font-mono-num font-bold {{ $item['count'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}">{{ $item['count'] }}</span>
                            </div>
                            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                <div class="{{ $item['count'] > 0 ? 'bg-amber-500' : 'bg-emerald-500' }} h-full rounded-full" style="width: {{ max($pct, 2) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <p class="text-[11px] text-slate-400 mt-4 pt-4 border-t border-slate-100">
                Data ini menyusul saat sumbernya tersedia (lihat <span class="font-mono-num">docs/04</span>) — bukan tanda kesalahan import.
            </p>
        </div>

    </div>

    <!-- ============================================== -->
    <!-- 3. TABEL UNIT KERJA                             -->
    <!-- ============================================== -->
    <div class="apple-glass-card rounded-3xl overflow-hidden mb-4 border border-white/90">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-bold text-slate-900">Unit Kerja</h2>
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-mono-num font-bold">{{ $units->count() }} unit</span>
                </div>
                <p class="text-xs text-slate-400 font-medium mt-0.5">Fakultas, prodi, bagian/subbagian, dan rektorat (jabatan rangkap)</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600" id="unitTable">
                <thead class="bg-slate-50/60 text-slate-400 uppercase text-[10px] tracking-wider font-bold border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Unit Kerja</th>
                        <th class="px-6 py-3.5">Jenis</th>
                        <th class="px-6 py-3.5">Kepala Unit</th>
                        <th class="px-6 py-3.5">Jumlah Pegawai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @foreach ($units as $unit)
                        @php
                            $jenisBadge = match ($unit->jenis_unit->value) {
                                'fakultas' => 'bg-indigo-50 text-indigo-600 border-indigo-100/60',
                                'prodi' => 'bg-emerald-50 text-emerald-600 border-emerald-100/60',
                                'biro' => 'bg-rose-50 text-rose-600 border-rose-100/60',
                                default => 'bg-amber-50 text-amber-600 border-amber-100/60',
                            };
                            $barPct = $maxPegawaiPerUnit > 0 ? round($unit->total_pegawai / $maxPegawaiPerUnit * 100) : 0;
                        @endphp
                        <tr class="hover:bg-white/80 transition-all">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900">{{ $unit->nama }}</p>
                                <p class="text-[10px] text-slate-400 font-mono-num">{{ $unit->kode }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full {{ $jenisBadge }} border text-[10px] font-bold capitalize">{{ $unit->jenis_unit->value }}</span>
                            </td>
                            <td class="px-6 py-4">{{ $unit->kepala?->nama_lengkap ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] font-mono-num text-slate-700 font-bold w-6">{{ $unit->total_pegawai }}</span>
                                    <div class="w-24 bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                        <div class="bg-indigo-500 h-full rounded-full" style="width: {{ $barPct }}%"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400 font-medium">
            <span>Menampilkan {{ $units->count() }} unit kerja</span>
        </div>
    </div>

@endsection
