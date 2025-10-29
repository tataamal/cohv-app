<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Throwable;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    public function index()
    {
        return view('Admin.search-stock');
    }

    public function show_stock(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'search_type'  => 'required|string|in:matnr,maktx',
                'search_value' => [ // MATNR
                    'nullable',
                    'string',
                    'max:40',
                    'required_if:search_type,maktx', // Wajib jika tipe MAKTX
                    // Wajib jika tipe MATNR DAN search_sloc kosong
                    Rule::requiredIf(function () use ($request) {
                        return $request->input('search_type') === 'matnr' && empty($request->input('search_sloc'));
                    }),
                ],
                'search_sloc'  => [ // S.Loc
                    'nullable',
                    'string',
                    'max:10',
                    // Wajib jika tipe MATNR DAN search_value kosong
                    Rule::requiredIf(function () use ($request) {
                        return $request->input('search_type') === 'matnr' && empty($request->input('search_value'));
                    }),
                ],
            ], [
                // Pesan custom jika salah satu rule requiredIf gagal
                'search_value.required' => 'Jika mencari berdasarkan Material (MATNR), setidaknya Material atau S.Loc harus diisi.',
                'search_sloc.required' => 'Jika mencari berdasarkan Material (MATNR), setidaknya Material atau S.Loc harus diisi.',
                // Pesan untuk required_if maktx
                'search_value.required_if' => 'Deskripsi Material (MAKTX) harus diisi.',
            ]);


            if ($validator->fails()) {
                // Return 422 Unprocessable Entity untuk validasi
                return response()->json(['error' => $validator->errors()], 422);
            }

            // 2. OTENTIKASI (Tidak berubah)
            $username = session('username') ?? session('sap_username');
            $password = session('password') ?? session('sap_password');
            if (!$username || !$password) {
                return response()->json(['error' => 'Otentikasi SAP tidak ditemukan. Silakan login kembali.'], 401);
            }

            // 3. LOGIKA BERSYARAT & AMBIL INPUT (Tidak berubah)
            $searchType  = $request->input('search_type');
            $searchValue = $request->input('search_value'); // Bisa null/kosong
            $slocValue   = $request->input('search_sloc');  // Bisa null/kosong

            $apiEndpoint = '';
            $queryParams = [];

            if ($searchType == 'matnr') {
                $apiEndpoint = 'http://127.0.0.1:8055/api/search_stock'; // Atau /api/get_stock sesuai kebutuhan Flask
                if (!empty($searchValue)) {
                    $queryParams['matnr'] = $searchValue;
                }
                if (!empty($slocValue)) {
                    $queryParams['lgort'] = $slocValue;
                }
                // Validasi di atas memastikan $queryParams tidak akan kosong
            } else { // searchType == 'maktx'
                $apiEndpoint = 'http://127.0.0.1:8055/api/search_stock_by_description';
                $queryParams = ['maktx' => $searchValue]; // searchValue pasti ada karena required_if
            }

            // 4. EKSEKUSI PANGGILAN API (Tidak berubah)
            $response = Http::withHeaders([
                'X-SAP-Username' => $username,
                'X-SAP-Password' => $password,
            ])->timeout(3600)->get($apiEndpoint, $queryParams);

            // 5. PEMROSESAN HASIL (Tidak berubah)
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
                 Log::error("SAP API Error ({$apiEndpoint}): " . $response->body());
                 return response()->json([
                     'error' => 'Gagal mengambil data dari SAP (API).',
                     'details' => $response->json() ?? ['message' => $response->body()]
                 ], $response->status());
            }

        } catch (ConnectionException $e) {
             Log::error('API Connection Error: ' . $e->getMessage());
             return response()->json(['error' => 'Tidak dapat terhubung ke layanan API.'], 503);

        } catch (Throwable $e) {
             Log::error('Fatal Controller Error in show_stock: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
             return response()->json([
                 'error' => 'Terjadi error internal pada server.',
                 'message' => $e->getMessage() // Kirim pesan error untuk debug
             ], 500);
        }
    }
}