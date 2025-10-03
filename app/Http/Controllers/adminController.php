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
use App\Models\ProductionTData;
use App\Models\SapUser;
use App\Models\workcenter;
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
        // Pastikan hanya PRO dengan STATS = 'REL' yang dihitung
            $ongoingPRO = ProductionTData3::where('WERKSX', $kode)
                ->whereDate('GSTRP', $today)
                ->where('STATS', 'REL') // <-- MODIFIKASI INI
                ->count();

        // Data dari TData3 (Ongoing) untuk ditampilkan di tabel
            $ongoingProData = ProductionTData3::where('WERKSX', $kode)
                ->whereDate('GSTRP', $today)
                ->where('STATS', 'REL') // <-- MODIFIKASI INI
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

    public function getProDetails($status, Request $request)
    {
        // Ambil filter tambahan dari request (jika ada)
        // Asumsi: 'kode' dikirimkan dari AJAX sebagai $request->input('kode')
        $kode_plant = $request->input('kode'); // Filter baru: Kode Plant/WERKSX
        $nama_bagian = $request->input('nama_bagian');
        $kategori = $request->input('kategori');

        // 1. Ambil data dengan SELECT EKSPLISIT dan ALIAS
        $proDetails = DB::table('production_t_data3')
            ->select(
                // Alias ke snake_case untuk konsistensi di Blade
                'KDAUF as so_number',      // SO
                'KDPOS as so_item',       // SO Item
                'AUFNR as pro_number',    // PRO
                'MATNR as material_code', // MATERIAL
                'MAKTX as description',   // DESKRIPSI
                'PWWRK as plant',         // PLANT
                'DISPO as mrp_controller',// MRP
                'PSMNG as order_quantity',// QTY. ORDER
                'WEMNG as gr_quantity',   // QTY. GR
                DB::raw('(PSMNG - WEMNG) AS outs_gr_quantity'), // OUTS. GR (Kolom terhitung)
                'GSTRP as start_date',    // START DATE
                'GLTRP as end_date',      // END DATE
                'STATS as stats'          // STATS
            )
            ->where('WERKSX', $kode_plant) 
            ->where('STATS', $status)
            ->get();

        // 2. Buat Tampilan Tabel (View)
        $htmlTable = view('components.pro_table_detail', compact('proDetails'))->render();

        // 3. Kembalikan respons dalam format JSON
        return response()->json([
            'success' => true,
            'status' => $status,
            'count' => $proDetails->count(),
            'htmlTable' => $htmlTable
        ]);
    }

    public function showProDetail(Request $request, $proNumber, $werksCode, $view = null)
    {
        // Pastikan nomor PRO diubah ke uppercase
        $proNumber = strtoupper($proNumber);

        // Dapatkan data header dari Query Parameters
        $bagianName = $request->query('bagian');
        $categoriesName = $request->query('categories');
        
        // Asumsi: Kita hanya ingin data yang terkait dengan PRO yang dicari
        
        // 1. Ambil data TData3 (PRO) yang dicari
        $tdata3 = ProductionTData3::where('AUFNR', $proNumber)->first();
        
        if (!$tdata3) {
            // Jika PRO tidak ditemukan di TData3, kita tidak bisa melanjutkan
            abort(404, 'Production Order (PRO) not found in TData3.');
        }
        
        // Kunci relasi dari TData3
        $t2Key = trim($tdata3->KDAUF ?? '') . '-' . trim($tdata3->KDPOS ?? '');
        $t1Key = trim($tdata3->KUNNR ?? '') . '-' . trim($tdata3->NAME1 ?? '');

        // 2. Ambil data TData2 (Outstanding Order) dan TData1 (Buyer) spesifik
        $outstandingOrder = $this->getTData2BySalesOrder($tdata3->KDAUF, $tdata3->KDPOS);
        $buyerData = $this->getTData1BySalesOrder($tdata3->NAME1);
        
        // --- START: LOGIKA PENGAMBILAN DATA GLOBAL MINIMAL UNTUK STATE ---

        // A. Ambil semua TData3 (jika ada lebih dari satu PRO di SO yang sama)
        $allTData3Grouped = ProductionTData3::where('WERKSX', $werksCode)
            ->where(DB::raw("CONCAT(KDAUF, '-', KDPOS)"), $t2Key)
            ->get()
            ->groupBy(function($it) {
                return trim($it->KDAUF ?? '') . '-' . trim($it->KDPOS ?? '');
            });

        // B. Ambil TData2 yang terkait dengan Buyer ini
        $allTData2 = ProductionTData2::where('WERKSX', $werksCode)
            ->where('NAME1', $tdata3->NAME1)
            ->get()
            ->groupBy(function ($r) {
                return trim($r->KUNNR ?? '') . '-' . trim($r->NAME1 ?? '');
            });

        // C. Ambil TData1 (yang sama dengan data Buyer)
        // T_DATA Anda (List Buyer) seharusnya hanya memiliki satu baris untuk Buyer ini
        $tdata = ProductionTData::where('WERKSX', $werksCode)
            ->where('NAME1', $tdata3->NAME1)
            ->get();
            
        // D. Ambil TData4 dan TData1 (Routing) untuk PRO yang relevan
        $aufnrValues = $allTData3Grouped->flatten()->pluck('AUFNR')->filter()->unique(); // Semua PRO di SO ini
        $plnumValues = $allTData3Grouped->flatten()->pluck('PLNUM')->filter()->unique(); // Semua PLO di SO ini

        $allTData1ByAufnr = ProductionTData1::whereIn('AUFNR', $aufnrValues->values())->get()->groupBy('AUFNR');
        $allTData4ByAufnr = ProductionTData4::whereIn('AUFNR', $aufnrValues->values())->get()->groupBy('AUFNR');
        $allTData4ByPlnum = ProductionTData4::whereIn('PLNUM', $plnumValues->values())->get()->groupBy('PLNUM');
        
        // E. Ambil Work Center
        $workCenters = workcenter::where('WERKSX', $werksCode)
            ->orderBy('kode_wc')
            ->get();

        // Tentukan tampilan awal
        $initialView = ($view === 't3') ? 'T3' : 'T1';

        // --- END: LOGIKA PENGAMBILAN DATA GLOBAL MINIMAL ---

        // 3. Kirim semua data yang dibutuhkan ke View
        return view('Admin.detail-data2', [
            // Data Header (untuk ditampilkan di atas)
            'WERKS' => $werksCode,
            'plant' => $werksCode, 
            'bagian' => urldecode($bagianName),
            'categories' => urldecode($categoriesName),
            'workCenters' => $workCenters, // Data pendukung (modal)

            // Data Tampilan Utama (agar frontend berfungsi)
            'tdata' => $tdata, // Data TData1 (Buyer)
            'allTData2' => $allTData2, // Data TData2 (Outstanding) - Harus global
            'allTData3' => $allTData3Grouped, // Data TData3 (Overview) - Harus global
            'allTData1' => $allTData1ByAufnr, // Data Routing
            'allTData4ByAufnr' => $allTData4ByAufnr, // Data Komponen PRO
            'allTData4ByPlnum' => $allTData4ByPlnum, // Data Komponen PLO

            // Data Shortcut (untuk validasi & penandaan di frontend)
            'proData' => $tdata3,                   // PRO yang dicari
            'outstandingOrder' => $outstandingOrder, // TData2 spesifik
            'buyerData' => $buyerData,              // TData1 spesifik
            'initialView' => $initialView           // Flag T3
        ]);
    }

    // --- FUNGSI PEMBANTU (Wajib Anda Implementasikan) ---

    protected function getTData2BySalesOrder($kdauf, $kdpos)
    {
        // GANTI INI: Logika untuk mencari baris Outstanding Order
        // Asumsi: TData2 adalah baris tunggal dari tabel yang memiliki KDAUF dan KDPOS
        // Contoh:
        return ProductionTData2::where('KDAUF', $kdauf)
                            ->where('KDPOS', $kdpos)
                            ->firstOrFail(); 
    }

    protected function getTData1BySalesOrder($name1)
    {
        // GANTI INI: Logika untuk mencari data Buyer
        // Ini mungkin diambil dari TData2, atau dari tabel Master Buyer, 
        // atau hanya data yang terkait dengan Sales Order Header (KDAUF).
        // Contoh:
        return ProductionTData::where('NAME1', $name1)->first(); 
    }
}
