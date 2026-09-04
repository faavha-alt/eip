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
        Schema::create('penempatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->foreignId('unit_kerja_id')->constrained('unit_kerja')->restrictOnDelete();
            $table->foreignId('jabatan_id')->constrained('jabatan')->restrictOnDelete();
            $table->date('tgl_mulai');
            $table->date('tgl_selesai')->nullable();
            $table->boolean('is_posisi_utama')->default(false);
            $table->string('status')->default('aktif'); // App\Enums\StatusPenempatan
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penempatan');
    }
};
