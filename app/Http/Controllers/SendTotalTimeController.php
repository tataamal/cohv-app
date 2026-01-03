<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SendTotalTimeController extends Controller
{

    public function calculateAndStoreDailyTime()
    {
        try {
            // 1. Tentukan Tanggal H-1
            $date = \Carbon\Carbon::yesterday()->toDateString();
            
            // 2. Ambil Semua Dokumen WI pada Tanggal Tersebut
            $documents = \App\Models\HistoryWi::whereDate('document_date', $date)->get();
            
            $aggregatedData = [];

            // 3. Iterasi Dokumen dan Hitung Total Time per NIK
            foreach ($documents as $doc) {
                $payload = $doc->payload_data; // Sudah dicast ke array oleh Model
                $plantCode = $doc->plant_code;

                if (is_array($payload)) {
                    foreach ($payload as $item) {
                        $nik = trim($item['nik'] ?? '');
                        
                        // Skip jika NIK kosong
                        if (empty($nik)) continue;

                        // Ambil calculated_tak_time, normalisasi format angka (ganti koma jadi titik)
                        $timeStr = $item['calculated_tak_time'] ?? '0';
                        $timeVal = floatval(str_replace(',', '.', $timeStr));

                        // Key unik berdasarkan NIK dan Plant Code (sesuai request: kode_laravel diambil dari plant_code)
                        $key = $nik . '|' . $plantCode;

                        if (!isset($aggregatedData[$key])) {
                            $aggregatedData[$key] = [
                                'nik' => $nik,
                                'kode_laravel' => $plantCode,
                                'total_time' => 0
                            ];
                        }

                        $aggregatedData[$key]['total_time'] += $timeVal;
                    }
                }
            }

            // 4. Simpan ke Table daily_time_wi
            $count = 0;
            foreach ($aggregatedData as $data) {
                // Gunakan updateOrInsert untuk mencegah duplikasi jika script dijalankan ulang
                \App\Models\DailyTimeWi::updateOrInsert(
                    [
                        'tanggal' => $date,
                        'nik' => $data['nik'],
                        'kode_laravel' => $data['kode_laravel']
                    ],
                    [
                        'total_time_wi' => $data['total_time']
                    ]
                );
                $count++;
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghitung dan menyimpan data harian untuk tanggal {$date}.",
                'records_processed' => $count
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error Calculating Daily Time WI: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal: ' . $e->getMessage()
            ], 500);
        }
    }
}
