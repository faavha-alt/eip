<?php

namespace App\Console\Commands;

use App\Enums\Agama;
use App\Enums\JenisKelamin;
use App\Enums\JenisRiwayatJabatan;
use App\Models\GolonganRuang;
use App\Models\Pegawai;
use App\Models\Pendidikan;
use App\Models\RiwayatJabatan;
use App\Models\RiwayatPangkatGolongan;
use App\Models\RiwayatPendidikan;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sinkronkan data pegawai dgn rekap resmi SIMPEG (docs/rekap_pegawai.xlsx,
 * TIDAK ikut git — berisi PII). Dicocokkan lewat `pegawai.id_simpeg` =
 * kolom "id_sumber" di tiap sheet (dikonfirmasi identik & tervalidasi via
 * NIP, lihat PROGRESS.md).
 *
 * SIMPEG = sumber resmi ASN (docs/CLAUDE.md §3.6) utk field yg TIDAK
 * tersedia di sumber HR lama: jenis_kelamin, agama. Ditulis TANPA syarat
 * (field ini 100% kosong sebelumnya).
 *
 * Sheet lain (Pendidikan Tinggi/Dasar, Golongan Ruang, Jabatan
 * Fungsional/Kelola) berisi RIWAYAT lengkap -> insert idempoten ke
 * riwayat_pendidikan / riwayat_pangkat_golongan / riwayat_jabatan (skip
 * kalau baris identik sudah ada, aman dijalankan berulang). Nilai
 * "TERKINI" di pegawai (pendidikan_terakhir_id, golongan_ruang_id +
 * tmt_golongan) disinkronkan ke riwayat SIMPEG PALING BARU di akhir —
 * kalau beda dari nilai sekarang, dicatat sbg peringatan (bukan ditimpa
 * diam-diam) supaya bisa ditinjau.
 *
 * Baris "Tidak ada data yang ditemukan." (SIMPEG kosong utk orang itu)
 * dilewati. id_sumber yg tidak match pegawai manapun (mis. 9901-9908 di
 * sheet "Data Kosong") dilaporkan, bukan dianggap error.
 */
class SyncPegawaiFromSimpeg extends Command
{
    protected $signature = 'pegawai:sync-simpeg {path=docs/rekap_pegawai.xlsx : Path ke file rekap SIMPEG .xlsx}
                            {--dry-run : Jalankan tanpa menyimpan perubahan}';

    protected $description = 'Sinkronkan jenis_kelamin/agama + riwayat pendidikan/golongan/jabatan dari rekap SIMPEG';

    private array $warnings = [];

    private array $currentValueChanges = [];

    public function handle(): int
    {
        $path = $this->argument('path');
        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $this->info('Membaca '.$path.' ...');
        $spreadsheet = IOFactory::load($path);

        /** @var array<string, Pegawai> $pegawaiBySimpeg */
        $pegawaiBySimpeg = Pegawai::query()->whereNotNull('id_simpeg')->get()->keyBy('id_simpeg')->all();

        $stats = [
            'data_utama_cocok' => 0, 'data_utama_tak_cocok' => 0,
            'gender_agama_diisi' => 0,
            'riwayat_pendidikan_baru' => 0, 'riwayat_golongan_baru' => 0, 'riwayat_jabatan_baru' => 0,
        ];

        $run = function () use ($spreadsheet, $pegawaiBySimpeg, &$stats) {
            $this->syncDataUtama($spreadsheet->getSheetByName('Data Utama'), $pegawaiBySimpeg, $stats);
            $this->syncRiwayatPendidikan($spreadsheet->getSheetByName('Pendidikan Tinggi'), $pegawaiBySimpeg, $stats);
            $this->syncRiwayatPendidikan($spreadsheet->getSheetByName('Pendidikan Dasar'), $pegawaiBySimpeg, $stats);
            $this->syncRiwayatGolongan($spreadsheet->getSheetByName('Golongan Ruang'), $pegawaiBySimpeg, $stats);
            $this->syncRiwayatJabatan($spreadsheet->getSheetByName('Jabatan Fungsional'), JenisRiwayatJabatan::Fungsional, $pegawaiBySimpeg, $stats);
            $this->syncRiwayatJabatan($spreadsheet->getSheetByName('Jabatan Kelola'), JenisRiwayatJabatan::Struktural, $pegawaiBySimpeg, $stats);
            $this->syncNilaiTerkini($pegawaiBySimpeg);
        };

        try {
            DB::transaction(function () use ($run, $dryRun) {
                $run();
                if ($dryRun) {
                    throw new \RuntimeException('__dry_run_rollback__');
                }
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== '__dry_run_rollback__') {
                throw $e;
            }
        }

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '').'Selesai.');
        $this->table(['Metrik', 'Jumlah'], collect($stats)->map(fn ($v, $k) => [$k, $v])->toArray());

        if ($this->currentValueChanges !== []) {
            $this->newLine();
            $this->warn(count($this->currentValueChanges).' nilai TERKINI pegawai berbeda dari riwayat SIMPEG paling baru (ditinjau, BELUM ditimpa otomatis kecuali dgn --dry-run=false diatas sdh menuliskannya):');
            foreach ($this->currentValueChanges as $c) {
                $this->line("  - {$c}");
            }
        }

        if ($this->warnings !== []) {
            $this->newLine();
            $this->warn(count($this->warnings).' peringatan:');
            foreach ($this->warnings as $w) {
                $this->line("  - {$w}");
            }
        }

        return self::SUCCESS;
    }

    /** @param array<string, Pegawai> $pegawaiBySimpeg */
    private function syncDataUtama(Worksheet $sheet, array $pegawaiBySimpeg, array &$stats): void
    {
        foreach ($this->rows($sheet) as $rowNum => $row) {
            $idSumber = $this->normalizeId($row[0] ?? null);
            $pegawai = $pegawaiBySimpeg[$idSumber] ?? null;
            if (! $pegawai) {
                $stats['data_utama_tak_cocok']++;

                continue;
            }
            $stats['data_utama_cocok']++;

            $nip = preg_replace('/\D/', '', (string) ($row[3] ?? ''));
            if ($nip !== '' && $nip !== preg_replace('/\D/', '', (string) $pegawai->nip)) {
                $this->warnings[] = "id_simpeg={$idSumber}: NIP sheet ({$nip}) beda dgn DB ({$pegawai->nip}) — dilewati, cek manual";

                continue;
            }

            $jenisKelamin = $this->mapJenisKelamin(trim((string) ($row[13] ?? '')));
            $agama = $this->mapAgama(trim((string) ($row[14] ?? '')));

            $dirty = false;
            if ($jenisKelamin && $pegawai->jenis_kelamin !== $jenisKelamin) {
                $pegawai->jenis_kelamin = $jenisKelamin;
                $dirty = true;
            }
            if ($agama && $pegawai->agama !== $agama) {
                $pegawai->agama = $agama;
                $dirty = true;
            }
            if ($dirty) {
                $pegawai->save();
                $stats['gender_agama_diisi']++;
            }
        }
    }

    /** @param array<string, Pegawai> $pegawaiBySimpeg */
    private function syncRiwayatPendidikan(Worksheet $sheet, array $pegawaiBySimpeg, array &$stats): void
    {
        foreach ($this->rows($sheet) as $rowNum => $row) {
            $idSumber = $this->normalizeId($row[0] ?? null);
            $pegawai = $pegawaiBySimpeg[$idSumber] ?? null;
            $jenjangRaw = trim((string) ($row[3] ?? ''));
            if (! $pegawai || $jenjangRaw === '') {
                continue; // tidak match, atau baris "Tidak ada data yang ditemukan."
            }

            $kode = $this->mapPendidikanKode($jenjangRaw);
            $pendidikan = $kode ? Pendidikan::where('kode', $kode)->first() : null;
            if (! $pendidikan) {
                $this->warnings[] = "id_simpeg={$idSumber}: jenjang pendidikan tidak dikenali [{$jenjangRaw}]";

                continue;
            }

            $namaInstitusi = trim((string) ($row[4] ?? '')) ?: null;
            $programStudi = trim((string) ($row[5] ?? ''));
            $programStudi = ($programStudi === '' || $programStudi === '-') ? null : $programStudi;
            $noIjazah = trim((string) ($row[6] ?? '')) ?: null;
            $tahunLulus = $this->parseTahun($row[7] ?? null);

            $riwayat = RiwayatPendidikan::firstOrCreate([
                'pegawai_id' => $pegawai->id,
                'pendidikan_id' => $pendidikan->id,
                'nama_institusi' => $namaInstitusi,
                'tahun_lulus' => $tahunLulus,
            ], [
                'program_studi' => $programStudi,
                'no_ijazah' => $noIjazah,
            ]);
            if ($riwayat->wasRecentlyCreated) {
                $stats['riwayat_pendidikan_baru']++;
            }
        }
    }

    /** @param array<string, Pegawai> $pegawaiBySimpeg */
    private function syncRiwayatGolongan(Worksheet $sheet, array $pegawaiBySimpeg, array &$stats): void
    {
        foreach ($this->rows($sheet) as $rowNum => $row) {
            $idSumber = $this->normalizeId($row[0] ?? null);
            $pegawai = $pegawaiBySimpeg[$idSumber] ?? null;
            $kodeGolongan = trim((string) ($row[4] ?? ''));
            if (! $pegawai || $kodeGolongan === '') {
                continue;
            }

            $golongan = GolonganRuang::where('kode', $kodeGolongan)->first();
            if (! $golongan) {
                $this->warnings[] = "id_simpeg={$idSumber}: golongan ruang tidak dikenali [{$kodeGolongan}]";

                continue;
            }

            $tmt = $this->parseTanggal($row[6] ?? null);
            if (! $tmt) {
                $this->warnings[] = "id_simpeg={$idSumber}: TMT SK golongan {$kodeGolongan} tidak terparse, dilewati";

                continue;
            }
            $noSk = trim((string) ($row[5] ?? '')) ?: null;

            $riwayat = RiwayatPangkatGolongan::firstOrCreate([
                'pegawai_id' => $pegawai->id,
                'golongan_ruang_id' => $golongan->id,
                'tmt' => $tmt,
                'no_sk' => $noSk,
            ]);
            if ($riwayat->wasRecentlyCreated) {
                $stats['riwayat_golongan_baru']++;
            }
        }
    }

    /** @param array<string, Pegawai> $pegawaiBySimpeg */
    private function syncRiwayatJabatan(Worksheet $sheet, JenisRiwayatJabatan $jenis, array $pegawaiBySimpeg, array &$stats): void
    {
        $hasStatusKolom = $sheet->getTitle() === 'Jabatan Kelola';

        foreach ($this->rows($sheet) as $rowNum => $row) {
            $idSumber = $this->normalizeId($row[0] ?? null);
            $pegawai = $pegawaiBySimpeg[$idSumber] ?? null;
            $jabatanNama = trim((string) ($row[3] ?? ''));
            if (! $pegawai || $jabatanNama === '') {
                continue;
            }

            $jabatanDetail = trim((string) ($row[4] ?? '')) ?: null;
            $noSk = trim((string) ($row[5] ?? '')) ?: null;
            $tmtAwal = $this->parseTanggal($row[6] ?? null);
            $tmtAkhir = $this->parseTanggal($row[7] ?? null);
            $status = $hasStatusKolom ? (trim((string) ($row[8] ?? '')) ?: null) : null;

            $riwayat = RiwayatJabatan::firstOrCreate([
                'pegawai_id' => $pegawai->id,
                'jenis' => $jenis,
                'jabatan_nama' => $jabatanNama,
                'jabatan_detail' => $jabatanDetail,
                'no_sk' => $noSk,
                'tmt_awal' => $tmtAwal,
            ], [
                'tmt_akhir' => $tmtAkhir,
                'status' => $status,
            ]);
            if ($riwayat->wasRecentlyCreated) {
                $stats['riwayat_jabatan_baru']++;
            }
        }
    }

    /**
     * Nilai "terkini" pegawai disamakan dgn riwayat SIMPEG paling baru
     * (pendidikan = jenjang tertinggi, golongan = TMT SK terakhir). Kalau
     * beda dari nilai yg sudah ada, DICATAT sbg peringatan supaya ditinjau
     * manual dulu — tidak ditimpa diam-diam (data lama bisa saja lebih
     * mutakhir dari SIMPEG kalau SIMPEG belum diupdate admin akademik).
     *
     * @param  array<string, Pegawai>  $pegawaiBySimpeg
     */
    private function syncNilaiTerkini(array $pegawaiBySimpeg): void
    {
        foreach ($pegawaiBySimpeg as $pegawai) {
            $pendidikanTertinggi = $pegawai->riwayatPendidikan()->with('pendidikan')
                ->get()->sortByDesc(fn ($r) => $r->pendidikan->jenjang)->first();
            if ($pendidikanTertinggi && $pendidikanTertinggi->pendidikan_id !== $pegawai->pendidikan_terakhir_id) {
                $lama = $pegawai->pendidikanTerakhir?->nama ?? '(kosong)';
                $this->currentValueChanges[] = "id_simpeg={$pegawai->id_simpeg} {$pegawai->nama_lengkap}: pendidikan_terakhir {$lama} -> {$pendidikanTertinggi->pendidikan->nama} (riwayat SIMPEG lebih tinggi)";
            }

            $golonganTerbaru = $pegawai->riwayatPangkatGolongan()->orderByDesc('tmt')->first();
            if ($golonganTerbaru && ($golonganTerbaru->golongan_ruang_id !== $pegawai->golongan_ruang_id
                || ! $pegawai->tmt_golongan?->isSameDay($golonganTerbaru->tmt))) {
                $lama = $pegawai->golonganRuang?->kode ?? '(kosong)';
                $this->currentValueChanges[] = "id_simpeg={$pegawai->id_simpeg} {$pegawai->nama_lengkap}: golongan_ruang {$lama} (TMT ".($pegawai->tmt_golongan?->toDateString() ?? '-').') -> '.$golonganTerbaru->golonganRuang->kode.' (TMT '.$golonganTerbaru->tmt->toDateString().', SK terbaru SIMPEG)';
            }
        }
    }

    /** @return \Generator<int, array<int, mixed>> */
    private function rows(?Worksheet $sheet): \Generator
    {
        if (! $sheet) {
            return;
        }
        $range = $sheet->rangeToArray('A2:'.$sheet->getHighestColumn().$sheet->getHighestRow(), null, true, true);
        foreach ($range as $i => $row) {
            yield $i => array_values($row);
        }
    }

    private function normalizeId(mixed $value): string
    {
        return trim((string) $value);
    }

    private function mapJenisKelamin(string $raw): ?JenisKelamin
    {
        return match ($raw) {
            'Laki Laki', 'Laki-Laki' => JenisKelamin::LakiLaki,
            'Perempuan' => JenisKelamin::Perempuan,
            default => null,
        };
    }

    private function mapAgama(string $raw): ?Agama
    {
        return match (mb_strtolower($raw)) {
            'islam' => Agama::Islam,
            'kristen' => Agama::Kristen,
            'katolik' => Agama::Katolik,
            'hindu' => Agama::Hindu,
            'buddha', 'budha' => Agama::Buddha,
            'konghucu' => Agama::Konghucu,
            default => null,
        };
    }

    /** Kode master `pendidikan` (termasuk jenjang di bawah SMA, lihat migrasi lengkapi_master_golongan_dan_pendidikan_dari_simpeg). */
    private function mapPendidikanKode(string $raw): ?string
    {
        return match (mb_strtoupper($raw)) {
            'SD' => 'sd',
            'SMP/SLTP' => 'smp',
            'SMU/SLTA' => 'sma_slta',
            'D3' => 'd3',
            'D4' => 'd4',
            'S1' => 's1',
            'PROFESI' => 'profesi',
            'S2' => 's2',
            'S3' => 's3',
            default => null,
        };
    }

    private function parseTahun(mixed $value): ?int
    {
        $tanggal = $this->parseTanggal($value);

        return $tanggal?->year;
    }

    private function parseTanggal(mixed $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return null;
        }
        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
