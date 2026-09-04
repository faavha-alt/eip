<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fondasi auth (docs/03-autentikasi-sso.md): login Google OIDC (manusia) +
 * RBAC terpusat + service client (mesin, via Sanctum token) utk app lain
 * baca API EIP Core.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('id');
            $table->string('avatar')->nullable()->after('email');
            $table->foreignId('pegawai_id')->nullable()->after('avatar')
                ->constrained('pegawai')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('pegawai_id');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('password')->nullable()->change();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique(); // mis. admin-gaji, approver-pengadaan
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'role_id']);
        });

        Schema::create('service_clients', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique(); // mis. kepegawaian, gaji, aset, logistik, wa-blast
            $table->string('nama');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_clients');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->string('password')->nullable(false)->change();
            $table->dropConstrainedForeignId('pegawai_id');
            $table->dropColumn(['google_id', 'avatar', 'is_active', 'last_login_at']);
        });
    }
};
