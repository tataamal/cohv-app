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
            'wi_code' => 'required|string',
        ]);

        $code = $request->input('wi_code');
        $document = HistoryWi::where('wi_document_code', $code)
                              ->where('expired_at', '>', Carbon::now())
                              ->where('document_date','<', Carbon::now())
                              ->first();
        if (!$document) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dokumen WI tidak ditemukan, WI Inactiveatau sudah expired.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'wi_document' => [
                'wi_code' => $document->wi_document_code,
                'plant_code' => $document->plant_code,
                'workcenter_code' => $document->workcenter_code,
                'document_date' => $document->document_date,
                'document_time' => $document->document_time,
                'expired_at' => $document->expired_at,
                'pro_items' => $document->payload_data,
            ]
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

            // $document->nik_last_updated = $nik; // Removed to avoid SQL error if column missing

            $payload = $document->payload_data;
            $updated = false;
            $documentCompleted = false;
            
            foreach ($payload as $key => $item) {
                // Modified: Check both AUFNR and NIK
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
                    $payload[$key]['last_confirmed_at'] = Carbon::now()->toDateTimeString();
                    $payload[$key]['last_confirmed_by_nik'] = $nik;

                    if ($newConfirmedQty === $assignedQty) {
                        $payload[$key]['status_pro_wi'] = 'Completed';
                        $payload[$key]['completed_at'] = Carbon::now()->toDateTimeString();
                        $payload[$key]['completed_by_nik'] = $nik;
                        $documentCompleted = true;

                    } elseif ($newConfirmedQty > 0) {
                        $payload[$key]['status_pro_wi'] = 'Progress';
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

            $finalStatusMessage = $documentCompleted ? 'berhasil diselesaikan (Completed).' : 'diperbarui secara parsial (Progress).';
            
            return response()->json([
                'status' => 'success',
                'message' => "Status PRO {$aufnrToComplete} di dokumen {$wiCode} {$finalStatusMessage}",
                'new_status' => $payload[$key]['status_pro_wi'] ?? 'Unknown',
                'confirmed_qty_total' => $newConfirmedQty,
                'assigned_qty' => $assignedQty,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API Error completing PRO status:', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Gagal mengubah status PRO: ' . $e->getMessage()], 500);
        }
    }
}
