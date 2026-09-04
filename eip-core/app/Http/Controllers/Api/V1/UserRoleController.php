<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dipanggil app lain (service token Sanctum) utk resolve peran user by
 * email — RBAC terpusat di EIP Core (docs/03 §4): "app lain baca peran
 * user via API / klaim token EIP Core", bukan kelola role sendiri-sendiri.
 */
class UserRoleController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::with(['roles' => fn ($q) => $q->where('is_active', true), 'pegawai'])
            ->where('email', $data['email'])
            ->first();

        if (! $user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        return response()->json([
            'email' => $user->email,
            'nama' => $user->name,
            'is_active' => $user->is_active,
            'pegawai_id' => $user->pegawai_id,
            'roles' => $user->roles->pluck('kode')->values(),
        ]);
    }
}
