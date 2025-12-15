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
        $unexpiredHistories = HistoryWi::where('expired_at', '>', Carbon::now())->where('document_date','<', Carbon::now())->get();
        $groupedAufnrs = [];
        $allUniqueAufnrs = [];

        foreach ($unexpiredHistories as $history) {
            $wiCode = $history->wi_document_code;
            $proItems = $history->payload_data;

            if (is_array($proItems)) {
                foreach ($proItems as $item) {
                    $aufnr = $item['aufnr'] ?? null;
                    $vornr = $item['vornr'] ?? null;
                    $status = $item['status_pro_wi'] ?? 'Created';
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

        $query = HistoryWi::where('expired_at', '>', Carbon::now())
                          ->where('document_date','<', Carbon::now());

        if ($code) {
            $query->where('wi_document_code', $code);
        }

        if ($nik) {
            $query->whereJsonContains('payload_data', [['nik' => $nik]]);
        }

        $documents = $query->get();

        if ($documents->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dokumen WI tidak ditemukan, WI Inactive, expired, atau NIK tidak sesuai.'
            ], 404);
        }


        $hasAssignableItems = false;
        $allCompleted = false;

        $mappedDocuments = $documents->map(function ($doc) use ($nik, &$hasAssignableItems, &$allCompleted) {
            $payload = $doc->payload_data;

            if ($nik) {
                if (is_array($payload)) {
                    $payload = array_values(array_filter($payload, function ($item) use ($nik) {
                        return isset($item['nik']) && $item['nik'] === $nik;
                    }));
                }
            }
            
            if (!empty($payload)) {
                $hasAssignableItems = true;

                $pendingItems = array_values(array_filter($payload, function ($item) {
                     $status = $item['status_pro_wi'] ?? '';
                     return !in_array($status, ['Completed', 'Completed With Remark']);
                }));

                if (empty($pendingItems) && !empty($payload)) {
                    $allCompleted = true; 
                }
                
                $payload = $pendingItems;
            }

            return [
                'wi_code' => $doc->wi_document_code,
                'plant_code' => $doc->plant_code,
                'workcenter_code' => $doc->workcenter_code,
                'document_date' => $doc->document_date,
                'document_time' => $doc->document_time,
                'expired_at' => $doc->expired_at,
                'pro_items' => $payload,
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
            'wi_code' => 'required|string|exists:db_history_wi,wi_document_code',
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

            $document = HistoryWi::where('wi_document_code', $wiCode)->lockForUpdate()->first();

            if (!$document) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Dokumen WI tidak ditemukan.'], 404);
            }

            $payload = $document->payload_data;
            $updated = false;
            $documentCompleted = false;
            
            foreach ($payload as $key => $item) {
                if (($item['aufnr'] ?? null) === $aufnrToComplete && ($item['nik'] ?? null) === $nik && ($item['vornr'] ?? null) === $vornr) {
                    
                    $assignedQty = (float) ($item['assigned_qty'] ?? 0);
                    $currentConfirmedQty = (float) ($item['confirmed_qty'] ?? 0);

                    if (($currentConfirmedQty + $confQty) > $assignedQty) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error', 
                            'message' => "Total Qty konfirmasi ({$currentConfirmedQty} + {$confQty}) melebihi Qty dialokasikan ({$assignedQty})."
                        ], 400);
                    }

                    $newConfirmedQty = $currentConfirmedQty + $confQty;
                    $newConfirmedQty = round($newConfirmedQty, 4); 
                    $assignedQty = round($assignedQty, 4);
                    
                    $payload[$key]['confirmed_qty'] = $newConfirmedQty;
                    $payload[$key]['confirmed_qty'] = $newConfirmedQty;
                    $payload[$key]['last_confirmed_at'] = Carbon::now()->toDateTimeString();
                    $payload[$key]['last_confirmed_by_nik'] = $nik;

                    $remarkQty = (float) ($item['remark_qty'] ?? 0);
                    $totalProcessed = $newConfirmedQty + $remarkQty;
                    $totalProcessed = round($totalProcessed, 4);

                    if ($totalProcessed >= $assignedQty) {
                         if ($remarkQty > 0 || !empty($item['remark'])) {
                            $payload[$key]['status_pro_wi'] = 'Completed With Remark';
                         } else {
                            $payload[$key]['status_pro_wi'] = 'Completed';
                         }
                        $payload[$key]['completed_at'] = Carbon::now()->toDateTimeString();
                        $payload[$key]['completed_by_nik'] = $nik;
                        $documentCompleted = true;

                    } elseif ($newConfirmedQty > 0 || $remarkQty > 0) {
                        if ($remarkQty > 0 || !empty($item['remark'])) {
                            $payload[$key]['status_pro_wi'] = 'Progress With Remark';
                        } else {
                            $payload[$key]['status_pro_wi'] = 'Progress';
                        }
                        $documentCompleted = false; 

                    } else {
                        $payload[$key]['status_pro_wi'] = 'Created';
                    }
                    
                    $updated = true;
                    break; 
                }
            }

            if (!$updated) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Kombinasi AUFNR, VORNR dan NIK tidak ditemukan di dalam dokumen ini.'], 404);
            }
            
            $document->payload_data = $payload;
            $document->save();

            DB::commit();

            $finalStatusMessage = $documentCompleted ? 'berhasil diselesaikan (Completed).' : 'diperbarui.';
            
            return response()->json([
                'status' => 'success',
                'message' => "Status PRO {$aufnrToComplete} di dokumen {$wiCode} {$finalStatusMessage}",
                'new_status' => $payload[$key]['status_pro_wi'] ?? 'Unknown',
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
            'wi_code' => 'required|string',
            'aufnr' => 'required|string',
            'nik' => 'required|string',
            'vornr' => 'required|string',
            'remark' => 'nullable|string',
            'remark_qty' => 'nullable|numeric',
        ]);

        $wiCode = $request->input('wi_code');
        $aufnr = $request->input('aufnr');
        $nik = $request->input('nik');
        $vornr = $request->input('vornr');
        $remark = $request->input('remark') ?? '';
        $remarkQty = $request->input('remark_qty') ?? 0;

        try {
            DB::beginTransaction();

            $document = HistoryWi::where('wi_document_code', $wiCode)->lockForUpdate()->first();

            if (!$document) {
                DB::rollBack();
                return response()->json(['message' => 'Document not found.'], 404);
            }

            $payload = $document->payload_data;
            if (!is_array($payload)) {
                 $payload = json_decode($payload, true) ?? [];
            }

            $updated = false;
            $key = null; // Track key for response
            
            foreach ($payload as $k => &$item) {
                // Match specific item by AUFNR, VORNR, and NIK
                if (
                    ($item['aufnr'] ?? '') === $aufnr &&
                    ($item['vornr'] ?? '') === $vornr &&
                    ($item['nik'] ?? '') === $nik
                ) {
                    $key = $k;
                    
                    $existingRemarkQty = (float) ($item['remark_qty'] ?? 0);
                    $existingRemark    = $item['remark'] ?? '';
                    $assignedQty       = (float) ($item['assigned_qty'] ?? 0);
                    $confirmedQty      = (float) ($item['confirmed_qty'] ?? 0);
                    
                    $inputRemarkQty = (float) $remarkQty;
                    $inputRemark    = $remark;

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
                    $remarkHistory = $item['remark_history'] ?? [];
                    if (!is_array($remarkHistory)) $remarkHistory = [];
                    
                    if ($inputRemarkQty > 0 || !empty($inputRemark)) {
                        $remarkHistory[] = [
                            'qty' => $inputRemarkQty,
                            'remark' => $inputRemark,
                            'created_at' => Carbon::now()->toDateTimeString(),
                            'created_by' => $request->input('nik') ?? 'System'
                        ];
                    }
                    $item['remark_history'] = $remarkHistory;
                    $displayRemarks = [];
                    foreach ($remarkHistory as $h) {
                        $q = floatval($h['qty'] ?? 0);
                        $m = $h['remark'] ?? '-';
                        if ($q > 0 || !empty($m)) {
                             $displayRemarks[] = "Qty {$q}: {$m}";
                        }
                    }
                    $newRemarkText = implode('; ', $displayRemarks);

                    $item['remark_qty'] = $newRemarkQty;
                    $item['remark'] = $newRemarkText; 
                    
                    if ($totalProcessed >= $assignedQty) {
                        if ($newRemarkQty > 0 || !empty($remarkHistory)) {
                            $item['status_pro_wi'] = 'Completed With Remark';
                        } else {
                            $item['status_pro_wi'] = 'Completed';
                        }
                    } else {
                        if ($newRemarkQty > 0 || !empty($remarkHistory)) {
                             $item['status_pro_wi'] = 'Progress With Remark';
                        } elseif ($confirmedQty > 0) {
                             $item['status_pro_wi'] = 'Progress';
                        } else {
                             $item['status_pro_wi'] = 'Created'; 
                        }
                    }

                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                $document->payload_data = $payload;
                $document->save();
                DB::commit();
                
                return response()->json([
                    'status' => 'success', 
                    'message' => 'Status updated successfully.',
                    'new_status' => $payload[$key]['status_pro_wi'] ?? 'Unknown',
                    'confirmed_qty_total' => $confirmedQty,
                    'remark_qty' => $newRemarkQty,
                    'remark' => $newRemarkText, // Now contains structured string summary
                    'remark_history' => $item['remark_history'] ?? [],
                    'assigned_qty' => $assignedQty,
                ]);
            } else {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Item not found in payload.'], 404);
            }

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

        // Find documents that contain this AUFNR in their payload
        $documents = HistoryWi::whereJsonContains('payload_data', [['aufnr' => $aufnr]])
                              ->orderBy('document_date', 'desc')
                              ->orderBy('document_time', 'desc')
                              ->get();

        $remarksData = [];

        foreach ($documents as $doc) {
            $payload = $doc->payload_data;
            if (is_array($payload)) {
                foreach ($payload as $item) {
                     if (($item['aufnr'] ?? '') === $aufnr) {
                         // Found the item
                         $history = $item['remark_history'] ?? [];
                         $legacyRemark = $item['remark'] ?? '';
                         $legacyQty = $item['remark_qty'] ?? 0;
                         
                         // Extract Operator Info
                         $operatorNik = $item['nik'] ?? '-';
                         $operatorName = $item['name'] ?? '-';
                         $operatorInfo = "{$operatorNik} - {$operatorName}";

                         // Update history items with operator info if missing or just override for display consistency
                         foreach ($history as &$hItem) {
                             $hItem['created_by'] = $operatorInfo;
                         }
                         unset($hItem); // break reference

                         // Fallback for legacy data
                         if (empty($history) && ($legacyQty > 0 || !empty($legacyRemark))) {
                             $history[] = [
                                 'qty' => $legacyQty,
                                 'remark' => $legacyRemark,
                                 'created_at' => $doc->created_at->toDateTimeString(),
                                 'created_by' => $operatorInfo,
                                 'is_legacy' => true
                             ];
                         }

                         if (!empty($history)) {
                             $remarksData[] = [
                                 'wi_code' => $doc->wi_document_code,
                                 'document_date' => \Carbon\Carbon::parse($doc->document_date)->format('Y-m-d'),
                                 'vornr' => $item['vornr'] ?? '-', 
                                 'material' => $item['material'] ?? '-', // Keep for legacy/fallback
                                 'material_desc' => $item['material_desc'] ?? ($item['material'] ?? '-'),
                                 'operator' => $operatorInfo,
                                 'history' => $history
                             ];
                         }
                     }
                }
            }
        }
        
        return response()->json([
            'status' => 'success',
            'aufnr' => $aufnr,
            'data' => $remarksData
        ]);
    }
}
