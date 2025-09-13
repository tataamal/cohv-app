<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ProductionTData1;
use App\Models\ProductionTData2;
use App\Models\ProductionTData3;
use App\Models\ProductionTData4;
use App\Models\Kode;
use App\Models\SapUser;

class adminController extends Controller
{
    public function index(Request $request, $kode)
    {
        // =================================================================
        // DATA UNTUK CHART PERTAMA (BAR CHART - WORKCENTER)
        // Query ini sudah benar dan spesifik per $kode. Tidak ada perubahan.
        // =================================================================

        // 1a) Buat query untuk mendapatkan daftar induk semua ARBPL unik untuk plant ini
        $allWcQuery = DB::table('production_t_data1')
            ->select('ARBPL')
            ->distinct()
            ->where('WERKSX', $kode);

        // 1b) Query utama: Mulai dari daftar induk, lalu LEFT JOIN ke data transaksi
        $statsPerWc = DB::table(DB::raw("({$allWcQuery->toSql()}) as master_wc"))
            ->mergeBindings($allWcQuery) // Penting untuk binding parameter
            ->leftJoin('production_t_data1 as trans_data', function ($join) use ($kode) {
                $join->on('master_wc.ARBPL', '=', 'trans_data.ARBPL')
                    ->where('trans_data.WERKSX', '=', $kode) // Pastikan join juga difilter berdasarkan plant
                    ->whereRaw("NULLIF(TRIM(trans_data.AUFNR), '') IS NOT NULL");
            })
            ->selectRaw("
                CASE
                    WHEN NULLIF(TRIM(master_wc.ARBPL), '') IS NULL
                    THEN 'Eksternal Workcenter'
                    ELSE master_wc.ARBPL
                END AS ARBPL_LABEL,
                COUNT(DISTINCT trans_data.AUFNR) AS pro_count,
                COALESCE(SUM(trans_data.CPCTYX), 0) AS total_capacity
            ")
            ->groupBy('ARBPL_LABEL')
            ->orderBy('ARBPL_LABEL', 'asc')
            ->get();

        // 2) Siapkan labels dan data untuk kedua dataset (Tidak ada perubahan di sini)
        $labels          = $statsPerWc->pluck('ARBPL_LABEL')->values()->all();
        $datasetPro      = $statsPerWc->pluck('pro_count')->map(fn($v) => (int)$v)->values()->all();
        $datasetCapacity = $statsPerWc->pluck('total_capacity')->map(fn($v) => (float)$v)->values()->all();

        $targetUrls = collect($labels)->map(function ($wcLabel) use ($kode) {
            return route('wc.details', [
                'kode' => $kode,
                'wc' => $wcLabel]);
        })->all();

        // 3) Definisikan kedua dataset untuk chart (Tidak ada perubahan di sini)
        $datasets = [
            [
                'label'           => 'Jumlah PRO',
                'data'            => $datasetPro,
                'backgroundColor' => 'rgba(59, 130, 246, 0.6)',
                'borderColor'     => 'rgba(37, 99, 235, 1)',
                'borderWidth'     => 1,
                'borderRadius'    => 4,
            ],
            [
                'label'           => 'Jumlah Kapasitas',
                'data'            => $datasetCapacity,
                'backgroundColor' => 'rgba(249, 115, 22, 0.6)',
                'borderColor'     => 'rgba(234, 88, 12, 1)',
                'borderWidth'     => 1,
                'borderRadius'    => 4,
            ],
        ];

        // =================================================================
        // DATA UNTUK CHART KEDUA (DOUGHNUT CHART - PER STATUS DI PLANT INI)
        // DIUBAH: Query ini sekarang menghitung jumlah PRO berdasarkan status ('REL', 'CNF', dll)
        // HANYA untuk plant ($kode) yang sedang aktif.
        // =================================================================
        $statsByStatus = DB::table('production_t_data1')
            ->where('WERKSX', $kode) // Filter utama berdasarkan plant
            ->whereRaw("NULLIF(TRIM(AUFNR), '') IS NOT NULL")
            ->select('STATS', DB::raw('COUNT(DISTINCT AUFNR) as pro_count_by_status'))
            ->groupBy('STATS')
            ->orderBy('STATS', 'asc')
            ->get();

        $doughnutChartLabels = $statsByStatus->pluck('STATS')->values()->all();
        $doughnutChartDataset = $statsByStatus->pluck('pro_count_by_status')->values()->all();

        $doughnutChartDatasets = [
            [
                'label' => 'Jumlah PRO per Status',
                'data'  => $doughnutChartDataset,
                'backgroundColor' => [
                    'rgba(255, 99, 132, 0.7)',  // Merah
                    'rgba(54, 162, 235, 0.7)', // Biru
                    'rgba(255, 206, 86, 0.7)', // Kuning
                    'rgba(75, 192, 192, 0.7)',  // Hijau
                    'rgba(153, 102, 255, 0.7)',// Ungu
                    'rgba(255, 159, 64, 0.7)' // Oranye
                ],
                'borderColor' => '#ffffff',
                'borderWidth' => 2,
            ]
        ];

        $nama_bagian = Kode::where('kode', $kode)->value('nama_bagian');
        
        // =================================================================
        // DATA KARDINAL (JUMLAH TOTAL)
        // DIUBAH: Semua query count sekarang ditambahkan ->where('WERKSX', $kode)
        // agar spesifik per plant.
        // Asumsi: Semua tabel (TData1 s/d TData4) memiliki kolom 'WERKSX'.
        // =================================================================

        $searchReservasi = $request->input('search_reservasi');

        $TData1 = ProductionTData1::where('WERKSX', $kode)->count();
        $TData2 = ProductionTData2::where('WERKSX', $kode)->count();
        $TData3 = ProductionTData3::where('WERKSX', $kode)->count();
        $TData4 = ProductionTData4::where('WERKSX', $kode)
        ->when($searchReservasi, function ($query, $term) {
            // Lakukan pencarian jika $searchReservasi tidak kosong
            return $query->where(function($q) use ($term) {
                $q->where('RSNUM', 'like', "%{$term}%")
                  ->orWhere('MATNR', 'like', "%{$term}%")
                  ->orWhere('MAKTX', 'like', "%{$term}%");
            });
        })->get();

        $outstandingReservasi = ProductionTData4::where('WERKSX', $kode)
                                    ->whereColumn('KALAB', '<', 'BDMNG')
                                    ->count();
        $nama_bagian = Kode::where('kode', $kode)->value('nama_bagian');

        return view('Admin.dashboard', [
            'TData1' => $TData1, 
            'TData2' => $TData2, 
            'TData3' => $TData3, 
            'TData4' => $TData4,
            'outstandingReservasi' => $outstandingReservasi, 
            'labels' => $labels, 
            'datasets' => $datasets,
            'targetUrls' => $targetUrls,
            'doughnutChartLabels' => $doughnutChartLabels,
            'doughnutChartDatasets' => $doughnutChartDatasets,
            'kode' => $kode,
            'nama_bagian' => $nama_bagian,
        ]);  
    }

    public function AdminDashboard()
    {
        // Inisialisasi koleksi kosong untuk menampung data plant
        $plants = collect();

        // Pastikan pengguna sudah login sebelum mengambil data
        if (Auth::check()) {
            $user = Auth::user();
            $sapUser = null;

            // Logika untuk menemukan SapUser berdasarkan peran (role) pengguna
            if ($user->role === 'admin') {
                // 1. Ambil 'sap_id' dari email pengguna
                $sapId = str_replace('@kmi.local', '', $user->email);
                // 2. Cari SapUser berdasarkan sap_id
                $sapUser = SapUser::where('sap_id', $sapId)->first();
            } 

            // Jika SapUser ditemukan, ambil semua 'kodes' (plant) yang berelasi dengannya
            if ($sapUser) {
                $plants = $sapUser->kode()->get();
            }
            $allUsers = SapUser::orderBy('nama')->get();
        }
        
        // Kirim data 'plants' ke view 'dashboard-landing'
        return view('dashboard', [
            'plants' => $plants, // asumsikan ini sudah ada
            'allUsers' => $allUsers
        ]);
    }
}
