<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use App\Models\ProductionTData;
use App\Models\ProductionTData1;
use App\Models\ProductionTData2;
use Illuminate\Support\Facades\Redirect;
use App\Models\ProductionTData3;
use App\Models\ProductionTData4;
use Throwable;

class Data1Controller extends Controller
{
    public function changeWc(Request $request)
    {
        // 1. Validasi input dari form
        $validated = $request->validate([
            'aufnr' => 'required|string',
            'vornr' => 'required|string',
            'plant' => 'required|string',
            'pwwrk' => 'required|string',
            'work_center_tujuan' => 'required|string',
        ]);
        
        // Cek Session SAP
        $sapUser = session('username');
        $sapPass = session('password');
        if (!$sapUser || !$sapPass) {
            // Kembali ke halaman sebelumnya dengan pesan error
            return Redirect::back()->with('error', 'Otentikasi SAP tidak valid atau sesi telah berakhir.');
        }
        
        $proCode = $validated['aufnr'];
        $wc_tujuan = $validated['work_center_tujuan'];
        $flaskBase = rtrim(env('FLASK_API_URL'), '/');

        try {
            // LANGKAH 1: Dapatkan Deskripsi Work Center Tujuan
            Log::info("Memulai pindah WC untuk PRO {$proCode}. Langkah 1: Get WC Desc.");
            $descResponse = Http::timeout(30)->withHeaders(['X-SAP-Username' => $sapUser, 'X-SAP-Password' => $sapPass])
                ->get($flaskBase . '/api/get_wc_desc', [
                    'wc' => $wc_tujuan,
                    'pwwrk' => $validated['pwwrk'],
                ]);
            
            if ($descResponse->failed()) {
                throw new \Exception("API Get WC Description Gagal: " . $descResponse->body());
            }
            $shortText = $descResponse->json()['E_DESC'] ?? '';
            Log::info(" -> Deskripsi untuk WC {$wc_tujuan} didapatkan: '{$shortText}'");

            // LANGKAH 2: Kirim Perubahan Work Center ke SAP
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
            
            // Beri jeda 2 detik untuk latensi SAP
            sleep(2);

            // LANGKAH 3: Refresh Data PRO yang Baru Diubah
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
            
            // Validasi data yang di-refresh
            if (empty($refreshedData['T_DATA']) || empty($refreshedData['T_DATA2']) || empty($refreshedData['T_DATA3'])) {
                throw new \Exception("Data inti tidak lengkap setelah refresh. Proses sinkronisasi dibatalkan.");
            }
            Log::info(" -> Data PRO berhasil di-refresh.");

            // LANGKAH 4: Jalankan Sinkronisasi ke Database
            DB::transaction(function () use ($refreshedData, $validated) {
                // Pastikan Anda memiliki method _syncSingleProData di controller ini
                $this->_syncSingleProData($refreshedData, $validated['plant']);
            });

            // Kembali ke halaman sebelumnya dengan pesan SUKSES
            $successMessage = "PRO {$proCode} berhasil dipindahkan ke WC {$wc_tujuan} dan data telah disinkronkan.";
            return Redirect::back()->with('success', $successMessage);

        } catch (Throwable $e) {
            Log::error("Gagal memindahkan PRO {$proCode}: " . $e->getMessage());
            // Kembali ke halaman sebelumnya dengan pesan GAGAL
            return Redirect::back()->with('error', "Proses Gagal! " . $e->getMessage());
        }
    }

    public function changeWcBulk(Request $request)
    {
        $validated = $request->validate(['bulk_pros' => 'required|string|json']);
        $wc_tujuan = $request->route('wc_tujuan');
        $kode = $request->route('kode');

        if (!$wc_tujuan || !$kode) return back()->with('error', 'Plant atau Work Center tujuan tidak valid.');

        $sapUser = session('username');
        $sapPass = session('password');
        if (!$sapUser || !$sapPass) return back()->with('error', 'Otentikasi SAP tidak valid.');

        $prosToMove = json_decode($validated['bulk_pros'], true);
        if (empty($prosToMove)) return back()->with('warning', 'Tidak ada PRO yang dipilih.');

        $flaskBase = rtrim(env('FLASK_API_URL'), '/');
        $successes = [];
        $failures = [];

        try {
            // LANGKAH 1: Ambil deskripsi WC Tujuan
            $firstPro = $prosToMove[0];
            Log::info("[BULK] Mengambil deskripsi untuk WC Tujuan: {$wc_tujuan}");
            $descResponse = Http::timeout(30)->withHeaders(['X-SAP-Username' => $sapUser, 'X-SAP-Password' => $sapPass])->get($flaskBase . '/api/get_wc_desc', ['wc' => $wc_tujuan, 'pwwrk' => $firstPro['pwwrk']]);
            if ($descResponse->failed()) throw new \Exception("Gagal mengambil deskripsi WC Tujuan: " . $descResponse->body());
            $shortText = $descResponse->json()['E_DESC'] ?? '';
            Log::info(" -> Deskripsi didapatkan: '{$shortText}'");

            foreach ($prosToMove as $pro) {
                $proCode = $pro['proCode'];
                try {
                    // LANGKAH 2: Ubah WC di SAP
                    $payload = ["IV_AUFNR" => $proCode, "IV_COMMIT" => "X", "IT_OPERATION" => [["SEQUEN" => "0", "OPER" => $pro['oper'], "WORK_CEN" => $wc_tujuan, "W" => "X", "SHORT_T" => $shortText, "S" => "X"]]];
                    Log::info("[BULK] [PRO: {$proCode}] Langkah 2: Mengubah WC di SAP...");
                    $changeResponse = Http::timeout(60)->withHeaders(['X-SAP-Username' => $sapUser, 'X-SAP-Password' => $sapPass])->post($flaskBase . '/api/save_edit', $payload);
                    if ($changeResponse->failed()) throw new \Exception("API Save Edit Gagal: " . $changeResponse->body());
                    Log::info(" -> [PRO: {$proCode}] Perubahan WC di SAP berhasil.");

                    // LANGKAH 3: Refresh data dengan mekanisme RETRY
                    $maxRetries = 5; $retryDelay = 5; $refreshedData = null;
                    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                        if ($attempt > 1) {
                            Log::info("[BULK] [PRO: {$proCode}] Menunggu {$retryDelay} detik...");
                            sleep($retryDelay);
                        }
                        Log::info("[BULK] [PRO: {$proCode}] Refresh attempt #{$attempt}/{$maxRetries}...");
                        $refreshResponse = Http::timeout(60)->withHeaders(['X-SAP-Username' => $sapUser, 'X-SAP-Password' => $sapPass])->get($flaskBase . '/api/refresh-pro', ['plant' => $kode, 'aufnr' => $proCode]);
                        if ($refreshResponse->failed()) throw new \Exception("API Refresh Gagal: " . $refreshResponse->body());
                        
                        $tempData = $refreshResponse->json();
                        if (!empty($tempData['T_DATA'])) {
                            $refreshedData = $tempData;
                            
                            // Pastikan T_DATA2 dan T_DATA3 ada sebagai array kosong agar
                            // langkah sinkronisasi data tidak error.
                            $refreshedData['T_DATA2'] = $tempData['T_DATA2'] ?? [];
                            $refreshedData['T_DATA3'] = $tempData['T_DATA3'] ?? [];

                            Log::info(" -> [PRO: {$proCode}] Data refresh valid on attempt #{$attempt}.");
                            break;
                        }
                        Log::warning(" -> [PRO: {$proCode}] Data refresh tidak lengkap pada attempt #{$attempt}.");
                    }

                    if (is_null($refreshedData)) throw new \Exception("Data inti tidak lengkap setelah {$maxRetries} kali percobaan.");

                    // LANGKAH 4: Sinkronisasi ke DB Lokal (INI YANG DIPERBAIKI)
                    DB::transaction(function () use ($refreshedData, $kode) {
                        $this->_syncSingleProData($refreshedData, $kode);
                    });
                    Log::info(" -> [PRO: {$proCode}] Data berhasil disinkronkan.");
                    $successes[] = $proCode;

                } catch (Throwable $e) {
                    Log::error("[BULK] Gagal memproses PRO {$proCode}: " . $e->getMessage());
                    $failures[] = ['pro' => $proCode, 'error' => $e->getMessage()];
                }
            }
        } catch (Throwable $e) {
            Log::error("[BULK] Proses gagal sebelum loop: " . $e->getMessage());
            return back()->with('error', 'Proses Gagal! ' . $e->getMessage());
        }
        
        // Membuat notifikasi akhir
        $successCount = count($successes);
        $failureCount = count($failures);
        if ($successCount > 0 && $failureCount == 0) session()->flash('success', "{$successCount} PRO berhasil dipindahkan dan disinkronkan.");
        elseif ($successCount > 0 && $failureCount > 0) session()->flash('warning', "{$successCount} PRO berhasil, namun {$failureCount} PRO gagal. Periksa log.");
        elseif ($successCount == 0 && $failureCount > 0) session()->flash('error', "Data berhasil dipindahkan namun gagal disinkronkan karena Make Stock, Silahkan Refresh PRO untuk Update Data.");

        return redirect()->back();
    }

    public function changeWcBulkStream(Request $request)
    {
        // Increase memory limit for this long running process
        ini_set('memory_limit', '5G');
        set_time_limit(0);

        $bulkPros = $request->input('bulk_pros');
        $wc_tujuan = $request->route('wc_tujuan');
        $kode = $request->route('kode');

        $response = new StreamedResponse(function () use ($bulkPros, $wc_tujuan, $kode) {
            $sapUser = session('username');
            $sapPass = session('password');
            
            // Helper function to send SSE event
            $sendEvent = function ($data) {
                echo "data: " . json_encode($data) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            if (!$sapUser || !$sapPass) {
                $sendEvent(['type' => 'error', 'message' => 'Otentikasi SAP tidak valid.']);
                return;
            }

            $prosToMove = json_decode($bulkPros, true);
            if (empty($prosToMove)) {
                $sendEvent(['type' => 'error', 'message' => 'Tidak ada PRO yang dipilih.']);
                return;
            }

            $flaskBase = rtrim(env('FLASK_API_URL'), '/');
            $total = count($prosToMove);
            $processed = 0;

            try {
                // LANGKAH 1: Ambil deskripsi WC Tujuan (Sekali saja di awal)
                $firstPro = $prosToMove[0];
                $pwwrk = $firstPro['pwwrk'] ?? ''; // Ensure pwwrk exists
                
                $descResponse = Http::timeout(30)->withHeaders(['X-SAP-Username' => $sapUser, 'X-SAP-Password' => $sapPass])
                    ->get($flaskBase . '/api/get_wc_desc', ['wc' => $wc_tujuan, 'pwwrk' => $pwwrk]);
                
                $shortText = '';
                if ($descResponse->successful()) {
                    $shortText = $descResponse->json()['E_DESC'] ?? '';
                } else {
                    $sendEvent(['type' => 'warning', 'message' => 'Gagal mengambil deskripsi WC, menggunakan default.']);
                }

                foreach ($prosToMove as $index => $pro) {
                    $proCode = $pro['proCode'];
                    $oper    = $pro['oper'];
                    
                    try {
                        // Notify start processing
                        $sendEvent([
                            'type' => 'progress',
                            'pro' => $proCode,
                            'status' => 'processing',
                            'message' => "Memproses PRO {$proCode}...",
                            'progress' => round(($processed / $total) * 100)
                        ]);

                        // LANGKAH 2: Ubah WC di SAP
                        $payload = [
                            "IV_AUFNR" => $proCode, 
                            "IV_COMMIT" => "X", 
                            "IT_OPERATION" => [[
                                "SEQUEN" => "0", 
                                "OPER" => $oper, 
                                "WORK_CEN" => $wc_tujuan, 
                                "W" => "X", 
                                "SHORT_T" => $shortText, 
                                "S" => "X"
                            ]]
                        ];

                        $changeResponse = Http::timeout(60)->withHeaders(['X-SAP-Username' => $sapUser, 'X-SAP-Password' => $sapPass])
                            ->post($flaskBase . '/api/save_edit', $payload);

                        if ($changeResponse->failed()) {
                            throw new \Exception("Gagal mengubah WC di SAP: " . $changeResponse->body());
                        }

                        // LANGKAH 3: Refresh data dengan mekanisme RETRY
                        $maxRetries = 5; 
                        $retryDelay = 3; 
                        $refreshedData = null;
                        
                        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                             if ($attempt > 1) sleep($retryDelay);
                             
                             $refreshResponse = Http::timeout(60)->withHeaders(['X-SAP-Username' => $sapUser, 'X-SAP-Password' => $sapPass])
                                ->get($flaskBase . '/api/refresh-pro', ['plant' => $kode, 'aufnr' => $proCode]);
                             
                             if ($refreshResponse->successful()) {
                                 $tempData = $refreshResponse->json();
                                 if (!empty($tempData['T_DATA'])) {
                                     $refreshedData = $tempData;
                                     // Ensure arrays exist
                                     $refreshedData['T_DATA2'] = $tempData['T_DATA2'] ?? [];
                                     $refreshedData['T_DATA3'] = $tempData['T_DATA3'] ?? [];
                                     break;
                                 }
                             }
                        }

                        if (is_null($refreshedData)) {
                             throw new \Exception("Gagal men-refresh data setelah {$maxRetries} percobaan.");
                        }

                        // LANGKAH 4: Sinkronisasi ke DB Lokal
                        DB::transaction(function () use ($refreshedData, $kode) {
                            $this->_syncSingleProData($refreshedData, $kode);
                        });

                        $processed++;
                        $sendEvent([
                            'type' => 'success',
                            'pro' => $proCode,
                            'message' => "Berhasil dipindahkan ke {$wc_tujuan}",
                            'progress' => round(($processed / $total) * 100)
                        ]);

                    } catch (\Throwable $e) {
                         $processed++; // Tetap hitung sebagai processed meski gagal
                         Log::error("Stream Bulk Error PRO {$proCode}: " . $e->getMessage());
                         $sendEvent([
                            'type' => 'failure',
                            'pro' => $proCode,
                            'message' => $e->getMessage(),
                            'progress' => round(($processed / $total) * 100)
                        ]);
                    }
                }
                
                // Final Event
                $sendEvent(['type' => 'complete', 'message' => 'Proses selesai.']);

            } catch (\Throwable $e) {
                $sendEvent(['type' => 'error', 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no'); // Disable Nginx buffering

        return $response;
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

        } catch (Throwable $e) {
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
            $seenT1 = [];
            $mapped_t1 = [];
            foreach ($t_data1 as $row) {
                // Ensure Unique VORNR per AUFNR
                $vornr = trim($row['VORNR'] ?? '');
                if (isset($seenT1[$vornr])) continue;
                $seenT1[$vornr] = true;

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
                $mapped_t1[] = $row;
            }
            if (!empty($mapped_t1)) {
                ProductionTData1::insert($mapped_t1);
            }
        }

        ProductionTData4::where('AUFNR', $proCode)->delete();
        if (!empty($t_data4)) {
            $seenT4 = [];
            $mapped_t4 = [];
            foreach ($t_data4 as $row) {
                // Ensure Unique RSNUM+RSPOS per AUFNR
                $rsnum = trim($row['RSNUM'] ?? '');
                $rspos = trim($row['RSPOS'] ?? '');
                $keyT4 = $rsnum . '-' . $rspos;
                
                if (isset($seenT4[$keyT4])) continue;
                $seenT4[$keyT4] = true;

                unset($row['VORNR'], $row['WERKS']);
                $row['WERKSX'] = $plant;
                $mapped_t4[] = $row;
            }
            if (!empty($mapped_t4)) {
                ProductionTData4::insert($mapped_t4);
            }
        }
        
        Log::info("Transaksi DB untuk PRO {$proCode} berhasil.");
    }
}


