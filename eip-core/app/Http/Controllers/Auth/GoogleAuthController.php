<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * Login Google Workspace via OIDC (docs/03-autentikasi-sso.md). EIP Core =
 * OIDC client sendiri; email = kunci relasi ke `pegawai`. Login terbuka utk
 * domain kampus, TAPI hanya diteruskan masuk kalau emailnya sudah terdaftar
 * sbg pegawai — mencegah akun domain kampus sembarang masuk tanpa data
 * pegawai.
 */
class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('Login Google gagal', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            return redirect()->route('login')->with('error', 'Login Google gagal, silakan coba lagi.');
        }

        $email = Str::lower($googleUser->getEmail());
        $domain = Str::after($email, '@');
        $allowedDomains = array_map(Str::lower(...), config('eip.allowed_email_domains', []));

        if ($allowedDomains !== [] && ! in_array($domain, $allowedDomains, true)) {
            return redirect()->route('login')->with('error', "Akun {$email} bukan domain kampus yang diizinkan.");
        }

        $user = $this->findOrCreateUser($googleUser, $email);

        if (! $user->is_active) {
            return redirect()->route('login')->with('error', 'Akun Anda dinonaktifkan. Hubungi admin EIP.');
        }

        if ($user->pegawai_id === null) {
            // Login Google sukses tapi belum terdaftar sbg pegawai (docs/03):
            // JANGAN diloloskan masuk — cegah akun domain kampus sembarang masuk.
            return redirect()->route('auth.belum-terdaftar', ['email' => $email]);
        }

        Auth::login($user, remember: true);
        $user->forceFill(['last_login_at' => now()])->save();
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function findOrCreateUser(SocialiteUser $googleUser, string $email): User
    {
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $email)->first();

        $pegawaiId = Pegawai::where('email', $email)->value('id');

        if ($user) {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'name' => $googleUser->getName() ?: $user->name,
                'avatar' => $googleUser->getAvatar(),
                'pegawai_id' => $pegawaiId,
            ])->save();

            return $user;
        }

        return User::create([
            'name' => $googleUser->getName() ?: $email,
            'email' => $email,
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'pegawai_id' => $pegawaiId,
            'password' => null,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }
}
