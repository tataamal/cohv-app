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

        // [PERUBAHAN] Loop untuk setiap PRO yang dicari
        foreach ($proNumbersArray as $proNumber) {
            
            Log::info("[ProController] Memproses PRO: {$proNumber}");
            
            // 1. Panggil Endpoint Flask BARU (/api/sap_get_pro_detail)
            $response = Http::timeout(300)->withHeaders([ // 5 menit timeout per PRO
                'X-SAP-Username' => $sapUser,
                'X-SAP-Password' => $sapPass,
            ])->get(env('FLASK_API_URL') . '/api/sap_get_pro_detail', [
                'plant' => $werksCode,
                'aufnr' => $proNumber // [BARU] Kirim PRO number
            ]);

            if (!$response->successful()) {
                Log::error("[ProController] Gagal mengambil data SAP untuk PRO: {$proNumber}. Status: " . $response->status());
                continue; // Lewati PRO ini, lanjut ke PRO berikutnya
            }

            $payload = $response->json();

            // 2. Ekstrak data (payload sekarang JAUH lebih kecil)
            $T1 = collect($payload['T_DATA1'] ?? []);
            $T3 = collect($payload['T_DATA3'] ?? []); // Harusnya hanya berisi 1 PRO
            $T4 = collect($payload['T_DATA4'] ?? []);

            $tdata3 = $T3->first(); // Ambil satu-satunya PRO
            
            if (!$tdata3) {
                Log::warning("[ProController] PRO {$proNumber} tidak ditemukan di SAP (payload kosong).");
                continue; // Lanjut ke PRO berikutnya
            }
            
            $allFoundProNumbers[] = $proNumber;
            
            // =================================================================
            // == [LANGKAH BARU] Transaksi "cicil" per PRO ==
            // =================================================================
            try {
                // [PERBAIKAN] Pindahkan konversi array ke luar transaksi
                $tdata3Array = (array) $tdata3;

                DB::transaction(function () use ($tdata3Array, $T1, $T4, $proNumber) {
                    
                    // 1. Hapus data anak yang lama HANYA untuk PRO ini
                    ProductionTData1::where('AUFNR', $proNumber)->delete();
                    ProductionTData4::where('AUFNR', $proNumber)->delete();

                    // 2. Update atau Buat (Upsert) data T3 (PRO)
                    ProductionTData3::updateOrCreate(
                        ['AUFNR' => $proNumber],
                        $tdata3Array // Data lengkap untuk di-update atau di-create
                    );
                    
                    // 3. Buat ulang data anak T1 dan T4 yang baru
                    
                    // [PERBAIKAN KRITIS] Terapkan mapping data T1 (logika dari DetailData2)
                    $t1DataToInsert = $T1->map(function($item) {
                        $item = (array) $item; // Konversi ke array
                        
                        // Ambil data SAP mentah
                        $arbpl1 = $item['ARBPL1'] ?? '';
                        $arbpl2 = $item['ARBPL2'] ?? '';
                        $arbpl3 = $item['ARBPL3'] ?? '';
                        $sssl1 = $this->formatSapDateForDisplay($item['SSSLDPV1'] ?? '');
                        $sssl2 = $this->formatSapDateForDisplay($item['SSSLDPV2'] ?? '');
                        $sssl3 = $this->formatSapDateForDisplay($item['SSSLDPV3'] ?? '');
                        
                        // Buat field PV1
                        $partsPv1 = [];
                        if (!empty($arbpl1)) $partsPv1[] = strtoupper($arbpl1);
                        if (!empty($sssl1)) $partsPv1[] = $sssl1;
                        $item['PV1'] = !empty($partsPv1) ? implode(' - ', $partsPv1) : null;

                        // Buat field PV2
                        $partsPv2 = [];
                        if (!empty($arbpl2)) $partsPv2[] = strtoupper($arbpl2);
                        if (!empty($sssl2)) $partsPv2[] = $sssl2;
                        $item['PV2'] = !empty($partsPv2) ? implode(' - ', $partsPv2) : null;

                        // Buat field PV3
                        $partsPv3 = [];
                        if (!empty($arbpl3)) $partsPv3[] = strtoupper($arbpl3);
                        if (!empty($sssl3)) $partsPv3[] = $sssl3;
                        $item['PV3'] = !empty($partsPv3) ? implode(' - ', $partsPv3) : null;

                        // HAPUS field SAP mentah agar tidak error saat INSERT
                        unset(
                            $item['ARBPL1'], $item['ARBPL2'], $item['ARBPL3'],
                            $item['SSSLDPV1'], $item['SSSLDPV2'], $item['SSSLDPV3']
                        );
                        
                        return $item; // Kembalikan array yang sudah bersih
                    })->all();
                    
                    if (!empty($t1DataToInsert)) {
                        ProductionTData1::insert($t1DataToInsert);
                    }

                    // T4 tidak butuh mapping, jadi bisa langsung
                    $t4DataToInsert = $T4->map(fn($item) => (array)$item)->all();
                    if (!empty($t4DataToInsert)) {
                        ProductionTData4::insert($t4DataToInsert);
                    }
                });
                Log::info("[ProController] Berhasil 'cicil' update DB untuk PRO: {$proNumber}.");

            } catch (\Exception $dbException) {
                Log::error("[ProController] GAGAL 'cicil' update DB untuk PRO: {$proNumber}. Error: " . $dbException->getMessage());
                // Jika DB gagal, kita jangan tampilkan datanya
                // karena data di DB (lama) dan data SAP (baru) tidak sinkron.
                continue; 
            }
            // =================================================================
            // == [AKHIR TRANSAKSI CICIL] ==
            // =================================================================
            
            // 4. Susun data untuk view
            $tdata3 = (object) $tdata3; // Ubah jadi object lagi untuk view
            $dateFields = ['GSTRP', 'GLTRP', 'SSAVD'];
            foreach ($dateFields as $field) {
                $tdata3->{$field . '_formatted'} = !empty($tdata3->{$field}) 
                    ? Carbon::parse($tdata3->{$field})->format('d/m/Y') 
                    : '-';
            }

            $proDetailsList->push([
                'pro_detail' => $tdata3,
                'routings'   => $T1, // T1 sudah otomatis hanya untuk PRO ini
                'components' => $T4, // T4 juga
            ]);

        } // <-- Akhir dari loop `foreach ($proNumbersArray as $proNumber)`

        // 5. Selesai loop, hitung yang tidak ditemukan
        $notFoundProNumbers = array_diff($proNumbersArray, $allFoundProNumbers);

        Log::info("[ProController] Penyusunan data selesai.");

        // 6. Kembalikan data yang berhasil diproses
        return [
            'proDetailsList'     => $proDetailsList,
            'notFoundProNumbers' => $notFoundProNumbers,
        ];
    }

    /**
     * [BARU] Helper private untuk memformat tanggal
     * Diambil dari logika controller sinkronisasi (DetailData2)
     */
    private function formatSapDateForDisplay($sap_date_str)
    {
        if (empty($sap_date_str) || trim($sap_date_str) === '00000000') {
            return null;
        }
        try {
            // [PERBAIKAN] Menggunakan Carbon::createFromFormat untuk string 'Ymd'
            return Carbon::createFromFormat('Ymd', trim($sap_date_str))->format('d-m-Y');
        } catch (\Exception $e) {
            Log::info("[ProController] Gagal format tanggal: {$sap_date_str}");
            return null;
        }
    }
}
