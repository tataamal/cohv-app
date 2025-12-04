<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// IMPORT MODEL (Perhatikan huruf besar/kecil sesuai file model Anda)
use App\Models\workcenter;       // Model workcenter (huruf kecil 'w')
use App\Models\ProductionTData1; 

class CreateWiController extends Controller
{
    public function index($kode)
    {
        // 1. API NIK
        $apiUrl = 'https://monitoring-kpi.kmifilebox.com/api/get-nik-confirmasi';
        $apiToken = env('API_TOKEN_NIK'); 
        $employees = []; 

        try {
            $response = Http::withToken($apiToken)->post($apiUrl, ['kode_laravel' => $kode]);
            if ($response->successful()) {
                $employees = $response->json()['data'];
            }
        } catch (\Exception $e) {
            Log::error('Koneksi API Error: ' . $e->getMessage());
        }

        // 2. Ambil Data TData1
        // Pastikan ProductionTData1 sudah di-import di atas: use App\Models\ProductionTData1;
        $tData1 = ProductionTData1::where('WERKSX', $kode)->get();

        // 3. Ambil Data Workcenter
        // Pastikan workcenter sudah di-import di atas: use App\Models\workcenter;
        $workcenters = workcenter::where('werksx', $kode)->get();

        // --- DEBUGGING START ---
        // 4. Return View
        return view('create-wi.index', [
            'kode'        => $kode,
            'employees'   => $employees,
            'tData1'      => $tData1,
            'workcenters' => $workcenters
        ]);
    }

    public function store(Request $request)
    {
        // Logika simpan
        return response()->json(['message' => 'Success']);
    }
}