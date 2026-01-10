<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Exception;
use App\Models\ProductionTData;
use App\Models\ProductionTData1;
use App\Models\ProductionTData2;
use App\Models\ProductionTData3;
use App\Models\ProductionTData4;
use App\Models\Kode;
use App\Models\wc_relations;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WcCompatibilityController extends Controller
{
    public function index()
    {
        $wc_relations = wc_relations::with(['asal', 'tujuan'])->orderBy('wc_asal_id')->get();
        return view('Admin.kelola-pro', compact('wc_relations'));
    }


    public function showDetails($kode, $wc)
    {
        $compatibilities = DB::table('wc_relations as rel')
            ->join('workcenters as asal', 'rel.wc_asal_id', '=', 'asal.id')
            ->join('workcenters as tujuan', 'rel.wc_tujuan_id', '=', 'tujuan.id')
            ->select(
                'asal.kode_wc as wc_asal_code',
                'tujuan.id as wc_tujuan_id',
                'tujuan.kode_wc as wc_tujuan_code',
                'tujuan.description as wc_tujuan_description',
                'rel.status'
            )
            ->get()
            ->groupBy('wc_asal_code');

        $compatibleWcCodes = $compatibilities->get($wc, collect())
            ->filter(function ($relation) {
                return strtolower($relation->status ?? '') === 'compatible';
            })
            ->pluck('wc_tujuan_code')
            ->all();

        $targetWcCodes = collect([$wc])->merge($compatibleWcCodes)->unique()->values()->all();
        
        $filteredWcs = DB::table('workcenters')
            ->select('kode_wc as ARBPL', 'description')
            ->whereIn('kode_wc', $targetWcCodes)
            ->distinct()
            ->get();

        $pros = DB::table('production_t_data1')
            ->where('ARBPL', $wc)
            ->whereRaw("NULLIF(TRIM(AUFNR), '') IS NOT NULL")
            ->orderBy('AUFNR', 'asc')
            ->get();

        $proDensity = DB::table('production_t_data1')
            ->select('ARBPL', DB::raw('COUNT(*) as pro_count'))
            ->groupBy('ARBPL')
            ->get()
            ->keyBy('ARBPL');

        $capacityDensity = DB::table('production_t_data3')
            ->select('ARBPL', DB::raw('SUM(CPCTYX) as capacity_sum'))
            ->groupBy('ARBPL')
            ->get()
            ->keyBy('ARBPL');

        $chartProData = $filteredWcs->map(function ($wc_item) use ($proDensity) {
            return $proDensity[$wc_item->ARBPL]->pro_count ?? 0;
        });

        $chartCapacityData = $filteredWcs->map(function ($wc_item) use ($capacityDensity) {
            return $capacityDensity[$wc_item->ARBPL]->capacity_sum ?? 0;
        });

        $wcDescriptionMap = $filteredWcs->keyBy('ARBPL')->map(function ($item) {
            return $item->description;
        });

        $plant = Kode::where('kode', $kode)->firstOrFail();
        
        return view('Admin.kelola-pro', [
            'workCenter'        => $wc,
            'pros'              => $pros,
            'allWcs'            => $filteredWcs,
            'chartLabels'       => $filteredWcs->pluck('ARBPL'),
            'chartProData'      => $chartProData,
            'chartCapacityData' => $chartCapacityData,
            'compatibilities'   => $compatibilities,
            'wcDescriptionMap'  => $wcDescriptionMap,
            'kode'              => $kode,
            'plant'             => $plant,
        ]);
    }

    public function changeWorkcenter(Request $request, $plant, $wc_tujuan)
    {
        $validated = $request->validate([
            'pro_code' => 'required|string',
            'oper_code' => 'required|string',
            'pwwrk' => 'required|string',
        ]);

        $proCode = $validated['pro_code'];
        $operCode = $validated['oper_code'];
        $pwwrk = $validated['pwwrk'];

        try {
            Log::info("Memulai proses pindah WC untuk PRO {$proCode}. Langkah 1: Get WC Description.");
            $descApiUrl = env('FLASK_API_URL') . '/api/get_wc_desc';
            $descApiResponse = Http::timeout(120)
                ->withHeaders([
                    'X-SAP-Username' => session('username'),
                    'X-SAP-Password' => session('password'),
                ])
                ->get($descApiUrl, [
                    'wc' => $wc_tujuan,
                    'pwwrk' => $pwwrk,
                ]);

            if (!$descApiResponse->successful()) {
                $errorData = $descApiResponse->json();
                throw new Exception("API Get WC Description Gagal: " . ($errorData['error'] ?? 'Terjadi kesalahan tidak diketahui'));
            }
            
            $wcDescriptionData = $descApiResponse->json();
            
            $shortText = $wcDescriptionData['E_DESC'] ?? '';
            
            Log::info(" -> Deskripsi untuk WC {$wc_tujuan} didapatkan: '{$shortText}'");

            $payload = [
                "IV_AUFNR" => $proCode,
                "IV_COMMIT" => "X",
                "IT_OPERATION" => [
                    [
                        "SEQUEN" => "0",
                        "OPER" => $operCode,
                        "WORK_CEN" => $wc_tujuan,
                        "W" => "X",
                        "SHORT_T" => $shortText,
                        "S" => "X"
                    ]
                ]
            ];

            Log::info("Langkah 2: Mengubah WC di SAP...");
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
            Log::info(" -> Perubahan WC di SAP berhasil.");

            Log::info("Langkah 3: Me-refresh data PRO dari SAP...");
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
            Log::info(" -> Data PRO berhasil di-refresh.");

            $refreshedData = $refreshApiResponse->json();
            $t_data  = $refreshedData['T_DATA'] ?? [];
            $t_data1 = $refreshedData['T_DATA1'] ?? [];
            $t_data2 = $refreshedData['T_DATA2'] ?? [];
            $t_data3 = $refreshedData['T_DATA3'] ?? [];
            $t_data4 = $refreshedData['T_DATA4'] ?? [];

            if (empty($t_data3) || empty($t_data[0]) || empty($t_data2[0])) {
                throw new Exception("Data inti (T_DATA, T_DATA2, T_DATA3) tidak lengkap setelah refresh.");
            }

            DB::transaction(function () use ($t_data, $t_data1, $t_data2, $t_data3, $t_data4, $plant, $proCode) {
                Log::info("Memulai transaksi DB untuk sinkronisasi PRO {$proCode}.");

                ProductionTData::updateOrCreate(
                    ['KUNNR' => $t_data[0]['KUNNR'], 'NAME1' => $t_data[0]['NAME1']],
                    $t_data[0] + ['WERKSX' => $plant]
                );

                ProductionTData2::updateOrCreate(
                    ['KDAUF' => $t_data2[0]['KDAUF'], 'KDPOS' => $t_data2[0]['KDPOS']],
                    $t_data2[0] + ['WERKSX' => $plant]
                );
                
                ProductionTData3::updateOrCreate(
                    ['AUFNR' => $t_data3[0]['AUFNR']],
                    $t_data3[0] + ['WERKSX' => $plant]
                );

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

            return redirect()->back()->with('success', "PRO {$proCode} berhasil dipindahkan ke WC {$wc_tujuan} dan data telah disinkronkan.");

        } catch (Exception $e) {
            Log::error("Gagal memindahkan PRO {$proCode}: " . $e->getMessage());
            return redirect()->back()->with('error', "Gagal! Terjadi kesalahan: " . $e->getMessage());
        }
    }
}
