<?php

namespace App\Http\Controllers;

use App\Enums\JenisJabatan;
use App\Enums\JenisPegawai;
use App\Enums\JenisUnitKerja;
use App\Models\Jabatan;
use App\Models\Pegawai;
use App\Models\UnitKerja;
use Illuminate\View\View;

/**
 * Ringkasan master data EIP Core (gaya AeroDeck — lihat skill
 * ui-dashboard-aerodeck). Semua angka dihitung live dari DB, bukan contoh.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user()->load(['pegawai', 'roles']);

        $totalPegawai = Pegawai::count();
        $aktifPegawai = Pegawai::where('is_active', true)->count();
        $nonaktifPegawai = $totalPegawai - $aktifPegawai;
        $aktifPct = $totalPegawai > 0 ? (int) round($aktifPegawai / $totalPegawai * 100) : 0;

        $dosenCount = Pegawai::where('jenis_pegawai', JenisPegawai::TenagaPendidik)->count();
        $tendikCount = Pegawai::where('jenis_pegawai', JenisPegawai::TenagaKependidikan)->count();

        $totalUnit = UnitKerja::count();
        $prodiCount = UnitKerja::where('jenis_unit', JenisUnitKerja::Prodi)->count();

        $totalJabatan = Jabatan::count();
        $strukturalCount = Jabatan::where('jenis', JenisJabatan::Struktural)->count();
        $fungsionalCount = $totalJabatan - $strukturalCount;

        $metrics = [
            'pegawai' => [
                'label' => 'Total Pegawai', 'value' => $totalPegawai,
                'badge' => "{$aktifPct}% Aktif", 'badge_color' => 'emerald',
                'foot_left' => "{$aktifPegawai} aktif", 'foot_right' => "{$nonaktifPegawai} nonaktif",
            ],
            'komposisi' => [
                'label' => 'Dosen vs Tendik', 'value' => $dosenCount, 'unit' => 'dosen',
                'badge' => "{$tendikCount} tendik", 'badge_color' => 'indigo',
                'foot_left' => 'Fakultas MIPA', 'foot_right' => "{$totalPegawai} total",
            ],
            'unit' => [
                'label' => 'Unit Kerja', 'value' => $totalUnit,
                'badge' => "{$prodiCount} prodi", 'badge_color' => 'amber',
                'foot_left' => ($totalUnit - $prodiCount).' non-prodi', 'foot_right' => 'UNS + FMIPA',
            ],
            'jabatan' => [
                'label' => 'Jabatan', 'value' => $totalJabatan,
                'badge' => "{$strukturalCount} struktural", 'badge_color' => 'rose',
                'foot_left' => "{$fungsionalCount} fungsional", 'foot_right' => 'katalog lengkap',
            ],
        ];

        $units = UnitKerja::query()
            ->withCount([
                'penempatan as total_pegawai' => fn ($q) => $q->where('is_posisi_utama', true),
                'penempatan as dosen_pegawai' => fn ($q) => $q->where('is_posisi_utama', true)
                    ->whereHas('pegawai', fn ($q2) => $q2->where('jenis_pegawai', JenisPegawai::TenagaPendidik->value)),
                'penempatan as tendik_pegawai' => fn ($q) => $q->where('is_posisi_utama', true)
                    ->whereHas('pegawai', fn ($q2) => $q2->where('jenis_pegawai', JenisPegawai::TenagaKependidikan->value)),
            ])
            ->with('kepala')
            ->orderByDesc('total_pegawai')
            ->get();

        $maxPegawaiPerUnit = max(1, (int) $units->max('total_pegawai'));

        $topUnits = $units->take(10)->values();
        $chartDatasets = [
            'Semua' => ['labels' => $topUnits->pluck('nama'), 'data' => $topUnits->pluck('total_pegawai')],
            'Dosen' => ['labels' => $topUnits->pluck('nama'), 'data' => $topUnits->pluck('dosen_pegawai')],
            'Tendik' => ['labels' => $topUnits->pluck('nama'), 'data' => $topUnits->pluck('tendik_pegawai')],
        ];

        $completeness = [
            ['label' => 'Belum ada NUPTK', 'count' => Pegawai::whereNull('nuptk')->count()],
            ['label' => 'Belum ada No. HP', 'count' => Pegawai::whereNull('no_hp')->count()],
            ['label' => 'Belum ada jenis kelamin', 'count' => Pegawai::whereNull('jenis_kelamin')->count()],
            ['label' => 'Belum ada golongan/ruang', 'count' => Pegawai::whereNull('golongan_ruang_id')->count()],
        ];

        $sidebarStats = ['aktif_pct' => $aktifPct];

        return view('dashboard', compact(
            'user', 'sidebarStats', 'metrics', 'units', 'maxPegawaiPerUnit',
            'chartDatasets', 'completeness', 'totalPegawai',
        ));
    }
}
