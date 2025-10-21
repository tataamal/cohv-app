<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class StockController extends Controller
{
    public function index()
    {
        return view('Admin.search-stock');
    }

    public function show_stock(Request $request)
    {
        // 1. VALIDASI BARU (DENGAN SLOC)
        $validator = Validator::make($request->all(), [
            'search_type'  => 'required|string|in:matnr,maktx',
            'search_value' => 'required|string|max:40',
            'search_sloc'  => 'nullable|string|max:10', // <-- TAMBAHAN: Validasi S.Loc (opsional)
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // 2. OTENTIKASI (Tidak berubah)
        $username = session('username') ?? session('sap_username');
        $password = session('password') ?? session('sap_password');
        if (!$username || !$password) {
            return response()->json(['error' => 'Otentikasi SAP tidak ditemukan. Silakan login kembali.'], 401);
        }

        try {
            // 3. LOGIKA BERSYARAT (DIUBAH)
            $searchType  = $request->input('search_type');
            $searchValue = $request->input('search_value');
            $slocValue   = $request->input('search_sloc'); // <-- TAMBAHAN: Ambil nilai S.Loc

            $apiEndpoint = '';
            $queryParams = [];

            if ($searchType == 'matnr') {
                // Skenario 1: Search by MATNR
                $apiEndpoint = 'http://127.0.0.1:8050/api/search_stock';
                
                // Siapkan query parameter dasar
                $queryParams = [
                    'matnr' => $searchValue,
                ];

                // --- INTI PERUBAHAN ---
                // Tambahkan LGORT (S.Loc) ke query HANYA JIKA diisi
                if (!empty($slocValue)) {
                    // Asumsi parameter API-nya adalah 'lgort'
                    $queryParams['lgort'] = $slocValue; 
                }
                // --- AKHIR PERUBAHAN ---

            } else {
                // Skenario 2: Search by MAKTX
                // (Tidak berubah, S.Loc tidak berlaku di sini)
                $apiEndpoint = 'http://127.0.0.1:8050/api/search_stock_by_description'; 
                $queryParams = [
                    'maktx' => $searchValue,
                ];
            }

            // 4. EKSEKUSI PANGGILAN API (Dinamis)
            // (Tidak berubah, $queryParams sudah dinamis)
            $response = Http::withHeaders([
                'X-SAP-Username' => $username,
                'X-SAP-Password' => $password,
            ])->timeout(seconds: 3600)->get($apiEndpoint, $queryParams);

            
            // 5. PEMROSESAN HASIL (Tidak berubah)
            if ($response->successful()) {
                $sapData = $response->json();

                // Handle jika SAP mengembalikan 1 objek (bukan array)
                if (!empty($sapData) && isset($sapData['MATNR'])) {
                    $sapData = [$sapData];
                }

                // Filter data (tetap sama, ini sudah fleksibel)
                $filteredData = array_map(function ($item) {
                    // Untuk 'maktx', field selain MATNR & MAKTX akan jadi null (sudah benar)
                    // Untuk 'matnr', semua field akan terisi (sudah benar)
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
                }, $sapData ?? []); // Ditambah '?? []' agar lebih aman jika $sapData null

                return response()->json($filteredData, 200);

            } else {
                // Penanganan error (sama seperti sebelumnya)
                Log::error('SAP API Error: ' . $response->body());
                return response()->json([
                    'error' => 'Gagal mengambil data stok dari SAP.',
                    'details' => $response->json() ?? ['message' => $response->body()]
                ], $response->status());
            }

        } catch (ConnectionException $e) {
            // Penanganan error koneksi (sama seperti sebelumnya)
            Log::error('SAP API Connection Error: ' . $e->getMessage());
            return response()->json(['error' => 'Tidak dapat terhubung ke layanan SAP.'], 503);
        }
    }
}