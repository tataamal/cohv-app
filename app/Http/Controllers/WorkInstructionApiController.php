<?php

namespace App\Http\Controllers;
use App\Models\HistoryWi;
use App\Models\HistoryPro;
use App\Models\HistoryWiItem;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class WorkInstructionApiController extends Controller
{
    public function getUniqueUnexpiredAufnrs()
    {
        $now   = Carbon::now();
        $today = $now->toDateString();
        $baseHeaderQuery = HistoryWi::query()
            ->whereNull('deleted_at') // opsional (SoftDeletes biasanya sudah otomatis)
            ->whereDate('document_date', '<=', $today)
            ->whereNotIn('status', ['COMPLETED', 'EXPIRED', 'COMPLETED WITH REMARK', 'INACTIVE']) // Exclude completed/expired
            ->where(function ($q) use ($today) {
                $q->whereNull('expired_at')
                ->orWhereDate('expired_at', '>', $today);
            });

        $histories = $baseHeaderQuery
            ->with(['items' => function ($q) {
                $q->where('status', '!=', 'Completed')
                ->whereNotNull('aufnr')
                ->whereNotNull('vornr')
                ->whereNotNull('nik');
            }])
            ->get();

        $grouped = [];

        foreach ($histories as $history) {
            $wiCode = $history->wi_document_code;

            // pastikan WI code tetap muncul walau items akhirnya kosong
            if (!isset($grouped[$wiCode])) {
                $grouped[$wiCode] = [];
            }

            $isMachining = ((int) $history->machining === 1);

            foreach ($history->items as $item) {
                $aufnr  = $item->aufnr ?? null;
                $vornr  = $item->vornr ?? null;
                $nik    = $item->nik ?? null;
                $status = $item->status ?? 'Created';

                if (!$aufnr || !$vornr || !$nik) continue;
                if ($status === 'Completed') continue;

                // Machining validation removed
                // Global unique check removed to allow per-WI listing

                if (!isset($grouped[$wiCode])) {
                    $grouped[$wiCode] = [];
                }

                $grouped[$wiCode][] = [
                    'aufnr' => $aufnr,
                    'vornr' => $vornr,
                    'nik'   => $nik,
                ];

                // $seen[$uniqueKey] = true; // Removed
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => $grouped,
            'count'  => collect($grouped)->flatten(1)->count(),
        ]);
    }

    public function getWiDocumentByCode(Request $request)
    {
        $request->validate([
            'wi_code' => 'nullable|string',
            'nik'     => 'nullable|string',
        ]);

        $code = $request->input('wi_code');
        $nik  = $request->input('nik');

        if (!$code && !$nik) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Harap masukkan WI Code atau NIK.'
            ], 400);
        }

        $now   = Carbon::now();
        $today = $now->toDateString();

        $query = HistoryWi::query()
            ->where(function ($grouped) use ($today) {
                // 1. Dokumen BIASA & MACHINING (Non-Longshift & Non-WIW) -> Kena filter tanggal & expired
                $grouped->where(function ($q) use ($today) {
                    $q->where(function ($sub) {
                        $sub->where(function ($s) {
                                $s->where('longshift', '!=', 1)
                                  ->orWhereNull('longshift');
                            })
                            ->where('wi_document_code', 'not like', 'WIW%');
                    })
                    ->where(function ($sub2) use ($today) {
                        $sub2->whereNull('expired_at')
                             ->orWhereDate('expired_at', '>', $today);
                    });
                })
                // 2. Dokumen LONGSHIFT atau WIW -> Hanya cek status ACTIVE (abaikan tanggal)
                ->orWhere(function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('longshift', 1)
                            ->orWhere('wi_document_code', 'like', 'WIW%');
                    })
                    ->where('status', 'ACTIVE');
                });
            })
            ->with(['items' => function ($q) use ($nik) {
                $q->whereNotNull('aufnr')
                ->whereNotNull('vornr')
                ->whereNotNull('nik');

                if ($nik) {
                    $q->where('nik', $nik);
                }
            }]);

        if ($code) {
            $query->where('wi_document_code', $code);
        }

        if ($nik && !$code) {
            $query->whereHas('items', function ($q) use ($nik) {
                $q->where('nik', $nik);
            });
        }

        $documents = $query->get();

        // Auto-activate WI INACTIVE jika sudah masuk start time
        foreach ($documents as $doc) {
            if ($doc->status === 'INACTIVE') {
                [$start, $end] = $this->wiStartEnd($doc);

                if ($now->greaterThanOrEqualTo($start)) {
                    $doc->status = 'ACTIVE';
                    $doc->save();

                    HistoryWiItem::where('history_wi_id', $doc->id)->update(['status' => 'ACTIVE']);

                    foreach ($doc->items as $item) {
                        $item->status = 'ACTIVE';
                    }
                }
            }
        }

        if ($documents->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Dokumen WI tidak ditemukan atau WI Inactive.'
            ], 404);
        }

        $hasAssignableItems = false;
        $allCompleted       = false;

        $mappedDocuments = $documents->map(function ($doc) use ($now, &$hasAssignableItems, &$allCompleted) {
            $isMachining = ((int) $doc->machining === 1);
            $rawItems    = $doc->items;

            // Filter machining window (kalau masih dipakai di read)
            if ($isMachining) {
                $rawItems = $rawItems->filter(function ($item) use ($now) {
                    $attrs = $item->getAttributes();

                    $ssavdRaw = $attrs['SSAVD'] ?? $attrs['ssavd'] ?? null;
                    $sssldRaw = $attrs['SSSLD'] ?? $attrs['sssld'] ?? null;

                    if (!$ssavdRaw || !$sssldRaw) return false;

                    try {
                        $ssavd = Carbon::parse($ssavdRaw);
                        $sssld = Carbon::parse($sssldRaw);

                        if (is_string($ssavdRaw) && strlen($ssavdRaw) <= 10) $ssavd = $ssavd->startOfDay();
                        if (is_string($sssldRaw) && strlen($sssldRaw) <= 10) $sssld = $sssld->endOfDay();
                    } catch (\Throwable $e) {
                        return false;
                    }

                    return $now->between($ssavd, $sssld, true);
                })->values();
            }

            if ($rawItems->isNotEmpty()) {
                $hasAssignableItems = true;
            }

            $pendingItems = $rawItems->filter(function ($item) {
                $status = (string) ($item->status ?? '');
                return !in_array($status, ['Completed', 'Completed With Remark'], true);
            })->values();

            if ($rawItems->isNotEmpty() && $pendingItems->isEmpty()) {
                $allCompleted = true;
            }

            // Map items memakai COUNTER, bukan pros->sum
            $historyWiItems = $pendingItems->map(function ($item) {
                $arr = $item->toArray();

                $arr['status_pro_wi']  = $item->status ?? null;
                $arr['confirmed_qty']  = (float) ($item->confirmed_qty_total ?? 0);
                $arr['remark_qty']     = (float) ($item->remark_qty_total ?? 0);

                // Optional tambahan kalau mau: processed & remaining
                $assigned = (float) ($item->assigned_qty ?? 0);
                $processed = $arr['confirmed_qty'] + $arr['remark_qty'];
                $arr['processed_total'] = $processed;
                $arr['remaining_qty']   = max(0, $assigned - $processed);

                return $arr;
            })->toArray();

            return [
                'wi_code'         => $doc->wi_document_code,
                'plant_code'      => $doc->plant_code,
                'workcenter'      => $doc->workcenter,
                'document_date'   => $doc->document_date,
                'document_time'   => $doc->document_time,
                'expired_at'      => $doc->expired_at,
                'machining'       => (int) $doc->machining,
                'longshift'       => (int) $doc->longshift,
                'history_wi_item' => $historyWiItems,
            ];
        });

        $finalDocuments = $mappedDocuments->filter(function ($doc) {
            return !empty($doc['history_wi_item']);
        })->values();

        if ($finalDocuments->isEmpty()) {
            if ($hasAssignableItems && $allCompleted) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Semua dokumen yang ditugaskan belum aktif atau telah terselesaikan'
                ], 404);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Dokumen WI tidak ditemukan atau tidak ada tugas untuk NIK ini.'
            ], 404);
        }

        return response()->json([
            'status'       => 'success',
            'wi_documents' => $finalDocuments,
        ]);
    }
    
    public function completeProStatus(Request $request)
    {
        $request->validate([
            'wi_code'        => 'required|string',
            'aufnr'          => 'required|string',
            'confirmed_qty'  => 'required|numeric|min:0.001',
            'nik'            => 'required|string',
            'vornr'          => 'required|string',
        ]);

        $wiCode  = $request->input('wi_code');
        $aufnr   = $request->input('aufnr');
        $nik     = $request->input('nik');
        $vornr   = $request->input('vornr');
        $confQty = (float) $request->input('confirmed_qty');
        $today   = Carbon::now()->toDateString();

        try {
            $result = $this->runTransactionWithDeadlockRetry(function () use ($wiCode, $aufnr, $nik, $vornr, $confQty, $today) {

                $document = HistoryWi::query()
                    ->where('wi_document_code', $wiCode)
                    ->whereDate('document_date', '<=', $today)
                    ->where(function ($q) use ($today) {
                        $q->whereNull('expired_at')
                        ->orWhereDate('expired_at', '>=', $today);
                    })
                    ->first();

                if (!$document) {
                    return ['http' => 404, 'payload' => [
                        'status'  => 'error',
                        'message' => 'Dokumen WI tidak ditemukan atau WI Inactive.'
                    ]];
                }

                $item = HistoryWiItem::query()
                    ->where('history_wi_id', $document->id)
                    ->where('aufnr', $aufnr)
                    ->where('vornr', $vornr)
                    ->where('nik', $nik)
                    ->lockForUpdate()
                    ->first();

                if (!$item) {
                    return ['http' => 404, 'payload' => [
                        'status'  => 'error',
                        'message' => 'Kombinasi AUFNR, VORNR dan NIK tidak ditemukan di dokumen ini.'
                    ]];
                }

                $assignedQty = (float) ($item->assigned_qty ?? 0);
                $confirmed   = (float) ($item->confirmed_qty_total ?? 0);
                $remark      = (float) ($item->remark_qty_total ?? 0);

                $processed = $confirmed + $remark;
                $remaining = $assignedQty - $processed;

                if ($confQty > $remaining) {
                    return ['http' => 400, 'payload' => [
                        'status'  => 'error',
                        'message' => "Qty konfirmasi melebihi sisa kuota. Remaining allow: {$remaining}.",
                        'meta'    => [
                            'assigned_qty'        => $assignedQty,
                            'confirmed_qty_total' => $confirmed,
                            'remark_qty_total'    => $remark,
                            'processed_total'     => $processed,
                            'remaining_qty'       => max(0, $remaining),
                        ],
                    ]];
                }

                // untuk decimal aman: jangan tempel float mentah ke SQL
                $confQtyStr = number_format($confQty, 3, '.', '');

                // atomic increment + guard (walau sudah lockForUpdate, ini menambah safety)
                $affected = HistoryWiItem::where('id', $item->id)
                    ->whereRaw('(confirmed_qty_total + remark_qty_total + ?) <= assigned_qty', [$confQty])
                    ->update([
                        'confirmed_qty_total' => DB::raw("confirmed_qty_total + {$confQtyStr}"),
                        'updated_at'          => now(),
                    ]);

                if ($affected === 0) {
                    return ['http' => 400, 'payload' => [
                        'status'  => 'error',
                        'message' => 'Qty konfirmasi melebihi sisa kuota (race protected).'
                    ]];
                }

                HistoryPro::create([
                    'history_wi_item_id' => $item->id,
                    'qty_pro'            => $confQty,
                    'status'             => 'confirmasi',
                    'remark_text'        => null,
                    'tag'                => null,
                ]);

                $item->refresh();

                $confirmedNew = (float) ($item->confirmed_qty_total ?? 0);
                $remarkNew    = (float) ($item->remark_qty_total ?? 0);
                $processedNew = $confirmedNew + $remarkNew;

                if ($assignedQty > 0 && $processedNew >= $assignedQty) {
                    $newStatus = ($remarkNew > 0) ? 'Completed With Remark' : 'Completed';
                } else {
                    if ($remarkNew > 0) $newStatus = 'Progress With Remark';
                    elseif ($confirmedNew > 0) $newStatus = 'Progress';
                    else $newStatus = 'Created';
                }

                $item->status = $newStatus;
                $item->save();

                return ['http' => 200, 'payload' => [
                    'status'              => 'success',
                    'message'             => "Konfirmasi berhasil disimpan untuk {$aufnr} ({$wiCode}).",
                    'new_status'          => $newStatus,
                    'assigned_qty'        => $assignedQty,
                    'confirmed_qty_total' => $confirmedNew,
                    'remark_qty_total'    => $remarkNew,
                    'processed_total'     => $processedNew,
                    'remaining_qty'       => max(0, $assignedQty - $processedNew),
                ], 'document_id' => $document->id, 'new_status' => $newStatus];

            }, 3, 1); // retry 3x, delay 1 detik

            // post-commit: update header (opsional) - aman dilakukan di luar transaksi
            if (($result['http'] ?? 500) === 200) {
                $st = $result['new_status'] ?? null;
                if (in_array($st, ['Completed', 'Completed With Remark'], true)) {
                    $this->updateDocumentStatusFast($result['document_id']);
                }
            }

            return response()->json($result['payload'], $result['http']);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengubah status PRO: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function completeWithRemark(Request $request)
    {
        $request->validate([
            'wi_code'     => 'required|string',
            'aufnr'       => 'required|string',
            'nik'         => 'required|string',
            'vornr'       => 'required|string',
            'remark'      => 'nullable|string',
            'remark_qty'  => 'required|numeric|min:0.001',
            'tag'         => 'nullable|string',
        ]);

        $wiCode    = $request->input('wi_code');
        $aufnr     = $request->input('aufnr');
        $nik       = $request->input('nik');
        $vornr     = $request->input('vornr');
        $remark    = $request->input('remark') ?? '';
        $remarkQty = (float) $request->input('remark_qty');
        $tag       = $request->input('tag') ?? '';
        $today     = Carbon::now()->toDateString();

        try {
            $result = $this->runTransactionWithDeadlockRetry(function () use (
                $wiCode, $aufnr, $nik, $vornr, $remark, $remarkQty, $tag, $today
            ) {
                // header aktif (tanpa lock)
                $document = HistoryWi::query()
                    ->where('wi_document_code', $wiCode)
                    ->whereDate('document_date', '<=', $today)
                    ->where(function ($q) use ($today) {
                        $q->whereNull('expired_at')
                        ->orWhereDate('expired_at', '>=', $today);
                    })
                    ->first();

                if (!$document) {
                    return ['http' => 404, 'payload' => [
                        'status'  => 'error',
                        'message' => 'Dokumen WI tidak ditemukan atau WI Inactive.'
                    ]];
                }

                // lock item (row-level)
                $wiItem = HistoryWiItem::query()
                    ->where('history_wi_id', $document->id)
                    ->where('aufnr', $aufnr)
                    ->where('vornr', $vornr)
                    ->where('nik', $nik)
                    ->lockForUpdate()
                    ->first();

                if (!$wiItem) {
                    return ['http' => 404, 'payload' => [
                        'status'  => 'error',
                        'message' => 'Item tidak ditemukan pada dokumen WI ini.'
                    ]];
                }

                $assignedQty = (float) ($wiItem->assigned_qty ?? 0);

                // pakai counter (tanpa SUM)
                $confirmed = (float) ($wiItem->confirmed_qty_total ?? 0);
                $remarkTot = (float) ($wiItem->remark_qty_total ?? 0);

                $processed = $confirmed + $remarkTot;
                $remaining = $assignedQty - $processed;

                if ($remarkQty > $remaining) {
                    return ['http' => 400, 'payload' => [
                        'status'  => 'error',
                        'message' => "Remark quantity melebihi sisa kuota. Remaining allow: {$remaining}.",
                        'meta'    => [
                            'assigned_qty'        => $assignedQty,
                            'confirmed_qty_total' => $confirmed,
                            'remark_qty_total'    => $remarkTot,
                            'processed_total'     => $processed,
                            'remaining_qty'       => max(0, $remaining),
                        ],
                    ]];
                }

                // decimal aman untuk SQL
                $remarkQtyStr = number_format($remarkQty, 3, '.', '');

                // atomic increment + guard
                $affected = HistoryWiItem::where('id', $wiItem->id)
                    ->whereRaw('(confirmed_qty_total + remark_qty_total + ?) <= assigned_qty', [$remarkQty])
                    ->update([
                        'remark_qty_total' => DB::raw("remark_qty_total + {$remarkQtyStr}"),
                        'updated_at'       => now(),
                    ]);

                if ($affected === 0) {
                    return ['http' => 400, 'payload' => [
                        'status'  => 'error',
                        'message' => 'Remark quantity melebihi sisa kuota (race protected).'
                    ]];
                }

                // audit trail
                HistoryPro::create([
                    'history_wi_item_id' => $wiItem->id,
                    'qty_pro'            => $remarkQty,
                    'status'             => 'remark',
                    'remark_text'        => $remark,
                    'tag'                => $tag,
                ]);

                // refresh counter terbaru
                $wiItem->refresh();

                $confirmedNew = (float) ($wiItem->confirmed_qty_total ?? 0);
                $remarkNew    = (float) ($wiItem->remark_qty_total ?? 0);
                $processedNew = $confirmedNew + $remarkNew;

                // hitung status
                if ($assignedQty > 0 && $processedNew >= $assignedQty) {
                    $newStatus = ($remarkNew > 0) ? 'Completed With Remark' : 'Completed';
                } else {
                    if ($remarkNew > 0) $newStatus = 'Progress With Remark';
                    elseif ($confirmedNew > 0) $newStatus = 'Progress';
                    else $newStatus = 'Created';
                }

                $wiItem->status = $newStatus;
                $wiItem->save();

                return [
                    'http'       => 200,
                    'payload'    => [
                        'status'              => 'success',
                        'message'             => 'Remark Added Successfully.',
                        'new_status'          => $newStatus,
                        'assigned_qty'        => $assignedQty,
                        'confirmed_qty_total' => $confirmedNew,
                        'remark_qty_total'    => $remarkNew,
                        'processed_total'     => $processedNew,
                        'remaining_qty'       => max(0, $assignedQty - $processedNew),
                    ],
                    'document_id' => $document->id,
                    'wi_item_id'  => $wiItem->id,
                    'new_status'  => $newStatus,
                ];
            }, 3, 1); // retry 3x, delay 1 detik

            // post-commit: update header hanya saat completed
            if (($result['http'] ?? 500) === 200) {
                $st = $result['new_status'] ?? null;
                if (in_array($st, ['Completed', 'Completed With Remark'], true)) {
                    $this->updateDocumentStatusFast($result['document_id']);
                }

                // ambil remark history di luar transaksi (lebih ringan)
                $remarkHistory = HistoryPro::query()
                    ->where('history_wi_item_id', $result['wi_item_id'])
                    ->where('status', 'remark')
                    ->orderBy('created_at', 'asc')
                    ->get(['qty_pro', 'remark_text', 'tag', 'created_at'])
                    ->toArray();

                $result['payload']['remark_history'] = $remarkHistory;
            }

            return response()->json($result['payload'], $result['http']);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function runTransactionWithDeadlockRetry(callable $callback, int $attempts = 3, int $sleepSeconds = 1)
    {
        for ($i = 1; $i <= $attempts; $i++) {
            try {
                return DB::transaction($callback); // 1 attempt per loop
            } catch (QueryException $e) {
                $driverCode = $e->errorInfo[1] ?? null;

                // MySQL InnoDB:
                // 1213 = Deadlock found when trying to get lock
                // 1205 = Lock wait timeout exceeded
                $isRetryable = in_array($driverCode, [1213, 1205], true);

                if ($isRetryable && $i < $attempts) {
                    sleep($sleepSeconds); // jarak antar retry = 1 detik
                    continue;
                }

                throw $e;
            }
        }

        // harusnya tidak pernah sampai sini
        return DB::transaction($callback);
    }

    private function updateDocumentStatusFast($historyWiId)
    {
        // masih ada item yang belum completed?
        $notCompleted = HistoryWiItem::where('history_wi_id', $historyWiId)
            ->whereNotIn('status', ['Completed', 'Completed With Remark'])
            ->count();

        if ($notCompleted > 0) {
            HistoryWi::where('id', $historyWiId)
                ->update(['status' => 'PROCESSED']);
            return;
        }

        $hasRemark = HistoryWiItem::where('history_wi_id', $historyWiId)
            ->where('status', 'Completed With Remark')
            ->exists();

        $newHeaderStatus = $hasRemark ? 'COMPLETED WITH REMARK' : 'COMPLETED';

        HistoryWi::where('id', $historyWiId)
            ->update(['status' => $newHeaderStatus]);
    }

    public function getRemarksByAufnr(Request $request)
    {
        $aufnr = $request->input('aufnr');

        if (empty($aufnr)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'AUFNR tidak boleh kosong.'
            ], 400);
        }

        $now   = Carbon::now();
        $today = $now->toDateString();
        $items = HistoryWiItem::query()
            ->where('aufnr', $aufnr)
            ->with(['wi', 'pros' => function($q) {
                // Eager load only remarks, ordered by created_at
                $q->where('status', 'remark')->orderBy('created_at', 'asc');
            }])
            ->get();

        $remarksData = [];

        foreach ($items as $item) {
            $wi = $item->wi;
            if (!$wi) continue;
            $operatorNik  = $item->nik ?? '-';
            $operatorName = $item->name1 ?? '-';
            $operatorInfo = "{$operatorNik} - {$operatorName}";
            
            // Use the eager-loaded 'pros' collection
            $history = $item->pros
                // No need to filter by status/order here as it's done in the eager load query
                ->map(function ($r) use ($operatorInfo) {
                    return [
                        'qty'        => (int) $r->qty_pro,
                        'remark'     => $r->remark_text ?? '',
                        'tag'        => $r->tag ?? '',
                        'created_at' => Carbon::parse($r->created_at)->toDateTimeString(),
                        'created_by' => $operatorInfo, 
                    ];
                })
                ->values() // Reset keys
                ->toArray();
                
            if (empty($history)) {
                continue;
            }

            $remarksData[] = [
                'wi_code'        => $wi->wi_document_code ?? '-',
                'document_date'  => $wi->document_date ? $wi->document_date->format('Y-m-d') : '-',
                'vornr'          => $item->vornr ?? '-',
                'material'       => $item->material_number ?? '-',
                'material_desc'  => $item->material_desc ?? '-',
                'operator'       => $operatorInfo,
                'history_wi_item_id' => $item->id,
                'history'        => $history,
            ];
        }

        return response()->json([
            'status' => 'success',
            'aufnr'  => $aufnr,
            'data'   => $remarksData,
        ]);
    }

    private function updateDocumentStatus($historyWiId)
    {
        $document = HistoryWi::find($historyWiId);
        if (!$document) return;

        $items = HistoryWiItem::where('history_wi_id', $historyWiId)->get();
        if ($items->isEmpty()) return;

        $allCompleted = true;
        $hasRemark    = false;

        foreach ($items as $item) {
            $st = strtoupper($item->status ?? '');
            
            // Check if item is completed
            if (!str_contains($st, 'COMPLETED')) {
                $allCompleted = false;
            }

            // Check if any remark exists on item (status includes REMARK or check history_pro if needed)
            // Simpler: check if status contains 'REMARK' or check remarkQty > 0
            if (str_contains($st, 'REMARK')) {
                $hasRemark = true;
            }
        }

        $newHeaderStatus = 'PROCESSED';

        if ($allCompleted) {
            $newHeaderStatus = $hasRemark ? 'COMPLETED WITH REMARK' : 'COMPLETED';
        }

        // Only update if changed
        // NOTE: if document is already expired? User says: "If exceeded expired... change status to EXPIRED".
        // That usually happens via schedule. But if we complete it LATE?
        // Usually Completed overrides Expired.
        
        if ($document->status !== $newHeaderStatus) {
            $document->status = $newHeaderStatus;
            $document->save();
        }
    }

    private function wiStartEnd($doc): array
    {
        $date = $doc->document_date instanceof CarbonInterface
            ? $doc->document_date->format('Y-m-d')
            : Carbon::parse($doc->document_date)->format('Y-m-d');

        if ((int)$doc->machining === 1) {
            $start = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            $end   = $doc->expired_at ? Carbon::parse($doc->expired_at) : $start->copy()->endOfDay();
            $end   = $end->endOfDay();
            return [$start, $end];
        }

        $time = $doc->document_time instanceof CarbonInterface
            ? $doc->document_time->format('H:i:s')
            : trim((string)($doc->document_time ?? ''));

        if ($time === '') $time = '00:00:00';
        if (preg_match('/^\d{2}:\d{2}$/', $time)) $time .= ':00';

        $start = Carbon::createFromFormat('Y-m-d H:i:s', "{$date} {$time}");
        $end   = $doc->expired_at ? Carbon::parse($doc->expired_at) : $start->copy()->addHours(24);

        return [$start, $end];
    }
}
