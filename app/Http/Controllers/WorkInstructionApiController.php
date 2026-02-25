<?php

namespace App\Http\Controllers;
use App\Models\HistoryWi;
use App\Models\HistoryPro;
use App\Models\HistoryWiItem;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
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
            ->where(function ($query) use ($today, $now) {
                // 1. Mode Machining
                // is_machining == true
                // document date <= today <= expired_at
                $query->where(function ($q) use ($today) {
                    $q->where('machining', 1)
                      ->whereDate('document_date', '<=', $today)
                      ->whereNotNull('expired_at') // Assumption: machining requires expired_at
                      ->whereDate('expired_at', '>=', $today);
                })
                // 2. Mode Longshift
                ->orWhere(function ($q) use ($today) {
                    $yesterday = Carbon::parse($today)->subDay()->toDateString();
                    $q->where('longshift', 1)
                      ->where(function($sub) {
                          $sub->where('machining', '!=', 1)
                              ->orWhereNull('machining');
                      })
                      ->where(function ($sub2) use ($today, $yesterday) {
                          $sub2->whereIn('document_date', [$today, $yesterday])
                               ->orWhereDate('expired_at', '>=', $today);
                      });
                })
                // 3. Mode Biasa (Normal)
                // document hanya dikirim apabila statusnya belum EXpited (date < today)
                // dan Statusnya bukan INACTIVE, COMPLETED, COMPLETED WITH REMARK, EXPIRED
                ->orWhere(function ($q) use ($today) {
                    $q->where(function($sub) {
                            $sub->where('longshift', '!=', 1)
                                ->orWhereNull('longshift');
                        })
                        ->where(function($sub) {
                            $sub->where('machining', '!=', 1)
                                ->orWhereNull('machining');
                        })
                        // Check expiry: valid if expired_at >= today OR null
                        ->where(function ($sub) use ($today) {
                            $sub->whereNull('expired_at')
                                ->orWhereDate('expired_at', '>=', $today);
                        })
                        // Check Status
                        ->whereNotIn('status', ['INACTIVE', 'COMPLETED', 'COMPLETED WITH REMARK', 'EXPIRED']);
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

        // [TEMPORARY FEATURE] Filter: Hanya tampilkan yang belum EXPIRED dan belum COMPLETED
        // Comment bagian ini jika ingin menonaktifkan filter
        // $query->whereNotIn('status', ['COMPLETED', 'COMPLETED WITH REMARK', 'EXPIRED']);
        // [END TEMPORARY FEATURE]

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
                    HistoryWi::where('id', $doc->id)->update(['status' => 'ACTIVE']);

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

        // Konsisten decimal (hindari beda rounding guard vs update)
        $rawConfQty = str_replace(',', '.', $request->input('confirmed_qty'));
        $confQty = round((float) $rawConfQty, 3);
        $confQtyStr = number_format($confQty, 3, '.', '');

        $todayStart = Carbon::today(); // 00:00:00
        $todayDate  = $todayStart->toDateString();

        try {
            // 1) Cari dokumen aktif (tanpa lock, query ringan)
            $document = HistoryWi::query()
                ->where('wi_document_code', $wiCode)
                ->where(function ($query) use ($todayDate) {
                    // 1. Machining
                    $query->where(function ($q) use ($todayDate) {
                        $q->where('machining', 1)
                          ->whereDate('document_date', '<=', $todayDate)
                          ->whereDate('expired_at', '>=', $todayDate);
                    })
                    // 2. Longshift
                    ->orWhere(function ($q) use ($todayDate) {
                        $yesterday = Carbon::parse($todayDate)->subDay()->toDateString();
                        $q->where('longshift', 1)
                          ->where(function($s) { $s->where('machining', '!=', 1)->orWhereNull('machining'); })
                          ->whereIn('document_date', [$todayDate, $yesterday]);
                    })
                    // 3. Normal
                    ->orWhere(function ($q) use ($todayDate) {
                        $q->where(function($s) { $s->where('longshift', '!=', 1)->orWhereNull('longshift'); })
                          ->where(function($s) { $s->where('machining', '!=', 1)->orWhereNull('machining'); })
                          ->where(function ($sub) use ($todayDate) {
                               $sub->whereNull('expired_at')
                                   ->orWhereDate('expired_at', '>=', $todayDate); // Reverted to >= as per logic
                           })
                          ->whereNotIn('status', ['INACTIVE', 'COMPLETED', 'COMPLETED WITH REMARK', 'EXPIRED']);
                    });
                })
                ->first(['id']);

            if (!$document) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Dokumen WI tidak ditemukan atau WI Inactive.'
                ], 404);
            }

            // 2) Ambil item id (tanpa lock). Harus ada index uq_wiitem_key agar cepat.
            $item = HistoryWiItem::query()
                ->where('history_wi_id', $document->id)
                ->where('aufnr', $aufnr)
                ->where('vornr', $vornr)
                ->where('nik', $nik)
                ->first(['id']);

            if (!$item) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Kombinasi AUFNR, VORNR dan NIK tidak ditemukan di dokumen ini.'
                ], 404);
            }

            // 3) Transaksi singkat: UPDATE atomic + insert HistoryPro
            $result = $this->runTransactionWithDeadlockRetry(function () use ($item, $confQty, $confQtyStr) {

                // Status dihitung langsung di SQL berdasarkan nilai setelah increment
                $statusCase = "
                    CASE
                        WHEN assigned_qty > 0
                        AND (confirmed_qty_total + remark_qty_total + {$confQtyStr}) >= assigned_qty
                            THEN CASE
                                    WHEN remark_qty_total > 0 THEN 'Completed With Remark'
                                    ELSE 'Completed'
                                END
                        WHEN remark_qty_total > 0
                            THEN 'Progress With Remark'
                        WHEN (confirmed_qty_total + {$confQtyStr}) > 0
                            THEN 'Progress'
                        ELSE 'Created'
                    END
                ";

                $affected = HistoryWiItem::where('id', $item->id)
                    ->whereRaw('(confirmed_qty_total + remark_qty_total + ?) <= assigned_qty', [$confQty])
                    ->update([
                        'status'              => DB::raw($statusCase),
                        'confirmed_qty_total' => DB::raw("confirmed_qty_total + {$confQtyStr}"),
                        'updated_at'          => now(),
                    ]);

                if ($affected === 0) {
                    // gagal karena kuota habis / race -> biar di-handle di luar transaksi
                    return ['ok' => false];
                }

                HistoryPro::create([
                    'history_wi_item_id' => $item->id,
                    'qty_pro'            => $confQty,
                    'status'             => 'confirmasi',
                    'remark_text'        => null,
                    'tag'                => null,
                ]);

                return ['ok' => true];
            }, 3, 1); // retry 3x, delay 1 detik

            if (empty($result['ok'])) {
                // Ambil snapshot terbaru untuk info remaining (di luar transaksi -> lock sudah lepas)
                $snap = HistoryWiItem::query()->where('id', $item->id)->first([
                    'assigned_qty', 'confirmed_qty_total', 'remark_qty_total'
                ]);

                if (!$snap) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Item tidak ditemukan.'
                    ], 404);
                }

                $assigned = (float)($snap->assigned_qty ?? 0);
                $confirmed = (float)($snap->confirmed_qty_total ?? 0);
                $remark = (float)($snap->remark_qty_total ?? 0);
                $processed = $confirmed + $remark;
                $remaining = $assigned - $processed;

                return response()->json([
                    'status'  => 'error',
                    'message' => "Qty konfirmasi melebihi sisa kuota. Remaining allow: {$remaining}.",
                    'meta'    => [
                        'assigned_qty'        => $assigned,
                        'confirmed_qty_total' => $confirmed,
                        'remark_qty_total'    => $remark,
                        'processed_total'     => $processed,
                        'remaining_qty'       => max(0, $remaining),
                    ],
                ], 400);
            }

            // 4) Post-commit: ambil data final untuk response
            $final = HistoryWiItem::query()->where('id', $item->id)->first([
                'assigned_qty', 'confirmed_qty_total', 'remark_qty_total', 'status'
            ]);

            $assigned = (float)($final->assigned_qty ?? 0);
            $confirmed = (float)($final->confirmed_qty_total ?? 0);
            $remark = (float)($final->remark_qty_total ?? 0);
            $processed = $confirmed + $remark;

            // 5) Update header jika item completed (di luar transaksi -> aman)
            if (in_array($final->status ?? '', ['Completed', 'Completed With Remark'], true)) {
                $this->updateDocumentStatusFast($document->id);
            }

            return response()->json([
                'status'              => 'success',
                'message'             => "Konfirmasi berhasil disimpan untuk {$aufnr} ({$wiCode}).",
                'new_status'          => $final->status,
                'assigned_qty'        => $assigned,
                'confirmed_qty_total' => $confirmed,
                'remark_qty_total'    => $remark,
                'processed_total'     => $processed,
                'remaining_qty'       => max(0, $assigned - $processed),
            ], 200);

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
        $remark    = (string)($request->input('remark') ?? '');
        $tag       = (string)($request->input('tag') ?? '');

        $rawRemarkQty = str_replace(',', '.', $request->input('remark_qty'));
        $remarkQty = round((float) $rawRemarkQty, 3);
        $remarkQtyStr = number_format($remarkQty, 3, '.', '');

        $todayStart = Carbon::today();
        $todayDate  = $todayStart->toDateString();

        try {
            // 1) Dokumen aktif
            $document = HistoryWi::query()
                ->where('wi_document_code', $wiCode)
                ->where(function ($query) use ($todayDate) {
                    // 1. Machining
                    $query->where(function ($q) use ($todayDate) {
                        $q->where('machining', 1)
                          ->whereDate('document_date', '<=', $todayDate)
                          ->whereDate('expired_at', '>=', $todayDate);
                    })
                    // 2. Longshift
                    ->orWhere(function ($q) use ($todayDate) {
                        $yesterday = Carbon::parse($todayDate)->subDay()->toDateString();
                        $q->where('longshift', 1)
                          ->where(function($s) { $s->where('machining', '!=', 1)->orWhereNull('machining'); })
                          ->whereIn('document_date', [$todayDate, $yesterday]);
                    })
                    // 3. Normal
                    ->orWhere(function ($q) use ($todayDate) {
                        $q->where(function($s) { $s->where('longshift', '!=', 1)->orWhereNull('longshift'); })
                          ->where(function($s) { $s->where('machining', '!=', 1)->orWhereNull('machining'); })
                          ->where(function ($sub) use ($todayDate) {
                               $sub->whereNull('expired_at')
                                   ->orWhereDate('expired_at', '>=', $todayDate);
                           })
                          ->whereNotIn('status', ['INACTIVE', 'COMPLETED', 'COMPLETED WITH REMARK', 'EXPIRED']);
                    });
                })
                ->first(['id']);

            if (!$document) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Dokumen WI tidak ditemukan atau WI Inactive.'
                ], 404);
            }

            // 2) Item id (tanpa lock)
            $item = HistoryWiItem::query()
                ->where('history_wi_id', $document->id)
                ->where('aufnr', $aufnr)
                ->where('vornr', $vornr)
                ->where('nik', $nik)
                ->first(['id']);

            if (!$item) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Item tidak ditemukan pada dokumen WI ini.'
                ], 404);
            }

            // 3) Transaksi singkat: UPDATE atomic + insert HistoryPro
            $result = $this->runTransactionWithDeadlockRetry(function () use ($item, $remarkQty, $remarkQtyStr, $remark, $tag) {

                // Setelah remark ditambah, status hanya 2 kemungkinan:
                // - Completed With Remark (kalau sudah >= assigned)
                // - Progress With Remark (kalau belum)
                $statusCase = "
                    CASE
                        WHEN assigned_qty > 0
                        AND (confirmed_qty_total + remark_qty_total + {$remarkQtyStr}) >= assigned_qty
                            THEN 'Completed With Remark'
                        ELSE 'Progress With Remark'
                    END
                ";

                $affected = HistoryWiItem::where('id', $item->id)
                    ->whereRaw('(confirmed_qty_total + remark_qty_total + ?) <= assigned_qty', [$remarkQty])
                    ->update([
                        'status'           => DB::raw($statusCase),
                        'remark_qty_total' => DB::raw("remark_qty_total + {$remarkQtyStr}"),
                        'updated_at'       => now(),
                    ]);

                if ($affected === 0) {
                    return ['ok' => false];
                }

                HistoryPro::create([
                    'history_wi_item_id' => $item->id,
                    'qty_pro'            => $remarkQty,
                    'status'             => 'remark',
                    'remark_text'        => $remark,
                    'tag'                => $tag,
                ]);

                return ['ok' => true];
            }, 3, 1);

            if (empty($result['ok'])) {
                $snap = HistoryWiItem::query()->where('id', $item->id)->first([
                    'assigned_qty', 'confirmed_qty_total', 'remark_qty_total'
                ]);

                if (!$snap) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Item tidak ditemukan.'
                    ], 404);
                }

                $assigned = (float)($snap->assigned_qty ?? 0);
                $confirmed = (float)($snap->confirmed_qty_total ?? 0);
                $remarkTot = (float)($snap->remark_qty_total ?? 0);
                $processed = $confirmed + $remarkTot;
                $remaining = $assigned - $processed;

                return response()->json([
                    'status'  => 'error',
                    'message' => "Remark quantity melebihi sisa kuota. Remaining allow: {$remaining}.",
                    'meta'    => [
                        'assigned_qty'        => $assigned,
                        'confirmed_qty_total' => $confirmed,
                        'remark_qty_total'    => $remarkTot,
                        'processed_total'     => $processed,
                        'remaining_qty'       => max(0, $remaining),
                    ],
                ], 400);
            }

            // 4) Post-commit: data final
            $final = HistoryWiItem::query()->where('id', $item->id)->first([
                'assigned_qty', 'confirmed_qty_total', 'remark_qty_total', 'status'
            ]);

            $assigned = (float)($final->assigned_qty ?? 0);
            $confirmed = (float)($final->confirmed_qty_total ?? 0);
            $remarkTot = (float)($final->remark_qty_total ?? 0);
            $processed = $confirmed + $remarkTot;

            // update header jika completed
            if (in_array($final->status ?? '', ['Completed', 'Completed With Remark'], true)) {
                $this->updateDocumentStatusFast($document->id);
            }

            // remark history (di luar transaksi)
            $remarkHistory = HistoryPro::query()
                ->where('history_wi_item_id', $item->id)
                ->where('status', 'remark')
                ->orderBy('created_at', 'asc')
                ->get(['qty_pro', 'remark_text', 'tag', 'created_at'])
                ->toArray();

            return response()->json([
                'status'              => 'success',
                'message'             => 'Remark Added Successfully.',
                'new_status'          => $final->status,
                'assigned_qty'        => $assigned,
                'confirmed_qty_total' => $confirmed,
                'remark_qty_total'    => $remarkTot,
                'processed_total'     => $processed,
                'remaining_qty'       => max(0, $assigned - $processed),
                'remark_history'      => $remarkHistory,
            ], 200);

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
                        'qty'        => (float) $r->qty_pro,
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
