<?php

namespace App\Http\Controllers;
use App\Models\HistoryWi;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkInstructionApiController extends Controller
{
    public function getUniqueUnexpiredAufnrs()
    {
        // [MODIFIKASI] Menggunakan relasi items dan menonaktifkan filter expired_at sementara (karena tidak ada di migration baru)
        // Jika ada logika expiry baru, hubungkan di sini.
        // Asumsi: Filter document_date masih valid.
        $histories = HistoryWi::with('items')
                        ->where('document_date', '<', Carbon::now())
                        ->get();

        $groupedAufnrs = [];
        $allUniqueAufnrs = [];

        foreach ($histories as $history) {
            $wiCode = $history->wi_document_code;
            
            foreach ($history->items as $item) {
                // Item adalah model HistoryWiItem
                $aufnr = $item->aufnr ?? null;
                $vornr = $item->vornr ?? null;
                $status = $item->status_item ?? 'Created'; // Mapping: status_pro_wi -> status_item

                if ($aufnr && $status !== 'Completed') {
                    if (!isset($groupedAufnrs[$wiCode])) {
                        $groupedAufnrs[$wiCode] = [];
                    }
                    $uniqueKey = $aufnr . '_' . $vornr;

                    if (!isset($allUniqueAufnrs[$uniqueKey])) {
                        $groupedAufnrs[$wiCode][] = [
                            'aufnr' => $aufnr,
                            'vornr' => $vornr
                        ];
                        $allUniqueAufnrs[$uniqueKey] = true;
                    }
                }
            }
        }
        $totalCount = count($allUniqueAufnrs);

        return response()->json([
            'status' => 'success',
            'data' => $groupedAufnrs, 
            'count' => $totalCount
        ]);
    }

    public function getWiDocumentByCode(Request $request)
    {
        $request->validate([
            'wi_code' => 'nullable|string',
            'nik' => 'nullable|string',
        ]);

        $code = $request->input('wi_code');
        $nik = $request->input('nik');

        if (!$code && !$nik) {
            return response()->json([
                'status' => 'error',
                'message' => 'Harap masukkan WI Code atau NIK.'
            ], 400);
        }

        // [MODIFIKASI] Query structure update
        $query = HistoryWi::with('items')
                          ->where('document_date', '<', Carbon::now());
        
        // Note: Filter expired_at dihilangkan karena tidak ada kolom di tabel baru.
        // Jika perlu, tambahkan logika status.

        if ($code) {
            $query->where('wi_document_code', $code);
        }

        // Jika filter by NIK, kita filter di level query parent (whereHas) agar efisien
        if ($nik) {
            $query->whereHas('items', function($q) use ($nik) {
                $q->where('nik', $nik);
            });
        }

        $documents = $query->get();

        if ($documents->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dokumen WI tidak ditemukan, WI Inactive, atau NIK tidak sesuai.'
            ], 404);
        }

        $hasAssignableItems = false;
        $allCompleted = false;

        $mappedDocuments = $documents->map(function ($doc) use ($nik, &$hasAssignableItems, &$allCompleted) {
            // Filter items sesuai NIK (jika ada) dan Status
            $rawItems = $doc->items;

            if ($nik) {
                $rawItems = $rawItems->where('nik', $nik);
            }
            
            // Konversi collection model ke array untuk memproses logika selanjutnya
            $proItems = [];
            foreach ($rawItems as $item) {
                // Map model fields to array structure expected by frontend (if needed)
                // Atau biarkan apa adanya jika frontend adaptif
                $itemArr = $item->toArray();
                $itemArr['status_pro_wi'] = $item->status_item; // Forward compatibility mapping
                $proItems[] = $itemArr;
            }

            if (!empty($proItems)) {
                $hasAssignableItems = true;

                $pendingItems = array_values(array_filter($proItems, function ($item) {
                     $status = $item['status_item'] ?? '';
                     return !in_array($status, ['Completed', 'Completed With Remark']);
                }));

                if (empty($pendingItems) && !empty($proItems)) {
                    $allCompleted = true; 
                }
                
                $proItems = $pendingItems;
            }

            return [
                'wi_code' => $doc->wi_document_code,
                'plant_code' => $doc->plant_code,
                'workcenter_code' => $doc->workcenter_induk, // Renamed
                'document_date' => $doc->document_date,
                'document_time' => $doc->document_time,
                'expired_at' => null, // Column removed
                'pro_items' => $proItems,
            ];
        });
        
        $finalDocuments = $mappedDocuments->filter(function($doc) {
             return !empty($doc['pro_items']);
        })->values();

        if ($finalDocuments->isEmpty()) {
            if ($hasAssignableItems && $allCompleted) {
                 return response()->json([
                    'status' => 'error', 
                    'message' => 'Semua dokumen yang ditugaskan telah terselesaikan'
                ], 404); 
            }
             return response()->json([
                'status' => 'error',
                'message' => 'Dokumen WI tidak ditemukan atau tidak ada tugas untuk NIK ini.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'wi_documents' => $finalDocuments,
        ]);
    }
    
    public function completeProStatus(Request $request)
    {
        $request->validate([
            'wi_code' => 'required|string|exists:history_wi,wi_document_code',
            'aufnr' => 'required|string',
            'confirmed_qty' => 'required|numeric', 
            'nik' => 'required|string',
            'vornr' => 'required|string',
        ]);

        $wiCode = $request->input('wi_code');
        $aufnrToComplete = $request->input('aufnr');
        $confQty = (float) $request->input('confirmed_qty'); 
        $nik = $request->input('nik');
        $vornr = $request->input('vornr');
        
        if ($confQty <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Qty konfirmasi harus lebih besar dari 0.'], 400);
        }

        try {
            DB::beginTransaction();

            $document = HistoryWi::where('wi_document_code', $wiCode)->first();

            if (!$document) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Dokumen WI tidak ditemukan.'], 404);
            }

            // Cari Item spesifik
            $item = $document->items()
                        ->where('aufnr', $aufnrToComplete)
                        ->where('vornr', $vornr)
                        ->where('nik', $nik)
                        ->lockForUpdate()
                        ->first();

            if (!$item) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Kombinasi AUFNR, VORNR dan NIK tidak ditemukan di dalam dokumen ini.'], 404);
            }

            $assignedQty = (float) $item->assigned_qty;
            $currentConfirmedQty = (float) $item->confirmed_qty;

            if (($currentConfirmedQty + $confQty) > $assignedQty) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error', 
                    'message' => "Total Qty konfirmasi ({$currentConfirmedQty} + {$confQty}) melebihi Qty dialokasikan ({$assignedQty})."
                ], 400);
            }

            $newConfirmedQty = $currentConfirmedQty + $confQty;
            $newConfirmedQty = round($newConfirmedQty, 4); 
            
            $item->confirmed_qty = $newConfirmedQty;
            
            // Simpan info last confirmed di item_json atau kolom lain jika ada?
            // Migration tidak punya kolom last_confirmed_at. Gunakan item_json.
            $extraData = $item->item_json ? json_decode($item->item_json, true) : [];
            $extraData['last_confirmed_at'] = Carbon::now()->toDateTimeString();
            $extraData['last_confirmed_by_nik'] = $nik;
            $item->item_json = json_encode($extraData);

            $remarkQty = (float) $item->remark_qty;
            $totalProcessed = $newConfirmedQty + $remarkQty;
            $totalProcessed = round($totalProcessed, 4);

            $documentCompleted = false;

            if ($totalProcessed >= $assignedQty) {
                 if ($remarkQty > 0 || !empty($item->remark_text)) {
                    $item->status_item = 'Completed With Remark';
                 } else {
                    $item->status_item = 'Completed';
                 }
                // Simpan info completed di item_json
                $extraData['completed_at'] = Carbon::now()->toDateTimeString();
                $extraData['completed_by_nik'] = $nik;
                $item->item_json = json_encode($extraData);
                
                $documentCompleted = true;

            } elseif ($newConfirmedQty > 0 || $remarkQty > 0) {
                if ($remarkQty > 0 || !empty($item->remark_text)) {
                    $item->status_item = 'Progress With Remark';
                } else {
                    $item->status_item = 'Progress';
                }
                $documentCompleted = false; 

            } else {
                $item->status_item = 'Created';
            }
            
            $item->save();

            DB::commit();

            $finalStatusMessage = $documentCompleted ? 'berhasil diselesaikan (Completed).' : 'diperbarui.';
            
            return response()->json([
                'status' => 'success',
                'message' => "Status PRO {$aufnrToComplete} di dokumen {$wiCode} {$finalStatusMessage}",
                'new_status' => $item->status_item ?? 'Unknown',
                'confirmed_qty_total' => $newConfirmedQty,
                'remark_qty' => $remarkQty,
                'assigned_qty' => $assignedQty,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API Error completing PRO status:', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Gagal mengubah status PRO: ' . $e->getMessage()], 500);
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
            'remark_qty'  => 'nullable|numeric',
            'tag'         => 'nullable|string',
        ]);

        $wiCode    = $request->input('wi_code');
        $aufnr     = $request->input('aufnr');
        $nik       = $request->input('nik');
        $vornr     = $request->input('vornr');
        $remark    = $request->input('remark') ?? '';
        $remarkQty = $request->input('remark_qty') ?? 0;
        $tag       = $request->input('tag') ?? ''; 

        try {
            DB::beginTransaction();

            $document = HistoryWi::where('wi_document_code', $wiCode)->first();

            if (!$document) {
                DB::rollBack();
                return response()->json(['message' => 'Document not found.'], 404);
            }

            $item = $document->items()
                        ->where('aufnr', $aufnr)
                        ->where('vornr', $vornr)
                        ->where('nik', $nik)
                        ->lockForUpdate()
                        ->first();

            if (!$item) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Item not found in payload.'], 404);
            }

            $existingRemarkQty = (float) $item->remark_qty;
            $assignedQty       = (float) $item->assigned_qty;
            $confirmedQty      = (float) $item->confirmed_qty;

            $inputRemarkQty = (float) $remarkQty;
            $inputRemark    = $remark;
            $inputTag       = $tag;

            $newRemarkQty = $existingRemarkQty + $inputRemarkQty;
            $totalProcessed = $confirmedQty + $newRemarkQty;
            $totalProcessed = round($totalProcessed, 4);

            if ($totalProcessed > $assignedQty) {
                DB::rollBack();
                $remaining = $assignedQty - $confirmedQty - $existingRemarkQty;
                return response()->json([
                    'status' => 'error',
                    'message' => "Remark quantity exceeds remaining quantity. Existing Remark: {$existingRemarkQty}, Remaining allow: {$remaining}."
                ], 400);
            }

            // Handle Remark History via item_json
            $extraData = $item->item_json ? json_decode($item->item_json, true) : [];
            $remarkHistory = $extraData['remark_history'] ?? [];
            if (!is_array($remarkHistory)) $remarkHistory = [];

            if (!empty($inputTag)) {
                $item->tag = $inputTag;
            }

            if ($inputRemarkQty > 0 || !empty($inputRemark) || !empty($inputTag)) {
                $remarkHistory[] = [
                    'qty'        => $inputRemarkQty,
                    'remark'     => $inputRemark,
                    'tag'        => $inputTag,
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'created_by' => $request->input('nik') ?? 'System',
                ];
            }

            $extraData['remark_history'] = $remarkHistory;
            $item->item_json = json_encode($extraData);

            // Build display string
            $displayRemarks = [];
            foreach ($remarkHistory as $h) {
                $q = floatval($h['qty'] ?? 0);
                $m = $h['remark'] ?? '-';
                $t = $h['tag'] ?? '';

                if ($q > 0 || !empty($m) || !empty($t)) {
                    $tagText = !empty($t) ? " [{$t}]" : "";
                    $displayRemarks[] = "Qty {$q}{$tagText}: {$m}";
                }
            }

            $newRemarkText = implode('; ', $displayRemarks);
            // Truncate if too long for DB column (30 chars in migration seems small for multiple remarks?)
            // Migration: remark_text string(30). CAREFUL.
            // If it's just 30 chars, we can't store full history string there.
            // We should trust item_json for full history and maybe put summary or "see detail" in remark_text if it's strictly display.
            // Or maybe migration was typo and it should be text?
            // Assuming strict 30 char limit:
            $item->remark_text = substr($newRemarkText, 0, 30); 
            $item->remark_qty = $newRemarkQty;

            if ($totalProcessed >= $assignedQty) {
                if ($newRemarkQty > 0 || !empty($remarkHistory)) {
                    $item->status_item = 'Completed With Remark';
                } else {
                    $item->status_item = 'Completed';
                }
            } else {
                if ($newRemarkQty > 0 || !empty($remarkHistory)) {
                    $item->status_item = 'Progress With Remark';
                } elseif ($confirmedQty > 0) {
                    $item->status_item = 'Progress';
                } else {
                    $item->status_item = 'Created';
                }
            }

            $item->save();
            DB::commit();

            return response()->json([
                'status'              => 'success',
                'message'             => 'Remark Added Successfully.',
                'new_status'          => $item->status_item ?? 'Unknown',
                'confirmed_qty_total' => $confirmedQty,
                'remark_qty'          => $newRemarkQty,
                'tag'                 => $item->tag ?? '',
                'remark'              => $newRemarkText,
                'remark_history'      => $remarkHistory,
                'assigned_qty'        => $assignedQty,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function getRemarksByAufnr(Request $request)
    {
        $aufnr = $request->input('aufnr');

        if (empty($aufnr)) {
            return response()->json([
                'status' => 'error',
                'message' => 'AUFNR tidak boleh kosong.'
            ], 400);
        }

        // Cari di tabel item
        $items = \App\Models\HistoryWiItem::where('aufnr', $aufnr)
                    ->with('wi') // Eager load parent
                    ->get();

        $remarksData = [];

        foreach ($items as $item) {
             $extraData = $item->item_json ? json_decode($item->item_json, true) : [];
             $history = $extraData['remark_history'] ?? [];
             $legacyRemark = $item->remark_text ?? '';
             $legacyQty = (float) $item->remark_qty;
             
             // Extract Operator Info
             $operatorNik = $item->nik ?? '-';
             $operatorName = $item->name1 ?? '-'; // name1 field in table
             $operatorInfo = "{$operatorNik} - {$operatorName}";

             // Update history items with operator info if missing
             foreach ($history as &$hItem) {
                 if (empty($hItem['created_by']) || $hItem['created_by'] === 'System') {
                    $hItem['created_by'] = $operatorInfo;
                 }
             }
             unset($hItem); 

             // Fallback
             if (empty($history) && ($legacyQty > 0 || !empty($legacyRemark))) {
                 $history[] = [
                     'qty' => $legacyQty,
                     'remark' => $legacyRemark,
                     'created_at' => $item->updated_at->toDateTimeString(), // Use updated_at as proxy
                     'created_by' => $operatorInfo,
                     'is_legacy' => true
                 ];
             }

             if (!empty($history)) {
                 $remarksData[] = [
                     'wi_code' => $item->wi ? $item->wi->wi_document_code : '-',
                     'document_date' => $item->wi ? ($item->wi->document_date ? $item->wi->document_date->format('Y-m-d') : '-') : '-',
                     'vornr' => $item->vornr ?? '-', 
                     'material' => $item->material_number ?? '-', 
                     'material_desc' => $item->material_desc ?? '-',
                     'operator' => $operatorInfo,
                     'history' => $history
                 ];
             }
        }
        
        return response()->json([
            'status' => 'success',
            'aufnr' => $aufnr,
            'data' => $remarksData
        ]);
    }
}
