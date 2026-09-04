<?php

namespace App\Console\Commands;

use App\Enums\JenisJabatan;
use App\Enums\JenisPegawai;
use App\Enums\JenisUnitKerja;
use App\Enums\PendidikanTerakhir;
use App\Enums\StatusKepegawaian;
use App\Enums\StatusPenempatan;
use App\Models\Jabatan;
use App\Models\Pegawai;
use App\Models\Penempatan;
use App\Models\UnitKerja;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Import data pegawai nyata (mis. docs/data_pegawai.xlsx, TIDAK ikut git —
 * berisi PII) ke master EIP. Membentuk unit_kerja & jabatan otomatis dari
 * nilai yang benar-benar ada di sumber (bukan katalog tebakan terpisah),
 * supaya penamaan pasti cocok.
 *
 * Kolom sumber (indeks 0-based, baris 1 = header):
 * 0 No, 1 ID, 2 Nama, 3 NIP, 4 NIK, 5 NPWP, 6 Email, 7 No HP,
 * 8 Tempat/Tanggal Lahir, 9 Tanggal Masuk, 10 No. Seri Kepeg, 11 J.Pegawai,
 * 12 Sub Unit, 13 Homebase (Sister), 14 Status Pegawai, 15 Pendidikan,
 * 16 Gol. Ruang, 17 TMT Gol., 18 Jab. Fungsional, 19 TMT JF, 20 Tanggal Lahir,
 * 21 Usia, 22 Masa Kerja, 23 Waktu Pensiun, 24 Jabatan (struktural).
 *
 * Baris dianggap data pegawai HANYA jika kolom "ID" terisi angka — sheet
 * sumber punya tabel rekap statistik menempel di bawah tabel utama yang
 * ikut kebaca kalau difilter cuma dari kolom Nama.
 */
class ImportPegawaiFromExcel extends Command
{
    protected $signature = 'pegawai:import {path : Path ke file .xlsx}
                            {--dry-run : Jalankan tanpa menyimpan perubahan}';

    protected $description = 'Import data pegawai dari file Excel nyata ke master EIP (unit_kerja, jabatan, pegawai, penempatan)';

    private const ACADEMIC_RANKS = ['Guru Besar', 'Lektor Kepala', 'Lektor', 'Asisten Ahli', 'Tenaga Pengajar'];

    private array $warnings = [];

    private array $strukturalNeedsReview = [];

    public function handle(): int
    {
        $path = $this->argument('path');
        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $fakultas = UnitKerja::where('kode', 'FMIPA')->first();
        if (! $fakultas) {
            $this->error('Unit kerja FMIPA belum ada. Jalankan `php artisan db:seed --class=MasterDataSeeder` dulu.');

            return self::FAILURE;
        }

        $this->info('Membaca '.$path.' ...');
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->rangeToArray(
            'A2:Y'.$sheet->getHighestDataRow(),
            null,
            true,
            true,
        );

        $stats = ['pegawai_baru' => 0, 'pegawai_update' => 0, 'unit_baru' => 0, 'jabatan_baru' => 0, 'penempatan' => 0];

        $run = function () use ($rows, $fakultas, &$stats) {
            foreach ($rows as $i => $row) {
                $rowNum = $i + 2;
                $id = trim((string) ($row[1] ?? ''));
                if (! preg_match('/^\d+$/', $id)) {
                    continue; // bukan baris pegawai (kosong / tabel rekap di bawahnya)
                }

                $this->importRow($rowNum, $row, $fakultas, $stats);
            }
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

        if ($this->warnings !== []) {
            $this->newLine();
            $this->warn(count($this->warnings).' peringatan (baris dgn data tidak lengkap/tidak terparse):');
            foreach ($this->warnings as $w) {
                $this->line("  - {$w}");
            }
        }

        if ($this->strukturalNeedsReview !== []) {
            $this->newLine();
            $this->warn(count($this->strukturalNeedsReview).' jabatan struktural TERCATAT di katalog tapi BELUM dibuatkan penempatan (unit tujuan tidak bisa ditentukan otomatis dari judul jabatan — perlu assignment manual):');
            foreach ($this->strukturalNeedsReview as $s) {
                $this->line("  - baris {$s['row']}: {$s['nama']} -> {$s['jabatan']}");
            }
        }

        return self::SUCCESS;
    }

    private function importRow(int $rowNum, array $row, UnitKerja $fakultas, array &$stats): void
    {
        $nama = trim((string) ($row[2] ?? ''));
        $nip = $this->sanitizeDigits($row[3] ?? null, $rowNum, 'NIP');
        $nik = $this->sanitizeDigits($row[4] ?? null, $rowNum, 'NIK');
        $npwp = trim((string) ($row[5] ?? '')) ?: null;
        $email = trim((string) ($row[6] ?? '')) ?: null;
        $noHp = trim((string) ($row[7] ?? '')) ?: null;
        $ttl = trim((string) ($row[8] ?? ''));
        $tempatLahir = $ttl !== '' ? trim(explode(',', $ttl)[0]) : null;
        $tanggalMasuk = $this->parseTanggal($row[9] ?? null, $rowNum, 'Tanggal Masuk');
        $noSeriKepeg = trim((string) ($row[10] ?? '')) ?: null;
        $jenisPegawaiRaw = trim((string) ($row[11] ?? ''));
        $subUnitNama = trim((string) ($row[12] ?? ''));
        $homebaseNama = trim((string) ($row[13] ?? ''));
        $statusRaw = trim((string) ($row[14] ?? ''));
        $pendidikanRaw = trim((string) ($row[15] ?? ''));
        $golonganRuang = trim((string) ($row[16] ?? '')) ?: null;
        $tmtGol = $this->parseTanggal($row[17] ?? null, $rowNum, 'TMT Gol.', 'm/d/y');
        $jabFungsionalNama = trim((string) ($row[18] ?? ''));
        $tmtJf = $this->parseTanggal($row[19] ?? null, $rowNum, 'TMT JF', 'm/d/y');
        $tanggalLahir = $this->parseTanggal($row[20] ?? null, $rowNum, 'Tanggal Lahir');
        $jabatanStrukturalNama = trim((string) ($row[24] ?? ''));

        $status = $this->mapStatus($statusRaw, $rowNum);
        $jenisPegawai = $jenisPegawaiRaw === 'Tenaga Pendidik' ? JenisPegawai::TenagaPendidik
            : ($jenisPegawaiRaw === 'Tenaga Kependidikan' ? JenisPegawai::TenagaKependidikan : null);
        $pendidikan = $this->mapPendidikan($pendidikanRaw);

        $subUnit = $subUnitNama !== '' ? $this->resolveUnitKerja($subUnitNama, $fakultas, $stats) : null;
        $homebase = ($homebaseNama !== '' && $homebaseNama !== $subUnitNama)
            ? $this->resolveUnitKerja($homebaseNama, $fakultas, $stats)
            : null;

        $jabatanFungsional = $jabFungsionalNama !== ''
            ? $this->resolveJabatan(
                $jabFungsionalNama,
                in_array($jabFungsionalNama, self::ACADEMIC_RANKS, true) ? JenisJabatan::Fungsional : JenisJabatan::FungsionalUmum,
                $stats,
            )
            : null;

        if ($jabatanStrukturalNama !== '' && $jabatanStrukturalNama !== '-') {
            $this->resolveJabatan($jabatanStrukturalNama, JenisJabatan::Struktural, $stats);
            $this->strukturalNeedsReview[] = ['row' => $rowNum, 'nama' => $nama, 'jabatan' => $jabatanStrukturalNama];
        }

        $isActive = $status !== StatusKepegawaian::PurnaTugas;
        $gelar = $this->parseGelar($nama);

        $pegawai = Pegawai::updateOrCreate(
            ['id_sumber' => trim((string) $row[1])],
            [
                'nip' => $nip,
                'nik' => $nik,
                'npwp' => $npwp,
                'no_seri_kepeg' => $noSeriKepeg,
                'nama_lengkap' => $gelar['inti'],
                'gelar_depan' => $gelar['depan'],
                'gelar_belakang' => $gelar['belakang'],
                'jenis_kelamin' => null, // tidak tersedia di sumber
                'tempat_lahir' => $tempatLahir,
                'tanggal_lahir' => $tanggalLahir,
                'email' => $email,
                'no_hp' => $noHp,
                'status_kepegawaian' => $status,
                'jenis_pegawai' => $jenisPegawai,
                'pendidikan_terakhir' => $pendidikan,
                'golongan_ruang' => $golonganRuang,
                'tmt_golongan' => $tmtGol,
                'tanggal_masuk' => $tanggalMasuk,
                'is_active' => $isActive,
            ],
        );
        $stats[$pegawai->wasRecentlyCreated ? 'pegawai_baru' : 'pegawai_update']++;

        $tglMulaiPosisi = $tmtJf ?? $tanggalMasuk ?? now();
        $statusPenempatan = $isActive ? StatusPenempatan::Aktif : StatusPenempatan::Nonaktif;

        if ($subUnit && $jabatanFungsional) {
            $this->upsertPenempatan($pegawai, $subUnit, $jabatanFungsional, $tglMulaiPosisi, true, $statusPenempatan, $stats);
        }

        if ($homebase && $jabatanFungsional) {
            $this->upsertPenempatan($pegawai, $homebase, $jabatanFungsional, $tglMulaiPosisi, false, $statusPenempatan, $stats);
        }
    }

    private function upsertPenempatan(Pegawai $pegawai, UnitKerja $unit, Jabatan $jabatan, Carbon $tglMulai, bool $utama, StatusPenempatan $status, array &$stats): void
    {
        $penempatan = Penempatan::updateOrCreate(
            ['pegawai_id' => $pegawai->id, 'unit_kerja_id' => $unit->id, 'jabatan_id' => $jabatan->id],
            ['tgl_mulai' => $tglMulai, 'is_posisi_utama' => $utama, 'status' => $status],
        );
        if ($penempatan->wasRecentlyCreated) {
            $stats['penempatan']++;
        }
    }

    private function resolveUnitKerja(string $nama, UnitKerja $fakultas, array &$stats): UnitKerja
    {
        $lower = mb_strtolower($nama);
        $jenis = str_contains($lower, 'subbagian') ? JenisUnitKerja::Subbagian
            : (str_contains($lower, 'bagian') ? JenisUnitKerja::Bagian : JenisUnitKerja::Prodi);

        $parent = $fakultas;
        if ($jenis === JenisUnitKerja::Subbagian) {
            // Kode HARUS sama dgn yg dihasilkan slug('Bagian Tata Usaha FMIPA')
            // di jalur normal di bawah, supaya tidak membuat 2 record berbeda
            // utk unit yang sama.
            $parent = $this->resolveUnitKerja('Bagian Tata Usaha FMIPA', $fakultas, $stats);
        }

        $kode = Str::upper(Str::slug($nama, '_'));
        $unit = UnitKerja::firstOrCreate(
            ['kode' => $kode],
            ['organisasi_id' => $fakultas->organisasi_id, 'parent_id' => $parent->id, 'nama' => $nama, 'jenis_unit' => $jenis, 'is_active' => true],
        );
        if ($unit->wasRecentlyCreated) {
            $stats['unit_baru']++;
        }

        return $unit;
    }

    private function resolveJabatan(string $nama, JenisJabatan $jenis, array &$stats): Jabatan
    {
        $kode = Str::upper(Str::slug($nama, '_'));
        $jabatan = Jabatan::firstOrCreate(
            ['kode' => $kode],
            ['nama' => $nama, 'jenis' => $jenis, 'level' => $this->levelFor($nama, $jenis), 'is_active' => true],
        );
        if ($jabatan->wasRecentlyCreated) {
            $stats['jabatan_baru']++;
        }

        return $jabatan;
    }

    /**
     * Level urutan approval — heuristik dari judul jabatan, PERLU DITINJAU
     * ULANG manual sebelum dipakai sbg dasar alur approval produksi.
     */
    private function levelFor(string $nama, JenisJabatan $jenis): int
    {
        if ($jenis === JenisJabatan::Fungsional) {
            $index = array_search($nama, self::ACADEMIC_RANKS, true);

            return $index === false ? 5 : $index + 1;
        }
        if ($jenis === JenisJabatan::FungsionalUmum) {
            return 6;
        }

        $lower = mb_strtolower($nama);

        return match (true) {
            str_contains($lower, 'wakil dekan') => 2,
            str_contains($lower, 'dekan') => 1,
            str_contains($lower, 'ketua program studi'), str_contains($lower, 'kepala bagian'), str_contains($lower, 'ketua senat'), str_contains($lower, 'kepala upt'), str_contains($lower, 'kepala kantor') => 3,
            str_contains($lower, 'kepala sub'), str_contains($lower, 'kepala seksi'), str_contains($lower, 'sekretaris'), str_contains($lower, 'koordinator'), str_contains($lower, 'ketua komisi'), str_contains($lower, 'ketua pusat'), str_contains($lower, 'ketua unit'), str_contains($lower, 'kepala laboratorium') => 4,
            default => 5,
        };
    }

    private function mapStatus(string $raw, int $rowNum): ?StatusKepegawaian
    {
        $status = match ($raw) {
            'PNS' => StatusKepegawaian::Pns,
            'Non PNS' => StatusKepegawaian::NonPns,
            'Kontrak Profesional' => StatusKepegawaian::KontrakProfesional,
            'Purna Tugas' => StatusKepegawaian::PurnaTugas,
            default => null,
        };
        if ($status === null) {
            $this->warnings[] = "baris {$rowNum}: Status Pegawai tidak dikenali [{$raw}]";
        }

        return $status;
    }

    private function mapPendidikan(string $raw): ?PendidikanTerakhir
    {
        return match ($raw) {
            'SMU/SLTA' => PendidikanTerakhir::SmaSlta,
            'D3' => PendidikanTerakhir::D3,
            'S1' => PendidikanTerakhir::S1,
            'PROFESI' => PendidikanTerakhir::Profesi,
            'S2' => PendidikanTerakhir::S2,
            'S3' => PendidikanTerakhir::S3,
            default => null,
        };
    }

    private function sanitizeDigits(mixed $value, int $rowNum, string $label): ?string
    {
        // Sel angka panjang (NIP/NIK) kadang diformat Excel dgn pemisah ribuan.
        $value = str_replace([',', '.', ' '], '', trim((string) $value));
        if ($value === '' || $value === '-') {
            return null;
        }
        if (! preg_match('/^\d+$/', $value)) {
            $this->warnings[] = "baris {$rowNum}: {$label} bukan angka, diabaikan (kemungkinan data sumber bergeser kolom)";

            return null;
        }

        return $value;
    }

    private function parseTanggal(mixed $value, int $rowNum, string $label, string $format = 'n/j/Y'): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return null;
        }
        try {
            return Carbon::createFromFormat($format, $value)->startOfDay();
        } catch (\Throwable) {
            $this->warnings[] = "baris {$rowNum}: {$label} tidak terparse [{$value}]";

            return null;
        }
    }

    /** Gelar depan yg dikenali, urutan tidak penting (dicek exact-match per token). */
    private const GELAR_DEPAN = [
        'Prof.', 'Prof.Drs.', 'Dr.rer.nat.', 'Dr.rer.nat', 'Dr.Eng.', 'Dr.-Ing.',
        'Dr.', 'Ir.', 'Drs.', 'Dra.', 'apt.', 'dr.', 'Hj.', 'H.', 'Eng.', 'rer.nat.', 'K.H.',
    ];

    /**
     * Pisahkan "Prof. Dr. Nama Lengkap, S.Si., M.Si." jadi gelar depan (token
     * awal yg cocok whitelist gelar akademik/profesi), nama inti, dan gelar
     * belakang (semua setelah koma pertama, utuh apa adanya).
     *
     * Heuristik, BUKAN sempurna — nama dgn inisial tengah (mis. "Irfan A N")
     * aman krn inisial tunggal tidak ada di whitelist. Kasus tak lazim tetap
     * mungkin salah; tinjau manual bila perlu.
     *
     * @return array{depan: ?string, belakang: ?string, inti: string}
     */
    private function parseGelar(string $namaMentah): array
    {
        [$depanDanNama, $belakang] = array_pad(explode(',', trim($namaMentah), 2), 2, null);
        $belakang = $belakang !== null ? (trim($belakang) ?: null) : null;

        $tokens = preg_split('/\s+/', trim($depanDanNama));
        $depanTokens = [];
        while (count($tokens) > 1 && in_array($tokens[0], self::GELAR_DEPAN, true)) {
            $depanTokens[] = array_shift($tokens);
        }

        return [
            'depan' => $depanTokens !== [] ? implode(' ', $depanTokens) : null,
            'belakang' => $belakang,
            'inti' => implode(' ', $tokens),
        ];
    }
}
