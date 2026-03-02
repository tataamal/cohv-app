<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Client\ConnectionException;

class CheckQuantityConfiramsi
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.sap_konfirmasi.base_url'), '/');
        $this->timeout = (int) (config('services.sap_konfirmasi.timeout') ?? 15);
    }

    public function check(string $aufnr, string $vornr, string $pernr, string $budat): array
    {
        $user = Session::get('username');
        $pass = Session::get('password');

        if (!$user || !$pass) {
            return [
                'status'        => 'failed',
                'msg_error'     => 'SAP credential tidak ada di session. Silakan login SAP ulang.',
                'total_rows'    => 0,
                'confirmed_qty' => 0,
                'http_code'     => 401,
            ];
        }

        if (!$this->isDdmmyyyy($budat)) {
            return [
                'status'        => 'failed',
                'msg_error'     => 'P_BUDAT harus format DDMMYYYY, contoh: 28022026',
                'total_rows'    => 0,
                'confirmed_qty' => 0,
                'http_code'     => 422,
            ];
        }

        $payload = [
            'P_AUFNR' => $aufnr,
            'P_VORNR' => $vornr,
            'P_PERNR' => $pernr,
            'P_BUDAT' => $budat,
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->withHeaders([
                    'X-SAP-Username' => $user,
                    'X-SAP-Password' => $pass,
                ])
                ->post($this->baseUrl . '/api/check_hasil_konfirmasi', $payload);

            $httpCode = $response->status();
            $data = $response->json();

            if (!is_array($data)) {
                return [
                    'status'        => 'failed',
                    'msg_error'     => 'Response API tidak valid (bukan JSON).',
                    'total_rows'    => 0,
                    'confirmed_qty' => 0,
                    'http_code'     => $httpCode,
                ];
            }

            return [
                'status'        => $data['status'] ?? ($response->successful() ? 'success' : 'failed'),
                'msg_error'     => $data['msg_error'] ?? '',
                'total_rows'    => $data['total_rows'] ?? 0,
                'confirmed_qty' => $data['confirmed_qty'] ?? 0,
                'http_code'     => $httpCode,
            ];
        } catch (ConnectionException $e) {
            return [
                'status'        => 'failed',
                'msg_error'     => 'Gagal konek ke API: ' . $e->getMessage(),
                'total_rows'    => 0,
                'confirmed_qty' => 0,
                'http_code'     => 502,
            ];
        } catch (\Throwable $e) {
            return [
                'status'        => 'failed',
                'msg_error'     => 'Error: ' . $e->getMessage(),
                'total_rows'    => 0,
                'confirmed_qty' => 0,
                'http_code'     => 500,
            ];
        }
    }

    private function isDdmmyyyy(string $s): bool
    {
        if (!preg_match('/^\d{8}$/', $s)) return false;

        $dd = (int) substr($s, 0, 2);
        $mm = (int) substr($s, 2, 2);
        $yy = (int) substr($s, 4, 4);

        return checkdate($mm, $dd, $yy);
    }
}