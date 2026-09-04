<?php

namespace Tests\Feature;

use App\Console\Commands\AssignRole;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\ServiceClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class AuthDanRbacTest extends TestCase
{
    use RefreshDatabase;

    private function mockGoogleUser(string $email, string $googleId = 'g-123'): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($googleId);
        $socialiteUser->shouldReceive('getName')->andReturn('Contoh Pegawai');
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://avatar.example/x.png');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_login_google_domain_luar_kampus_ditolak(): void
    {
        $this->mockGoogleUser('siapa@gmail.com');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'siapa@gmail.com']);
    }

    public function test_login_google_domain_kampus_tapi_belum_terdaftar_pegawai_tidak_diloloskan(): void
    {
        $this->mockGoogleUser('belum.terdaftar@mipa.uns.ac.id');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('auth.belum-terdaftar', ['email' => 'belum.terdaftar@mipa.uns.ac.id']));
        $this->assertGuest();
        $this->assertDatabaseHas('users', ['email' => 'belum.terdaftar@mipa.uns.ac.id', 'pegawai_id' => null]);
    }

    public function test_login_google_domain_kampus_dan_terdaftar_pegawai_berhasil_masuk(): void
    {
        $pegawai = Pegawai::factory()->create(['email' => 'dosen@mipa.uns.ac.id']);
        $this->mockGoogleUser('dosen@mipa.uns.ac.id');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $user = User::where('email', 'dosen@mipa.uns.ac.id')->firstOrFail();
        $this->assertSame($pegawai->id, $user->pegawai_id);
        $this->assertNotNull($user->last_login_at);
    }

    public function test_domain_staff_uns_ac_id_juga_diizinkan_bukan_cuma_mipa(): void
    {
        // Mayoritas data pegawai nyata FMIPA pakai @staff.uns.ac.id (181/189),
        // bukan @mipa.uns.ac.id (cuma 2/189) — regresi bug yg sempat menolak
        // hampir semua pegawai asli login.
        $pegawai = Pegawai::factory()->create(['email' => 'dosen@staff.uns.ac.id']);
        $this->mockGoogleUser('dosen@staff.uns.ac.id');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertSame($pegawai->id, User::where('email', 'dosen@staff.uns.ac.id')->value('pegawai_id'));
    }

    public function test_akun_nonaktif_ditolak_meski_terdaftar_pegawai(): void
    {
        Pegawai::factory()->create(['email' => 'nonaktif@mipa.uns.ac.id']);
        User::factory()->create(['email' => 'nonaktif@mipa.uns.ac.id', 'is_active' => false]);
        $this->mockGoogleUser('nonaktif@mipa.uns.ac.id');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_dashboard_wajib_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_role_manual_via_command_dan_helper_hasrole(): void
    {
        $user = User::factory()->create();

        $this->artisan(AssignRole::class, ['email' => $user->email, 'role' => 'admin-gaji', '--create-role' => true])
            ->assertExitCode(0);

        $this->assertTrue($user->fresh()->hasRole('admin-gaji'));
        $this->assertFalse($user->fresh()->hasRole('approver-pengadaan'));
    }

    public function test_api_roles_ditolak_tanpa_token(): void
    {
        $this->getJson('/api/v1/users/roles?email=x@mipa.uns.ac.id')->assertUnauthorized();
    }

    public function test_api_roles_dgn_token_service_client_mengembalikan_peran(): void
    {
        $serviceClient = ServiceClient::factory()->create();
        $role = Role::factory()->create(['kode' => 'operator-aset']);
        $user = User::factory()->create(['email' => 'operator@mipa.uns.ac.id']);
        $user->roles()->attach($role);

        Sanctum::actingAs($serviceClient, ['*']);

        $response = $this->getJson('/api/v1/users/roles?email=operator@mipa.uns.ac.id');

        $response->assertOk()->assertJson([
            'email' => 'operator@mipa.uns.ac.id',
            'roles' => ['operator-aset'],
        ]);
    }
}
