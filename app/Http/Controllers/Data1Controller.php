<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ProductionTData;
use App\Models\ProductionTData1;
use App\Models\ProductionTData2;

use App\Models\ProductionTData3;
use App\Models\ProductionTData4;

class Data1Controller extends Controller
{
    public function changeWc(Request $request)
    {
        // 1. Validasi input dari form AJAX
        $validated = $request->validate([
            'aufnr' => 'required|string',
            'vornr' => 'required|string',
            'plant' => 'required|string',
            'pwwrk' => 'required|string',
            'work_center_tujuan' => 'required|string',
        ]);
        
        // Cek Session
        $sapUser = session('username');
        $sapPass = session('password');
        if (!$sapUser || !$sapPass) {
            return response()->json(['success' => false, 'message' => 'Otentikasi SAP tidak valid.'], 401);
        }
        
        $proCode = $validated['aufnr'];
        $wc_tujuan = $validated['work_center_tujuan'];
        $flaskBase = rtrim(env('FLASK_API_URL'), '/');

        try {
            // ==========================================================
            // LANGKAH 1: Dapatkan Deskripsi Work Center Tujuan
            // ==========================================================
            Log::info("Memulai pindah WC untuk PRO {$proCode}. Langkah 1: Get WC Desc.");
            $descResponse = Http::timeout(30)->withHeaders(['X-SAP-Username' => $sapUser, 'X-SAP-Password' => $sapPass])
                ->get($flaskBase . '/api/get_wc_desc', [
                    'wc' => $wc_tujuan,
                    'pwwrk' => $validated['pwwrk'], // Menggunakan plant dari hidden input
                ]);
            
            if ($descResponse->failed()) {
                throw new \Exception("API Get WC Description Gagal: " . $descResponse->body());
            }
            $shortText = $descResponse->json()['E_DESC'] ?? '';
            Log::info(" -> Deskripsi untuk WC {$wc_tujuan} didapatkan: '{$shortText}'");

            // ==========================================================
            // LANGKAH 2: Kirim Perubahan Work Center ke SAP
            // ==========================================================
            $payload = [
                "IV_AUFNR" => $proCode, "IV_COMMIT" => "X",
                "IT_OPERATION" => [[
                    "SEQUEN" => "0", "OPER" => $validated['vornr'], "WORK_CEN" => $wc_tujuan,
                    "W" => "X", "SHORT_T" => $shortText, "S" => "X"
                ]]
            ];
            
            Log::info("Langkah 2: Mengubah WC di SAP...");
            $changeResponse = Http::timeout(60)->withHeaders(['X-SAP-Username' => $sapUser, 'X-SAP-Password' => $sapPass])
                ->post($flaskBase . '/api/save_edit', $payload);

            if ($changeResponse->failed()) {
                throw new \Exception("API Change WC Gagal: " . $changeResponse->body());
            }
            Log::info(" -> Perubahan WC di SAP berhasil.");

            // ==========================================================
            // LANGKAH 3: Refresh Data PRO yang Baru Diubah
            // ==========================================================
            Log::info("Langkah 3: Me-refresh data PRO {$proCode} dari SAP...");
            $refreshResponse = Http::timeout(60)->withHeaders(['X-SAP-Username' => $sapUser, 'X-SAP-Password' => $sapPass])
                ->get($flaskBase . '/api/refresh-pro', [
                    'plant' => $validated['plant'],
                    'aufnr' => $proCode,
                ]);

            if ($refreshResponse->failed()) {
                throw new \Exception("API Refresh PRO Gagal: " . $refreshResponse->body());
            }
            $refreshedData = $refreshResponse->json();
            Log::info(" -> Data PRO berhasil di-refresh.");

            // ==========================================================
            // LANGKAH 4: Jalankan Sinkronisasi ke Database
            // ==========================================================
            DB::transaction(function () use ($refreshedData, $validated) {
                $this->_syncSingleProData($refreshedData, $validated['plant']);
            });

            return response()->json(['success' => true, 'message' => "PRO {$proCode} berhasil dipindahkan ke WC {$wc_tujuan} dan data telah disinkronkan."], 200);

        } catch (\Throwable $e) {
            Log::error("Gagal memindahkan PRO {$proCode}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => "Gagal! " . $e->getMessage()], 500);
        }
    }

    public function changePv(Request $request)
    {
        // 1. Validasi & Normalisasi Input
        $data = $request->validate([
            'AUFNR'        => ['required', 'string'],
            'PROD_VERSION' => ['required', 'string'],
            'plant'        => ['required', 'string'],
        ]);

        $aufnr = str_pad(preg_replace('/\\D/', '', $data['AUFNR']), 12, '0', STR_PAD_LEFT);
        $verid = str_pad(preg_replace('/\\D/', '', $data['PROD_VERSION']), 4, '0', STR_PAD_LEFT);
        $plant = trim($data['plant']);

        try {
            // 2. Cek Kredensial SAP di Session
            $username = session('username') ?? session('sap_username');
            $password = session('password') ?? session('sap_password');
            if (!$username || !$password) {
                return response()->json(['error' => 'Kredensial SAP tidak ditemukan di session.'], 401);
            }

            $flaskBase = rtrim(env('FLASK_API_URL'), '/');

            // ========== LANGKAH 1: UBAH PV DI SAP VIA FLASK ==========
            $changeResp = Http::timeout(60)
                ->withHeaders([
                    'X-SAP-Username' => $username,
                    'X-SAP-Password' => $password,
                ])
                ->post($flaskBase . '/api/change_prod_version', [
                    'AUFNR'        => $aufnr,
                    'PROD_VERSION' => $verid,
                ]);

            if (!$changeResp->successful()) {
                $msg = optional($changeResp->json())['error'] ?? $changeResp->body();
                return response()->json(['error' => 'Flask change_pv error: ' . $msg], $changeResp->status());
            }
            $changeData = $changeResp->json();

            // ========== LANGKAH 2: REFRESH DATA PRO DARI SAP VIA FLASK ==========
            $refreshResp = Http::timeout(120)
                ->withHeaders([
                    'X-SAP-Username' => $username,
                    'X-SAP-Password' => $password,
                ])
                ->get($flaskBase . '/api/refresh-pro', [
                    'plant' => $plant,
                    'aufnr' => $aufnr,
                ]);

            if (!$refreshResp->successful()) {
                $msg = optional($refreshResp->json())['error'] ?? $refreshResp->body();
                return response()->json(['error' => 'Flask refresh-pro error: ' . $msg], $refreshResp->status());
            }
            $payload = $refreshResp->json();

            // ========== LANGKAH 3: SINKRONKAN DATA KE DATABASE LOKAL ==========
            DB::transaction(function () use ($payload, $plant) {
                // Panggil private function untuk menjalankan logika sinkronisasi
                $this->_syncSingleProData($payload, $plant);
            });

            return response()->json([
                'message'       => 'PV berhasil diubah, data PRO berhasil di-refresh & disinkronkan.',
                'change_result' => $changeData,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('changePv exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Exception: ' . $e->getMessage()], 500);
        }
    }

    private function _syncSingleProData(array $refreshedData, string $plant): void
    {
        $t_data  = $refreshedData['T_DATA'] ?? [];
        $t_data1 = $refreshedData['T_DATA1'] ?? [];
        $t_data2 = $refreshedData['T_DATA2'] ?? [];
        $t_data3 = $refreshedData['T_DATA3'] ?? [];
        $t_data4 = $refreshedData['T_DATA4'] ?? [];

        // Validasi data inti yang wajib ada
        if (empty($t_data[0]) || empty($t_data2[0]) || empty($t_data3[0])) {
            throw new \Exception("Data inti (T_DATA, T_DATA2, T_DATA3) tidak lengkap setelah refresh.");
        }

        $proCode = $t_data3[0]['AUFNR'];
        Log::info("Memulai transaksi DB untuk sinkronisasi PRO {$proCode}.");

        // Menggunakan updateOrCreate untuk data utama agar idempotent
        ProductionTData::updateOrCreate(
            ['KUNNR' => $t_data[0]['KUNNR'], 'NAME1' => $t_data[0]['NAME1']],
            $t_data[0] + ['WERKSX' => $plant]
        );

        ProductionTData2::updateOrCreate(
            ['KDAUF' => $t_data2[0]['KDAUF'], 'KDPOS' => $t_data2[0]['KDPOS']],
            $t_data2[0] + ['WERKSX' => $plant]
        );
        
        ProductionTData3::updateOrCreate(
            ['AUFNR' => $proCode],
            $t_data3[0] + ['WERKSX' => $plant]
        );

        ProductionTData1::where('AUFNR', $proCode)->delete();
        if (!empty($t_data1)) {
            $mapped_t1 = array_map(function($row) use ($plant) {
                $formatTanggal = function ($tgl) {
                    if (empty($tgl) || trim($tgl) === '00000000') return null;
                    try { return Carbon::createFromFormat('Ymd', $tgl)->format('d-m-Y'); } catch (\Exception $e) { return null; }
                };

                $sssl1 = $formatTanggal($row['SSSLDPV1'] ?? '');
                $sssl2 = $formatTanggal($row['SSSLDPV2'] ?? '');
                $sssl3 = $formatTanggal($row['SSSLDPV3'] ?? '');

                $partsPv1 = !empty($row['ARBPL1']) ? [strtoupper($row['ARBPL1'])] : [];
                if (!empty($sssl1)) $partsPv1[] = $sssl1;
                $row['PV1'] = !empty($partsPv1) ? implode(' - ', $partsPv1) : null;

                $partsPv2 = !empty($row['ARBPL2']) ? [strtoupper($row['ARBPL2'])] : [];
                if (!empty($sssl2)) $partsPv2[] = $sssl2;
                $row['PV2'] = !empty($partsPv2) ? implode(' - ', $partsPv2) : null;

                $partsPv3 = !empty($row['ARBPL3']) ? [strtoupper($row['ARBPL3'])] : [];
                if (!empty($sssl3)) $partsPv3[] = $sssl3;
                $row['PV3'] = !empty($partsPv3) ? implode(' - ', $partsPv3) : null;

                // [KOREKSI DITERAPKAN] Hapus field asli yang sudah tidak diperlukan lagi
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
    }
}


