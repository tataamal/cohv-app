<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\workcenter;
use App\Models\ProductionTData1; 
use App\Models\WorkcenterMapping;
use App\Models\HistoryWi;
use App\Models\HistoryWiItem;
use App\Models\HistoryPro;
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
// use App\Services\WorkcenterConsumeService;
use Carbon\CarbonInterface;

class CreateWiController extends Controller
{
    public function delete(Request $request) {
        $ids = $request->input('wi_codes');
        if (!$ids || !is_array($ids)) {
            return response()->json(['message' => 'Invalid data provided.'], 400);
        }
        try {
            DB::beginTransaction();

            $documents = HistoryWi::whereIn('wi_document_code', $ids)->get();
            
            if ($documents->isNotEmpty()) {
                $docIds = $documents->pluck('id');
                HistoryWiItem::whereIn('history_wi_id', $docIds)->update(['status' => 'DELETED']);
                HistoryWi::whereIn('id', $docIds)->update(['status' => 'DELETED']);
                HistoryWi::whereIn('id', $docIds)->delete();
            }

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
                'nextPage' => $pagination->hasMorePages() ? $pagination->currentPage() + 1 : null
            ]);
        } catch (\Exception $e) {
            Log::error("Create WI Index Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->view('errors.500', ['exception' => $e], 500);
        }
    }

    protected function getAssignedProQuantities(string $kodePlant)
    {
        $histories = HistoryWi::with(['items.pros'])
                              ->where('plant_code', $kodePlant)
                              ->where(function ($query) {
                                  $query->where('document_date', '>=', Carbon::today())
                                        ->orWhere('expired_at', '>=', Carbon::now());
                              })
                              ->get();
                              
        $assignedProQuantities = [];

        foreach ($histories as $history) {
            $proItems = $history->items;
            
            if ($proItems->isEmpty()) continue;

            // Check completion per item
            foreach ($proItems as $item) {
                // Determine quantities from relations
                $confirmedQty = $item->pros->whereIn('status', ['confirmasi', 'confirm', 'confirmed'])->sum('qty_pro');
                $remarkQty    = $item->pros->where('status', 'remark')->sum('qty_pro');
                $assignedQty  = (float) $item->assigned_qty;

                // Check if item is completed (Balance <= 0)
                $balance = $assignedQty - ($confirmedQty + $remarkQty);
                if ($balance <= 0.001) {
                    continue; // Skip completed items
                }
                
                // If Item Status is literally COMPLETED, also skip
                 if (str_contains(strtoupper($item->status ?? ''), 'COMPLETED')) {
                    continue;
                }
                
                $effectiveAssigned = max(0, $balance);

                if ($item->aufnr) {
                    $key = $item->aufnr . '-' . ($item->vornr ?? '');
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

    public function saveWorkInstruction(Request $request) // , WorkcenterConsumeService $consumeService
    {
        $requestData = $request->json()->all();

        $plantCode  = $requestData['plant_code'] ?? null; // INI KODE/VALUE (mis. "3021"), BUKAN ID
        $inputDate  = $requestData['document_date'] ?? null;
        $inputTime  = $requestData['document_time'] ?? null;
        $payload    = $requestData['workcenter_allocations'] ?? [];

        if (!$plantCode || !$inputDate || empty($payload)) {
            return response()->json([
                'message' => 'Data tidak lengkap. Tanggal/Plant/alokasi kosong.'
            ], 400);
        }
        $kodeLaravel = null;

        // fallback 1: kalau plantCode numeric, coba as ID dulu
        $kodeLaravel = \App\Models\KodeLaravel::query()
            ->where('laravel_code', (string)$plantCode)   // plantCode = VALUE
            ->first(['id']);

        if (!$kodeLaravel && ctype_digit((string)$plantCode)) {
            // fallback kalau ternyata plantCode adalah ID
            $kodeLaravel = \App\Models\KodeLaravel::query()
                ->where('id', (int)$plantCode)
                ->first(['id']);
        }

        if (!$kodeLaravel) {
            return response()->json([
                'message' => "plant_code '{$plantCode}' tidak ditemukan di master KodeLaravel."
            ], 400);
        }

        $kodeLaravelId = (int)$kodeLaravel->id;

        // prefix masih mengikuti pola kamu (kalau plant_code value diawali '3' => WIH)
        $docPrefix = str_starts_with((string)$plantCode, '3') ? 'WIH' : 'WIW';

        $baseDateTime = Carbon::parse(trim($inputDate . ' ' . ($inputTime ?: '00:00')));

        $defaultExpiredAt = ($docPrefix === 'WIH')
            ? $baseDateTime->copy()->addDay()->startOfDay()
            : $baseDateTime->copy()->addHours(24);

        $defaultDateForDb = $baseDateTime->toDateString();
        $defaultTimeForDb = $baseDateTime->format('H:i:s');

        $todayStr = Carbon::now()->toDateString();
        $wiDocuments = [];

        try {
            foreach ($payload as $wcAllocation) {
                $rawItems = $wcAllocation['pro_items'] ?? [];
                if (empty($rawItems)) {
                    continue;
                }

                $headerWc = $wcAllocation['parent_wc']
                    ?? $wcAllocation['workcenter']
                    ?? ($rawItems[0]['parent_wc'] ?? null)
                    ?? ($rawItems[0]['child_wc'] ?? null);

                if (!$headerWc) {
                    return response()->json([
                        'message' => 'Workcenter header tidak ditemukan pada allocation/payload.'
                    ], 400);
                }

                // =========================
                // Merge items by aufnr+vornr+nik
                // =========================
                $merged = [];
                foreach ($rawItems as $it) {
                    $aufnr = trim($it['aufnr'] ?? '');
                    $vornr = trim($it['vornr'] ?? '');
                    $nik   = trim($it['nik'] ?? '');

                    if ($aufnr === '' || $vornr === '' || $nik === '') continue;

                    $key = "{$aufnr}_{$vornr}_{$nik}";
                    $assignedQty = (float)($it['assigned_qty'] ?? 0);

                    if (!isset($merged[$key])) {
                        $merged[$key] = $it;
                        $merged[$key]['assigned_qty'] = $assignedQty;
                    } else {
                        $merged[$key]['assigned_qty'] = (float)$merged[$key]['assigned_qty'] + $assignedQty;
                        $merged[$key]['is_machining'] = !empty($merged[$key]['is_machining']) || !empty($it['is_machining']);
                        $merged[$key]['is_longshift'] = !empty($merged[$key]['is_longshift']) || !empty($it['is_longshift']);

                        $oldChild = $merged[$key]['child_wc'] ?? ($merged[$key]['child_workcenter'] ?? null);
                        $newChild = $it['child_wc'] ?? ($it['child_workcenter'] ?? null);
                        if ($oldChild && $newChild && $oldChild !== $newChild) {
                            Log::warning("Duplicate key with different child_wc for $key: $oldChild vs $newChild (merged by requirement)");
                        }
                    }

                    $baseTime = (float)($merged[$key]['vgw01'] ?? $it['vgw01'] ?? 0);
                    $unit = strtoupper((string)($merged[$key]['vge01'] ?? $it['vge01'] ?? ''));
                    $qty = (float)$merged[$key]['assigned_qty'];

                    $mins = $baseTime * $qty;
                    if (in_array($unit, ['S', 'SEC'], true)) {
                        $mins = $mins / 60;
                    } elseif (in_array($unit, ['H', 'HUR'], true)) {
                        $mins = $mins * 60;
                    }

                    $merged[$key]['calculated_takt_time'] = number_format($mins, 2, '.', '');
                }

                $items = array_values($merged);

                // =========================
                // Header flags
                // =========================
                $headerMachining = !empty($wcAllocation['is_machining']);
                $headerLongshift = !empty($wcAllocation['is_longshift']);
                foreach ($items as $itm) {
                    if (!empty($itm['is_machining'])) $headerMachining = true;
                    if (!empty($itm['is_longshift'])) $headerLongshift = true;
                }

                // =========================
                // Determine dates/times/expired_at
                // =========================
                $dateForDb = $defaultDateForDb;
                $timeForDb = $defaultTimeForDb;
                $expiredAt = $defaultExpiredAt;

                if ($headerMachining) {
                    $minSsavd = null;
                    $maxSssld = null;

                    foreach ($items as $itm) {
                        $sRaw = (($itm['ssavd'] ?? '-') !== '-') ? trim((string)$itm['ssavd']) : null;
                        $eRaw = (($itm['sssld'] ?? '-') !== '-') ? trim((string)$itm['sssld']) : null;

                        $s = $this->parseWiDate($sRaw);
                        $e = $this->parseWiDate($eRaw);

                        if ($s && (!$minSsavd || $s->lt($minSsavd))) $minSsavd = $s;
                        if ($e && (!$maxSssld || $e->gt($maxSssld))) $maxSssld = $e;
                    }

                    if ($minSsavd) $dateForDb = $minSsavd->toDateString();
                    if ($maxSssld) $expiredAt = $maxSssld->copy()->endOfDay();

                    $timeForDb = null;
                } elseif ($headerLongshift) {
                    $expiredAt = $baseDateTime->copy()->addHours(24);
                }

                // =========================
                // [CAPACITY] booking hanya kalau ACTIVE (tanggal == hari ini)
                // resolve workcenter via mapping_table menggunakan kode_laravel_id (ID)
                // =========================
                // $shouldConsume = ($dateForDb === $todayStr);
                $shouldConsume = false; // FEATURE DISABLED

                $needsByWcId  = [];
                $totalsByWcId = [];

                /*
                if ($shouldConsume) {
                    $needsByWcCode = [];

                    foreach ($items as $itm) {
                        $mins = (float)($itm['calculated_takt_time'] ?? 0);
                        $needSec = (int)ceil($mins * 60);
                        if ($needSec <= 0) continue;

                        $childCode = $itm['child_wc'] ?? ($itm['child_workcenter'] ?? null);
                        $actualWcCode = trim((string)($childCode ?: $headerWc));
                        if ($actualWcCode === '') continue;

                        $needsByWcCode[$actualWcCode] = ($needsByWcCode[$actualWcCode] ?? 0) + $needSec;
                    }

                    $wcCodes = array_keys($needsByWcCode);

                    // IMPORTANT: kodeLaravelId yang dikirim ke service adalah ID (FK), bukan value plantCode
                    $resolved = $consumeService->resolveWorkcentersByKodeLaravel($kodeLaravelId, $wcCodes);

                    foreach ($wcCodes as $code) {
                        if (!isset($resolved[$code])) {
                            $wcId = \App\Models\workcenter::query()
                                ->where('kode_wc', $code)
                                ->value('id');

                            $wcIdText = $wcId ? (string)$wcId : '<workcenter_id>';

                            throw new \InvalidArgumentException(
                                "Workcenter {$code} (workcenter_id={$wcIdText}) belum dimapping ke kode_laravel_id={$kodeLaravelId} (plant_code='{$plantCode}'). " .
                                "Tambahkan row di mapping_table: INSERT INTO mapping_table (kode_laravel_id, workcenter_id, created_at, updated_at) " .
                                "VALUES ({$kodeLaravelId}, {$wcIdText}, NOW(), NOW());"
                            );
                        }

                        $wcId = (int)$resolved[$code]['id'];

                        // INI YANG BENAR: total detik kapasitas sudah dihitung dari operating_time di SERVICE
                        $totalSec = (int)($resolved[$code]['capacity_total_sec'] ?? WorkcenterConsumeService::MIN_CAPACITY_SEC);

                        $needsByWcId[$wcId]  = ($needsByWcId[$wcId] ?? 0) + (int)$needsByWcCode[$code];
                        $totalsByWcId[$wcId] = $totalSec;
                    }

                    ksort($needsByWcId);
                    ksort($totalsByWcId);
                }
                */

                // =========================================================
                // A) TRANSAKSI KECIL: lock sequence + (optional) consume + create header
                // =========================================================
                $maxRetry = 5;
                $history = null;
                $documentCode = null;

                for ($attempt = 1; $attempt <= $maxRetry; $attempt++) {
                    try {
                        [$history, $documentCode] = DB::transaction(function () use (
                            $docPrefix,
                            $plantCode,
                            $headerWc,
                            $dateForDb,
                            $timeForDb,
                            $expiredAt,
                            $headerMachining,
                            $headerLongshift,
                            $todayStr,
                            $shouldConsume,
                            $needsByWcId,
                            $totalsByWcId
                            // $consumeService
                        ) {
                            $latestHistory = HistoryWi::withTrashed()
                                ->where('doc_prefix', $docPrefix)
                                ->orderByDesc('sequence_number')
                                ->lockForUpdate()
                                ->first(['id', 'sequence_number']);

                            $nextNumber   = ($latestHistory?->sequence_number ?? 0) + 1;
                            $documentCode = $docPrefix . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);

                            $initialStatus = ($dateForDb === $todayStr) ? 'ACTIVE' : 'INACTIVE';

                            if ($shouldConsume && $initialStatus === 'ACTIVE' && !empty($needsByWcId)) {
                                // $consumeService->consumeManyOrFail($dateForDb, $needsByWcId, $totalsByWcId);
                            }

                            $history = HistoryWi::create([
                                'wi_document_code' => $documentCode,
                                'doc_prefix'       => $docPrefix,
                                'workcenter'       => $headerWc,
                                'plant_code'       => $plantCode,
                                'document_date'    => $dateForDb,
                                'document_time'    => $timeForDb,
                                'expired_at'       => $expiredAt,
                                'sequence_number'  => $nextNumber,
                                'status'           => $initialStatus,
                                'machining'        => $headerMachining ? 1 : 0,
                                'longshift'        => $headerLongshift ? 1 : 0,
                            ]);

                            return [$history, $documentCode];
                        }, 3);

                        break;
                    } catch (\Illuminate\Database\QueryException $qe) {
                        $sqlState = $qe->errorInfo[0] ?? null;
                        $errCode  = (int)($qe->errorInfo[1] ?? 0);

                        $isRetryable =
                            ($sqlState === '23000' && $errCode === 1062) ||
                            ($sqlState === 'HY000' && in_array($errCode, [1205, 1213], true));

                        if ($isRetryable && $attempt < $maxRetry) {
                            usleep(150000);
                            continue;
                        }

                        throw $qe;
                    }
                }

                if (!$history || !$documentCode) {
                    throw new \RuntimeException("Gagal membuat WI header setelah {$maxRetry} percobaan.");
                }

                // =========================================================
                // B) TRANSAKSI TERPISAH: insert items + pro
                // =========================================================
                try {
                    DB::transaction(function () use ($history, $items, $headerWc) {
                        $seen = [];

                        foreach ($items as $itemData) {
                            $nik   = $itemData['nik'] ?? null;
                            $aufnr = $itemData['aufnr'] ?? null;
                            $vornr = $itemData['vornr'] ?? null;

                            $k = trim((string)$aufnr) . '_' . trim((string)$vornr) . '_' . trim((string)$nik);
                            if (isset($seen[$k])) {
                                Log::warning("Duplicate item in same document (skipped): $k");
                                continue;
                            }
                            $seen[$k] = true;

                            $childWc  = $itemData['child_wc'] ?? ($itemData['child_workcenter'] ?? null);
                            $parentWc = $itemData['parent_wc'] ?? ($itemData['parent_workcenter'] ?? $headerWc);

                            $confirmedQty = (int)($itemData['confirmed_qty'] ?? ($itemData['qty_pro'] ?? 0));
                            $remarkQty    = (int)($itemData['remark_qty'] ?? 0);

                            $rawStatus = $itemData['status'] ?? ($itemData['stats'] ?? 'Open');
                            $dbStatus = $rawStatus;

                            if (!str_contains(strtoupper($rawStatus), 'COMPLETED')) {
                                $dbStatus = ($confirmedQty > 0 || $remarkQty > 0) ? 'PROGRESS' : 'CREATED';
                            }

                            $wiItem = HistoryWiItem::create([
                                'history_wi_id' => $history->id,
                                'nik'           => $nik,
                                'aufnr'         => $aufnr,
                                'vornr'         => $vornr,
                                'uom'           => $itemData['uom'] ?? null,
                                'operator_name' => $itemData['operator_name'] ?? ($itemData['name'] ?? null),
                                'dispo'         => $itemData['dispo'] ?? null,
                                'kapaz'         => $itemData['kapaz'] ?? null,
                                'kdauf'         => $itemData['kdauf'] ?? null,
                                'kdpos'         => $itemData['kdpos'] ?? null,
                                'name1'         => $itemData['name1'] ?? null,
                                'netpr'         => $itemData['netpr'] ?? 0,
                                'waerk'         => $itemData['waerk'] ?? null,
                                'ssavd'         => ($itemData['ssavd'] ?? '-') !== '-' ? $itemData['ssavd'] : null,
                                'sssld'         => ($itemData['sssld'] ?? '-') !== '-' ? $itemData['sssld'] : null,
                                'steus'         => $itemData['steus'] ?? null,
                                'vge01'         => $itemData['vge01'] ?? null,
                                'vgw01'         => $itemData['vgw01'] ?? 0,
                                'material_number' => $itemData['material_number'] ?? null,
                                'material_desc'   => $itemData['material_desc'] ?? null,
                                'qty_order'        => $itemData['qty_order'] ?? 0,
                                'assigned_qty'     => $itemData['assigned_qty'] ?? 0,
                                'parent_wc'        => $parentWc,
                                'child_wc'         => $childWc,
                                'status'           => $dbStatus,
                                'machining'        => !empty($itemData['is_machining']),
                                'longshift'        => !empty($itemData['is_longshift']),
                                'calculated_takt_time' => $itemData['calculated_takt_time'] ?? 0,
                                'stats'            => $itemData['stats'] ?? null,
                            ]);

                            $proEntries = $itemData['history_pro'] ?? ($itemData['pro_entries'] ?? null);

                            if (is_array($proEntries) && count($proEntries) > 0) {
                                foreach ($proEntries as $p) {
                                    HistoryPro::create([
                                        'history_wi_item_id' => $wiItem->id,
                                        'qty_pro'     => (int)($p['qty_pro'] ?? 0),
                                        'status'      => $p['status'] ?? null,
                                        'remark_text' => $p['remark_text'] ?? null,
                                        'tag'         => $p['tag'] ?? null,
                                    ]);
                                }
                            } else {
                                $tag = $itemData['tag'] ?? null;

                                $confirmedQty2 = (int)($itemData['confirmed_qty'] ?? ($itemData['qty_pro'] ?? 0));
                                if ($confirmedQty2 > 0) {
                                    HistoryPro::create([
                                        'history_wi_item_id' => $wiItem->id,
                                        'qty_pro'     => $confirmedQty2,
                                        'status'      => 'confirmasi',
                                        'remark_text' => null,
                                        'tag'         => $tag,
                                    ]);
                                }

                                $remarkText = $itemData['remark_text'] ?? ($itemData['remark'] ?? null);
                                if ($remarkText) {
                                    $remarkQty2 = (int)($itemData['remark_qty'] ?? 0);
                                    HistoryPro::create([
                                        'history_wi_item_id' => $wiItem->id,
                                        'qty_pro'     => $remarkQty2,
                                        'status'      => 'remark',
                                        'remark_text' => $remarkText,
                                        'tag'         => $tag,
                                    ]);
                                }
                            }
                        }
                    }, 3);

                } catch (\Throwable $inner) {
                    try {
                        HistoryWi::where('id', $history->id)->update(['status' => 'FAILED']);
                    } catch (\Throwable $ignored) {}

                    if ($shouldConsume && !empty($needsByWcId)) {
                        try {
                            /*
                            DB::transaction(function () use ($consumeService, $dateForDb, $needsByWcId) {
                                $consumeService->releaseMany($dateForDb, $needsByWcId);
                            }, 3);
                            */
                        } catch (\Throwable $ignored2) {}
                    }

                    throw $inner;
                }

                $wiDocuments[] = [
                    'workcenter'    => $headerWc,
                    'document_code' => $documentCode,
                ];
            }

            return response()->json([
                'message'   => 'Work Instructions berhasil disimpan.',
                'documents' => $wiDocuments,
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);

        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);

        } catch (\Throwable $e) {
            Log::error('Error saat menyimpan WI:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'payload' => $requestData,
            ]);

            return response()->json([
                'message'      => 'Terjadi kesalahan saat menyimpan Work Instructions.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }


    private function wiStartEnd($doc): array
    {
        $date = $doc->document_date instanceof CarbonInterface
            ? $doc->document_date->format('Y-m-d')
            : Carbon::parse($doc->document_date)->format('Y-m-d');

        // 1. MACHINING (Expired At is mandatory or covers ranges)
        if ((int)$doc->machining === 1) {
            $start = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            $end   = $doc->expired_at ? Carbon::parse($doc->expired_at)->endOfDay() : $start->copy()->endOfDay();
            return [$start, $end];
        }

        // 2. LONGSHIFT
        if ((int)$doc->longshift === 1) {
            $start = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            // Valid for 2 days (Today & Tomorrow) OR until expired_at
            // If expired_at is set, use it. Else default to start + 1 day end (approx 48h from date 00:00)
            $end = $doc->expired_at 
                ? Carbon::parse($doc->expired_at)->endOfDay() 
                : $start->copy()->addDay()->endOfDay();
            return [$start, $end];
        }

        // 3. NORMAL
        $time = $doc->document_time instanceof CarbonInterface
            ? $doc->document_time->format('H:i:s')
            : trim((string)($doc->document_time ?? ''));

        if ($time === '') $time = '00:00:00';
        if (preg_match('/^\d{2}:\d{2}$/', $time)) $time .= ':00';

        $start = Carbon::createFromFormat('Y-m-d H:i:s', "{$date} {$time}");
        $end   = $doc->expired_at 
            ? Carbon::parse($doc->expired_at) 
            : $start->copy()->addHours(24);

        return [$start, $end];
    }

    public function history(Request $request, $kode)
    {
        $plantCode = $kode;
        $nama_bagian = KodeLaravel::where('laravel_code', $plantCode)->first();
        $now   = Carbon::now();
        $today = Carbon::today();
        $query = HistoryWi::where('plant_code', $plantCode);

        // Cek Dokumen INACTIVE -> ACTIVE
        $inactives = HistoryWi::where('plant_code', $plantCode)
            ->where('status', 'INACTIVE')
            ->get();

        foreach ($inactives as $doc) {
            [$start, $end] = $this->wiStartEnd($doc);
            
            if ($now->greaterThanOrEqualTo($start)) {
                $doc->update(['status' => 'ACTIVE']);
                HistoryWiItem::where('history_wi_id', $doc->id)->update(['status' => 'ACTIVE']);
            }
        }

        if ($request->filled('date')) {
            $dateInput = $request->date;
            $start = null; 
            $end = null;

            if (strpos($dateInput, ' to ') !== false) {
                $dates = explode(' to ', $dateInput);
                if (count($dates) == 2) {
                    $start = Carbon::parse($dates[0])->startOfDay();
                    $end   = Carbon::parse($dates[1])->endOfDay();
                } elseif (count($dates) == 1) {
                    $start = Carbon::parse($dates[0])->startOfDay();
                    $end   = Carbon::parse($dates[0])->endOfDay();
                }
            } else {
                $start = Carbon::parse($dateInput)->startOfDay();
                $end   = Carbon::parse($dateInput)->endOfDay();
            }

            if ($start && $end) {
                $query->where(function($q) use ($start, $end) {
                    // 1. Has Expired At (Includes Machining & Explicit Normal/Longshift)
                    // Valid if ANY overlap between [DocDate, ExpiredAt] and [FilStart, FilEnd]
                    // Overlap: (DocStart <= FilEnd) AND (DocEnd >= FilStart)
                    // Using dates:
                    $q->where(function($sub) use ($start, $end) {
                        $sub->whereNotNull('expired_at')
                            ->whereDate('document_date', '<=', $end)
                            ->whereDate('expired_at', '>=', $start);
                    })
                    // 2. Longshift (No Expired At -> Implied 2 Days: Document Date & Next Day)
                    // Valid if DocDate is in [Start-1, End] (Since DocDate+1 is valid on Start)
                    ->orWhere(function($sub) use ($start, $end) {
                        $sub->whereNull('expired_at')
                            ->where('longshift', 1)
                            ->whereDate('document_date', '>=', $start->copy()->subDay())
                            ->whereDate('document_date', '<=', $end);
                    })
                    // 3. Normal (No Expired At -> Implied 24 Hours from DocDate+Time)
                    // Effectively treated as "Active on Document Date" for simpler filtering
                    // OR strictly overlap logic if needed, but usually DocDate match is enough for list view
                    ->orWhere(function($sub) use ($start, $end) {
                        $sub->whereNull('expired_at')
                            ->where(function($n) {
                                $n->where('longshift', '!=', 1)
                                  ->orWhereNull('longshift');
                            })
                            // For normal docs without expiry, we assume they are relevant on their document_date
                            ->whereBetween('document_date', [$start, $end]);
                    });
                });
            }
        } else {
            // Default View: Recent Docs OR Currently Active/Future Docs (like Machining/Longshift)
            $query->where(function($q) use ($today) {
                // 1. Created recently (last 7 days)
                $q->whereDate('document_date', '>=', $today->copy()->subDays(7))
                // 2. OR still active/valid today (or in future)
                  ->orWhereDate('expired_at', '>=', $today);
            });
        }

        if ($request->filled('search_nik')) {
            $nik = $request->search_nik;
            $query->whereHas('items', function($q) use ($nik) {
                $q->where('nik', 'like', "%{$nik}%");
            });
        }

        if ($request->filled('multi_search')) {
            $raw = $request->input('multi_search');
            $keywords = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
            
            if (count($keywords) > 0) {
                $query->where(function($q) use ($keywords) {
                    $q->whereIn('wi_document_code', $keywords)
                      ->orWhereHas('items', function($q2) use ($keywords) {
                          $q2->whereIn('aufnr', $keywords)
                             ->orWhereIn('nik', $keywords);
                      });
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('wi_document_code', 'like', "%{$search}%")
                ->orWhere('workcenter', 'like', "%{$search}%")
                ->orWhereHas('items', function($q2) use ($search) {
                    $q2->where('aufnr', 'like', "%{$search}%")
                        ->orWhere('material_desc', 'like', "%{$search}%")
                        ->orWhere('operator_name', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%");
                })
                ->orWhereHas('items.pros', function($q3) use ($search) {
                    $q3->where('remark_text', 'like', "%{$search}%")
                        ->orWhere('tag', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('workcenter') && $request->workcenter !== 'all') {
            $query->where('workcenter', $request->workcenter);
        }

        $wiDocuments = $query->with(['items.pros'])
            ->orderBy('document_date', 'desc')
            ->orderByRaw('CASE WHEN document_time IS NULL THEN 1 ELSE 0 END')
            ->orderBy('document_time', 'desc')
            ->get();

        $activeWIDocuments    = collect();
        $inactiveWIDocuments  = collect();
        $expiredWIDocuments   = collect();
        $completedWIDocuments = collect();
        $workcenterMappings = WorkcenterMapping::with(['parentWorkcenter', 'childWorkcenter'])
            ->where('kode_laravel_id', $nama_bagian ? $nama_bagian->id : null)
            ->get();

        $sapPlant = $nama_bagian ? $nama_bagian->plant : $plantCode;

        $wcNames = workcenter::where('plant', $sapPlant)
            ->get()
            ->mapWithKeys(function ($item) {
                 return [strtoupper($item->kode_wc) => $item->description];
            })
            ->toArray();

        $childWorkcenters = workcenter::where('plant', $sapPlant)
            ->get()
            ->mapWithKeys(fn($item) => [strtoupper($item->kode_wc) => $item]);

        $allWcCodes = $workcenterMappings->flatMap(function($m) {
            $codes = [];
            if ($m->parentWorkcenter) $codes[] = $m->parentWorkcenter->kode_wc;
            if ($m->childWorkcenter)  $codes[] = $m->childWorkcenter->kode_wc;
            return $codes;
        })->filter()->unique()->map(fn($code) => strtoupper($code));

        $rawWcData = workcenter::where('plant', $plantCode)->get();
        $wcCapacityMap = [];
        foreach ($rawWcData as $rw) {
            $code = strtoupper($rw->kode_wc);
            $kapazHours = floatval(str_replace(',', '.', $rw->kapaz ?? 0));
            $wcCapacityMap[$code] = $kapazHours * 60; // minutes
        }

        $workcenters = collect();
        foreach ($allWcCodes as $wcCode) {
            $children = $workcenterMappings->filter(function($m) use ($wcCode) {
                return $m->parentWorkcenter
                    && strtoupper($m->parentWorkcenter->kode_wc) === $wcCode
                    && $m->childWorkcenter
                    && strtoupper($m->childWorkcenter->kode_wc) !== $wcCode;
            });

            if ($children->isNotEmpty()) {
                $totalCap = 0;
                foreach ($children as $child) {
                    $cCode = strtoupper($child->childWorkcenter->kode_wc);
                    $totalCap += ($wcCapacityMap[$cCode] ?? 0);
                }
                $finalCap = $totalCap;
            } else {
                $finalCap = $wcCapacityMap[$wcCode] ?? 0;
            }

            $workcenters->push((object) [
                'workcenter_code' => $wcCode,
                'kapaz' => $finalCap,
                'raw_unit' => 'MIN'
            ]);
        }

        $concurrentUsageMap = [];
        foreach ($wiDocuments as $doc) {
            [$start, $end] = $this->wiStartEnd($doc);

            $isExpired = $now->greaterThan($end);
            $doc->is_expired = $isExpired;

            if (!$isExpired && $end->greaterThanOrEqualTo($today)) {
                foreach (($doc->items ?? collect()) as $plItem) {
                    $k = ($plItem->aufnr ?? '-') . '_' . ($plItem->vornr ?? '-');
                    if (!isset($concurrentUsageMap[$k])) $concurrentUsageMap[$k] = 0;
                    $concurrentUsageMap[$k] += (float)($plItem->assigned_qty ?? 0);
                }
            }
        }

        $allAufnrs = [];
        foreach ($wiDocuments as $doc) {
            foreach (($doc->items ?? collect()) as $it) {
                if (!empty($it->aufnr)) $allAufnrs[] = $it->aufnr;
            }
        }
        $allAufnrs = array_values(array_unique($allAufnrs));

        $prodDataMap1 = [];
        $prodDataMap3 = [];
        if (!empty($allAufnrs)) {
            $prodDataMap1 = ProductionTData1::whereIn('AUFNR', $allAufnrs)->get()->keyBy('AUFNR');
            $prodDataMap3 = ProductionTData3::whereIn('AUFNR', $allAufnrs)->get()->keyBy('AUFNR');
        }

        foreach ($wiDocuments as $doc) {
            [$start, $end] = $this->wiStartEnd($doc);

            $doc->is_expired  = $now->greaterThan($end);
            $doc->is_inactive = !$doc->is_expired && $now->lessThan($start);
            $doc->is_active   = !$doc->is_expired && $now->betweenIncluded($start, $end);

            $items = $doc->items ?? collect();

            $summary = [
                'total_items' => 0,
                'total_load_mins' => 0,
                'details' => []
            ];

            $isFullyCompleted = $items->isNotEmpty();

            foreach ($items as $item) {
                $summary['total_items']++;

                $assignedQty = (float)($item->assigned_qty ?? 0);
                $pros = $item->pros ?? collect();
                $confirmedQty = (float)$pros->filter(function($p){
                    $s = strtolower(trim($p->status ?? ''));
                    return in_array($s, ['confirmation', 'confirm', 'confirmed', 'confirmasi', 'konfirmasi']);
                })->sum('qty_pro');

                $remarkQty = (float)$pros->filter(function($p){
                    return str_contains(strtolower((string)($p->status ?? '')), 'remark');
                })->sum('qty_pro');

                $allRemarks = $pros->filter(function($p){
                    return str_contains(strtolower((string)($p->status ?? '')), 'remark');
                })->sortByDesc('created_at'); // Newest first

                $remarkHistory = $allRemarks->map(function($r){
                    return [
                        'qty' => $r->qty_pro,
                        'remark_text' => $r->remark_text,
                        'tag' => $r->tag,
                        'created_at' => $r->created_at
                    ];
                })->values()->toArray();

                $totalDone = $confirmedQty + $remarkQty;
                $remainingQty = max(0, $assignedQty - $totalDone);

                $auf = $item->aufnr ?? '';
                $prodData = $prodDataMap1[$auf] ?? null;
                if (!$prodData) $prodData = $prodDataMap3[$auf] ?? null;
                $sapTotal = $prodData ? floatval($prodData->MGVRG2) : (float)($item->qty_order ?? $assignedQty);
                $sapConfirmed = $prodData ? floatval($prodData->LMNGA) : 0;
                $fullOrderQty = max(0, $sapTotal - $sapConfirmed);
                $key = ($item->aufnr ?? '-') . '_' . ($item->vornr ?? '-');
                $totalConcurrentUsage = $concurrentUsageMap[$key] ?? 0;
                $isUsedInMap = !$doc->is_expired && $end->greaterThanOrEqualTo($today);
                $usageByOthers = $isUsedInMap ? max(0, $totalConcurrentUsage - $assignedQty) : $totalConcurrentUsage;
                $effectiveMax = max(0, $fullOrderQty - $usageByOthers);
                $qtyOrderRaw = $effectiveMax;
                $takTime = (float)($item->calculated_takt_time ?? 0);
                $summary['total_load_mins'] += $takTime;
                $progressPct = $assignedQty > 0 ? ($totalDone / $assignedQty) * 100 : 0;
                if ($progressPct >= 100) $statusItem = 'Completed';
                elseif ($totalDone > 0)  $statusItem = 'On Progress';
                else                     $statusItem = 'Created';
                $t3Data = $prodDataMap3[$auf] ?? null;
                $kdaufRaw = $t3Data->KDAUF ?? ($item->kdauf ?? '-');
                $kdposRaw = $t3Data->KDPOS ?? ($item->kdpos ?? '-');
                $kdposDisplay = ($kdposRaw !== '-' && $kdposRaw !== '') ? ltrim($kdposRaw, '0') : $kdposRaw;

                $summary['details'][] = [
                    'aufnr'         => $item->aufnr ?? '-',
                    'kdauf'         => $kdaufRaw,
                    'kdpos'         => $kdposDisplay,
                    'material'      => $item->material_desc ?? '-',
                    'nik'           => $item->nik ?? '-',
                    'name'          => $item->operator_name ?? '-',
                    'vornr'         => $item->vornr ?? '-',
                    'description'   => $item->material_desc ?? '',
                    'assigned_qty'  => $assignedQty,
                    'remaining_qty' => $remainingQty, // Added remaining_qty
                    'confirmed_qty' => $confirmedQty,
                    'qty_order'     => $qtyOrderRaw,
                    'uom'           => $item->uom ?? 'EA',
                    'progress_pct'  => $progressPct,
                    'status'        => $statusItem,
                    'item_mins'     => $takTime,
                    'remark'        => $latestRemark->remark_text ?? null,
                    'remark_qty'    => $remarkQty,
                    'remark_history'=> $remarkHistory, // Added remark_history
                    'vgw01'         => $item->vgw01 ?? 0,
                    'vge01'         => $item->vge01 ?? '',
                    'machining'     => (int)($item->machining ?? 0),
                    'longshift'     => (int)($item->longshift ?? 0),    
                ];

                if ($totalDone < $assignedQty) $isFullyCompleted = false;
            }

            $targetWcCode = strtoupper($doc->workcenter ?? '');
            $maxMins = 0;

            $childrenOfThisWc = $workcenterMappings->filter(function($m) use ($targetWcCode) {
                return $m->parentWorkcenter
                    && strtoupper($m->parentWorkcenter->kode_wc) === $targetWcCode
                    && $m->childWorkcenter
                    && strtoupper($m->childWorkcenter->kode_wc) !== $targetWcCode;
            });

            if ($childrenOfThisWc->count() > 0) {
                foreach ($childrenOfThisWc as $child) {
                    $cCode = strtoupper($child->childWorkcenter->kode_wc);
                    $cap = $wcCapacityMap[$cCode] ?? 0;
                    if ($cap == 0) $cap = 570; // fallback
                    $maxMins += $cap;
                }
            } else {
                $maxMins = $wcCapacityMap[$targetWcCode] ?? 0;
                if ($maxMins == 0) $maxMins = 570; // fallback
            }

            $percentageLoad = $maxMins > 0 ? ($summary['total_load_mins'] / $maxMins) * 100 : 0;

            $doc->capacity_info = [
                'max_mins'   => $maxMins,
                'used_mins'  => $summary['total_load_mins'],
                'percentage' => $percentageLoad
            ];

            usort($summary['details'], function($a, $b) {
                return strnatcmp($a['nik'] ?? '', $b['nik'] ?? '');
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
            $wiCapacityMap[$doc->wi_document_code] = $doc->capacity_info ?? ['max_mins' => 0, 'used_mins' => 0];
        }

        $aggregatedCapacities = [];
        foreach ($activeWIDocuments as $doc) {
            $wcCode = $doc->workcenter ?? '';
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

        foreach ($aggregatedCapacities as &$cap) {
            $cap['percentage'] = $cap['max_mins'] > 0 ? ($cap['used_mins'] / $cap['max_mins']) * 100 : 0;
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
            'employees' => [],
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
                'kmi3.58.smg@gmail.com',
                'kmi3.57.smg@gmail.com',
                'kmi3.2.smg@gmail.com',
                'kmi3.1.smg@gmail.com'
            ]
        ]);
    }

    public function updateQty(Request $request)
    {
        $validated = $request->validate([
            'wi_code' => 'required|string',
            'aufnr'   => 'required|string',
            'nik'     => 'required|string',
            'vornr'   => 'required|string',
            'new_qty' => 'required|numeric|min:0',
        ]);

        try {
            $doc = HistoryWi::where('wi_document_code', $validated['wi_code'])->firstOrFail();

            $item = HistoryWiItem::where('history_wi_id', $doc->id)
                ->where('aufnr', $validated['aufnr'])
                ->where('nik', $validated['nik'])
                ->where('vornr', $validated['vornr'])
                ->first();

            if (!$item) {
                return back()->with('error', 'Item tidak ditemukan (AUFNR+NIK+VORNR tidak match).');
            }

            $newQty = (float) $validated['new_qty'];

            // Recalc takt time (minutes)
            $vgw01 = (float) ($item->vgw01 ?? 0);
            $unit  = strtoupper((string) ($item->vge01 ?? 'MIN'));

            $mins = $vgw01 * $newQty;
            if (in_array($unit, ['S','SEC'], true)) $mins = $mins / 60;
            elseif (in_array($unit, ['H','HUR'], true)) $mins = $mins * 60;

            $item->assigned_qty = $newQty;
            $item->calculated_takt_time = $mins; // IMPORTANT: pakai field yang benar
            $item->save();

            $label = $item->material_desc ?? $item->aufnr;
            return back()->with('success', "Qty {$label} diupdate menjadi {$newQty}.");

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
                    if ($dates[0] === $dates[1]) {
                         $query->where(function($q) use ($dates) {
                             $q->whereDate('document_date', $dates[0])
                               ->orWhereDate('expired_at', $dates[0]);
                         });
                         $dateInfo = Carbon::parse($dates[0])->format('d-m-Y');
                    } else {
                         $query->where(function($q) use ($dates) {
                            $q->whereBetween('document_date', [$dates[0], $dates[1]])
                              ->orWhereBetween('expired_at', [$dates[0], $dates[1]]);
                         });
                         $start = Carbon::parse($dates[0])->format('d-m-Y');
                         $end = Carbon::parse($dates[1])->format('d-m-Y');
                         $dateInfo = "$start s/d $end";
                    }
                } else {
                    $query->where(function($q) use ($dates) {
                         $q->whereDate('document_date', $dates[0])
                           ->orWhereDate('expired_at', $dates[0]);
                    });
                    $dateInfo = Carbon::parse($dates[0])->format('d-m-Y');
                }
            } else {
                $query->where(function($q) use ($date) {
                     $q->whereDate('document_date', $date)
                       ->orWhereDate('expired_at', $date);
                });
                $dateInfo = Carbon::parse($date)->format('d-m-Y');
            }
        }
        if ($search) {
             $query->where(function($q) use ($search) {
                $q->where('wi_document_code', 'like', "%$search%");
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
        $kodeModel = KodeLaravel::where('laravel_code', $plantCode)->first();

        $kodeModel = KodeLaravel::where('laravel_code', $plantCode)->first();
        $namaBagian = $kodeModel ? $kodeModel->description : '-';
        // Sanitize to remove non-printable characters or weird encoding
        $namaBagian = preg_replace('/[^\x20-\x7E]/', '', $namaBagian);

        // Fetch Workcenter Descriptions
        // Fetch Workcenter Descriptions (Global & With Trashed to ensure match)
        $wcDescriptions = \App\Models\workcenter::withTrashed()
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->pluck('description', 'kode_wc')
            ->mapWithKeys(fn($item, $key) => [strtoupper(trim($key)) => $item])
            ->toArray();

        $allProcessedItems = [];
        $finalReports = [];
        $statusFilter = $request->input('filter_status');
        
        // Loop Each Doc
        foreach($documents as $doc) {
            $docItems = [];
            // Refactored: Use relationship instead of payload_data
            foreach ($doc->items as $item) {
                // Item Processing Logic
                $wc = !empty($item->child_wc) ? $item->child_wc : ($item->parent_wc ?? '-');
                $matnr = $item->material_number ?? '';
                if(ctype_digit($matnr)) { $matnr = ltrim($matnr, '0'); }

                $assigned = floatval($item->assigned_qty ?? 0);
                
                // Calculate confirmed and remark from PROS
                $confirmed = 0;
                $remarkQty = 0;
                $remarkTexts = [];
                $remarkDetails = [];
                
                foreach ($item->pros as $pro) {
                    $st = strtolower($pro->status ?? '');
                    if (in_array($st, ['confirmation', 'confirm', 'confirmed', 'confirmasi', 'konfirmasi'])) {
                        $confirmed += $pro->qty_pro;
                    } elseif (str_contains($st, 'remark')) {
                        $remarkQty += $pro->qty_pro;
                        $rText = $pro->remark_text;
                        $rTag = $pro->tag;
                        
                        // Effective Remark: Text -> Tag -> '-'
                        $effectiveRemark = $rText;
                        if (empty($effectiveRemark)) {
                            $effectiveRemark = $rTag;
                        }
                        if (empty($effectiveRemark)) {
                            $effectiveRemark = '-';
                        }
                        
                        // Populate Texts array for top-level summary if needed
                        if (!empty($rText)) {
                            $remarkTexts[] = $rText;
                        } elseif (!empty($rTag)) {
                             $remarkTexts[] = $rTag; // Include tag in summary if text missing? Optional, but safer.
                        }
                        
                        $remarkDetails[] = [
                            'qty' => $pro->qty_pro,
                            'remark' => $effectiveRemark, // For Expired Tab
                            'remark_text' => $rText,      // For Active Tab
                            'tag' => $rTag
                        ];
                    }
                }
                $netpr = floatval($item->netpr ?? 0);
                $waerk = $item->waerk ?? '';
                
                 // Remark Data
                $remarkText = !empty($remarkTexts) ? implode("\n", $remarkTexts) : '-';

                $confirmedPrice = $netpr * $confirmed;
                $balance = $assigned - ($confirmed + $remarkQty);
                $failedPrice = $netpr * ($balance + $remarkQty);

                $hasRemark = ($remarkQty > 0 || ($remarkText !== '-' && !empty($remarkText)));
                
                $expiredAt = $doc->expired_at ? Carbon::parse($doc->expired_at) : null;
                $isDocExpired = $expiredAt && now()->gt($expiredAt);
                
                if (!empty($item->status)) {
                    $status = strtoupper($item->status);
                     // Optional: Map REL to ACTIVE for consistency if needed, but user asked for DB values.
                     // We will stick to DB values. 
                } elseif ($balance <= 0) {
                    $status = 'COMPLETED'; 
                } elseif ($hasRemark) {
                    $status = 'NOT COMPLETED WITH REMARK';
                } elseif ($isDocExpired) {
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
                }
                
                if (!$keep && $request->has('wi_codes')) {
                    // Re-evaluate for Manual Print
                    $keep = true; 
                }

                if (!$keep) continue;

                // Rest of Fields
                $kdauf = $item->kdauf ?? '-';
                $kdpos = $item->kdpos ? ltrim((string)$item->kdpos, '0') : '-';
                
                if (trim(strtoupper($kdauf)) === 'MAKE STOCK') {
                    $soItem = 'MAKE STOCK';
                } else {
                    $soItem = ($kdauf !== '-' && $kdpos !== '-') ? "{$kdauf}-{$kdpos}" : '-';
                }

                // Takt Time
                // Takt Time (Changed to ASSIGNED Qty as requested)
                $vgw01 = floatval($item->vgw01 ?? 0);
                $vge01 = strtoupper(trim($item->vge01 ?? ''));
                
                // 1. Planned Time (Assigned) - "Time" Column & "Jam Kerja" Denominator
                $baseMins = $vgw01 * $assigned; 

                if (in_array($vge01, ['S', 'SEC'])) {
                    $baseMins = $baseMins / 60;
                } elseif (in_array($vge01, ['H', 'HUR', 'HR'])) {
                    $baseMins = $baseMins * 60;
                }
                
                $finalTime = $baseMins;
                $taktFull = $finalTime;

                 // 2. Confirmed Time - "Jam Kerja" Numerator
                $confBaseMins = $vgw01 * $confirmed;
                if (in_array($vge01, ['S', 'SEC'])) {
                    $confBaseMins = $confBaseMins / 60;
                } elseif (in_array($vge01, ['H', 'HUR', 'HR'])) {
                    $confBaseMins = $confBaseMins * 60;
                }

                // Format Individual Takt Time (Planned)
                if ($finalTime > 0) {
                     $totSec = $finalTime * 60;
                     $hrs = floor($totSec / 3600);
                     $mins = floor(($totSec % 3600) / 60);
                     $secs = round($totSec % 60);
                     
                     $parts = [];
                     if ($hrs > 0) $parts[] = $hrs . ' Jam';
                     if ($mins > 0) $parts[] = $mins . ' Menit';
                     if ($secs > 0 || empty($parts)) $parts[] = $secs . ' Detik';
                     
                     $taktFull = implode(', ', $parts);
                } else {
                     $taktFull = '-';
                }
                
                 // Format Prices
                 if (strtoupper($waerk) === 'USD') {
                    $prefixInfo = '$ ';
                } elseif (strtoupper($waerk) === 'IDR') {
                    $prefixInfo = 'Rp ';
                } else {
                    $prefixInfo = !empty($waerk) ? strtoupper($waerk) . ' ' : '';
                }
                
                $priceFormatted = $prefixInfo . number_format($confirmedPrice, (strtoupper($waerk) === 'USD' ? 2 : 0), ',', '.');
                $failedPriceFormatted = $prefixInfo . number_format($failedPrice, (strtoupper($waerk) === 'USD' ? 2 : 0), ',', '.');
                
                $processedItem = [
                    'doc_no'        => $doc->wi_document_code,
                    'created_at'    => $doc->created_at,
                    'expired_at'    => $expiredAt->format('m-d H:i'),
                    'workcenter'    => $wc,
                    'wc_description'=> $wcDescriptions[strtoupper(trim($wc))] ?? '-', // Populated via lookup
                    'aufnr'         => $item->aufnr ?? '-',
                    'vornr'         => $item->vornr ?? '', 
                    'material'      => $matnr,
                    'description'   => $item->material_desc ?? '-',
                    'assigned'      => $assigned,
                    'confirmed'     => $confirmed,
                    'balance'       => $balance,
                    'remark_qty'    => $remarkQty,
                    'remark_text'   => $remarkText,
                    'remark_text'   => $remarkText,
                    'remark_history'=> $remarkDetails, // Mapped for Blade Loop
                    'remark_details'=> $remarkDetails, // Backup/Duplicate just in case
                    'price_formatted' => $priceFormatted,
                    'confirmed_price' => $confirmedPrice, 
                    'failed_price'    => $failedPrice,
                    'price_ok_fmt'    => $priceFormatted,       
                    'price_fail_fmt'  => $failedPriceFormatted,   
                    'currency'        => strtoupper($waerk),
                    'buyer'           => $item->buyer_name ?? '-', // Check model attribute
                    'nik'             => $item->nik ?? '-',
                    'name'            => $item->operator_name ?? '-',
                    'status'          => $status,
                    'so_item'         => $soItem,
                    'takt_time'       => $taktFull,
                    'raw_total_time'  => $finalTime,
                    'raw_confirmed_time' => $confBaseMins, // NEW FIELD
                    'is_machining'    => (int)($doc->machining ?? 0) === 1, // NEW FIELD
                    'item_progress_numerator' => ($confirmed + $remarkQty),
                    'item_progress_pct' => ($assigned > 0) ? round((($confirmed + $remarkQty) / $assigned) * 100) : 0,
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
                $totalFailed = $totalAssigned - $totalConfirmed; 
                $totalRemarkQty = collect($sortedItems)->sum('remark_qty');
                $achievement = ($totalAssigned > 0) ? round(($totalConfirmed / $totalAssigned) * 100) . '%' : '0%';
                
                $totalConfirmedPrice = collect($sortedItems)->sum('confirmed_price');
                $totalFailedPrice = collect($sortedItems)->sum('failed_price');
                $totalAssignedPrice = $totalConfirmedPrice + $totalFailedPrice;

                $curr = $sortedItems[0]['currency'] ?? 'IDR';
                $pfx = ($curr === 'USD') ? '$ ' : 'Rp ';
                $dec = ($curr === 'USD') ? 2 : 0;

                $wcKendalaArr = collect($sortedItems)
                    ->filter(fn($i) => ($i['remark_qty'] ?? 0) > 0)
                    ->pluck('workcenter')
                    ->unique()
                    ->filter()
                    ->values()
                    ->all();

                $finalReports[] = [
                    'report_title' => 'WORK INSTRUCTION SHEET', 
                    'items' => $sortedItems,
                    'summary' => [
                        'total_assigned' => $totalAssigned,
                        'total_confirmed' => $totalConfirmed,
                        'total_failed' => $totalFailed,
                        'total_remark_qty' => $totalRemarkQty,
                        'achievement_rate' => $achievement,
                        'wc_kendala' => empty($wcKendalaArr) ? '-' : implode(', ', $wcKendalaArr),
                        'total_price_assigned_raw' => $totalAssignedPrice,
                        'total_price_assigned' => $pfx . number_format($totalAssignedPrice, $dec, ',', '.'),
                        'total_price_ok_raw' => $totalConfirmedPrice,
                        'total_price_ok' => $pfx . number_format($totalConfirmedPrice, $dec, ',', '.'),
                        'total_price_fail_raw' => $totalFailedPrice,
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
                    return sprintf('%s_%s', $item['nik'] ?? '', $item['workcenter'] ?? '');
                } else {
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
            $totalAssignedPrice = $totalConfirmedPrice + $totalFailedPrice;

            $achievement = $totalAssigned > 0 ? round(($totalConfirmed / $totalAssigned) * 100) . '%' : '0%';

            $firstCurrency = collect($sortedItems)->first()['currency'] ?? '';
            $prefix = (strtoupper($firstCurrency) === 'USD') ? '$ ' : 'Rp ';
            $decimal = (strtoupper($firstCurrency) === 'USD') ? 2 : 0;

            $wcKendalaArr = collect($sortedItems)
                 ->filter(fn($i) => ($i['remark_qty'] ?? 0) > 0)
                 ->pluck('workcenter')
                 ->unique()
                 ->filter()
                 ->values()
                 ->all();

            // Calculate Aggregate Status
            $uniqueStatuses = collect($sortedItems)->pluck('status')->unique();
            $aggStatus = 'ACTIVE';
            
            if ($uniqueStatuses->contains('ACTIVE')) {
                $aggStatus = 'ACTIVE';
            } elseif ($uniqueStatuses->contains('INACTIVE')) {
                 $aggStatus = 'INACTIVE';
            } elseif ($uniqueStatuses->contains('NOT COMPLETED') || $uniqueStatuses->contains('NOT COMPLETED WITH REMARK')) {
                $aggStatus = 'EXPIRED';
            } elseif ($uniqueStatuses->contains('COMPLETED')) {
                $aggStatus = 'COMPLETED';
            }

            $reportData = [
                'report_title' => 'DAILY REPORT',
                'items' => $sortedItems,
                'summary' => [
                    'total_assigned' => $totalAssigned,
                    'total_confirmed' => $totalConfirmed,
                    'total_failed' => $totalFailed,
                    'total_remark_qty' => $totalRemarkQty,
                    'achievement_rate' => $achievement,
                    'wc_kendala' => empty($wcKendalaArr) ? '-' : implode(', ', $wcKendalaArr),
                    'total_price_assigned_raw' => $totalAssignedPrice,
                    'total_price_assigned' => $prefix . number_format($totalAssignedPrice, $decimal, ',', '.'),
                    'total_price_ok_raw' => $totalConfirmedPrice,
                    'total_price_ok' => $prefix . number_format($totalConfirmedPrice, $decimal, ',', '.'),
                    'total_price_fail_raw' => $totalFailedPrice,
                    'total_price_fail' => $prefix . number_format($totalFailedPrice, $decimal, ',', '.')
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
                                      (Carbon::parse($documents->min('expired_at'))->format('Y-m-d') === Carbon::parse($documents->max('expired_at'))->format('Y-m-d'))
                                      ? Carbon::parse($documents->first()->expired_at)->format('d-M-Y H:i')
                                      : Carbon::parse($documents->min('expired_at'))->format('d-M-Y') . ' - ' . Carbon::parse($documents->max('expired_at'))->format('d-M-Y')
                                   ) : '-',
                ]
            ];
            
            $finalReports = [$reportData];
        }

        if (empty($finalReports)) {
             return ['success' => false, 'message' => 'Document Tidak Ditemukan (Empty Result)'];
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
                
                if (trim(strtoupper($kdauf)) === 'MAKE STOCK') {
                    $soItem = 'MAKE STOCK';
                } else {
                    $soItem = $kdauf . '-' . $kdpos;
                }
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
                } elseif ($request->input('status_override') === 'INACTIVE') {
                    $status = 'INACTIVE';
                } elseif ($docDate->gt($today)) {
                    $status = 'INACTIVE';
                } elseif (isset($doc->status) && strtoupper($doc->status) === 'INACTIVE') {
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
        $documents = HistoryWi::with(['items.pros'])
                    ->whereIn('wi_document_code', $wiCodes)
                    ->where('expired_at', '>', now()) 
                    ->get();

        if ($documents->isEmpty()) {
            return back()->with('error', 'Dokumen tidak ditemukan atau sudah expired.');
        }
        $data = [
            'documents' => $documents,
            'printedBy' => $request->input('printed_by'),
            'printedBy' => $request->input('printed_by'),
            'department' => preg_replace('/[^\x20-\x7E]/', '', str_replace(['–', '—'], '-', $request->input('department'))),
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
        $documents = HistoryWi::with(['items.pros'])->whereIn('wi_document_code', $wiCodes)->get();
        
        // Fetch Plant Code for WC Lookup
        $plantCode = $documents->first() ? $documents->first()->plant_code : null;
        $wcDescriptions = [];
        if ($plantCode) {
            $wcDescriptions = \App\Models\workcenter::where('plant', $plantCode)
                ->pluck('description', 'kode_wc')
                ->mapWithKeys(fn($item, $key) => [strtoupper(trim($key)) => $item])
                ->toArray();
        }

        $reportItems = [];
        $grandTotalAssigned = 0;
        $grandTotalConfirmed = 0;

        foreach ($documents as $doc) {
            foreach ($doc->items as $item) {
                // Data Dasar
                $matnr = isset($item->material_number) && ctype_digit($item->material_number)
                        ? ltrim($item->material_number, '0') 
                        : ($item->material_number ?? '');
                        
                $wcCode = !empty($item->child_wc) ? $item->child_wc : ($item->parent_wc ?? '-');
                $wcDesc = $wcDescriptions[strtoupper($wcCode)] ?? '';
                $wcDisplay = $wcCode . ($wcDesc ? ' - ' . substr($wcDesc, 0, 15) : ''); // Shorten desc

                $assigned = floatval($item->assigned_qty ?? 0);
                
                // Calculate from PROS
                $confirmed = 0;
                $remarkQty = 0;
                $remarkTexts = [];
                
                foreach ($item->pros as $pro) {
                    $st = strtolower($pro->status ?? '');
                    if (in_array($st, ['confirmation', 'confirm', 'confirmed'])) {
                        $confirmed += $pro->qty_pro;
                    } elseif ($st === 'remark') {
                        $remarkQty += $pro->qty_pro;
                        if (!empty($pro->remark_text)) {
                            $remarkTexts[] = $pro->remark_text;
                        }
                    }
                }
                
                $balance = $assigned - ($confirmed + $remarkQty);
                $grandTotalAssigned += $assigned;
                $grandTotalConfirmed += $confirmed;
                
                $remarkTextStr = !empty($remarkTexts) ? implode("\n", $remarkTexts) : '-';

                $reportItems[] = [
                    'wi_code'     => $doc->wi_document_code,
                    'nik'         => $item->nik ?? '-',
                    'name'        => $item->operator_name ?? '-',
                    'expired_at'  => $doc->expired_at,
                    'workcenter'  => $wcDisplay,
                    'aufnr'       => $item->aufnr ?? '-',
                    'material'    => $matnr,
                    'description' => $item->material_desc ?? '-',
                    'assigned'    => $assigned,
                    'confirmed'   => $confirmed,
                    'balance'     => $balance,
                    'remark_qty'  => $remarkQty,
                    'remark_text' => $remarkTextStr,
                    'status'      => ($balance <= 0.001 && $remarkQty == 0) ? 'COMPLETED' : 
                                     (($remarkQty > 0 || ($remarkTextStr !== '-' && !empty($remarkTextStr))) ? 'NOT COMPLETED WITH REMARK' : 'NOT COMPLETED')
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
        $documents = HistoryWi::with(['items.pros'])->whereIn('wi_document_code', $wiCodes)->get();
        
        $reportItems = [];
        $grandTotalAssigned = 0;
        $grandTotalConfirmed = 0;
        // ...
        
        foreach ($documents as $doc) {
            foreach ($doc->items as $item) {
                // Data Dasar
                $matnr = isset($item->material_number) && ctype_digit($item->material_number)
                        ? ltrim($item->material_number, '0') 
                        : ($item->material_number ?? '');
                        
                $wc = !empty($item->child_wc) ? $item->child_wc : ($item->parent_wc ?? '-');
                $assigned = floatval($item->assigned_qty ?? 0);

                // Calculate confirmed from PROS
                $confirmed = 0;
                foreach ($item->pros as $pro) {
                    $st = strtolower($pro->status ?? '');
                    if (in_array($st, ['confirmation', 'confirm', 'confirmed'])) {
                        $confirmed += $pro->qty_pro;
                    }
                }

                $balance = $assigned - $confirmed;
                $grandTotalAssigned += $assigned;
                $grandTotalConfirmed += $confirmed;

                $reportItems[] = [
                    'wi_code'     => $doc->wi_document_code,
                    'nik'         => $item->nik ?? '-',
                    'name'        => $item->operator_name ?? '-',
                    'expired_at'  => $doc->expired_at,
                    'workcenter'  => $wc,
                    'aufnr'       => $item->aufnr ?? '-',
                    'material'    => $matnr,
                    'description' => $item->material_desc ?? '-',
                    'assigned'    => $assigned,
                    'confirmed'   => $confirmed,
                    'balance'     => $balance,
                    'remark'      => ($balance > 0.001) ? 'Not Completed' : 'Completed' 
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
                    $releaseService->release($aufnr);
                    $ypprService->refreshPro($plantCode, $aufnr);
                    $pro = ProductionTData1::where('WERKSX', $plantCode)->where('AUFNR', $aufnr)->first();
                    $isValid = false;
                    $currentStats = $pro ? $pro->STATS : 'UNKNOWN';

                    if ($pro && (str_contains($currentStats, 'REL') || str_contains($currentStats, 'DSP'))) {
                        $isValid = true;
                    }
                    
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
                    $ypprService->refreshPro($plantCode, $aufnr);

                    $percent = round((($index + 1) / $total) * 100);
                    echo "data: " . json_encode([
                        'progress' => $percent,
                        'message' => "$msgPrefix Refreshed successfully.",
                        'aufnr' => $aufnr,
                        'type' => 'success'
                    ]) . "\n\n";

                } catch (\Exception $e) {
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

            $nama_bagian = KodeLaravel::where('laravel_code', $kode)->first();
            $children = WorkcenterMapping::with(['parentWorkcenter', 'childWorkcenter'])
                ->where('kode_laravel_id', $nama_bagian ? $nama_bagian->id : null)
                ->get()
                ->filter(function ($m) use ($workcenter) {
                    return $m->parentWorkcenter
                        && strtoupper($m->parentWorkcenter->kode_wc) === strtoupper($workcenter)
                        && $m->childWorkcenter;
                })
                ->map(function ($m) {
                    return strtoupper($m->childWorkcenter->kode_wc);
                })
                ->unique()
                ->values()
                ->all();

            if (!empty($children)) {
                $children[] = strtoupper($workcenter);
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
                'ssavd' => $item->SSAVD ?? null,
                'sssld' => $item->SSSLD ?? null,
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
        $validated = $request->validate([
            'wi_code' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.aufnr' => 'required|string',
            'items.*.vornr' => 'required|string',
            'items.*.qty' => 'required|numeric|min:0.001',
            'items.*.nik' => 'required|string',
            'items.*.name' => 'required|string',
            'items.*.target_workcenter' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            /** @var HistoryWi $doc */
            $doc = HistoryWi::where('wi_document_code', $validated['wi_code'])
                ->lockForUpdate()
                ->firstOrFail();

            $plantCode = $doc->plant_code;
            $isMachiningDoc = ((int)($doc->machining ?? 0) === 1);
            $availableItems = $this->getAvailableProsData($plantCode);
            $allMappingsQuery = WorkcenterMapping::query();
            $allMappingsQuery->where(function ($q) use ($plantCode) {
                if (Schema::hasColumn('workcenter_mappings', 'plant_code')) {
                    $q->orWhere('plant_code', $plantCode);
                }
                if (Schema::hasColumn('workcenter_mappings', 'kode_laravel')) {
                    $q->orWhere('kode_laravel', $plantCode);
                }
                if (Schema::hasColumn('workcenter_mappings', 'plant')) {
                    $q->orWhere('plant', $plantCode);
                }
            });

            $allMappings = $allMappingsQuery->get();
            $groupedRequests = collect($validated['items'])->groupBy(fn($it) => $it['aufnr'].'_'.$it['vornr']);
            $minSsavd = null;
            $maxSssld = null;

            foreach ($groupedRequests as $groupKey => $requests) {

                $niks = $requests->pluck('nik')
                    ->map(fn($n) => trim((string)$n))
                    ->filter();

                if ($niks->count() !== $niks->unique()->count()) {
                    throw new \Exception("NIK tidak boleh sama untuk PRO {$firstReq['aufnr']} / {$firstReq['vornr']} (Longshift).");
                }
                $firstReq = $requests->first();

                $targetItem = $availableItems->first(function($i) use ($firstReq) {
                    return (string)$i->AUFNR === (string)$firstReq['aufnr']
                        && (string)$i->VORNR === (string)$firstReq['vornr'];
                });

                if (!$targetItem) {
                    throw new \Exception("Item {$firstReq['aufnr']} / {$firstReq['vornr']} tidak ditemukan atau quantity sudah habis.");
                }

                $totalRequestedQty = (float)$requests->sum('qty');
                $availableQty = (float)($targetItem->real_sisa_qty ?? 0);

                if ($totalRequestedQty > $availableQty + 0.0001) {
                    throw new \Exception("Total Qty ({$totalRequestedQty}) melebihi sisa tersedia ({$availableQty}) untuk PRO {$firstReq['aufnr']}.");
                }

                // proses per operator (nik)
                foreach ($requests as $req) {
                    $aufnr = (string)$req['aufnr'];
                    $vornr = (string)$req['vornr'];
                    $nik   = (string)$req['nik'];
                    $qty   = (float)$req['qty'];

                    // cari existing item berdasarkan UNIQUE KEY dokumen: aufnr+vornr+nik
                    $existingItem = HistoryWiItem::where('history_wi_id', $doc->id)
                        ->where('aufnr', $aufnr)
                        ->where('vornr', $vornr)
                        ->where('nik', $nik)
                        ->first();

                    // mapping WC
                    $wcTarget = (string)$req['target_workcenter'];
                    $mapping = $allMappings->first(fn($m) => strtoupper((string)$m->workcenter) === strtoupper($wcTarget));

                    $childWc  = $wcTarget;
                    $parentWc = $mapping ? ($mapping->wc_induk ?? $wcTarget) : $wcTarget;

                    // hitung takt time (menit)
                    $baseTime = (float)($targetItem->VGW01 ?? 0);
                    $unit     = strtoupper((string)($targetItem->VGE01 ?? 'MIN'));

                    $mins = $baseTime * $qty;
                    if (in_array($unit, ['S','SEC'], true)) $mins = $mins / 60;
                    elseif (in_array($unit, ['H','HUR'], true)) $mins = $mins * 60;

                    if ($existingItem) {
                        // blok merge kalau sudah ada confirmasi
                        $confirmedQty = 0;
                        if (method_exists($existingItem, 'pros')) {
                            $confirmedQty = (float)$existingItem->pros()
                                ->whereIn(DB::raw('LOWER(status)'), ['confirmasi','confirm','confirmed'])
                                ->sum('qty_pro');
                        }

                        if ($confirmedQty > 0) {
                            throw new \Exception("Item PRO {$aufnr} untuk operator {$req['name']} tidak bisa ditambah karena sudah ada konfirmasi.");
                        }

                        // merge qty
                        $newAssigned = (float)$existingItem->assigned_qty + $qty;

                        // recalc takt berdasarkan VGW01/VGE01 yang tersimpan pada item (lebih konsisten)
                        $exBase = (float)($existingItem->vgw01 ?? $baseTime);
                        $exUnit = strtoupper((string)($existingItem->vge01 ?? $unit));

                        $newMins = $exBase * $newAssigned;
                        if (in_array($exUnit, ['S','SEC'], true)) $newMins = $newMins / 60;
                        elseif (in_array($exUnit, ['H','HUR'], true)) $newMins = $newMins * 60;

                        $existingItem->assigned_qty = $newAssigned;
                        $existingItem->calculated_takt_time = $newMins;

                        // kalau WC beda, pilih: keep yg lama + log warning (atau bisa throw)
                        if (!empty($existingItem->child_wc) && strtoupper($existingItem->child_wc) !== strtoupper($childWc)) {
                            Log::warning("AddItem merge: same AUFNR+VORNR+NIK but different child_wc. Keep existing child_wc={$existingItem->child_wc}, incoming={$childWc}");
                        } else {
                            $existingItem->child_wc  = $childWc;
                            $existingItem->parent_wc = $parentWc;
                        }

                        if ($isMachiningDoc) $existingItem->machining = 1;

                        $existingItem->save();
                    } else {
                        // create baru di history_wi_item
                        $wiItem = HistoryWiItem::create([
                            'history_wi_id' => $doc->id,
                            'nik'           => $nik,
                            'aufnr'         => $aufnr,
                            'vornr'         => $vornr,
                            'uom'           => $targetItem->MEINS ?? null,
                            'operator_name' => $req['name'] ?? null,

                            'material_number' => $targetItem->MATNR ?? null,
                            'material_desc'   => $targetItem->MAKTX ?? null,

                            'qty_order'        => (float)($targetItem->MGVRG2 ?? 0),
                            'assigned_qty'     => $qty,

                            'kdauf' => $targetItem->KDAUF ?? null,
                            'kdpos' => $targetItem->KDPOS ?? null,
                            'dispo' => $targetItem->DISPO ?? null,
                            'steus' => $targetItem->STEUS ?? null,

                            'ssavd' => $targetItem->SSAVD ?? null,
                            'sssld' => $targetItem->SSSLD ?? null,

                            'kapaz' => $targetItem->KAPAZ ?? null,

                            'vgw01' => (float)($targetItem->VGW01 ?? 0),
                            'vge01' => $targetItem->VGE01 ?? null,

                            'parent_wc' => $parentWc,
                            'child_wc'  => $childWc,

                            'machining' => $isMachiningDoc ? 1 : 0,
                            'calculated_takt_time' => $mins,
                            'status' => 'Open',
                        ]);

                        if ($isMachiningDoc) {
                            $s = $this->parseWiDate(($targetItem->SSAVD ?? null));
                            $e = $this->parseWiDate(($targetItem->SSSLD ?? null));

                            if ($s && (!$minSsavd || $s->lt($minSsavd))) $minSsavd = $s;
                            if ($e && (!$maxSssld || $e->gt($maxSssld))) $maxSssld = $e;
                        }
                    }
                }
            }

            if ($isMachiningDoc) {
                $docDate = Carbon::parse($doc->document_date)->startOfDay();
                $docExp  = $doc->expired_at ? Carbon::parse($doc->expired_at) : null;

                if ($minSsavd && $minSsavd->lt($docDate)) {
                    $doc->document_date = $minSsavd->toDateString();
                }

                if ($maxSssld) {
                    $newExp = $maxSssld->copy()->endOfDay();
                    if (!$docExp || $newExp->gt($docExp)) {
                        $doc->expired_at = $newExp;
                    }
                }

                $doc->document_time = null; // machining flow
                $doc->save();
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => count($validated['items']).' Item berhasil ditambahkan.']);

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
            ->whereNotNull('NETPR2')
            ->where('NETPR2', '!=', 0)
            ->whereRaw('CAST(MGVRG2 AS DECIMAL(20,3)) > CAST(COALESCE(LMNGA, 0) AS DECIMAL(20,3))');

        if ($search) {
            if (preg_match('/^"(.*)"$/', trim($search), $matches)) {
                $term = $matches[1];
                $query->where(function($q) use ($term) {
                    $fields = ['AUFNR', 'MATNR', 'MAKTX', 'KDAUF', 'KDPOS', 'ARBPL', 'STEUS', 'VORNR'];
                    foreach($fields as $f) $q->orWhere($f, '=', $term);
                });
            } else {
                $terms = array_filter(array_map('trim', explode(';', $search)));
                $query->where(function($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere(function($subQ) use ($term) {
                            if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $term, $matches)) {
                                $subQ->where('KDAUF', '=', $matches[1])
                                    ->where('KDPOS', '=', str_pad($matches[2], 6, '0', STR_PAD_LEFT));
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

        // --- Advanced Search (Spesifik Kolom) ---

        // PRO (AUFNR)
        if ($request->filled('adv_aufnr')) {
            $val = $request->adv_aufnr;
            $arr = array_map('trim', explode(',', $val));
            str_contains($val, ',') ? $query->whereIn('AUFNR', $arr) : $query->where('AUFNR', $val);
        }

        // Material (MATNR)
        if ($request->filled('adv_matnr')) {
            $val = $request->adv_matnr;
            $arr = array_map('trim', explode(',', $val));
            str_contains($val, ',') ? $query->whereIn('MATNR', $arr) : $query->where('MATNR', $val);
        }

        // Description (MAKTX)
        if ($request->filled('adv_maktx')) {
            $val = $request->adv_maktx;
            $arr = array_map('trim', explode(',', $val));
            // Gunakan whereIn agar performa lebih ringan dibanding looping orWhere
            $query->whereIn('MAKTX', $arr);
        }

        // Workcenter (ARBPL)
        if ($request->filled('adv_arbpl')) {
            $val = $request->adv_arbpl;
            $arr = array_map('trim', explode(',', $val));
            str_contains($val, ',') ? $query->whereIn('ARBPL', $arr) : $query->where('ARBPL', $val);
        }
        
        // SO & Item (KDAUF)
        if ($request->filled('adv_kdauf')) {
            $val = $request->adv_kdauf;
            $rawInputs = explode(',', $val);
            $soList = [];
            $soItemPairs = [];

            foreach ($rawInputs as $input) {
                $input = trim($input);
                if (empty($input)) continue;
                if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $input, $matches)) {
                    $soItemPairs[] = ['so' => $matches[1], 'item' => str_pad($matches[2], 6, '0', STR_PAD_LEFT)];
                } else {
                    $soList[] = $input;
                }
            }

            $query->where(function($q) use ($soList, $soItemPairs) {
                if (!empty($soList)) $q->orWhereIn('KDAUF', $soList);
                foreach ($soItemPairs as $pair) {
                    $q->orWhere(function($sub) use ($pair) {
                        $sub->where('KDAUF', '=', $pair['so'])->where('KDPOS', '=', $pair['item']);
                    });
                }
            });
        }

        // Activity (VORNR)
        if ($request->filled('adv_vornr')) {
            $val = $request->adv_vornr;
            str_contains($val, ',') 
                ? $query->whereIn('VORNR', array_map('trim', explode(',', $val))) 
                : $query->where('VORNR', 'like', "%{$val}%");
        }

        // Filter DSPT REL
        if ($filter === 'dspt_rel') {
            $query->where('STATS', 'LIKE', '%DSP%');
        }
        
        return $query;
    }

    private function parseWiDate(?string $value): ? Carbon
    {
        if (!$value) return null;

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'd.m.Y', 'Y/m/d'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $value)->startOfDay();
            } catch (\Exception $e) {
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function printInactiveReport(Request $request)
    {
        $request->validate([
            'wi_codes'    => 'required',
            'printed_by'  => 'required',
            'department'  => 'required',
        ]);

        $rawInput = $request->input('wi_codes');
        $wiCodes = array_values(array_filter(array_map('trim', explode(',', $rawInput))));
        
        $documents = HistoryWi::with(['items.pros'])
            ->whereIn('wi_document_code', $wiCodes)
            // ->where('status', 'INACTIVE') // Optional: remove restriction if we want to print any selected docs from inactive tab
            ->get();

        if ($documents->isEmpty()) {
            return back()->with('error', 'Dokumen inactive tidak ditemukan.');
        }

        // Use the same data structure as printSingleWi to support wi_single_document template
        $data = [
            'documents'  => $documents,
            'printedBy'  => $request->input('printed_by'),
            'department' => preg_replace('/[^\x20-\x7E]/', '', str_replace(['–', '—'], '-', $request->input('department'))),
            'printTime'  => now(),
            'isEmail'    => false,
            'status_override' => 'INACTIVE', // Force INACTIVE status for this report
        ];

        // Load the Single Document view instead of the report view
        $pdf = Pdf::loadView('pdf.wi_single_document', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->stream('Work_Instruction_Inactive.pdf');
    }
}
    