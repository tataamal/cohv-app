<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\workcenter;
use App\Models\ProductionTData1; 
use App\Models\WorkcenterMapping;
use App\Models\HistoryWi;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\KodeLaravel;
use App\Models\ProductionTData;
use App\Models\ProductionTData2;
use App\Models\ProductionTData3;
use App\Models\ProductionTData4;
use App\Services\Release;
use App\Services\YPPR074Z;
use App\Services\ChangeWc;

class CreateWiController extends Controller
{
    public function delete(Request $request) {
        $ids = $request->input('wi_codes');
        if (!$ids || !is_array($ids)) {
            return response()->json(['message' => 'Invalid data provided.'], 400);
        }
        try {
            DB::beginTransaction();
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
        try {
            $kodeModel = KodeLaravel::where('laravel_code', $kode)->first();
            $sapPlant = $kodeModel ? $kodeModel->plant : $kode;
            $filter = $request->query('filter', 'dspt_rel');
            $apiUrl = 'https://monitoring-kpi.kayumebelsmg.net/api/get-nik-confirmasi';
            $apiToken = env('API_TOKEN_NIK'); 
            $employees = []; 

            try {
                $response = Http::withToken($apiToken)->timeout(5)->post($apiUrl, ['kode_laravel' => $kode]);
                if ($response->successful()) {
                    $employees = $response->json()['data'];
                }
            } catch (\Exception $e) {
                Log::error('Koneksi API NIK Error: ' . $e->getMessage());
            }

            $tData1 = $this->_buildSourceQuery($request, $kode);
            $perPage = 200;
            $page = $request->input('page', 1);
            $tDataQuery = $tData1;
            if (!$tData1) {
                Log::error("tData1 query builder is null in index");
                 return abort(500, "Query builder failed");
            }

            $pagination = $tDataQuery->paginate($perPage); 
            $assignedProQuantities = $this->getAssignedProQuantities($kode);
            
            $processedCollection = $pagination->getCollection()->transform(function ($item) use ($assignedProQuantities) {
                $aufnr = $item->AUFNR;
                $key = $aufnr . '-' . ($item->VORNR ?? '');
                $qtySisaAwal = $item->MGVRG2 - $item->LMNGA;
                $qtyAllocatedInWi = $assignedProQuantities[$key] ?? 0;
                $qtySisaAkhir = $qtySisaAwal - $qtyAllocatedInWi;
                $item->real_sisa_qty = $qtySisaAkhir; 
                $item->qty_wi = $qtyAllocatedInWi; 
                return $item;
            })->filter(function ($item) {
                 return $item->real_sisa_qty > 0.001; 
            });

            $workcenterMappings = WorkcenterMapping::with(['parentWorkcenter', 'childWorkcenter'])
                ->where('kode_laravel_id', $kodeModel->id)
                ->get();

            $wcNames = [];
            foreach ($workcenterMappings as $m) {
                if ($m->parentWorkcenter) $wcNames[strtoupper($m->parentWorkcenter->kode_wc)] = $m->parentWorkcenter->description;
                if ($m->childWorkcenter) $wcNames[strtoupper($m->childWorkcenter->kode_wc)] = $m->childWorkcenter->description;
            }

            $wcDescriptions = workcenter::where('plant', $sapPlant)
                ->get()
                ->mapWithKeys(function ($item) {
                     return [strtoupper($item->kode_wc) => $item->description];
                })
                ->toArray();

            if ($request->ajax()) {
                $html = view('create-wi.partials.source_table_rows', [
                    'tData1' => $processedCollection,
                    'wcNames' => $wcNames, 
                    'wcDescriptions' => $wcDescriptions,
                ])->render();
                return response()->json([
                    'html' => $html,
                    'next_page' => $pagination->hasMorePages() ? $pagination->currentPage() + 1 : null
                ]);
            }
            
            $sectionWcIds = \App\Models\MappingTable::where('kode_laravel_id', $kodeModel->id)
                ->pluck('workcenter_id')
                ->toArray();
            
            $sectionWcs = workcenter::whereIn('id', $sectionWcIds)->get();

            $neededParentIds = $workcenterMappings->filter(function($m) use ($sectionWcIds) {
                return in_array($m->wc_anak_id, $sectionWcIds);
            })->pluck('wc_induk_id')->unique();

            $parentWcs = workcenter::whereIn('id', $neededParentIds)->get();
            $allWorkcenters = $sectionWcs->merge($parentWcs)->unique('id');
            $parentWorkcenters = $this->buildWorkcenterHierarchy($allWorkcenters, $workcenterMappings);
            $childCodes = $workcenterMappings->flatMap(function($m) {
                return $m->childWorkcenter ? [strtoupper($m->childWorkcenter->kode_wc)] : [];
            })->unique()->all();

            $workcenters = $allWorkcenters->reject(function ($wc) use ($childCodes) {
                return in_array(strtoupper($wc->kode_wc), $childCodes);
            });
            $capacityData = $allWorkcenters->map(function ($wc) {
                return [
                    'ARBPL'      => strtoupper($wc->kode_wc),
                    'KAPAZ'      => $wc->operating_time,
                    'START_TIME' => $wc->start_time,
                    'END_TIME'   => $wc->end_time
                ];
            });
            $capacityMap = $capacityData->pluck('KAPAZ', 'ARBPL')->toArray();
            return view('create-wi.index', [
                'kode'                 => $kode,
                'employees'            => $employees,
                'tData1'               => $processedCollection,
                'workcenters'          => $workcenters,
                'parentWorkcenters'    => $parentWorkcenters,
                'capacityMap'          => $capacityMap,
                'wcTimeInfo'           => $capacityData,
                'wcNames'              => $wcNames, 
                'wcDescriptions'       => $wcDescriptions,
                'currentFilter'        => $filter,
                'nextPage'             => $pagination->hasMorePages() ? 2 : null
            ]);
        } catch (\Exception $e) {
            Log::error("Create WI Index Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->view('errors.500', ['exception' => $e], 500);
        }
    }

    protected function getAssignedProQuantities(string $kodePlant)
    {
        $histories = HistoryWi::with('items')
                              ->where('plant_code', $kodePlant)
                              ->where(function ($query) {
                                  $query->where('document_date', '>=', Carbon::today())
                                        ->orWhere('expired_at', '>=', Carbon::now());
                              })
                              ->get();
                              
        $assignedProQuantities = [];

        foreach ($histories as $history) {
            $proItems = $history->items;
            
            $isFullyCompleted = true;
            if ($proItems->isNotEmpty()) {
                foreach ($proItems as $item) {
                    $assignedQty = (float) $item->assigned_qty;
                    $confirmedQty = (float) $item->confirmed_qty;
                    $rQty = (float) $item->remark_qty;
                    
                    if ($rQty > 0) {
                         $totalDone = $confirmedQty + $rQty;
                         // Floating point tolerance
                         if (($assignedQty - $totalDone) > 0.001) {
                             $isFullyCompleted = false;
                             break; 
                         }
                    } else {
                         if (($assignedQty - $confirmedQty) > 0.001) {
                             $isFullyCompleted = false;
                             break;
                         }
                    }
                }
            } else {
                $isFullyCompleted = false;
            }
            
            if ($isFullyCompleted) {
                continue;
            }

            foreach ($proItems as $item) {
                $aufnr = $item->aufnr;
                $vornr = $item->vornr ?? ''; 
                $assignedQty = (float) $item->assigned_qty;
                $remarkQty = (float) $item->remark_qty; 
                $effectiveAssigned = max(0, $assignedQty - $remarkQty);

                if ($aufnr) {
                    $key = $aufnr . '-' . $vornr;
                    $currentTotal = $assignedProQuantities[$key] ?? 0;
                    $assignedProQuantities[$key] = $currentTotal + $effectiveAssigned;
                }
            }
        }

        return $assignedProQuantities;
    }

    public function refreshData(Request $request, $kode)
    {
        set_time_limit(0);
        Log::info("==================================================");
        Log::info("Memulai REFERESH DATA (WI) untuk Plant: {$kode} via Service YPPR074Z");
        Log::info("==================================================");

        try {
            if (!session('username') || !session('password')) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Session SAP username/password tidak ditemukan. Silakan login ulang.'
                ], 401);
            }

            // Gunakan Service YPPR074Z
            $service = new YPPR074Z();
            $summary = $service->refreshAndStore($kode);

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diperbaharui dari SAP.',
                'details' => $summary
            ]);

        } catch (\Exception $e) {
            Log::error('Error saat refresh data SAP:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Gagal memperbaharui data: ' . $e->getMessage()
            ], 500);
        }
    }


    public function releaseAndRefreshPro(Request $request)
    {
        $request->validate([
            'aufnr' => 'required|string',
            'plant' => 'required|string',
        ]);

        $aufnr = $request->input('aufnr');
        $plant = $request->input('plant');

        try {
            // 1. Release Order
            $releaseService = new Release();
            $releaseResult = $releaseService->release($aufnr);
            
            // Note: We might want to check $releaseResult for specific success flags if needed, 
            // but the service throws exception on failure.

            // 2. Refresh PRO
            $ypprService = new YPPR074Z();
            $refreshResult = $ypprService->refreshPro($plant, $aufnr);

            return response()->json([
                'success' => true,
                'message' => "Order $aufnr successfully released and refreshed.",
                'data' => $refreshResult
            ]);

        } catch (\Exception $e) {
            Log::error("Release and Refresh Error for AUFNR $aufnr: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function fetchProStatus(Request $request)
    {
        $kode = $request->input('plant_code'); // Ensure plant code if needed for scope
        $aufnrs = $request->input('aufnrs', []);

        if (empty($aufnrs)) {
            return response()->json([]);
        }

        try {
            $query = ProductionTData1::whereIn('AUFNR', $aufnrs);
            if($kode) {
                $query->where('WERKSX', $kode);
            }
            
            $results = $query->select('AUFNR', 'VORNR', 'STATS')->get();
            
            return response()->json($results);
        } catch (\Exception $e) {
            Log::error("Fetch PRO Status Error: " . $e->getMessage());
             return response()->json(['error' => $e->getMessage()], 500);
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
            if (!$mapping->parentWorkcenter || !$mapping->childWorkcenter) continue;

            $parentCode = strtoupper($mapping->parentWorkcenter->kode_wc);
            $childCode = $mapping->childWorkcenter->kode_wc;
            $childName = $mapping->childWorkcenter->description;

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
                // Determine capacity from primaryWCs or from child relation
                $childWcObj = $primaryWCs->firstWhere('kode_wc', $childCode);
                
                // [UPDATED] Use operating_time as requested (User: operating_time satuanya jam)
                $childKapaz = $childWcObj ? $childWcObj->operating_time : ($mapping->childWorkcenter->operating_time ?? 0);

                $parentHierarchy[$parentCode][] = [
                    'code' => $childCode,
                    'name' => $childName,
                    'kapaz' => $childKapaz,
                ];
            }
        }

        $parentHierarchy = array_filter($parentHierarchy, function($children) {
            return count($children) > 0;
        });

        return $parentHierarchy;
    }

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
            $expiredAt = $dateTime->copy()->addDay()->startOfDay();
        } else {
            $expiredAt = $dateTime->copy()->addHours(24);
        }
        $dateForDb = $dateTime->toDateString();
        $timeForDb = $dateTime->toTimeString();
        $year = $dateTime->year;
        
        $wiDocuments = [];

        try {
            foreach ($payload as $wcAllocation) {
                $workcenterCode = $wcAllocation['workcenter'];
                $rawItems = $wcAllocation['pro_items'] ?? [];
                Log::info("Raw Items Received: " . count($rawItems), ['items' => $rawItems]);
                $mergedMap = [];
                
                foreach ($rawItems as $item) {
                     $uAufnr = trim($item['aufnr'] ?? '');
                     $uVornr = trim($item['vornr'] ?? '');
                     $uNik = trim($item['nik'] ?? '');
                     $uWc = trim($item['child_workcenter'] ?? '');
                     
                     $key = "{$uAufnr}_{$uVornr}_{$uNik}_{$uWc}";
                     
                     Log::info("Merge Check: Key = $key. Existing? " . (isset($mergedMap[$key]) ? "YES" : "NO"));
                     
                     if (isset($mergedMap[$key])) {
                         $mergedMap[$key]['assigned_qty'] = floatval($mergedMap[$key]['assigned_qty']) + floatval($item['assigned_qty']);
                         
                         $baseTime = floatval($mergedMap[$key]['vgw01'] ?? 0);
                         $newQty = $mergedMap[$key]['assigned_qty'];
                         $unit = strtoupper($mergedMap[$key]['vge01'] ?? '');
                         
                         $totalRaw = $baseTime * $newQty;
                         $mins = $totalRaw;
                         
                         if ($unit === 'S' || $unit === 'SEC') $mins = $totalRaw / 60;
                         elseif ($unit === 'H' || $unit === 'HUR') $mins = $totalRaw * 60;
                         
                        $mergedMap[$key]['calculated_tak_time'] = number_format($mins, 2, '.', '');

                        if (!empty($item['is_machining'])) $mergedMap[$key]['is_machining'] = 1;
                        if (!empty($item['is_longshift'])) $mergedMap[$key]['is_longshift'] = 1;
                        
                        Log::info("Merged Item. New Qty: {$newQty}");
                         $mergedMap[$key] = $item;
                     }
                }
                
                $wcAllocation['pro_items'] = array_values($mergedMap); 
                Log::info("Final Items Count: " . count($wcAllocation['pro_items']));

                $headerMachining = false;
                $headerLongshift = false;
                foreach ($wcAllocation['pro_items'] as $itm) {
                    if (!empty($itm['is_machining'])) $headerMachining = true;
                    if (!empty($itm['is_longshift'])) $headerLongshift = true;
                }
                if (!empty($wcAllocation['is_machining'])) $headerMachining = true;
                if (!empty($wcAllocation['is_longshift'])) $headerLongshift = true;

                if ($headerMachining) {
                    $minSsavd = null;
                    $maxSssld = null;
                    
                    foreach ($wcAllocation['pro_items'] as $itm) {
                        $s = ($itm['ssavd'] ?? '-') !== '-' ? $itm['ssavd'] : null;
                        $e = ($itm['sssld'] ?? '-') !== '-' ? $itm['sssld'] : null;
                        
                        if ($s) {
                            $ts = strtotime($s);
                            if ($minSsavd === null || $ts < $minSsavd) $minSsavd = $ts;
                        }
                        if ($e) {
                            $te = strtotime($e);
                            if ($maxSssld === null || $te > $maxSssld) $maxSssld = $te;
                        }
                    }
                    
                    if ($minSsavd) {
                        $dt = Carbon::createFromTimestamp($minSsavd);
                        $dateForDb = $dt->toDateString();
                    }
                    
                    if ($maxSssld) {
                        $expiredAt = Carbon::createFromTimestamp($maxSssld);
                    } else {

                    }
                    
                } else {
                }

                DB::transaction(function () use ($docPrefix, $workcenterCode, $plantCode, $dateForDb, $timeForDb, $year, $expiredAt, $wcAllocation, $isMachining, $isLongshift, &$wiDocuments) {
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
                    $history = HistoryWi::create([
                        'wi_document_code' => $documentCode,
                        'workcenter_induk' => $workcenterCode,
                        'plant_code' => $plantCode,
                        'document_date' => $dateForDb,
                        'document_time' => $timeForDb,       
                        'expired_at' => $expiredAt,
                        'sequence_number' => $nextNumber, 
                        'status' => 'Open',
                        'machining' => $headerMachining,
                        'longshift' => $headerLongshift,
                    ]);
                    foreach ($wcAllocation['pro_items'] as $itemData) {
                        
                        $nik = $itemData['nik'] ?? null;
                        $aufnr = $itemData['aufnr'] ?? null;
                        $vornr = $itemData['vornr'] ?? null;
                        
                        $exists = \App\Models\HistoryWiItem::where('aufnr', $aufnr)
                                    ->where('vornr', $vornr)
                                    ->where('nik', $nik)
                                    ->exists();
                                    
                        if (!$exists) {
                            \App\Models\HistoryWiItem::create([
                                'history_wi_id' => $history->id,
                                'nik' => $nik,
                                'aufnr' => $aufnr,
                                'vornr' => $vornr,
                                'uom' => $itemData['uom'] ?? null,
                                'operator_name' => $itemData['name'] ?? null, 
                                'dispo' => $itemData['dispo'] ?? null,
                                'kapaz' => $itemData['kapaz'] ?? null,
                                'kdauf' => $itemData['kdauf'] ?? null,
                                'kdpos' => $itemData['kdpos'] ?? null,
                                'name1' => $itemData['name1'] ?? null,
                                'netpr' => $itemData['netpr'] ?? 0,
                                'waerk' => $itemData['waerk'] ?? null,
                                'ssavd' => ($itemData['ssavd'] ?? '-') !== '-' ? $itemData['ssavd'] : null,
                                'sssld' => ($itemData['sssld'] ?? '-') !== '-' ? $itemData['sssld'] : null,
                                'steus' => $itemData['steus'] ?? null,
                                'vge01' => $itemData['vge01'] ?? null,
                                'vgw01' => $itemData['vgw01'] ?? 0,
                                'material_number' => $itemData['material_number'] ?? null,
                                'material_desc' => $itemData['material_desc'] ?? null,
                                'qty_order' => $itemData['qty_order'] ?? 0,
                                'assigned_qty' => $itemData['assigned_qty'] ?? 0,
                                'confirmed_qty' => 0,
                                'workcenter_induk' => $workcenterCode,
                                'child_workcenter' => $itemData['child_workcenter'] ?? null,
                                'status_item' => 'Open',
                                'calculated_takt_time' => $itemData['calculated_tak_time'] ?? 0,
                                'calculated_takt_time' => $itemData['calculated_tak_time'] ?? 0,
                                'machining' => !empty($itemData['is_machining']),
                                'longshift' => !empty($itemData['is_longshift']),
                                'remark_text' => $itemData['remark'] ?? null,
                                'remark_qty' => $itemData['remark_qty'] ?? 0,
                            ]);
                        } else {
                            Log::warning("Skipping duplicate HistoryWiItem: $aufnr - $vornr - $nik");
                        }
                    }

                    
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
        $nama_bagian  = KodeLaravel::where('laravel_code', $plantCode)->first();
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
        } else {
            // DEFAULT: Recent History (Last 7 Days) or Future
            $query->where('document_date', '>=', Carbon::today()->subDays(7));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('wi_document_code', 'like', "%{$search}%")
                ->orWhere('workcenter_induk', 'like', "%{$search}%")
                ->orWhereHas('items', function($q2) use ($search) {
                     $q2->where('aufnr', 'like', "%{$search}%")
                        ->orWhere('item_json', 'like', "%{$search}%")
                        ->orWhere('material_desc', 'like', "%{$search}%");
                });
            });
        }

        // Add Specific Workcenter Filter
        if ($request->filled('workcenter') && $request->workcenter !== 'all') {
            $query->where('workcenter_induk', $request->workcenter);
        }
        $wiDocuments = $query->with('items')
                            ->orderBy('document_date', 'desc')
                            ->orderBy('document_time', 'desc')
                            ->get();

        $activeWIDocuments = collect();   
        $inactiveWIDocuments = collect(); 
        $expiredWIDocuments = collect();  
        $completedWIDocuments = collect(); 
        
        $workcenterMappings = WorkcenterMapping::with(['parentWorkcenter', 'childWorkcenter'])
            ->where('kode_laravel_id', $nama_bagian ? $nama_bagian->id : null)
            ->get();

        $wcNames = [];

        foreach ($workcenterMappings as $m) {
            if ($m->parentWorkcenter) $wcNames[strtoupper($m->parentWorkcenter->kode_wc)] = $m->parentWorkcenter->description;
            if ($m->childWorkcenter) $wcNames[strtoupper($m->childWorkcenter->kode_wc)] = $m->childWorkcenter->description;
        }

        $childWorkcenters = workcenter::where('plant', $plantCode)
                            ->get()
                            ->mapWithKeys(function ($item) {
                                return [strtoupper($item->kode_wc) => $item];
                            });
        
        $allWcCodes = $workcenterMappings->flatMap(function($m) {
            $codes = [];
            if ($m->parentWorkcenter) $codes[] = $m->parentWorkcenter->kode_wc;
            if ($m->childWorkcenter) $codes[] = $m->childWorkcenter->kode_wc;
            return $codes;
        })->filter()->unique()->map(function($code) { return strtoupper($code); });
        
        // --- CAPACITY SOURCING FROM WORKCENTERS TABLE ---
        $rawWcData = workcenter::where('plant', $plantCode)
            ->get();
            
        // 1. Build Base Capacity Map (Single WCs)
        $wcCapacityMap = [];
        foreach ($rawWcData as $rw) {
            $code = strtoupper($rw->kode_wc);
            $kapazHours = floatval(str_replace(',', '.', $rw->kapaz ?? 0));
            $wcCapacityMap[$code] = $kapazHours * 60; // Convert to Minutes
        }

        // 2. Resolve Parent Capacities (Sum of Children)
        $workcenters = collect();
        foreach ($allWcCodes as $wcCode) {
            $isParent = false;
            // Check if this WC code appears as 'wc_induk' for OTHERS
            $children = $workcenterMappings->filter(function($m) use ($wcCode) {
                 return $m->parentWorkcenter && strtoupper($m->parentWorkcenter->kode_wc) === $wcCode && 
                        $m->childWorkcenter && strtoupper($m->childWorkcenter->kode_wc) !== $wcCode;
            });

            if ($children->isNotEmpty()) {
                // It is a Parent
                $totalCap = 0;
                foreach ($children as $child) {
                    $cCode = strtoupper($child->childWorkcenter->kode_wc);
                    $totalCap += ($wcCapacityMap[$cCode] ?? 0);
                }
                $finalCap = $totalCap;
            } else {
                // It is a Single/Child WC
                $finalCap = $wcCapacityMap[$wcCode] ?? 0;
            }

            $workcenters->push((object) [
                 'workcenter_code' => $wcCode,
                 'kapaz' => $finalCap,
                 'raw_unit' => 'MIN'
            ]);
            
            if ($finalCap == 0 && $children->isNotEmpty()) {
                Log::info("DEBUG CAPACITY WC ZERO: {$wcCode}. Children Count: " . $children->count());
                foreach($children as $child) {
                     $cCode = strtoupper($child->workcenter);
                     $cap = $wcCapacityMap[$cCode] ?? 'MISSING';
                     Log::info(" - Child {$cCode}: {$cap}");
                }
            }
        }
        
        $concurrentUsageMap = [];
        $today = Carbon::today();
        $now = Carbon::now();

        foreach ($wiDocuments as $doc) {
            try {
                $chkDate = Carbon::parse($doc->document_date)->startOfDay();
                // $expiredAt = $doc->expired_at; // Column removed
                
                $isExpired = false;
                // Expiration logic fallback (Since expired_at is missing)
                // Assume expired if older than X or based on document_time + 12-24h
                
                $effStart = Carbon::parse($doc->document_date . ' ' . $doc->document_time);
                // Prefix check for duration logic (WIH=24h, WIW=12h default?)
                $isWIH = str_starts_with($doc->wi_document_code, 'WIH');
                $hoursToAdd = $isWIH ? 24 : 24; // Default to 24h safe? Old code had 12/24 logic in save.
                
                $expirationTime = $effStart->copy()->addHours($hoursToAdd);
                $isExpired = $now->greaterThan($expirationTime);
                
                $doc->is_expired = $isExpired;

                if (!$isExpired && $chkDate->greaterThanOrEqualTo($today)) {
                     $plItems = $doc->items;
                     
                     if ($plItems->isNotEmpty()) {
                         foreach ($plItems as $plItem) {
                             $k = ($plItem->aufnr ?? '-') . '_' . ($plItem->vornr ?? '-');
                             if (!isset($concurrentUsageMap[$k])) $concurrentUsageMap[$k] = 0;
                             
                             $q = (float) $plItem->assigned_qty;
                             $concurrentUsageMap[$k] += $q;
                         }
                     }
                }
            } catch (\Exception $e) { /* Ignore parsing errors */ }
        }

        // --- BATCH FETCHING PRODUCTION DATA ---
        $allAufnrs = [];
        foreach ($wiDocuments as $doc) {
            $raw = $doc->payload_data;
            if(empty($raw)) continue;
            $items = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);
            if(is_array($items)) {
                foreach($items as $i) {
                     if(!empty($i['aufnr'])) $allAufnrs[] = $i['aufnr'];
                }
            }
        }
        $allAufnrs = array_unique($allAufnrs);
        
        $prodDataMap1 = [];
        $prodDataMap3 = [];

        if (!empty($allAufnrs)) {
            $prodDataMap1 = ProductionTData1::whereIn('AUFNR', $allAufnrs)->get()->keyBy('AUFNR');
            $prodDataMap3 = ProductionTData3::whereIn('AUFNR', $allAufnrs)->get()->keyBy('AUFNR');
        }

        foreach ($wiDocuments as $doc) {
            $docDate = Carbon::parse($doc->document_date)->startOfDay();
            $today = Carbon::today();

            $doc->is_inactive = $docDate->greaterThan($today) && !$doc->is_expired; 
            $doc->is_active = $docDate->equalTo($today) && !$doc->is_expired;       

            $payloadItems = [];
            // [REFACTOR] Use Items Relationship if available (New Data)
            if ($doc->items && $doc->items->count() > 0) {
                 $payloadItems = $doc->items->toArray();
            } else {
                // Fallback to JSON Payload (Old Data)
                $rawData = $doc->payload_data;
                if (is_array($rawData)) {
                    $payloadItems = $rawData;
                } elseif (is_string($rawData)) {
                    $payloadItems = json_decode($rawData, true);
                }
            }
            $payloadItems = $payloadItems ?? [];
            $firstItem = $payloadItems[0] ?? [];
            $rawKapaz = str_replace(',', '.', $firstItem['kapaz'] ?? ($firstItem['kapaz'] ?? 0));
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
                
                // Optimized: Use Map
                // $prodData = ProductionTData1::where('AUFNR', $item['aufnr'] ?? '')->first();
                $auf = $item['aufnr'] ?? '';
                $prodData = $prodDataMap1[$auf] ?? null;
                
                if (!$prodData) {
                    // $prodData = ProductionTData3::where('AUFNR', $item['aufnr'] ?? '')->first();
                    $prodData = $prodDataMap3[$auf] ?? null;
                }

                // Calculate Base Remaining: MGVRG2 (Total) - LMNGA (Confirmed)
                $sapTotal = $prodData ? floatval($prodData->MGVRG2) : (isset($item['qty_order']) ? floatval(str_replace(',', '.', $item['qty_order'])) : $assignedQty);
                $sapConfirmed = $prodData ? floatval($prodData->LMNGA) : 0;
                
                $fullOrderQty = max(0, $sapTotal - $sapConfirmed);
                
                $key = ($item['aufnr'] ?? '-') . '_' . ($item['vornr'] ?? '-');
                $totalConcurrentUsage = $concurrentUsageMap[$key] ?? 0;
                
                $chkDate = Carbon::parse($doc->document_date)->startOfDay();
                $isUsedInMap = !$doc->is_expired && $chkDate->greaterThanOrEqualTo($today);
                
                if ($isUsedInMap) {
                    $usageByOthers = max(0, $totalConcurrentUsage - $assignedQty);
                } else {
                    $usageByOthers = $totalConcurrentUsage;
                }
                
                $effectiveMax = max(0, $fullOrderQty - $usageByOthers);
                
                $qtyOrderRaw = $effectiveMax;

                $takTime = floatval(str_replace(',', '.', $item['calculated_tak_time'] ?? 0));
                $summary['total_load_mins'] += $takTime;
                $progressPct = $assignedQty > 0 ? ($confirmedQty / $assignedQty) * 100 : 0;

                if ($progressPct >= 100) $statusItem = 'Completed';
                elseif ($confirmedQty > 0) $statusItem = 'On Progress';
                else $statusItem = 'Created';

                // Fetch KDAUF & KDPOS
                $t3Data = $prodDataMap3[$auf] ?? null;
                $kdaufRaw = $t3Data->KDAUF ?? ($item['kdauf'] ?? '-');
                $kdposRaw = $t3Data->KDPOS ?? ($item['kdpos'] ?? '-');
                
                // Format KDPOS: Remove leading zeros
                $kdposDisplay = ($kdposRaw !== '-' && $kdposRaw !== '') ? ltrim($kdposRaw, '0') : $kdposRaw;

                $summary['details'][] = [
                    'aufnr'         => $item['aufnr'] ?? '-',
                    'kdauf'         => $kdaufRaw,
                    'kdpos'         => $kdposDisplay,
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
                    'item_mins'     => $takTime,
                    'remark'        => $item['remark'] ?? ($item['remark_text'] ?? null),
                    'remark_qty'    => $item['remark_qty'] ?? 0,
                    'vgw01'         => $item['vgw01'] ?? 0,
                    'vge01'         => $item['vge01'] ?? ''
                ];

                
                $rQty = floatval(str_replace(',', '.', $item['remark_qty'] ?? 0));
                
                if ($rQty > 0) {
                     $totalDone = $confirmedQty + $rQty;
                     if ($totalDone < $assignedQty) {
                         $isFullyCompleted = false;
                     }
                } else {
                     if ($confirmedQty < $assignedQty) {
                         $isFullyCompleted = false;
                     }
                }
            }
            // --- RESOLVE MAX CAPACITY FROM WORKCENTERS TABLE ---
            $targetWcCode = strtoupper($doc->workcenter_code);
            $maxMins = 0;

            // Check if Parent (Has children in mapping)
             $childrenOfThisWc = $workcenterMappings->filter(function($m) use ($targetWcCode) {
                 return strtoupper($m->wc_induk) === $targetWcCode && 
                        strtoupper($m->workcenter) !== $targetWcCode;
             });

            if ($childrenOfThisWc->count() > 0) {
                // It is a Parent - Sum Children
                foreach ($childrenOfThisWc as $child) {
                     $cCode = strtoupper($child->workcenter);
                     $cap = $wcCapacityMap[$cCode] ?? 0;
                     if ($cap == 0) $cap = 570; // Fallback Default
                     $maxMins += $cap;
                }
            } else {
                 // Single - Check self
                 $maxMins = $wcCapacityMap[$targetWcCode] ?? 0;
                 if ($maxMins == 0) $maxMins = 570; // Fallback Default
            }

            $percentageLoad = $maxMins > 0 ? ($summary['total_load_mins'] / $maxMins) * 100 : 0;

            $doc->capacity_info = [
                'max_mins'   => $maxMins,
                'used_mins'  => $summary['total_load_mins'],
                'percentage' => $percentageLoad
            ];

            // Sort details by NIK ascending
            usort($summary['details'], function($a, $b) {
                // Ensure we compare strings or numbers correctly
                // Use 'nik' key
                $nikA = $a['nik'] ?? '';
                $nikB = $b['nik'] ?? '';
                
                // Natural string comparison is usually best for NIKs
                return strnatcmp($nikA, $nikB);
            });

            $doc->pro_summary = $summary;

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

        $wiCapacityMap = [];
        foreach ($activeWIDocuments as $doc) {
            $wiCapacityMap[$doc->document_code] = $doc->capacity_info ?? ['max_mins' => 0, 'used_mins' => 0];
        }

        // --- AGGREGATE WORKCENTER CAPACITIES FOR PROGRESS BAR ---
        $aggregatedCapacities = [];
        foreach ($activeWIDocuments as $doc) {
            $wcCode = $doc->workcenter_code;
            if (!isset($aggregatedCapacities[$wcCode])) {
                $aggregatedCapacities[$wcCode] = [
                    'code' => $wcCode,
                    'name' => $wcNames[strtoupper($wcCode)] ?? $wcCode,
                    'max_mins' => $doc->capacity_info['max_mins'] ?? 0, 
                    'used_mins' => 0
                ];
            }
            $aggregatedCapacities[$wcCode]['used_mins'] += $doc->capacity_info['used_mins'] ?? 0;
        }

        // Finalize percentages
        foreach ($aggregatedCapacities as &$cap) {
            if ($cap['max_mins'] > 0) {
                $cap['percentage'] = ($cap['used_mins'] / $cap['max_mins']) * 100;
            } else {
                $cap['percentage'] = 0;
            }
        }
        unset($cap);

        return view('create-wi.history', [
            'plantCode' => $plantCode,
            'nama_bagian' => $nama_bagian,
            'activeWIDocuments' => $activeWIDocuments,
            'inactiveWIDocuments' => $inactiveWIDocuments,
            'expiredWIDocuments' => $expiredWIDocuments,
            'completedWIDocuments' => $completedWIDocuments, 
            'wcNames' => $wcNames,
            'workcenters' => $workcenters, 
            'refWorkcenters' => $childWorkcenters, 
            'workcenterMappings' => $workcenterMappings, 
            'wiCapacityMap' => $wiCapacityMap, 
            'activeWorkcenterCapacities' => $aggregatedCapacities,
            'employees' => [], // Empty initially, fetched via AJAX
            'search' => $request->search,
            'date' => $request->date,
            'defaultRecipients' => [
                'finc.smg@pawindo.com',
                'kmi356smg@gmail.com',
                'adm.mkt5.smg@pawindo.com',
                'lily.smg@pawindo.com',
                'kmi3.60.smg@gmail.com',
                'kmi3.31.smg@gmail.com',
                'kmi3.16.smg@gmail.com',
                'kmi3.29.smg@gmail.com',
                'tataamal1128@gmail.com',
            ] 
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
            $doc = HistoryWi::where('wi_document_code', $request->wi_code)->firstOrFail();
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

            $targetAufnr = $request->aufnr;
            
            // 1. Fetch Production Data for Real Limits
            $prodData = ProductionTData1::where('AUFNR', $targetAufnr)->first();
            if (!$prodData) {
                $prodData = ProductionTData3::where('AUFNR', $targetAufnr)->first();
            }
            
            $sapTotal = $prodData ? floatval($prodData->MGVRG2) : 0;
            $sapConfirmed = $prodData ? floatval($prodData->LMNGA) : 0;
            
            $dbMaxQty = $prodData ? max(0, $sapTotal - $sapConfirmed) : 0;

            $plantCode = $doc->plant_code;
            $today = Carbon::today();
            $now = Carbon::now();

            $relatedDocs = HistoryWi::where('plant_code', $plantCode)
                ->where('id', '!=', $doc->id) 
                ->get();
            
            $otherUsage = 0;
            
            foreach ($relatedDocs as $rDoc) {
                try {
                    $chkDate = Carbon::parse($rDoc->document_date)->startOfDay();
                    
                    // Determine Expiration
                    $isExpired = false;
                    if ($rDoc->expired_at) {
                         $isExpired = $now->greaterThan(Carbon::parse($rDoc->expired_at));
                    } else {
                         // Default 12h logic if field null
                         $effStart = Carbon::parse($rDoc->document_date . ' ' . $rDoc->document_time);
                         $isExpired = $now->greaterThan($effStart->addHours(12));
                    }

                    if (!$isExpired && $chkDate->greaterThanOrEqualTo($today)) {
                        $rawPl = $rDoc->payload_data;
                        $plItems = is_string($rawPl) ? json_decode($rawPl, true) : (is_array($rawPl) ? $rawPl : []);
                        
                        if (is_array($plItems)) {
                            foreach ($plItems as $plItem) {
                            }
                        }
                    }
                } catch (\Exception $e) {}
            }
            // --- End Setup ---
            $targetNik = $request->nik;
            $targetVornr = $request->vornr;
            
            foreach ($payload as &$item) {
                // strict comparison for all 3 keys
                $itemAufnr = $item['aufnr'] ?? '';
                $itemNik = $item['nik'] ?? '';
                $itemVornr = $item['vornr'] ?? '';

                if ($itemAufnr === $request->aufnr && 
                    $itemNik === $targetNik && 
                    $itemVornr === $targetVornr
                ) {
                    

                    // Capacity Check & Max Qty Check REMOVED (User Request 2026-01-03)
                    $newQty = floatval($request->new_qty);

                    $vgw01 = $parseNumber($item['vgw01'] ?? 0);
                    $unit = strtoupper($item['vge01'] ?? '');
                    
                    Log::info("Update Qty Calc: AUFNR: $request->aufnr, VGW01: $vgw01, Unit: $unit");
    
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
                    } else {
                        $newMinutes = $totalRaw; 
                    }
    
                    $item['assigned_qty'] = $newQty;
                    $item['calculated_tak_time'] = number_format($newMinutes, 2, '.', '');
                    $item['vgw01'] = $vgw01; 
                    $item['vge01'] = $unit;
                    $updated = true;
                    $materialName = $item['material_desc'] ?? $item['aufnr'];
                    
                    Log::info("Update Qty Success: $materialName set to $newQty (Total: $newMinutes min)");
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


    public function emailLog(Request $request, $plantCode)
    {
        try {
            $data = $this->_generateReportData($request, $plantCode);

            if (!$data['success']) {
                return response()->json(['success' => false, 'message' => $data['message']]);
            }

            $fileName = 'log_wi_' . now()->format('Ymd_His') . '.pdf';
            $filePath = storage_path('app/public/' . $fileName);
            
            $pdf = Pdf::loadView('pdf.log_history', ['reports' => $data['reports'], 'isEmail' => true])
                    ->setPaper('a4', 'landscape');
            $pdf->save($filePath);
            
            $filesToAttach = [$filePath];

            $activeAttachment = $this->_generateActiveAttachment($request, $plantCode, $data['printedBy'], $data['department']);
            if($activeAttachment) {
                $filesToAttach[] = $activeAttachment;
            }
            $recipientsRaw = $request->input('recipients'); 
            if(is_string($recipientsRaw)) {
                $recipients = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $recipientsRaw)));
            } else {
                $recipients = $recipientsRaw;
            }

            if (empty($recipients)) {
                 return response()->json(['success' => false, 'message' => 'Tidak ada penerima email yang dipilih.']);
            }

            $dateInfo = $data['filterInfoString'] ?? '-';
            
            \Illuminate\Support\Facades\Mail::to($recipients)->send(new \App\Mail\LogHistoryMail($filesToAttach, $dateInfo));
            return response()->json(['success' => true, 'message' => 'Log berhasil diexport dan dikirim ke ' . count($recipients) . ' email.']);

        } catch (\Exception $e) {
            Log::error("Email Log Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengirim email: ' . $e->getMessage()], 500);
        }
    }

    public function previewLog(Request $request, $plantCode)
    {
        $data = $this->_generateReportData($request, $plantCode);
        
        if (!$data['success']) {
            return response($data['message'], 404);
        }

        $pdf = Pdf::loadView('pdf.log_history', ['reports' => $data['reports'], 'isEmail' => true])
                ->setPaper('a4', 'landscape');
        
        return $pdf->stream('preview_log.pdf');
    }

    private function _generateReportData(Request $request, $plantCode) {
        $date = $request->input('filter_date');
        $search = $request->input('filter_search');
        
        $query = HistoryWi::where('plant_code', $plantCode);
        
        $dateInfo = 'All History';
        if ($date) {
            if (strpos($date, ' to ') !== false) {
                $dates = explode(' to ', $date);
                if (count($dates) == 2) {
                    $query->whereBetween('document_date', [$dates[0], $dates[1]]);
                    
                    $start = Carbon::parse($dates[0])->format('d-m-Y');
                    $end = Carbon::parse($dates[1])->format('d-m-Y');
                    $dateInfo = "$start s/d $end";
                } else {
                    $query->whereDate('document_date', $dates[0]);
                    $dateInfo = Carbon::parse($dates[0])->format('d-m-Y');
                }
            } else {
                $query->whereDate('document_date', $date);
                $dateInfo = Carbon::parse($date)->format('d-m-Y');
            }
        }
        if ($search) {
             $query->where(function($q) use ($search) {
                $q->where('wi_document_code', 'like', "%$search%") 
                ->orWhere('payload_data', 'like', "%$search%"); 
            });
        }
        $documents = $query->orderBy('created_at', 'desc')->get();

        $filterInfo = [];
        if($date) $filterInfo[] = "Date: " . $date;
        if($search) $filterInfo[] = "Search: $search";
        $filterString = empty($filterInfo) ? "All Data" : implode(', ', $filterInfo);

        return $this->_processDocumentsToReport($documents, $request, $plantCode, $dateInfo ?? $filterString, false, 'nik');
    }

    public function printLogByNik(Request $request, $plantCode)
    {
        $wiCodes = $request->input('wi_codes');
        if (empty($wiCodes)) {
            return back()->with('error', 'Tidak ada dokumen yang dipilih.');
        }

        $codesArray = explode(',', $wiCodes);
        $documents = HistoryWi::where('plant_code', $plantCode)
            ->whereIn('wi_document_code', $codesArray)
            ->get();

        if ($documents->isEmpty()) {
            return back()->with('error', 'Dokumen tidak ditemukan.');
        }

        // Use "Selected Documents" as filter info
        $filterString = "Selected " . $documents->count() . " Documents";
        
        $data = $this->_processDocumentsToReport($documents, $request, $plantCode, $filterString, false, 'nik');

        if (!$data['success']) {
             return back()->with('error', $data['message']);
        }

        $pdf = Pdf::loadView('pdf.log_history', ['reports' => $data['reports'], 'isEmail' => false])
                ->setPaper('a4', 'landscape');
        
        return $pdf->stream('log_monitor_' . now()->format('Ymd_His') . '.pdf');
    }

    private function _processDocumentsToReport($documents, Request $request, $plantCode, $filterString, $groupByDoc = false, $sortMode = 'default') {
        $printedBy = $request->input('printed_by') ?? session('username');
        $department = $request->input('department') ?? '-';

        // Fetch Nama Bagian Once
        $kodeModel = Kode::where('kode', $plantCode)->first();
        $namaBagian = $kodeModel ? $kodeModel->nama_bagian : '-';

        // Fetch Workcenter Descriptions (Check both werks and werksx)
        $wcDescriptions = \App\Models\workcenter::where('werks', $plantCode)
            ->orWhere('werksx', $plantCode)
            ->pluck('description', 'kode_wc')
            ->mapWithKeys(fn($item, $key) => [strtoupper($key) => $item])
            ->toArray();

        $allProcessedItems = [];
        $finalReports = [];
        $statusFilter = $request->input('filter_status');
        
        // Loop Each Doc
        foreach($documents as $doc) {
            $docItems = [];
            $payload = is_string($doc->payload_data) ? json_decode($doc->payload_data, true) : (is_array($doc->payload_data) ? $doc->payload_data : []);
            if (!is_array($payload)) $payload = [];

            foreach ($payload as $item) {
                // Item Processing Logic (Same as Command)
                $wc = !empty($item['child_workcenter']) ? $item['child_workcenter'] : ($item['workcenter_induk'] ?? '-');
                $matnr = $item['material_number'] ?? '';
                if(ctype_digit($matnr)) { $matnr = ltrim($matnr, '0'); }

                $assigned = isset($item['assigned_qty']) ? floatval($item['assigned_qty']) : 0;
                $confirmed = isset($item['confirmed_qty']) ? floatval($item['confirmed_qty']) : 0;
                $netpr = isset($item['netpr']) ? floatval($item['netpr']) : 0;
                $waerk = isset($item['waerk']) ? $item['waerk'] : '';
                
                // Remark Data
                $remarkQty = isset($item['remark_qty']) ? floatval($item['remark_qty']) : 0;
                $remarkText = isset($item['remark']) ? $item['remark'] : '-';
                $remarkText = str_replace('; ', "\n", $remarkText);

                $confirmedPrice = $netpr * $confirmed;
                $balance = $assigned - ($confirmed + $remarkQty);
                $failedPrice = $netpr * ($balance + $remarkQty);

                $hasRemark = ($remarkQty > 0 || ($remarkText !== '-' && !empty($remarkText)));
                
                $expiredAt = Carbon::parse($doc->expired_at);
                
                if ($balance <= 0) {
                    $status = 'COMPLETED'; 
                } elseif ($hasRemark) {
                    $status = 'NOT COMPLETED WITH REMARK';
                } elseif (now()->gt($expiredAt)) {
                    $status = 'NOT COMPLETED';
                } else {
                    $status = 'ACTIVE';
                }

                // FILTER LOGIC PER ITEM
                $keep = true;
                if ($statusFilter) {
                    if ($statusFilter === 'NOT COMPLETED') { 
                        if (!in_array($status, ['NOT COMPLETED', 'NOT COMPLETED WITH REMARK'])) $keep = false;
                    } elseif ($statusFilter === 'COMPLETED') {
                         if ($status !== 'COMPLETED') $keep = false;
                    } else {
                        if ($status !== $statusFilter) $keep = false;
                    }
                }
                
                if (!$statusFilter && !$request->has('wi_codes')) {
                     // Standard Email Report Behavior: Exclude Active
                     if (in_array($status, ['ACTIVE', 'INACTIVE'])) $keep = false;
                }
                
                if (!$keep && $request->has('wi_codes')) {
                    // Re-evaluate for Manual Print
                    $keep = true; 
                }

                if (!$keep) continue;

                // Rest of Fields
                $kdauf = $item['kdauf'] ?? '';
                $kdpos = isset($item['kdpos']) ? ltrim($item['kdpos'], '0') : '';
                $soItem = $kdauf . '-' . $kdpos;

                $baseTime = isset($item['vgw01']) ? floatval($item['vgw01']) : 0;
                $unit = isset($item['vge01']) ? strtoupper($item['vge01']) : '';
                $totalTime = $baseTime * $assigned;

                if ($unit == 'S' || $unit == 'SEC') {
                    $finalTime = $totalTime / 60; 
                    $finalUnit = 'Menit';
                } else {
                    $finalTime = $totalTime;
                    $finalUnit = $unit; 
                }
                $taktDisplay = (fmod($finalTime, 1) !== 0.00) ? number_format($finalTime, 2) : number_format($finalTime, 0);
                $taktFull = $taktDisplay . ' ' . $finalUnit;

                 // Format Prices
                 if (strtoupper($waerk) === 'USD') {
                    $prefixInfo = '$ ';
                } elseif (strtoupper($waerk) === 'IDR') {
                    $prefixInfo = 'Rp ';
                } else {
                    $prefixInfo = '';
                }
                
                $priceFormatted = $prefixInfo . number_format($confirmedPrice, (strtoupper($waerk) === 'USD' ? 2 : 0), ',', '.');
                $failedPriceFormatted = $prefixInfo . number_format($failedPrice, (strtoupper($waerk) === 'USD' ? 2 : 0), ',', '.');
                
                $processedItem = [
                    'doc_no'        => $doc->wi_document_code,
                    'created_at'    => $doc->created_at,
                    'expired_at'    => $expiredAt->format('m-d H:i'),
                    'workcenter'    => $wc,
                    'wc_description'=> $wcDescriptions[strtoupper($wc)] ?? '-', // Populated via lookup
                    'aufnr'         => $item['aufnr'] ?? '-',
                    'vornr'         => $item['vornr'] ?? '', // [NEW] Added vornr
                    'material'      => $matnr,
                    'description'   => $item['material_desc'] ?? '-',
                    'assigned'      => $assigned,
                    'confirmed'     => $confirmed,
                    'balance'       => $balance,
                    'remark_qty'    => $remarkQty,
                    'remark_text'   => $remarkText,
                    'price_formatted' => $priceFormatted,
                    'confirmed_price' => $confirmedPrice, 
                    'failed_price'    => $failedPrice,
                    'price_ok_fmt'    => $priceFormatted,       
                    'price_fail_fmt'  => $failedPriceFormatted,   
                    'currency'        => strtoupper($waerk),
                    'buyer'           => $item['name1'] ?? '-',
                    'nik'             => $item['nik'] ?? '-',
                    'name'            => $item['name'] ?? '-',
                    'status'          => $status,
                    'so_item'         => $soItem,
                    'takt_time'       => $taktFull,
                    'raw_total_time'  => $finalTime, // Use normalized time (Minutes)
                ];

                if ($groupByDoc) {
                    $docItems[] = $processedItem;
                } else {
                    $allProcessedItems[] = $processedItem;
                }
            } // End Item Loop

            if ($groupByDoc && !empty($docItems)) {
                $sortedItems = collect($docItems)->sortBy([
                    ['workcenter', 'asc'],
                    ['nik', 'asc']
                ])->values()->all();

                $totalAssigned = collect($sortedItems)->sum('assigned');
                $totalConfirmed = collect($sortedItems)->sum('confirmed');
                $totalFailed = collect($sortedItems)->sum('balance'); 
                $totalRemarkQty = collect($sortedItems)->sum('remark_qty');
                $achievement = ($totalAssigned > 0) ? round(($totalConfirmed / $totalAssigned) * 100) . '%' : '0%';
                
                $totalConfirmedPrice = collect($sortedItems)->sum('confirmed_price');
                $totalFailedPrice = collect($sortedItems)->sum('failed_price');

                $curr = $sortedItems[0]['currency'] ?? 'IDR';
                $pfx = ($curr === 'USD') ? '$ ' : 'Rp ';
                $dec = ($curr === 'USD') ? 2 : 0;

                $finalReports[] = [
                    'report_title' => 'WORK INSTRUCTION SHEET', 
                    'items' => $sortedItems,
                    'summary' => [
                        'total_assigned' => $totalAssigned,
                        'total_confirmed' => $totalConfirmed,
                        'total_failed' => $totalFailed,
                        'total_remark_qty' => $totalRemarkQty,
                        'achievement_rate' => $achievement,
                        'total_price_ok' => $pfx . number_format($totalConfirmedPrice, $dec, ',', '.'),
                        'total_price_fail' => $pfx . number_format($totalFailedPrice, $dec, ',', '.')
                    ],
                    'nama_bagian' => $namaBagian,  
                    'printDate' => now()->format('d-M-Y H:i'),
                    'filterInfo' => $filterString,
                    'doc_metadata' => [
                         'code'     => $doc->wi_document_code,
                         'status'   => (now()->gt($doc->expired_at)) ? 'EXPIRED' : (
                                        (collect($sortedItems)->sum('balance') <= 0) ? 'COMPLETED' : 'ACTIVE'
                                      ),
                         'date'     => $doc->created_at->format('d-M-Y'),
                         'expired'  => $doc->expired_at ? Carbon::parse($doc->expired_at)->format('d-M-Y H:i') : '-',
                    ]
                ];
            }
        } // End Doc Loop

        if (!$groupByDoc && !empty($allProcessedItems)) {
            // 1. Sort
            $sortedItems = collect($allProcessedItems)->sortBy(function($item) use ($sortMode) {
                if ($sortMode === 'nik') {
                    // Sort by NIK first, then Workcenter
                    return sprintf('%s_%s', $item['nik'] ?? '', $item['workcenter'] ?? '');
                } else {
                    // Default: Sort by Workcenter first, then NIK
                    return sprintf('%s_%s', $item['workcenter'] ?? '', $item['nik'] ?? '');
                }
            })->values()->all();

            // 2. Summary
            $totalAssigned = collect($sortedItems)->sum('assigned');
            $totalConfirmed = collect($sortedItems)->sum('confirmed'); 
            $totalRemarkQty = collect($sortedItems)->sum('remark_qty');
            $totalFailed = $totalAssigned - $totalConfirmed; 
            
            $totalConfirmedPrice = collect($sortedItems)->sum('confirmed_price');
            $totalFailedPrice = collect($sortedItems)->sum('failed_price');

            $achievement = $totalAssigned > 0 ? round(($totalConfirmed / $totalAssigned) * 100) . '%' : '0%';

            $firstCurrency = collect($sortedItems)->first()['currency'] ?? '';
            $prefix = (strtoupper($firstCurrency) === 'USD') ? '$ ' : 'Rp ';
            $decimal = (strtoupper($firstCurrency) === 'USD') ? 2 : 0;

            $totalConfirmedPriceFmt = $prefix . number_format($totalConfirmedPrice, $decimal, ',', '.');
            $totalFailedPriceFmt = $prefix . number_format($totalFailedPrice, $decimal, ',', '.');

            // Calculate Aggregate Status
            $uniqueStatuses = collect($sortedItems)->pluck('status')->unique();
            $aggStatus = 'ACTIVE';
            
            if ($uniqueStatuses->contains('ACTIVE') || $uniqueStatuses->contains('INACTIVE')) {
                $aggStatus = 'ACTIVE';
            } elseif ($uniqueStatuses->contains('NOT COMPLETED') || $uniqueStatuses->contains('NOT COMPLETED WITH REMARK')) {
                $aggStatus = 'EXPIRED';
            } elseif ($uniqueStatuses->contains('COMPLETED')) {
                $aggStatus = 'COMPLETED';
            }

            $reportData = [
                'report_title' => 'DAILY REPORT WI',
                'items' => $sortedItems,
                'summary' => [
                    'total_assigned' => $totalAssigned,
                    'total_confirmed' => $totalConfirmed,
                    'total_failed' => $totalFailed,
                    'total_remark_qty' => $totalRemarkQty,
                    'achievement_rate' => $achievement,
                    'total_price_ok' => $totalConfirmedPriceFmt,
                    'total_price_fail' => $totalFailedPriceFmt
                ],
                'nama_bagian' => $namaBagian,  
                'printDate' => now()->format('d-M-Y H:i'),
                'filterInfo' => $filterString,
                'doc_metadata' => [
                     'code'     => $documents->count() === 1 ? $documents->first()->wi_document_code : 'MULTIPLE (' . $documents->count() . ')',
                     'status'   => $aggStatus,
                     'date'     => ($documents->count() > 0) ? (
                                     ($documents->min('created_at')->format('Y-m-d') === $documents->max('created_at')->format('Y-m-d')) 
                                     ? $documents->first()->created_at->format('d-M-Y') 
                                     : $documents->min('created_at')->format('d-M-Y') . ' - ' . $documents->max('created_at')->format('d-M-Y')
                                   ) : '-',
                     'expired'  => ($documents->count() > 0 && $documents->first()->expired_at) ? (
                                      ($documents->min('expired_at') === $documents->max('expired_at'))
                                      ? Carbon::parse($documents->first()->expired_at)->format('d-M-Y H:i')
                                      : Carbon::parse($documents->min('expired_at'))->format('d-M-Y') . ' - ' . Carbon::parse($documents->max('expired_at'))->format('d-M-Y')
                                   ) : '-',
                ]
            ];
            
            $finalReports = [$reportData];
        }

        if (empty($finalReports)) {
             return ['success' => false, 'message' => 'Tidak ada data history untuk diprint.'];
        }

        return [
            'success' => true, 
            'reports' => $finalReports, 
            'printedBy' => $printedBy,
            'department' => $department,
            'filterInfoString' => $dateInfo ?? $filterString
        ];
        // === UNIFIED REPORT LOGIC END ===

        if (empty($allReports)) {
             return ['success' => false, 'message' => 'Tidak ada data history.'];
        }

        return [
            'success' => true, 
            'reports' => $allReports, // Return Reports Array
            'printedBy' => $printedBy,
            'department' => $department,
            'filterInfoString' => $dateInfo
        ];
    }

    private function _generateActiveAttachment($request, $plantCode, $printedBy, $department) {
        $date = $request->input('filter_date');
        $today = Carbon::today();
        $targetDate = $date ? (strpos($date, ' to ') === false ? $date : null) : $today->format('Y-m-d');
        
        if (!$targetDate) return null;

        $activeDocs = HistoryWi::where('plant_code', $plantCode)
            ->whereDate('document_date', $targetDate)
            ->get()
            ->filter(function($doc) {
                if ($doc->expired_at) return !now()->greaterThan(Carbon::parse($doc->expired_at));
                return true; 
            });

        if ($activeDocs->isEmpty()) return null;

        foreach ($activeDocs as $doc) {
            $payload = is_string($doc->payload_data) ? json_decode($doc->payload_data, true) : $doc->payload_data;
            if (!$payload) $payload = [];
            
            $updatedPayload = [];
            foreach ($payload as $item) {
                $aufnr = $item['aufnr'] ?? null;
                if ($aufnr) {
                    $prodData = ProductionTData1::where('AUFNR', $aufnr)->first();
                    if ($prodData) {
                        $item['buyer_sourced'] = $prodData->NAME1; 
                        $price = $prodData->NETPR ?? 0;
                        $currency = $prodData->WAERK ?? '';
                        $fmtPrice = number_format((float)$price, (strtoupper($currency) === 'USD' ? 2 : 0), ',', '.');
                        $item['price_sourced'] = $currency . ' ' . $fmtPrice; 
                    } else {
                        $item['buyer_sourced'] = '-';
                        $item['price_sourced'] = '-';
                    }
                }
                $updatedPayload[] = $item;
            }
            $doc->payload_data = $updatedPayload;
        }

        $activePdfName = 'Active_WI_' . now()->format('Ymd_His') . '.pdf';
        $activePdfPath = storage_path('app/public/' . $activePdfName);

        $activeData = [
            'documents' => $activeDocs,
            'printedBy' => $printedBy,
            'department' => $department,
            'isEmail' => true
        ];

        $pdfActive = Pdf::loadView('pdf.wi_single_document', $activeData)
                    ->setPaper('a4', 'landscape');
        
        $pdfActive->save($activePdfPath);
        return $activePdfPath;
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
                $remarkQty = isset($item['remark_qty']) ? floatval($item['remark_qty']) : 0;
                $remarkText = isset($item['remark']) ? $item['remark'] : '-';
                $remarkText = str_replace('; ', "\n", $remarkText);

                $confirmed = isset($item['confirmed_qty']) ? floatval($item['confirmed_qty']) : 0;
                $balance = $assigned - ($confirmed + $remarkQty);

                $netpr = isset($item['netpr']) ? floatval($item['netpr']) : 0;
                $waerk = isset($item['waerk']) ? $item['waerk'] : '';
                
                $confirmedPrice = $netpr * $confirmed;
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
                    'confirmed_price' => $confirmedPrice,
                    'failed_price'    => $failedPrice,
                    'price_ok_fmt'    => $priceFormatted,
                    'price_fail_fmt'  => $failedPriceFormatted,
                    'currency'        => strtoupper($waerk),
                    
                    'qty_op'        => $qtyOper,
                    'qty_wi'        => $assigned,
                    'takt_time'     => $taktFull,
                    'raw_total_time' => $finalTime,
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
            'documents' => $documents,
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
        $date = $request->input('date');
        $time = $request->input('time');
        $items = $request->input('items', []);

        if (!$plantCode || !$date || !$time || empty($items)) {
            return response()->json(['error' => 'Missing required fields'], 400);
        }

        $formattedDate = Carbon::parse($date)->format('Ymd');
        $formattedTime = Carbon::parse($time)->format('H:i:s');
        
        $response = new StreamedResponse(function() use ($items, $plantCode, $formattedDate, $formattedTime) {
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

    public function streamRelease(Request $request)
    {
        $plantCode = $request->input('plant_code');
        $items = $request->input('items', []); // Array of objects with 'aufnr'

        $response = new StreamedResponse(function () use ($items, $plantCode) {
            $total = count($items);
            if ($total === 0) {
                echo "data: " . json_encode(['progress' => 100, 'message' => 'No items to release.', 'completed' => true]) . "\n\n";
                ob_flush();
                flush();
                return;
            }

            $releaseService = new Release();
            $ypprService = new YPPR074Z();

            foreach ($items as $index => $item) {
                $aufnr = $item['aufnr'];
                $msgPrefix = "[$aufnr]";

                try {
                    // 1. Release
                    // Call Flask Service Release
                    $releaseService->release($aufnr);

                    // 2. Refresh YPPR074Z (Specific PRO)
                    // Note: User requested to refresh only the specific PRO using refreshPro
                    $ypprService->refreshPro($plantCode, $aufnr);

                    // 3. Verify Status
                    // Check if PRO is now valid (REL or DSP)
                    // We check if it is valid by re-fetching TData1
                    $pro = ProductionTData1::where('WERKSX', $plantCode)->where('AUFNR', $aufnr)->first();
                    $isValid = false;
                    $currentStats = $pro ? $pro->STATS : 'UNKNOWN';

                    if ($pro && (str_contains($currentStats, 'REL') || str_contains($currentStats, 'DSP'))) {
                        $isValid = true;
                    }

                    // Check if it is strictly NOT 'CRTD' (as per "jika ada yang belum rel atau dsp")
                    // If it contains CRTD even if it has REL? Usually CRTD goes away.
                    // Let's assume Valid = Has REL OR Has DSP.
                    
                    if ($isValid) {
                        $percent = round((($index + 1) / $total) * 100);
                        echo "data: " . json_encode([
                            'progress' => $percent,
                            'message' => "$msgPrefix Released & Refreshed. Status: $currentStats",
                            'aufnr' => $aufnr,
                            'type' => 'success'
                        ]) . "\n\n";
                    } else {
                        throw new \Exception("Release succeeded but status is still invalid: $currentStats");
                    }

                } catch (\Exception $e) {
                    $percent = round((($index + 1) / $total) * 100);
                    echo "data: " . json_encode([
                        'progress' => $percent,
                        'message' => "$msgPrefix Release Failed: " . $e->getMessage(),
                        'aufnr' => $aufnr,
                        'type' => 'error'
                    ]) . "\n\n";
                }

                if (ob_get_level() > 0) ob_flush();
                flush();
            }

            echo "data: " . json_encode(['progress' => 100, 'message' => 'Release process completed.', 'completed' => true, 'type' => 'success']) . "\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Cache-Control', 'no-cache');
        return $response;
    }

    public function streamRefresh(Request $request)
    {
        $plantCode = $request->input('plant_code');
        $items = $request->input('items', []); // Array of objects with 'aufnr'

        $response = new StreamedResponse(function () use ($items, $plantCode) {
            $total = count($items);
            if ($total === 0) {
                echo "data: " . json_encode(['progress' => 100, 'message' => 'No items to refresh.', 'completed' => true]) . "\n\n";
                ob_flush();
                flush();
                return;
            }

            $ypprService = new YPPR074Z();

            foreach ($items as $index => $item) {
                $aufnr = $item['aufnr'];
                $msgPrefix = "[$aufnr]";

                try {
                    // Refresh YPPR074Z (Specific PRO)
                    $ypprService->refreshPro($plantCode, $aufnr);

                    $percent = round((($index + 1) / $total) * 100);
                    echo "data: " . json_encode([
                        'progress' => $percent,
                        'message' => "$msgPrefix Refreshed successfully.",
                        'aufnr' => $aufnr,
                        'type' => 'success'
                    ]) . "\n\n";

                } catch (\Exception $e) {
                    // Fallback: If SAP fails (e.g. TECO/No Data), clear local data conforming to user request
                    try {
                        ProductionTData1::where('WERKSX', $plantCode)->where('AUFNR', $aufnr)->delete();
                        ProductionTData3::where('WERKSX', $plantCode)->where('AUFNR', $aufnr)->delete();
                        ProductionTData4::where('WERKSX', $plantCode)->where('AUFNR', $aufnr)->delete();

                        $percent = round((($index + 1) / $total) * 100);
                        echo "data: " . json_encode([
                            'progress' => $percent,
                            'message' => "$msgPrefix Data cleaned (SAP Empty/Error).",
                            'aufnr' => $aufnr,
                            'type' => 'success' 
                        ]) . "\n\n";

                    } catch (\Exception $delErr) {
                         // Double Fail
                        $percent = round((($index + 1) / $total) * 100);
                        echo "data: " . json_encode([
                            'progress' => $percent,
                            'message' => "$msgPrefix Refresh Failed & Cleanup Failed: " . $e->getMessage(),
                            'aufnr' => $aufnr,
                            'type' => 'error'
                        ]) . "\n\n";
                    }
                }

                if (ob_get_level() > 0) ob_flush();
                flush();
            }

            echo "data: " . json_encode(['progress' => 100, 'message' => 'Refresh process completed.', 'type' => 'complete']) . "\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Cache-Control', 'no-cache');
        return $response;
    }

    public function streamChangeWc(Request $request) 
    {
        $items = $request->input('items', []);
        $targetWc = $request->input('target_wc');
        if (empty($items) || !$targetWc) {
             return response()->json(['error' => 'Invalid parameters'], 400);
        }
        $reqPlant = $request->input('plant');
        $response = new StreamedResponse(function () use ($items, $targetWc, $reqPlant) {
            $total = count($items);
            $changeWcService = new ChangeWc();
            $flaskBase = rtrim(env('FLASK_API_URL'), '/');
            $shortText = '';
            try {
                 $firstPlant = is_array($items[0]) ? ($items[0]['plant'] ?? $items[0]['pwwrk'] ?? '') : ($items[0]->plant ?? $items[0]->pwwrk ?? '');
                 if ($firstPlant) {
                     $sapUser = session('username');
                     $sapPass = session('password');
                     $descRes = Http::timeout(10)->withHeaders([
                        'X-SAP-Username' => $sapUser,
                        'X-SAP-Password' => $sapPass,
                     ])->get($flaskBase . '/api/get_wc_desc', [
                        'wc' => $targetWc,
                        'pwwrk' => $firstPlant,
                     ]);
                     if ($descRes->successful()) {
                         $shortText = $descRes->json()['E_DESC'] ?? '';
                     }
                 }
            } catch (\Exception $e) {
                // Ignore desc fetch error, continue with empty desc
            }

            foreach ($items as $index => $item) {
                $aufnr = is_array($item) ? ($item['aufnr'] ?? $item['proCode'] ?? '') : ($item->aufnr ?? $item->proCode ?? '');
                $plant = is_array($item) ? ($item['plant'] ?? $item['pwwrk'] ?? '') : ($item->plant ?? $item->pwwrk ?? '');
                $oper  = is_array($item) ? ($item['oper'] ?? '0010') : ($item->oper ?? '0010');
                
                if (!$aufnr) continue;

                $msgPrefix = "[$aufnr]";

                try {
                    // 1. Call Change WC Service (Correct Payload for /api/save_edit)
                    $payload = [
                        "IV_AUFNR" => $aufnr, 
                        "IV_COMMIT" => "X",
                        "IT_OPERATION" => [[
                            "SEQUEN" => "0", 
                            "OPER" => $oper, 
                            "WORK_CEN" => $targetWc,
                            "W" => "X", 
                            "SHORT_T" => $shortText, 
                            "S" => "X"
                        ]]
                    ];
                    
                    $res = $changeWcService->handle($payload);

                    if (!$res['success']) {
                         $errors = collect($res['messages'] ?? [])
                            ->filter(fn($m) => in_array($m['type'], ['E', 'A']))
                            ->pluck('message')
                            ->filter()
                            ->unique()
                            ->join(', ');
                         
                         if (empty($errors)) {
                             $errors = collect($res['messages'] ?? [])->pluck('message')->filter()->join(', ');
                         }
                         
                         if (empty($errors)) {
                             $errors = "Unknown SAP Error. Raw: " . json_encode($res['raw'] ?? $res);
                         }
                         throw new \Exception("Change WC Failed: $errors");
                    }

                    $refreshPlant = $reqPlant; 
                    if (!$refreshPlant) {
                         $existingPro = ProductionTData3::where('AUFNR', $aufnr)->first();
                         $refreshPlant = $existingPro->WERKSX ?? $plant;
                    }

                    $ypprService = new YPPR074Z();
                    $ypprService->refreshPro($refreshPlant, $aufnr);
                    
                    $percent = round((($index + 1) / $total) * 100);
                    echo "data: " . json_encode([
                        'progress' => $percent,
                        'message' => "$msgPrefix Changed to $targetWc & Refreshed.",
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
                
                if (ob_get_level() > 0) ob_flush();
                flush();
            }
            
            echo "data: " . json_encode(['progress' => 100, 'message' => 'Process Completed.', 'completed' => true]) . "\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Cache-Control', 'no-cache');
        return $response;
    }

    protected function getAvailableProsData($kode, $workcenter = null, $search = null)
    {
        $tData1 = ProductionTData1::where('WERKSX', $kode)
            ->whereRaw('CAST(MGVRG2 AS DECIMAL(20,3)) > CAST(COALESCE(LMNGA, 0) AS DECIMAL(20,3))')
            ->where(function ($query) {
                $query->where('STATS', 'LIKE', '%REL%')
                      ->orWhere('STATS', 'LIKE', '%PCNF%');
            });
            
        if ($workcenter && $workcenter !== 'all') {
            $children = WorkcenterMapping::where('wc_induk', $workcenter)->pluck('workcenter')->toArray();
            if (!empty($children)) {
                $children[] = $workcenter;
                $tData1->whereIn('ARBPL', $children);
            } else {
                $tData1->where('ARBPL', $workcenter);
            }
        }

        if ($search) {
             if (preg_match('/^"(.*)"$/', trim($search), $matches)) {
                 $term = $matches[1];
                 $tData1->where(function($q) use ($term) {
                     $q->where('AUFNR', '=', $term)
                       ->orWhere('MATNR', '=', $term)
                       ->orWhere('MAKTX', '=', $term)
                       ->orWhere('KDAUF', '=', $term)
                       ->orWhere('KDPOS', '=', $term)
                       ->orWhere('ARBPL', '=', $term)
                       ->orWhere('STEUS', '=', $term)
                       ->orWhere('VORNR', '=', $term);
                 });
             } else {
                 $terms = array_filter(array_map('trim', explode(';', $search)));
                 
                 $tData1->where(function($q) use ($terms) {
                     foreach ($terms as $term) {
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
        }

        $assignedProQuantities = $this->getAssignedProQuantities($kode);
        
        $results = $tData1->get()->transform(function ($item) use ($assignedProQuantities) {
            $aufnr = $item->AUFNR;
            $key = $aufnr . '-' . ($item->VORNR ?? '');
            
            $qtySisaAwal = $item->MGVRG2 - $item->LMNGA;
            $qtyAllocatedInWi = $assignedProQuantities[$key] ?? 0;
            $qtySisaAkhir = $qtySisaAwal - $qtyAllocatedInWi;
            
            $item->real_sisa_qty = $qtySisaAkhir; 
            return $item;
        })->filter(function ($item) {
             return $item->real_sisa_qty > 0.001; 
        });

        return $results;
    }

    public function getAvailableItems(Request $request, $kode)
    {
        $workcenter = $request->query('workcenter', 'all');
        $search = $request->query('search');
        
        $availableItems = $this->getAvailableProsData($kode, $workcenter, $search);
        
        $data = $availableItems->values()->map(function($item) {
             return [
                 'aufnr' => $item->AUFNR,
                 'vornr' => $item->VORNR,
                 'material' => $item->MATNR,
                 'description' => $item->MAKTX,
                 'available_qty' => $item->real_sisa_qty,
                 'uom' => $item->MEINS, 
                 'workcenter' => $item->ARBPL,
                 'vgw01' => floatval($item->VGW01 ?? 0),
                 'vge01' => strtoupper($item->VGE01 ?? ''),
             ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getEmployees(Request $request, $kode)
    {
        $apiUrl = 'https://monitoring-kpi.kayumebelsmg.net/api/get-nik-confirmasi';
        $apiToken = env('API_TOKEN_NIK'); 
        $employees = []; 

        try {
            $response = Http::withToken($apiToken)->post($apiUrl, ['kode_laravel' => $kode]);
            if ($response->successful()) {
                $employees = $response->json()['data'];
            }
            return response()->json(['success' => true, 'data' => $employees]);
        } catch (\Exception $e) {
            Log::error('Koneksi API NIK Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
    }

    public function addItem(Request $request)
    {
        $request->validate([
            'wi_code' => 'required|string',
            'aufnr' => 'required|string',
            'vornr' => 'required|string', 
            'qty' => 'required|numeric|min:0.001',
            'nik' => 'required|string',
            'name' => 'required|string',
            'target_workcenter' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $doc = HistoryWi::where('wi_document_code', $request->wi_code)->lockForUpdate()->firstOrFail();
            $plantCode = $doc->plant_code; 

            $availableItems = $this->getAvailableProsData($plantCode);
            $targetItem = $availableItems->first(function($i) use ($request) {
                return $i->AUFNR == $request->aufnr && $i->VORNR == $request->vornr;
            });

            if (!$targetItem) {
                return response()->json(['success' => false, 'message' => 'Item tidak ditemukan atau quantity sudah habis.'], 400);
            }

            if ($request->qty > $targetItem->real_sisa_qty) {
                 return response()->json(['success' => false, 'message' => "Quantity melebihi sisa tersedia ({$targetItem->real_sisa_qty})."], 400);
            }

            $payload = is_string($doc->payload_data) ? json_decode($doc->payload_data, true) : $doc->payload_data;
            if (!is_array($payload)) $payload = [];

            $requestedNik = $request->nik;
            $requestedWc = $request->target_workcenter;
            
            $itemFoundAndUpdated = false;
            foreach ($payload as $index => $existing) {
                $exNik = $existing['nik'] ?? '-';
                $exWc = $existing['target_workcenter'] ?? ($existing['workcenter'] ?? ''); 
                
                if ($exNik === $requestedNik && $exWc === $requestedWc && ($existing['aufnr'] === $request->aufnr) && ($existing['vornr'] === $request->vornr)) {
                     return response()->json([
                        'success' => false, 
                        'message' => "Operator {$request->name} ({$requestedNik}) sudah ditugaskan untuk PRO ini di Workcenter {$requestedWc}."
                    ], 400);
                }
            }

            $currentLoadMins = 0;
            if(!empty($payload)) {
                 foreach($payload as $pItem) {
                      $currentLoadMins += floatval($pItem['calculated_tak_time'] ?? 0);
                 }
            }
            
            $fixedSingleMins = 570;
            $workcenterMappings = WorkcenterMapping::where('plant', $plantCode)
                                  ->orWhere('kode_laravel', $plantCode)->get();
            
            $childrenOfThisWc = $workcenterMappings->filter(function($m) use ($doc) {
                 return strtoupper($m->wc_induk) === strtoupper($doc->workcenter_code) && 
                        strtoupper($m->workcenter) !== strtoupper($m->wc_induk);
            });
            
            $childCount = $childrenOfThisWc->count();
            $maxMins = ($childCount > 0) ? ($childCount * $fixedSingleMins) : $fixedSingleMins;

            $baseTime = floatval($targetItem->VGW01);
            $reqQty = floatval($request->qty);
            $unit = strtoupper($targetItem->VGE01);
            $totalRaw = $baseTime * $reqQty;
             if ($unit === 'S' || $unit === 'SEC') {
                $checkNewMins = $totalRaw / 60;
            } elseif ($unit === 'H' || $unit === 'HUR') {
                $checkNewMins = $totalRaw * 60;
            } else {
                $checkNewMins = $totalRaw;
            }

            if (($currentLoadMins + $checkNewMins) > ($maxMins + 0.01)) { // 0.01 tolerance
                 $sisaMins = max(0, $maxMins - $currentLoadMins);
                 return response()->json([
                     'success' => false, 
                     'message' => "Kapasitas Harian tidak mencukupi! Max: " . number_format($maxMins, 0) . " Min. Tersisa: " . number_format($sisaMins, 2) . " Min. Dibutuhkan: " . number_format($checkNewMins, 2) . " Min." 
                 ], 400);
            }

            $newItem = [
                'aufnr' => $targetItem->AUFNR,
                'vornr' => $targetItem->VORNR,
                'material' => $targetItem->MATNR,
                'material_desc' => $targetItem->MAKTX,
                'assigned_qty' => $request->qty,
                'qty_order' => $targetItem->MGVRG2, 
                'confirmed_qty' => 0,
                'remark_qty' => 0,
                'uom' => $targetItem->MEINS,
                'nik' => $request->nik,
                'name' => $request->name,
                'target_workcenter' => $request->target_workcenter,
                'vge01' => $targetItem->VGE01,
                'vgw01' => $targetItem->VGW01,
            ];
            
            $baseTime = floatval($targetItem->VGW01);
            $qty = floatval($request->qty);
            $unit = strtoupper($targetItem->VGE01);
            $totalRaw = $baseTime * $qty;
             if ($unit === 'S' || $unit === 'SEC') {
                $mins = $totalRaw / 60;
            } elseif ($unit === 'H' || $unit === 'HUR') {
                $mins = $totalRaw * 60;
            } else {
                $mins = $totalRaw;
            }
            $newItem['calculated_tak_time'] = number_format($mins, 2, '.', '');
            
            $newItem['name1'] = $targetItem->NAME1 ?? '-';
            $newItem['netpr'] = $targetItem->NETPR ?? 0;
            $newItem['waerk'] = $targetItem->WAERK ?? '';

            $payload[] = $newItem;
            $doc->payload_data = $payload;
            $doc->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Item berhasil ditambahkan.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function addItemBatch(Request $request)
    {
        $request->validate([
            'wi_code' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.aufnr' => 'required|string',
            'items.*.vornr' => 'required|string',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.nik' => 'required|string',
            'items.*.name' => 'required|string',
            'items.*.target_workcenter' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $doc = HistoryWi::where('wi_document_code', $request->wi_code)->lockForUpdate()->firstOrFail();
            $plantCode = $doc->plant_code; 

            $availableItems = $this->getAvailableProsData($plantCode);
            
            $groupedRequests = collect($request->items)->groupBy(function($item) {
                return $item['aufnr'] . '_' . $item['vornr'];
            });

            $allMappings = WorkcenterMapping::where('plant', $plantCode)
                                            ->orWhere('kode_laravel', $plantCode)
                                            ->get();

            $payload = is_string($doc->payload_data) ? json_decode($doc->payload_data, true) : $doc->payload_data;
            if (!is_array($payload)) $payload = [];

            foreach ($groupedRequests as $key => $requests) {
                $firstReq = $requests->first();
                $targetItem = $availableItems->first(function($i) use ($firstReq) {
                    return $i->AUFNR == $firstReq['aufnr'] && $i->VORNR == $firstReq['vornr'];
                });

                if (!$targetItem) {
                    throw new \Exception("Item {$firstReq['aufnr']} tidak ditemukan atau quantity sudah habis.");
                }

                $totalRequestedQty = $requests->sum('qty');
                
                if ($totalRequestedQty > $targetItem->real_sisa_qty + 0.0001) {
                     throw new \Exception("Total Quantity ({$totalRequestedQty}) melebihi sisa tersedia ({$targetItem->real_sisa_qty}) untuk Pro {$firstReq['aufnr']}.");
                }

                foreach ($requests as $req) {
                    $requestedNik = $req['nik'];
                    $requestedWc = $req['target_workcenter'];
                    
                    $isMerged = false;
                    foreach ($payload as &$existing) {
                        $exNik = $existing['nik'] ?? '-';
                        $exWc = $existing['target_workcenter'] ?? ($existing['workcenter'] ?? ($existing['child_workcenter'] ?? '')); 
                        
                        $exAufnr = $existing['aufnr'] ?? '-';
                        $exVornr = $existing['vornr'] ?? '-';
                        
                        Log::info("Compare AddItem: Req[N:{$requestedNik}, W:{$requestedWc}, A:{$req['aufnr']}] vs Ex[N:{$exNik}, W:{$exWc}, A:{$exAufnr}]");

                        if (trim($exNik) == trim($requestedNik) && 
                            trim($exWc) == trim($requestedWc) && 
                            trim($exAufnr) == trim($req['aufnr']) && 
                            (trim($exVornr) == trim($req['vornr'] ?? ''))
                           ) {
                             $confirmed = floatval($existing['confirmed_qty'] ?? 0);
                             if ($confirmed > 0) {
                                 throw new \Exception("Item PRO {$exAufnr} untuk Operator {$req['name']} tidak bisa ditambahkan karena sudah ada item yang dikonfirmasi.");
                             } else {
                                 Log::info("MATCH FOUND! Merging.");
                                 $currentAssigned = floatval($existing['assigned_qty']);
                                 $newAssigned = $currentAssigned + floatval($req['qty']);
                                 $existing['assigned_qty'] = $newAssigned;

                                 $baseTime = floatval($existing['vgw01'] ?? 0);
                                 $unit = strtoupper($existing['vge01'] ?? '');
                                 $totalRaw = $baseTime * $newAssigned;
                                 
                                 $mins = $totalRaw;
                                 if ($unit === 'S' || $unit === 'SEC') $mins = $totalRaw / 60;
                                 elseif ($unit === 'H' || $unit === 'HUR') $mins = $totalRaw * 60;
                                 
                                 $existing['calculated_tak_time'] = number_format($mins, 2, '.', '');
                                 
                                 $isMerged = true;
                                 break; 
                             }
                        }
                    }
                    unset($existing); 

                    if ($isMerged) continue; 

                    $duplicatesInBatch = $requests->filter(function($r) use ($requestedNik, $requestedWc, $req) {
                        return $r['nik'] === $requestedNik && 
                               $r['target_workcenter'] === $requestedWc &&
                               $r['aufnr'] == $req['aufnr'] &&
                               ($r['vornr'] ?? '') == ($req['vornr'] ?? '');
                    });
                    if ($duplicatesInBatch->count() > 1) {
                         throw new \Exception("Operator {$req['name']} dipilih lebih dari satu kali untuk PRO/Workcenter yang sama dalam batch ini.");
                    }

                    $wcTarget = $req['target_workcenter'];
                    $mapping = $allMappings->first(function($m) use ($wcTarget) {
                        return strtoupper($m->workcenter) === strtoupper($wcTarget);
                    });

                    if ($mapping) {
                        $childWc = $wcTarget;
                        $parentWc = $mapping->wc_induk;
                    } else {
                        $childWc = $wcTarget;
                        $parentWc = $wcTarget;
                    }

                    $newItem = [
                        'aufnr' => $targetItem->AUFNR,
                        'vornr' => $targetItem->VORNR,
                        'material' => $targetItem->MATNR,
                        'material_desc' => $targetItem->MAKTX,
                        'assigned_qty' => $req['qty'],
                        'qty_order' => $targetItem->MGVRG2, 
                        'confirmed_qty' => 0,
                        'remark_qty' => 0,
                        'uom' => $targetItem->MEINS,
                        'nik' => $req['nik'],
                        'name' => $req['name'],
                        'child_workcenter' => $childWc,
                        'workcenter_induk' => $parentWc,
                        'vge01' => $targetItem->VGE01,
                        'vgw01' => $targetItem->VGW01,
                        'kdauf' => $targetItem->KDAUF,
                        'kdpos' => $targetItem->KDPOS,
                        'dispo' => $targetItem->DISPO,
                        'steus' => $targetItem->STEUS,
                        'sssld' => $targetItem->SSSLD,
                        'ssavd' => $targetItem->SSAVD,
                        'kapaz' => $targetItem->KAPAZ, 
                        'material_number' => $targetItem->MATNR, 
                    ];
                    
                    $baseTime = floatval($targetItem->VGW01);
                    $qty = floatval($req['qty']);
                    $unit = strtoupper($targetItem->VGE01);
                    $totalRaw = $baseTime * $qty;
                     if ($unit === 'S' || $unit === 'SEC') {
                        $mins = $totalRaw / 60;
                    } elseif ($unit === 'H' || $unit === 'HUR') {
                        $mins = $totalRaw * 60;
                    } else {
                        $mins = $totalRaw;
                    }
                    $newItem['calculated_tak_time'] = number_format($mins, 2, '.', '');
                    $newItem['name1'] = $targetItem->NAME1 ?? '-';
                    $newItem['netpr'] = $targetItem->NETPR ?? 0;
                    $newItem['waerk'] = $targetItem->WAERK ?? '';

                    $payload[] = $newItem;
                }
            }

            $doc->payload_data = $payload;
            $doc->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => count($request->items) . ' Item berhasil ditambahkan.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400); 
        }
    }

    public function removeItem(Request $request)
    {
        $request->validate([
            'wi_code' => 'required|string',
            'aufnr' => 'required|string',
            'vornr' => 'required|string',
            'nik' => 'required|string',
            'qty' => 'required'
        ]);

        try {
            DB::beginTransaction();
            $doc = HistoryWi::where('wi_document_code', $request->wi_code)->lockForUpdate()->firstOrFail();
            
            $payload = is_string($doc->payload_data) ? json_decode($doc->payload_data, true) : $doc->payload_data;
            if (!is_array($payload)) $payload = [];
            
            $newPayload = [];
            $found = false;
            
            $reqNik = (string)$request->nik;
            $reqQty = floatval($request->qty);

            foreach ($payload as $item) {
                $itemNik = (string)($item['nik'] ?? '');
                $itemQty = floatval($item['assigned_qty'] ?? 0);

                if ( !$found &&
                     ($item['aufnr'] == $request->aufnr) && 
                     (($item['vornr'] ?? '') == $request->vornr) &&
                     ($itemNik === $reqNik) &&
                     (abs($itemQty - $reqQty) < 0.0001)
                   ) {
                    
                    $conf = floatval($item['confirmed_qty'] ?? 0);
                    if ($conf > 0) {
                        return response()->json(['success' => false, 'message' => 'Item sudah memiliki konfirmasi, tidak dapat dihapus.'], 400);
                    }
                    $found = true;
                } else {
                    $newPayload[] = $item;
                }
            }

            if (!$found) {
                return response()->json(['success' => false, 'message' => 'Item spesifik tidak ditemukan (Cek NIK/Qty).'], 400);
            }

            $doc->payload_data = $newPayload;
            $doc->save();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Item berhasil dihapus.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function fetchAllIds(Request $request, $kode)
    {
        $query = $this->_buildSourceQuery($request, $kode);
        
        $results = $query->select(['id', 'AUFNR', 'VORNR', 'WERKSX', 'PWWRK', 'ARBPL', 'MGVRG2', 'LMNGA', 'VGE01', 'VGW01'])->get();

        $assignedProQuantities = $this->getAssignedProQuantities($kode);
        
        $filtered = $results->map(function($item) use ($assignedProQuantities) {
            $key = $item->AUFNR . '-' . ($item->VORNR ?? '');
            
            $qtySisaAwal = $item->MGVRG2 - $item->LMNGA;
            $qtyAllocatedInWi = $assignedProQuantities[$key] ?? 0;
            $qtySisaAkhir = $qtySisaAwal - $qtyAllocatedInWi;
            
            return [
                'proCode' => $item->AUFNR,
                'oper'    => $item->VORNR,
                'pwwrk'   => $item->PWWRK ?? $item->WERKSX,
                'real_sisa' => $qtySisaAkhir
            ];
        })->filter(function($row) {
            return $row['real_sisa'] > 0.001;
        })->values();

        return response()->json([
            'success' => true,
            'data' => $filtered
        ]);
    }

    private function _buildSourceQuery(Request $request, $kode)
    {
        $search = $request->query('search');
        $filter = $request->query('filter', 'dspt_rel');
        
        $query = ProductionTData1::where('WERKSX', $kode)
            ->whereRaw('CAST(MGVRG2 AS DECIMAL(20,3)) > CAST(COALESCE(LMNGA, 0) AS DECIMAL(20,3))');

        if ($search) {
            if (preg_match('/^"(.*)"$/', trim($search), $matches)) {
                $term = $matches[1];
                $query->where(function($q) use ($term) {
                    $q->where('AUFNR', '=', $term)
                      ->orWhere('MATNR', '=', $term)
                      ->orWhere('MAKTX', '=', $term)
                      ->orWhere('KDAUF', '=', $term)
                      ->orWhere('KDPOS', '=', $term)
                      ->orWhere('ARBPL', '=', $term)
                      ->orWhere('STEUS', '=', $term)
                      ->orWhere('VORNR', '=', $term);
                });
            } else {
                $terms = array_filter(array_map('trim', explode(';', $search)));
                $query->where(function($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere(function($subQ) use ($term) {
                            if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $term, $matches)) {
                                $so = $matches[1];
                                $item = str_pad($matches[2], 6, '0', STR_PAD_LEFT);
                                $subQ->where('KDAUF', '=', $so)
                                     ->where('KDPOS', '=', $item);
                            } else {
                                $subQ->where('AUFNR', 'like', "%{$term}%")
                                     ->orWhere('MATNR', 'like', "%{$term}%")
                                     ->orWhere('MAKTX', 'like', "%{$term}%")
                                     ->orWhere('KDAUF', 'like', "%{$term}%")
                                     ->orWhere('KDPOS', 'like', "%{$term}%")
                                     ->orWhere('ARBPL', 'like', "%{$term}%")
                                     ->orWhere('STEUS', 'like', "%{$term}%")
                                     ->orWhere('VORNR', 'like', "%{$term}%");
                            }
                        });
                    }
                });
            }
        }

        // Advanced Search
        if ($request->has('adv_aufnr') && $request->adv_aufnr) {
            $val = $request->adv_aufnr;
            if(str_contains($val, ',')) {
                $arr = array_map('trim', explode(',', $val));
                $query->whereIn('AUFNR', $arr); 
            } else {
                $query->where('AUFNR', '=', $val);
            }
        }
        if ($request->has('adv_matnr') && $request->adv_matnr) {
            $val = $request->adv_matnr;
            if(str_contains($val, ',')) {
                 $arr = array_map('trim', explode(',', $val));
                 $query->whereIn('MATNR', $arr);
            } else {
                 $query->where('MATNR', '=', $val);
            }
        }
        if ($request->has('adv_maktx') && $request->adv_maktx) {
             $val = $request->adv_maktx;
             if(str_contains($val, ',')) {
                 $arr = array_map('trim', explode(',', $val));
                 $query->where(function($q) use ($arr) {
                     foreach($arr as $term) {
                         $q->orWhere('MAKTX', '=', $term);
                     }
                 });
             } else {
                 $query->where('MAKTX', '=', $val);
             }
        }
        if ($request->has('adv_arbpl') && $request->adv_arbpl) {
            $val = $request->adv_arbpl;
            if(str_contains($val, ',')) {
                 $arr = array_map('trim', explode(',', $val));
                 $query->whereIn('ARBPL', $arr);
            } else {
                 $query->where('ARBPL', '=', $val);
            }
        }
        
        // Revised SO (KDAUF) & Item Logic
        if ($request->has('adv_kdauf') && $request->adv_kdauf) {
            $val = $request->adv_kdauf;
            $rawInputs = str_contains($val, ',') ? explode(',', $val) : [$val];
            
            $soList = [];
            $soItemPairs = [];

            foreach ($rawInputs as $input) {
                $input = trim($input);
                if (empty($input)) continue;

                if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $input, $matches)) {
                    // It is SO-Item
                    $soItemPairs[] = [
                        'so' => $matches[1],
                        'item' => str_pad($matches[2], 6, '0', STR_PAD_LEFT)
                    ];
                } else {
                    // Assume it is just SO or maybe partial
                     $soList[] = $input;
                }
            }

            $query->where(function($q) use ($soList, $soItemPairs) {
                if (!empty($soList)) {
                    $q->orWhereIn('KDAUF', $soList);
                }
                foreach ($soItemPairs as $pair) {
                    $q->orWhere(function($sub) use ($pair) {
                        $sub->where('KDAUF', '=', $pair['so'])
                            ->where('KDPOS', '=', $pair['item']);
                    });
                }
            });
        }
        if ($request->has('adv_vornr') && $request->adv_vornr) {
            $query->where('VORNR', 'like', '%' . $request->adv_vornr . '%');
        }

        if ($filter === 'dspt_rel') {
            $query->where('STATS', 'LIKE', '%DSP%');
        }
        
        return $query;
    }

}
