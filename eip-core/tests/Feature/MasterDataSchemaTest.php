<?php

namespace Tests\Feature;

use App\Enums\JenisUnitKerja;
use App\Models\GolonganRuang;
use App\Models\Jabatan;
use App\Models\Organisasi;
use App\Models\Pegawai;
use App\Models\Pendidikan;
use App\Models\Penempatan;
use App\Models\StatusKepegawaian;
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

        $this->assertInstanceOf(StatusKepegawaian::class, $pegawai->statusKepegawaian);

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

    public function test_master_status_pendidikan_golongan_terisi_dari_migrasi(): void
    {
        $this->assertSame(4, StatusKepegawaian::count());
        $this->assertSame(6, Pendidikan::count());
        $this->assertSame(11, GolonganRuang::count());
        $this->assertNotNull(StatusKepegawaian::where('kode', 'pns')->first());
        $this->assertNotNull(Pendidikan::where('kode', 's3')->first());
        $this->assertNotNull(GolonganRuang::where('kode', 'IV/e')->first());
    }

    public function test_pegawai_terhubung_ke_master_status_pendidikan_golongan(): void
    {
        $status = StatusKepegawaian::where('kode', 'pns')->firstOrFail();
        $pendidikan = Pendidikan::where('kode', 's3')->firstOrFail();
        $golongan = GolonganRuang::where('kode', 'IV/e')->firstOrFail();

        $pegawai = Pegawai::factory()->create([
            'status_kepegawaian_id' => $status->id,
            'pendidikan_terakhir_id' => $pendidikan->id,
            'golongan_ruang_id' => $golongan->id,
        ]);

        $this->assertTrue($pegawai->statusKepegawaian->is($status));
        $this->assertTrue($pegawai->pendidikanTerakhir->is($pendidikan));
        $this->assertTrue($pegawai->golonganRuang->is($golongan));
    }
}
