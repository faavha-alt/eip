<?php

use App\Http\Controllers\Api\V1\JabatanController;
use App\Http\Controllers\Api\V1\OrganisasiController;
use App\Http\Controllers\Api\V1\PegawaiController;
use App\Http\Controllers\Api\V1\PenempatanController;
use App\Http\Controllers\Api\V1\UnitKerjaController;
use App\Http\Controllers\Api\V1\UserRoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — dikonsumsi app domain baru (kepegawaian, dst) & sistem lama
|--------------------------------------------------------------------------
|
| Service-to-service via token Sanctum (ServiceClient), bukan sesi login
| manusia. Ability "master:read" utk semua konsumen baca; "pegawai:write"
| HANYA utk app kepegawaian (docs/04 §6: satu jalur tulis data pegawai).
|
*/
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/users/roles', [UserRoleController::class, 'show']);

    Route::middleware('abilities:master:read')->group(function () {
        Route::get('/pegawai', [PegawaiController::class, 'index']);
        Route::get('/pegawai/{pegawai}', [PegawaiController::class, 'show']);
        Route::get('/unit-kerja', [UnitKerjaController::class, 'index']);
        Route::get('/unit-kerja/{unitKerja}', [UnitKerjaController::class, 'show']);
        Route::get('/jabatan', [JabatanController::class, 'index']);
        Route::get('/jabatan/{jabatan}', [JabatanController::class, 'show']);
        Route::get('/organisasi', [OrganisasiController::class, 'index']);
        Route::get('/organisasi/{organisasi}', [OrganisasiController::class, 'show']);
    });

    Route::middleware('abilities:pegawai:write')->group(function () {
        Route::post('/pegawai', [PegawaiController::class, 'store']);
        Route::put('/pegawai/{pegawai}', [PegawaiController::class, 'update']);
        Route::post('/penempatan', [PenempatanController::class, 'store']);
        Route::put('/penempatan/{penempatan}', [PenempatanController::class, 'update']);
    });
});
