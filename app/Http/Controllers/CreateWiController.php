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
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Kode;
use App\Models\ProductionTData;
use App\Models\ProductionTData2;
use App\Models\ProductionTData3;
use App\Models\ProductionTData4;

class CreateWiController extends Controller
{
    public function delete(Request $request) {
        $ids = $request->input('wi_codes');
        if (!$ids || !is_array($ids)) {
            return response()->json(['message' => 'Invalid data provided.'], 400);
        }

        try {
            DB::beginTransaction();
            // Delete based on wi_document_code
            HistoryWi::whereIn('wi_document_code', $ids)->delete();
            DB::commit();
            return response()->json(['message' => 'Documents deleted successfully.', 'count' => count($ids)]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete WI Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete documents: ' . $e->getMessage()], 500);
        }
    }
    public function index(Request $request, $kode)
    {
        $filter = $request->query('filter', 'all');
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
        $search = $request->query('search'); // Add search param
        
        $tData1 = ProductionTData1::where('WERKSX', $kode)
            ->whereRaw('MGVRG2 > LMNGA')
            ->where(function ($query) {
                $query->where('STATS', 'LIKE', '%REL%')
                      ->orWhere('STATS', 'LIKE', '%PCNF%');
            });

        if ($search) {
            // Split by space, comma, or newline to support list pasting
            $terms = preg_split('/[\s,]+/', $search, -1, PREG_SPLIT_NO_EMPTY);
            
            $tData1->where(function($q) use ($terms) {
                foreach ($terms as $term) {
                    // Use orWhere to allow "Term1 OR Term2" logic (e.g. WC250 WC251)
                    $q->orWhere(function($subQ) use ($term) {
                        $subQ->where('AUFNR', 'like', "%{$term}%")
                             ->orWhere('MATNR', 'like', "%{$term}%")
                             ->orWhere('MAKTX', 'like', "%{$term}%")
                             ->orWhere('KDAUF', 'like', "%{$term}%")
                             ->orWhere('KDPOS', 'like', "%{$term}%")
                             ->orWhere('ARBPL', 'like', "%{$term}%")
                             ->orWhere('STEUS', 'like', "%{$term}%")
                             ->orWhere('VORNR', 'like', "%{$term}%");
                    });
                }
            });
        }

        if ($filter === 'today') {
            $tData1->whereDate('SSAVD', now());
        } elseif ($filter === 'week') {
            $tData1->whereBetween('SSAVD', [now()->startOfWeek(), now()->endOfWeek()]);
        }
        $perPage = 30;
        $page = $request->input('page', 1);
        $tDataQuery = $tData1;
        $pagination = $tDataQuery->paginate($perPage); 
        $assignedProQuantities = $this->getAssignedProQuantities($kode);
        $processedCollection = $pagination->getCollection()->transform(function ($item) use ($assignedProQuantities) {
            $aufnr = $item->AUFNR;
            $qtySisaAwal = $item->MGVRG2 - $item->LMNGA;
            $qtyAllocatedInWi = $assignedProQuantities[$aufnr] ?? 0;
            $qtySisaAkhir = $qtySisaAwal - $qtyAllocatedInWi;
            $item->real_sisa_qty = $qtySisaAkhir; 
            $item->qty_wi = $qtyAllocatedInWi; 
            return $item;
        })->filter(function ($item) {
             return $item->real_sisa_qty > 0.001; 
        });

        // Initialize WC Names Map for global usage (Ajax & Full View)
        $workcenterMappings = WorkcenterMapping::where('plant', $kode)->get();
        if ($workcenterMappings->isEmpty()) {
             $workcenterMappings = WorkcenterMapping::where('kode_laravel', $kode)->get();
        }
        $wcNames = [];
        foreach ($workcenterMappings as $m) {
            if ($m->wc_induk) $wcNames[strtoupper($m->wc_induk)] = $m->nama_wc_induk;
            if ($m->workcenter) $wcNames[strtoupper($m->workcenter)] = $m->nama_workcenter;
        }

        if ($request->ajax()) {
            $html = view('create-wi.partials.source_table_rows', [
                'tData1' => $processedCollection,
                'wcNames' => $wcNames, // Pass to partial
            ])->render();
            return response()->json([
                'html' => $html,
                'next_page' => $pagination->hasMorePages() ? $pagination->currentPage() + 1 : null
            ]);
        }
        
        $allWorkcenters = workcenter::where('werksx', $kode)->get();
        $parentWorkcenters = $this->buildWorkcenterHierarchy($allWorkcenters, $workcenterMappings);

        // Filter workcenters that are REGISTERED AS CHILD in mapping
        // "WC induknya tetap tampilkan, hanya yang terdaftar sebagai WC anak yang tidak ditampilkan"
        $childCodes = $workcenterMappings->pluck('workcenter')
            ->filter()
            ->map(fn($code) => strtoupper($code))
            ->unique()
            ->all();

        $workcenters = $allWorkcenters->reject(function ($wc) use ($childCodes) {
            return in_array(strtoupper($wc->kode_wc), $childCodes);
        });
        $capacityData = ProductionTData1::where('WERKSX', $kode)
            ->whereRaw('MGVRG2 > LMNGA') // Only active items
            ->select('ARBPL', 'KAPAZ')
            ->distinct()
            ->get();
            
        $capacityMap = $capacityData->pluck('KAPAZ', 'ARBPL')->toArray();

        return view('create-wi.index', [
            'kode'                 => $kode,
            'employees'            => $employees,
            'tData1'               => $processedCollection,
            'workcenters'          => $workcenters,
            'parentWorkcenters'    => $parentWorkcenters,
            'capacityMap'          => $capacityMap,
            'wcNames'              => $wcNames, // Pass Map
            'currentFilter'        => $filter,
            'nextPage'             => $pagination->hasMorePages() ? 2 : null
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

    public function refreshData(Request $request, $kode)
    {
        set_time_limit(0);
        Log::info("==================================================");
        Log::info("Memulai REFERESH DATA (WI) untuk Plant: {$kode}");
        Log::info("==================================================");

        try {
            // 1. Validasi Auth SAP
            if (!session('username') || !session('password')) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Session SAP username/password tidak ditemukan. Silakan login ulang.'
                ], 401);
            }

            // 2. Ambil data dari API SAP
            Log::info("[Refresh WI] Fetching from API SAP...");
            $response = Http::timeout(3600)->withHeaders([
                'X-SAP-Username' => session('username'),
                'X-SAP-Password' => session('password'),
            ])->get(env('FLASK_API_URL') . '/api/sap_combined', ['plant' => $kode]);

            if (!$response->successful()) {
                Log::error("[Refresh WI] Gagal ambil data SAP. Status: " . $response->status());
                return response()->json([
                    'success' => false, 
                    'message' => 'Gagal mengambil data dari SAP (Status: ' . $response->status() . ')'
                ], 500);
            }
            $payload = $response->json();
            
            // Helper format tanggal
            $formatTanggal = function ($tgl) {
                if (empty($tgl) || trim($tgl) === '00000000') return null;
                try { return Carbon::createFromFormat('Ymd', $tgl)->format('d-m-Y'); }
                catch (\Exception $e) { return null; }
            };

            // 3. Update Database
            DB::transaction(function () use ($payload, $kode, $formatTanggal) {
                $T_DATA = $T1 = $T2 = $T3 = $T4 = [];
                $dataBlocks = $payload['results'] ?? [$payload];

                foreach ($dataBlocks as $res) {
                    if (!empty($res['T_DATA']))  $T_DATA = array_merge($T_DATA, $res['T_DATA']);
                    if (!empty($res['T_DATA1'])) $T1 = array_merge($T1, $res['T_DATA1']);
                    if (!empty($res['T_DATA2'])) $T2 = array_merge($T2, $res['T_DATA2']);
                    if (!empty($res['T_DATA3'])) $T3 = array_merge($T3, $res['T_DATA3']);
                    if (!empty($res['T_DATA4'])) $T4 = array_merge($T4, $res['T_DATA4']);
                }

                // Hapus Data Lama
                ProductionTData4::where('WERKSX', $kode)->delete();
                ProductionTData1::where('WERKSX', $kode)->delete();
                ProductionTData3::where('WERKSX', $kode)->delete();
                ProductionTData2::where('WERKSX', $kode)->delete();
                ProductionTData::where('WERKSX', $kode)->delete();

                // Grouping untuk Relasi
                $t2_grouped = collect($T2)->groupBy(fn($item) => trim($item['KUNNR'] ?? '') . '-' . trim($item['NAME1'] ?? ''));
                $t3_grouped = collect($T3)->groupBy(fn($item) => trim($item['KDAUF'] ?? '') . '-' . trim($item['KDPOS'] ?? ''));
                $t1_grouped = collect($T1)->groupBy(fn($item) => trim($item['AUFNR'] ?? ''));
                $t4_grouped = collect($T4)->groupBy(fn($item) => trim($item['AUFNR'] ?? ''));

                // Insert Berjenjang
                foreach ($T_DATA as $t_data_row) {
                    $kunnr = trim((string)($t_data_row['KUNNR'] ?? ''));
                    $name1 = trim((string)($t_data_row['NAME1'] ?? ''));
                    if ($kunnr === '' && $name1 === '') continue;

                    $t_data_row['KUNNR'] = $kunnr;
                    $t_data_row['NAME1'] = $name1;
                    $t_data_row['WERKSX'] = $kode;
                    $t_data_row['EDATU'] = (empty($t_data_row['EDATU']) || trim($t_data_row['EDATU']) === '00000000') ? null : $t_data_row['EDATU'];

                    $parentRecord = ProductionTData::create($t_data_row);
                    
                    $key_t2 = $kunnr . '-' . $name1;
                    $children_t2 = $t2_grouped->get($key_t2, []);

                    foreach ($children_t2 as $t2_row) {
                        $t2_row['WERKSX'] = $kode;
                        $t2_row['EDATU'] = (empty($t2_row['EDATU']) || trim($t2_row['EDATU']) === '00000000') ? null : $t2_row['EDATU'];
                        $t2_row['KUNNR'] = $parentRecord->KUNNR;
                        $t2_row['NAME1'] = $parentRecord->NAME1;
                        
                        $t2Record = ProductionTData2::create($t2_row);
                        
                        $key_t3 = trim($t2Record->KDAUF ?? '') . '-' . trim($t2Record->KDPOS ?? '');
                        $children_t3 = $t3_grouped->get($key_t3, []);

                        foreach ($children_t3 as $t3_row) {
                            $t3_row['WERKSX'] = $kode;
                            $t3Record = ProductionTData3::create($t3_row);
                            
                            $key_t1_t4 = trim($t3Record->AUFNR ?? '');
                            if (empty($key_t1_t4)) continue;

                            $children_t1 = $t1_grouped->get($key_t1_t4, []);
                            $children_t4 = $t4_grouped->get($key_t1_t4, []);
                            
                            foreach ($children_t1 as $t1_row) {
                                $sssl1 = $formatTanggal($t1_row['SSSLDPV1'] ?? '');
                                $sssl2 = $formatTanggal($t1_row['SSSLDPV2'] ?? '');
                                $sssl3 = $formatTanggal($t1_row['SSSLDPV3'] ?? '');
                                
                                $partsPv1 = [];
                                if (!empty($t1_row['ARBPL1'])) $partsPv1[] = strtoupper($t1_row['ARBPL1']);
                                if (!empty($sssl1)) $partsPv1[] = $sssl1;
                                $t1_row['PV1'] = !empty($partsPv1) ? implode(' - ', $partsPv1) : null;

                                $partsPv2 = [];
                                if (!empty($t1_row['ARBPL2'])) $partsPv2[] = strtoupper($t1_row['ARBPL2']);
                                if (!empty($sssl2)) $partsPv2[] = $sssl2;
                                $t1_row['PV2'] = !empty($partsPv2) ? implode(' - ', $partsPv2) : null;

                                $partsPv3 = [];
                                if (!empty($t1_row['ARBPL3'])) $partsPv3[] = strtoupper($t1_row['ARBPL3']);
                                if (!empty($sssl3)) $partsPv3[] = $sssl3;
                                $t1_row['PV3'] = !empty($partsPv3) ? implode(' - ', $partsPv3) : null;
                                
                                $t1_row['WERKSX'] = $kode; 
                                ProductionTData1::create($t1_row);
                            }
                            
                            foreach ($children_t4 as $t4_row) {
                                $t4_row['WERKSX'] = $kode;
                                ProductionTData4::create($t4_row);
                            }
                        }
                    }
                }
            });

            return response()->json(['success' => true, 'message' => 'Data berhasil di-refresh dari SAP.'], 200);

        } catch (\Exception $e) {
            Log::error("[Refresh WI] Error: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
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
                // Find child Kapaz
                $childWcObj = $primaryWCs->firstWhere('kode_wc', $childCode);
                
                // Kapaz is string in DB "7,5", "7.5" etc. Need to normalize or pass as is.
                // View expects raw capacity to handle conversion. 
                // Let's pass raw KAPAZ string.
                $childKapaz = $childWcObj ? $childWcObj->KAPAZ : 0;

                $parentHierarchy[$parentCode][] = [
                    'code' => $childCode,
                    'name' => $childName,
                    'kapaz' => $childKapaz, // Added KAPAZ
                ];
            }
        }

        $parentHierarchy = array_filter($parentHierarchy, function($children) {
            return count($children) > 0;
        });

        return $parentHierarchy;
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
        $docPrefix = str_starts_with($plantCode, '3') ? 'WIH' : 'WIW';

        $dateTime = Carbon::parse($inputDate . ' ' . $inputTime);
        
        if ($docPrefix === 'WIH') {
            // Set expired to 00:00 Next Day (Midnight)
            $expiredAt = $dateTime->copy()->addDay()->startOfDay();
        } else {
            $expiredAt = $dateTime->copy()->addHours(12);
        }
        $dateForDb = $dateTime->toDateString();
        $timeForDb = $dateTime->toTimeString();
        $year = $dateTime->year; // Get Year
        
        $wiDocuments = [];

        try {
            foreach ($payload as $wcAllocation) {
                $workcenterCode = $wcAllocation['workcenter'];
                DB::transaction(function () use ($docPrefix, $workcenterCode, $plantCode, $dateForDb, $timeForDb, $year, $expiredAt, $wcAllocation, &$wiDocuments) {
                    $latestHistory = HistoryWi::withTrashed()
                        ->where('wi_document_code', 'LIKE', $docPrefix . '%')
                        ->orderByRaw('LENGTH(wi_document_code) DESC')
                        ->orderBy('wi_document_code', 'desc')
                        ->lockForUpdate() 
                        ->first();
    
                    $nextNumber = 1;
                    
                    if ($latestHistory) {
                        $currentCode = $latestHistory->wi_document_code;
                        $numberPart = substr($currentCode, 3); 
                        $nextNumber = intval($numberPart) + 1;
                    }
                    $documentCode = $docPrefix . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);

                    HistoryWi::create([
                        'wi_document_code' => $documentCode,
                        'workcenter_code' => $workcenterCode,
                        'plant_code' => $plantCode,
                        'document_date' => $dateForDb,
                        'document_time' => $timeForDb,       
                        'expired_at' => $expiredAt->toDateTimeString(),
                        'sequence_number' => $nextNumber, 
                        'payload_data' => $wcAllocation['pro_items'], 
                        'year' => $year // Save Year
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

    public function history(Request $request, $kode) 
    {
        $plantCode = $kode;
        $nama_bagian  = Kode::where('kode', $plantCode)->first();
        $now = Carbon::now();
        $query = HistoryWi::where('plant_code', $plantCode);

        if ($request->filled('date')) {
            $dateInput = $request->date;
            if (strpos($dateInput, ' to ') !== false) {
                $dates = explode(' to ', $dateInput);
                if (count($dates) == 2) {
                    $query->whereBetween('document_date', [$dates[0], $dates[1]]);
                } elseif (count($dates) == 1) {
                    $query->whereDate('document_date', $dates[0]);
                }
            } else {
                $query->whereDate('document_date', $dateInput);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('wi_document_code', 'like', "%{$search}%")
                ->orWhere('workcenter_code', 'like', "%{$search}%")
                ->orWhere('payload_data', 'like', "%{$search}%");
            });
        }
        $wiDocuments = $query->orderBy('document_date', 'desc')
                            ->orderBy('document_time', 'desc')
                            ->get();

        // Initialize Collections
        $activeWIDocuments = collect();   // Today
        $inactiveWIDocuments = collect(); // Future
        $expiredWIDocuments = collect();  // Expired
        $completedWIDocuments = collect(); // Completed
        
        $workcenterMappings = WorkcenterMapping::where('plant', $plantCode)->get(); // Assuming plant column is correct or use kode_laravel
        // Fallback or verify column. Previous index used 'kode_laravel' for $kode.
        // Let's use 'kode_laravel' to be safe as in index method.
        if ($workcenterMappings->isEmpty()) {
             $workcenterMappings = WorkcenterMapping::where('kode_laravel', $plantCode)->get();
        }

        $wcNames = [];
        foreach ($workcenterMappings as $m) {
            if ($m->wc_induk) $wcNames[strtoupper($m->wc_induk)] = $m->nama_wc_induk;
            if ($m->workcenter) $wcNames[strtoupper($m->workcenter)] = $m->nama_workcenter;
        }
        
        // Loop and Categorize
        foreach ($wiDocuments as $doc) {
            $now = Carbon::now();
            $expiredAt = $doc->expired_at; 
            
            if ($expiredAt) {
                $expirationTime = Carbon::parse($expiredAt); 
                $doc->is_expired = $now->greaterThan($expirationTime);
            } else {
                try {
                    $effectiveStart = Carbon::parse($doc->document_date . ' ' . $doc->document_time);
                    $expirationTime = $effectiveStart->copy()->addHours(12);
                    $doc->is_expired = $now->greaterThan($expirationTime);
                } catch (\Exception $e) {
                    $doc->is_expired = true;
                }
            }

            $docDate = Carbon::parse($doc->document_date)->startOfDay();
            $today = Carbon::today();

            $doc->is_inactive = $docDate->greaterThan($today) && !$doc->is_expired; // Future & Not Expired
            $doc->is_active = $docDate->equalTo($today) && !$doc->is_expired;      // Today & Not Expired

            $rawData = $doc->payload_data;
            if (is_array($rawData)) {
                $payloadItems = $rawData;
            } elseif (is_string($rawData)) {
                $payloadItems = json_decode($rawData, true);
            } else {
                $payloadItems = [];
            }
            $payloadItems = $payloadItems ?? [];
            $firstItem = $payloadItems[0] ?? [];
            $rawKapaz = str_replace(',', '.', $firstItem['kapaz'] ?? 0);
            $kapazHours = floatval($rawKapaz);
            $maxMins = $kapazHours * 60; 
            $summary = [
                'total_items' => 0,
                'total_load_mins' => 0, 
                'details' => [] 
            ];

            $isFullyCompleted = true;
            if (empty($payloadItems)) $isFullyCompleted = false;

            foreach ($payloadItems as $item) {
                $summary['total_items']++;
                $assignedQty = floatval(str_replace(',', '.', $item['assigned_qty'] ?? 0));
                $confirmedQty = floatval(str_replace(',', '.', $item['confirmed_qty'] ?? 0));
                $qtyOrderRaw = floatval(str_replace(',', '.', $item['qty_order'] ?? $assignedQty));
                $takTime = floatval(str_replace(',', '.', $item['calculated_tak_time'] ?? 0));
                $summary['total_load_mins'] += $takTime;
                $progressPct = $assignedQty > 0 ? ($confirmedQty / $assignedQty) * 100 : 0;

                if ($progressPct >= 100) $statusItem = 'Completed';
                elseif ($confirmedQty > 0) $statusItem = 'On Progress';
                else $statusItem = 'Created';

                $summary['details'][] = [
                    'aufnr'         => $item['aufnr'] ?? '-',
                    'material'      => $item['material_desc'] ?? ($item['material'] ?? '-'),
                    'nik'           => $item['nik'] ?? '-',
                    'name'          => $item['name'] ?? '-',
                    'vornr'         => $item['vornr'] ?? '-',
                    'description'   => $item['material_desc'] ?? '', 
                    'assigned_qty'  => $assignedQty,
                    'confirmed_qty' => $confirmedQty,
                    'qty_order'     => $qtyOrderRaw,
                    'uom'           => $item['uom'] ?? 'EA',
                    'progress_pct'  => $progressPct,
                    'status'        => $statusItem,
                    'item_mins'     => $takTime,
                    'remark'        => $item['remark'] ?? null,
                    'remark_qty'    => $item['remark_qty'] ?? 0
                ];

                // Check for completion status for the document
                
                // Get remark qty
                $rQty = floatval(str_replace(',', '.', $item['remark_qty'] ?? 0));
                
                if ($rQty > 0) {
                     // If remark exists, check total
                     $totalDone = $confirmedQty + $rQty;
                     // Allow small float tolerance if needed, but direct comparison usually ok if logical
                     if ($totalDone < $assignedQty) {
                         $isFullyCompleted = false;
                     }
                } else {
                     // Normal check
                     if ($confirmedQty < $assignedQty) {
                         $isFullyCompleted = false;
                     }
                }
            }

            // Get max capacity for the workcenter
            $firstItem = $payloadItems[0] ?? [];
            $rawKapaz = str_replace(',', '.', $firstItem['kapaz'] ?? 0);
            $maxMins = floatval($rawKapaz) * 60; // Convert hours to minutes

            $percentageLoad = $maxMins > 0 ? ($summary['total_load_mins'] / $maxMins) * 100 : 0;

            $doc->capacity_info = [
                'max_mins'   => $maxMins,
                'used_mins'  => $summary['total_load_mins'],
                'percentage' => $percentageLoad
            ];

            $doc->pro_summary = $summary;

            // Categorization Logic (Priority: Completed -> Expired -> Inactive -> Active)
            if ($isFullyCompleted) {
                $completedWIDocuments->push($doc);
            } elseif ($doc->is_expired) {
                $expiredWIDocuments->push($doc);
            } elseif ($doc->is_inactive) {
                $inactiveWIDocuments->push($doc);
            } else {
                $activeWIDocuments->push($doc);
            }
        }

        return view('create-wi.history', [
            'plantCode' => $plantCode,
            'nama_bagian' => $nama_bagian,
            'activeWIDocuments' => $activeWIDocuments,
            'inactiveWIDocuments' => $inactiveWIDocuments,
            'expiredWIDocuments' => $expiredWIDocuments,
            'completedWIDocuments' => $completedWIDocuments, // PASS TO VIEW
            'wcNames' => $wcNames,
            'search' => $request->search,
            'date' => $request->date
        ]);
    }

    public function updateQty(Request $request)
    {
        $request->validate([
            'wi_code' => 'required|string',
            'aufnr'   => 'required|string',
            'new_qty' => 'required|numeric|min:0',
        ]);

        try {
            // 1. Cari Dokumen
            $doc = HistoryWi::where('wi_document_code', $request->wi_code)->firstOrFail();

            // 2. Decode Payload
            // 2. Decode Payload
            $rawData = $doc->payload_data;
            if (is_string($rawData)) {
                $payload = json_decode($rawData, true);
                if (!is_array($payload)) $payload = [];
            } else {
                $payload = (array) $rawData;
            }

            $updated = false;
            $materialName = '';
            $parseNumber = function($value) {
                if (is_numeric($value)) return floatval($value);
                $string = (string) $value;
                if (strpos($string, '.') !== false && strpos($string, ',') !== false) {
                    if (strrpos($string, ',') > strrpos($string, '.')) {
                        $string = str_replace('.', '', $string); 
                        $string = str_replace(',', '.', $string);
                    } else {
                        $string = str_replace(',', '', $string); 
                    }
                } elseif (strpos($string, ',') !== false) {
                    $string = str_replace(',', '.', $string);
                }
                return floatval($string);
            };
            foreach ($payload as &$item) {
                if ($item['aufnr'] === $request->aufnr) {
                $maxQty = $parseNumber($item['qty_order'] ?? 0);
                $newQty = floatval($request->new_qty);

                if ($maxQty > 0 && $newQty > $maxQty) {
                    return back()->with('error', "Gagal! Quantity ($newQty) melebihi Order ($maxQty).");
                }
                $vgw01 = $parseNumber($item['vgw01'] ?? 0);
                $unit = strtoupper($item['vge01'] ?? '');
                if ($vgw01 == 0) {
                    $oldTakTime = floatval($item['calculated_tak_time'] ?? 0);
                    $oldQty = floatval($item['assigned_qty'] ?? 0);
                    
                    if ($oldQty > 0) {
                        $vgw01 = $oldTakTime / $oldQty; 
                        $unit = 'MIN'; 
                    }
                }

                $totalRaw = $vgw01 * $newQty;
                $newMinutes = 0;

                if ($unit === 'S' || $unit === 'SEC') {
                    $newMinutes = $totalRaw / 60;
                } elseif ($unit === 'H' || $unit === 'HUR') {
                    $newMinutes = $totalRaw * 60;
                } else {
                    $newMinutes = $totalRaw; // Default MIN
                }

                $item['assigned_qty'] = $newQty;
                $item['calculated_tak_time'] = number_format($newMinutes, 2, '.', '');
                $item['vgw01'] = $vgw01; 
                $item['vge01'] = $unit;
                $updated = true;
                $materialName = $item['material_desc'] ?? $item['aufnr'];
                break; 
            }
            }

            if ($updated) {
                $doc->payload_data = $payload; 
                $doc->save();

                return back()->with('success', "Qty $materialName diupdate menjadi $newQty. Kapasitas diperbarui.");
            }

            return back()->with('error', 'Item tidak ditemukan.');

        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    public function previewLog(Request $request, $plantCode)
    {
        $date = $request->input('filter_date');
        $search = $request->input('filter_search');
        $query = HistoryWi::where('plant_code', $plantCode);

        if ($date) {
            if (strpos($date, ' to ') !== false) {
                $dates = explode(' to ', $date);
                if (count($dates) == 2) {
                    $query->whereBetween('document_date', [$dates[0], $dates[1]]);
                } else {
                    $query->whereDate('document_date', $dates[0]);
                }
            } else {
                $query->whereDate('document_date', $date);
            }
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('wi_document_code', 'like', "%$search%") 
                ->orWhere('payload_data', 'like', "%$search%"); 
            });
        }
        // Limit preview to 50 rows to avoid heavy payload
        $rawDocuments = $query->orderBy('created_at', 'desc')->take(50)->get();
        $previewData = $this->_prepareLogData($rawDocuments);

        return response()->json([
            'success' => true,
            'data' => $previewData,
            'count' => count($previewData), // This is partial count
            'total_docs' => $query->count()
        ]);
    }

    // 2. Email Log (Generate CSV & Send)
    public function emailLog(Request $request, $plantCode)
    {
        $date = $request->input('filter_date');
        $search = $request->input('filter_search');
        
        // --- 1. Generate Data ---
        $query = HistoryWi::where('plant_code', $plantCode);
        if ($date) {
            if (strpos($date, ' to ') !== false) {
                $dates = explode(' to ', $date);
                if (count($dates) == 2) {
                    $query->whereBetween('document_date', [$dates[0], $dates[1]]);
                } else {
                    $query->whereDate('document_date', $dates[0]);
                }
            } else {
                $query->whereDate('document_date', $date);
            }
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('wi_document_code', 'like', "%$search%") 
                ->orWhere('payload_data', 'like', "%$search%"); 
            });
        }
        $documents = $query->orderBy('created_at', 'desc')->get();
        $csvData = $this->_prepareLogData($documents);

        if (empty($csvData)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada data untuk diexport.']);
        }

        // --- 2. Create PDF File (DOMPDF) ---
        try {
            $fileName = 'log_wi_' . now()->format('Ymd_His') . '.pdf';
            $filePath = storage_path('app/public/' . $fileName);
            
            $printedBy = $request->input('printed_by') ?? session('username');
            $department = $request->input('department') ?? '-';
            
            // Filter Info String
            $filterInfo = [];
            if($date) $filterInfo[] = "Date: " . $date;
            if($search) $filterInfo[] = "Search: $search";
            $filterString = empty($filterInfo) ? "All Data" : implode(', ', $filterInfo);

            // Calculations for Summary
            $totalAssigned = collect($csvData)->sum('assigned');
            $totalConfirmed = collect($csvData)->sum('confirmed'); 
            $totalFailed = $totalAssigned - $totalConfirmed;
            $achievement = $totalAssigned > 0 ? round(($totalConfirmed / $totalAssigned) * 100) . '%' : '0%';
            
            // Prepare Data for View
            // Prepare Data for View (Single Report wrapped in Array)
            $singleReport = [
                'items' => $csvData,
                'summary' => [
                    'total_assigned' => $totalAssigned,
                    'total_confirmed' => $totalConfirmed,
                    'total_failed' => $totalFailed,
                    'total_remark_qty' => collect($csvData)->sum('remark_qty'),
                    'achievement_rate' => $achievement
                ],
                'printedBy' => $printedBy,
                'department' => $department,
                'printDate' => now()->format('d-M-Y H:i'),
                'filterInfo' => $filterString
            ];
            
            // Generate PDF
            $pdf = Pdf::loadView('pdf.log_history', ['reports' => [$singleReport]])
                    ->setPaper('a4', 'landscape');
            
            $pdf->save($filePath);

            // --- 3. Send Email ---
            $recipients = [
                'finc.smg@pawindo.com',
                'kmi356smg@gmail.com',
                'adm.mkt5.smg@pawindo.com',
                'lily.smg@pawindo.com',
                'kmi3.60.smg@gmail.com',
                'tataamal1128@gmail.com'
            ];
            
            if ($date) {
                if (strpos($date, ' to ') !== false) {
                    $parts = explode(' to ', $date);
                    $start = Carbon::parse($parts[0])->format('d-m-Y');
                    $end = isset($parts[1]) ? Carbon::parse($parts[1])->format('d-m-Y') : '';
                    $dateInfo = "$start s/d $end";
                } else {
                    $dateInfo = Carbon::parse($date)->format('d-m-Y');
                }
            } else {
                $dateInfo = 'All History';
            }

            \Illuminate\Support\Facades\Mail::to($recipients)->send(new \App\Mail\LogHistoryMail($filePath, $dateInfo));
            return response()->json(['success' => true, 'message' => 'Log berhasil diexport (PDF) dan dikirim ke email.']);

        } catch (\Exception $e) {
            Log::error("Email Log Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengirim email: ' . $e->getMessage()], 500);
        }
    }

    // Helper to format data
    private function _prepareLogData($documents)
    {
        $reportData = [];
        $today = Carbon::today();

        foreach ($documents as $doc) {
            if (empty($doc->payload_data) || !is_array($doc->payload_data)) continue;

            $docDate = Carbon::parse($doc->document_date)->startOfDay();
            $expiredAt = Carbon::parse($doc->expired_at);

            foreach ($doc->payload_data as $item) {
                $wc = !empty($item['child_workcenter']) ? $item['child_workcenter'] : ($item['workcenter_induk'] ?? '-');
                $kdauf = $item['kdauf'] ?? '';
                $kdpos = isset($item['kdpos']) ? ltrim($item['kdpos'], '0') : '';
                $soItem = $kdauf . '-' . $kdpos;
                $matnr = $item['material_number'] ?? '';
                if(ctype_digit($matnr)) { $matnr = ltrim($matnr, '0'); }
                $assigned = isset($item['assigned_qty']) ? floatval($item['assigned_qty']) : 0;
                // Remark Data (Moved Up)
                $remarkQty = isset($item['remark_qty']) ? floatval($item['remark_qty']) : 0;
                $remarkText = isset($item['remark']) ? $item['remark'] : '-';
                $remarkText = str_replace('; ', "\n", $remarkText);

                $confirmed = isset($item['confirmed_qty']) ? floatval($item['confirmed_qty']) : 0;
                // Fix Balance
                $balance = $assigned - ($confirmed + $remarkQty);

                $netpr = isset($item['netpr']) ? floatval($item['netpr']) : 0;
                $waerk = isset($item['waerk']) ? $item['waerk'] : '';
                
                // Price Calculation
                $confirmedPrice = $netpr * $confirmed;
                // Failed Price includes Balance AND Remark Qty
                $failedPrice = $netpr * ($balance + $remarkQty);
                
                if (strtoupper($waerk) === 'USD') {
                    $prefixInfo = '$ ';
                } elseif (strtoupper($waerk) === 'IDR') {
                    $prefixInfo = 'Rp ';
                } else {
                    $prefixInfo = '';
                }

                $priceFormatted = $prefixInfo . number_format($confirmedPrice, (strtoupper($waerk) === 'USD' ? 2 : 0), ',', '.');
                $failedPriceFormatted = $prefixInfo . number_format($failedPrice, (strtoupper($waerk) === 'USD' ? 2 : 0), ',', '.');

                $qtyOper = isset($item['qty_order']) ? floatval($item['qty_order']) : 0;

                // Time Calculation
                $baseTime = isset($item['vgw01']) ? floatval($item['vgw01']) : 0;
                $unit = isset($item['vge01']) ? strtoupper($item['vge01']) : '';
                
                $totalTime = $baseTime * $assigned;
                if ($unit == 'S' || $unit == 'SEC') {
                    $finalTime = $totalTime / 60; $finalUnit = 'Menit';
                } else {
                    $finalTime = $totalTime; $finalUnit = $unit;
                }
                $taktDisplay = (fmod($finalTime, 1) !== 0.00) ? number_format($finalTime, 2) : number_format($finalTime, 0);
                $taktFull = $taktDisplay . ' ' . $finalUnit;

                // Status Logic
                // Remark Data
                // Status Logic
                $hasRemark = ($remarkQty > 0 || ($remarkText !== '-' && !empty($remarkText)));
                
                if ($hasRemark) {
                    $status = 'NOT COMPLETED WITH REMARK';
                } elseif ($balance <= 0) {
                    $status = 'COMPLETED';
                } elseif ($docDate->gt($today)) {
                    $status = 'INACTIVE';
                } elseif (now()->gt($expiredAt)) {
                    $status = 'NOT COMPLETED';
                } else {
                    $status = 'ACTIVE';
                }

                $reportData[] = [
                    'doc_no'        => $doc->wi_document_code,
                    'nik'           => $item['nik'] ?? '-',
                    'name'          => $item['name'] ?? '-',
                    'buyer'         => $item['name1'] ?? '-', // Buyer
                    'created_at'    => $doc->created_at,
                    'expired_at'    => $expiredAt->format('Y-m-d H:i'),
                    'status'        => $status,
                    'workcenter'    => $wc,
                    'so_item'       => $soItem,
                    'aufnr'         => $item['aufnr'] ?? '-',
                    'material'      => $matnr,
                    'description'   => $item['material_desc'] ?? '-',
                    
                    'assigned'      => $assigned, 
                    'confirmed'     => $confirmed,
                    'balance'       => $balance,
                    'remark_qty'    => isset($item['remark_qty']) ? floatval($item['remark_qty']) : 0,
                    'remark_text'   => isset($item['remark']) ? $item['remark'] : '-',
                    
                    'price_formatted' => $priceFormatted,
                    'confirmed_price' => $confirmedPrice, // Raw for calc if needed, but view uses formatted
                    'failed_price'    => $failedPrice,
                    'price_ok_fmt'    => $priceFormatted,   // NEW
                    'price_fail_fmt'  => $failedPriceFormatted, // NEW
                    'currency'        => strtoupper($waerk),
                    
                    'qty_op'        => $qtyOper,
                    'qty_wi'        => $assigned,
                    'takt_time'     => $taktFull
                ];
            }
        }
        return $reportData;
    }
    public function printSingleWi(Request $request)
    {
        $request->validate([
            'wi_codes' => 'required',
            'printed_by' => 'required',
            'department' => 'required',
        ]);
        $rawInput = $request->input('wi_codes');
        $wiCodes = explode(',', $rawInput);
        $documents = HistoryWi::whereIn('wi_document_code', $wiCodes)
                    ->where('expired_at', '>', now()) 
                    ->get();

        if ($documents->isEmpty()) {
            return back()->with('error', 'Dokumen tidak ditemukan atau sudah expired.');
        }
        $data = [
            'documents' => $documents, // Kirim Collection dokumen, bukan single doc
            'printedBy' => $request->input('printed_by'),
            'department' => $request->input('department'),
            'printTime' => now(),
        ];
        $pdf = Pdf::loadView('pdf.wi_single_document', $data)
                ->setPaper('a4', 'landscape');

        return $pdf->stream('Work_Instruction_Print.pdf');
    }

    public function printExpiredReport(Request $request)
    {
        $rawInput = $request->input('wi_codes'); // Ganti nama input agar konsisten dg JS baru
        $wiCodes = explode(',', $rawInput);
        $documents = HistoryWi::whereIn('wi_document_code', $wiCodes)->get();
        $reportItems = [];
        $grandTotalAssigned = 0;
        $grandTotalConfirmed = 0;

        foreach ($documents as $doc) {
            if (empty($doc->payload_data) || !is_array($doc->payload_data)) continue;

            foreach ($doc->payload_data as $item) {
                // Data Dasar
                $matnr = isset($item['material_number']) && ctype_digit($item['material_number']) 
                        ? ltrim($item['material_number'], '0') 
                        : ($item['material_number'] ?? '');
                        
                $wc = !empty($item['child_workcenter']) ? $item['child_workcenter'] : ($item['workcenter_induk'] ?? '-');
                $assigned = isset($item['assigned_qty']) ? floatval($item['assigned_qty']) : 0;
                $confirmed = isset($item['confirmed_qty']) ? floatval($item['confirmed_qty']) : 0;
                $remarkQty = isset($item['remark_qty']) ? floatval($item['remark_qty']) : 0;
                $balance = $assigned - ($confirmed + $remarkQty);
                
                $grandTotalAssigned += $assigned;
                $grandTotalConfirmed += $confirmed;
                
                $remarkText = isset($item['remark']) ? $item['remark'] : '-';
                $remarkText = str_replace('; ', "\n", $remarkText);

                $reportItems[] = [
                    'wi_code'     => $doc->wi_document_code,
                    'nik'         => $item['nik'] ?? '-',
                    'name'        => $item['name'] ?? '-',
                    'expired_at'  => $doc->expired_at,
                    'workcenter'  => $wc,
                    'aufnr'       => $item['aufnr'] ?? '-',
                    'material'    => $matnr,
                    'description' => $item['material_desc'] ?? '-',
                    'assigned'    => $assigned,
                    'confirmed'   => $confirmed,
                    'balance'     => $balance,
                    'remark_qty'  => $remarkQty,
                    'remark_text' => $remarkText,
                    'status'      => ($balance <= 0 && $remarkQty == 0) ? 'COMPLETED' : 
                                     (($remarkQty > 0 || ($remarkText !== '-' && !empty($remarkText))) ? 'NOT COMPLETED WITH REMARK' : 'NOT COMPLETED')
                ];
            }
        }

        $summary = [
            'total_assigned' => $grandTotalAssigned,
            'total_confirmed' => $grandTotalConfirmed,
            'total_balance' => $grandTotalAssigned - $grandTotalConfirmed,
            'total_remark_qty' => collect($reportItems)->sum('remark_qty'),
            'achievement_rate' => ($grandTotalAssigned > 0) ? round(($grandTotalConfirmed / $grandTotalAssigned) * 100, 1) : 0
        ];

        $data = [
            'items' => $reportItems,
            'summary' => $summary,
            'printedBy' => $request->input('printed_by'),
            'department' => $request->input('department'),
            'printDate' => now()->format('d-M-Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.wi_expired_report', $data)
                ->setPaper('a4', 'landscape');
        return $pdf->stream('Laporan_Produksi_Expired.pdf');
    }

    public function printCompletedReport(Request $request)
    {
        $rawInput = $request->input('wi_codes'); 
        $wiCodes = explode(',', $rawInput);
        $documents = HistoryWi::whereIn('wi_document_code', $wiCodes)->get();
        $reportItems = [];
        $grandTotalAssigned = 0;
        $grandTotalConfirmed = 0;

        foreach ($documents as $doc) {
            if (empty($doc->payload_data) || !is_array($doc->payload_data)) continue;

            foreach ($doc->payload_data as $item) {
                // Data Dasar
                $matnr = isset($item['material_number']) && ctype_digit($item['material_number']) 
                        ? ltrim($item['material_number'], '0') 
                        : ($item['material_number'] ?? '');
                        
                $wc = !empty($item['child_workcenter']) ? $item['child_workcenter'] : ($item['workcenter_induk'] ?? '-');
                $assigned = isset($item['assigned_qty']) ? floatval($item['assigned_qty']) : 0;
                $confirmed = isset($item['confirmed_qty']) ? floatval($item['confirmed_qty']) : 0;
                $balance = $assigned - $confirmed;
                $grandTotalAssigned += $assigned;
                $grandTotalConfirmed += $confirmed;
                $reportItems[] = [
                    'wi_code'     => $doc->wi_document_code,
                    'nik'         => $item['nik'] ?? '-',
                    'name'        => $item['name'] ?? '-',
                    'expired_at'  => $doc->expired_at,
                    'workcenter'  => $wc,
                    'aufnr'       => $item['aufnr'] ?? '-',
                    'material'    => $matnr,
                    'description' => $item['material_desc'] ?? '-',
                    'assigned'    => $assigned,
                    'confirmed'   => $confirmed,
                    'balance'     => $balance,
                    'remark'      => ($balance > 0) ? 'Not Completed' : 'Completed' 
                ];
            }
        }

        $summary = [
            'total_assigned' => $grandTotalAssigned,
            'total_confirmed' => $grandTotalConfirmed,
            'total_balance' => $grandTotalAssigned - $grandTotalConfirmed,
            'achievement_rate' => ($grandTotalAssigned > 0) ? round(($grandTotalConfirmed / $grandTotalAssigned) * 100, 1) : 0
        ];

        $data = [
            'items' => $reportItems,
            'summary' => $summary,
            'printedBy' => $request->input('printed_by'),
            'department' => $request->input('department'),
            'printDate' => now()->format('d-M-Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.wi_completed_report', $data)
                ->setPaper('a4', 'landscape');
        return $pdf->stream('Laporan_Produksi_Completed.pdf');
    }

    public function streamSchedule(Request $request) 
    {
        $plantCode = $request->input('plant_code');
        $date = $request->input('date'); // YYYY-MM-DD
        $time = $request->input('time'); // HH:MM
        $items = $request->input('items', []); // Array of {aufnr, ...}

        if (!$plantCode || !$date || !$time || empty($items)) {
            return response()->json(['error' => 'Missing required fields'], 400);
        }

        $formattedDate = Carbon::parse($date)->format('Ymd');
        $formattedTime = Carbon::parse($time)->format('H:i:s');
        
        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function() use ($items, $plantCode, $formattedDate, $formattedTime) {
            $manufactController = new ManufactController();
            $total = count($items);
            
            echo "data: " . json_encode(['progress' => 0, 'message' => 'Starting process...']) . "\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            foreach ($items as $index => $item) {
                $aufnr = $item['aufnr'];
                $msgPrefix = "[$aufnr] ";

                try {
                    // 1. Call API Schedule Order
                    $scheduleUrl = env('FLASK_API_URL') . '/api/schedule_order';
                    $schedRes = Http::withHeaders([
                        'X-SAP-Username' => session('username'),
                        'X-SAP-Password' => session('password'),
                    ])->post($scheduleUrl, [
                        'AUFNR' => $aufnr,
                        'DATE' => $formattedDate,
                        'TIME' => $formattedTime
                    ]);

                    if ($schedRes->failed()) {
                        throw new \Exception("Schedule failed: " . $schedRes->body());
                    }

                    // 2. Call API Refresh PRO to get new data
                    $refreshUrl = env('FLASK_API_URL') . '/api/refresh-pro';
                    $refreshRes = Http::withHeaders([
                        'X-SAP-Username' => session('username'),
                        'X-SAP-Password' => session('password'),
                    ])->get($refreshUrl, [
                        'aufnr' => $aufnr,
                        'plant' => $plantCode
                    ]);

                    if ($refreshRes->failed()) {
                         throw new \Exception("Refresh failed: " . $refreshRes->body());
                    }

                    $payload = $refreshRes->json();
                    $results = $payload['results'] ?? $payload;
                    
                    // 3. Sync to DB using ManufactController logic
                    $T1 = $results['T_DATA1'] ?? [];
                    $T3 = $results['T_DATA3'] ?? [];
                    $T4 = $results['T_DATA4'] ?? [];
                    
                    // Use ManufactController logic via public internal method
                    // Note: We need to ensure we are calling it correctly. 
                    // Using transaction here is good practice to ensure consistency per item.
                    DB::transaction(function () use ($manufactController, $aufnr, $plantCode, $T3, $T1, $T4) {
                        $manufactController->syncProInternal($aufnr, $plantCode, $T3, $T1, $T4);
                    });

                    $percent = round((($index + 1) / $total) * 100);
                    echo "data: " . json_encode([
                        'progress' => $percent, 
                        'message' => "$msgPrefix Scheduled & Synced successfully.",
                        'aufnr' => $aufnr,
                        'status' => 'success'
                    ]) . "\n\n";

                } catch (\Exception $e) {
                    $percent = round((($index + 1) / $total) * 100);
                    echo "data: " . json_encode([
                        'progress' => $percent, 
                        'message' => "$msgPrefix Error: " . $e->getMessage(),
                        'aufnr' => $aufnr,
                        'status' => 'error'
                    ]) . "\n\n";
                }
                
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
            
            echo "data: " . json_encode(['progress' => 100, 'message' => 'All items processed.', 'completed' => true]) . "\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Cache-Control', 'no-cache');
        return $response;
    }


}