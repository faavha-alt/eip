<header class="h-16 apple-glass-card rounded-none lg:rounded-3xl px-6 flex items-center justify-between gap-4 select-none mb-5 shrink-0">
    <div class="flex items-center gap-3 flex-1 max-w-lg">
        <div class="relative w-full">
            <input type="text" id="searchInput" placeholder="Cari unit kerja atau prodi... (tekan /)" class="w-full bg-[#F4F4F6]/80 text-xs text-slate-900 placeholder-slate-400 pl-10 pr-16 py-2.5 rounded-2xl border border-transparent focus:border-slate-200 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/5 transition-all">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <span class="absolute right-3 top-2.5 text-[10px] font-mono-num font-semibold text-slate-400 bg-white px-1.5 py-0.5 rounded-lg border border-slate-200 shadow-2xs">/</span>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-2xl bg-slate-50 border border-slate-200/60 text-xs font-medium text-slate-600">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span class="text-slate-400 text-[11px]">Sumber:</span>
            <span class="font-bold text-slate-800">eip.mipa.uns.ac.id</span>
        </div>

        <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-2xl bg-white border border-slate-200/60 text-xs font-medium text-slate-500">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Diperbarui {{ now()->translatedFormat('d M Y, H:i') }}</span>
        </div>
    </div>
</header>
