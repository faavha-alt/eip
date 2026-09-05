<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PullNomorWaFromWablastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.wablast.base_url' => 'https://wablast.test',
            'services.wablast.inbound_token' => 'token-uji',
        ]);
    }

    public function test_update_no_hp_dari_kontak_yg_tertaut_dan_ikuti_paginasi(): void
    {
        $a = Pegawai::factory()->create(['no_hp' => null]);
        $b = Pegawai::factory()->create(['no_hp' => 'sampah-lama']);
        $c = Pegawai::factory()->create(['no_hp' => null]); // tanpa nomor di wa-blast

        Http::fake([
            'wablast.test/api/eip/contacts*' => Http::sequence()
                ->push([
                    'data' => [
                        ['eip_pegawai_id' => $a->id, 'nip' => $a->nip, 'phone' => '0812', 'phone_normalized' => '6281200000001', 'updated_at' => now()->toIso8601String()],
                    ],
                    'links' => ['next' => 'https://wablast.test/api/eip/contacts?page=2'],
                ])
                ->push([
                    'data' => [
                        ['eip_pegawai_id' => $b->id, 'nip' => $b->nip, 'phone' => '0813', 'phone_normalized' => '6281300000002', 'updated_at' => now()->toIso8601String()],
                        ['eip_pegawai_id' => $c->id, 'nip' => $c->nip, 'phone' => null, 'phone_normalized' => null, 'updated_at' => now()->toIso8601String()],
                        ['eip_pegawai_id' => 999999, 'nip' => '0', 'phone' => '0814', 'phone_normalized' => '6281400000003', 'updated_at' => now()->toIso8601String()],
                    ],
                    'links' => ['next' => null],
                ]),
        ]);

        $this->artisan('pegawai:pull-nomor-wa')->assertSuccessful();

        $this->assertSame('6281200000001', $a->fresh()->no_hp);
        $this->assertSame('6281300000002', $b->fresh()->no_hp);
        $this->assertNull($c->fresh()->no_hp);
        Http::assertSentCount(2);
    }

    public function test_dry_run_tidak_menyimpan_perubahan_dan_tidak_update_checkpoint(): void
    {
        $a = Pegawai::factory()->create(['no_hp' => null]);

        Http::fake([
            'wablast.test/api/eip/contacts*' => Http::response([
                'data' => [
                    ['eip_pegawai_id' => $a->id, 'nip' => $a->nip, 'phone' => '0812', 'phone_normalized' => '6281200000001', 'updated_at' => now()->toIso8601String()],
                ],
                'links' => ['next' => null],
            ]),
        ]);

        $this->artisan('pegawai:pull-nomor-wa', ['--dry-run' => true])->assertSuccessful();

        $this->assertNull($a->fresh()->no_hp);
        $this->assertNull(Cache::get('wablast_last_pulled_at'));
    }

    public function test_pull_kedua_pakai_checkpoint_sbg_updated_since(): void
    {
        Http::fake([
            'wablast.test/api/eip/contacts*' => Http::response(['data' => [], 'links' => ['next' => null]]),
        ]);

        $this->artisan('pegawai:pull-nomor-wa')->assertSuccessful();
        $checkpoint1 = Cache::get('wablast_last_pulled_at');
        $this->assertNotNull($checkpoint1);

        $this->artisan('pegawai:pull-nomor-wa')->assertSuccessful();

        Http::assertSent(function ($request) {
            return str_contains((string) $request->url(), 'updated_since=');
        });
    }
}
