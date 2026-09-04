<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom tambahan berdasarkan struktur data pegawai nyata FMIPA UNS
 * (docs/data_pegawai.xlsx) — tidak ada di rancangan skema awal:
 * NPWP, no. seri Karpeg, pendidikan terakhir, golongan/ruang + TMT,
 * jenis pegawai (dosen/tendik, dimensi terpisah dari status_kepegawaian),
 * dan id_sumber (kunci baris dari sistem sumber, utk idempotensi impor).
 * jenis_kelamin dibuat nullable krn sumber data tidak menyediakannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            $table->string('id_sumber')->nullable()->unique()->after('id');
            // Tidak unique: NPWP di sumber tersimpan sbg angka Excel, rawan
            // kehilangan presisi digit belakang (>15 digit) shg 2 NPWP
            // berbeda bisa "collide" jadi sama. NIP/NIK tetap unique.
            $table->string('npwp')->nullable()->after('nik');
            $table->string('no_seri_kepeg')->nullable()->after('id_simpeg');
            $table->string('jenis_pegawai')->nullable()->after('status_kepegawaian'); // App\Enums\JenisPegawai
            $table->string('pendidikan_terakhir')->nullable()->after('jenis_pegawai'); // App\Enums\PendidikanTerakhir
            $table->string('golongan_ruang')->nullable()->after('pendidikan_terakhir');
            $table->date('tmt_golongan')->nullable()->after('golongan_ruang');
        });

        Schema::table('pegawai', function (Blueprint $table) {
            $table->string('jenis_kelamin')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            $table->dropColumn([
                'id_sumber', 'npwp', 'no_seri_kepeg', 'jenis_pegawai',
                'pendidikan_terakhir', 'golongan_ruang', 'tmt_golongan',
            ]);
        });

        Schema::table('pegawai', function (Blueprint $table) {
            $table->string('jenis_kelamin')->nullable(false)->change();
        });
    }
};
