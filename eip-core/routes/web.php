<?php

use App\Http\Controllers\Auth\GoogleAuthController;
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
    Route::get('/dashboard', function () {
        return view('dashboard', ['user' => auth()->user()->load(['pegawai', 'roles'])]);
    })->name('dashboard');
});
