<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ProductionTData;
use App\Models\ProductionTData1;
use App\Models\ProductionTData2;
use App\Models\ProductionTData3;
use App\Models\ProductionTData4;
use RuntimeException;


class YPPR074Z
{
    public function __construct()
    {
        //
    }

    /**
     * Refresh data dari endpoint /api/sap_combined lalu simpan dengan pola relasi + unique check.
     *
     * @param  string  $plant
     * @param  string  $aufnr
     * @return array   Ringkasan hasil refresh (counts, dsb)
     */

    public function refreshPro(string $plant, string $aufnr): array
    {
        set_time_limit(0);

        Log::info("==================================================");
        Log::info("Memulai refresh PRO YPPR074Z | Plant={$plant} | AUFNR={$aufnr}");
        Log::info("==================================================");

        // 1) Fetch payload khusus AUFNR
        $payload = $this->fetchSapCombined($plant, $aufnr);

        // 2) Flatten payload (kalau response sudah flat, ini tetap aman)
        [$T_DATA, $T1, $T2, $T3, $T4] = $this->flattenPayload($payload);

        // 3) (Defensive) Pastikan hanya AUFNR target
        $aufnr = trim($aufnr);
        $T1 = array_values(array_filter($T1, fn($r) => trim($r['AUFNR'] ?? '') === $aufnr));
        $T3 = array_values(array_filter($T3, fn($r) => trim($r['AUFNR'] ?? '') === $aufnr));
        $T4 = array_values(array_filter($T4, fn($r) => trim($r['AUFNR'] ?? '') === $aufnr));

        // 4) Simpan dengan transaksi & mapping bertingkat
        $summary = DB::transaction(function () use ($plant, $aufnr, $T_DATA, $T1, $T2, $T3, $T4) {
            // 4.1 Hapus data lama khusus AUFNR ini
            $this->deleteOldByAufnr($plant, $aufnr);

            // 4.2 Grouping (sama seperti controller kamu)
            $t2_grouped = collect($T2)->groupBy(fn($item) => trim($item['KUNNR'] ?? '') . '-' . trim($item['NAME1'] ?? ''));
            $t3_grouped = collect($T3)->groupBy(fn($item) => trim($item['KDAUF'] ?? '') . '-' . trim($item['KDPOS'] ?? ''));
            $t1_grouped = collect($T1)->groupBy(fn($item) => trim($item['AUFNR'] ?? ''));
            $t4_grouped = collect($T4)->groupBy(fn($item) => trim($item['AUFNR'] ?? ''));

            // 4.3 Unique tracking
            $seenTData  = [];
            $seenTData2 = [];
            $seenTData3 = [];
            $seenTData4 = [];
            $seenTData1 = [];

            $inserted = ['t_data'=>0,'t_data2'=>0,'t_data3'=>0,'t_data1'=>0,'t_data4'=>0];

            foreach ($T_DATA as $t_data_row) {
                $kunnr = trim((string)($t_data_row['KUNNR'] ?? ''));
                $name1 = trim((string)($t_data_row['NAME1'] ?? ''));
                if ($kunnr === '' && $name1 === '') continue;

                $key_tdata = $plant . '-' . $name1 . '-' . $kunnr;
                if (isset($seenTData[$key_tdata])) continue;
                $seenTData[$key_tdata] = true;

                // Use updateOrCreate for Parent T_DATA to avoid duplicate errors if it exists (shared across PROs)
                // Key: WERKSX, KUNNR, NAME1
                // Note: removed delete logic above because we merge/update instead.
                $parent = ProductionTData::updateOrCreate(
                    [
                        'WERKSX' => $plant,
                        'KUNNR'  => $kunnr,
                        'NAME1'  => $name1,
                    ],
                    $t_data_row
                );
                $inserted['t_data']++;

                $children_t2 = $t2_grouped->get($kunnr . '-' . $name1, []);
                foreach ($children_t2 as $t2_row) {
                    $kdauf = trim($t2_row['KDAUF'] ?? '');
                    $kdpos = trim($t2_row['KDPOS'] ?? '');
                    if ($kdauf === '' && $kdpos === '') continue;

                    $key_t2_unique = $plant . '-' . $kdauf . '-' . $kdpos;
                    if (isset($seenTData2[$key_t2_unique])) continue;
                    $seenTData2[$key_t2_unique] = true;

                    $t2_row['WERKSX'] = $plant;
                    $t2_row['EDATU']  = (empty($t2_row['EDATU']) || trim($t2_row['EDATU']) === '00000000') ? null : $t2_row['EDATU'];
                    $t2_row['KUNNR']  = $parent->KUNNR;
                    $t2_row['NAME1']  = $parent->NAME1;

                    // Use updateOrCreate for T2 (Sales Order Item) 
                    // Key: WERKSX, KDAUF, KDPOS
                    ProductionTData2::updateOrCreate(
                        [
                            'WERKSX' => $plant,
                            'KDAUF'  => $kdauf,
                            'KDPOS'  => $kdpos,
                        ],
                        $t2_row
                    );
                    $inserted['t_data2']++;

                    $children_t3 = $t3_grouped->get($kdauf . '-' . $kdpos, []);
                    foreach ($children_t3 as $t3_row) {
                        // Pastikan AUFNR sesuai target
                        if (trim($t3_row['AUFNR'] ?? '') !== $aufnr) continue;

                        $key_t3_unique = $plant . '-' . $aufnr;
                        if (isset($seenTData3[$key_t3_unique])) continue;
                        $seenTData3[$key_t3_unique] = true;

                        $t3_row['WERKSX'] = $plant;
                        ProductionTData3::create($t3_row);
                        $inserted['t_data3']++;

                        // Anak T1 & T4 by AUFNR
                        $children_t1 = $t1_grouped->get($aufnr, []);
                        $children_t4 = $t4_grouped->get($aufnr, []);

                        foreach ($children_t1 as $t1_row) {
                            $vornr = trim($t1_row['VORNR'] ?? '');
                            $key_t1_unique = $plant . '-' . $aufnr . '-' . $vornr;
                            if (isset($seenTData1[$key_t1_unique])) continue;
                            $seenTData1[$key_t1_unique] = true;

                            $t1_row['PV1'] = $this->buildPv($t1_row['ARBPL1'] ?? null, $this->formatTanggal($t1_row['SSSLDPV1'] ?? ''));
                            $t1_row['PV2'] = $this->buildPv($t1_row['ARBPL2'] ?? null, $this->formatTanggal($t1_row['SSSLDPV2'] ?? ''));
                            $t1_row['PV3'] = $this->buildPv($t1_row['ARBPL3'] ?? null, $this->formatTanggal($t1_row['SSSLDPV3'] ?? ''));

                            $t1_row['WERKSX'] = $plant;
                            ProductionTData1::create($t1_row);
                            $inserted['t_data1']++;
                        }

                        foreach ($children_t4 as $t4_row) {
                            $rsnum = trim($t4_row['RSNUM'] ?? '');
                            $rspos = trim($t4_row['RSPOS'] ?? '');
                            $key_t4_unique = $plant . '-' . $aufnr . '-' . $rsnum . '-' . $rspos;

                            if (isset($seenTData4[$key_t4_unique])) continue;
                            $seenTData4[$key_t4_unique] = true;

                            $t4_row['WERKSX'] = $plant;
                            ProductionTData4::create($t4_row);
                            $inserted['t_data4']++;
                        }
                    }
                }
            }

            return [
                'plant' => $plant,
                'aufnr' => $aufnr,
                'raw_counts' => [
                    'T_DATA'  => count($T_DATA),
                    'T_DATA1' => count($T1),
                    'T_DATA2' => count($T2),
                    'T_DATA3' => count($T3),
                    'T_DATA4' => count($T4),
                ],
                'inserted_counts' => $inserted,
            ];
        });

        Log::info("Refresh PRO YPPR074Z selesai | Plant={$plant} | AUFNR={$aufnr}", $summary);

        return $summary;
    }
    public function refreshAndStore(string $plant): array
    {
        set_time_limit(0);

        Log::info("==================================================");
        Log::info("Memulai proses refresh YPPR074Z untuk Plant: {$plant}");
        Log::info("==================================================");

        // --- 1) Fetch dari Flask API ---
        $payload = $this->fetchSapCombined($plant);

        // Helper format tanggal aman (Ymd -> d-m-Y)
        $formatTanggal = function ($tgl) {
            if (empty($tgl) || trim($tgl) === '00000000') return null;
            try {
                return Carbon::createFromFormat('Ymd', $tgl)->format('d-m-Y');
            } catch (\Throwable $e) {
                return null;
            }
        };

        // --- 2) Flatten payload jadi T_DATA + T1..T4 ---
        [$T_DATA, $T1, $T2, $T3, $T4] = $this->flattenPayload($payload);

        Log::info("Jumlah data mentah: T_DATA=" . count($T_DATA)
            . ", T1=" . count($T1)
            . ", T2=" . count($T2)
            . ", T3=" . count($T3)
            . ", T4=" . count($T4)
        );

        // --- 3) Simpan transaksi ---
        $summary = DB::transaction(function () use ($plant, $T_DATA, $T1, $T2, $T3, $T4, $formatTanggal) {

            // 3a) Delete data lama per plant
            $this->deleteOldByPlant($plant);

            // 3b) Grouping anak untuk lookup cepat
            $t2_grouped = collect($T2)->groupBy(fn($item) =>
                trim($item['KUNNR'] ?? '') . '-' . trim($item['NAME1'] ?? '')
            );

            $t3_grouped = collect($T3)->groupBy(fn($item) =>
                trim($item['KDAUF'] ?? '') . '-' . trim($item['KDPOS'] ?? '')
            );

            $t1_grouped = collect($T1)->groupBy(fn($item) => trim($item['AUFNR'] ?? ''));
            $t4_grouped = collect($T4)->groupBy(fn($item) => trim($item['AUFNR'] ?? ''));

            // 3c) Tracking uniqueness (seperti di controller kamu)
            $seenTData  = [];
            $seenTData2 = [];
            $seenTData3 = [];
            $seenTData4 = []; // uniq: AUFNR, RSNUM, RSPOS
            $seenTData1 = []; // uniq: AUFNR, VORNR

            $inserted = [
                't_data'  => 0,
                't_data2' => 0,
                't_data3' => 0,
                't_data1' => 0,
                't_data4' => 0,
            ];

            foreach ($T_DATA as $t_data_row) {
                // Normalisasi key parent
                $kunnr = trim((string)($t_data_row['KUNNR'] ?? ''));
                $name1 = trim((string)($t_data_row['NAME1'] ?? ''));

                if ($kunnr === '' && $name1 === '') {
                    continue;
                }

                // Unique parent: plant + name1 + kunnr
                $key_tdata = $plant . '-' . $name1 . '-' . $kunnr;
                if (isset($seenTData[$key_tdata])) {
                    continue;
                }
                $seenTData[$key_tdata] = true;

                // Set field tambahan
                $t_data_row['KUNNR']  = $kunnr;
                $t_data_row['NAME1']  = $name1;
                $t_data_row['WERKSX'] = $plant;
                $t_data_row['EDATU']  = (empty($t_data_row['EDATU']) || trim($t_data_row['EDATU']) === '00000000')
                    ? null
                    : $t_data_row['EDATU'];

                $parentRecord = ProductionTData::create($t_data_row);
                $inserted['t_data']++;

                // Ambil anak T2 berdasarkan key baru: KUNNR-NAME1
                $key_t2 = $kunnr . '-' . $name1;
                $children_t2 = $t2_grouped->get($key_t2, []);

                foreach ($children_t2 as $t2_row) {
                    $kdauf = trim($t2_row['KDAUF'] ?? '');
                    $kdpos = trim($t2_row['KDPOS'] ?? '');

                    if ($kdauf === '' && $kdpos === '') {
                        continue;
                    }

                    // Unique T2: plant + kdauf + kdpos
                    $key_t2_unique = $plant . '-' . $kdauf . '-' . $kdpos;
                    if (isset($seenTData2[$key_t2_unique])) {
                        continue;
                    }
                    $seenTData2[$key_t2_unique] = true;

                    $t2_row['WERKSX'] = $plant;
                    $t2_row['EDATU']  = (empty($t2_row['EDATU']) || trim($t2_row['EDATU']) === '00000000')
                        ? null
                        : $t2_row['EDATU'];

                    // Konsistensi key dari induk
                    $t2_row['KUNNR'] = $parentRecord->KUNNR;
                    $t2_row['NAME1'] = $parentRecord->NAME1;

                    ProductionTData2::create($t2_row);
                    $inserted['t_data2']++;

                    // Anak T3 berdasarkan (KDAUF-KDPOS)
                    $key_t3 = $kdauf . '-' . $kdpos;
                    $children_t3 = $t3_grouped->get($key_t3, []);

                    foreach ($children_t3 as $t3_row) {
                        $aufnr = trim($t3_row['AUFNR'] ?? '');
                        if ($aufnr === '') continue;

                        // Unique T3: plant + aufnr
                        $key_t3_unique = $plant . '-' . $aufnr;
                        if (isset($seenTData3[$key_t3_unique])) {
                            continue;
                        }
                        $seenTData3[$key_t3_unique] = true;

                        $t3_row['WERKSX'] = $plant;
                        ProductionTData3::create($t3_row);
                        $inserted['t_data3']++;

                        // Anak T1 & T4 berdasarkan AUFNR
                        $children_t1 = $t1_grouped->get($aufnr, []);
                        $children_t4 = $t4_grouped->get($aufnr, []);

                        // T1: Unique plant-aufnr-vornr + mapping PV1..PV3
                        foreach ($children_t1 as $t1_row) {
                            $vornr = trim($t1_row['VORNR'] ?? '');
                            $key_t1_unique = $plant . '-' . $aufnr . '-' . $vornr;

                            if (isset($seenTData1[$key_t1_unique])) continue;
                            $seenTData1[$key_t1_unique] = true;

                            $sssl1 = $formatTanggal($t1_row['SSSLDPV1'] ?? '');
                            $sssl2 = $formatTanggal($t1_row['SSSLDPV2'] ?? '');
                            $sssl3 = $formatTanggal($t1_row['SSSLDPV3'] ?? '');

                            $t1_row['PV1'] = $this->buildPv($t1_row['ARBPL1'] ?? null, $sssl1);
                            $t1_row['PV2'] = $this->buildPv($t1_row['ARBPL2'] ?? null, $sssl2);
                            $t1_row['PV3'] = $this->buildPv($t1_row['ARBPL3'] ?? null, $sssl3);

                            $t1_row['WERKSX'] = $plant;
                            ProductionTData1::create($t1_row);
                            $inserted['t_data1']++;
                        }

                        // T4: Unique plant-aufnr-rsnum-rspos
                        foreach ($children_t4 as $t4_row) {
                            $aufnr_t4 = trim($t4_row['AUFNR'] ?? '');
                            $rsnum    = trim($t4_row['RSNUM'] ?? '');
                            $rspos    = trim($t4_row['RSPOS'] ?? '');

                            $key_t4_unique = $plant . '-' . $aufnr_t4 . '-' . $rsnum . '-' . $rspos;

                            if (isset($seenTData4[$key_t4_unique])) continue;
                            $seenTData4[$key_t4_unique] = true;

                            $t4_row['WERKSX'] = $plant;
                            ProductionTData4::create($t4_row);
                            $inserted['t_data4']++;
                        }
                    }
                }
            }

            return [
                'plant' => $plant,
                'raw_counts' => [
                    'T_DATA'  => count($T_DATA),
                    'T_DATA1' => count($T1),
                    'T_DATA2' => count($T2),
                    'T_DATA3' => count($T3),
                    'T_DATA4' => count($T4),
                ],
                'inserted_counts' => $inserted,
            ];
        });

        Log::info("Refresh YPPR074Z selesai untuk Plant {$plant}", $summary);

        return $summary;
    }

    private function fetchSapCombined(string $plant, ?string $aufnr = null): array
    {
        $baseUrl = rtrim(config('services.credential_sap.base_url'), '/');
        if (!$baseUrl) throw new \RuntimeException('FLASK_API_URL is not configured');

        $user = session('username');
        $pass = session('password');
        if (!$user || !$pass) throw new \RuntimeException('SAP credentials not found in session');

        $query = ['plant' => $plant];
        if (!empty($aufnr)) $query['aufnr'] = $aufnr; // ✅

        $resp = Http::timeout(config('services.credential_sap.timeout', 3600))
            ->withHeaders([
                'X-SAP-Username' => $user,
                'X-SAP-Password' => $pass,
            ])
            ->get($baseUrl . '/api/sap_combined', $query);

        $resp->throw();

        return $resp->json() ?? [];
    }

    /**
     * Payload kadang {T_DATA...} atau {results:[{T_DATA...},{...}]}
     */
    private function flattenPayload(array $payload): array
    {
        $T_DATA = $T1 = $T2 = $T3 = $T4 = [];

        $dataBlocks = $payload['results'] ?? [$payload];

        foreach ($dataBlocks as $res) {
            if (!empty($res['T_DATA'])  && is_array($res['T_DATA']))  $T_DATA = array_merge($T_DATA, $res['T_DATA']);
            if (!empty($res['T_DATA1']) && is_array($res['T_DATA1'])) $T1     = array_merge($T1, $res['T_DATA1']);
            if (!empty($res['T_DATA2']) && is_array($res['T_DATA2'])) $T2     = array_merge($T2, $res['T_DATA2']);
            if (!empty($res['T_DATA3']) && is_array($res['T_DATA3'])) $T3     = array_merge($T3, $res['T_DATA3']);
            if (!empty($res['T_DATA4']) && is_array($res['T_DATA4'])) $T4     = array_merge($T4, $res['T_DATA4']);
        }

        return [$T_DATA, $T1, $T2, $T3, $T4];
    }

    private function deleteOldByPlant(string $plant): void
    {
        Log::info("Menghapus data lama untuk plant {$plant}...");

        ProductionTData4::where('WERKSX', $plant)->delete();
        ProductionTData1::where('WERKSX', $plant)->delete();
        ProductionTData3::where('WERKSX', $plant)->delete();
        ProductionTData2::where('WERKSX', $plant)->delete();
        ProductionTData::where('WERKSX', $plant)->delete();

        Log::info("Data lama berhasil dihapus untuk plant {$plant}.");
    }

    private function buildPv(?string $arbpl, ?string $tglFormatted): ?string
    {
        $parts = [];
        if (!empty($arbpl)) $parts[] = strtoupper($arbpl);
        if (!empty($tglFormatted)) $parts[] = $tglFormatted;
        return $parts ? implode(' - ', $parts) : null;
    }
    
    private function formatTanggal($tgl): ?string
    {
        if (empty($tgl) || trim($tgl) === '00000000') return null;
        try { return Carbon::createFromFormat('Ymd', $tgl)->format('d-m-Y'); }
        catch (\Throwable $e) { return null; }
    }

    private function deleteOldByAufnr(string $plant, string $aufnr): void
    {
        ProductionTData4::where('WERKSX', $plant)->where('AUFNR', $aufnr)->delete();
        ProductionTData1::where('WERKSX', $plant)->where('AUFNR', $aufnr)->delete();
        ProductionTData3::where('WERKSX', $plant)->where('AUFNR', $aufnr)->delete();
    }
    
}
