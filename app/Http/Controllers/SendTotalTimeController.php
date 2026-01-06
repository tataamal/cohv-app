<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\HistoryWi;
use App\Models\DailyTimeWi;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SendTotalTimeController extends Controller
{

    public function calculateAndStoreDailyTime()
    {
        try {
            $date = Carbon::yesterday()->toDateString();
            
            $documents = HistoryWi::whereDate('document_date', $date)->get();
            
            $aggregatedData = [];

            foreach ($documents as $doc) {
                $payload = $doc->payload_data;
                $plantCode = $doc->plant_code;

                if (is_array($payload)) {
                    foreach ($payload as $item) {
                        $nik = trim($item['nik'] ?? '');
                        
                        if (empty($nik)) continue;

                        $nama = $item['name'] ?? null;

                        $timeStr = $item['calculated_tak_time'] ?? '0';
                        $timeVal = floatval(str_replace(',', '.', $timeStr));

                        $key = $nik;

                        if (!isset($aggregatedData[$key])) {
                            $aggregatedData[$key] = [
                                'nik' => $nik,
                                'nama' => $nama,
                                'plants' => [],
                                'total_time' => 0
                            ];
                        }
                        
                        if (empty($aggregatedData[$key]['nama']) && !empty($nama)) {
                            $aggregatedData[$key]['nama'] = $nama;
                        }

                        $aggregatedData[$key]['total_time'] += $timeVal;
                        
                        if (!in_array($plantCode, $aggregatedData[$key]['plants'])) {
                            $aggregatedData[$key]['plants'][] = $plantCode;
                        }
                    }
                }
            }

            $count = 0;
            foreach ($aggregatedData as $data) {
                sort($data['plants']);
                $plantString = implode(',', $data['plants']);
                $record = DailyTimeWi::firstOrNew([
                    'tanggal' => $date,
                    'nik' => $data['nik']
                ]);
                
                $record->kode_laravel = $plantString;
                $record->total_time_wi = ceil($data['total_time']); 
                $record->nama = $data['nama']; 
                
                $record->save();
                $count++;
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghitung dan menyimpan data harian untuk tanggal {$date}.",
                'records_processed' => $count
            ]);

        } catch (\Exception $e) {
            Log::error("Error Calculating Daily Time WI: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal: ' . $e->getMessage()
            ], 500);
        }
    }
}
