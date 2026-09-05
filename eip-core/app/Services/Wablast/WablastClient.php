<?php

namespace App\Services\Wablast;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;

/**
 * Klien baca-saja ke API wa-blast — arah kebalikan dari `EipClient` di sisi
 * wa-blast. wa-blast adalah acuan terakhir nomor HP pegawai (lihat
 * `App\Console\Commands\PullNomorWaFromWablast`). Kontrak lengkap:
 * proyek `wa-blast`, `docs/api-eip-inbound.md`.
 */
class WablastClient
{
    /**
     * Iterasi semua kontak yang tertaut ke pegawai EIP, berubah sejak
     * `$since` (null = semua), mengikuti paginasi otomatis.
     *
     * @return LazyCollection<int, array{eip_pegawai_id: int, nip: ?string, phone: ?string, phone_normalized: ?string, updated_at: ?string}>
     */
    public function contactsSince(?Carbon $since): LazyCollection
    {
        return LazyCollection::make(function () use ($since) {
            $url = rtrim((string) config('services.wablast.base_url'), '/').'/api/eip/contacts';
            $query = ['per_page' => 200];
            if ($since) {
                $query['updated_since'] = $since->toIso8601String();
            }

            do {
                $response = Http::withToken((string) config('services.wablast.inbound_token'))
                    ->get($url, $query)
                    ->throw();
                $body = $response->json();

                foreach ($body['data'] as $row) {
                    yield $row;
                }

                $url = $body['links']['next'] ?? null;
                $query = []; // URL halaman berikutnya sudah lengkap dgn query string
            } while ($url);
        });
    }
}
