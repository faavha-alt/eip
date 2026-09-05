<?php

namespace App\Console\Commands;

use App\Models\GolonganRuang;
use App\Models\Pegawai;
use App\Models\Pendidikan;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Terapkan 7 koreksi manual dari tinjauan 10-kasus beda nilai "terkini"
 * pegawai vs riwayat SIMPEG (PROGRESS.md 2026-09-05, "audit kelengkapan
 * master pegawai"). Ditinjau satu-satu via riwayat lengkap (bukan cuma
 * nilai akhir) sebelum diputuskan — 2 dari 10 kasus (Budianto, Fora
 * Falentina) SENGAJA TIDAK masuk daftar ini krn SIMPEG sendiri yg tampak
 * salah/tertinggal; 1 kasus (Fea Prihapsara) msh nunggu verifikasi
 * eksternal ke HR/departemen. Daftar tetap (bukan dari file), sekali
 * pakai lalu command ini boleh dihapus kalau sudah tak relevan lagi.
 */
class TerapkanKoreksiSimpeg extends Command
{
    protected $signature = 'pegawai:terapkan-koreksi-simpeg {--dry-run : Jalankan tanpa menyimpan perubahan}';

    protected $description = 'Terapkan 7 koreksi manual hasil tinjauan SIMPEG (lihat PROGRESS.md 2026-09-05)';

    /**
     * id_simpeg => ['golongan_kode' => ?string, 'tmt_golongan' => ?string, 'pendidikan_kode' => ?string]
     * null/absen = kolom itu tidak disentuh utk orang ini.
     */
    private const KOREKSI = [
        '7736' => ['tmt_golongan' => '2025-06-01'], // Fitrawan H. Pribadi — kode III/b tetap, TMT ke SK terbaru
        '7926' => ['golongan_kode' => 'III/c'], // Luthfiya K.P. — kode salah total, TMT (2026-08-01) sudah benar
        '941' => ['tmt_golongan' => '2023-04-01'], // Aris Dwi Mahardi — kode III/b tetap, pola 4-tahunan SIMPEG konsisten
        '2911' => ['pendidikan_kode' => 's1'], // Siti Baroroh Z.I. — ijazah S1 2022 jelas di SIMPEG
        '2917' => ['pendidikan_kode' => 'sma_slta'], // Heri Sukarno Putro — tak ada bukti S1, progresi golongan normal
        '7179' => ['pendidikan_kode' => 's1'], // Purwo Edi Minarno — tak ada bukti S2
        '7924' => ['tmt_golongan' => '2026-07-01'], // Albertus Sindhu A.K. — kode III/a tetap, SK terbaru SIMPEG
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $rows = [];

        foreach (self::KOREKSI as $idSimpeg => $koreksi) {
            $pegawai = Pegawai::where('id_simpeg', $idSimpeg)->first();
            if (! $pegawai) {
                $this->error("id_simpeg={$idSimpeg} tidak ditemukan, dilewati.");

                continue;
            }

            $perubahan = [];

            if (isset($koreksi['golongan_kode'])) {
                $golongan = GolonganRuang::where('kode', $koreksi['golongan_kode'])->firstOrFail();
                if ($pegawai->golongan_ruang_id !== $golongan->id) {
                    $perubahan['golongan_ruang_id'] = $golongan->id;
                    $rows[] = [$pegawai->nama_lengkap, 'golongan', $pegawai->golonganRuang?->kode, $golongan->kode];
                }
            }

            if (isset($koreksi['tmt_golongan'])) {
                $tmtBaru = Carbon::parse($koreksi['tmt_golongan']);
                if (! $pegawai->tmt_golongan?->isSameDay($tmtBaru)) {
                    $perubahan['tmt_golongan'] = $tmtBaru;
                    $rows[] = [$pegawai->nama_lengkap, 'tmt_golongan', $pegawai->tmt_golongan?->toDateString(), $tmtBaru->toDateString()];
                }
            }

            if (isset($koreksi['pendidikan_kode'])) {
                $pendidikan = Pendidikan::where('kode', $koreksi['pendidikan_kode'])->firstOrFail();
                if ($pegawai->pendidikan_terakhir_id !== $pendidikan->id) {
                    $perubahan['pendidikan_terakhir_id'] = $pendidikan->id;
                    $rows[] = [$pegawai->nama_lengkap, 'pendidikan', $pegawai->pendidikanTerakhir?->nama, $pendidikan->nama];
                }
            }

            if ($perubahan !== [] && ! $dryRun) {
                $pegawai->update($perubahan);
            }
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '').count($rows).' perubahan:');
        $this->table(['Nama', 'Field', 'Lama', 'Baru'], $rows);

        return self::SUCCESS;
    }
}
