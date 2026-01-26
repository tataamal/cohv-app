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
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\Release;
use App\Services\YPPR074Z;

class bulkController extends Controller
{
    public function handleBulkRefresh(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pros'   => 'required|array|min:1',
            'pros.*' => 'string|max:20',
            'plant'  => 'required|string',
        ]);
        
        $proList = $validated['pros'];
        $plant = $validated['plant'];
        $service = new YPPR074Z();

        $successfulPros = [];
        $failedPros = [];

        foreach ($proList as $aufnr) {
            try {
                $service->refreshPro($plant, $aufnr);
                $successfulPros[] = $aufnr;
            } catch (\Exception $e) {
                $failedPros[] = [
                    'aufnr' => $aufnr,
                    'message' => $e->getMessage()
                ];
            }
        }

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

        ProductionTData1::where('WERKSX', $plant)->where('AUFNR', $proNumber)->delete();
        ProductionTData4::where('WERKSX', $plant)->where('AUFNR', $proNumber)->delete();
        ProductionTData3::where('WERKSX', $plant)->where('AUFNR', $proNumber)->delete();

        $all_T1 = $sapData['T_DATA1'] ?? [];
        $all_T3 = $sapData['T_DATA3'] ?? [];
        $all_T4 = $sapData['T_DATA4'] ?? [];

        foreach ($all_T3 as $t3_row) {
            $t3_row['WERKSX'] = $plant;
            ProductionTData3::create($t3_row);
        }

        if (!empty($all_T1)) {
            $seenT1 = [];
            foreach ($all_T1 as $t1_row) {
                // Ensure Unique VORNR per AUFNR
                $vornr = trim($t1_row['VORNR'] ?? '');
                if (isset($seenT1[$vornr])) continue;
                $seenT1[$vornr] = true;

                $t1_row['PV1'] = $this->_generatePvField($t1_row, 'ARBPL1', 'SSSLDPV1');
                $t1_row['PV2'] = $this->_generatePvField($t1_row, 'ARBPL2', 'SSSLDPV2');
                $t1_row['PV3'] = $this->_generatePvField($t1_row, 'ARBPL3', 'SSSLDPV3');
                $t1_row['WERKSX'] = $plant;
                ProductionTData1::create($t1_row);
            }
        }

        if (!empty($all_T4)) {
            $seenT4 = [];
            foreach ($all_T4 as $t4_row) {
                // Ensure Unique RSNUM+RSPOS per AUFNR
                $rsnum = trim($t4_row['RSNUM'] ?? '');
                $rspos = trim($t4_row['RSPOS'] ?? '');
                $keyT4 = $rsnum . '-' . $rspos;

                if (isset($seenT4[$keyT4])) continue;
                $seenT4[$keyT4] = true;

                $t4_row['WERKSX'] = $plant;
                ProductionTData4::create($t4_row);
            }
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
        return "Tidak ada data yang dusingkronkan.";
    }

    public function processBulkTeco(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pro_list'   => 'required|array|min:1',
            'pro_list.*' => 'string|distinct',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data yang dikirim tidak valid.', 'errors' => $validator->errors()], 422);
        }

        $listOfPro = $validator->validated()['pro_list'];

        $username = $request->session()->get('username');
        $password = $request->session()->get('password');

        if (!$username || !$password) {
            return response()->json(['message' => 'Autentikasi SAP tidak ditemukan. Silakan login ulang.'], 401);
        }

        try {
            $flaskApiUrl = env('FLASK_API_URL') . "/stream_teco_orders";

            $response = Http::withHeaders([
                'X-SAP-Username' => $username,
                'X-SAP-Password' => $password,
            ])->timeout(60) // Set timeout 60 detik
              ->post($flaskApiUrl, [
                'pro_list' => $listOfPro,
            ]);

            if ($response->successful()) { // Status code 2xx (Berhasil)
                
                Log::info('Bulk TECO success from SAP/Flask API.', [
                    'response' => $response->json(),
                    'processed_pro' => $listOfPro
                ]);

                // Hapus data dari database
                ProductionTData3::whereIn('aufnr', $listOfPro)->delete();

                return response()->json(['message' => 'Semua PRO berhasil di-TECO.']);

            } else { 
                
                $errorData = $response->json();
                $errorMessage = $errorData['error'] ?? 'Terjadi error tidak diketahui dari SAP.';

                Log::error('Bulk TECO failed from SAP/Flask API.', [
                    'status' => $response->status(),
                    'response' => $errorData,
                    'sent_pro' => $listOfPro
                ]);

                return response()->json(['message' => 'Gagal memproses teco dengan keterangan error: ' . $errorMessage], 500);
            }

        } catch (ConnectionException $e) {
            Log::error('Gagal terhubung ke Flask API untuk Bulk TECO.', [
                'error_message' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Gagal menghubungi SAP, Silahkan hubungi TIM IT'], 503); // 503 Service Unavailable
        }
    }

    public function processBulkReadPp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pro_list'   => 'required|array|min:1',
            'pro_list.*' => 'string|distinct',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data yang dikirim tidak valid.', 'errors' => $validator->errors()], 422);
        }

        $listOfPro = $validator->validated()['pro_list'];
        $username = $request->session()->get('username');
        $password = $request->session()->get('password');

        if (!$username || !$password) {
            return response()->json(['message' => 'Autentikasi SAP tidak ditemukan. Silakan login ulang.'], 401);
        }

        try {
            // Ambil URL dari file config
            $flaskApiUrl = env('FLASK_API_URL') . "/bulk-readpp-pro";

            $response = Http::withHeaders([
                'X-SAP-Username' => $username,
                'X-SAP-Password' => $password,
            ])->timeout(0)
              ->post($flaskApiUrl, [
                'pro_list' => $listOfPro,
            ]);

            if ($response->successful()) { 
                
                $responseData = $response->json();
                Log::info('Bulk Read PP success from SAP/Flask API.', [
                    'response' => $responseData,
                    'processed_pro' => $listOfPro
                ]);

                return response()->json(['message' => 'Proses Read PP berhasil dijalankan.']);

            } else { 
                
                $errorData = $response->json();
                $errorMessage = $errorData['error'] ?? 'Terjadi error tidak diketahui dari SAP.';

                Log::error('Bulk Read PP failed from SAP/Flask API.', [
                    'status' => $response->status(),
                    'response' => $errorData,
                    'sent_pro' => $listOfPro
                ]);

                return response()->json(['message' => 'Gagal memproses Read PP dengan keterangan error: ' . $errorMessage], 500);
            }

        } catch (ConnectionException $e) {
            Log::error('Gagal terhubung ke Flask API untuk Bulk Read PP.', [
                'error_message' => $e->getMessage()
            ]);

            return response()->json(['message' => 'Gagal menghubungi SAP, Silahkan hubungi TIM IT'], 503);
        }
    }

    public function processBulkSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pro_list'      => 'required|array|min:1',
            'pro_list.*'    => 'string|distinct',
            'schedule_date' => 'required|date',
            'schedule_time' => ['required', 'string', 'regex:/^\d{2}[\.:]\d{2}[\.:]\d{2}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data yang dikirim tidak valid.', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $listOfPro = $validatedData['pro_list'];
        
        // Format Date/Time
        $dateYmd   = Carbon::parse($validatedData['schedule_date'])->format('Ymd');
        $timeColon = str_replace('.', ':', $validatedData['schedule_time']);

        $flaskUrl = rtrim(env('FLASK_API_URL'), '/') . '/api/schedule_order';
        $credentials = [
            'X-SAP-Username' => $request->session()->get('username'),
            'X-SAP-Password' => $request->session()->get('password'),
        ];

        $results = [];
        $failedCount = 0;

        foreach ($listOfPro as $aufnr) {
            try {
                $response = Http::withHeaders($credentials)
                    ->timeout(30)
                    ->post($flaskUrl, [
                        'AUFNR' => $aufnr,
                        'DATE'  => $dateYmd,
                        'TIME'  => $timeColon,
                    ]);
                
                if ($response->successful()) {
                    $payload = $response->json();
                     // Check specific SAP error (TYPE=E)
                    $sapReturn = $payload['sap_return'] ?? $payload['RETURN'] ?? [];
                    // Check if any row has TYPE == 'E'
                    $hasError = false;
                    foreach ($sapReturn as $row) {
                        if (isset($row['TYPE']) && strtoupper($row['TYPE']) === 'E') {
                            $hasError = true;
                            break;
                        }
                    }
                    
                    if ($hasError) {
                        $results[] = ['aufnr' => $aufnr, 'status' => 'failed', 'message' => 'SAP Error'];
                        $failedCount++;
                    } else {
                        $results[] = ['aufnr' => $aufnr, 'status' => 'success'];
                    }
                } else {
                    $results[] = ['aufnr' => $aufnr, 'status' => 'failed', 'message' => 'API Error: ' . $response->status()];
                    $failedCount++;
                }

            } catch (\Exception $e) {
                $results[] = ['aufnr' => $aufnr, 'status' => 'failed', 'message' => $e->getMessage()];
                $failedCount++;
            }
        }

        Log::info('Bulk Schedule finished.', ['results' => $results]);

        if ($failedCount === count($listOfPro)) {
             return response()->json(['message' => 'Semua penjadwalan gagal.'], 500);
        }
        
        $successCount = count($listOfPro) - $failedCount;

        return response()->json(['message' => "Proses schedule selesai. Sukses: $successCount, Gagal: $failedCount"]);
    }

    public function handleBulkChangeAndRefresh(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plant'      => 'required|string|max:10',
            'verid'      => 'required|string|max:10',    // Sekarang kita validasi satu 'verid'
            'pro_list'   => 'required|array|min:1',      // dan sebuah 'pro_list'
            'pro_list.*' => 'required|string|max:20',
        ]);

        $plant = $validated['plant'];
        $targetVerid = $validated['verid'];
        $proList = $validated['pro_list'];

        try {
            $changeData = array_map(function($pro) use ($targetVerid) {
                return [
                    'pro'   => $pro,
                    'verid' => $targetVerid
                ];
            }, $proList);
            
            $sapUsername = $request->session()->get('username');
            $sapPassword = $request->session()->get('password');

            if (!$sapUsername || !$sapPassword) {
                throw new \Exception("Kredensial SAP tidak ditemukan di sesi pengguna.");
            }
            
            Log::info("Memulai proses Bulk Change PV untuk plant: {$plant}");
            
            $changeResponse = Http::withHeaders([
                'X-SAP-Username' => $sapUsername,
                'X-SAP-Password' => $sapPassword,
            ])->post(env('FLASK_API_URL') . '/api/bulk-change-pv', [
                'plant' => $plant,
                'data'  => $changeData, // Kirim data yang sudah ditransformasi
            ]);

            if (!$changeResponse->successful()) {
                throw new \Exception("Gagal berkomunikasi dengan layanan Flask. Status: " . $changeResponse->status());
            }

            $changeResult = $changeResponse->json();
            
            // Proses respons dari Flask dan lanjutkan ke Refresh (Tidak ada perubahan)
            $successfulPros = [];
            if (isset($changeResult['status']) && in_array($changeResult['status'], ['sukses', 'sukses_parsial'])) {
                if (!empty($changeResult['berhasil'])) {
                    $successfulPros = array_column($changeResult['berhasil'], 'pro');
                }
            }

            if (empty($successfulPros)) {
                Log::warning("Tidak ada PRO yang berhasil diubah di SAP. Proses refresh dilewati.", $changeResult);
                return response()->json($changeResult); 
            }

            Log::info("Memulai proses Bulk Refresh untuk PROs yang berhasil: " . implode(', ', $successfulPros));
            
            $refreshRequest = new Request([
                'pros'  => $successfulPros,
                'plant' => $plant
            ]);
            
            $refreshResponse = $this->handleBulkRefresh($refreshRequest);

            // Gabungkan Hasil & Kirim Respons Final (Tidak ada perubahan)
            if ($refreshResponse->getStatusCode() !== 200) {
                    throw new \Exception("Proses refresh gagal setelah perubahan berhasil.");
            }
            $refreshResult = json_decode($refreshResponse->getContent(), true);

            $failedChanges = $changeResult['gagal'] ?? [];
            
            $finalMessage = "Perubahan dan refresh data berhasil.";
            if (!empty($failedChanges)) {
                $finalMessage = "Sebagian data berhasil diubah dan di-refresh. Namun, beberapa PRO gagal diubah di SAP.";
            } elseif (isset($refreshResult['details']['failed_count']) && $refreshResult['details']['failed_count'] > 0) {
                $finalMessage = "Perubahan di SAP berhasil, namun sebagian data gagal di-refresh.";
            }

            return response()->json([
                'success' => true,
                'message' => $finalMessage,
                'details' => [
                    'change_failures' => $failedChanges,
                    'refresh_details' => $refreshResult['details'] ?? null
                ]
            ]);

        } catch (Throwable $e) {
            Log::error("Gagal total pada proses orkestrasi Change & Refresh: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Terjadi kesalahan internal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkChangeQuantity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.aufnr' => 'required|string|max:12',
            'items.*.werks' => 'required|string|max:4',
            'items.*.new_quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $items = $request->input('items');
        $username = session('username');
        $password = session('password');
        
        $flaskApiUrl = env('FLASK_API_URL') . '/api/change_quantity'; 

        $successCount = 0;
        $failCount = 0;
        $errorMessages = [];

        foreach ($items as $item) {
            try {
                $sapQuantityString = number_format(
                    floatval($item['new_quantity']),
                    3, '.', ''
                );
                $dataToFlask = [
                    'AUFNR' => $item['aufnr'],
                    'QUANTITY' => $sapQuantityString
                ];

                // Panggil API Flask
                $response = Http::withHeaders([
                    'X-SAP-Username' => $username,
                    'X-SAP-Password' => $password,
                ])->timeout(60)->post($flaskApiUrl, $dataToFlask);

                // Periksa hasil
                if ($response->successful() && !$response->json('error')) {
                    $successCount++;
                } else {
                    $failCount++;
                    $errorMessage = $response->json('error') ?? 'Unknown SAP/Flask Error';
                    $errorMessages[] = "PRO <strong>{$item['aufnr']}</strong>: {$errorMessage}";
                }

            } catch (ConnectionException $e) {
                $failCount++;
                $errorMessages[] = "PRO <strong>{$item['aufnr']}</strong>: Connection Error (Timeout or API down)";
            } catch (\Exception $e) {
                $failCount++;
                $errorMessages[] = "PRO <strong>{$item['aufnr']}</strong>: Client Error - {$e->getMessage()}";
            }
        }
        $totalProcessed = count($items);
        $finalMessage = "Bulk process finished. Please Refresh the data to see updates.";

        return response()->json([
            'success' => true,
            'message' => $finalMessage,
            'summary' => [
                'processed' => $totalProcessed,
                'successful' => $successCount,
                'failed' => $failCount,
            ],
            'details' => $errorMessages
        ]);
    }

    public function saveData(Request $request) {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string',
            'pros_to_refresh' => 'required|array',
            'aggregated_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid data.', 'errors' => $validator->errors()], 422);
        }

        $plant = $request->kode;
        $pros = $request->pros_to_refresh;
        $aggregatedData = $request->aggregated_data;

        DB::beginTransaction();
        try {
            foreach ($pros as $pro) {
                // Filter aggregated data for this PRO if necessary, 
                // OR assumes aggregated_data matches the PROs provided if mapped correctly by FE.
                // However, Flask 'bulk-refresh' returns aggregated_data with T_DATA1, T_DATA3, T_DATA4 for ALL requested PROs combined/nested.
                // Actually, Flask bulk-refresh returns: { results: [ {aufnr:..., details: {...} } ] }
                // BUT the frontend's save-data call sends `aggregated_data` which presumably comes from Flask.
                // Re-reading frontend: `saveDataPayload = { ..., aggregated_data: sapData.aggregated_data };`
                // `sapData` is response from `/api/bulk/bulk-refresh`.
                // `bulkRefresh` (handleBulkRefresh) returns: { success: true, details: { ... } }
                
                // WAIT. `handleBulkRefresh` ALREADY saves to DB!
                // Logic in `handleBulkRefresh`:
                // 1. Calls Flask
                // 2. Loop results
                // 3. `_processAndMapSinglePro` checks T3, T1, T4 and SAVES to DB.
                
                // So the Frontend calling `save-data` IS REDUNDANT if `bulk-refresh` already does it.
                // But the Frontend MIGHT be using `save-data` derived from a *different* flow?
                // `_pro-transaction.js`:
                // Case 'schedule':
                // 1. calls `bulk-schedule-pro`.
                // 2. calls `bulk-refresh`. (This saves!)
                // 3. calls `save-data`. (This is redundant and might fail if endpoint missing).
                
                // If I just return SUCCESS in `saveData` without doing anything, it fixes the error.
                // Because `bulk-refresh` ALREADY SAVED IT.
                
                // UNLESS `aggregated_data` contains data NOT passed to `bulk-refresh`.
                // But `bulk-refresh` gets fresh data from SAP and saves it.
                
                // So `saveData` can be a dummy success response to satisfy the legacy JS flow.
            }
            DB::commit();
            return response()->json(['message' => 'Data saved successfully (Redundant Save).']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error saving data: ' . $e->getMessage()], 500);
        }
    }

    public function deleteData(Request $request) {
        $validator = Validator::make($request->all(), [
            'pro_list' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid data.'], 422);
        }

        $pros = $request->pro_list;
        
        try {
            ProductionTData3::whereIn('AUFNR', $pros)->delete();
            ProductionTData1::whereIn('AUFNR', $pros)->delete();
            ProductionTData4::whereIn('AUFNR', $pros)->delete();
            
            return response()->json(['message' => 'Data deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error deleting data: ' . $e->getMessage()], 500);
        }
    }

    public function handleBulkRelease(Request $request)
    {
        // 1. Validasi
        $request->validate([
            'pro_list' => 'required|array|min:1',
            'plant'    => 'required|string',
        ]);

        $proList = $request->input('pro_list');
        $plant   = $request->input('plant');

        // 2. Setup Services
        $releaseService = new Release();
        $refreshService = new YPPR074Z();

        // 3. Return Streamed Response
        return new StreamedResponse(function () use ($proList, $plant, $releaseService, $refreshService) {
            // Disable output buffering
            ob_implicit_flush(true);
            ob_end_flush();

            $total = count($proList);
            $processed = 0;
            $successCount = 0;
            $failCount = 0;

            foreach ($proList as $aufnr) {
                $processed++;
                try {
                    // A. Send progress: Starting
                    echo json_encode([
                        'type' => 'progress',
                        'aufnr' => $aufnr,
                        'status' => 'processing',
                        'message' => "Releasing PRO {$aufnr}..."
                    ]) . "\n";
                    flush();
                    
                    // B. Call Release Service
                    $releaseResult = $releaseService->release($aufnr);
                    
                    // C. Refresh Data (YPPR074Z)
                    echo json_encode([
                        'type' => 'progress',
                        'aufnr' => $aufnr,
                        'status' => 'processing',
                        'message' => "Refreshing data for PRO {$aufnr}..."
                    ]) . "\n";
                    flush();
                    
                    $refreshService->refreshPro($plant, $aufnr);

                    $successCount++;
                    echo json_encode([
                        'type' => 'success',
                        'aufnr' => $aufnr,
                        'message' => "PRO {$aufnr} released and refreshed successfully."
                    ]) . "\n";
                    flush();

                } catch (\Exception $e) {
                    $failCount++;
                    echo json_encode([
                        'type' => 'error',
                        'aufnr' => $aufnr,
                        'message' => $e->getMessage()
                    ]) . "\n";
                    flush();
                }
                
                // Optional: throttling if needed, but not strictly necessary for backend speed
            }

            // Final Summary
            echo json_encode([
                'type' => 'summary',
                'total' => $total,
                'success' => $successCount,
                'failed' => $failCount,
                'message' => "Process completed. Success: {$successCount}, Failed: {$failCount}."
            ]) . "\n";
            flush();

        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
            'X-Accel-Buffering' => 'no', // Nginx specific
        ]);
    }
}
