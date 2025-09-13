<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\WcCompatibility;

class WcCompatibilityController extends Controller
{
    public function index()
    {
        $compatibilities = WcCompatibility::orderBy('wc_asal')->get();
        return view('Admin.kelola-pro', compact('compatibilities'));
    }

    public function showDetails($kode, $wc)
    {
        $allWcQuery = DB::table('production_t_data1')
            ->select('ARBPL')
            ->distinct()
            ->where('WERKSX', $kode)->get();
        // Ganti 'Pro' dan 'work_center_column' dengan nama model dan kolom yang sesuai di proyek Anda
        // Ini adalah contoh query untuk mengambil semua PRO yang terkait dengan WC yang di-klik
        $pros = DB::table('production_t_data1')
                ->where('WERKSX', $kode) // <-- TAMBAHKAN BARIS INI
                ->where('ARBPL', $wc)
                ->whereRaw("NULLIF(TRIM(AUFNR), '') IS NOT NULL")
                ->orderBy('AUFNR', 'asc')
                ->get();

        $proDensity = DB::table('production_t_data1')
                    ->select('ARBPL', DB::raw('COUNT(*) as pro_count')) // Hapus DISTINCT dan ganti AUFNR dengan *
                    ->where('WERKSX', $kode)
                    ->groupBy('ARBPL')
                    ->get()
                    ->keyBy('ARBPL');
        $chartDensityData = $allWcQuery->map(function ($wc_item) use ($proDensity) {
            return $proDensity[$wc_item->ARBPL]->pro_count ?? 0;
        });

        $compatibilities = DB::table('wc_compatibilities')
                        ->select('wc_asal', 'wc_tujuan', 'status')
                        ->get()
                        ->groupBy('wc_asal'); // Kelompokkan berdasarkan 'wc_asal' agar mudah diakses di JavaScript

        // Kirim data yang ditemukan ke view 'wc-details'
        return view('Admin.kelola-pro', [
            'workCenter' => $wc,
            'pros' => $pros,
            'allWcs' => $allWcQuery,
            'chartLabels'       => $allWcQuery->pluck('ARBPL'),
            'chartDensityData'  => $chartDensityData,
            'compatibilities'   => $compatibilities,
            'kode'              => $kode,
        ]);
    }

     /**
     * Memproses pemindahan workcenter.
     */
    public function changeWorkcenter(Request $request)
    {
        // Logika untuk validasi dan memindahkan PRO yang dipilih
        // ...
        return back()->with('success', 'Workcenter berhasil diubah.');
    }

    /**
     * Memproses pemindahan workcenter via Change PV.
     */
    public function changePv(Request $request)
    {
        // Logika untuk validasi dan memindahkan PRO via Change PV
        // ...
        return back()->with('success', 'Perubahan melalui PV berhasil diajukan.');
    }
}
