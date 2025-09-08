<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\ProductionTData4;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Data4Controller extends Controller
{
    public function addComponent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'iv_aufnr' => 'required|string',
            'iv_matnr' => 'required|string',
            'iv_bdmng' => 'required|numeric|min:0.001',
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
            $flaskEndpoint = 'http://127.0.0.1:8006/api/add_component';
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
            $authoritativePlant = ProductionTData4::where('AUFNR', $aufnr)
                                ->orWhere('ORDERX', $aufnr)
                                ->value('WERKSX'); // Ambil nilai kolom WERKSX saja
            
            $refreshResult = $this->refreshAndSyncOrderByAufnr($aufnr, $authoritativePlant);

            if (!$refreshResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Komponen berhasil ditambahkan di SAP, tetapi gagal me-refresh data lokal.',
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
            $flaskEndpoint = 'http://127.0.0.1:8006/api/delete_component'; // Ganti dengan URL Flask Anda

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

    private function refreshAndSyncOrderByAufnr(string $aufnr, string $plant): array
    {
        try {
            $username = session('username') ?? session('sap_username');
            $password = session('password') ?? session('sap_password');
            if (!$username || !$password) {
                return ['success' => false, 'error' => 'Kredensial SAP tidak ditemukan.'];
            }

            // 1. Panggil API Refresh
            $flaskRefreshUrl = 'http://127.0.0.1:8006/api/refresh-pro'; // Sesuaikan URL
            $refreshResp = Http::timeout(120)
                ->withHeaders([
                    'X-SAP-Username' => $username,
                    'X-SAP-Password' => $password,
                ])
                ->get($flaskRefreshUrl, ['plant' => $plant, 'AUFNR' => $aufnr]);

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
            
            DB::transaction(function () use ($aufnr, $T4) {
                $keep4 = [];
                $rsnumScope = [];
                
                // Hapus dulu data lama untuk AUFNR ini
                // Cari RSNUM (Reservation Number) yang terkait dengan AUFNR ini
                $oldRsnums = ProductionTData4::where('ORDERX', $aufnr)->orWhere('AUFNR', $aufnr)->pluck('RSNUM')->unique()->toArray();
                if(!empty($oldRsnums)) {
                    ProductionTData4::whereIn('RSNUM', $oldRsnums)->delete();
                }

                // Masukkan data baru
                foreach ($T4 as $row) {
                    if (!isset($row['RSNUM']) || !isset($row['RSPOS'])) continue;
                    
                    ProductionTData4::updateOrCreate(
                        ['RSNUM' => $row['RSNUM'], 'RSPOS' => $row['RSPOS']],
                        $row
                    );
                }
            });

            return ['success' => true, 'data' => ['T4' => $T4]];

        } catch (\Throwable $e) {
            Log::error('refreshAndSyncOrderByAufnr exception', ['e' => $e]);
            return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
        }
    }
}
