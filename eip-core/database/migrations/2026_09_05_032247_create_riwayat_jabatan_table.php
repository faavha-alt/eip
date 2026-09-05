<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('riwayat_jabatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->string('jenis'); // App\Enums\JenisRiwayatJabatan: struktural|fungsional
            $table->string('jabatan_nama');
            $table->string('jabatan_detail')->nullable();
            $table->string('no_sk')->nullable();
            $table->date('tmt_awal')->nullable();
            $table->date('tmt_akhir')->nullable();
            $table->string('status')->nullable(); // struktural: PLH/PJD/PLT dst (dari SIMPEG)
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_jabatan');
    }
};
