<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Pegawai;
use App\Models\Penempatan;
use App\Models\Role;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KepegawaianModuleTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $kode): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['kode' => $kode], ['nama' => $kode, 'is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_direktori_wajib_login(): void
    {
        $this->get(route('kepegawaian.index'))->assertRedirect(route('login'));
    }

    public function test_direktori_bisa_dilihat_user_tanpa_role_khusus(): void
    {
        $user = User::factory()->create();
        Pegawai::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('kepegawaian.index'));

        $response->assertOk();
    }

    public function test_pencarian_direktori_by_nama(): void
    {
        $user = User::factory()->create();
        Pegawai::factory()->create(['nama_lengkap' => 'Budi Santoso Unik']);
        Pegawai::factory()->create(['nama_lengkap' => 'Orang Lain']);

        $response = $this->actingAs($user)->get(route('kepegawaian.index', ['cari' => 'Unik']));

        $response->assertOk()->assertSee('Budi Santoso Unik')->assertDontSee('Orang Lain');
    }

    public function test_tambah_pegawai_ditolak_tanpa_role_admin_kepegawaian(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('kepegawaian.create'))->assertForbidden();
        $this->actingAs($user)->post(route('kepegawaian.store'), ['nama_lengkap' => 'X', 'tanggal_masuk' => '2024-01-01'])
            ->assertForbidden();
    }

    public function test_form_tambah_dan_ubah_tampil_dgn_role_admin_kepegawaian(): void
    {
        $user = $this->userWithRole('admin-kepegawaian');
        $pegawai = Pegawai::factory()->create();

        $this->actingAs($user)->get(route('kepegawaian.create'))->assertOk()->assertSee('Tambah Pegawai');
        $this->actingAs($user)->get(route('kepegawaian.edit', $pegawai))->assertOk()->assertSee($pegawai->nama_lengkap);
    }

    public function test_tambah_pegawai_berhasil_dgn_role_admin_kepegawaian(): void
    {
        $user = $this->userWithRole('admin-kepegawaian');

        $response = $this->actingAs($user)->post(route('kepegawaian.store'), [
            'nama_lengkap' => 'Pegawai Baru Kepegawaian',
            'email' => 'baru.kepeg@staff.uns.ac.id',
            'tanggal_masuk' => '2024-01-01',
            'is_active' => '1',
        ]);

        $pegawai = Pegawai::where('nama_lengkap', 'Pegawai Baru Kepegawaian')->firstOrFail();
        $response->assertRedirect(route('kepegawaian.show', $pegawai));
        $this->assertTrue($pegawai->is_active);
    }

    public function test_tambah_pegawai_berhasil_dgn_role_admin_super(): void
    {
        $user = $this->userWithRole('admin');

        $response = $this->actingAs($user)->post(route('kepegawaian.store'), [
            'nama_lengkap' => 'Dibuat Admin',
            'tanggal_masuk' => '2024-01-01',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pegawai', ['nama_lengkap' => 'Dibuat Admin']);
    }

    public function test_ubah_data_pegawai(): void
    {
        $user = $this->userWithRole('admin-kepegawaian');
        $pegawai = Pegawai::factory()->create(['nama_lengkap' => 'Nama Lama']);

        $response = $this->actingAs($user)->put(route('kepegawaian.update', $pegawai), [
            'nama_lengkap' => 'Nama Baru',
            'tanggal_masuk' => $pegawai->tanggal_masuk->toDateString(),
        ]);

        $response->assertRedirect(route('kepegawaian.show', $pegawai));
        $this->assertSame('Nama Baru', $pegawai->fresh()->nama_lengkap);
    }

    public function test_tambah_penempatan_utama_menutup_penempatan_utama_lama(): void
    {
        $user = $this->userWithRole('admin-kepegawaian');
        $pegawai = Pegawai::factory()->create();
        $unitLama = UnitKerja::factory()->create();
        $unitBaru = UnitKerja::factory()->create();
        $jabatan = Jabatan::factory()->create();

        $lama = Penempatan::factory()->create([
            'pegawai_id' => $pegawai->id, 'unit_kerja_id' => $unitLama->id,
            'jabatan_id' => $jabatan->id, 'is_posisi_utama' => true, 'tgl_selesai' => null,
        ]);

        $this->actingAs($user)->post(route('kepegawaian.penempatan.store', $pegawai), [
            'unit_kerja_id' => $unitBaru->id,
            'jabatan_id' => $jabatan->id,
            'tgl_mulai' => now()->toDateString(),
            'is_posisi_utama' => '1',
        ])->assertRedirect();

        $lama->refresh();
        $this->assertFalse((bool) $lama->is_posisi_utama);
        $this->assertNotNull($lama->tgl_selesai);

        $baru = $pegawai->penempatan()->where('unit_kerja_id', $unitBaru->id)->firstOrFail();
        $this->assertTrue((bool) $baru->is_posisi_utama);
    }

    public function test_penempatan_store_ditolak_tanpa_role(): void
    {
        $user = User::factory()->create();
        $pegawai = Pegawai::factory()->create();
        $unit = UnitKerja::factory()->create();
        $jabatan = Jabatan::factory()->create();

        $this->actingAs($user)->post(route('kepegawaian.penempatan.store', $pegawai), [
            'unit_kerja_id' => $unit->id,
            'jabatan_id' => $jabatan->id,
            'tgl_mulai' => now()->toDateString(),
        ])->assertForbidden();
    }
}
