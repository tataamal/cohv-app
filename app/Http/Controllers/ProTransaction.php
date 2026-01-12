<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ProductionTData1;
use App\Models\ProductionTData3;
use App\Models\ProductionTData4;

class ProTransaction extends Controller
{
    public function get_data_pro(string $werksCode, array $proNumbersArray, string $sapUser, string $sapPass)
    {
        Log::info("[ProController] Memulai 'cicil' update untuk " . count($proNumbersArray) . " PRO.");
        
        $proDetailsList = collect();
        $allFoundProNumbers = [];

        foreach ($proNumbersArray as $proNumber) {
            
            Log::info("[ProController] Memproses PRO: {$proNumber}");
            
            // 1. Panggil Endpoint Flask BARU (/api/sap_get_pro_detail)
            $response = Http::timeout(300)->withHeaders([ // 5 menit timeout per PRO
                'X-SAP-Username' => $sapUser,
                'X-SAP-Password' => $sapPass,
            ])->get(env('FLASK_API_URL') . '/api/sap_get_pro_detail', [
                'plant' => $werksCode,
                'aufnr' => $proNumber 
            ]);

            if (!$response->successful()) {
                Log::error("[ProController] Gagal mengambil data SAP untuk PRO: {$proNumber}. Status: " . $response->status());
                continue; // Lewati PRO ini, lanjut ke PRO berikutnya
            }

            $payload = $response->json();

            // 2. Ekstrak data
            $T1 = collect($payload['T_DATA1'] ?? []);
            $T3 = collect($payload['T_DATA3'] ?? []); 
            $T4 = collect($payload['T_DATA4'] ?? []);

            $tdata3 = $T3->first(); // Ambil satu-satunya PRO
            
            // --- [MODIFIKASI DI SINI] ---
            if (!$tdata3) {
                Log::warning("[ProController] PRO {$proNumber} tidak ditemukan di SAP (payload kosong). Mencoba menghapus data lokal...");
                
                try {
                    // Panggil API Flask untuk menghapus data di DB Lokal
                    $deleteResponse = Http::post(env('FLASK_API_URL') . '/api/delete-data', [
                        'pro_list' => [$proNumber] // Sesuai format yang diminta app.py
                    ]);

                    if ($deleteResponse->successful()) {
                        Log::info("[ProController] SUKSES sinkronisasi hapus: PRO {$proNumber} dihapus dari DB lokal.");
                    } else {
                        Log::error("[ProController] GAGAL sinkronisasi hapus: PRO {$proNumber}. Status API: " . $deleteResponse->status() . " - " . $deleteResponse->body());
                        
                        // Opsional: Fallback delete manual via Eloquent jika API gagal
                        // ProductionTData3::where('AUFNR', $proNumber)->delete();
                    }
                } catch (\Exception $e) {
                    Log::error("[ProController] Exception saat memanggil API delete-data: " . $e->getMessage());
                }

                continue; // Lanjut ke PRO berikutnya karena data ini invalid
            }
            
            $allFoundProNumbers[] = $proNumber;
            
            try {
                $tdata3Array = (array) $tdata3;
                $mappedT1Collection = $T1->map(function($item) {
                    $item = (array) $item; 
                    
                    $arbpl1 = $item['ARBPL1'] ?? '';
                    $arbpl2 = $item['ARBPL2'] ?? '';
                    $arbpl3 = $item['ARBPL3'] ?? '';
                    $sssl1 = $this->formatSapDateForDisplay($item['SSSLDPV1'] ?? '');
                    $sssl2 = $this->formatSapDateForDisplay($item['SSSLDPV2'] ?? '');
                    $sssl3 = $this->formatSapDateForDisplay($item['SSSLDPV3'] ?? '');
                    $partsPv1 = [];
                    if (!empty($arbpl1)) $partsPv1[] = strtoupper($arbpl1);
                    if (!empty($sssl1)) $partsPv1[] = $sssl1;
                    $item['PV1'] = !empty($partsPv1) ? implode(' - ', $partsPv1) : null;

                    $partsPv2 = [];
                    if (!empty($arbpl2)) $partsPv2[] = strtoupper($arbpl2);
                    if (!empty($sssl2)) $partsPv2[] = $sssl2;
                    $item['PV2'] = !empty($partsPv2) ? implode(' - ', $partsPv2) : null;

                    $partsPv3 = [];
                    if (!empty($arbpl3)) $partsPv3[] = strtoupper($arbpl3);
                    if (!empty($sssl3)) $partsPv3[] = $sssl3;
                    $item['PV3'] = !empty($partsPv3) ? implode(' - ', $partsPv3) : null;

                    unset(
                        $item['ARBPL1'], $item['ARBPL2'], $item['ARBPL3'],
                        $item['SSSLDPV1'], $item['SSSLDPV2'], $item['SSSLDPV3']
                    );
                    
                    /* 
                    // [REMOVED] MENG2 -> MENGE2 conversion. Data source should strictly provide MENGE2 now.
                    if (isset($item['MENG2'])) {
                        $item['MENGE2'] = $item['MENG2'];
                        unset($item['MENG2']);
                    }
                    */
                    
                    return $item; 
                });

                /*
                // [REMOVED] Fix MENG2 -> MENGE2 for T_DATA3
                if (isset($tdata3Array['MENG2'])) {
                   $tdata3Array['MENGE2'] = $tdata3Array['MENG2'];
                   unset($tdata3Array['MENG2']);
                }
                */

                DB::transaction(function () use ($tdata3Array, $mappedT1Collection, $T4, $proNumber) {
                    
                    ProductionTData1::where('AUFNR', $proNumber)->delete();
                    ProductionTData4::where('AUFNR', $proNumber)->delete();
                    ProductionTData3::updateOrCreate(
                        ['AUFNR' => $proNumber],
                        $tdata3Array 
                    );

                    // Insert T1 with Uniqueness Check
                    $t1DataToInsert = []; 
                    $seenT1 = [];
                    foreach ($mappedT1Collection as $item) {
                        $vornr = trim($item['VORNR'] ?? '');
                        if (isset($seenT1[$vornr])) continue;
                        $seenT1[$vornr] = true;
                        $t1DataToInsert[] = $item;
                    }

                    if (!empty($t1DataToInsert)) {
                        ProductionTData1::insert($t1DataToInsert);
                    }

                    // Insert T4 with Uniqueness Check
                    $t4DataToInsert = [];
                    $seenT4 = [];
                    foreach ($T4 as $item) {
                        $row = (array)$item;
                        $rsnum = trim($row['RSNUM'] ?? '');
                        $rspos = trim($row['RSPOS'] ?? '');
                        $keyT4 = $rsnum . '-' . $rspos;

                        if (isset($seenT4[$keyT4])) continue;
                        $seenT4[$keyT4] = true;
                        $t4DataToInsert[] = $row;
                    }

                    if (!empty($t4DataToInsert)) {
                        ProductionTData4::insert($t4DataToInsert);
                    }
                });
                Log::info("[ProController] Berhasil 'cicil' update DB untuk PRO: {$proNumber}.");

            } catch (\Exception $dbException) {
                Log::error("[ProController] GAGAL 'cicil' update DB untuk PRO: {$proNumber}. Error: " . $dbException->getMessage());
                continue; 
            }

            $tdata3 = (object) $tdata3; 
            $dateFields = ['GSTRP', 'GLTRP', 'SSAVD'];
            foreach ($dateFields as $field) {
                $tdata3->{$field . '_formatted'} = !empty($tdata3->{$field}) 
                    ? Carbon::parse($tdata3->{$field})->format('d/m/Y') 
                    : '-';
            }

            $proDetailsList->push([
                'pro_detail' => $tdata3,
                'routings'   => $mappedT1Collection, 
                'components' => $T4,
            ]);

        }

        $notFoundProNumbers = array_diff($proNumbersArray, $allFoundProNumbers);

        Log::info("[ProController] Penyusunan data selesai.");

        return [
            'proDetailsList'     => $proDetailsList,
            'notFoundProNumbers' => $notFoundProNumbers,
        ];
    }

    private function formatSapDateForDisplay($sap_date_str)
    {
        if (empty($sap_date_str) || trim($sap_date_str) === '00000000') {
            return null;
        }
        try {
            return Carbon::createFromFormat('Ymd', trim($sap_date_str))->format('d-m-Y');
        } catch (\Exception $e) {
            Log::info("[ProController] Gagal format tanggal: {$sap_date_str}");
            return null;
        }
    }
}