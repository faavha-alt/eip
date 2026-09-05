@extends('layouts.app')

@section('title', 'Ubah '.$pegawai->nama_lengkap.' — EIP')

@section('content')
    <div class="flex items-center justify-between px-1">
        <div>
            <a href="{{ route('kepegawaian.show', $pegawai) }}" class="text-[11px] font-semibold text-slate-400 hover:text-slate-700">&larr; Kembali ke profil</a>
            <h1 class="text-lg font-bold text-slate-900 mt-1">Ubah Data: {{ $pegawai->nama_lengkap }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ route('kepegawaian.update', $pegawai) }}" class="space-y-5 pb-8">
        @csrf
        @method('PUT')
        @include('kepegawaian._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('kepegawaian.show', $pegawai) }}" class="px-4 py-2.5 rounded-2xl text-xs font-semibold text-slate-500 hover:bg-white/60 transition">Batal</a>
            <button type="submit" class="px-5 py-2.5 rounded-2xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 shadow-md shadow-slate-900/10 transition">Simpan Perubahan</button>
        </div>
    </form>
@endsection
