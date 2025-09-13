<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PROController extends Controller
{
    /**
     * Menampilkan halaman detail PRO untuk workcenter tertentu.
     */
    public function showByWorkcenter(string $kode, string $workcenter)
    {
        // 1. Ambil data master untuk UI (dropdown, dll)
        $compatibleWc = DB::table('production_t_data3')
                            ->where('WERKSX', $kode)
                            ->distinct()
                            ->pluck('ARBPL');

        $pv1 = DB::table('production_t_data1')->distinct()->where('ARBPL', $workcenter)->whereNotNull('PV1')->pluck('PV1');
        $pv2 = DB::table('production_t_data1')->distinct()->where('ARBPL', $workcenter)->whereNotNull('PV2')->pluck('PV2');
        $pv3 = DB::table('production_t_data1')->distinct()->where('ARBPL', 'like', $workcenter . '%')->whereNotNull('PV3')->pluck('PV3');
        $conditionalWc = $pv1->merge($pv2)->merge($pv3)->unique()->values();

        // 2. Ambil data mentah dari database
        // Menggabungkan t3 dan t1, serta mengambil kolom sumber untuk pemrosesan
        $rawListPro = DB::table('production_t_data3 as t3')
                        ->leftJoin('production_t_data1 as t1', 't3.AUFNR', '=', 't1.AUFNR')
                        ->where('t3.ARBPL', $workcenter)
                        // Ambil semua kolom dari t3 dan kolom sumber dari t1
                        ->select(
                            't3.*', 
                            't1.ARBPL1', 't1.SSSLDPV1',
                            't1.ARBPL2', 't1.SSSLDPV2',
                            't1.ARBPL3', 't1.SSSLDPV3'
                        )
                        ->get();

        // 3. Proses data mentah menjadi data matang
        $listPro = $rawListPro->map(function ($pro) {
            // Logika untuk PV1
            $partsPv1 = [];
            if (!empty($pro->ARBPL1)) {
                $partsPv1[] = strtoupper($pro->ARBPL1);
            }
            $tgl1 = $pro->SSSLDPV1 ?? '';
            if (!empty($tgl1) && trim($tgl1) !== '00000000') {
                try {
                    $partsPv1[] = Carbon::createFromFormat('Ymd', $tgl1)->format('d-m-Y');
                } catch (\Exception $e) {}
            }
            // Tambahkan properti PV1 ke objek $pro
            $pro->PV1 = !empty($partsPv1) ? implode(' - ', $partsPv1) : '-';

            // Logika untuk PV2
            $partsPv2 = [];
            if (!empty($pro->ARBPL2)) {
                $partsPv2[] = strtoupper($pro->ARBPL2);
            }
            $tgl2 = $pro->SSSLDPV2 ?? '';
            if (!empty($tgl2) && trim($tgl2) !== '00000000') {
                try {
                    $partsPv2[] = Carbon::createFromFormat('Ymd', $tgl2)->format('d-m-Y');
                } catch (\Exception $e) {}
            }
            $pro->PV2 = !empty($partsPv2) ? implode(' - ', $partsPv2) : '-';

            // Logika untuk PV3
            $partsPv3 = [];
            if (!empty($pro->ARBPL3)) {
                $partsPv3[] = strtoupper($pro->ARBPL3);
            }
            $tgl3 = $pro->SSSLDPV3 ?? '';
            if (!empty($tgl3) && trim($tgl3) !== '00000000') {
                try {
                    $partsPv3[] = Carbon::createFromFormat('Ymd', $tgl3)->format('d-m-Y');
                } catch (\Exception $e) {}
            }
            $pro->PV3 = !empty($partsPv3) ? implode(' - ', $partsPv3) : '-';

            return $pro;
        });

        // 4. Siapkan data untuk chart
        $proCounts = DB::table('production_t_data3')
                            ->where('WERKSX', $kode)
                            ->select('ARBPL', DB::raw('count(*) as total'))
                            ->groupBy('ARBPL')
                            ->get();

        $pieChartLabels = $proCounts->pluck('ARBPL');
        $pieChartData = $proCounts->pluck('total');

        // 5. Kirim semua data yang sudah matang ke view
        return view('Admin.kelola-pro', [
            'workcenter' => $workcenter,
            'kode' => $kode,
            'listPro' => $listPro, // Data ini sudah matang!
            'compatibleWc' => $compatibleWc,
            'conditionalWc' => $conditionalWc,
            'pieChartLabels' => $pieChartLabels,
            'pieChartData' => $pieChartData,
        ]);
    }
}
