<?php

namespace App\Http\Controllers;

use App\Models\ProductionTData3;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class Data3Controller extends Controller
{
    public function releaseOrderDirect(Request $request, $aufnr) {
        $payload = ["AUFNR" => $aufnr];

        $response = Http::timeout(0)->withHeaders([
            'X-SAP-Username' => session('username'),
            'X-SAP-Password' => session('password'),
        ])->post('http://127.0.0.1:8055/api/release_order', $payload);

        if ($response->successful()) {
            $data = $response-> json();
            $return = $data['RETURN'] ?? $data['BAPI_PRODORD_RELEASE']['RETURN'] ?? [];
            $message = is_array($return) ? ($return[0]['MESSAGE'] ?? 'Order berhasil direlease') : 'Order berhasil direlease';

            ProductionTData3::where('AUFNR', $aufnr)->update(['STATS' => 'REL']);

            // Jika request dari fetch() (AJAX), kirim response JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Order $aufnr berhasil direlease. Pesan SAP: {$message}"
                ]);
            }

            // Kalau bukan dari fetch, kembalikan redirect biasa
            return back()->with('success', "Order $aufnr berhasil direlease. Pesan SAP: {$message}");
        }

        $errorMessage = $response->json('error') ?? 'Tidak diketahui';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => "Order $aufnr gagal direlease: $errorMessage"
            ], 500);
        }

        return back()->with('error', "Order $aufnr gagal direlease: $errorMessage");

    }

    public function reschedule(Request $request)
    {
        // Validasi basic
        $data = $request->validate([
            'aufnr' => 'required|string',
            'date'  => 'required|date',       // dari <input type="date"> -> YYYY-MM-DD
            'time'  => ['required','regex:/^\d{2}[\.:]\d{2}[\.:]\d{2}$/'], // 13.30.00 atau 13:30:00
        ], [
            'time.regex' => 'Format jam harus HH.MM.SS atau HH:MM:SS',
        ]);

        // Normalisasi format yang diharapkan Flask:
        // DATE => YYYYMMDD, TIME => HH:MM:SS
        $dateYmd   = Carbon::parse($data['date'])->format('Ymd');
        $timeColon = str_replace('.', ':', $data['time']);

        // Ambil kredensial SAP dari session (diset saat login)
        $sapUser = Session::get('username');  // pastikan kamu set ini saat login
        $sapPass = Session::get('password');

        if (!$sapUser || !$sapPass) {
            return response()->json([
                'success' => false,
                'message' => 'SAP credential tidak tersedia di sesi. Silakan login kembali.'
            ], 401);
        }

        try {
            $flaskBase = rtrim(env('FLASK_API_URL', 'http://127.0.0.1:8055'), '/');

            $response = Http::withHeaders([
                    'X-SAP-Username' => $sapUser,
                    'X-SAP-Password' => $sapPass,
                    'Accept'         => 'application/json',
                ])
                ->timeout(30)
                ->post($flaskBase.'/api/schedule_order', [
                    'AUFNR' => $data['aufnr'],
                    'DATE'  => $dateYmd,
                    'TIME'  => $timeColon,
                ]);

            if ($response->failed()) {
                return back()->withErrors(['msg' => 'Gagal menghubungi API Scheduler: '.$response->body()]);
            }

            $payload = $response->json();

            // Tangkap RETURN dari SAP untuk cek error/warning
            $sapReturn = $payload['sap_return'] ?? $payload['RETURN'] ?? [];
            $detail    = $payload['detail_return'] ?? [];
            $applog    = $payload['application_log'] ?? [];

            // Cari ada TYPE = 'E'?
            $hasError = collect($sapReturn)->contains(function ($row) {
                return isset($row['TYPE']) && Str::upper($row['TYPE']) === 'E';
            });

            if ($hasError) {
                // Satukan pesan error dari SAP agar mudah dibaca
                $msg = collect($sapReturn)
                        ->filter(fn($r) => isset($r['TYPE']) && Str::upper($r['TYPE']) === 'E')
                        ->map(function ($r) {
                            $id  = $r['ID']   ?? '';
                            $num = $r['NUMBER'] ?? '';
                            $txt = $r['MESSAGE'] ?? json_encode($r);
                            return trim("[$id $num] $txt");
                        })
                        ->implode("\n");

                return response()->json([
                    'success' => false,
                    'message' => "SAP menolak penjadwalan:\n".$msg
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Production Order berhasil dijadwalkan/di-reschedule, Silahkan Refresh PRO'
            ], 200);
                         
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal di server: '.$e->getMessage()
            ], 500);
        }
    }

    public function tecoOrder(Request $request)
    {
        // 1. Validasi input dari frontend
        $validated = $request->validate([
            'aufnr' => 'required|string|max:12' // Sesuaikan validasi jika perlu
        ]);

        $aufnr = $validated['aufnr'];
        $flaskApiUrl = 'http://127.0.0.1:8055/api/teco_order'; // URL API Flask Anda

        try {
            // 2. Hit ke endpoint API Flask
            $response = Http::withHeaders([
                    'X-SAP-Username' => session('username'),
                    'X-SAP-Password' => session('password'),
                ])->timeout(60)
                ->post($flaskApiUrl, [
                'AUFNR' => $aufnr // Pastikan key 'AUFNR' sesuai dengan yang diharapkan Flask
            ]);

            // 3. Proses respons dari Flask
            if ($response->successful()) {
                $sapData = $response->json();
                
                // Cek apakah ada pesan error dari BAPI di dalam respons Flask
                // Anda mungkin perlu menyesuaikan ini berdasarkan struktur balikan SAP
                $sapReturn = $sapData['BAPI_PRODORD_COMPLETE_TECH']['DETAIL_RETURN'][0] ?? null;

                if (isset($sapReturn['TYPE']) && in_array($sapReturn['TYPE'], ['E', 'A'])) {
                    // Jika SAP mengembalikan error ('E') atau abbort ('A')
                    return response()->json([
                        'success' => false,
                        'message' => 'SAP Error: ' . ($sapReturn['MESSAGE'] ?? 'Terjadi error tidak diketahui di SAP.')
                    ]);
                }

                try {
                    // Ganti 'ProductionOrder' dan 'aufnr' sesuai dengan Model dan nama kolom Anda
                    ProductionTData3::where('aufnr', $aufnr)->delete();
                    Log::info('Local data for AUFNR ' . $aufnr . ' deleted successfully after TECO.');
                } catch (\Exception $dbException) {
                    // Jika gagal hapus data, kirim log tapi tetap lanjutkan sebagai sukses TECO
                    Log::error('Failed to delete local data for AUFNR ' . $aufnr . ': ' . $dbException->getMessage());
                }

                // Jika berhasil, kirim respons sukses beserta sinyal untuk refresh
                return response()->json([
                    'success' => true,
                    'message' => 'Order ' . $aufnr . ' berhasil di-TECO dan data diperbaharui.',
                    'action' => 'refresh' // Sinyal untuk frontend
                ]);

            } else {
                // Jika request ke Flask gagal (misal: server down, 404, dll)
                $errorBody = $response->json();
                $errorMessage = $errorBody['error'] ?? 'Gagal menghubungi service TECO.';
                Log::error('Flask API Error for AUFNR ' . $aufnr . ': ' . $response->body());
                return response()->json([
                    'success' => false, 
                    'message' => $errorMessage
                ], $response->status());
            }

        } catch (\Exception $e) {
            // Menangkap error koneksi atau timeout
            Log::error('Connection to Flask API failed for AUFNR ' . $aufnr . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat terhubung ke server TECO. Mohon coba lagi nanti.'
            ], 500);
        }
    }

    public function readPpOrder(Request $request)
    {
        // 1. Validasi input dari frontend
        $validated = $request->validate([
            'aufnr' => 'required|string|max:12'
        ]);

        $aufnr = $validated['aufnr'];
        // URL API Flask untuk Read PP
        $flaskApiUrl = 'http://127.0.0.1:8055/api/read-pp';

        try {
            // 2. Hit ke endpoint API Flask
            // Perhatikan: Key di body harus 'IV_AUFNR' sesuai ekspektasi Flask
            $response = Http::withHeaders([
                    'X-SAP-Username' => session('username'),
                    'X-SAP-Password' => session('password'),
                ])->timeout(60)->post($flaskApiUrl, [
                'IV_AUFNR' => $aufnr 
            ]);

            // 3. Proses respons dari Flask
            $flaskData = $response->json();

            if ($response->successful()) {
                // Flask mengembalikan status 200 (OK)
                return response()->json([
                    'success' => true,
                    'message' => $flaskData['message'] ?? 'Proses Read PP berhasil.',
                ]);
            } else {
                 // Ambil pesan error detail dari SAP, default ke array kosong jika tidak ada
                $sapErrors = $flaskData['sap_errors'] ?? [];
                // Siapkan pesan error default dari Flask
                $errorMessage = $flaskData['message'] ?? 'Gagal menghubungi service Read PP.';

                // Loop melalui setiap pesan error dari SAP
                foreach ($sapErrors as $error) {
                    // Periksa apakah pesan dimulai dengan 'E:' (Error)
                    if (str_starts_with(trim($error), 'E:')) {
                        // Jika ya, ganti pesan error default dengan pesan kustom Anda
                        $errorMessage = 'Sudah ada transaksi pada Production Order ini, data master tidak dapat diubah.';
                        // Hentikan loop karena kita sudah menemukan error tipe 'E'
                        break;
                    }
                }

                // Kirim respons ke frontend dengan pesan yang sudah disesuaikan
                Log::error('Flask API Error for Read PP AUFNR ' . $aufnr . ': ' . $response->body());
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage, // Gunakan variabel pesan error yang baru
                    'errors'  => $sapErrors 
                ], $response->status());
            }

        } catch (\Exception $e) {
            // Menangkap error koneksi atau timeout
            Log::error('Connection to Flask API failed for Read PP ' . $aufnr . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat terhubung ke server Read PP. Mohon coba lagi nanti.'
            ], 500);
        }
    }

    public function changeQuantity(Request $request)
    {
        // 1. Validasi Input (Tetap sama)
        $validator = Validator::make($request->all(), [
            'aufnr' => 'required|string|max:12',
            'werks' => 'required|string|max:4',
            'new_quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $sapQuantityString = number_format(
                floatval($request->input('new_quantity')),
                3,
                '.',
                ''
            );
            $dataToFlask = [
                'AUFNR' => $request->input('aufnr'),
                'QUANTITY' => $sapQuantityString
            ];

            $flaskApiUrl = 'http://127.0.0.1:8055//api/change_quantity';
           $response = Http::withHeaders([
                'X-SAP-Username' => session('username'),
                'X-SAP-Password' => session('password'),
            ])->timeout(60)->post($flaskApiUrl, $dataToFlask);
            
            if ($response->successful() && !$response->json('error')) {
                return response()->json([
                    'success' => true,
                    'message' => $response->json('message') ?? 'Quantity changed successfully, Please Refresh the Production Order data.'
                ]);
            } else {
                $errorMessage = $response->json('error') ?? 'Failed to update quantity. Unknown error.';
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}
