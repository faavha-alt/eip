<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Organisasi;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterOrganisasiControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['kode' => 'admin'], ['nama' => 'admin', 'is_active' => true]));

        return $user;
    }

    public function test_hanya_admin_yang_boleh_akses(): void
    {
        $this->get(route('master.unit-kerja.index'))->assertRedirect(route('login'));

        $biasa = User::factory()->create();
        $this->actingAs($biasa)->get(route('master.unit-kerja.index'))->assertForbidden();

        $this->actingAs($this->admin())->get(route('master.unit-kerja.index'))->assertOk();
    }

    public function test_semua_halaman_master_render(): void
    {
        $admin = $this->admin();
        Organisasi::factory()->create();

        foreach (['unit-kerja', 'jabatan', 'organisasi'] as $m) {
            $this->actingAs($admin)->get(route("master.{$m}.index"))->assertOk();
            $this->actingAs($admin)->get(route("master.{$m}.create"))->assertOk();
        }
    }

    public function test_tambah_unit_kerja_auto_kode(): void
    {
        $org = Organisasi::factory()->create();

        $this->actingAs($this->admin())->post(route('master.unit-kerja.store'), [
            'nama' => 'S-1 Farmasi', 'jenis_unit' => 'prodi', 'organisasi_id' => $org->id, 'is_active' => '1',
        ])->assertRedirect(route('master.unit-kerja.index'));

        $this->assertDatabaseHas('unit_kerja', ['nama' => 'S-1 Farmasi', 'kode' => 'S_1_FARMASI', 'is_active' => true]);
    }

    public function test_kode_duplikat_ditolak(): void
    {
        $org = Organisasi::factory()->create();
        UnitKerja::factory()->create(['kode' => 'DUP', 'organisasi_id' => $org->id]);

        $this->actingAs($this->admin())->post(route('master.unit-kerja.store'), [
            'nama' => 'Unit Lain', 'kode' => 'DUP', 'jenis_unit' => 'prodi', 'organisasi_id' => $org->id,
        ])->assertSessionHasErrors('kode');
    }

    public function test_toggle_nonaktifkan_unit_kerja(): void
    {
        $unit = UnitKerja::factory()->create(['is_active' => true, 'nama' => 'S-1 Informatika']);

        $this->actingAs($this->admin())->patch(route('master.unit-kerja.aktif', $unit))->assertRedirect();

        $this->assertFalse($unit->fresh()->is_active);

        // aktifkan lagi
        $this->actingAs($this->admin())->patch(route('master.unit-kerja.aktif', $unit));
        $this->assertTrue($unit->fresh()->is_active);
    }

    public function test_filter_status_nonaktif(): void
    {
        UnitKerja::factory()->create(['nama' => 'Unit Aktif', 'is_active' => true]);
        UnitKerja::factory()->create(['nama' => 'Unit Mati', 'is_active' => false]);

        $this->actingAs($this->admin())->get(route('master.unit-kerja.index', ['status' => 'nonaktif']))
            ->assertOk()->assertSee('Unit Mati')->assertDontSee('Unit Aktif');
    }

    public function test_penempatan_ke_unit_nonaktif_ditolak(): void
    {
        $adminKepeg = User::factory()->create();
        $adminKepeg->roles()->attach(Role::firstOrCreate(['kode' => 'admin-kepegawaian'], ['nama' => 'x', 'is_active' => true]));

        $pegawai = Pegawai::factory()->create();
        $unitMati = UnitKerja::factory()->create(['is_active' => false]);
        $jabatan = Jabatan::factory()->create(['is_active' => true]);

        $this->actingAs($adminKepeg)->post(route('kepegawaian.penempatan.store', $pegawai), [
            'unit_kerja_id' => $unitMati->id, 'jabatan_id' => $jabatan->id, 'tgl_mulai' => '2027-01-01',
        ])->assertSessionHasErrors('unit_kerja_id');

        $this->assertDatabaseCount('penempatan', 0);
    }

    public function test_jabatan_dan_organisasi_toggle(): void
    {
        $admin = $this->admin();
        $jabatan = Jabatan::factory()->create(['is_active' => true]);
        $org = Organisasi::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->patch(route('master.jabatan.aktif', $jabatan))->assertRedirect();
        $this->actingAs($admin)->patch(route('master.organisasi.aktif', $org))->assertRedirect();

        $this->assertFalse($jabatan->fresh()->is_active);
        $this->assertFalse($org->fresh()->is_active);
    }
}
