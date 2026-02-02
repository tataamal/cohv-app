<?php

namespace App\Http\Controllers;
use App\Models\HistoryWi;
use App\Models\HistoryPro;
use App\Models\HistoryWiItem;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkInstructionApiController extends Controller
{
    public function getUniqueUnexpiredAufnrs()
    {
        $now   = Carbon::now();
        $today = $now->toDateString();
        $baseHeaderQuery = HistoryWi::query()
            ->whereNull('deleted_at') // opsional (SoftDeletes biasanya sudah otomatis)
            ->whereDate('document_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('expired_at')
                ->orWhereDate('expired_at', '>=', $today);
            });

        $machiningHistories = (clone $baseHeaderQuery)
            ->where('machining', 1)
            ->with(['items' => function ($q) {
                $q->where('status', '!=', 'Completed')
                ->whereNotNull('aufnr')
                ->whereNotNull('vornr')
                ->whereNotNull('nik');
            }])
            ->get();

        // Ambil non-machining headers
        $nonMachiningHistories = (clone $baseHeaderQuery)
            ->where(function ($q) {
                $q->whereNull('machining')->orWhere('machining', '!=', 1);
            })
            ->with(['items' => function ($q) {
                $q->where('status', '!=', 'Completed')
                ->whereNotNull('aufnr')
                ->whereNotNull('vornr')
                ->whereNotNull('nik');
            }])
            ->get();

        $histories = $machiningHistories->concat($nonMachiningHistories);

        $grouped = [];
        $seen = [];

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

                if ($isMachining) {
                    // ambil SSAVD/SSSLD dengan cara paling aman (case mismatch)
                    $attrs = $item->getAttributes();
                    $ssavdRaw = $attrs['SSAVD'] ?? $attrs['ssavd'] ?? null;
                    $sssldRaw = $attrs['SSSLD'] ?? $attrs['sssld'] ?? null;

                    if (!$ssavdRaw || !$sssldRaw) continue;

                    try {
                        $ssavd = Carbon::parse($ssavdRaw);
                        $sssld = Carbon::parse($sssldRaw);

                        // kalau value-nya date-only, anggap full-day
                        if (is_string($ssavdRaw) && strlen($ssavdRaw) <= 10) $ssavd = $ssavd->startOfDay();
                        if (is_string($sssldRaw) && strlen($sssldRaw) <= 10) $sssld = $sssld->endOfDay();
                    } catch (\Throwable $e) {
                        continue;
                    }

                    if (!$now->between($ssavd, $sssld, true)) continue;
                }

                $uniqueKey = $aufnr . '_' . $vornr . '_' . $nik;
                if (isset($seen[$uniqueKey])) continue;

                $grouped[$wiCode][] = [
                    'aufnr' => $aufnr,
                    'vornr' => $vornr,
                    'nik'   => $nik,
                ];

                $seen[$uniqueKey] = true;
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => $grouped,
            'count'  => count($seen),
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
            ->whereDate('document_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('expired_at')
                ->orWhereDate('expired_at', '>=', $today);
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

        if ($documents->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Dokumen WI tidak ditemukan atau WI Inactive.'
            ], 404);
        }

        $hasAssignableItems = false;
        $allCompleted       = false;

        $mappedDocuments = $documents->map(function ($doc) use ($nik, $now, &$hasAssignableItems, &$allCompleted) {
            $isMachining = ((int) $doc->machining === 1);
            $rawItems = $doc->items;

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
                $status = $item->status ?? '';
                return !in_array($status, ['Completed', 'Completed With Remark'], true);
            })->values();

            if ($rawItems->isNotEmpty() && $pendingItems->isEmpty()) {
                $allCompleted = true;
            }

            $historyWiItems = $pendingItems->map(function ($item) {
                $arr = $item->toArray();
                $arr['status_pro_wi'] = $item->status ?? null;
                return $arr;
            })->toArray();

            return [
                'wi_code'        => $doc->wi_document_code,
                'plant_code'     => $doc->plant_code,
                'workcenter'     => $doc->workcenter,
                'document_date'  => $doc->document_date,
                'document_time'  => $doc->document_time,
                'expired_at'     => $doc->expired_at,
                'machining'      => (int) $doc->machining,
                'history_wi_item'=> $historyWiItems,
            ];
        });

        $finalDocuments = $mappedDocuments->filter(function ($doc) {
            return !empty($doc['history_wi_item']);
        })->values();

        if ($finalDocuments->isEmpty()) {
            if ($hasAssignableItems && $allCompleted) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Semua dokumen yang ditugaskan telah terselesaikan'
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
            'confirmed_qty'  => 'required|integer|min:1', // qty_pro int
            'nik'            => 'required|string',
            'vornr'          => 'required|string',
        ]);

        $wiCode  = $request->input('wi_code');
        $aufnr   = $request->input('aufnr');
        $nik     = $request->input('nik');
        $vornr   = $request->input('vornr');
        $confQty = (int) $request->input('confirmed_qty');

        $now   = Carbon::now();
        $today = $now->toDateString();

        try {
            DB::beginTransaction();

            // Header aktif
            $document = HistoryWi::query()
                ->where('wi_document_code', $wiCode)
                ->whereDate('document_date', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('expired_at')
                    ->orWhereDate('expired_at', '>=', $today);
                })
                ->lockForUpdate()
                ->first();

            if (!$document) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Dokumen WI tidak ditemukan atau WI Inactive.'
                ], 404);
            }

            // Item unik
            $item = HistoryWiItem::query()
                ->where('history_wi_id', $document->id)
                ->where('aufnr', $aufnr)
                ->where('vornr', $vornr)
                ->where('nik', $nik)
                ->lockForUpdate()
                ->first();

            if (!$item) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Kombinasi AUFNR, VORNR dan NIK tidak ditemukan di dokumen ini.'
                ], 404);
            }

            $isMachining = ((int) $document->machining === 1);
            if ($isMachining) {
                $ssavd = $item->ssavd ? Carbon::parse($item->ssavd)->startOfDay() : null;
                $sssld = $item->sssld ? Carbon::parse($item->sssld)->endOfDay() : null;

                if (!$ssavd || !$sssld) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'SSAVD/SSSLD tidak ditemukan pada item machining.'
                    ], 400);
                }

                if (!$now->between($ssavd, $sssld, true)) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Item machining sudah di luar range SSAVD/SSSLD.'
                    ], 400);
                }
            }

            $assignedQty = (float) ($item->assigned_qty ?? 0);
            $confirmedTotal = (int) HistoryPro::where('history_wi_item_id', $item->id)
                ->where('status', 'confirmasi')
                ->sum('qty_pro');

            $remarkTotal = (int) HistoryPro::where('history_wi_item_id', $item->id)
                ->where('status', 'remark')
                ->sum('qty_pro');

            $processed = $confirmedTotal + $remarkTotal;
            $remaining = $assignedQty - $processed;

            if ($confQty > $remaining) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => "Qty konfirmasi melebihi sisa kuota. Remaining allow: {$remaining}.",
                    'meta'    => [
                        'assigned_qty'        => $assignedQty,
                        'confirmed_qty_total' => $confirmedTotal,
                        'remark_qty_total'    => $remarkTotal,
                        'processed_total'     => $processed,
                        'remaining_qty'       => max(0, $remaining),
                    ],
                ], 400);
            }

            // Insert confirmation row
            HistoryPro::create([
                'history_wi_item_id' => $item->id,
                'qty_pro'            => $confQty,
                'status'             => 'confirmasi',
                'remark_text'        => null,
                'tag'                => null,
            ]);
            $confirmedTotalNew = $confirmedTotal + $confQty;
            $processedNew = $confirmedTotalNew + $remarkTotal;

            if ($assignedQty > 0 && $processedNew >= $assignedQty) {
                $newStatus = ($remarkTotal > 0) ? 'Completed With Remark' : 'Completed';
            } else {
                if ($remarkTotal > 0) $newStatus = 'Progress With Remark';
                elseif ($confirmedTotalNew > 0) $newStatus = 'Progress';
                else $newStatus = 'Created';
            }

            $item->status = $newStatus;
            $item->save();

            DB::commit();

            return response()->json([
                'status'              => 'success',
                'message'             => "Konfirmasi berhasil disimpan untuk {$aufnr} ({$wiCode}).",
                'new_status'          => $newStatus,
                'assigned_qty'        => $assignedQty,
                'confirmed_qty_total' => $confirmedTotalNew,
                'remark_qty_total'    => $remarkTotal,
                'processed_total'     => $processedNew,
                'remaining_qty'       => max(0, $assignedQty - $processedNew),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
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
            'remark_qty'  => 'required|integer|min:1', // qty_pro int
            'tag'         => 'nullable|string',
        ]);

        $wiCode    = $request->input('wi_code');
        $aufnr     = $request->input('aufnr');
        $nik       = $request->input('nik');
        $vornr     = $request->input('vornr');
        $remark    = $request->input('remark') ?? '';
        $remarkQty = (int) $request->input('remark_qty');
        $tag       = $request->input('tag') ?? '';

        $now   = Carbon::now();
        $today = $now->toDateString();

        try {
            DB::beginTransaction();
            $document = HistoryWi::query()
                ->where('wi_document_code', $wiCode)
                ->whereDate('document_date', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('expired_at')
                    ->orWhereDate('expired_at', '>=', $today);
                })
                ->lockForUpdate()
                ->first();

            if (!$document) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Dokumen WI tidak ditemukan atau WI Inactive.'
                ], 404);
            }

            $wiItem = HistoryWiItem::query()
                ->where('history_wi_id', $document->id)
                ->where('aufnr', $aufnr)
                ->where('vornr', $vornr)
                ->where('nik', $nik)
                ->lockForUpdate()
                ->first();

            if (!$wiItem) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Item tidak ditemukan pada dokumen WI ini.'
                ], 404);
            }

            $isMachining = ((int) $document->machining === 1);
            if ($isMachining) {
                $attrs = $wiItem->getAttributes();

                // prioritas pakai property cast, fallback ke uppercase/lowercase attribute
                $ssavdRaw = $wiItem->ssavd ?? ($attrs['SSAVD'] ?? $attrs['ssavd'] ?? null);
                $sssldRaw = $wiItem->sssld ?? ($attrs['SSSLD'] ?? $attrs['sssld'] ?? null);

                if (!$ssavdRaw || !$sssldRaw) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'SSAVD/SSSLD tidak ditemukan pada item machining.'
                    ], 400);
                }

                try {
                    $ssavd = Carbon::parse($ssavdRaw)->startOfDay();
                    $sssld = Carbon::parse($sssldRaw)->endOfDay();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Format SSAVD/SSSLD tidak valid.'
                    ], 400);
                }

                if (!$now->between($ssavd, $sssld, true)) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Item machining sudah di luar range SSAVD/SSSLD.'
                    ], 400);
                }
            }

            $assignedQty = (float) ($wiItem->assigned_qty ?? 0);
            $confirmedQtyTotal = (int) HistoryPro::query()
                ->where('history_wi_item_id', $wiItem->id)
                ->where('status', 'confirmasi')
                ->sum('qty_pro');

            $remarkQtyTotal = (int) HistoryPro::query()
                ->where('history_wi_item_id', $wiItem->id)
                ->where('status', 'remark')
                ->sum('qty_pro');

            $processedTotal = (float) ($confirmedQtyTotal + $remarkQtyTotal);
            $remaining = (float) ($assignedQty - $processedTotal);
            $eps = 0.000001;

            if (($remarkQty - $remaining) > $eps) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => "Remark quantity melebihi sisa kuota. Remaining allow: {$remaining}.",
                    'meta'    => [
                        'assigned_qty'        => $assignedQty,
                        'confirmed_qty_total' => $confirmedQtyTotal,
                        'remark_qty_total'    => $remarkQtyTotal,
                        'processed_total'     => $processedTotal,
                        'remaining_qty'       => max(0, $remaining),
                    ],
                ], 400);
            }
            HistoryPro::create([
                'history_wi_item_id' => $wiItem->id,
                'qty_pro'            => $remarkQty,
                'status'             => 'remark',
                'remark_text'        => $remark,
                'tag'                => $tag,
            ]);
            $remarkQtyTotalNew = $remarkQtyTotal + $remarkQty;
            $processedTotalNew = (float) ($confirmedQtyTotal + $remarkQtyTotalNew);

            if ($assignedQty > 0 && ($processedTotalNew + $eps) >= $assignedQty) {
                $newStatus = ($remarkQtyTotalNew > 0) ? 'Completed With Remark' : 'Completed';
            } else {
                if ($remarkQtyTotalNew > 0) {
                    $newStatus = 'Progress With Remark';
                } elseif ($confirmedQtyTotal > 0) {
                    $newStatus = 'Progress';
                } else {
                    $newStatus = 'Created';
                }
            }

            $wiItem->status = $newStatus;
            $wiItem->save();
            $remarkHistory = HistoryPro::query()
                ->where('history_wi_item_id', $wiItem->id)
                ->where('status', 'remark')
                ->orderBy('created_at', 'asc')
                ->get(['qty_pro', 'remark_text', 'tag', 'created_at'])
                ->toArray();

            DB::commit();

            return response()->json([
                'status'              => 'success',
                'message'             => 'Remark Added Successfully.',
                'new_status'          => $newStatus,
                'assigned_qty'        => $assignedQty,
                'confirmed_qty_total' => $confirmedQtyTotal,
                'remark_qty_total'    => $remarkQtyTotalNew,
                'processed_total'     => $processedTotalNew,
                'remaining_qty'       => max(0, $assignedQty - $processedTotalNew),
                'remark_history'      => $remarkHistory,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
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
            ->with('wi')
            ->get();

        $remarksData = [];

        foreach ($items as $item) {
            $wi = $item->wi;
            if (!$wi) continue;
            $operatorNik  = $item->nik ?? '-';
            $operatorName = $item->name1 ?? '-';
            $operatorInfo = "{$operatorNik} - {$operatorName}";
            $history = HistoryPro::query()
                ->where('history_wi_item_id', $item->id)
                ->where('status', 'remark')
                ->orderBy('created_at', 'asc')
                ->get(['qty_pro', 'remark_text', 'tag', 'created_at'])
                ->map(function ($r) use ($operatorInfo) {
                    return [
                        'qty'        => (int) $r->qty_pro,
                        'remark'     => $r->remark_text ?? '',
                        'tag'        => $r->tag ?? '',
                        'created_at' => Carbon::parse($r->created_at)->toDateTimeString(),
                        'created_by' => $operatorInfo, // kalau nanti ada kolom created_by di history_pro, ganti dari sini
                    ];
                })
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
}
