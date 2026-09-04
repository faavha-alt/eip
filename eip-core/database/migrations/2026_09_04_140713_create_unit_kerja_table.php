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
        Schema::create('unit_kerja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('unit_kerja')->nullOnDelete();
            $table->foreignId('organisasi_id')->constrained('organisasi')->restrictOnDelete();
            $table->string('nama');
            $table->string('kode')->unique();
            $table->string('jenis_unit'); // App\Enums\JenisUnitKerja
            $table->foreignId('kepala_id')->nullable()->constrained('pegawai')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_kerja');
    }
};
