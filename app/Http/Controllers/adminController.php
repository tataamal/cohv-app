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
use Illuminate\Support\Facades\Log;
use App\Models\SapUser;
use App\Models\workcenter;
use App\Models\WorkcenterMapping;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;

class adminController extends Controller
{
    public function index(Request $request, $kode)
    {
        // =================================================================
        // DATA UNTUK CHART PERTAMA (BAR CHART - WORKCENTER)
        // =================================================================
        $childCodes = WorkcenterMapping::where('plant', $kode) // Or use 'kode_laravel' if 'plant' column inconsistent
            ->orWhere('kode_laravel', $kode)
            ->pluck('workcenter')
            ->filter()
            ->map(fn($code) => strtoupper($code))
            ->unique();
            
        $allWcQuery = DB::table('workcenters')
            ->select('kode_wc', 'description')
            ->where('werksx', $kode)
            ->whereNotIn(DB::raw('UPPER(kode_wc)'), $childCodes);

        $statsPerWc = DB::table(DB::raw("({$allWcQuery->toSql()}) as master_wc"))
            ->mergeBindings($allWcQuery)
            ->leftJoin('production_t_data1 as trans_data', 'master_wc.kode_wc', '=', 'trans_data.ARBPL')
            ->selectRaw("
                master_wc.kode_wc AS wc_label,
                master_wc.description AS wc_description,
                COUNT(DISTINCT trans_data.AUFNR) AS pro_count,
                SUM(trans_data.CPCTYX) AS total_capacity
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
                DB::raw('COALESCE(SUM(t1.CPCTYX), 0) as total_capacity')
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
        $sub_kategori = Kode::where('kode', $kode)->value('sub_kategori');
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
            'sub_kategori' => $sub_kategori,
        ]);  
    }


    public function AdminDashboard()
    {
        $plants = collect();
        $allUsers = collect();

        if (Auth::check()) {
            $user = Auth::user();
            $sapUser = null;

            if ($user->role === 'admin') {
                $sapId = str_replace('@kmi.local', '', $user->email);
                $sapUser = SapUser::where('sap_id', $sapId)->first();
            } 

            if ($sapUser) {
                $allRelatedKodes = $sapUser->kodes()->get();
                $plants = $allRelatedKodes->unique('kode');
            }
            
            $allUsers = SapUser::orderBy('nama')->get();
        }
        
        return view('dashboard', [
            'plants' => $plants,
            'allUsers' => $allUsers
        ]);
    }

    public function getProDetails($status, Request $request)
    {
        $kode_plant = $request->input('kode');
        $nama_bagian = $request->input('nama_bagian');
        $kategori = $request->input('kategori');

        $proDetails = DB::table('production_t_data3')
            ->select(
                'KDAUF as so_number', 
                'KDPOS as so_item', 
                'AUFNR as pro_number', 
                'MATNR as material_code', 
                'MAKTX as description',
                'PWWRK as plant',
                'DISPO as mrp_controller',
                'PSMNG as order_quantity',
                'WEMNG as gr_quantity',
                DB::raw('(PSMNG - WEMNG) AS outs_gr_quantity'),
                'GSTRP as start_date', 
                'GLTRP as end_date',
                'STATS as stats' 
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

    public function handleMultiProSearch(Request $request) 
    {
        Log::info("[POST] Menerima request pencarian multi-pro...");

        try {
            // 1. Validasi input (sama seperti kode Anda)
            $validated = $request->validate([
                'werks_code'        => 'required|string',
                'bagian_name'       => 'required|string',
                'categories_name'   => 'required|string',
                'pro_numbers'       => 'required|string', // Masih string JSON
            ]);

            $proNumbersArray = json_decode($validated['pro_numbers'], true);
            if (empty($proNumbersArray)) {
                return back()->withErrors(['pro_numbers' => 'Tidak ada nomor PRO yang dimasukkan.']);
            }
            
            Log::info("[POST] Validasi sukses. Redirect ke route GET '...hasil'.");

            // 2. Redirect ke route GET (showMultiProResult)
            // Kita kirim semua data yang divalidasi sebagai parameter query
            return Redirect::route('manufaktur.pro.search.hasil', [
                'werks_code' => $validated['werks_code'],
                'bagian_name' => $validated['bagian_name'],
                'categories_name' => $validated['categories_name'],
                'pro_numbers' => $validated['pro_numbers'], // Kirim sebagai string JSON
            ]);

        } catch (\Exception $e) {
            Log::error('[POST] Gagal validasi atau redirect: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function showMultiProResult(Request $request, ProTransaction $proApi) // [BARU] Inject ProController
    {
        Log::info("==================================================");
        Log::info("[GET] Menampilkan halaman hasil pencarian PRO...");
        Log::info("==================================================");

        try {
            // 1. Ambil data dari QUERY PARAMETER (URL)
            $werksCode = $request->query('werks_code');
            $bagianName = $request->query('bagian_name');
            $categoriesName = $request->query('categories_name');
            $proNumbersJson = $request->query('pro_numbers');

            $proNumbersArray = json_decode($proNumbersJson, true);

            if (empty($proNumbersArray) || empty($werksCode)) {
                Log::warn("[GET] Parameter tidak lengkap, kembali ke dashboard.");
                return redirect()->route('manufaktur.dashboard.show'); // Redirect ke 'aman'
            }
            
            // 2. Ambil Kredensial SAP (Sama seperti kode Anda)
            $sapUser = session('username');
            $sapPass = session('password');
            
            if (empty($sapUser) || empty($sapPass)) {
                Log::error("[GET] Kredensial SAP tidak ditemukan di session.");
                return back()->with('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
            }

            // 3. Panggil API SAP (Sama seperti kode Anda)
            Log::info("[GET] Memanggil ProController@get_data_pro...");
            $sapData = $proApi->get_data_pro(
                $werksCode, 
                $proNumbersArray, 
                $sapUser, 
                $sapPass
            );

            // 4. Ambil Data Lokal (Sama seperti kode Anda)
            $workCenters = WorkCenter::where('WERKSX', $werksCode)->orderBy('kode_wc')->get();

            // 5. Tampilkan View (Sama seperti kode Anda)
            return view('Admin.pro-transaction', [ // Nama view Anda
                // Data Header
                'WERKS'          => $werksCode,
                'bagian'         => $bagianName,
                'categories'     => $categoriesName,
                'workCenters'    => $workCenters,
                
                // Data Utama (Sekarang datang dari $sapData)
                'proDetailsList' => $sapData['proDetailsList'],
                
                // Data Info
                'proNumbersSearched' => $proNumbersArray,
                'notFoundProNumbers' => $sapData['notFoundProNumbers'],
            ]);

        } catch (\Exception $e) {
            Log::error('[GET] Gagal menampilkan PRO live: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

}
