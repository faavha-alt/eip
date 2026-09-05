<?php

namespace App\Console\Commands;

use App\Models\ServiceClient;
use Illuminate\Console\Command;

/**
 * Daftarkan app konsumen baru (kepegawaian, gaji, aset, logistik, wa-blast,
 * dst) sbg klien service-to-service EIP Core API, terbitkan token Sanctum
 * sekali tampil (plaintext token tidak disimpan, hanya hash-nya).
 *
 * Ability default "master:read" (baca pegawai/unit_kerja/jabatan/organisasi).
 * "pegawai:write" HANYA utk app kepegawaian (docs/04 §6: satu jalur tulis).
 */
class CreateServiceClient extends Command
{
    protected $signature = 'service-client:create {kode} {nama}
                            {--abilities=master:read : Daftar ability, pisah koma}';

    protected $description = 'Daftarkan service client baru + terbitkan token API-nya';

    public function handle(): int
    {
        $kode = $this->argument('kode');

        if (ServiceClient::where('kode', $kode)->exists()) {
            $this->error("Service client [{$kode}] sudah ada.");

            return self::FAILURE;
        }

        $abilities = array_map('trim', explode(',', (string) $this->option('abilities')));

        $client = ServiceClient::create([
            'kode' => $kode,
            'nama' => $this->argument('nama'),
            'is_active' => true,
        ]);

        $token = $client->createToken($kode, $abilities)->plainTextToken;

        $this->info("Service client [{$kode}] dibuat. Ability: ".implode(', ', $abilities));
        $this->warn('Token (tampil SEKALI, simpan baik-baik):');
        $this->line($token);

        return self::SUCCESS;
    }
}
