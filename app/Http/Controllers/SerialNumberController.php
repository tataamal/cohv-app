<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SerialNumberGeneratorService;

class SerialNumberController extends Controller
{
    public function generateForPro(Request $request, SerialNumberGeneratorService $service)
    {
        $validated = $request->validate([
            'pro' => 'required|string',
            'pairs' => 'required|array|min:1',
            'pairs.*.so' => 'required|string',
            'pairs.*.item' => 'required|string',
        ]);

        // Kredensial SAP dari session (sesuai contohmu)
        $sapUser = session('username');
        $sapPass = session('password');

        if (!$sapUser || !$sapPass) {
            \Illuminate\Support\Facades\Log::error('[SN Controller] Missing SAP Session. User: ' . ($sapUser ? 'Set' : 'Null'));
            return response()->json(['status' => 'error', 'message' => 'Session SAP belum ada. Silakan login ulang.'], 401);
        }

        // Controller hanya meneruskan ke service
        $result = $service->generateForPro(
            $validated['pro'],
            $validated['pairs'],
            $sapUser,
            $sapPass
        );

        // Return apa adanya dari service
        $code = ($result['status'] ?? '') === 'error' ? 502 : 200;
        return response()->json($result, $code);
    }
}
