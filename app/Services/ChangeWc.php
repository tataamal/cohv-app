<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class ChangeWc
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.credential_sap.base_url'), '/');
        $this->timeout = (int) (config('services.credential_sap.timeout') ?? 30);
    }

    public function handle(array $payload): array
    {
        try {
            $sapUser = session('username');
            $sapPass = session('password');
        
            if (!$sapUser || !$sapPass) {
                 return [
                    'success' => false,
                    'message' => 'SAP credentials not found in session',
                 ];
            }

            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'X-SAP-Username' => $sapUser,
                    'X-SAP-Password' => $sapPass,
                ])
                ->post($this->baseUrl . '/api/save_edit', $payload);

            $data = $response->json();

            // Kalau HTTP gagal, tetap coba ambil pesan error dari body
            if (!$response->successful()) {
                $sapMessages = $this->extractSapMessages($data);

                return [
                    'success' => false,
                    'http_status' => $response->status(),
                    'messages' => $sapMessages,
                    'raw' => $data,
                ];
            }

            $sapMessages = $this->extractSapMessages($data);

            return [
                'success' => $this->isSapSuccess($sapMessages),
                'messages' => $sapMessages,
                'raw' => $data,
            ];

        } catch (RequestException $e) {
            return [
                'success' => false,
                'message' => 'Connection error to Flask API',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ambil pesan SAP dari response RFC (RETURN / ET_RETURN) dan normalisasi.
     */
    private function extractSapMessages($data): array
    {
        if (!is_array($data)) return [];

        $return = $data['RETURN'] ?? $data['ET_RETURN'] ?? [];

        // kalau SAP balikin single object, jadikan array
        if (is_array($return) && $this->isAssoc($return)) {
            $return = [$return];
        }

        if (!is_array($return)) return [];

        return collect($return)->map(function ($msg) {
            return [
                'type' => $msg['TYPE'] ?? null,
                'id' => $msg['ID'] ?? null,
                'number' => $msg['NUMBER'] ?? null,
                'message' => $msg['MESSAGE'] ?? ($msg['MESSAGE_V1'] ?? null),
            ];
        })->values()->toArray();
    }

    private function isSapSuccess(array $messages): bool
    {
        foreach ($messages as $msg) {
            // E = Error, A = Abort (kadang dipakai juga)
            if (in_array(($msg['type'] ?? ''), ['E', 'A'], true)) {
                return false;
            }
        }
        return true;
    }

    private function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}