<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductionTData4;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class Data4Controller extends Controller
{
    public function addComponent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'iv_aufnr' => 'required|string',
            'iv_matnr' => 'required|string',
            'iv_bdmng' => 'required|string',
            'iv_meins' => 'required|string',
            'iv_werks' => 'required|string',
            'iv_lgort' => 'required|string',
            'iv_vornr' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        try {
            // ==================== PERBAIKAN DI SINI ====================
            // 1. Ubah semua key request menjadi huruf kapital
            $payload = array_change_key_case($request->all(), CASE_UPPER);
            
            // 2. Hapus _token jika ada (tidak diperlukan oleh Flask)
            unset($payload['_TOKEN']);
            // =========================================================

            // ========== PANGGIL API UNTUK ADD COMPONENT ==========
            $flaskEndpoint = 'http://127.0.0.1:8050/api/add_component';
            $response = Http::timeout(60)->withHeaders([
                    'X-SAP-Username' => session('username'),
                    'X-SAP-Password' => session('password'),
                ])->post($flaskEndpoint, $payload); // 3. Gunakan $payload yang sudah diubah

            // ... sisa dari kode Anda sudah benar ...
            if (!$response->successful() || !$response->json()['success']) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['return_message'] ?? $errorBody['error'] ?? 'Gagal menambahkan komponen di SAP.';
                return response()->json(['success' => false, 'message' => $errorMessage], $response->status());
            }

            // ... (logika refresh dan response Anda) ...
            $aufnr = str_pad($request->input('iv_aufnr'), 12, '0', STR_PAD_LEFT);
            $authoritativePlant = ProductionTData4::where('AUFNR', $aufnr)->value('WERKSX'); // Ambil nilai kolom WERKSX saja
            
            $refreshResult = $this->refreshAndSyncOrderByAufnr($aufnr, $authoritativePlant);

            if (!$refreshResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Komponen berhasil ditambahkan di SAP, silahkan refres',
                    'warning' => $refreshResult['error']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Komponen berhasil ditambahkan dan data telah disinkronkan.',
                'components' => $refreshResult['data']['T4']
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json(['success' => false, 'message' => 'Service tidak dapat dijangkau: ' . $e->getMessage()], 503);
        }
    }

    public function deleteBulkComponents(Request $request)
    {
        // 1. Validasi input dari frontend
        $validator = Validator::make($request->all(), [
            'aufnr' => 'required|string',
            'plant' => 'required|string',
            'components' => 'required|array|min:1',
            'components.*.rspos' => 'required|string', // Pastikan setiap komponen punya rspos
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $aufnr = $request->input('aufnr');
        $components = $request->input('components');

        $successCount = 0;
        $failedCount = 0;
        $errorMessages = [];

        try {
            $aufnr = $request->input('aufnr');
            $plant = $request->input('plant');

            $flaskEndpoint = 'http://127.0.0.1:8050/api/delete_component'; // Ganti dengan URL Flask Anda

            // 2. Loop untuk setiap komponen yang dipilih
            foreach ($components as $component) {
                $payload = [
                    'IV_AUFNR' => $aufnr,
                    'IV_RSPOS' => $component['rspos'],
                ];

                // 3. Panggil endpoint Flask untuk setiap komponen
                $response = Http::timeout(30)->withHeaders([
                    'X-SAP-Username' => session('username'),
                    'X-SAP-Password' => session('password'),
                ])->post($flaskEndpoint, $payload);

                if ($response->successful() && $response->json()['success']) {
                    $successCount++;
                } else {
                    $failedCount++;
                    $errorMessages[] = $response->json()['return_message'] ?? "Gagal menghapus item RSPOS {$component['rspos']}";
                }
            }

            // 4. Siapkan pesan ringkasan
            $summaryMessage = "Proses selesai: {$successCount} komponen berhasil dihapus, {$failedCount} komponen gagal dihapus.";

            return response()->json([
                'success' => $failedCount === 0,
                'message' => $summaryMessage,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'errors' => $errorMessages,
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json(['success' => false, 'message' => 'Service tidak dapat dijangkau: ' . $e->getMessage()], 503);
        }
    }

    public function update(Request $request)
    {
        // 1. Validasi data masuk dari form
        $validated = $request->validate([
            'aufnr' => 'required|string',
            'rspos' => 'required|string',
            'plant' => 'required|string', // Ditambahkan karena diperlukan untuk sinkronisasi
            'matnr' => 'sometimes|nullable|string',
            'bdmng' => 'sometimes|nullable|numeric',
            'lgort' => 'sometimes|nullable|string',
            'sobkz' => 'sometimes|nullable|in:0,1',
        ]);

        // 2. Cek kredensial SAP dari session
        $sapUser = session('username');
        $sapPass = session('password');
        if (!$sapUser || !$sapPass) {
            return response()->json(['message' => 'Otentikasi SAP tidak valid atau sesi telah berakhir.'], 401);
        }

        // 3. Siapkan request ke API Flask
        $flaskApiUrl = rtrim(env('FLASK_API_URL'), '/') . '/api/edit_component';

        try {
            $response = Http::timeout(60) // Timeout 60 detik
                ->withHeaders([
                    'X-SAP-Username' => $sapUser,
                    'X-SAP-Password' => $sapPass,
                ])
                ->post($flaskApiUrl, $validated);

            // 4. Handle response dari Flask
            if ($response->successful()) {
                // Jika Flask mengembalikan sukses (2xx), lanjutkan ke sinkronisasi
                Log::info('Transaksi SAP berhasil, memulai proses refresh dan sinkronisasi...');
                $syncResult = $this->refreshAndSyncOrderByAufnr($validated['aufnr'], $validated['plant']);

                $originalResponse = $response->json();

                if ($syncResult['success']) {
                    Log::info('Sinkronisasi data berhasil.');
                    $originalResponse['sync_message'] = 'Data berhasil disinkronkan ke database lokal.';
                } else {
                    Log::error('Sinkronisasi GAGAL setelah transaksi SAP berhasil.', ['error' => $syncResult['error']]);
                    $originalResponse['sync_warning'] = 'Transaksi SAP berhasil, tetapi sinkronisasi data lokal gagal. ' . $syncResult['error'];
                }

                return response()->json($originalResponse);

            } else {
                // Jika Flask mengembalikan error (4xx atau 5xx)
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? 'Terjadi kesalahan pada API service.';
                Log::error('Flask API Error:', $errorData);
                return response()->json(['message' => $errorMessage, 'details' => $errorData], $response->status());
            }

        } catch (Throwable $th) {
            // Handle jika API Flask tidak bisa dihubungi
            Log::error('Gagal menghubungi Flask API: ' . $th->getMessage());
            return response()->json(['message' => 'Tidak dapat terhubung ke service SAP.'], 503); // Service Unavailable
        }
    }

    private function refreshAndSyncOrderByAufnr(string $aufnr, string $plant): array
    {
        try {
            $username = session('username') ?? session('sap_username');
            $password = session('password') ?? session('sap_password');
            if (!$username || !$password) {
                return ['success' => false, 'error' => 'Kredensial SAP tidak ditemukan.'];
            }

            // 1. Panggil API Refresh
            $flaskRefreshUrl = 'http://127.0.0.1:8050/api/refresh-pro'; // Sesuaikan URL
            $refreshResp = Http::timeout(120)
                ->withHeaders([
                    'X-SAP-Username' => $username,
                    'X-SAP-Password' => $password,
                ])
                ->get($flaskRefreshUrl, ['aufnr' => $aufnr, 'plant' => $plant]);

            if (!$refreshResp->successful()) {
                $msg = optional($refreshResp->json())['error'] ?? $refreshResp->body();
                return ['success' => false, 'error' => 'Flask refresh-pro error: ' . $msg];
            }
            
            $payload = $refreshResp->json();

            // 2. Normalisasi & Upsert (Hanya untuk T_DATA4 untuk efisiensi)
            $T4 = $payload['T_DATA4'] ?? [];
            if (isset($payload['results']) && is_array($payload['results'])) {
                $T4 = $payload['results'][0]['T_DATA4'] ?? [];
            }
            
            DB::transaction(function () use ($aufnr, $plant, $T4) { // Tambahkan $plant jika diperlukan
                // Hapus semua data T_DATA4 yang terkait LANGSUNG dengan AUFNR ini. Ini jauh lebih aman.
                ProductionTData4::where('AUFNR', $aufnr)->delete();

                // Masukkan data baru yang didapat dari API refresh
                if (!empty($T4)) {
                    // Kita bisa menggunakan 'insert' untuk performa yang lebih baik jika datanya banyak
                    // Pastikan setiap row memiliki 'created_at' dan 'updated_at' jika tabel Anda menggunakannya
                    $insertData = array_map(function($row) use ($plant) {
                        $row['created_at'] = now();
                        $row['updated_at'] = now();
                    
                        // BENAR: 
                        // 1. Pastikan kolom WERKS (kode plant) diisi dengan variabel $plant.
                        $row['WERKS'] = $plant; 
                        
                        // 2. Pastikan WERKSX (deskripsi plant) ada, jika tidak ada dari SAP, biarkan kosong.
                        $row['WERKSX'] = $row['WERKSX'] ?? ''; 
                    
                        return $row;
                    }, $T4);
                    
                    ProductionTData4::insert($insertData);
                }
            });

            return ['success' => true, 'data' => ['T4' => $T4]];

        } catch (Throwable $e) {
            Log::error('refreshAndSyncOrderByAufnr exception', ['e' => $e]);
            return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
        }
    }
    public function show_stock(Request $request)
    {
        // --- Bungkus SEMUA logika dalam try...catch ---
        try {
            
            // 1. VALIDASI BARU: Sesuaikan dengan parameter dari JavaScript
            $validator = Validator::make($request->all(), [
                // 'search_type' tidak perlu divalidasi ketat karena kita tahu ini dari tombol
                'search_value' => 'required|string|max:40', // Ini adalah MATNR
                'search_sloc'  => 'nullable|string|max:10', // Ini adalah LGORT (opsional)
            ]);

            if ($validator->fails()) {
                // Jika validasi gagal, kirim JSON error 400
                return response()->json(['error' => $validator->errors()], 400);
            }

            // 2. OTENTIKASI (Tidak berubah)
            $username = session('username') ?? session('sap_username');
            $password = session('password') ?? session('sap_password');
            if (!$username || !$password) {
                // Jika session tidak ada, kirim JSON error 401
                return response()->json(['error' => 'Otentikasi SAP tidak ditemukan. Silakan login kembali.'], 401);
            }

            // 3. AMBIL INPUT YANG SUDAH SESUAI
            $matnrValue = $request->input('search_value'); // Ambil MATNR
            $slocValue  = $request->input('search_sloc');  // Ambil LGORT (bisa null)

            // 4. SIAPKAN PARAMETER UNTUK FLASK API (/api/get_stock)
            $queryParams = [
                'matnr' => $matnrValue,
            ];

            // Tambahkan 'lgort' HANYA JIKA 'slocValue' diisi
            if (!empty($slocValue)) {
                // Asumsi endpoint Flask /api/get_stock sekarang menerima 'lgort'
                $queryParams['lgort'] = $slocValue; 
            }

            // 5. EKSEKUSI PANGGILAN API KE /api/get_stock
            $response = Http::withHeaders([
                'X-SAP-Username' => $username,
                'X-SAP-Password' => $password,
            ])->timeout(120)->get('http://127.0.0.1:8050/api/get_stock', $queryParams); // Panggil endpoint yang benar
            
            // 6. PEMROSESAN HASIL (Tidak berubah)
            if ($response->successful()) {
                $sapData = $response->json();

                if (!empty($sapData) && isset($sapData['MATNR'])) {
                    $sapData = [$sapData];
                }

                $filteredData = array_map(function ($item) {
                    return [
                        'MATNR' => $item['MATNR'] ?? null,
                        'MAKTX' => $item['MAKTX'] ?? null,
                        'LGORT' => $item['LGORT'] ?? null,
                        'CLABS' => $item['CLABS'] ?? 0,
                        'CHARG' => $item['CHARG'] ?? null,
                        'VBELN' => $item['VBELN'] ?? null,
                        'POSNR' => $item['POSNR'] ?? null,
                        'MEINS' => $item['MEINS'] ?? null,
                    ];
                }, $sapData ?? []);

                return response()->json($filteredData, 200);

            } else {
                // Penanganan error dari Flask API
                Log::error('SAP API Error (via Flask /api/get_stock): ' . $response->body());
                return response()->json([
                    'error' => 'Gagal mengambil data stok dari SAP.',
                    'details' => $response->json() ?? ['message' => $response->body()]
                ], $response->status());
            }

        } catch (ConnectionException $e) {
            // Penanganan error koneksi ke Flask API
            Log::error('Flask API Connection Error (/api/get_stock): ' . $e->getMessage());
            return response()->json(['error' => 'Tidak dapat terhubung ke layanan API Flask.'], 503);
        
        } catch (Throwable $e) {
            // Penanganan error PHP internal
            Log::error('Fatal Controller Error in show_stock: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'error' => 'Terjadi error internal pada server Laravel.',
                'message' => $e->getMessage() // Kirim pesan error untuk debug
            ], 500); 
        }
    }
}
