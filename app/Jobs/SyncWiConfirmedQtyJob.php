<?php

namespace App\Jobs;

use App\Models\HistoryWi;
use App\Services\CheckQuantityConfiramsi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncWiConfirmedQtyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600;
    public array $backoff = [60, 180, 600];

    public function handle(\App\Services\CheckQuantityConfiramsi $service): void
    {
        HistoryWi::query()
            ->whereDate('document_date', today())
            ->whereNotIn('status', [
                'COMPLETED',
                'COMPLETED WITH REMARK',
                'DELETED',
            ])
            ->with(['items' => function ($q) {
                $q->select('id', 'history_wi_id', 'aufnr', 'vornr', 'nik', 'confirmed_qty_total', 'status');
            }])
            ->orderBy('id')
            ->chunkById(50, function ($wis) use ($service) {

                foreach ($wis as $wi) {
                    if (!$wi->document_date) continue;

                    $isMachining = (int) $wi->machining === 1;
                    $dates = [];
                    
                    if ($isMachining) {
                        $start = $wi->document_date->copy()->startOfDay();
                        $end = $wi->expired_at ? \Carbon\Carbon::parse($wi->expired_at)->endOfDay() : \Carbon\Carbon::now()->endOfDay();
                        
                        $currentDate = $start->copy();
                        while ($currentDate->lessThanOrEqualTo($end)) {
                            $dates[] = $currentDate->copy();
                            $currentDate->addDay();
                        }
                    } else {
                        $dates = [$wi->document_date];
                    }

                    foreach ($wi->items as $item) {
                        $itemStatus = strtoupper($item->status ?? '');
                        if ($itemStatus === 'COMPLETED' || $itemStatus === 'COMPLETED WITH REMARK') {
                            continue;
                        }

                        $totalConfirmed = 0.0;

                        foreach ($dates as $d) {
                            $budat = $d->format('dmY');
                            $res = $service->check(
                                (string) $item->aufnr,
                                (string) $item->vornr,
                                (string) $item->nik,
                                (string) $budat,
                                (string) $wi->plant_code
                            );

                            if (($res['status'] ?? 'failed') === 'success') {
                                $totalConfirmed += (float) ($res['confirmed_qty'] ?? 0);
                            } else {
                                \Log::warning('SAP konfirmasi gagal', [
                                    'wi_id' => $wi->id,
                                    'item_id' => $item->id,
                                    'aufnr' => $item->aufnr,
                                    'vornr' => $item->vornr,
                                    'nik' => $item->nik,
                                    'budat' => $budat,
                                    'werks' => $wi->plant_code,
                                    'msg_error' => $res['msg_error'] ?? null,
                                    'http_code' => $res['http_code'] ?? null,
                                ]);
                            }
                        }

                        $item->confirmed_qty_total = $totalConfirmed;
                        $item->save();
                    }
                }
            });
    }
}