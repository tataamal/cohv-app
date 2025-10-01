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
use Carbon\Carbon;

class adminController extends Controller
{
    public function index(Request $request, $kode)
    {
        // =================================================================
        // DATA UNTUK CHART PERTAMA (BAR CHART - WORKCENTER)
        // =================================================================
        $allWcQuery = DB::table('workcenters')
            ->select('kode_wc', 'description')
            ->where('werksx', $kode);

        $statsPerWc = DB::table(DB::raw("({$allWcQuery->toSql()}) as master_wc"))
            ->mergeBindings($allWcQuery)
            ->leftJoin('production_t_data1 as trans_data', 'master_wc.kode_wc', '=', 'trans_data.ARBPL')
            ->selectRaw("
                master_wc.kode_wc AS wc_label,
                master_wc.description AS wc_description,
                COUNT(DISTINCT trans_data.AUFNR) AS pro_count,
                COALESCE(SUM(trans_data.CPCTYX), 0) AS total_capacity
            ")
            ->groupBy('master_wc.kode_wc', 'master_wc.description')
            ->orderBy('master_wc.kode_wc', 'asc')
            ->get();

        $labels = $statsPerWc->pluck('wc_label')->all();
        $descriptions = $statsPerWc->pluck('wc_description')->all();
        $datasetPro = $statsPerWc->pluck('pro_count')->all();
        $datasetCapacity = $statsPerWc->pluck('total_capacity')->all();

        $targetUrls = collect($labels)->map(function ($wcLabel) use ($kode) {
            return route('wc.details', ['kode' => $kode, 'wc' => $wcLabel]);
        })->all();

        $datasets = [
            [
                'label' => 'PRO Count',
                'data' => $datasetPro,
                'descriptions' => $descriptions,
                'backgroundColor' => 'rgba(59, 130, 246, 0.6)',
                'borderColor' => 'rgba(37, 99, 235, 1)',
                'borderWidth' => 1,
                'borderRadius' => 4,
                'satuan' => 'PRO'
            ],
            [
                'label' => 'Capacity Count',
                'data' => $datasetCapacity,
                'descriptions' => $descriptions,
                'backgroundColor' => 'rgba(249, 115, 22, 0.6)',
                'borderColor' => 'rgba(234, 88, 12, 1)',
                'borderWidth' => 1,
                'borderRadius' => 4,
                'satuan' => 'Jam'
            ],
        ];

        // =================================================================
        // DATA UNTUK CHART KEDUA (DOUGHNUT CHART - PER STATUS DI PLANT INI)
        // =================================================================
        $statsByStatus = DB::table('production_t_data1')
            ->where('WERKSX', $kode)
            ->whereRaw("NULLIF(TRIM(AUFNR), '') IS NOT NULL")
            ->select('STATS', DB::raw('COUNT(DISTINCT AUFNR) as pro_count_by_status'))
            ->groupBy('STATS')
            ->orderBy('STATS', 'asc')
            ->get();

        $doughnutChartLabels = $statsByStatus->pluck('STATS')->values()->all();
        $doughnutChartDataset = $statsByStatus->pluck('pro_count_by_status')->values()->all();

        $doughnutChartDatasets = [
            [
                'label' => 'PRO Count by Status',
                'data' => $doughnutChartDataset,
                'backgroundColor' => [
                    'rgba(255, 99, 132, 0.7)', 'rgba(54, 162, 235, 0.7)', 'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)', 'rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)'
                ],
                'borderColor' => '#ffffff',
                'borderWidth' => 2,
            ]
        ];
        
        // =================================================================
        // DATA UNTUK CHART KETIGA (LOLLIPOP CHART - TOP 5 WORKCENTER)
        // =================================================================
        $topWcByCapacity = DB::table('production_t_data1 as t1')
            ->join('workcenters as wc', 't1.ARBPL', '=', 'wc.kode_wc')
            ->select(
                't1.ARBPL',
                'wc.description',
                DB::raw('SUM(t1.CPCTYX) as total_capacity')
            )
            ->where('t1.WERKSX', $kode)
            ->whereNotNull('t1.ARBPL')
            ->groupBy('t1.ARBPL', 'wc.description')
            ->orderByDesc('total_capacity')
            ->limit(5)
            ->get();

        $lolipopChartLabels = $topWcByCapacity->pluck('ARBPL')->all();
        $lolipopChartData = $topWcByCapacity->pluck('total_capacity')->all();
        $lolipopChartDescriptions = $topWcByCapacity->pluck('description')->all();

        $lolipopChartDatasets = [
            [
                'label' => 'Distribusi Kapasitas',
                'data' => $lolipopChartData,
                'descriptions' => $lolipopChartDescriptions,
                'satuan' => 'Jam',
                'backgroundColor' => [
                    'rgba(255, 166, 158, 0.8)', 'rgba(174, 217, 224, 0.8)', 'rgba(204, 204, 255, 0.8)',
                    'rgba(255, 225, 179, 0.8)', 'rgba(181, 234, 215, 0.8)',
                ],
                'borderColor' => '#ffffff',
                'borderWidth' => 2,
            ]
        ];
        
        // =================================================================
        // PENGAMBILAN DATA KARDINAL & TABEL
        // =================================================================
        $nama_bagian = Kode::where('kode', $kode)->value('nama_bagian');
        $kategori = Kode::where('kode', $kode)->value('kategori');
        
        $searchReservasi = $request->input('search_reservasi');

        $TData1 = ProductionTData1::where('WERKSX', $kode)->count();
        $TData2 = ProductionTData2::where('WERKSX', $kode)->count();
        $TData3 = ProductionTData3::where('WERKSX', $kode)->count();
        $TData4 = ProductionTData4::where('WERKSX', $kode)
            ->when($searchReservasi, function ($query, $term) {
                return $query->where(function($q) use ($term) {
                    $q->where('RSNUM', 'like', "%{$term}%")
                      ->orWhere('MATNR', 'like', "%{$term}%")
                      ->orWhere('MAKTX', 'like', "%{$term}%");
                });
            })->get()
            ->map(function ($item) {
                $item->MAKTX = trim(preg_replace('/\s+/', ' ', $item->MAKTX ?? ''));
                return $item;
            });
        
        $outstandingReservasi = ProductionTData4::where('WERKSX', $kode)
                                      ->whereColumn('KALAB', '<', 'BDMNG')
                                      ->count();
        $today = Carbon::today();
        $ongoingPRO = ProductionTData3::where('WERKSX', $kode)
                                ->whereDate('GSTRP', $today)
                                ->count();

        // Data dari TData3 (Ongoing) untuk ditampilkan di tabel
        $ongoingProData = ProductionTData3::where('WERKSX', $kode)
                                ->whereDate('GSTRP', $today)
                                ->latest('AUFNR')
                                ->get();

        // [BARU] Data dari TData3 (Keseluruhan) untuk tabel Total PRO
        $allProData = ProductionTData3::where('WERKSX', $kode)
                                ->latest('AUFNR')
                                ->get();

        // Data dari TData2 untuk ditampilkan sebagai tabel Sales Order
        $salesOrderData = ProductionTData2::where('WERKSX', $kode)
                                ->latest('KDAUF') // Diurutkan berdasarkan nomor SO terbaru
                                ->get();

        // =================================================================
        // MENGIRIM SEMUA DATA KE VIEW
        // =================================================================
        return view('Admin.dashboard', [
            // Data untuk kardinal (summary cards)
            'TData1' => $TData1, 
            'TData2' => $TData2, 
            'TData3' => $TData3, 
            'TData4' => $TData4,
            'outstandingReservasi' => $outstandingReservasi,
            'ongoingPRO' => $ongoingPRO,
            'ongoingProData' => $ongoingProData,
            'salesOrderData' => $salesOrderData, 
            'allProData' => $allProData, // DATA BARU
            
            // Data untuk Bar Chart
            'labels' => $labels, 
            'datasets' => $datasets,
            'targetUrls' => $targetUrls,
            
            // Data untuk Doughnut Chart
            'doughnutChartLabels' => $doughnutChartLabels,
            'doughnutChartDatasets' => $doughnutChartDatasets,
            
            // Data untuk Lollipop Chart
            'lolipopChartLabels' => $lolipopChartLabels,
            'lolipopChartDatasets' => $lolipopChartDatasets,
            
            // Info Halaman
            'kode' => $kode,
            'kategori' => $kategori,
            'nama_bagian' => $nama_bagian,
        ]);  
    }


    public function AdminDashboard()
    {
        // Inisialisasi koleksi kosong untuk menampung data
        $plants = collect();
        $allUsers = collect(); // Inisialisasi allUsers juga

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

            // Jika SapUser ditemukan, ambil dan filter 'kodes' (plant) yang berelasi
            if ($sapUser) {
                // Ambil SEMUA kode yang berelasi terlebih dahulu
                $allRelatedKodes = $sapUser->kode()->get();

                // FIX: Filter koleksi untuk mendapatkan hanya nilai 'kode' yang unik.
                // Metode unique('kode') akan mengambil item pertama untuk setiap 'kode'
                // dan mengabaikan duplikat selanjutnya.
                $plants = $allRelatedKodes->unique('kode');
            }
            
            // Ambil semua user untuk keperluan lain di dashboard
            $allUsers = SapUser::orderBy('nama')->get();
        }
        
        // Kirim data 'plants' yang sudah unik ke view
        return view('dashboard', [
            'plants' => $plants,
            'allUsers' => $allUsers
        ]);
    }
}
