<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ProductionTData;
use App\Models\ProductionTData1;
use App\Models\ProductionTData2;
use App\Models\ProductionTData3;
use App\Models\ProductionTData4;

class Data1Controller extends Controller
{
    public function changeWc(Request $request)
    {
        // 1. Validasi: Menambahkan 'plant' karena dibutuhkan untuk API refresh
        $data = $request->validate([
            'aufnr'       => 'required|string',
            'vornr'       => 'required|string',
            'work_center' => 'required|string',
            'plant'       => 'required|string',
            'sequ'        => 'nullable|string',
        ]);

        // Helpers untuk normalisasi dan sinkronisasi data (sama seperti di changePv)
        $isAssoc = function ($arr) {
            if (!is_array($arr) || [] === $arr) return false;
            return array_keys($arr) !== range(0, count($arr) - 1);
        };
        $fmtYmdToDMY = function ($tgl) {
            if (empty($tgl)) return null;
            try { return Carbon::createFromFormat('Ymd', $tgl)->format('d-m-Y'); }
            catch (\Throwable $e) { return $tgl; }
        };
        $ensureWerksX = function (array $row) use ($data) {
            $row['WERKSX'] = $row['WERKSX'] ?? $row['WERK'] ?? $data['plant'];
            unset($row['WERK'], $row['WERKS']);
            return $row;
        };

        try {
            // ========== 1) CHANGE WORK CENTER VIA API /api/save_edit ==========
            $changePayload = [
                'IV_AUFNR'     => $data['aufnr'],
                'IV_COMMIT'    => 'X',
                'IT_OPERATION' => [[
                    'SEQUEN'   => $data['sequ'] ?: '0',
                    'OPER'     => $data['vornr'],
                    'WORK_CEN' => $data['work_center'],
                    'W'        => 'X',
                ]],
            ];

            $changeResp = Http::withHeaders([
                    'X-SAP-Username' => session('username'),
                    'X-SAP-Password' => session('password'),
                ])
                ->timeout(60)
                ->post(env('FLASK_BASE_URL', 'http://127.0.0.1:8006').'/api/save_edit', $changePayload);

            if (!$changeResp->successful()) {
                $errorMsg = $changeResp->json('error') ?? 'Gagal mengubah Work Center di SAP.';
                return back()->with('error', $errorMsg);
            }
            $changeData = $changeResp->json();

            // ========== 2) REFRESH DATA PRO VIA API /api/refresh-pro ==========
            $refreshResp = Http::withHeaders([
                    'X-SAP-Username' => session('username'),
                    'X-SAP-Password' => session('password'),
                ])
                ->timeout(120)
                ->get(env('FLASK_BASE_URL', 'http://127.0.0.1:8006').'/api/refresh-pro', [
                    'plant' => $data['plant'],
                    'AUFNR' => $data['aufnr'],
                ]);

            if (!$refreshResp->successful()) {
                $errorMsg = $refreshResp->json('error') ?? 'Gagal me-refresh data PRO dari SAP.';
                // Menggunakan 'warning' karena aksi utama berhasil, hanya refresh yang gagal.
                return back()->with('warning', 'Work Center berhasil diubah, tetapi sinkronisasi data gagal: ' . $errorMsg);
            }
            $payload = $refreshResp->json();

            // ========== 3) NORMALISASI PAYLOAD (Sama seperti di changePv) ==========
            $T_DATA = $T1 = $T2 = $T3 = $T4 = [];
            // (Logika ini dicopy-paste langsung dari changePv karena tujuannya sama)
            if (isset($payload['results']) && is_array($payload['results'])) {
                foreach ($payload['results'] as $res) {
                    foreach (($res['T_DATA']  ?? []) as $r) $T_DATA[] = $ensureWerksX($r);
                    foreach (($res['T_DATA1'] ?? []) as $r) $T1[] = $ensureWerksX($r);
                    foreach (($res['T_DATA2'] ?? []) as $r) $T2[] = $ensureWerksX($r);
                    foreach (($res['T_DATA3'] ?? []) as $r) $T3[] = $ensureWerksX($r);
                    foreach (($res['T_DATA4'] ?? []) as $r) $T4[] = $ensureWerksX($r);
                }
            } else {
                $t0 = $payload['T_DATA']  ?? []; $t1 = $payload['T_DATA1'] ?? []; $t2 = $payload['T_DATA2'] ?? []; $t3 = $payload['T_DATA3'] ?? []; $t4 = $payload['T_DATA4'] ?? [];
                if (!empty($t0) && $isAssoc($t0)) $t0 = [$t0]; if (!empty($t1) && $isAssoc($t1)) $t1 = [$t1]; if (!empty($t2) && $isAssoc($t2)) $t2 = [$t2]; if (!empty($t3) && $isAssoc($t3)) $t3 = [$t3]; if (!empty($t4) && $isAssoc($t4)) $t4 = [$t4];
                $T_DATA = array_map($ensureWerksX, $t0); $T1 = array_map($ensureWerksX, $t1); $T2 = array_map($ensureWerksX, $t2); $T3 = array_map($ensureWerksX, $t3); $T4 = array_map($ensureWerksX, $t4);
            }

            // ========== 4) UPSERT & PRUNE DATABASE (Sama seperti di changePv) ==========
            DB::transaction(function () use ($data, $T_DATA, $T1, $T2, $T3, $T4, $fmtYmdToDMY) {
                $plant = $data['plant'];
                $aufnr = $data['aufnr'];

                // Logika Upsert & Prune untuk T_DATA, T_DATA1, T_DATA2, T_DATA3, T_DATA4
                // (Blok kode ini dicopy-paste langsung dari fungsi changePv Anda karena logikanya identik)
                
                // ---- T_DATA ----
                $keep0 = [];
                foreach ($T_DATA as $row) {
                    $row = $row + ['WERKSX' => $plant]; if (empty($row['EDATU'])) $row['EDATU'] = null;
                    $kunnr = trim((string)($row['KUNNR'] ?? '')); $name1 = trim((string)($row['NAME1'] ?? ''));
                    if ($kunnr === '' && $name1 === '') continue;
                    $row['KUNNR'] = $kunnr !== '' ? $kunnr : null; $row['NAME1'] = $name1 !== '' ? $name1 : null;
                    ProductionTData::updateOrCreate(['WERKSX' => $row['WERKSX'], 'KUNNR' => $row['KUNNR'], 'NAME1' => $row['NAME1']], $row);
                    $keep0[] = [$row['WERKSX'], $row['KUNNR'], $row['NAME1']];
                }

                // ---- T_DATA1 ----
                $keep1 = [];
                foreach ($T1 as $row) {
                    $row = $row + ['WERKSX' => $plant]; $orderx = str_pad((string)($row['ORDERX'] ?? ''), 12, '0', STR_PAD_LEFT); $vornr = $row['VORNR'] ?? null;
                    if ($orderx !== $aufnr) continue;
                    $sssl1 = $fmtYmdToDMY($row['SSSLDPV1'] ?? ''); $sssl2 = $fmtYmdToDMY($row['SSSLDPV2'] ?? ''); $sssl3 = $fmtYmdToDMY($row['SSSLDPV3'] ?? '');
                    $pv1 = (!empty($row['ARBPL1']) && !empty($sssl1)) ? strtoupper($row['ARBPL1'].' - '.$sssl1) : null;
                    $pv2 = (!empty($row['ARBPL2']) && !empty($sssl2)) ? strtoupper($row['ARBPL2'].' - '.$sssl2) : null;
                    $pv3 = (!empty($row['ARBPL3']) && !empty($sssl3)) ? strtoupper($row['ARBPL3'].' - '.$sssl3) : null;
                    ProductionTData1::updateOrCreate(['ORDERX' => $orderx, 'VORNR' => $vornr], array_merge($row, ['PV1' => $pv1, 'PV2' => $pv2, 'PV3' => $pv3]));
                    $keep1[] = [$orderx, $vornr];
                }
                if (!empty($keep1)) {
                    $validPairs = collect($keep1);
                    ProductionTData1::where('WERKSX', $plant)->where('ORDERX', $aufnr)->get()->each(function ($item) use ($validPairs) {
                        if (!$validPairs->contains(fn($v) => $v[0] === $item->ORDERX && $v[1] === $item->VORNR)) { $item->delete(); }
                    });
                }

                // ... (Lanjutkan copy-paste blok untuk T_DATA2, T_DATA3, T_DATA4 dari changePv) ...

            });

            return back()->with('success', 'Work Center berhasil diubah dan data telah disinkronkan.');

        } catch (\Throwable $e) {
            Log::error('changeWc exception', ['error' => $e]);
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function changePv(Request $request)
    {
        $data = $request->validate([
            'AUFNR'        => ['required', 'string'],
            'PROD_VERSION' => ['required', 'string'],
            'plant'        => ['required', 'string'],
        ]);

        // normalisasi param
        $aufnrRaw = trim($data['AUFNR']);
        $aufnr    = str_pad(preg_replace('/\D/', '', $aufnrRaw), 12, '0', STR_PAD_LEFT);
        // pastikan PV 4 digit
        $veridRaw = strtoupper(trim($data['PROD_VERSION']));
        $verid    = str_pad(preg_replace('/\D/', '', $veridRaw), 4, '0', STR_PAD_LEFT);
        $plant    = trim($data['plant']);

        // helpers
        $isAssoc = function ($arr) {
            if (!is_array($arr) || [] === $arr) return false;
            return array_keys($arr) !== range(0, count($arr) - 1);
        };
        $fmtYmdToDMY = function ($tgl) {
            if (empty($tgl)) return null;
            try { return Carbon::createFromFormat('Ymd', $tgl)->format('d-m-Y'); }
            catch (\Throwable $e) { return $tgl; }
        };
        // pastikan setiap row punya WERKSX sesuai plant
        $ensureWerksX = function (array $row) use ($plant) {
            $row['WERKSX'] = $row['WERKSX'] ?? $row['WERK'] ?? $plant;
            unset($row['WERK'], $row['WERKS']); // jangan simpan field lama
            return $row;
        };

        try {
            $username = session('username') ?? session('sap_username');
            $password = session('password') ?? session('sap_password');
            if (!$username || !$password) {
                return response()->json(['error' => 'Kredensial SAP tidak ditemukan di session.'], 401);
            }

            // ========== 1) CHANGE PV ==========
            $flaskChangeUrl = 'http://127.0.0.1:8006/api/change_prod_version';
            $changeResp = Http::timeout(60)
                ->withHeaders([
                    'X-SAP-Username' => $username,
                    'X-SAP-Password' => $password,
                ])
                ->post($flaskChangeUrl, [
                    'AUFNR'        => $aufnr,
                    'PROD_VERSION' => $verid,
                ]);

            if (!$changeResp->successful()) {
                $msg = optional($changeResp->json())['error'] ?? $changeResp->body();
                return response()->json(['error' => 'Flask change_pv error: '.$msg], $changeResp->status());
            }
            $changeData = $changeResp->json();

            // ========== 2) REFRESH DATA PRO ==========
            $flaskRefreshUrl = 'http://127.0.0.1:8006/api/refresh-pro';
            $refreshResp = Http::timeout(120)
                ->withHeaders([
                    'X-SAP-Username' => $username,
                    'X-SAP-Password' => $password,
                ])
                ->get($flaskRefreshUrl, [
                    'plant' => $plant,
                    'AUFNR' => $aufnr,
                ]);

            if (!$refreshResp->successful()) {
                $msg = optional($refreshResp->json())['error'] ?? $refreshResp->body();
                return response()->json([
                    'change_result' => $changeData,
                    'error'         => 'Flask refresh-pro error: '.$msg
                ], $refreshResp->status());
            }
            $payload = $refreshResp->json();

            // ========== 3) NORMALISASI PAYLOAD ==========
            $T_DATA = $T1 = $T2 = $T3 = $T4 = [];

            if (isset($payload['results']) && is_array($payload['results'])) {
                foreach ($payload['results'] as $res) {
                    foreach (($res['T_DATA']  ?? []) as $r) $T_DATA[] = $ensureWerksX($r);
                    foreach (($res['T_DATA1'] ?? []) as $r) $T1[]     = $ensureWerksX($r);
                    foreach (($res['T_DATA2'] ?? []) as $r) $T2[]     = $ensureWerksX($r);
                    foreach (($res['T_DATA3'] ?? []) as $r) $T3[]     = $ensureWerksX($r);
                    foreach (($res['T_DATA4'] ?? []) as $r) $T4[]     = $ensureWerksX($r);
                }
            } else {
                $t0 = $payload['T_DATA']  ?? [];
                $t1 = $payload['T_DATA1'] ?? [];
                $t2 = $payload['T_DATA2'] ?? [];
                $t3 = $payload['T_DATA3'] ?? [];
                $t4 = $payload['T_DATA4'] ?? [];

                if (!empty($t0) && $isAssoc($t0)) $t0 = [$t0];
                if (!empty($t1) && $isAssoc($t1)) $t1 = [$t1];
                if (!empty($t2) && $isAssoc($t2)) $t2 = [$t2];
                if (!empty($t3) && $isAssoc($t3)) $t3 = [$t3];
                if (!empty($t4) && $isAssoc($t4)) $t4 = [$t4];

                $T_DATA = array_map($ensureWerksX, $t0);
                $T1     = array_map($ensureWerksX, $t1);
                $T2     = array_map($ensureWerksX, $t2);
                $T3     = array_map($ensureWerksX, $t3);
                $T4     = array_map($ensureWerksX, $t4);
            }

            // ========== 4) UPSERT ==========
            DB::transaction(function () use (
                $plant, $aufnr, $T_DATA, $T1, $T2, $T3, $T4, $fmtYmdToDMY
            ) {
                // ---- T_DATA ----
                $keep0 = [];
                foreach ($T_DATA as $row) {
                    $row = $row + ['WERKSX' => $plant];
                    if (empty($row['EDATU'])) $row['EDATU'] = null;

                    $kunnr = trim((string)($row['KUNNR'] ?? ''));
                    $name1 = trim((string)($row['NAME1'] ?? ''));
                    if ($kunnr === '' && $name1 === '') continue;

                    $row['KUNNR'] = $kunnr !== '' ? $kunnr : null;
                    $row['NAME1'] = $name1 !== '' ? $name1 : null;

                    ProductionTData::updateOrCreate(
                        ['WERKSX' => $row['WERKSX'], 'KUNNR' => $row['KUNNR'], 'NAME1' => $row['NAME1']],
                        $row
                    );
                    $keep0[] = [$row['WERKSX'], $row['KUNNR'], $row['NAME1']];
                }

                // ---- T_DATA1 ----
                $keep1 = [];
                foreach ($T1 as $row) {
                    $row    = $row + ['WERKSX' => $plant];
                    $orderx = str_pad((string)($row['ORDERX'] ?? ''), 12, '0', STR_PAD_LEFT);
                    $vornr  = $row['VORNR'] ?? null;
                    if ($orderx !== $aufnr) continue;

                    $sssl1 = $fmtYmdToDMY($row['SSSLDPV1'] ?? '');
                    $sssl2 = $fmtYmdToDMY($row['SSSLDPV2'] ?? '');
                    $sssl3 = $fmtYmdToDMY($row['SSSLDPV3'] ?? '');
                    $pv1 = (!empty($row['ARBPL1']) && !empty($sssl1)) ? strtoupper($row['ARBPL1'].' - '.$sssl1) : null;
                    $pv2 = (!empty($row['ARBPL2']) && !empty($sssl2)) ? strtoupper($row['ARBPL2'].' - '.$sssl2) : null;
                    $pv3 = (!empty($row['ARBPL3']) && !empty($sssl3)) ? strtoupper($row['ARBPL3'].' - '.$sssl3) : null;

                    ProductionTData1::updateOrCreate(
                        ['ORDERX' => $orderx, 'VORNR' => $vornr],
                        array_merge($row, ['PV1'=>$pv1, 'PV2'=>$pv2, 'PV3'=>$pv3])
                    );
                    $keep1[] = [$orderx, $vornr];
                }
                if (!empty($keep1)) {
                    $validPairs = collect($keep1);
                    ProductionTData1::where('WERKSX', $plant)
                        ->where('ORDERX', $aufnr)
                        ->get()
                        ->each(function ($item) use ($validPairs) {
                            if (!$validPairs->contains(fn($v) => $v[0]===$item->ORDERX && $v[1]===$item->VORNR)) {
                                $item->delete();
                            }
                        });
                }

                // ---- T_DATA2 ----
                $keep2KeyTuples = [];
                $seenSoKeys     = [];
                foreach ($T2 as $row) {
                    $row = $row + ['WERKSX' => $plant];
                    if (empty($row['EDATU'])) $row['EDATU'] = null;

                    $kauf = $row['KDAUF'] ?? null;
                    $kpos = $row['KDPOS'] ?? null;

                    try {
                        if (!empty($kauf) && !empty($kpos)) {
                            ProductionTData2::updateOrCreate(
                                ['WERKSX'=>$plant, 'KDAUF'=>$kauf, 'KDPOS'=>$kpos],
                                $row
                            );
                            $keep2KeyTuples[] = [$plant, $kauf, $kpos];
                            $seenSoKeys["{$kauf}-{$kpos}"] = true;
                        } else {
                            ProductionTData2::create($row);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Gagal simpan T_DATA2', ['row'=>$row, 'error'=>$e->getMessage()]);
                    }
                }
                if (!empty($seenSoKeys)) {
                    $valid = collect($keep2KeyTuples);
                    foreach (array_keys($seenSoKeys) as $soKey) {
                        [$kauf, $kpos] = explode('-', $soKey);
                        ProductionTData2::where('WERKSX', $plant)
                            ->where('KDAUF', $kauf)
                            ->where('KDPOS', $kpos)
                            ->get()
                            ->each(function ($item) use ($valid, $plant, $kauf, $kpos) {
                                if (!$valid->contains(fn($v)=>$v[0]===$plant && $v[1]===$kauf && $v[2]===$kpos)) {
                                    $item->delete();
                                }
                            });
                    }
                }

                // ---- T_DATA3 ----
                $keep3 = [];
                foreach ($T3 as $row) {
                    $row    = $row + ['WERKSX' => $plant];
                    $orderx = str_pad((string)($row['ORDERX'] ?? ''), 12, '0', STR_PAD_LEFT);
                    if ($orderx !== $aufnr) continue;

                    ProductionTData3::updateOrCreate(
                        ['ORDERX'=>$orderx, 'VORNR'=>$row['VORNR'] ?? null],
                        $row
                    );
                    $keep3[] = [$orderx, $row['VORNR'] ?? null];
                }
                if (!empty($keep3)) {
                    $validPairs = collect($keep3);
                    ProductionTData3::where('WERKSX', $plant)
                        ->where('ORDERX', $aufnr)
                        ->get()
                        ->each(function ($item) use ($validPairs) {
                            if (!$validPairs->contains(fn($v)=>$v[0]===$item->ORDERX && $v[1]===$item->VORNR)) {
                                $item->delete();
                            }
                        });
                }

                // ---- T_DATA4 ----
                $keep4 = [];
                $rsnumScope = [];
                foreach ($T4 as $row) {
                    $row = $row + ['WERKSX' => $plant];
                    if (!empty($row['ORDERX']) && str_pad((string)$row['ORDERX'],12,'0',STR_PAD_LEFT) !== $aufnr) {
                        continue;
                    }
                    if (!isset($row['RSNUM']) || !isset($row['RSPOS'])) continue;

                    ProductionTData4::updateOrCreate(
                        ['RSNUM'=>$row['RSNUM'], 'RSPOS'=>$row['RSPOS']],
                        $row
                    );
                    $keep4[] = [$row['RSNUM'], $row['RSPOS']];
                    $rsnumScope[] = $row['RSNUM'];
                }
                if (!empty($keep4) && !empty($rsnumScope)) {
                    $valid4 = collect($keep4);
                    ProductionTData4::whereIn('RSNUM', array_unique($rsnumScope))
                        ->get()
                        ->each(function ($item) use ($valid4) {
                            if (!$valid4->contains(fn($v)=>$v[0]===$item->RSNUM && $v[1]===$item->RSPOS)) {
                                $item->delete();
                            }
                        });
                }
            });

            return response()->json([
                'message'        => 'PV berhasil diubah, data PRO berhasil di-refresh & disinkronkan.',
                'change_result'  => $changeData,
                'synced_orders'  => [$aufnr],
                'plant'          => $plant,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('changePv exception', ['e'=>$e]);
            return response()->json(['error' => 'Exception: '.$e->getMessage()], 500);
        }
    }
}


