<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `role:admin-kepegawaian` — lolos kalau user py salah satu role yg
 * disebut, ATAU role "admin" (super-admin selalu lolos semua gate role).
 */
class EnsureHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $userRoles = $user?->roles->pluck('kode') ?? collect();

        abort_unless(
            $userRoles->contains('admin') || $userRoles->intersect($roles)->isNotEmpty(),
            403,
            'Anda tidak punya peran yang diizinkan utk aksi ini.',
        );

        return $next($request);
    }
}
