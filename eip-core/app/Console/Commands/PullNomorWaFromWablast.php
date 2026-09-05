<?php

namespace App\Console\Commands;

use App\Models\Pegawai;
use App\Services\Wablast\WablastClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Tarik nomor HP terkini dari wa-blast — wa-blast adalah ACUAN TERAKHIR
 * nomor (kontak di sana tak pernah ditimpa balik dari EIP, lihat
 * `App\Console\Commands\ImportPegawaiFromExcel` & CLAUDE.md). Pull
 * inkremental via `updated_since`; checkpoint disimpan di cache
 * (`wablast_last_pulled_at`, forever) — kalau cache hilang/di-flush, pull
 * berikutnya cuma jadi full-refresh (aman & idempoten, bukan bug).
 *
 * Dicocokkan via `eip_pegawai_id` = `pegawai.id` (bukan `id_simpeg`),
 * sesuai kunci yang dipakai wa-blast saat sinkron awal dari EIP.
 */
class PullNomorWaFromWablast extends Command
{
    private const CACHE_KEY = 'wablast_last_pulled_at';

    protected $signature = 'pegawai:pull-nomor-wa
                            {--dry-run : Jalankan tanpa menyimpan perubahan}
                            {--full : Abaikan checkpoint, tarik semua kontak}';

    protected $description = 'Tarik nomor HP pegawai dari wa-blast (wa-blast = acuan terakhir nomor)';

    public function handle(WablastClient $client): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $sejak = $this->option('full') ? null : Cache::get(self::CACHE_KEY);
        $mulai = now();

        $stats = ['dicek' => 0, 'diupdate' => 0, 'tanpa_nomor' => 0, 'pegawai_tak_ditemukan' => 0];

        foreach ($client->contactsSince($sejak ? Carbon::parse($sejak) : null) as $row) {
            $stats['dicek']++;

            if (empty($row['phone_normalized'])) {
                $stats['tanpa_nomor']++;

                continue;
            }

            $pegawai = Pegawai::find($row['eip_pegawai_id']);
            if (! $pegawai) {
                $stats['pegawai_tak_ditemukan']++;

                continue;
            }

            if ($pegawai->no_hp !== $row['phone_normalized']) {
                if (! $dryRun) {
                    $pegawai->update(['no_hp' => $row['phone_normalized']]);
                }
                $stats['diupdate']++;
            }
        }

        if (! $dryRun) {
            Cache::forever(self::CACHE_KEY, $mulai->toIso8601String());
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '').'Selesai.');
        $this->table(['Metrik', 'Jumlah'], collect($stats)->map(fn ($v, $k) => [$k, $v])->toArray());

        return self::SUCCESS;
    }
}
