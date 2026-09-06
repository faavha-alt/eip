<aside class="w-64 p-5 flex flex-col justify-between hidden lg:flex select-none shrink-0">
    <div class="space-y-6">

        <!-- Brand -->
        <div class="flex items-center gap-3 px-2">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-slate-900 via-slate-800 to-indigo-950 flex items-center justify-center text-white shadow-md shadow-slate-900/15">
                <svg class="w-5 h-5 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l9 4.5-9 4.5-9-4.5L12 3z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9 4.5 9-4.5M3 16.5l9 4.5 9-4.5"/>
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-1.5">
                    <span class="text-sm font-bold tracking-tight text-slate-900">EIP</span>
                    <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-600 border border-indigo-100/80">Core</span>
                </div>
                <p class="text-[11px] text-slate-400 font-medium">Fakultas MIPA UNS</p>
            </div>
        </div>

        <!-- Ringkasan pegawai aktif -->
        <div class="p-3 rounded-2xl apple-glass-card border border-white/80">
            <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500 mb-1.5">
                <span class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Pegawai Aktif
                </span>
                <span class="font-mono-num text-slate-900 font-bold">{{ $sidebarStats['aktif_pct'] ?? 0 }}%</span>
            </div>
            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: {{ $sidebarStats['aktif_pct'] ?? 0 }}%"></div>
            </div>
        </div>

        <!-- Navigasi -->
        <div class="space-y-1">
            <p class="px-4 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Master Data</p>

            <a href="{{ route('dashboard') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white shadow-md shadow-slate-900/10' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60' }} font-semibold text-xs transition-all">
                <span class="flex items-center gap-3">
                    <svg class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Ringkasan
                </span>
                @if (request()->routeIs('dashboard'))
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                @endif
            </a>

            <a href="{{ route('kepegawaian.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl {{ request()->routeIs('kepegawaian.*') ? 'bg-slate-900 text-white shadow-md shadow-slate-900/10' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60' }} font-medium text-xs transition-all">
                <svg class="w-4 h-4 {{ request()->routeIs('kepegawaian.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 4v-2a4 4 0 00-3-3.87m-9.13-9A4 4 0 105 5a4 4 0 002.87 3.87"/></svg>
                Direktori Pegawai
            </a>

            @if (auth()->user()->roles->contains('kode', 'admin'))
                <a href="{{ route('master.unit-kerja.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl {{ request()->routeIs('master.unit-kerja.*') ? 'bg-slate-900 text-white shadow-md shadow-slate-900/10' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60' }} font-medium text-xs transition-all">
                    <svg class="w-4 h-4 {{ request()->routeIs('master.unit-kerja.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M19 21H5m0 0H3m8-16h.01M11 12h.01M11 16h.01M15 8h.01M15 12h.01M15 16h.01"/></svg>
                    Unit Kerja
                </a>
                <a href="{{ route('master.jabatan.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl {{ request()->routeIs('master.jabatan.*') ? 'bg-slate-900 text-white shadow-md shadow-slate-900/10' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60' }} font-medium text-xs transition-all">
                    <svg class="w-4 h-4 {{ request()->routeIs('master.jabatan.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                    Jabatan
                </a>
                <a href="{{ route('master.organisasi.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl {{ request()->routeIs('master.organisasi.*') ? 'bg-slate-900 text-white shadow-md shadow-slate-900/10' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60' }} font-medium text-xs transition-all">
                    <svg class="w-4 h-4 {{ request()->routeIs('master.organisasi.*') ? 'text-indigo-400' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M3 7v14m18-14v14M6 21V4a1 1 0 011-1h10a1 1 0 011 1v17M9 7h1m-1 4h1m4-4h1m-1 4h1"/></svg>
                    Organisasi
                </a>
            @endif
        </div>

        <div class="space-y-1">
            <p class="px-4 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Pengaturan</p>
            <a href="#" class="flex items-center justify-between px-3.5 py-2.5 rounded-2xl text-slate-600 hover:text-slate-900 hover:bg-white/60 font-medium text-xs transition-all">
                <span class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Kelola Peran
                </span>
                <span class="text-[10px] font-mono-num font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-500">CLI</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-slate-600 hover:text-slate-900 hover:bg-white/60 font-medium text-xs transition-all">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 8l-4 4 4 4"/></svg>
                Integrasi API
            </a>
        </div>

    </div>

    <!-- Profil user -->
    <div class="p-2.5 apple-glass-card rounded-3xl flex items-center justify-between border border-white/80">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="relative shrink-0">
                <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-slate-200 to-slate-100 flex items-center justify-center font-bold text-xs text-slate-800 border border-slate-300/60 shadow-sm">
                    {{ collect(explode(' ', auth()->user()->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                </div>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 absolute -bottom-0.5 -right-0.5 ring-2 ring-white"></span>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                <p class="text-[10px] font-medium text-slate-400 font-mono-num truncate">{{ auth()->user()->roles->pluck('kode')->join(', ') ?: 'tanpa peran' }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-slate-400 hover:text-slate-700 p-1.5 rounded-xl hover:bg-slate-100/80 transition" title="Keluar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </button>
        </form>
    </div>
</aside>
