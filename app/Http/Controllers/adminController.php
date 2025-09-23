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
        $allWcQuery = DB::table('workcenters')
            ->select('kode_wc', 'description') // Ambil juga kolom 'description' untuk tooltip
            ->where('werksx', $kode);

        // 2. Gabungkan (LEFT JOIN) daftar WC utama dengan data transaksi
        $statsPerWc = DB::table(DB::raw("({$allWcQuery->toSql()}) as master_wc"))
            ->mergeBindings($allWcQuery)
            ->leftJoin('production_t_data1 as trans_data', 'master_wc.kode_wc', '=', 'trans_data.ARBPL')
            ->selectRaw("
                master_wc.kode_wc AS wc_label,
                master_wc.description AS wc_description,
                COUNT(DISTINCT trans_data.AUFNR) AS pro_count,
                COALESCE(SUM(trans_data.CPCTYX), 0) AS total_capacity
            ")
            ->groupBy('master_wc.kode_wc', 'master_wc.description') // Group berdasarkan keduanya
            ->orderBy('master_wc.kode_wc', 'asc')
            ->get();

        // 3. Siapkan data untuk Chart.js
        $labels          = $statsPerWc->pluck('wc_label')->all();
        $descriptions    = $statsPerWc->pluck('wc_description')->all(); // Data deskripsi untuk tooltip
        $datasetPro      = $statsPerWc->pluck('pro_count')->all();
        $datasetCapacity = $statsPerWc->pluck('total_capacity')->all();

        // Membuat URL untuk setiap bar chart agar bisa diklik
        $targetUrls = collect($labels)->map(function ($wcLabel) use ($kode) {
            return route('wc.details', ['kode' => $kode, 'wc' => $wcLabel]);
        })->all();

        // 4. Definisikan dataset untuk chart, tambahkan 'descriptions' ke meta-data
        $datasets = [
            [
                'label'           => 'Jumlah PRO',
                'data'            => $datasetPro,
                'descriptions'    => $descriptions, // Kirim deskripsi ke view
                'backgroundColor' => 'rgba(59, 130, 246, 0.6)',
                'borderColor'     => 'rgba(37, 99, 235, 1)',
                'borderWidth'     => 1,
                'borderRadius'    => 4,
                'satuan'          => 'PRO' // Menambahkan satuan
            ],
            [
                'label'           => 'Jumlah Kapasitas',
                'data'            => $datasetCapacity,
                'descriptions'    => $descriptions, // Kirim deskripsi ke view
                'backgroundColor' => 'rgba(249, 115, 22, 0.6)',
                'borderColor'     => 'rgba(234, 88, 12, 1)',
                'borderWidth'     => 1,
                'borderRadius'    => 4,
                'satuan'          => 'Jam' // Asumsi satuan kapasitas adalah jam
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

        $topWcByCapacity = DB::table('production_t_data1 as t1')
            ->join('workcenters as wc', 't1.ARBPL', '=', 'wc.kode_wc') // JOIN dengan tabel workcenters
            ->select(
                't1.ARBPL',
                'wc.description', // Ambil kolom deskripsi
                DB::raw('SUM(t1.CPCTYX) as total_capacity')
            )
            ->where('t1.WERKSX', $kode)
            ->whereNotNull('t1.ARBPL')
            ->groupBy('t1.ARBPL', 'wc.description') // Group by deskripsi juga
            ->orderByDesc('total_capacity')
            ->limit(5)
            ->get();

        // 2. Siapkan data untuk Chart.js dari hasil query
        $pieChartLabels       = $topWcByCapacity->pluck('ARBPL')->all();
        $pieChartData         = $topWcByCapacity->pluck('total_capacity')->all();
        $pieChartDescriptions = $topWcByCapacity->pluck('description')->all(); // Data deskripsi untuk tooltip

        // 3. Siapkan dataset untuk dikirim ke view, kini dengan deskripsi dan satuan
        $pieChartDatasets = [
            [
                'label'           => 'Distribusi Kapasitas',
                'data'            => $pieChartData,
                'descriptions'    => $pieChartDescriptions, // Kirim deskripsi untuk tooltip
                'satuan'          => 'Jam',                  // Kirim satuan
                'backgroundColor' => [
                    'rgba(255, 166, 158, 0.8)', // Soft Coral (alpha diperbaiki)
                    'rgba(174, 217, 224, 0.8)', // Soft Blue
                    'rgba(204, 204, 255, 0.8)', // Soft Lavender (menggantikan merah gelap)
                    'rgba(255, 225, 179, 0.8)', // Soft Yellow (alpha diperbaiki)
                    'rgba(181, 234, 215, 0.8)', // Soft Mint Green (menggantikan duplikat kuning)
                ],
                'borderColor'     => '#ffffff',
                'borderWidth'     => 2,
            ]
        ];

        $nama_bagian = Kode::where('kode', $kode)->value('nama_bagian');
        
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
        })->get()
        // TAMBAHKAN BLOK INI untuk membersihkan data sebelum dikirim
        ->map(function ($item) {
            // Hapus baris baru, tabs, dan spasi berlebih dari deskripsi
            $item->wc_description = trim(preg_replace('/\s+/', ' ', $item->wc_description ?? ''));
            return $item;
        });

        $outstandingReservasi = ProductionTData4::where('WERKSX', $kode)
                                    ->whereColumn('KALAB', '<', 'BDMNG')
                                    ->count();
        $nama_bagian = Kode::where('kode', $kode)->value('nama_bagian');

        return view('Admin.dashboard', [
            // ... data kardinal dan doughnut chart ...
            'TData1' => $TData1, 
            'TData2' => $TData2, 
            'TData3' => $TData3, 
            'TData4' => $TData4,
            'outstandingReservasi' => $outstandingReservasi, 
            'labels' => $labels, 
            'datasets' => $datasets, // 'datasets' sekarang berisi deskripsi dan satuan
            'targetUrls' => $targetUrls,
            'doughnutChartLabels' => $doughnutChartLabels,
            'doughnutChartDatasets' => $doughnutChartDatasets,
            'pieChartLabels' => $pieChartLabels,
            'pieChartDatasets' => $pieChartDatasets,
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
