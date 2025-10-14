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
        $proNumber = strtoupper($proNumber);

        $bagianName = $request->query('bagian');
        $categoriesName = $request->query('categories');
        
        // 1. Ambil data TData3 (PRO) yang dicari
        $tdata3 = ProductionTData3::where('AUFNR', $proNumber)->first();
        
        if (!$tdata3) {
            abort(404, 'Production Order (PRO) not found in TData3.');
        }
        
        // --- PEMFORMATAN TANGGAL DI CONTROLLER ---
        // Tambahkan properti _formatted untuk View Blade
        $dateFields = ['GSTRP', 'GLTRP', 'SSAVD'];
        foreach ($dateFields as $field) {
            if (!empty($tdata3->{$field})) {
                try {
                    $tdata3->{$field . '_formatted'} = Carbon::parse($tdata3->{$field})->format('d/m/Y');
                } catch (\Exception $e) {
                    $tdata3->{$field . '_formatted'} = '-';
                }
            } else {
                $tdata3->{$field . '_formatted'} = '-';
            }
        }
        
        // Kunci relasi dasar
        $t2Key = trim($tdata3->KDAUF ?? '') . '-' . trim($tdata3->KDPOS ?? '');
        
        // 2. Ambil data TData2 dan TData1 spesifik
        $outstandingOrder = $this->getTData2BySalesOrder($tdata3->KDAUF, $tdata3->KDPOS);
        $buyerData = $this->getTData1BySalesOrder($tdata3->NAME1); 

        // Handle jika salah satu data utama tidak ditemukan
        if (!$outstandingOrder) { $outstandingOrder = (object)['key_for_frontend' => '']; }
        if (!$buyerData) { $buyerData = (object)['key_for_frontend' => '', 'NAME1' => $tdata3->NAME1 ?? '']; }

        // --- LOGIKA PENGAMBILAN DATA GLOBAL (DIPERTAHANKAN UNTUK KONTEKS) ---
        
        // A. Ambil SEMUA TData3 (Masih perlu untuk 'Order Overview' di Tab 1)
        $allTData3_flat = ProductionTData3::where('WERKSX', $werksCode)
            ->where(DB::raw("CONCAT(KDAUF, '-', KDPOS)"), $t2Key)
            ->where("AUFNR", $proNumber)
            ->get();
        $allTData3Grouped = $allTData3_flat->groupBy(fn($it) => trim($it->KDAUF ?? '') . '-' . trim($it->KDPOS ?? ''));

        // B. Ambil SEMUA TData2
        $allTData2_flat = ProductionTData2::where('WERKSX', $werksCode)
            ->where('NAME1', $tdata3->NAME1)
            ->get();
        $allTData2 = $allTData2_flat->groupBy(function ($r) {
            $kunnr = trim($r->KUNNR ?? '');
            $name1 = trim($r->NAME1 ?? '');
            return !empty($kunnr) ? ($kunnr . '-' . $name1) : $name1;
        });

        // C. Ambil SEMUA TData1 (List Buyer)
        $tdata = ProductionTData::where('WERKSX', $werksCode)
            ->where('NAME1', $tdata3->NAME1) 
            ->get();
        $tdata->each(function ($item) {
            $kunnr = trim($item->KUNNR ?? '');
            $name1 = trim($item->NAME1 ?? '');
            $item->key_for_frontend = !empty($kunnr) ? ($kunnr . '-' . $name1) : $name1;
        });
            
        // D. ✅ PERBAIKAN KRITIS: ROUTING & KOMPONEN HANYA BERDASARKAN PRO SPESIFIK
        
        $targetAufnr = $proNumber; 
        $targetPlnum = $tdata3->PLNUM;
        
        // TData1 (Routing) - HANYA PRO SPESIFIK YANG DI-SEARCH
        $allTData1ByAufnr = ProductionTData1::where('AUFNR', $targetAufnr)
            ->get()
            ->groupBy('AUFNR');
        
        // TData4 (Komponen) - HANYA PRO SPESIFIK YANG DI-SEARCH
        $allTData4ByAufnr = ProductionTData4::where('AUFNR', $targetAufnr)
            ->get()
            ->groupBy('AUFNR');
        
        // TData4 (Komponen PLO) - HANYA PLNUM TERKAIT DARI PRO SPESIFIK
        $allTData4ByPlnum = ProductionTData4::where('PLNUM', $targetPlnum)
            ->get()
            ->groupBy('PLNUM');
        
        // E. Ambil Work Center
        $workCenters = workcenter::where('WERKSX', $werksCode)->orderBy('kode_wc')->get();

        // --- PEMBENTUKAN KUNCI INISIALISASI SESSION ---
        $activeSOKey = $buyerData->key_for_frontend ?? '';
        $activeT2Key = $outstandingOrder->key_for_frontend ?? ''; 
        $initialView = ($view === 't3') ? 'T3' : 'T1';

        // 3. Kirim semua data yang dibutuhkan ke View BARU
        return view('Admin.pro-transaction', [
            // Data Header
            'WERKS'             => $werksCode,
            'plant'             => $werksCode, 
            'bagian'            => urldecode($bagianName),
            'categories'        => urldecode($categoriesName),
            'workCenters'       => $workCenters,

            // Data Tampilan Utama (GLOBAL DATA - Sekarang lebih fokus)
            'tdata'             => $tdata, 
            'allTData2'         => $allTData2, 
            'allTData3'         => $allTData3Grouped, 
            'allTData1'         => $allTData1ByAufnr, // Hanya 1 PRO
            'allTData4ByAufnr'  => $allTData4ByAufnr, // Hanya 1 PRO
            'allTData4ByPlnum'  => $allTData4ByPlnum, 
            
            // Data Shortcut & Inisialisasi
            'proData'           => $tdata3, 
            'outstandingOrder'  => $outstandingOrder,
            'buyerData'         => $buyerData, 
            'initialView'       => $initialView,
            'initSOKey'         => $activeSOKey,
            'initT2Key'         => $activeT2Key,
            'initProNumber'     => $proNumber,
            'search'            => null,
        ]);
    }

    // --- FUNGSI PEMBANTU (Wajib Anda Implementasikan) ---

    protected function getTData1BySalesOrder($name1)
    {
        // 1. Ambil data Buyer berdasarkan NAME1
        $buyerData = ProductionTData::where('NAME1', $name1)->first(); 

        if (!$buyerData) {
            return null;
        }

        // 2. Hitung Kunci T1 (activeSOKey) dengan logika Fallback
        $kunnr = trim($buyerData->KUNNR ?? '');
        $name1 = trim($buyerData->NAME1 ?? '');

        // Kunci T1 (activeSalesOrderKey): Prioritas: KUNNR-NAME1. Fallback: NAME1
        $activeSOKey = !empty($kunnr) ? ($kunnr . '-' . $name1) : $name1;

        // 3. Tambahkan kunci ini ke objek data yang dikembalikan
        $buyerData->key_for_frontend = $activeSOKey;

        return $buyerData;
    }

    /**
     * Mengambil data Outstanding Order (TData2/ProductionTData2) dan menghitung kunci frontend-nya.
     * Kunci: KDAUF-KDPOS.
     */
    protected function getTData2BySalesOrder($kdauf, $kdpos)
    {
        // 1. Ambil data TData2 spesifik (gunakan first() untuk menghindari exception)
        $outstandingOrder = ProductionTData2::where('KDAUF', $kdauf)
                                        ->where('KDPOS', $kdpos)
                                        ->first();

        if (!$outstandingOrder) {
            return null;
        }

        // 2. Hitung Kunci T2 (activeTdata2Key)
        $kdauf = trim($outstandingOrder->KDAUF ?? '');
        $kdpos = trim($outstandingOrder->KDPOS ?? '');

        // Kunci T2: KDAUF-KDPOS
        $activeT2Key = $kdauf . '-' . $kdpos;

        // 3. Tambahkan kunci ini ke objek data yang dikembalikan
        $outstandingOrder->key_for_frontend = $activeT2Key;

        return $outstandingOrder;
    }

}
