@if (session('status'))
    <div class="apple-glass-card rounded-2xl px-4 py-3 text-xs font-semibold text-emerald-700 border border-emerald-100">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="apple-glass-card rounded-2xl px-4 py-3 text-xs font-semibold text-rose-700 border border-rose-100 space-y-1">
        @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
@endif
