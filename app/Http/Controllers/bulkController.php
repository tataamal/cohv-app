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
        $validated = $request->validate([
            'pros'   => 'required|array|min:1',
            'pros.*' => 'string|max:20',
            'plant'  => 'required|string',
        ]);
        
        $proList = $validated['pros'];
        $plant = $validated['plant'];

        try {
            $flaskResponse = $this->_callBulkFlaskService('/api/bulk-refresh-pro', $proList, $plant);
            $results = $flaskResponse['results'] ?? [];

            $successfulPros = [];
            $failedPros = [];

            DB::beginTransaction();

            foreach ($results as $result) {
                if (isset($result['status']) && $result['status'] === 'sukses') {
                    $proNumber = $result['aufnr'];
                    $sapData = $result['details'];
                    
                    $this->_processAndMapSinglePro($proNumber, $plant, $sapData);

                    $successfulPros[] = $proNumber;
                } else {
                    $failedPros[] = [
                        'aufnr' => $result['aufnr'] ?? 'Unknown',
                        'message' => $result['message'] ?? 'Unknown error from Flask service'
                    ];
                }
            }

            DB::commit();

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

        foreach ($all_T1 as $t1_row) {
            $t1_row['PV1'] = $this->_generatePvField($t1_row, 'ARBPL1', 'SSSLDPV1');
            $t1_row['PV2'] = $this->_generatePvField($t1_row, 'ARBPL2', 'SSSLDPV2');
            $t1_row['PV3'] = $this->_generatePvField($t1_row, 'ARBPL3', 'SSSLDPV3');
            $t1_row['WERKSX'] = $plant;
            ProductionTData1::create($t1_row);
        }

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
            $flaskApiUrl = env('FLASK_API_URL') . "/api/bulk-teco-pro";

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
            $flaskApiUrl = env('FLASK_API_URL') . "/api/bulk-readpp-pro";

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
            'plant'         => 'required|string|size:4',
            'schedule_date' => 'required|date',
            'schedule_time' => ['required', 'string', 'regex:/^\d{2}[\.:]\d{2}[\.:]\d{2}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data yang dikirim tidak valid.', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $listOfPro = $validatedData['pro_list'];
        $plant = $validatedData['plant'];

        try {
            $scheduleApiUrl = env('FLASK_API_URL') . "/api/bulk-schedule-pro";
            $credentials = [
                'X-SAP-Username' => $request->session()->get('username'),
                'X-SAP-Password' => $request->session()->get('password'),
            ];

            $scheduleResponse = Http::withHeaders($credentials)
                ->timeout(0)
                ->post($scheduleApiUrl, [
                    'pro_list'      => $listOfPro,
                    'schedule_date' => $validatedData['schedule_date'],
                    'schedule_time' => $validatedData['schedule_time'],
                ]);

            if (!$scheduleResponse->successful()) {
                $errorData = $scheduleResponse->json();
                $errorMessage = $errorData['error'] ?? 'Gagal saat proses scheduling di API.';
                Log::error('Bulk Schedule API call failed.', ['response' => $errorData]);
                return response()->json(['message' => 'Gagal memproses schedule: ' . $errorMessage], 500);
            }

        } catch (ConnectionException $e) {
            Log::error('Gagal terhubung ke Flask API untuk Bulk Schedule.', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menghubungi service scheduler, Silahkan hubungi TIM IT'], 503);
        }

        Log::info('Scheduling berhasil, melanjutkan ke proses refresh data untuk PROs:', $listOfPro);

        $refreshResult = $this->_callBulkFlaskService(  "/api/bulk-refresh-pro", $listOfPro, $plant);

        $successCount = 0;
        $failedPros = [];

        if (!empty($refreshResult['success_details'])) {
            foreach ($refreshResult['success_details'] as $detail) {
                try {
                    $this->_processAndMapSinglePro($detail['pro_number'], $plant, $detail['sap_response']);
                    $successCount++;
                } catch (\Exception $e) {
                    Log::error('Gagal memproses/menyimpan data refresh untuk PRO: ' . $detail['pro_number'], ['error' => $e->getMessage()]);
                    $failedPros[] = $detail['pro_number'];
                }
            }
        }

        if (!empty($refreshResult['error_details'])) {
            foreach ($refreshResult['error_details'] as $errorDetail) {
                $failedPros[] = $errorDetail['pro_number'];
            }
        }
        
        $finalMessage = $this->_buildResponseMessage($successCount, $failedPros);
        
        return response()->json(['message' => "Proses schedule berhasil. " . $finalMessage]);
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
            ])->post('http://192.168.90.27:6001/api/bulk-change-pv', [
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
        
        $flaskApiUrl = 'http://192.168.90.27:6001//api/change_quantity'; 

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
}
