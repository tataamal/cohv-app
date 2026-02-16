<?php

namespace App\Services;

use App\Models\SerialNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class SerialNumberGeneratorService
{
    private string $flaskBaseUrl;

    public function __construct()
    {
        $this->flaskBaseUrl = rtrim(env('FLASK_API_URL'), '/');
        if (empty($this->flaskBaseUrl)) {
            Log::error('[SN Service] FLASK_API_URL is empty or missing in .env');
        }
    }

    private function pad12(string $v): string
    {
        $v = trim((string) $v);
        return strlen($v) >= 12 ? $v : str_pad($v, 12, '0', STR_PAD_LEFT);
    }

    private function normItem($item): string
    {
        $item = ltrim((string)($item ?? ''), '0');
        return $item === '' ? '0' : $item;
    }

    /**
     * Proses 1 PRO (AUFNR) → call Flask → simpan serial ke DB.
     * pairs: [{so,item},...]
     */
    public function generateForPro(string $pro, array $pairs, string $sapUser, string $sapPass): array
    {
        $pro = $this->pad12($pro);

        // Candidate set (so|item) yang valid untuk disimpan
        $candidateSet = [];
        foreach ($pairs as $p) {
            $so = trim((string)($p['so'] ?? ''));
            $it = $this->normItem($p['item'] ?? '');
            if ($so !== '') $candidateSet["{$so}|{$it}"] = true;
        }

        if (count($candidateSet) === 0) {
            return [
                'status' => 'error',
                'pro' => $pro,
                'message' => 'pairs kosong / tidak valid'
            ];
        }

        $url = $this->flaskBaseUrl . '/api/generate_serial_number';

        Log::info("[SN] Call Flask generate_serial_number pro={$pro} pairs=" . count($candidateSet));

        $resp = Http::timeout(60)->withHeaders([
            'X-SAP-Username' => $sapUser,
            'X-SAP-Password' => $sapPass,
            'Accept'         => 'application/json',
            'Content-Type'   => 'application/json',
        ])->post($url, [
            'AUFNR' => $pro,
        ]);

        if (!$resp->ok()) {
            return [
                'status' => 'error',
                'pro' => $pro,
                'http_status' => $resp->status(),
                'message' => 'Flask/SAP call failed',
                'detail' => $resp->json() ?? $resp->body(),
            ];
        }

        $payload   = $resp->json() ?? [];
        $sapReturn = $payload['return'] ?? $payload['RETURN'] ?? [];
        $tData1    = $payload['T_DATA1'] ?? [];

        // normalisasi T_DATA1
        if (is_array($tData1) && isset($tData1['SERNR'])) $tData1 = [$tData1];
        if (!is_array($tData1)) $tData1 = [];

        $saved = 0; $dup = 0; $skipped = 0;

        foreach ($tData1 as $row) {
            if (!is_array($row)) { $skipped++; continue; }

            $serial = trim((string)($row['SERNR'] ?? ''));
            if ($serial === '') { $skipped++; continue; }

            $so   = trim((string)($row['KDAUF'] ?? ''));
            $item = $this->normItem($row['KDPOS'] ?? '');

            // fallback: kalau SAP tidak mengisi KDAUF/KDPOS, dan kandidat hanya 1 pasang
            if (($so === '' || $item === '0') && count($candidateSet) === 1) {
                $only = array_key_first($candidateSet);
                [$so, $item] = explode('|', $only);
            }

            // pastikan hanya simpan untuk so+item yang dikirim FE
            if ($so === '' || !isset($candidateSet["{$so}|{$item}"])) {
                $skipped++;
                continue;
            }

            try {
                SerialNumber::create([
                    'so' => $so,
                    'item' => $item,
                    'serial_number' => $serial,
                    'gi_painting_date' => null,
                    'gr_painting_date' => null,
                    'gi_packing_date' => null,
                    'gr_packing_date' => null,
                ]);
                $saved++;
            } catch (QueryException $e) {
                // kalau kamu sudah pasang unique index (so,item,serial_number)
                $dup++;
            }
        }

        // status: pending jika belum ada serial keluar
        $status = empty($tData1) ? 'pending' : 'success';

        return [
            'status' => $status,
            'pro' => $pro,
            'attempts' => $payload['attempts'] ?? null,
            'sap_return' => $sapReturn,
            'counts' => [
                'received' => count($tData1),
                'saved' => $saved,
                'duplicates' => $dup,
                'skipped' => $skipped,
            ],
        ];
    }
}
