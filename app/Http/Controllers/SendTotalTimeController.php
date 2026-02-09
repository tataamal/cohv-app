<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\HistoryWiItem;
use App\Models\DailyTimeWi;
use Illuminate\Support\Facades\Log;

class SendTotalTimeController extends Controller
{

    public function calculateAndStoreDailyTime($startDate = null, $endDate = null)
    {
        try {
            // Determine date range
            if ($startDate) {
                $start = Carbon::parse($startDate)->startOfDay();
                $end   = $endDate ? Carbon::parse($endDate)->startOfDay() : Carbon::parse($startDate)->startOfDay();
            } else {
                $start = Carbon::now()->startOfDay();
                $end   = Carbon::now()->startOfDay();
            }

            $totalProcessed = 0;
            $processedDates = [];

            // Helper to get Plant Code Map
            $plantMap = \App\Models\KodeLaravel::pluck('plant', 'laravel_code')->toArray();

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $currentDate = $date->toDateString();
                
                // Aggregator per NIK for this specific date
                // Structure: [ 'NIK' => [ 'nik', 'nama', 'total_time' => float, 'plants' => [], 'tags' => ['TAG'=>qty] ] ]
                $dailyData = [];

                // =========================================================================================
                // PART 1: NORMAL WI (Non-Machining)
                // Filter: Status COMPLETED, COMPLETED WITH REMARK, EXPIRED
                // Date: document_date matches $currentDate
                // =========================================================================================
                
                $normalRows = HistoryWiItem::query()
                    ->join('history_wi', 'history_wi_item.history_wi_id', '=', 'history_wi.id')
                    ->whereNull('history_wi.deleted_at')
                    ->where(function($q) {
                        $q->whereNull('history_wi.machining')
                          ->orWhere('history_wi.machining', '=', 0);
                    })
                    ->whereDate('history_wi.document_date', $currentDate)
                    ->where(function($q) {
                        $q->where('history_wi.status', 'LIKE', '%COMPLETED%')
                          ->orWhere('history_wi.status', 'EXPIRED');
                    })
                    ->whereNotNull('history_wi_item.nik')
                    ->select([
                        'history_wi_item.id',
                        'history_wi_item.history_wi_id',
                        'history_wi_item.nik',
                        'history_wi_item.operator_name',
                        'history_wi_item.name1',
                        'history_wi_item.calculated_takt_time',
                        'history_wi_item.assigned_qty',
                        'history_wi.plant_code',
                        'history_wi.status as doc_status',
                    ])
                    ->with(['pros'])
                    ->get();

                foreach ($normalRows as $row) {
                    $nik  = trim((string)$row->nik);
                    if ($nik === '') continue;
                    
                    $this->initAggregator($dailyData, $nik, $row->operator_name ?: $row->name1);

                    // Add Time
                    $timeVal = max(0, (float)$row->calculated_takt_time);
                    $dailyData[$nik]['total_time'] += $timeVal;

                    // Add Plant
                    $pCode = (string)($row->plant_code ?? '');
                    if ($pCode !== '' && !in_array($pCode, $dailyData[$nik]['plants'])) {
                        $dailyData[$nik]['plants'][] = $pCode;
                    }

                    // --- Processing Tags & NO REASON Logic ---
                    $confirmedQty = 0;
                    $remarkQty = 0;

                    if ($row->pros) {
                        foreach ($row->pros as $pro) {
                            $tag = trim((string)$pro->tag);
                            if ($tag !== '') {
                                if (!isset($dailyData[$nik]['tags'][$tag])) $dailyData[$nik]['tags'][$tag] = 0;
                                $dailyData[$nik]['tags'][$tag]++;
                            }

                            $st = strtolower($pro->status ?? '');
                            if (in_array($st, ['confirmasi', 'confirm', 'confirmed', 'confirmation'])) {
                                $confirmedQty += $pro->qty_pro;
                            } elseif (str_contains($st, 'remark')) {
                                $remarkQty += $pro->qty_pro;
                            }
                        }
                    }

                    // Expired - NO REASON check
                    if ($row->doc_status === 'EXPIRED') {
                        $assigned = (float)$row->assigned_qty;
                        $totalDone = $confirmedQty + $remarkQty;
                        
                        // If incomplete, add NO REASON tag
                        if (($assigned - $totalDone) > 0.001) {
                            $tInfo = 'NO REASON';
                            if (!isset($dailyData[$nik]['tags'][$tInfo])) $dailyData[$nik]['tags'][$tInfo] = 0;
                            $dailyData[$nik]['tags'][$tInfo]++;
                        }
                    }
                }

                // =========================================================================================
                // PART 2: MACHINING WI
                // Filter: Status ACTIVE, PROGRESS, PROCESSED (Working)
                // Date: $currentDate IS BETWEEN document_date (start) AND expired_at (end)
                // Logic: Unique Group (AUFNR-VORNR-NIK-WI) -> Sum Time -> Divide by Duration(Days) -> Add to Daily
                // =========================================================================================

                $machiningRows = HistoryWiItem::query()
                    ->join('history_wi', 'history_wi_item.history_wi_id', '=', 'history_wi.id')
                    ->whereNull('history_wi.deleted_at')
                    ->where('history_wi.machining', 1)
                    ->whereIn('history_wi.status', ['ACTIVE', 'PROGRESS', 'PROCESSED', 'Active', 'Progress', 'Processed', 'Open', 'OPEN'])
                    ->where(function($q) use ($currentDate) {
                         $q->whereDate('history_wi.document_date', '<=', $currentDate)
                           ->where(function($q2) use ($currentDate) {
                               $q2->whereNull('history_wi.expired_at')
                                  // Update: Use strict comparison for expired_at to avoid counting documents failing on 00:00:00 of the current date
                                  ->orWhere('history_wi.expired_at', '>', $currentDate . ' 00:00:00');
                           });
                    })
                    // Filter item NIK relevant? Yes.
                    ->whereNotNull('history_wi_item.nik')
                    ->select([
                        'history_wi_item.id',
                        'history_wi_item.nik',
                        'history_wi_item.operator_name',
                        'history_wi_item.name1',
                        'history_wi_item.aufnr',
                        'history_wi_item.vornr',
                        'history_wi_item.calculated_takt_time',
                        'history_wi.id as wi_id',
                        'history_wi.wi_document_code',
                        'history_wi.document_date',
                        'history_wi.expired_at',
                        'history_wi.plant_code',
                    ])
                    ->with(['pros' => function($q) use ($currentDate) {
                        $q->whereDate('created_at', $currentDate);
                    }])
                    ->get();

                // Grouping: [NIK][WI_CODE][AUFNR_VORNR]
                $machineGroups = [];

                foreach ($machiningRows as $row) {
                    $nik = trim((string)$row->nik);
                    if ($nik === '') continue;
                    $this->initAggregator($dailyData, $nik, $row->operator_name ?: $row->name1);
                    if ($row->pros) {
                        foreach ($row->pros as $pro) {
                            $tag = trim((string)$pro->tag);
                            if ($tag !== '') {
                                if (!isset($dailyData[$nik]['tags'][$tag])) {
                                    $dailyData[$nik]['tags'][$tag] = 0;
                                }
                                $dailyData[$nik]['tags'][$tag]++; // Counting frequency
                            }
                        }
                    }
                    $wiCode = $row->wi_document_code;
                    $aufnr  = $row->aufnr;
                    $vornr  = $row->vornr;
                    $itemKey = "{$aufnr}-{$vornr}";

                    if (!isset($machineGroups[$nik])) $machineGroups[$nik] = [];
                    if (!isset($machineGroups[$nik][$wiCode])) {
                        $machineGroups[$nik][$wiCode] = [
                            'items' => [], // Keyed by itemKey to ensure uniqueness
                            'start' => $row->document_date, // Carbon by cast? Need verify. Model cast is date:Y-m-d
                            'end'   => $row->expired_at,
                            'plant' => $row->plant_code,
                            'name'  => $row->operator_name ?: $row->name1
                        ];
                    }
                    if (!isset($machineGroups[$nik][$wiCode]['items'][$itemKey])) {
                        $machineGroups[$nik][$wiCode]['items'][$itemKey] = 0.0;
                    }
                    $machineGroups[$nik][$wiCode]['items'][$itemKey] += max(0, (float)$row->calculated_takt_time);
                }
                foreach ($machineGroups as $nik => $wiContexts) {
                    // If not, we take name from the first context
                    $first = reset($wiContexts);
                    $this->initAggregator($dailyData, $nik, $first['name'] ?? null);

                    foreach ($wiContexts as $wiCode => $ctx) {
                        // Calculate Total Time for this WI
                        $totalWiTime = array_sum($ctx['items']);

                        // Calculate Duration (Days)
                        // Start: document_date
                        // End: expired_at
                        $sDate = Carbon::parse($ctx['start'])->startOfDay();
                        
                        // Fix for Duration: If expired_at is exactly 00:00:00, it shouldn't count as an extra day
                        $endC = $ctx['end'] ? Carbon::parse($ctx['end']) : null;
                        if ($endC && $endC->format('H:i:s') === '00:00:00') {
                            // If ends at 00:00:00, count it as ending on previous day for duration purposes
                            $eDate = $endC->copy()->subSecond()->startOfDay();
                        } else {
                            $eDate = $endC ? $endC->startOfDay() : $sDate->copy()->endOfDay();
                        }
                        
                         // Duration in days (inclusive)
                         // e.g., 2nd to 5th. 2,3,4,5 = 4 days.
                         // diffInDays(2, 5) is 3? (5-2=3). So diff + 1.
                         
                         $duration = $sDate->diffInDays($eDate) + 1;
                         // Prevent div by zero safety
                         $duration = max(1, $duration);

                         $dailyTarget = ($totalWiTime / $duration);
                         
                         // Add to Daily Data
                         $dailyData[$nik]['total_time'] += $dailyTarget;
                         
                         // Add Plant
                         $p = (string)$ctx['plant'];
                         if ($p !== '' && !in_array($p, $dailyData[$nik]['plants'])) {
                             $dailyData[$nik]['plants'][] = $p;
                         }
                    }
                }
                
                // ===================================
                // SAVE TO DB
                // ===================================
                $count = 0;
                foreach ($dailyData as $nik => $data) {
                    // Plant Mapping
                    sort($data['plants']);
                    $plantCodesStr = implode(',', $data['plants']);
                    
                    $realPlants = [];
                    foreach ($data['plants'] as $pc) {
                        if (isset($plantMap[$pc])) $realPlants[] = $plantMap[$pc];
                        // else $realPlants[] = $pc; // or ignore unknown?
                    }
                    $realPlants = array_unique($realPlants);
                    sort($realPlants);
                    $plantNameStr = implode(',', $realPlants);

                    // Tags JSON
                    $tagList = [];
                    foreach ($data['tags'] as $tName => $qty) {
                        $tagList[] = ['tag' => $tName, 'qty' => $qty];
                    }
                    $tagJson = !empty($tagList) ? json_encode($tagList) : null;

                    // Upsert DB
                    $record = DailyTimeWi::firstOrNew([
                        'tanggal' => $currentDate,
                        'nik'     => $nik,
                    ]);
                    
                    $record->nama          = $data['nama'];
                    $record->kode_laravel  = $plantCodesStr;
                    $record->plant         = $plantNameStr;
                    $record->total_time_wi = (int)ceil($data['total_time']); // user usually expects Int minutes
                    $record->tag           = $tagJson;
                    
                    $record->save();
                    $count++;
                }

                $totalProcessed += $count;
                $processedDates[] = $currentDate;
            }

            $dateRangeStr = $start->toDateString() . ($start->notEqualTo($end) ? ' to ' . $end->toDateString() : '');

            return response()->json([
                'success'           => true,
                'message'           => "Berhasil menghitung dan menyimpan data harian (Normal & Machining) untuk: {$dateRangeStr}.",
                'records_processed' => $totalProcessed,
                'dates_processed'   => $processedDates,
            ]);

        } catch (\Exception $e) {
            Log::error("Error Calculating Daily Time WI: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function initAggregator(&$data, $nik, $name) {
        if (!isset($data[$nik])) {
            $data[$nik] = [
                'nik'        => $nik,
                'nama'       => $name,
                'total_time' => 0.0,
                'plants'     => [],
                'tags'       => [],
            ];
        } else {
            // Update name if missing
            if (empty($data[$nik]['nama']) && !empty($name)) {
                $data[$nik]['nama'] = $name;
            }
        }
    }
}
