<?php

namespace Tests\Feature;

use App\Enums\JenisUnitKerja;
use App\Enums\StatusKepegawaian;
use App\Models\Jabatan;
use App\Models\Organisasi;
use App\Models\Pegawai;
use App\Models\Penempatan;
use App\Models\UnitKerja;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_kerja_tree_dan_kepala_terhubung_ke_pegawai(): void
    {
        $organisasi = Organisasi::factory()->create();
        $fakultas = UnitKerja::factory()->create([
            'organisasi_id' => $organisasi->id,
            'jenis_unit' => JenisUnitKerja::Fakultas,
        ]);
        $prodi = UnitKerja::factory()->create([
            'organisasi_id' => $organisasi->id,
            'parent_id' => $fakultas->id,
            'jenis_unit' => JenisUnitKerja::Prodi,
        ]);
        $kaprodi = Pegawai::factory()->create();
        $prodi->update(['kepala_id' => $kaprodi->id]);

        $this->assertTrue($fakultas->children->contains($prodi));
        $this->assertTrue($prodi->parent->is($fakultas));
        $this->assertTrue($prodi->kepala->is($kaprodi));
        $this->assertInstanceOf(JenisUnitKerja::class, $prodi->jenis_unit);
    }

    public function test_penempatan_menghubungkan_pegawai_unit_kerja_dan_jabatan(): void
    {
        $pegawai = Pegawai::factory()->create();
        $unitKerja = UnitKerja::factory()->create();
        $jabatan = Jabatan::factory()->create();

        $penempatan = Penempatan::factory()->create([
            'pegawai_id' => $pegawai->id,
            'unit_kerja_id' => $unitKerja->id,
            'jabatan_id' => $jabatan->id,
        ]);

        $this->assertTrue($pegawai->penempatan->contains($penempatan));
        $this->assertTrue($penempatan->unitKerja->is($unitKerja));
        $this->assertTrue($penempatan->jabatan->is($jabatan));
    }

    public function test_kolom_identitas_pegawai_unik(): void
    {
        $pegawai = Pegawai::factory()->create([
            'nip' => '198501012010011001',
            'email' => 'unik@mipa.uns.ac.id',
        ]);

        $this->assertInstanceOf(StatusKepegawaian::class, $pegawai->status_kepegawaian);

        $this->expectException(QueryException::class);
        Pegawai::factory()->create(['nip' => '198501012010011001']);
    }

    public function test_kode_organisasi_unik(): void
    {
        Organisasi::factory()->create(['kode' => 'UNS']);

        $this->expectException(QueryException::class);
        Organisasi::factory()->create(['kode' => 'UNS']);
    }

    public function test_organisasi_tidak_bisa_dihapus_permanen_selama_masih_punya_unit_kerja(): void
    {
        $organisasi = Organisasi::factory()->create();
        UnitKerja::factory()->create(['organisasi_id' => $organisasi->id]);

        // Soft delete boleh (baris masih ada), tapi forceDelete harus dicegah FK restrict.
        $organisasi->delete();
        $this->assertSoftDeleted($organisasi);

        $this->expectException(QueryException::class);
        $organisasi->forceDelete();
    }
}
