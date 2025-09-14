<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\WcCompatibility;
use Illuminate\Support\Facades\Http;
use Exception;
use App\Models\ProductionTData;
use App\Models\ProductionTData1;
use App\Models\ProductionTData2;
use App\Models\ProductionTData3;
use App\Models\ProductionTData4;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
    public function changeWorkcenter(Request $request, $plant, $wc_tujuan)
    {
        // 1. Validasi input dari form
        $validated = $request->validate([
            'pro_code' => 'required|string',
            'oper_code' => 'required|string',
        ]);

        // dd($request->pro_code);

        $proCode = $validated['pro_code'];
        $operCode = $validated['oper_code'];

        try {
            // 2. Siapkan payload untuk API changeWC
            $payload = [
                "IV_AUFNR" => $proCode,
                "IV_COMMIT" => "X",
                "IT_OPERATION" => [
                    [
                        "SEQUEN" => "0",
                        "OPER" => $operCode,
                        "WORK_CEN" => $wc_tujuan,
                        "W" => "X"
                    ]
                ]
            ];

            // 3. Panggil API untuk mengubah WC di SAP
            $changeApiUrl = env('FLASK_API_URL') . '/api/save_edit';
            $changeApiResponse = Http::timeout(120)
                ->withHeaders([
                    'X-SAP-Username' => session('username'),
                    'X-SAP-Password' => session('password'),
                ])
                ->post($changeApiUrl, $payload);

            if (!$changeApiResponse->successful()) {
                $errorData = $changeApiResponse->json();
                throw new Exception("API Change WC Gagal: " . ($errorData['error'] ?? 'Terjadi kesalahan tidak diketahui'));
            }

            // 4. Panggil API untuk me-refresh data PRO dari SAP
            $refreshApiUrl = env('FLASK_API_URL') . '/api/refresh-pro';
            $refreshApiResponse = Http::timeout(120)
                ->withHeaders([
                    'X-SAP-Username' => session('username'),
                    'X-SAP-Password' => session('password'),
                ])
                ->get($refreshApiUrl, [
                    'plant' => $plant,
                    'aufnr' => $proCode,
                ]);

            if (!$refreshApiResponse->successful()) {
                $errorData = $refreshApiResponse->json();
                throw new Exception("API Refresh PRO Gagal: " . ($errorData['error'] ?? 'Terjadi kesalahan tidak diketahui'));
            }

            // 5. Ekstrak semua data yang sudah di-refresh
            $refreshedData = $refreshApiResponse->json();
            $t_data  = $refreshedData['T_DATA'] ?? [];
            $t_data1 = $refreshedData['T_DATA1'] ?? [];
            $t_data2 = $refreshedData['T_DATA2'] ?? [];
            $t_data3 = $refreshedData['T_DATA3'] ?? [];
            $t_data4 = $refreshedData['T_DATA4'] ?? [];

            if (empty($t_data3) || empty($t_data[0]) || empty($t_data2[0])) {
                throw new Exception("Data inti (T_DATA, T_DATA2, T_DATA3) tidak lengkap setelah refresh.");
            }

            // Memulai transaksi database untuk memastikan integritas data
            DB::transaction(function () use ($t_data, $t_data1, $t_data2, $t_data3, $t_data4, $plant, $proCode) {
                Log::info("Memulai transaksi DB untuk sinkronisasi PRO {$proCode} di plant {$plant}.");

                // --- Update atau Buat Record Induk ---
                $t_data_row = $t_data[0];
                ProductionTData::updateOrCreate(
                    ['KUNNR' => $t_data_row['KUNNR'], 'NAME1' => $t_data_row['NAME1']],
                    $t_data_row + ['WERKSX' => $plant]
                );

                $t2_row = $t_data2[0];
                ProductionTData2::updateOrCreate(
                    ['KDAUF' => $t2_row['KDAUF'], 'KDPOS' => $t2_row['KDPOS']],
                    $t2_row + ['WERKSX' => $plant]
                );
                
                $t3_row = $t_data3[0];
                ProductionTData3::updateOrCreate(
                    ['AUFNR' => $t3_row['AUFNR']],
                    $t3_row + ['WERKSX' => $plant]
                );

                // --- Hapus dan Sisipkan Ulang Data Anak ---
                
                // Proses T_DATA1
                ProductionTData1::where('AUFNR', $proCode)->delete();
                if (!empty($t_data1)) {
                    $formatTanggal = function ($tgl) {
                        if (empty($tgl) || trim($tgl) === '00000000') return null;
                        try { return Carbon::createFromFormat('Ymd', $tgl)->format('d-m-Y'); } catch (Exception $e) { return null; }
                    };

                    $mapped_t1 = array_map(function($row) use ($plant, $formatTanggal) {
                        $sssl1 = $formatTanggal($row['SSSLDPV1'] ?? '');
                        $sssl2 = $formatTanggal($row['SSSLDPV2'] ?? '');
                        $sssl3 = $formatTanggal($row['SSSLDPV3'] ?? '');

                        $partsPv1 = [];
                        if (!empty($row['ARBPL1'])) $partsPv1[] = strtoupper($row['ARBPL1']);
                        if (!empty($sssl1)) $partsPv1[] = $sssl1;
                        $row['PV1'] = !empty($partsPv1) ? implode(' - ', $partsPv1) : null;

                        $partsPv2 = [];
                        if (!empty($row['ARBPL2'])) $partsPv2[] = strtoupper($row['ARBPL2']);
                        if (!empty($sssl2)) $partsPv2[] = $sssl2;
                        $row['PV2'] = !empty($partsPv2) ? implode(' - ', $partsPv2) : null;

                        $partsPv3 = [];
                        if (!empty($row['ARBPL3'])) $partsPv3[] = strtoupper($row['ARBPL3']);
                        if (!empty($sssl3)) $partsPv3[] = $sssl3;
                        $row['PV3'] = !empty($partsPv3) ? implode(' - ', $partsPv3) : null;

                        unset($row['ARBPL1'], $row['ARBPL2'], $row['ARBPL3']);
                        unset($row['SSSLDPV1'], $row['SSSLDPV2'], $row['SSSLDPV3'], $row['WERKS']);
                        
                        $row['WERKSX'] = $plant;
                        return $row;
                    }, $t_data1);
                    ProductionTData1::insert($mapped_t1);
                }

                // Proses T_DATA4
                ProductionTData4::where('AUFNR', $proCode)->delete();
                if (!empty($t_data4)) {
                    $mapped_t4 = array_map(function($row) use ($plant) {

                        unset($row['VORNR'], $row['WERKS']);

                        $row['WERKSX'] = $plant;
                        return $row;
                    }, $t_data4);
                    ProductionTData4::insert($mapped_t4);
                }
                Log::info("Transaksi DB untuk PRO {$proCode} berhasil.");
            });

            // 7. Redirect kembali dengan pesan sukses
            return redirect()->back()->with('success', "PRO {$proCode} berhasil dipindahkan ke WC {$wc_tujuan} dan data telah disinkronkan.");

        } catch (Exception $e) {
            // Jika terjadi error, catat dan tampilkan pesan
            Log::error("Gagal memindahkan PRO {$proCode}: " . $e->getMessage());
            return redirect()->back()->with('error', "Gagal! Terjadi kesalahan: " . $e->getMessage());
        }
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
