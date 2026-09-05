<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Organisasi;
use App\Models\Pegawai;
use App\Models\Penempatan;
use App\Models\ServiceClient;
use App\Models\UnitKerja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    private function actingAsClient(array $abilities = ['master:read']): ServiceClient
    {
        $client = ServiceClient::factory()->create();
        Sanctum::actingAs($client, $abilities);

        return $client;
    }

    public function test_endpoint_master_ditolak_tanpa_token(): void
    {
        $this->getJson('/api/v1/pegawai')->assertUnauthorized();
        $this->getJson('/api/v1/unit-kerja')->assertUnauthorized();
        $this->getJson('/api/v1/jabatan')->assertUnauthorized();
        $this->getJson('/api/v1/organisasi')->assertUnauthorized();
    }

    public function test_endpoint_master_ditolak_tanpa_ability_master_read(): void
    {
        $this->actingAsClient(['pegawai:write']); // ability lain, bukan master:read

        $this->getJson('/api/v1/pegawai')->assertForbidden();
    }

    public function test_index_pegawai_mengembalikan_data_terpaginasi(): void
    {
        $this->actingAsClient();
        Pegawai::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/pegawai');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure(['data' => [['id', 'nama_lengkap', 'email', 'is_active']], 'links', 'meta']);
    }

    public function test_index_pegawai_filter_updated_since(): void
    {
        $this->actingAsClient();
        $lama = Pegawai::factory()->create();
        $lama->forceFill(['updated_at' => now()->subYear()])->saveQuietly();
        Pegawai::factory()->create(); // baru saja dibuat, updated_at = now

        $response = $this->getJson('/api/v1/pegawai?updated_since='.now()->subDay()->toDateString());

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_show_pegawai_menyertakan_penempatan_utama(): void
    {
        $this->actingAsClient();
        $pegawai = Pegawai::factory()->create();
        $unit = UnitKerja::factory()->create(['nama' => 'Prodi Ilmu Komputer']);
        $jabatan = Jabatan::factory()->create(['nama' => 'Dosen']);
        Penempatan::factory()->create([
            'pegawai_id' => $pegawai->id, 'unit_kerja_id' => $unit->id,
            'jabatan_id' => $jabatan->id, 'is_posisi_utama' => true,
        ]);

        $response = $this->getJson("/api/v1/pegawai/{$pegawai->id}");

        $response->assertOk();
        $response->assertJsonPath('data.penempatan_utama.unit_kerja_id', $unit->id);
        // Regresi: nama unit/jabatan harus ikut termuat (dulu null krn penempatan.unitKerja/
        // penempatan.jabatan belum di-eager-load — konsumen sprt wa-blast butuh nama ini).
        $response->assertJsonPath('data.penempatan_utama.unit_kerja_nama', 'Prodi Ilmu Komputer');
        $response->assertJsonPath('data.penempatan_utama.jabatan_nama', 'Dosen');
    }

    public function test_index_unit_kerja_jabatan_organisasi_smoke(): void
    {
        $this->actingAsClient();
        $organisasi = Organisasi::factory()->create();
        UnitKerja::factory()->create(['organisasi_id' => $organisasi->id]);
        Jabatan::factory()->create();

        $this->getJson('/api/v1/unit-kerja')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/jabatan')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/organisasi')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_store_pegawai_ditolak_tanpa_ability_pegawai_write(): void
    {
        $this->actingAsClient(['master:read']);

        $this->postJson('/api/v1/pegawai', ['nama_lengkap' => 'Contoh', 'tanggal_masuk' => '2020-01-01'])
            ->assertForbidden();
    }

    public function test_store_pegawai_berhasil_dgn_ability_pegawai_write(): void
    {
        $this->actingAsClient(['pegawai:write']);

        $response = $this->postJson('/api/v1/pegawai', [
            'nama_lengkap' => 'Pegawai Baru',
            'email' => 'baru@staff.uns.ac.id',
            'tanggal_masuk' => '2020-01-01',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('pegawai', ['nama_lengkap' => 'Pegawai Baru', 'email' => 'baru@staff.uns.ac.id']);
    }

    public function test_update_pegawai_berhasil_dgn_ability_pegawai_write(): void
    {
        $this->actingAsClient(['pegawai:write']);
        $pegawai = Pegawai::factory()->create(['nama_lengkap' => 'Nama Lama']);

        $response = $this->putJson("/api/v1/pegawai/{$pegawai->id}", ['nama_lengkap' => 'Nama Baru']);

        $response->assertOk();
        $this->assertSame('Nama Baru', $pegawai->fresh()->nama_lengkap);
    }

    public function test_store_penempatan_berhasil_dgn_ability_pegawai_write(): void
    {
        $this->actingAsClient(['pegawai:write']);
        $pegawai = Pegawai::factory()->create();
        $unit = UnitKerja::factory()->create();
        $jabatan = Jabatan::factory()->create();

        $response = $this->postJson('/api/v1/penempatan', [
            'pegawai_id' => $pegawai->id,
            'unit_kerja_id' => $unit->id,
            'jabatan_id' => $jabatan->id,
            'tgl_mulai' => '2024-01-01',
            'is_posisi_utama' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('penempatan', ['pegawai_id' => $pegawai->id, 'unit_kerja_id' => $unit->id]);
    }
}
