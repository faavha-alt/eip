<?php

namespace App\Console\Commands;

use App\Enums\JenisUnitKerja;
use App\Enums\StatusPenempatan;
use App\Models\Jabatan;
use App\Models\Organisasi;
use App\Models\Pegawai;
use App\Models\Penempatan;
use App\Models\UnitKerja;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Tindak lanjut `pegawai:import`: assignment manual 58 jabatan struktural
 * (Dekan, Wadek, Kaprodi, Ka.Lab, dst — lihat docs/04 §Langkah 2b) ke unit
 * kerja yang tepat. Dipisah dari importer krn pemetaan jabatan->unit tidak
 * bisa ditentukan otomatis dari judul jabatan saja & berisiko salah kalau
 * ditebak (terutama posisi pimpinan) — ditinjau manual sekali di sini,
 * dikunci per orang via NIP + nama jabatan persis (bbrp judul jabatan
 * dipakai >1 orang, mis. "Kepala Laboratorium Farmasi").
 *
 * 3 orang memegang jabatan tingkat UNIVERSITAS (Wakil Rektor, Kepala
 * Subdirektorat) — dosen FMIPA yg merangkap jabatan tingkat UNS. Dibuatkan
 * unit "Rektorat Universitas Sebelas Maret" sbg tempatnya, bukan dipaksa
 * masuk ke hierarki FMIPA.
 *
 * tgl_mulai pakai tanggal command dijalankan (recorded-at), BUKAN TMT
 * sesungguhnya — sumber data tidak menyediakan TMT jabatan struktural.
 */
class AssignJabatanStruktural extends Command
{
    protected $signature = 'pegawai:assign-struktural {--dry-run : Jalankan tanpa menyimpan perubahan}';

    protected $description = 'Assign 58 jabatan struktural (hasil pegawai:import) ke unit kerja yang tepat';

    /** NIP => [kode unit_kerja tujuan, nama jabatan struktural persis]. */
    private const MAP = [
        '196102231986011001' => ['FMIPA', 'Ketua Komisi Akademik dan Kemahasiswaan'],
        '196008091986121001' => ['FMIPA', 'Kepala Kantor Seleksi Penerimaan Mahasiswa Baru'],
        '196303271986012002' => ['FMIPA', 'Ketua Komisi Sumber Daya Manusia'],
        '196603281992031001' => ['FMIPA', 'Sekretaris Senat Akademik FMIPA'],
        '196610071993021001' => ['FMIPA', 'Ketua Komisi C Bidang Kemahasiswaan dan Alumni Senat Akademik Fakultas MIPA'],
        '197002281995122001' => ['FMIPA', 'Wakil Dekan Bidang Akademik dan Penelitian Fakultas Matematika dan Ilmu Pengetahuan Alam (FMIPA)'],
        '197112271997022001' => ['FMIPA', 'Kepala Laboratorium MIPA Terpadu'],
        '197210132000031002' => ['FMIPA', 'Wakil Dekan Bidang Nonakademik Fakultas Matematika dan Ilmu Pengetahuan Alam (FMIPA)'],
        '197311092000031001' => ['S_2_FISIKA', 'Ketua Program Studi S2 Ilmu Fisika'],
        '196403052000031002' => ['FMIPA', 'Ketua Komisi Bidang Pengembangan Akademik dan Keilmuan Kegurubesaran'],
        '196903131997022001' => ['FMIPA', 'Kepala Seksi Pengujian, Kalibrasi, dan Sertifikasi'],
        '197112111997022001' => ['FMIPA', 'Ketua Komisi B Bidang Nonakademik Senat Akademik Fakultas MIPA'],
        '197208171997022001' => ['FMIPA', 'Kepala UPT Laboratorium Terpadu'],
        '197301241999032001' => ['FMIPA', 'Ketua Pusat Studi Halal Research and Services (HRCS)'],
        '196906081997022001' => ['S_3_BIOLOGI', 'Ketua Program Studi S3 Biologi'],
        '197102211997022001' => ['S_2_BIOSAIN', 'Ketua Program Studi S2 Biosains'],
        '197205241999031002' => ['S_3_ILMU_LINGKUNGAN', 'Ketua Program Studi S3 Ilmu Lingkungan'],
        '196708131992031002' => ['S_1_MATEMATIKA', 'Ketua Program Studi S1 Matematika'],
        '196811101995121001' => ['S_1_MATEMATIKA', 'Kepala Laboratorium Matematika'],
        '197105111995121001' => ['S_1_STATISTIKA', 'Ketua Program Studi S1 Statistika'],
        '197808142005012002' => ['S_1_STATISTIKA', 'Kepala Laboratorium Statistika'],
        '196805081997021001' => ['FMIPA', 'Kepala Seksi Riset Pengembangan dan Hak Kekayaan Intelektual LPPM'],
        '196903032000031001' => ['FMIPA', 'Ketua Senat Akademik FMIPA'],
        '196908261999031001' => ['S_3_FISIKA', 'Ketua Program Studi S3 Fisika'],
        '197001281999031001' => ['FMIPA', 'Ketua Pusat Studi Inovasi Pendidikan Tinggi'],
        '197012062000032001' => ['FMIPA', 'Ketua Unit Penjaminan Mutu FMIPA'],
        '197305101999031002' => ['FMIPA', 'Kepala Unit Pengembangan Sistem Pembelajaran LPPMP'],
        '198006302005011001' => ['S_1_FISIKA', 'Ketua Program Studi S1 Fisika'],
        '197212071999032001' => ['FMIPA', 'Dekan Fakultas Matematika dan Ilmu Pengetahuan Alam'],
        '197306052000031001' => ['UNS_REKTORAT', 'Kepala Subdirektorat Evaluasi dan Remunerasi'],
        '197404192000032001' => ['S_2_KIMIA', 'Ketua Program Studi S2 Ilmu Kimia'],
        '197510102000032001' => ['UNS_REKTORAT', 'Wakil Rektor Bidang Akademik dan Penelitian'],
        '197903262005012001' => ['FMIPA', 'Kepala Seksi Akreditasi Jurnal Ilmiah LPPM'],
        '197906052005011001' => ['FMIPA', 'Kepala Unit Hilirisasi dan Komersialisasi Produk Penelitian LPPM'],
        '196808232000031001' => ['FMIPA', 'Kepala Seksi Sarana Prasarana'],
        '197112242000032001' => ['FMIPA', 'Koordinator Penelitian dan Pengabdian Masyarakat Fakultas (KPPMF) LPPM'],
        '198007052002121002' => ['S_1_BIOLOGI', 'Ketua Program Studi S1 Biologi'],
        '198110182003122002' => ['UNS_REKTORAT', 'Kepala Subdirektorat Layanan Internasional'],
        '197803192005011003' => ['S_1_FARMASI', 'Ketua Program Studi S1 Farmasi'],
        '198005102005012002' => ['FMIPA', 'Kepala Seksi Kerja Sama Internasional dan Pengembangan Program Internasionalisasi'],
        '198507172010121003' => ['FMIPA', 'Kepala Seksi Penjaminan Mutu Publikasi Ilmiah LPPM'],
        '198609072012121002' => ['FMIPA', 'Kepala Laboratorium Komputasi'],
        '197108312000031005' => ['FMIPA', 'Kepala Seksi Pengelolaan Sub Laboratorium'],
        '197208012000031001' => ['FMIPA', 'Ketua Pusat Penelitian dan Penanggulangan Bencana (PPPB)'],
        '198105182005012002' => ['S_1_FISIKA', 'Kepala Laboratorium Fisika'],
        '197608222005011001' => ['S_1_KIMIA', 'Kepala Laboratorium Kimia'],
        '198309182008121003' => ['S_1_KIMIA', 'Ketua Program Studi S1 Kimia'],
        '197504232008011009' => ['S_1_ILMU_LINGKUNGAN', 'Ketua Program Studi S1 Ilmu Lingkungan'],
        '197608122005012001' => ['S_1_BIOLOGI', 'Kepala Laboratorium Biologi'],
        '197012112005012001' => ['S_1_FARMASI', 'Kepala Laboratorium Farmasi'],
        '197604032005011001' => ['PROFESI_APOTEKER', 'Ketua Program Studi Profesi Apoteker'],
        '198005202005012002' => ['FMIPA', 'Wakil Dekan Bidang Kemahasiswaan dan Alumni Fakultas Matematika dan Ilmu Pengetahuan Alam (FMIPA)'],
        '198112042014042001' => ['S_1_FARMASI', 'Kepala Laboratorium Farmasi'],
        '197507311999032002' => ['FMIPA', 'Ketua Komisi A Bidang Akademik dan Penelitian Senat Akademik Fakultas MIPA'],
        '199412222019031004' => ['S_1_ILMU_LINGKUNGAN', 'Kepala Laboratorium Ilmu Lingkungan'],
        '198204152006041020' => ['BAGIAN_TATA_USAHA_FMIPA', 'Kepala Bagian Tata Usaha Fak. Matematika dan IPA'],
        '197910202009102002' => ['SUBBAGIAN_AKADEMIK_FMIPA', 'Kepala Subbagian Akademik FMIPA'],
        '197001051993031002' => ['SUBBAGIAN_NONAKADEMIK_FMIPA', 'Kepala Subbagian Nonakademik FMIPA'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $notFound = [];
        $assigned = 0;

        $run = function () use (&$notFound, &$assigned) {
            $uns = Organisasi::where('kode', 'UNS')->firstOrFail();
            $fakultas = UnitKerja::where('kode', 'FMIPA')->firstOrFail();

            UnitKerja::firstOrCreate(
                ['kode' => 'S_3_ILMU_LINGKUNGAN'],
                ['organisasi_id' => $uns->id, 'parent_id' => $fakultas->id, 'nama' => 'S-3 Ilmu Lingkungan', 'jenis_unit' => JenisUnitKerja::Prodi, 'is_active' => true],
            );
            UnitKerja::firstOrCreate(
                ['kode' => 'UNS_REKTORAT'],
                ['organisasi_id' => $uns->id, 'parent_id' => null, 'nama' => 'Rektorat Universitas Sebelas Maret', 'jenis_unit' => JenisUnitKerja::Biro, 'is_active' => true],
            );

            foreach (self::MAP as $nip => [$kodeUnit, $namaJabatan]) {
                $pegawai = Pegawai::where('nip', $nip)->first();
                $unit = UnitKerja::where('kode', $kodeUnit)->first();
                $jabatan = Jabatan::where('nama', $namaJabatan)->first();

                if (! $pegawai || ! $unit || ! $jabatan) {
                    $notFound[] = "NIP {$nip} -> [{$kodeUnit}] {$namaJabatan} (".
                        (! $pegawai ? 'pegawai ' : '').(! $unit ? 'unit ' : '').(! $jabatan ? 'jabatan' : '').' tidak ditemukan)';

                    continue;
                }

                Penempatan::updateOrCreate(
                    ['pegawai_id' => $pegawai->id, 'unit_kerja_id' => $unit->id, 'jabatan_id' => $jabatan->id],
                    ['tgl_mulai' => now(), 'is_posisi_utama' => false, 'status' => StatusPenempatan::Aktif],
                );
                $assigned++;
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

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Assigned: {$assigned} / ".count(self::MAP));
        foreach ($notFound as $n) {
            $this->warn($n);
        }

        return self::SUCCESS;
    }
}
