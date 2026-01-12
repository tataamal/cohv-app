<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;


class Release
{
    public function release(string $aufnr): array
    {
        $baseUrl = rtrim(config('services.credential_sap.base_url'), '/');
        if (!$baseUrl) {
            throw new RuntimeException('FLASK_API_URL is not configured');
        }

        $sapUser = session('username');
        $sapPass = session('password');

        if (!$sapUser || !$sapPass) {
            throw new RuntimeException('SAP credential not found in session');
        }

        $response = Http::timeout(config('services.credential_sap.timeout', 30))
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-SAP-Username' => $sapUser,
                'X-SAP-Password' => $sapPass,
            ])
            ->post($baseUrl . '/api/release_order', [
                'AUFNR' => $aufnr,
            ]);

        if ($response->failed()) {
            $data = $response->json() ?? [];
            
            // Format error message from 'messages' array if exists
            if (!empty($data['messages']) && is_array($data['messages'])) {
                $errorStr = "SAP Error: ";
                foreach ($data['messages'] as $msg) {
                    $mType = $msg['type'] ?? '';
                    $mText = $msg['message'] ?? '';
                    if ($mType && $mText) {
                         $errorStr .= "[$mType] $mText; ";
                    }
                }
                // Trim trailing semicolon
                $errorStr = rtrim($errorStr, "; ");
                throw new RuntimeException($errorStr);
            }
            
            // Fallback to generic error or detail
            $detail = $data['detail'] ?? $data['message'] ?? $data['error'] ?? 'Unknown error';
            throw new RuntimeException("Release Failed ({$response->status()}): $detail");
        }

        return $response->json() ?? [];
    }
}
