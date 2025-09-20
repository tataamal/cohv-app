<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ProductionTData1;
use App\Models\ProductionTData3;
use App\Models\ProductionTData4;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\ConnectionException;
use Carbon\Carbon;
use Throwable;

class bulkController extends Controller
{
    public function handleBulkRefresh(Request $request): JsonResponse
    {
        // 1. Validasi input
        $validated = $request->validate([
            'pros'   => 'required|array|min:1',
            'pros.*' => 'string|max:20',
            'plant'  => 'required|string',
        ]);
        
        $proList = $validated['pros'];
        $plant = $validated['plant'];

        try {
            // 2. Panggil service Flask untuk mendapatkan data mentah
            $flaskResponse = $this->_callBulkFlaskService('/api/bulk-refresh-pro', $proList, $plant);
            $results = $flaskResponse['results'] ?? [];

            $successfulPros = [];
            $failedPros = [];

            // Memulai transaksi database. Jika ada error, semua akan di-rollback.
            DB::beginTransaction();

            // 3. Iterasi hasil dari Flask dan proses satu per satu
            foreach ($results as $result) {
                if (isset($result['status']) && $result['status'] === 'sukses') {
                    // Jika PRO berhasil diambil dari SAP, proses data dan simpan ke DB
                    $proNumber = $result['aufnr'];
                    $sapData = $result['details'];
                    
                    // Panggil private function untuk mapping dan update DB
                    $this->_processAndMapSinglePro($proNumber, $plant, $sapData);

                    $successfulPros[] = $proNumber;
                } else {
                    // Jika PRO gagal diambil dari SAP, catat kegagalannya
                    $failedPros[] = [
                        'aufnr' => $result['aufnr'] ?? 'Unknown',
                        'message' => $result['message'] ?? 'Unknown error from Flask service'
                    ];
                }
            }

            // Jika semua proses di atas berhasil, commit transaksi
            DB::commit();

            // 4. Buat pesan respons yang informatif untuk frontend
            $message = $this->_buildResponseMessage(count($successfulPros), $failedPros);

            return response()->json([
                'success' => true, 
                'message' => $message,
                'details' => [
                    'successful_count' => count($successfulPros),
                    'failed_count' => count($failedPros),
                    'failed_pros' => $failedPros
                ]
            ]);

        } catch (Throwable $e) {
            // Jika terjadi error di mana pun, rollback transaksi dan lapor
            DB::rollBack();
            Log::error("Gagal total pada proses bulk refresh: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function _callBulkFlaskService(string $endpoint, array $proList, string $plant): array
    {
        $apiUrl = env('FLASK_API_URL') . $endpoint;
        $username = session('username');
        $password = session('password');

        if (!$username || !$password) {
            throw new \Exception('Konfigurasi API atau otentikasi sesi tidak lengkap.');
        }

        Log::info("Mengirim permintaan bulk ke Flask. Endpoint: {$endpoint}, Jumlah PRO: " . count($proList));

        // Set timeout ke 0 berarti tidak ada batas waktu
        $response = Http::timeout(0)->withHeaders([
            'X-SAP-Username' => $username,
            'X-SAP-Password' => $password,
        ])->post($apiUrl, [
            'plant' => $plant,
            'pros'  => $proList,
        ]);

        if ($response->failed()) {
            $errorBody = $response->body();
            Log::error("Flask service error: " . $errorBody);
            throw new \Exception('Gagal pada saat menghubungi SAP. Detail: ' . $errorBody);
        }

        return $response->json();
    }

    private function _processAndMapSinglePro(string $proNumber, string $plant, array $sapData): void
    {
        Log::info("Memproses dan mapping data untuk PRO: {$proNumber}");

        // 1. Hapus data lama yang spesifik untuk PRO ini
        ProductionTData1::where('WERKSX', $plant)->where('AUFNR', $proNumber)->delete();
        ProductionTData4::where('WERKSX', $plant)->where('AUFNR', $proNumber)->delete();
        ProductionTData3::where('WERKSX', $plant)->where('AUFNR', $proNumber)->delete();

        $all_T1 = $sapData['T_DATA1'] ?? [];
        $all_T3 = $sapData['T_DATA3'] ?? [];
        $all_T4 = $sapData['T_DATA4'] ?? [];

        // 2. Insert data T3 (parent)
        foreach ($all_T3 as $t3_row) {
            $t3_row['WERKSX'] = $plant;
            ProductionTData3::create($t3_row);
        }

        // 3. Insert data T1 (child) dengan mapping
        foreach ($all_T1 as $t1_row) {
            $t1_row['PV1'] = $this->_generatePvField($t1_row, 'ARBPL1', 'SSSLDPV1');
            $t1_row['PV2'] = $this->_generatePvField($t1_row, 'ARBPL2', 'SSSLDPV2');
            $t1_row['PV3'] = $this->_generatePvField($t1_row, 'ARBPL3', 'SSSLDPV3');
            $t1_row['WERKSX'] = $plant;
            ProductionTData1::create($t1_row);
        }

        // 4. Insert data T4 (child)
        foreach ($all_T4 as $t4_row) {
            $t4_row['WERKSX'] = $plant;
            ProductionTData4::create($t4_row);
        }
        Log::info("Selesai memproses PRO: {$proNumber}");
    }

    private function _generatePvField(array $data, string $arbplKey, string $ssslKey): ?string
    {
        $formatTanggal = function ($tgl) {
            if (empty($tgl) || trim($tgl) === '00000000') return null;
            try { return Carbon::createFromFormat('Ymd', $tgl)->format('d-m-Y'); }
            catch (\Exception $e) { return null; }
        };

        $datePart = $formatTanggal($data[$ssslKey] ?? '');
        $arbplPart = !empty($data[$arbplKey]) ? strtoupper($data[$arbplKey]) : null;

        $parts = array_filter([$arbplPart, $datePart]);
        
        return !empty($parts) ? implode(' - ', $parts) : null;
    }

    private function _buildResponseMessage(int $successCount, array $failedPros): string
    {
        if ($successCount > 0 && count($failedPros) === 0) {
            return "{$successCount} PRO berhasil disinkronkan.";
        }
        if ($successCount > 0 && count($failedPros) > 0) {
            return "Proses selesai. {$successCount} PRO berhasil disinkronkan, namun " . count($failedPros) . " PRO gagal.";
        }
        if ($successCount === 0 && count($failedPros) > 0) {
            return "Semua PRO yang dipilih gagal disinkronkan. Mohon periksa detailnya.";
        }
        return "Tidak ada data yang diproses.";
    }

    public function processBulkTeco(Request $request)
    {
        // =======================================================
        // LANGKAH 1: VALIDASI INPUT
        // =======================================================
        $validator = Validator::make($request->all(), [
            'pro_list'   => 'required|array|min:1',
            'pro_list.*' => 'string|distinct',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data yang dikirim tidak valid.', 'errors' => $validator->errors()], 422);
        }

        $listOfPro = $validator->validated()['pro_list'];

        // =======================================================
        // LANGKAH 2: AMBIL KREDENSIAL DARI SESSION
        // =======================================================
        $username = $request->session()->get('username');
        $password = $request->session()->get('password');

        if (!$username || !$password) {
            return response()->json(['message' => 'Autentikasi SAP tidak ditemukan. Silakan login ulang.'], 401);
        }

        // =======================================================
        // LANGKAH 3: PROSES HIT API FLASK DENGAN TRY-CATCH
        // =======================================================
        try {
            $flaskApiUrl = env('FLASK_API_URL') . "/api/bulk-teco-pro";

            // Mengirim request ke Flask dengan header dan body
            $response = Http::withHeaders([
                'X-SAP-Username' => $username,
                'X-SAP-Password' => $password,
            ])->timeout(60) // Set timeout 60 detik
              ->post($flaskApiUrl, [
                'pro_list' => $listOfPro,
            ]);

            // --- JIKA FLASK MEMBERIKAN RESPONS (Berhasil atau Gagal dari sisi Flask) ---
            if ($response->successful()) { // Status code 2xx (Berhasil)
                
                Log::info('Bulk TECO success from SAP/Flask API.', [
                    'response' => $response->json(),
                    'processed_pro' => $listOfPro
                ]);

                // Hapus data dari database
                ProductionTData3::whereIn('aufnr', $listOfPro)->delete();

                return response()->json(['message' => 'Semua PRO berhasil di-TECO.']);

            } else { // Status code 4xx atau 5xx (Gagal dari sisi Flask)
                
                $errorData = $response->json();
                // Ambil pesan error dari respons Flask, jika tidak ada, beri pesan default
                $errorMessage = $errorData['error'] ?? 'Terjadi error tidak diketahui dari SAP.';

                Log::error('Bulk TECO failed from SAP/Flask API.', [
                    'status' => $response->status(),
                    'response' => $errorData,
                    'sent_pro' => $listOfPro
                ]);

                return response()->json(['message' => 'Gagal memproses teco dengan keterangan error: ' . $errorMessage], 500);
            }

        } catch (ConnectionException $e) {
            // --- JIKA GAGAL TERHUBUNG KE SERVER FLASK ---
            Log::error('Gagal terhubung ke Flask API untuk Bulk TECO.', [
                'error_message' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Gagal menghubungi SAP, Silahkan hubungi TIM IT'], 503); // 503 Service Unavailable
        }
    }
}
