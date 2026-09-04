<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pelengkap master pegawai berdasarkan riset referensi resmi (BKN SIMPEG/DRH,
 * SISTER Kemdiktisaintek, KP4 tunjangan keluarga) — lihat PROGRESS.md.
 * Semua kolom/tabel di sini NULLABLE / boleh kosong: skema disiapkan lebih
 * dulu, datanya menyusul kalau/kapan tersedia — tidak wajib diisi sekarang.
 *
 * - Kolom baru langsung di `pegawai` (fakta 1:1, "nilai terkini").
 * - 4 tabel riwayat/dokumen baru (1:banyak) — melengkapi, BUKAN menggantikan
 *   kolom "terkini" di pegawai (pola BKN: nilai terkini + riwayat perubahan
 *   terpisah, tiap perubahan = baris baru, bukan menimpa).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            // NUPTK: pengganti resmi NIDN/NIDK/NUP sejak pertengahan 2024,
            // identitas dosen yg berlaku skrg utk SISTER/PDDIKTI/sertifikasi.
            $table->string('nuptk')->nullable()->unique()->after('id_simpeg');
            $table->string('agama')->nullable()->after('jenis_kelamin'); // App\Enums\Agama
            $table->string('status_perkawinan')->nullable()->after('agama'); // App\Enums\StatusPerkawinan
            $table->text('alamat_domisili')->nullable()->after('tempat_lahir');
            $table->date('tmt_cpns')->nullable()->after('tanggal_masuk');
            $table->date('tmt_pns')->nullable()->after('tmt_cpns');
            $table->string('no_bpjs_kesehatan')->nullable()->after('npwp');
            $table->string('no_bpjs_ketenagakerjaan')->nullable()->after('no_bpjs_kesehatan');
            $table->string('no_taspen')->nullable()->after('no_bpjs_ketenagakerjaan');
        });

        Schema::create('riwayat_pendidikan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->foreignId('pendidikan_id')->constrained('pendidikan')->restrictOnDelete();
            $table->string('nama_institusi')->nullable();
            $table->string('program_studi')->nullable();
            $table->unsignedSmallInteger('tahun_lulus')->nullable();
            $table->string('no_ijazah')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('riwayat_pangkat_golongan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->foreignId('golongan_ruang_id')->constrained('golongan_ruang')->restrictOnDelete();
            $table->date('tmt');
            $table->string('no_sk')->nullable();
            $table->date('tgl_sk')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('keluarga_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->string('hubungan'); // App\Enums\HubunganKeluarga
            $table->string('nama');
            $table->string('jenis_kelamin')->nullable(); // App\Enums\JenisKelamin
            $table->date('tanggal_lahir')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->boolean('status_tanggungan')->default(true); // dasar hitung tunjangan KP4
            $table->string('no_hp')->nullable(); // rangkap kontak darurat
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dokumen_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->string('jenis'); // App\Enums\JenisDokumenPegawai
            $table->string('nomor_dokumen')->nullable();
            $table->date('tanggal_dokumen')->nullable();
            $table->string('file_path')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_pegawai');
        Schema::dropIfExists('keluarga_pegawai');
        Schema::dropIfExists('riwayat_pangkat_golongan');
        Schema::dropIfExists('riwayat_pendidikan');

        Schema::table('pegawai', function (Blueprint $table) {
            $table->dropColumn([
                'nuptk', 'agama', 'status_perkawinan', 'alamat_domisili',
                'tmt_cpns', 'tmt_pns', 'no_bpjs_kesehatan',
                'no_bpjs_ketenagakerjaan', 'no_taspen',
            ]);
        });
    }
};
