<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Kepegawaian\DokumenController;
use App\Http\Controllers\Kepegawaian\KeluargaController;
use App\Http\Controllers\Kepegawaian\PegawaiController;
use App\Http\Controllers\Kepegawaian\PenempatanController;
use App\Http\Controllers\Kepegawaian\RiwayatPangkatGolonganController;
use App\Http\Controllers\Kepegawaian\RiwayatPendidikanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('auth.login');
})->name('login');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::post('/logout', [GoogleAuthController::class, 'logout'])->name('logout');

Route::get('/auth/belum-terdaftar', function () {
    return view('auth.belum-terdaftar', ['email' => request('email')]);
})->name('auth.belum-terdaftar');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('kepegawaian')->name('kepegawaian.')->group(function () {
        Route::get('/', [PegawaiController::class, 'index'])->name('index');

        Route::middleware('role:admin-kepegawaian')->group(function () {
            Route::get('/baru', [PegawaiController::class, 'create'])->name('create');
            Route::post('/', [PegawaiController::class, 'store'])->name('store');
            Route::get('/{pegawai}/ubah', [PegawaiController::class, 'edit'])->name('edit');
            Route::put('/{pegawai}', [PegawaiController::class, 'update'])->name('update');

            Route::post('/{pegawai}/penempatan', [PenempatanController::class, 'store'])->name('penempatan.store');
            Route::put('/penempatan/{penempatan}', [PenempatanController::class, 'update'])->name('penempatan.update');
            Route::delete('/penempatan/{penempatan}', [PenempatanController::class, 'destroy'])->name('penempatan.destroy');

            Route::post('/{pegawai}/riwayat-pendidikan', [RiwayatPendidikanController::class, 'store'])->name('riwayat-pendidikan.store');
            Route::delete('/riwayat-pendidikan/{riwayatPendidikan}', [RiwayatPendidikanController::class, 'destroy'])->name('riwayat-pendidikan.destroy');

            Route::post('/{pegawai}/riwayat-pangkat', [RiwayatPangkatGolonganController::class, 'store'])->name('riwayat-pangkat.store');
            Route::delete('/riwayat-pangkat/{riwayatPangkatGolongan}', [RiwayatPangkatGolonganController::class, 'destroy'])->name('riwayat-pangkat.destroy');

            Route::post('/{pegawai}/keluarga', [KeluargaController::class, 'store'])->name('keluarga.store');
            Route::delete('/keluarga/{keluarga}', [KeluargaController::class, 'destroy'])->name('keluarga.destroy');

            Route::post('/{pegawai}/dokumen', [DokumenController::class, 'store'])->name('dokumen.store');
            Route::get('/dokumen/{dokumen}/unduh', [DokumenController::class, 'download'])->name('dokumen.download');
            Route::delete('/dokumen/{dokumen}', [DokumenController::class, 'destroy'])->name('dokumen.destroy');
        });

        Route::get('/{pegawai}', [PegawaiController::class, 'show'])->name('show');
    });
});
