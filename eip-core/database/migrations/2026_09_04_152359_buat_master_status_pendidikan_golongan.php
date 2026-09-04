<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Naikkan status_kepegawaian, pendidikan_terakhir, golongan_ruang dari kolom
 * string/enum jadi tabel master tersendiri (pola sama dgn organisasi/
 * unit_kerja/jabatan) — bisa dikelola via UI nanti, bukan hardcode di kode.
 *
 * Urutan: buat tabel master + seed nilai referensi tetap -> tambah kolom FK
 * nullable ke pegawai -> backfill dari kolom string lama -> hapus kolom lama.
 * Nilai golongan_ruang sumber "X" (placeholder non-PNS, bukan golongan
 * sungguhan) sengaja TIDAK di-seed & tidak match apapun -> jadi null di FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_kepegawaian', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pendidikan', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->unsignedInteger('jenjang')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('golongan_ruang', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama')->nullable();
            $table->unsignedInteger('tingkat')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();

        DB::table('status_kepegawaian')->insert(collect([
            'pns' => 'PNS',
            'non_pns' => 'Non PNS',
            'kontrak_profesional' => 'Kontrak Profesional',
            'purna_tugas' => 'Purna Tugas',
        ])->map(fn ($nama, $kode) => ['kode' => $kode, 'nama' => $nama, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now])->values()->all());

        DB::table('pendidikan')->insert(collect([
            'sma_slta' => ['SMA/SLTA', 1],
            'd3' => ['D3', 2],
            's1' => ['S1', 3],
            'profesi' => ['Profesi', 4],
            's2' => ['S2', 5],
            's3' => ['S3', 6],
        ])->map(fn ($v, $kode) => ['kode' => $kode, 'nama' => $v[0], 'jenjang' => $v[1], 'is_active' => true, 'created_at' => $now, 'updated_at' => $now])->values()->all());

        DB::table('golongan_ruang')->insert(collect([
            'II/c' => 1, 'II/d' => 2,
            'III/a' => 3, 'III/b' => 4, 'III/c' => 5, 'III/d' => 6,
            'IV/a' => 7, 'IV/b' => 8, 'IV/c' => 9, 'IV/d' => 10, 'IV/e' => 11,
        ])->map(fn ($tingkat, $kode) => ['kode' => $kode, 'nama' => null, 'tingkat' => $tingkat, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now])->values()->all());

        Schema::table('pegawai', function (Blueprint $table) {
            $table->foreignId('status_kepegawaian_id')->nullable()->after('status_kepegawaian')
                ->constrained('status_kepegawaian')->nullOnDelete();
            $table->foreignId('pendidikan_terakhir_id')->nullable()->after('pendidikan_terakhir')
                ->constrained('pendidikan')->nullOnDelete();
            $table->foreignId('golongan_ruang_id')->nullable()->after('golongan_ruang')
                ->constrained('golongan_ruang')->nullOnDelete();
        });

        foreach (DB::table('status_kepegawaian')->get() as $s) {
            DB::table('pegawai')->where('status_kepegawaian', $s->kode)->update(['status_kepegawaian_id' => $s->id]);
        }
        foreach (DB::table('pendidikan')->get() as $p) {
            DB::table('pegawai')->where('pendidikan_terakhir', $p->kode)->update(['pendidikan_terakhir_id' => $p->id]);
        }
        foreach (DB::table('golongan_ruang')->get() as $g) {
            DB::table('pegawai')->where('golongan_ruang', $g->kode)->update(['golongan_ruang_id' => $g->id]);
        }

        Schema::table('pegawai', function (Blueprint $table) {
            $table->dropColumn(['status_kepegawaian', 'pendidikan_terakhir', 'golongan_ruang']);
        });
    }

    public function down(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            $table->string('status_kepegawaian')->nullable()->after('status_kepegawaian_id');
            $table->string('pendidikan_terakhir')->nullable()->after('pendidikan_terakhir_id');
            $table->string('golongan_ruang')->nullable()->after('golongan_ruang_id');
        });

        DB::table('pegawai as p')
            ->join('status_kepegawaian as s', 'p.status_kepegawaian_id', '=', 's.id')
            ->update(['p.status_kepegawaian' => DB::raw('s.kode')]);
        DB::table('pegawai as p')
            ->join('pendidikan as d', 'p.pendidikan_terakhir_id', '=', 'd.id')
            ->update(['p.pendidikan_terakhir' => DB::raw('d.kode')]);
        DB::table('pegawai as p')
            ->join('golongan_ruang as g', 'p.golongan_ruang_id', '=', 'g.id')
            ->update(['p.golongan_ruang' => DB::raw('g.kode')]);

        Schema::table('pegawai', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_kepegawaian_id');
            $table->dropConstrainedForeignId('pendidikan_terakhir_id');
            $table->dropConstrainedForeignId('golongan_ruang_id');
        });

        Schema::dropIfExists('golongan_ruang');
        Schema::dropIfExists('pendidikan');
        Schema::dropIfExists('status_kepegawaian');
    }
};
