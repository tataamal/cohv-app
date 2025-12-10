<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\workcenter;
use App\Models\ProductionTData1; 
use App\Models\WorkcenterMapping;
use App\Models\HistoryWi; // Model History WI
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CreateWiController extends Controller
{
    /**
     * Delete WI Documents.
     */
    public function delete(Request $request) {
        $ids = $request->input('wi_codes');
        if (!$ids || !is_array($ids)) {
            return response()->json(['message' => 'Invalid data provided.'], 400);
        }

        try {
            DB::beginTransaction();
            // Delete based on wi_document_code
            HistoryWi::whereIn('wi_document_code', $ids)->delete();
            DB::commit();
            return response()->json(['message' => 'Documents deleted successfully.', 'count' => count($ids)]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete WI Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete documents: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menampilkan halaman utama Work Instruction (Drag & Drop).
     */
    public function index(Request $request, $kode)
    {
        $filter = $request->query('filter', 'all'); // Default filter: all (modified from today)
        $apiUrl = 'https://monitoring-kpi.kmifilebox.com/api/get-nik-confirmasi';
        $apiToken = env('API_TOKEN_NIK'); 
        $employees = []; 

        try {
            $response = Http::withToken($apiToken)->post($apiUrl, ['kode_laravel' => $kode]);
            if ($response->successful()) {
                $employees = $response->json()['data'];
            }
        } catch (\Exception $e) {
            Log::error('Koneksi API NIK Error: ' . $e->getMessage());
        }
        $search = $request->query('search'); // Add search param
        
        $tData1 = ProductionTData1::where('WERKSX', $kode)
            ->whereRaw('MGVRG2 > LMNGA')
            ->where('STATS', 'REL');

        if ($search) {
             $tData1->where(function($q) use ($search) {
                 $q->where('AUFNR', 'like', "%{$search}%")
                   ->orWhere('MATNR', 'like', "%{$search}%")
                   ->orWhere('MAKTX', 'like', "%{$search}%")
                   ->orWhere('KDAUF', 'like', "%{$search}%")
                   ->orWhere('KDPOS', 'like', "%{$search}%")
                   ->orWhere('ARBPL', 'like', "%{$search}%")
                   ->orWhere('STEUS', 'like', "%{$search}%")
                   ->orWhere('VORNR', 'like', "%{$search}%");
             });
        }

        if ($filter === 'today') {
            $tData1->whereDate('SSAVD', now());
        } elseif ($filter === 'week') {
            $tData1->whereBetween('SSAVD', [now()->startOfWeek(), now()->endOfWeek()]);
        }
        
        // Use pagination instead of get() to handle lazy loading
        // We need to fetch all matching IDs first to do the filtering logic (because filtering depends on Relation/Calculation not DB column)
        // OR, we can try to filter AFTER GET, but that breaks pagination count.
        // For accurate pagination with custom filtering (assigned Check), we load potentially more and slice manualy 
        // OR we just paginate the DB query and filter the result. This might result in pages with fewer items than Limit.
        // Let's stick to paginate the initial query, filter it, and if it's empty, user scrolls more. Simple for now.
        
        // Actually, the request is for lazy loading. 
        $perPage = 50;
        $page = $request->input('page', 1);
        
        // Get data
        $tDataQuery = $tData1; // Keep builder
        // We cannot use standard paginate() easily if we are going to filter items out in PHP after fetching.
        // So we will fetch a chunk, filter it, and return.
        
        // Improved approach: Fetch with skip/take but we need to account for filtered items? 
        // No, standard pagination on DB level is safer for performance. Filtered items (fully assigned) will just be hidden.
        
        // Let's paginate the query
        $pagination = $tDataQuery->paginate($perPage); 
    
        $assignedProQuantities = $this->getAssignedProQuantities($kode);

        // Transform collection (Add Qty Info & Filter)
        $processedCollection = $pagination->getCollection()->transform(function ($item) use ($assignedProQuantities) {
            $aufnr = $item->AUFNR;
            $qtySisaAwal = $item->MGVRG2 - $item->LMNGA;
            $qtyAllocatedInWi = $assignedProQuantities[$aufnr] ?? 0;
            $qtySisaAkhir = $qtySisaAwal - $qtyAllocatedInWi;
            $item->real_sisa_qty = $qtySisaAkhir; 
            $item->qty_wi = $qtyAllocatedInWi; 
            return $item;
        })->filter(function ($item) {
             return $item->real_sisa_qty > 0.001; // Filter out completed items
        });

        if ($request->ajax()) {
            $html = view('create-wi.partials.source_table_rows', ['tData1' => $processedCollection])->render();
            return response()->json([
                'html' => $html,
                'next_page' => $pagination->hasMorePages() ? $pagination->currentPage() + 1 : null
            ]);
        }
        
        $workcenters = workcenter::where('werksx', $kode)->get();
        $workcenterMappings = WorkcenterMapping::where('kode_laravel', $kode)->get();
        $parentWorkcenters = $this->buildWorkcenterHierarchy($workcenters, $workcenterMappings);

        return view('create-wi.index', [
            'kode'                 => $kode,
            'employees'            => $employees,
            'tData1'               => $processedCollection, // Send first page processed
            'workcenters'          => $workcenters,
            'parentWorkcenters'    => $parentWorkcenters,
            'currentFilter'        => $filter,
            'nextPage'             => $pagination->hasMorePages() ? 2 : null
        ]);
    }

    protected function getAssignedProQuantities(string $kodePlant)
    {
        $histories = HistoryWi::where('plant_code', $kodePlant)->get();
        $assignedProQuantities = [];

        foreach ($histories as $history) {
            $proItems = $history->payload_data; 

            if (is_array($proItems)) {
                foreach ($proItems as $item) {
                    $aufnr = $item['aufnr'] ?? null;
                    $assignedQty = $item['assigned_qty'] ?? 0;

                    if ($aufnr) {
                        $currentTotal = $assignedProQuantities[$aufnr] ?? 0;
                        $assignedProQuantities[$aufnr] = $currentTotal + $assignedQty;
                    }
                }
            }
        }

        return $assignedProQuantities;
    }

    /**
     * Memproses mapping Workcenter Induk dan Anak.
     */
    protected function buildWorkcenterHierarchy($primaryWCs, $mappings)
    {
        $primaryWcCodes = $primaryWCs->pluck('kode_wc')->map(fn($code) => strtoupper($code))->all();
        $parentHierarchy = [];

        foreach ($mappings as $mapping) {
            $parentCode = strtoupper($mapping->wc_induk);
            $childCode = $mapping->workcenter;
            $childName = $mapping->nama_workcenter;

            if (!in_array($parentCode, $primaryWcCodes)) {
                continue;
            }
            
            if ($parentCode === strtoupper($childCode)) {
                continue;
            }
            
            if (!isset($parentHierarchy[$parentCode])) {
                $parentHierarchy[$parentCode] = [];
            }

            $isDuplicate = collect($parentHierarchy[$parentCode])->contains('code', $childCode);

            if (!$isDuplicate) {
                $parentHierarchy[$parentCode][] = [
                    'code' => $childCode,
                    'name' => $childName,
                ];
            }
        }

        $parentHierarchy = array_filter($parentHierarchy, function($children) {
            return count($children) > 0;
        });

        return $parentHierarchy;
    }

    /**
     * Menyimpan alokasi Work Instruction (WI) dan membuat kode dokumen unik.
     */
    public function saveWorkInstruction(Request $request)
    {
        $requestData = $request->json()->all();
        $plantCode = $requestData['plant_code'] ?? null;
        $inputDate = $requestData['document_date'] ?? now()->toDateString();
        $inputTime = $requestData['document_time'] ?? '00:00';
        $payload = $requestData['workcenter_allocations'] ?? [];

        if (empty($payload) || !$plantCode || !$inputDate) {
            return response()->json(['message' => 'Data tidak lengkap. Tanggal/Plant/alokasi kosong.'], 400);
        }
        $docPrefix = str_starts_with($plantCode, '3') ? 'WIH' : 'WIW';

        $dateTime = Carbon::parse($inputDate . ' ' . $inputTime);
        $expiredAt = $dateTime->copy()->addHours(12);
        $dateForDb = $dateTime->toDateString();
        $timeForDb = $dateTime->toTimeString();
        
        $wiDocuments = [];

        try {
            foreach ($payload as $wcAllocation) {
                $workcenterCode = $wcAllocation['workcenter'];
                DB::transaction(function () use ($docPrefix, $workcenterCode, $plantCode, $dateForDb, $timeForDb, $expiredAt, $wcAllocation, &$wiDocuments) {
                    $latestHistory = HistoryWi::where('wi_document_code', 'LIKE', $docPrefix . '%')
                        ->orderByRaw('LENGTH(wi_document_code) DESC')
                        ->orderBy('wi_document_code', 'desc')
                        ->lockForUpdate() 
                        ->first();
    
                    $nextNumber = 1;
                    
                    if ($latestHistory) {
                        $currentCode = $latestHistory->wi_document_code;
                        $numberPart = substr($currentCode, 3); 
                        $nextNumber = intval($numberPart) + 1;
                    }
                    $documentCode = $docPrefix . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);

                    HistoryWi::create([
                        'wi_document_code' => $documentCode,
                        'workcenter_code' => $workcenterCode,
                        'plant_code' => $plantCode,
                        'document_date' => $dateForDb,
                        'document_time' => $timeForDb,       
                        'expired_at' => $expiredAt->toDateTimeString(),
                        'sequence_number' => $nextNumber, // Simpan urutan angka saja
                        'payload_data' => $wcAllocation['pro_items'], // Asumsi kolom ini sudah dicasting array/json di Model
                    ]);
                    
                    $wiDocuments[] = [
                        'workcenter' => $workcenterCode,
                        'document_code' => $documentCode,
                    ];
                });
            }

            return response()->json([
                'message' => 'Work Instructions berhasil disimpan.',
                'documents' => $wiDocuments,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error saat menyimpan WI:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'payload' => $requestData
            ]);
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan Work Instructions.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function history(Request $request, $kode) 
    {
        $plantCode = $kode; 
        $now = Carbon::now();
        $query = HistoryWi::where('plant_code', $plantCode);

        if ($request->filled('date')) {
            $query->whereDate('document_date', $request->date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('wi_document_code', 'like', "%{$search}%")
                ->orWhere('workcenter_code', 'like', "%{$search}%");
            });
        }
        $wiDocuments = $query->orderBy('document_date', 'desc')
                            ->orderBy('document_time', 'desc')
                            ->get();

        // Initialize Collections
        $activeWIDocuments = collect();   // Today
        $inactiveWIDocuments = collect(); // Future
        $expiredWIDocuments = collect();  // Expired
        $completedWIDocuments = collect(); // Completed
        
        // Loop and Categorize
        foreach ($wiDocuments as $doc) {
            $now = Carbon::now();
            $expiredAt = $doc->expired_at; 
            
            if ($expiredAt) {
                $expirationTime = Carbon::parse($expiredAt); 
                $doc->is_expired = $now->greaterThan($expirationTime);
            } else {
                try {
                    $effectiveStart = Carbon::parse($doc->document_date . ' ' . $doc->document_time);
                    $expirationTime = $effectiveStart->copy()->addHours(12);
                    $doc->is_expired = $now->greaterThan($expirationTime);
                } catch (\Exception $e) {
                    $doc->is_expired = true;
                }
            }

            $docDate = Carbon::parse($doc->document_date)->startOfDay();
            $today = Carbon::today();

            $doc->is_inactive = $docDate->greaterThan($today) && !$doc->is_expired; // Future & Not Expired
            $doc->is_active = $docDate->equalTo($today) && !$doc->is_expired;      // Today & Not Expired

            $rawData = $doc->payload_data;
            if (is_array($rawData)) {
                $payloadItems = $rawData;
            } else {
                $payloadItems = json_decode($rawData, true);
            }
            $payloadItems = $payloadItems ?? [];
            $firstItem = $payloadItems[0] ?? [];
            $rawKapaz = str_replace(',', '.', $firstItem['kapaz'] ?? 0);
            $kapazHours = floatval($rawKapaz);
            $maxMins = $kapazHours * 60; 
            $summary = [
                'total_items' => 0,
                'total_load_mins' => 0, 
                'details' => [] 
            ];

            $isFullyCompleted = true;
            if (empty($payloadItems)) $isFullyCompleted = false;

            foreach ($payloadItems as $item) {
                $summary['total_items']++;
                $assignedQty = floatval(str_replace(',', '.', $item['assigned_qty'] ?? 0));
                $confirmedQty = floatval(str_replace(',', '.', $item['confirmed_qty'] ?? 0));
                $qtyOrderRaw = floatval(str_replace(',', '.', $item['qty_order'] ?? $assignedQty));
                $takTime = floatval(str_replace(',', '.', $item['calculated_tak_time'] ?? 0));
                $summary['total_load_mins'] += $takTime;
                $progressPct = $assignedQty > 0 ? ($confirmedQty / $assignedQty) * 100 : 0;

                if ($progressPct >= 100) $statusItem = 'Completed';
                elseif ($confirmedQty > 0) $statusItem = 'On Progress';
                else $statusItem = 'Created';

                $summary['details'][] = [
                    'aufnr'         => $item['aufnr'] ?? '-',
                    'material'      => $item['material_desc'] ?? ($item['material'] ?? '-'), // Sesuaikan key JSON
                    'description'   => $item['material_desc'] ?? '', 
                    'assigned_qty'  => $assignedQty,
                    'confirmed_qty' => $confirmedQty,
                    'qty_order'     => $qtyOrderRaw,
                    'uom'           => $item['uom'] ?? 'EA',
                    'progress_pct'  => $progressPct,
                    'status'        => $statusItem,
                    'item_mins'     => $takTime 
                ];

                // Check for completion status for the document
                if ($confirmedQty < $assignedQty) {
                    $isFullyCompleted = false;
                }
            }

            // Get max capacity for the workcenter
            $firstItem = $payloadItems[0] ?? [];
            $rawKapaz = str_replace(',', '.', $firstItem['kapaz'] ?? 0);
            $maxMins = floatval($rawKapaz) * 60; // Convert hours to minutes

            $percentageLoad = $maxMins > 0 ? ($summary['total_load_mins'] / $maxMins) * 100 : 0;

            $doc->capacity_info = [
                'max_mins'   => $maxMins,
                'used_mins'  => $summary['total_load_mins'],
                'percentage' => $percentageLoad
            ];

            $doc->pro_summary = $summary;

            // Categorization Logic (Priority: Completed -> Expired -> Inactive -> Active)
            if ($isFullyCompleted) {
                $completedWIDocuments->push($doc);
            } elseif ($doc->is_expired) {
                $expiredWIDocuments->push($doc);
            } elseif ($doc->is_inactive) {
                $inactiveWIDocuments->push($doc);
            } else {
                // Default / Active (includes current day and past unexpired)
                $activeWIDocuments->push($doc);
            }
        }

        return view('create-wi.history', [
            'plantCode' => $plantCode,
            'activeWIDocuments' => $activeWIDocuments,
            'inactiveWIDocuments' => $inactiveWIDocuments,
            'expiredWIDocuments' => $expiredWIDocuments,
            'completedWIDocuments' => $completedWIDocuments, // PASS TO VIEW
            'search' => $request->search,
            'date' => $request->date
        ]);
    }

    public function updateQty(Request $request)
    {
        $request->validate([
            'wi_code' => 'required|string',
            'aufnr'   => 'required|string',
            'new_qty' => 'required|numeric|min:0',
        ]);

        try {
            // 1. Cari Dokumen
            $doc = HistoryWi::where('wi_document_code', $request->wi_code)->firstOrFail();

            // 2. Decode Payload
            $payload = is_array($doc->payload_data) 
                        ? $doc->payload_data 
                        : json_decode($doc->payload_data, true);

            $updated = false;
            $materialName = '';

            // --- FUNGSI HELPER PARSING ANGKA (Sama dengan logika History) ---
            $parseNumber = function($value) {
                if (is_numeric($value)) return floatval($value);
                $string = (string) $value;
                
                // Deteksi format Indonesia (Ribuan titik, Desimal koma) vs Inggris
                if (strpos($string, '.') !== false && strpos($string, ',') !== false) {
                    if (strrpos($string, ',') > strrpos($string, '.')) {
                        $string = str_replace('.', '', $string); 
                        $string = str_replace(',', '.', $string);
                    } else {
                        $string = str_replace(',', '', $string); 
                    }
                } elseif (strpos($string, ',') !== false) {
                    $string = str_replace(',', '.', $string);
                }
                return floatval($string);
            };
            // ---------------------------------------------------

            // 3. Loop Item & Update
            foreach ($payload as &$item) {
                if ($item['aufnr'] === $request->aufnr) {
                $maxQty = $parseNumber($item['qty_order'] ?? 0);
                $newQty = floatval($request->new_qty);

                if ($maxQty > 0 && $newQty > $maxQty) {
                    return back()->with('error', "Gagal! Quantity ($newQty) melebihi Order ($maxQty).");
                }
                $vgw01 = $parseNumber($item['vgw01'] ?? 0);
                $unit = strtoupper($item['vge01'] ?? '');
                if ($vgw01 == 0) {
                    $oldTakTime = floatval($item['calculated_tak_time'] ?? 0);
                    $oldQty = floatval($item['assigned_qty'] ?? 0);
                    
                    if ($oldQty > 0) {
                        $vgw01 = $oldTakTime / $oldQty; 
                        $unit = 'MIN'; 
                    }
                }

                $totalRaw = $vgw01 * $newQty;
                $newMinutes = 0;

                if ($unit === 'S' || $unit === 'SEC') {
                    $newMinutes = $totalRaw / 60;
                } elseif ($unit === 'H' || $unit === 'HUR') {
                    $newMinutes = $totalRaw * 60;
                } else {
                    $newMinutes = $totalRaw; // Default MIN
                }

                $item['assigned_qty'] = $newQty;
                $item['calculated_tak_time'] = number_format($newMinutes, 2, '.', '');
                $item['vgw01'] = $vgw01; 
                $item['vge01'] = $unit;
                $updated = true;
                $materialName = $item['material_desc'] ?? $item['aufnr'];
                break; 
            }
            }

            if ($updated) {
                $doc->payload_data = $payload; 
                $doc->save();

                return back()->with('success', "Qty $materialName diupdate menjadi $newQty. Kapasitas diperbarui.");
            }

            return back()->with('error', 'Item tidak ditemukan.');

        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    public function printPdf(Request $request, $plantCode)
    {
        $date = $request->input('filter_date');
        $search = $request->input('filter_search');
        $query = HistoryWi::where('plant_code', $plantCode);

        if ($date) {
            $query->whereDate('created_at', $date);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('wi_document_code', 'like', "%$search%") // Sesuaikan nama kolom
                ->orWhere('payload_data', 'like', "%$search%"); // Search di dalam JSON
            });
        }
        $rawDocuments = $query->orderBy('created_at', 'desc')->get();
        $reportData = [];

        foreach ($rawDocuments as $doc) {
            if (empty($doc->payload_data) || !is_array($doc->payload_data)) {
                continue;
            }

            foreach ($doc->payload_data as $item) {
                $wc = !empty($item['workcenter_induk']) ? $item['workcenter_induk'] : ($item['child_workcenter'] ?? '-');
                $kdauf = $item['kdauf'] ?? '';
                $kdpos = isset($item['kdpos']) ? ltrim($item['kdpos'], '0') : '';
                $soItem = $kdauf . '-' . $kdpos;
                $matnr = $item['material_number'] ?? '';
                if(ctype_digit($matnr)) { 
                    $matnr = ltrim($matnr, '0'); 
                }
                $qtyOper = isset($item['qty_order']) ? floatval($item['qty_order']) : 0;
                $qtyWi = isset($item['assigned_qty']) ? floatval($item['assigned_qty']) : 0;
                $baseTime = isset($item['vgw01']) ? floatval($item['vgw01']) : 0;
                $unit = isset($item['vge01']) ? strtoupper($item['vge01']) : '';
                $totalTime = $baseTime * $qtyWi;
                if ($unit == 'S' || $unit == 'SEC') {
                    $finalTime = $totalTime / 60; 
                    $finalUnit = 'Menit';
                } else {
                    $finalTime = $totalTime;
                    $finalUnit = $unit;
                }
                $taktDisplay = (fmod($finalTime, 1) !== 0.00) ? number_format($finalTime, 2) : number_format($finalTime, 0);
                $taktFull = $taktDisplay . ' ' . $finalUnit;
                $reportData[] = [
                    'doc_no'        => $doc->wi_document_code,
                    'nik'           => $item['nik'] ?? '-',
                    'name'          => $item['name'] ?? '-',
                    'created_at'    => $doc->created_at, // atau document_date
                    'status'        => ($doc->expired_at > now()) ? 'Active' : 'Expired',
                    'workcenter'    => $wc,
                    'so_item'       => $soItem,
                    'aufnr'         => $item['aufnr'] ?? '-',
                    'material'      => $matnr,
                    'description'   => $item['material_desc'] ?? '-',
                    'qty_op'        => $qtyOper,
                    'qty_wi'        => $qtyWi,
                    'takt_time'     => $taktFull
                ];
            }
        }
        $data = [
            'reportRows' => $reportData, 
            'plantCode' => $plantCode,
            'printedBy' => $request->input('printed_by'),
            'department' => $request->input('department'),
            'printDate' => now()->format('d-M-Y H:i'),
            'filterDate' => $date ? Carbon::parse($date)->format('d-M-Y') : 'All Dates'
        ];

        // 5. Generate PDF
        $pdf = Pdf::loadView('pdf.wi_history_report', $data)
                ->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan_Log_WI.pdf');
    }
    public function printSingleWi(Request $request)
    {
        $request->validate([
            'wi_codes' => 'required',
            'printed_by' => 'required',
            'department' => 'required',
        ]);
        $rawInput = $request->input('wi_codes');
        $wiCodes = explode(',', $rawInput);
        $documents = HistoryWi::whereIn('wi_document_code', $wiCodes)
                    ->where('expired_at', '>', now()) 
                    ->get();

        if ($documents->isEmpty()) {
            return back()->with('error', 'Dokumen tidak ditemukan atau sudah expired.');
        }
        $data = [
            'documents' => $documents, // Kirim Collection dokumen, bukan single doc
            'printedBy' => $request->input('printed_by'),
            'department' => $request->input('department'),
            'printTime' => now(),
        ];
        $pdf = Pdf::loadView('pdf.wi_single_document', $data)
                ->setPaper('a4', 'landscape');

        return $pdf->stream('Work_Instruction_Print.pdf');
    }

    public function printExpiredReport(Request $request)
    {
        $rawInput = $request->input('wi_codes'); // Ganti nama input agar konsisten dg JS baru
        $wiCodes = explode(',', $rawInput);
        $documents = HistoryWi::whereIn('wi_document_code', $wiCodes)->get();
        $reportItems = [];
        $grandTotalAssigned = 0;
        $grandTotalConfirmed = 0;

        foreach ($documents as $doc) {
            if (empty($doc->payload_data) || !is_array($doc->payload_data)) continue;

            foreach ($doc->payload_data as $item) {
                // Data Dasar
                $matnr = isset($item['material_number']) && ctype_digit($item['material_number']) 
                        ? ltrim($item['material_number'], '0') 
                        : ($item['material_number'] ?? '');
                        
                $wc = !empty($item['workcenter_induk']) ? $item['workcenter_induk'] : ($item['child_workcenter'] ?? '-');
                $assigned = isset($item['assigned_qty']) ? floatval($item['assigned_qty']) : 0;
                $confirmed = isset($item['confirmed_qty']) ? floatval($item['confirmed_qty']) : 0;
                $balance = $assigned - $confirmed;
                $grandTotalAssigned += $assigned;
                $grandTotalConfirmed += $confirmed;
                $reportItems[] = [
                    'wi_code'     => $doc->wi_document_code,
                    'nik'         => $item['nik'] ?? '-',
                    'name'        => $item['name'] ?? '-',
                    'expired_at'  => $doc->expired_at,
                    'workcenter'  => $wc,
                    'aufnr'       => $item['aufnr'] ?? '-',
                    'material'    => $matnr,
                    'description' => $item['material_desc'] ?? '-',
                    'assigned'    => $assigned,
                    'confirmed'   => $confirmed,
                    'balance'     => $balance,
                    'remark'      => ($balance > 0) ? 'Not Completed' : 'Completed' // Status per baris
                ];
            }
        }

        $summary = [
            'total_assigned' => $grandTotalAssigned,
            'total_confirmed' => $grandTotalConfirmed,
            'total_balance' => $grandTotalAssigned - $grandTotalConfirmed,
            'achievement_rate' => ($grandTotalAssigned > 0) ? round(($grandTotalConfirmed / $grandTotalAssigned) * 100, 1) : 0
        ];

        $data = [
            'items' => $reportItems,
            'summary' => $summary,
            'printedBy' => $request->input('printed_by'),
            'department' => $request->input('department'),
            'printDate' => now()->format('d-M-Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.wi_expired_report', $data)
                ->setPaper('a4', 'landscape');
        return $pdf->stream('Laporan_Produksi_Expired.pdf');
    }
}