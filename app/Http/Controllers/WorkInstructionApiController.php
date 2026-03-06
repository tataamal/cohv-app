<?php

namespace App\Http\Controllers;
use App\Models\HistoryWi;
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
            ->with(['wi'])
            ->get();

        $remarksData = [];

        foreach ($items as $item) {
            $wi = $item->wi;
            if (!$wi) continue;
            
            $remarkQty = (float)($item->remark_qty_total ?? 0);
            if ($remarkQty <= 0) {
                continue;
            }

            $operatorNik  = $item->nik ?? '-';
            $operatorName = $item->name1 ?? '-';
            $operatorInfo = "{$operatorNik} - {$operatorName}";
            
            // Generate single history item from HistoryWiItem
            $history = [
                [
                    'qty'        => $remarkQty,
                    'remark'     => $item->remark_text ?? '',
                    'tag'        => $item->tag ?? '',
                    'created_at' => Carbon::parse($item->updated_at)->toDateTimeString(),
                    'created_by' => $operatorInfo,
                ]
            ];

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


    public function getWorkcentersLoad(Request $request)
    {
        try {
            // Check for date parameter (ddmmyyyy)
            $dateParam = $request->query('date');
            
            if ($dateParam) {
                try {
                    // Try parsing the ddmmyyyy format
                    $dateObj = Carbon::createFromFormat('dmY', $dateParam);
                    if ($dateObj === false) {
                        throw new \Exception();
                    }
                    $filterDate = $dateObj->toDateString();
                } catch (\Exception $ex) {
                    return response()->json([
                        'status'        => 'fail',
                        'mess_err'      => 'Format tanggal tidak valid. Gunakan format ddmmyyyy (contoh: 03032026).',
                        'http_response' => 400,
                    ], 400);
                }
            } else {
                // Default to today
                $filterDate = Carbon::now()->toDateString();
            }

            $loads = HistoryWiItem::select(
                DB::raw('COALESCE(child_wc, parent_wc) as workcenters'),
                DB::raw('SUM(calculated_takt_time) as assigned_time'),
                DB::raw('COUNT(id) as total_item_wi'),
                DB::raw('GROUP_CONCAT(DISTINCT nik) as niks_string'),
                DB::raw('SUM(assigned_qty) as total_quantity')
            )
            ->whereHas('wi', function ($query) use ($filterDate) {
                $query->whereDate('document_date', $filterDate);
            })
            ->whereNotNull(DB::raw('COALESCE(child_wc, parent_wc)'))
            ->groupByRaw('COALESCE(child_wc, parent_wc)')
            ->get();

            $formattedLoads = $loads->map(function ($load) {
                return [
                    'workcenters'              => $load->workcenters,
                    'assigned_time'            => (float) $load->assigned_time,
                    'jml_pro_vornr_nik_unique' => (int) $load->total_item_wi,
                    'nik'                      => $load->niks_string ? explode(',', $load->niks_string) : [],
                    'total_quantity'           => (float) $load->total_quantity
                ];  
            });

            return response()->json([
                'status'        => 'success',
                'mess_err'      => null,
                'http_response' => 200,
                'data'          => $formattedLoads
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'        => 'fail',
                'mess_err'      => $e->getMessage(),
                'http_response' => 500,
            ], 500);
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
