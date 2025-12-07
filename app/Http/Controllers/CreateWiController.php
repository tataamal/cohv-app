<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\workcenter;
use App\Models\ProductionTData1; 
use App\Models\WorkcenterMapping;
use App\Models\HistoryWi; // Model History WI
use Carbon\Carbon;

class CreateWiController extends Controller
{
    /**
     * Menampilkan halaman utama Work Instruction (Drag & Drop).
     */
    public function index($kode)
    {
        $apiUrl = 'https://monitoring-kpi.kmifilebox.com/api/get-nik-confirmasi';
        $apiToken = env('API_TOKEN_NIK'); 
        $employees = []; 

        try {
            $response = Http::withToken($apiToken)->post($apiUrl, ['kode_laravel' => $kode]);
            if ($response->successful()) {
                $employees = $response->json()['data'];
            }
        } catch (\Exception $e) {
            Log::error('Koneksi API NIK Error: ' . $e->getMessage());
        }

        // --- PERBAIKAN DI SINI ---
        $tData1 = ProductionTData1::where('WERKSX', $kode)
            ->whereRaw('MGVRG2 > LMNGA')
            ->where('STATS', 'REL') // <-- HANYA AMBIL PRO YANG BERSTATUS 'REL' (Released)
            ->get();
        // -------------------------

        $assignedProQuantities = $this->getAssignedProQuantities($kode);
        
        $filteredTData1 = $tData1->filter(function ($item) use ($assignedProQuantities) {
            $aufnr = $item->AUFNR;
            $qtySisaAwal = $item->MGVRG2 - $item->LMNGA;
            $qtyAllocatedInWi = $assignedProQuantities[$aufnr] ?? 0;
            $qtySisaAkhir = $qtySisaAwal - $qtyAllocatedInWi;
            return $qtySisaAkhir > 0;
        });
        
        $finalTData1 = $filteredTData1->map(function ($item) use ($assignedProQuantities) {
            $aufnr = $item->AUFNR;
            $qtySisaAwal = $item->MGVRG2 - $item->LMNGA;
            $qtyAllocatedInWi = $assignedProQuantities[$aufnr] ?? 0;
            $qtySisaAkhir = $qtySisaAwal - $qtyAllocatedInWi;
            
            // Mempertahankan nilai MGVRG2 asli untuk Qty Oper
            // Kita hanya mengupdate Qty sisa riil
            $item->real_sisa_qty = $qtySisaAkhir; 
            return $item;
        });
        
        $workcenters = workcenter::where('werksx', $kode)->get();
        $workcenterMappings = WorkcenterMapping::where('kode_laravel', $kode)->get();
        $parentWorkcenters = $this->buildWorkcenterHierarchy($workcenters, $workcenterMappings);

        return view('create-wi.index', [
            'kode'                 => $kode,
            'employees'            => $employees,
            'tData1'               => $finalTData1,
            'workcenters'          => $workcenters,
            'parentWorkcenters'    => $parentWorkcenters,
        ]);
    }

    protected function getAssignedProQuantities(string $kodePlant)
    {
        $histories = HistoryWi::where('plant_code', $kodePlant)->get();
        $assignedProQuantities = [];

        foreach ($histories as $history) {
            $proItems = $history->payload_data; 

            if (is_array($proItems)) {
                foreach ($proItems as $item) {
                    $aufnr = $item['aufnr'] ?? null;
                    $assignedQty = $item['assigned_qty'] ?? 0;

                    if ($aufnr) {
                        $currentTotal = $assignedProQuantities[$aufnr] ?? 0;
                        $assignedProQuantities[$aufnr] = $currentTotal + $assignedQty;
                    }
                }
            }
        }

        return $assignedProQuantities;
    }

    /**
     * Memproses mapping Workcenter Induk dan Anak.
     */
    protected function buildWorkcenterHierarchy($primaryWCs, $mappings)
    {
        $primaryWcCodes = $primaryWCs->pluck('kode_wc')->map(fn($code) => strtoupper($code))->all();
        $parentHierarchy = [];

        foreach ($mappings as $mapping) {
            $parentCode = strtoupper($mapping->wc_induk);
            $childCode = $mapping->workcenter;
            $childName = $mapping->nama_workcenter;

            if (!in_array($parentCode, $primaryWcCodes)) {
                continue;
            }
            
            if ($parentCode === strtoupper($childCode)) {
                continue;
            }
            
            if (!isset($parentHierarchy[$parentCode])) {
                $parentHierarchy[$parentCode] = [];
            }

            $isDuplicate = collect($parentHierarchy[$parentCode])->contains('code', $childCode);

            if (!$isDuplicate) {
                $parentHierarchy[$parentCode][] = [
                    'code' => $childCode,
                    'name' => $childName,
                ];
            }
        }

        $parentHierarchy = array_filter($parentHierarchy, function($children) {
            return count($children) > 0;
        });

        return $parentHierarchy;
    }

    /**
     * Mendapatkan kode Plant dari kode Workcenter.
     */
    protected function mapPlantToLocationCode(string $plantCode): string
    {
        $plantCode = trim($plantCode);
        $plantNumericCode = '';

        if ($plantCode === '1001') {
            $plantNumericCode = '1001';
        } 

        elseif (is_numeric($plantCode) && strlen($plantCode) >= 4) {
            $prefix = substr($plantCode, 0, 1);
            $plantNumericCode = $prefix . '000'; 
            $plantNumericCode = substr($plantCode, 0, 4); 

            if (str_starts_with($plantCode, '1')) {
                $plantNumericCode = '1000'; // Mencakup 1004, 1005, dll.
            } elseif (str_starts_with($plantCode, '2')) {
                $plantNumericCode = '2000'; // Mencakup 2002, dll.
            } elseif (str_starts_with($plantCode, '3')) {
                $plantNumericCode = '3000'; // Mencakup 3016, dll.
            }

        } else {
            return 'XXXX'; 
        }
        if ($plantNumericCode === '3000' || $plantNumericCode === '3016') { 
            return 'SMG';
        } 
        if ($plantNumericCode !== 'XXXX') {
            return 'SBY';
        }
        
        return 'XXXX';
    }

    /**
     * Menyimpan alokasi Work Instruction (WI) dan membuat kode dokumen unik.
     */
    public function saveWorkInstruction(Request $request)
    {
        $requestData = $request->json()->all();
        $plantCode = $requestData['plant_code'] ?? null;
        $inputDate = $requestData['document_date'] ?? now()->toDateString();
        $inputTime = $requestData['document_time'] ?? '00:00';
        $payload = $requestData['workcenter_allocations'] ?? [];

        if (empty($payload) || !$plantCode || !$inputDate) {
            return response()->json(['message' => 'Data tidak lengkap. Tanggal/Plant/alokasi kosong.'], 400);
        }
        
        $dateTime = Carbon::parse($inputDate . ' ' . $inputTime);
        $expiredAt = $dateTime->copy()->addHours(12);
        $dateForCode = $dateTime->format('ymd'); 
        $dateForDb = $dateTime->toDateString();
        $timeForDb = $dateTime->toTimeString();
        
        $wiDocuments = [];
        $locationCode = $this->mapPlantToLocationCode($plantCode);

        try {
            foreach ($payload as $wcAllocation) {
                $workcenterCode = $wcAllocation['workcenter'];
                
                DB::transaction(function () use ($workcenterCode, $dateForDb, $plantCode, $wcAllocation, $locationCode, $dateForCode, $timeForDb, $expiredAt, &$wiDocuments) {
                    $latestHistory = HistoryWi::where('workcenter_code', $workcenterCode)
                        ->where('document_date', $dateForDb) 
                        ->orderByDesc('sequence_number')
                        ->lockForUpdate() 
                        ->first();
    
                    $sequence = $latestHistory ? $latestHistory->sequence_number + 1 : 1;
                    $documentCode = sprintf(
                        '%s%s%s%03d',
                        $workcenterCode, 
                        $locationCode,    
                        $dateForCode,     
                        $sequence         
                    );

                    HistoryWi::create([
                        'wi_document_code' => $documentCode,
                        'workcenter_code' => $workcenterCode,
                        'plant_code' => $plantCode,
                        'document_date' => $dateForDb,
                        'document_time' => $timeForDb,       // <-- BARU: Simpan Jam Mulai
                        'expired_at' => $expiredAt->toDateTimeString(), // <-- BARU: Simpan Waktu Expired
                        'sequence_number' => $sequence,
                        'payload_data' => $wcAllocation['pro_items'], 
                    ]);
                    
                    $wiDocuments[] = [
                        'workcenter' => $workcenterCode,
                        'document_code' => $documentCode,
                    ];
                });
            }

            return response()->json([
                'message' => 'Work Instructions berhasil disimpan.',
                'documents' => $wiDocuments,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error saat menyimpan WI:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'payload' => $requestData
            ]);
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan Work Instructions.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }
}