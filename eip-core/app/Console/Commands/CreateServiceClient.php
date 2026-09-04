<?php

namespace App\Console\Commands;

use App\Models\ServiceClient;
use Illuminate\Console\Command;

/**
 * Daftarkan app konsumen baru (kepegawaian, gaji, aset, logistik, wa-blast,
 * dst) sbg klien service-to-service EIP Core API, terbitkan token Sanctum
 * sekali tampil (plaintext token tidak disimpan, hanya hash-nya).
 */
class CreateServiceClient extends Command
{
    protected $signature = 'service-client:create {kode} {nama}';

    protected $description = 'Daftarkan service client baru + terbitkan token API-nya';

    public function handle(): int
    {
        $kode = $this->argument('kode');

        if (ServiceClient::where('kode', $kode)->exists()) {
            $this->error("Service client [{$kode}] sudah ada.");

            return self::FAILURE;
        }

        $client = ServiceClient::create([
            'kode' => $kode,
            'nama' => $this->argument('nama'),
            'is_active' => true,
        ]);

        $token = $client->createToken($kode)->plainTextToken;

        $this->info("Service client [{$kode}] dibuat.");
        $this->warn('Token (tampil SEKALI, simpan baik-baik):');
        $this->line($token);

        return self::SUCCESS;
    }
}
