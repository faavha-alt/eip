@extends('layouts.app')

@section('title', 'Tambah Pegawai — EIP')

@section('content')
    <div class="flex items-center justify-between px-1">
        <div>
            <a href="{{ route('kepegawaian.index') }}" class="text-[11px] font-semibold text-slate-400 hover:text-slate-700">&larr; Kembali ke direktori</a>
            <h1 class="text-lg font-bold text-slate-900 mt-1">Tambah Pegawai</h1>
        </div>
    </div>

    <form method="POST" action="{{ route('kepegawaian.store') }}" class="space-y-5 pb-8">
        @csrf
        @include('kepegawaian._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('kepegawaian.index') }}" class="px-4 py-2.5 rounded-2xl text-xs font-semibold text-slate-500 hover:bg-white/60 transition">Batal</a>
            <button type="submit" class="px-5 py-2.5 rounded-2xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 shadow-md shadow-slate-900/10 transition">Simpan Pegawai</button>
        </div>
    </form>
@endsection
