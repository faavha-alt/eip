<?php

use App\Http\Controllers\Api\V1\UserRoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — dikonsumsi app domain baru (kepegawaian, dst) & sistem lama
|--------------------------------------------------------------------------
|
| Service-to-service via token Sanctum (ServiceClient), bukan sesi login
| manusia. Master pegawai/unit_kerja/jabatan/organisasi menyusul di
| Langkah 4 (docs/04 §6).
|
*/
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/users/roles', [UserRoleController::class, 'show']);
});
