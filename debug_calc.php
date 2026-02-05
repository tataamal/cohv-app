<?php
// Debug Script for Daily Time Calculation
// Run with: php artisan tinker debug_calc.php

use Carbon\Carbon;
use App\Models\HistoryWiItem;
use Illuminate\Support\Facades\DB;

$nik = '10000994';
$targetDateStr = '2026-02-03';
$targetDate = Carbon::parse($targetDateStr)->startOfDay();

echo "Debugging Daily Time for NIK: $nik on $targetDateStr\n";

// 1. Fetch Machining Items
// NOTE: I added 'Active', 'Open' etc manually to match controller logic if needed,
// but relying on machining=1 and dates should be enough if status isn't filtering it out.
// Controller filters status IN [...]. I should replicate that to be 100% sure.
$allowedStatus = ['ACTIVE', 'PROGRESS', 'PROCESSED', 'Active', 'Progress', 'Processed', 'Open', 'OPEN'];

$machiningItems = HistoryWiItem::query()
    ->join('history_wi', 'history_wi_item.history_wi_id', '=', 'history_wi.id')
    ->where('history_wi.machining', 1)
    ->whereNull('history_wi.deleted_at')
    ->whereIn('history_wi.status', $allowedStatus) // Replicate Controller Filter
    ->where('history_wi_item.nik', $nik)
    ->where(function($q) use ($targetDateStr) {
         $q->whereDate('history_wi.document_date', '<=', $targetDateStr)
           ->where(function($q2) use ($targetDateStr) {
               $q2->whereNull('history_wi.expired_at')
                  ->orWhereDate('history_wi.expired_at', '>=', $targetDateStr);
           });
    })
    ->select([
        'history_wi.wi_document_code',
        'history_wi.document_date', 
        'history_wi.expired_at',
        'history_wi_item.calculated_takt_time',
        'history_wi_item.aufnr',
        'history_wi_item.vornr'
    ])
    ->get();

$groups = [];
foreach ($machiningItems as $item) {
    $code = $item->wi_document_code;
    $key = $item->aufnr . '-' . $item->vornr;
    
    if (!isset($groups[$code])) {
        $groups[$code] = [
            'items' => [],
            'start' => $item->document_date,
            'end'   => $item->expired_at
        ];
    }
    // Sum unique items (logic from controller: overwrite or sum? Controller sums += calculated_takt_time)
    if (!isset($groups[$code]['items'][$key])) {
        $groups[$code]['items'][$key] = 0;
    }
    $groups[$code]['items'][$key] += (float)$item->calculated_takt_time;
}

$totalDaily = 0;
foreach ($groups as $code => $data) {
    $totalWiTime = array_sum($data['items']);
    
    $sDate = Carbon::parse($data['start'])->startOfDay();
    $eDate = $data['end'] ? Carbon::parse($data['end'])->startOfDay() : $sDate->copy()->endOfDay();
    
    $duration = $sDate->diffInDays($eDate) + 1;
    $duration = max(1, $duration);
    
    $dailyTarget = $totalWiTime / $duration;
    $totalDaily += $dailyTarget;
    
    echo "WI: $code | Total Time: $totalWiTime | Duration: $duration days | Daily: $dailyTarget\n";
}

echo "Total Machining Daily: $totalDaily\n";

// 2. Fetch Normal Items (Completed/Expired on this day)
$normalItems = HistoryWiItem::query()
    ->join('history_wi', 'history_wi_item.history_wi_id', '=', 'history_wi.id')
    ->whereNull('history_wi.deleted_at')
    ->where(function($q) {
        $q->whereNull('history_wi.machining')
          ->orWhere('history_wi.machining', '=', 0);
    })
    ->where('history_wi_item.nik', $nik)
    ->where(function($q) use ($targetDateStr) {
        $q->where(function($sub) use ($targetDateStr) {
            $sub->where('history_wi.status', 'LIKE', '%COMPLETED%')
                ->whereDate('history_wi.updated_at', $targetDateStr);
        })
        ->orWhere(function($sub) use ($targetDateStr) {
            $sub->where('history_wi.status', 'EXPIRED')
                ->whereDate('history_wi.expired_at', $targetDateStr);
        });
    })
    ->select([
        'history_wi.wi_document_code',
        'history_wi.status',
        'history_wi_item.calculated_takt_time'
    ])
    ->get();

$normalTotal = 0;
echo "--- Normal Items ---\n";
foreach ($normalItems as $item) {
    echo "WI: {$item->wi_document_code} | Status: {$item->status} | Time: {$item->calculated_takt_time}\n";
    $normalTotal += $item->calculated_takt_time;
}

echo "Total Normal Daily: $normalTotal\n";
echo "GRAND TOTAL: " . ($totalDaily + $normalTotal) . "\n";
