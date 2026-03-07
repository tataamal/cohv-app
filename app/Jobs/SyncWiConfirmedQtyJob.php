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
                $q->select('id', 'history_wi_id', 'aufnr', 'vornr', 'nik', 'confirmed_qty_total');
            }])
            ->orderBy('id')
            ->chunkById(50, function ($wis) use ($service) {

                foreach ($wis as $wi) {
                    if (!$wi->document_date) continue;

                    $isLongshift = (int) $wi->longshift === 1;

                    $dates = [$wi->document_date];
                    if ($isLongshift) {
                        $dates[] = $wi->document_date->copy()->subDay();
                    }

                    foreach ($wi->items as $item) {
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