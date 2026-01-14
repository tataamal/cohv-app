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
                $errorLines = [];
                $errorLines[] = "SAP Error:";
                foreach ($data['messages'] as $msg) {
                    $mType = $msg['type'] ?? '';
                    $mText = $msg['message'] ?? '';
                    if ($mType && $mText) {
                         // Simplify message: Remove type if redundant or format it nicely
                         $errorLines[] = "[$mType] $mText";
                    }
                }
                // Join with HTML break for frontend display
                $errorStr = implode('<br>', $errorLines);
                
                // Also clean up semicolons if they exist within individual messages
                $errorStr = str_replace(';', '<br>', $errorStr);
                
                throw new RuntimeException($errorStr);
            }
            
            // Fallback to generic error or detail
            $detail = $data['detail'] ?? $data['message'] ?? $data['error'] ?? 'Unknown error';
            // Determine if detail contains semicolons that need formatting
            if (str_contains($detail, ';')) {
                 $detail = str_replace(';', '<br>', $detail);
            }
            throw new RuntimeException("Release Failed ({$response->status()}):<br>$detail");
        }

        return $response->json() ?? [];
    }
}
