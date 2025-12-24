<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HistoryWi;
use Carbon\Carbon;
use App\Models\Kode;

class SendLogHistoryEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wi:send-log-email {plant_code : The plant code to filter data} {--date= : Specific date YYYY-MM-DD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Work Instruction Log History via Email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $argPlant = $this->argument('plant_code');
        // Date Logic:
        $dateHistory = $this->option('date') ?? Carbon::today()->subDay()->toDateString(); 
        $dateActive = Carbon::parse($dateHistory)->addDay()->toDateString();

        $this->info("Processing Reports (Global Scheduler)");
        $this->info("History Date (Yesterday): $dateHistory");
        $this->info("Active Date (Today): $dateActive");

        // --- 1. FETCH DATA (GLOBAL) ---
        // Fetch ALL WIH docs for the dates, regardless of plant_code arg
        $historyDocsAll = HistoryWi::with('kode')
                    ->where('wi_document_code', 'LIKE', 'WIH%')
                    ->whereDate('document_date', $dateHistory)
                    ->orderBy('created_at', 'desc')
                    ->get();
        
        $activeDocsAll = HistoryWi::with('kode')
                    ->where('wi_document_code', 'LIKE', 'WIH%')
                    ->whereDate('document_date', $dateActive)
                    ->orderBy('created_at', 'desc')
                    ->get();
        
        if ($historyDocsAll->isEmpty() && $activeDocsAll->isEmpty()) {
            $this->info("No documents found for both dates.");
            return;
        }

        // --- 2. GROUP BY PLANT ---
        $plants = $historyDocsAll->pluck('plant_code')
                    ->merge($activeDocsAll->pluck('plant_code'))
                    ->unique()
                    ->filter(); // Remove null/empty

        // Initialize Global Collections
        $allReports = [];
        $allActiveDocs = collect();
        
        $this->info("Found " . $plants->count() . " plants to process.");

        foreach ($plants as $plantCode) {
            $this->info(">>> Processing Plant: {$plantCode}");

            $historyDocs = $historyDocsAll->where('plant_code', $plantCode);
            $activeDocs = $activeDocsAll->where('plant_code', $plantCode);
            
            // OPTIMIZATION: Fetch Kode Info once per Plant
            $kodeData = Kode::with('sapUser')->where('kode', $plantCode)->first();
            $printedBy = ($kodeData && $kodeData->sapUser) ? $kodeData->sapUser->sap_id : 'AUTO_SCHEDULER';
            $namaBagian = $kodeData ? $kodeData->nama_bagian : '-';
            
            // --- 1. COLLECT HISTORY DATA ---
            $departments = $historyDocs->pluck('department')->unique();

            foreach ($departments as $department) {
                // Determine Dept Docs
                $deptDocs = $historyDocs->where('department', $department);
                
                // --- REFACTOR: Process PER DOCUMENT ---
                foreach ($deptDocs as $doc) {
                    $csvData = [];
                    $payload = is_string($doc->payload_data) ? json_decode($doc->payload_data, true) : (is_array($doc->payload_data) ? $doc->payload_data : []);
                    if (!is_array($payload)) $payload = [];

                    foreach ($payload as $item) {
                        $wc = !empty($item['child_workcenter']) ? $item['child_workcenter'] : ($item['workcenter_induk'] ?? '-');
                        $matnr = $item['material_number'] ?? '';
                        if(ctype_digit($matnr)) { $matnr = ltrim($matnr, '0'); }

                        $assigned = isset($item['assigned_qty']) ? floatval($item['assigned_qty']) : 0;
                        $confirmed = isset($item['confirmed_qty']) ? floatval($item['confirmed_qty']) : 0;
                        $netpr = isset($item['netpr']) ? floatval($item['netpr']) : 0;
                        $waerk = isset($item['waerk']) ? $item['waerk'] : '';
                        
                        $rawPrice = ($waerk == 'USD') ? '$ ' . number_format($netpr, 2) : 'Rp ' . number_format($netpr, 0, ',', '.');
                        if (strtoupper($waerk) === 'USD') {
                            $priceFormatted = '$ ' . number_format($netpr, 2);
                        } elseif (strtoupper($waerk) === 'IDR') {
                            $priceFormatted = 'Rp ' . number_format($netpr, 0, ',', '.');
                        } else {
                            $priceFormatted = $rawPrice; 
                        }

                        // Remark Data
                        $remarkQty = isset($item['remark_qty']) ? floatval($item['remark_qty']) : 0;
                        $remarkText = isset($item['remark']) ? $item['remark'] : '-';
                        // Clean up "Qty X: " prefix if present (User Request 2025-12-24)
                        $remarkText = preg_replace('/^Qty\s+\d+:\s*/i', '', $remarkText);
                        $remarkText = str_replace('; ', "\n", $remarkText);

                        $confirmedPrice = $netpr * $confirmed;
                        // Fix Balance Calculation
                        $balance = $assigned - ($confirmed + $remarkQty);
                        // Failed Price
                        $failedPrice = $netpr * ($balance + $remarkQty);

                        $hasRemark = ($remarkQty > 0 || ($remarkText !== '-' && !empty($remarkText)));
                        
                        // Define expiredAt and docDate for status logic
                        $docDate = Carbon::parse($doc->document_date)->startOfDay();
                        $expiredAt = Carbon::parse($doc->expired_at);
                        $today = Carbon::today();

                        if ($hasRemark) {
                            $status = 'NOT COMPLETED WITH REMARK';
                        } elseif ($balance <= 0) {
                            $status = 'COMPLETED'; 
                        } elseif (now()->gt($expiredAt)) {
                            $status = 'NOT COMPLETED';
                        } else {
                            $status = 'ACTIVE';
                        }

                        // SO Item Logic
                        $kdauf = $item['kdauf'] ?? '';
                        $kdpos = isset($item['kdpos']) ? ltrim($item['kdpos'], '0') : '';
                        $soItem = $kdauf . '-' . $kdpos;

                        // Takt Time Logic
                        $baseTime = isset($item['vgw01']) ? floatval($item['vgw01']) : 0;
                        $unit = isset($item['vge01']) ? strtoupper($item['vge01']) : '';
                        $totalTime = $baseTime * $assigned;

                        if ($unit == 'S' || $unit == 'SEC') {
                            $finalTime = $totalTime / 60; 
                            $finalUnit = 'Menit';
                        } else {
                            $finalTime = $totalTime;
                            $finalUnit = $unit; 
                        }
                        $taktDisplay = (fmod($finalTime, 1) !== 0.00) ? number_format($finalTime, 2) : number_format($finalTime, 0);
                        $taktFull = $taktDisplay . ' ' . $finalUnit;

                        // Format Prices
                        if (strtoupper($waerk) === 'USD') {
                            $prefixInfo = '$ ';
                        } elseif (strtoupper($waerk) === 'IDR') {
                            $prefixInfo = 'Rp ';
                        } else {
                            $prefixInfo = '';
                        }
                        
                        $priceFormatted = $prefixInfo . number_format($confirmedPrice, (strtoupper($waerk) === 'USD' ? 2 : 0), ',', '.');
                        $failedPriceFormatted = $prefixInfo . number_format($failedPrice, (strtoupper($waerk) === 'USD' ? 2 : 0), ',', '.');

                        $csvData[] = [
                            'doc_no'        => $doc->wi_document_code,
                            'created_at'    => $doc->created_at,
                            'expired_at'    => $expiredAt->format('m-d H:i'),
                            'workcenter'    => $wc,
                            'aufnr'         => $item['aufnr'] ?? '-',
                            'material'      => $matnr,
                            'description'   => $item['material_desc'] ?? '-',
                            'assigned'      => $assigned,
                            'confirmed'     => $confirmed,
                            'balance'       => $balance,
                            'remark_qty'    => $remarkQty,
                            'remark_text'   => $remarkText,
                            'price_formatted' => $priceFormatted, // Keep for backward compat if needed
                            'confirmed_price' => $confirmedPrice, 
                            'failed_price'    => $failedPrice,
                            'price_ok_fmt'    => $priceFormatted,       // NEW
                            'price_fail_fmt'  => $failedPriceFormatted, // NEW  
                            'currency'        => strtoupper($waerk),
                            'buyer'           => $item['name1'] ?? '-',
                            'nik'           => $item['nik'] ?? '-',
                            'name'          => $item['name'] ?? '-',
                            'status'        => $status,
                            'so_item'       => $soItem,
                            'takt_time'     => $taktFull,
                        ];
                    } // End Payload Loop

                    if (!empty($csvData)) {
                        $totalAssigned = collect($csvData)->sum('assigned');
                        $totalConfirmed = collect($csvData)->sum('confirmed'); 
                        $totalRemarkQty = collect($csvData)->sum('remark_qty');
                        $totalFailed = $totalAssigned - $totalConfirmed; 
    
                        $totalConfirmedPrice = collect($csvData)->sum('confirmed_price');
                        $totalFailedPrice = collect($csvData)->sum('failed_price');
    
                        $achievement = $totalAssigned > 0 ? round(($totalConfirmed / $totalAssigned) * 100) . '%' : '0%';
    
                        // Use the currency from the first item (assuming same currency per department/report) 
                        // or handle mixed currencies. For simplicity, we grab the first valid one.
                        $firstCurrency = collect($csvData)->first()['currency'] ?? '';
                        $prefix = (strtoupper($firstCurrency) === 'USD') ? '$ ' : 'Rp ';
                        $decimal = (strtoupper($firstCurrency) === 'USD') ? 2 : 0;
    
                        $totalConfirmedPriceFmt = $prefix . number_format($totalConfirmedPrice, $decimal, ',', '.');
                        $totalFailedPriceFmt = $prefix . number_format($totalFailedPrice, $decimal, ',', '.');
    
                        $reportData = [
                            'items' => $csvData,
                            'summary' => [
                                'total_assigned' => $totalAssigned,
                                'total_confirmed' => $totalConfirmed,
                                'total_failed' => $totalFailed,
                                'total_remark_qty' => $totalRemarkQty,
                                'achievement_rate' => $achievement,
                                'total_price_ok' => $totalConfirmedPriceFmt,
                                'total_price_fail' => $totalFailedPriceFmt
                            ],
                            'printedBy' => $printedBy ?? 'SYSTEM',
                            'department' => $department,
                            'nama_bagian' => $namaBagian,  
                            'printDate' => now()->format('d-M-Y H:i'),
                            'filterInfo' => "History Date: " . $dateHistory // Removed Doc Code
                        ];
                        // APPEND EACH DOC REPORT AS A SEPARATE ENTRY
                        $allReports[] = $reportData;
                    }
                } // End Doc Loop
            } // End Dept Loop

            // --- 2. COLLECT ACTIVE DOCUMENTS ---
            foreach($activeDocs as $doc) {
                 // Inject Buyer & Price for Email PDF (Active)
                 $payload = is_string($doc->payload_data) ? json_decode($doc->payload_data, true) : $doc->payload_data;
                 if (is_array($payload)) {
                    $updatedPayload = [];
                    foreach ($payload as $item) {
                        // 1. Buyer
                        $item['buyer_sourced'] = $item['name1'] ?? '-';
                        
                        // 2. Price
                        $netpr = isset($item['netpr']) ? floatval($item['netpr']) : 0;
                        $waerk = isset($item['waerk']) ? $item['waerk'] : '';
                        
                        // Simple Format Logic from History Loop
                        if (strtoupper($waerk) === 'USD') {
                            $priceFmt = '$ ' . number_format($netpr, 2);
                        } elseif (strtoupper($waerk) === 'IDR') {
                            $priceFmt = 'Rp ' . number_format($netpr, 0, ',', '.');
                        } else {
                            $priceFmt = $waerk . ' ' . number_format($netpr, 0, ',', '.'); 
                        }
                        
                        $item['price_sourced'] = $priceFmt;
                        $updatedPayload[] = $item;
                    }
                     $doc->payload_data = $updatedPayload;
                     
                     // Calculate Total Price for Header (Email only)
                     $totalDocPrice = 0;
                     $docCurrency = 'IDR'; // Default
                     
                     foreach ($updatedPayload as $itm) {
                         $assg = floatval(str_replace(',', '.', $itm['assigned_qty'] ?? 0));
                         $prc = floatval($itm['netpr'] ?? 0);
                         $totalDocPrice += ($assg * $prc);
                         
                         if (!empty($itm['waerk'])) {
                             $docCurrency = $itm['waerk'];
                         }
                     }
                     
                     if (strtoupper($docCurrency) === 'USD') {
                         $doc->total_price_formatted = '$ ' . number_format($totalDocPrice, 2);
                     } elseif (strtoupper($docCurrency) === 'IDR') {
                         $doc->total_price_formatted = 'Rp ' . number_format($totalDocPrice, 0, ',', '.');
                     } else {
                         $doc->total_price_formatted = $docCurrency . ' ' . number_format($totalDocPrice, 0, ',', '.');
                     }
                  }
 
                  $allActiveDocs->push($doc);
             }

        } 
        $filesToAttach = [];

        // 1. Generate History PDF
        if (!empty($allReports)) { 
             $historyPdfName = "Report_WI_{$dateHistory}.pdf";
             $historyPdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.log_history', ['reports' => $allReports])
                          ->setPaper('a4', 'landscape');
             $historyPath = storage_path("app/public/{$historyPdfName}");
             $historyPdf->save($historyPath);
             $filesToAttach[] = $historyPath;
             $this->info("   Generated Global History PDF (" . count($allReports) . " reports).");
        }

        // 2. Generate Active PDF
        /*if ($allActiveDocs->isNotEmpty()) {
             $activeData = [
                'documents' => $allActiveDocs,
                'printedBy' => 'Scheduler',
                'department' => 'ALL',
                'printTime' => now(),
                'isEmail'   => true, // Trigger column display in View
             ];
             $activePdfName = 'Dokument_WI_' . $dateActive . '.pdf';
             $activePdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.wi_single_document', $activeData)
                         ->setPaper('a4', 'landscape');
             $activePath = storage_path("app/public/{$activePdfName}");
             $activePdf->save($activePath);
             $filesToAttach[] = $activePath;
             $this->info("   Generated Global Active PDF (" . $allActiveDocs->count() . " docs).");
        }*/

        // 3. Send Email
        if (empty($filesToAttach)) {
            $this->info("   No reports/files to send.");
        } else {
            $recipients = ['tataamal1128@gmail.com','finc.smg@pawindo.com','kmi356smg@gmail.com','adm.mkt5.smg@gmail.com','lily.smg@pawindo.com','kmi3.60.smg@gmail.com','kmi3.31.smg@gmail.com'];
            // $recipients = ['tataamal1128@gmail.com'];
            $dateInfoFormatted = Carbon::parse($dateActive)->locale('id')->translatedFormat('d F Y');

            try {
                \Illuminate\Support\Facades\Mail::to($recipients)->send(new \App\Mail\LogHistoryMail($filesToAttach, $dateInfoFormatted));
                $this->info("   Global Email sent successfully.");
            } catch (\Exception $e) {
                $this->error("   Failed to send Global Email: " . $e->getMessage());
            }
        }
    }
}
