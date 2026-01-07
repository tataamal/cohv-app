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
    protected $signature = 'wi:send-log-email {date? : Specific date dd-mm-yyyy}';

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
        // Date Logic:
        $dateArg = $this->argument('date');
        
        if ($dateArg) {
            try {
                $dateHistory = Carbon::createFromFormat('d-m-Y', $dateArg)->format('Y-m-d');
            } catch (\Exception $e) {
                $this->error("Invalid date format. Please use dd-mm-yyyy. Example: 29-12-2025");
                return 1;
            }
        } else {
            $dateHistory = Carbon::today()->subDay()->toDateString(); 
        }

        $dateActive = Carbon::parse($dateHistory)->addDay()->toDateString();

        $this->info("Processing Reports (Global Scheduler)");
        $this->info("History Date (Yesterday): $dateHistory");
        $this->info("Active Date (Today): $dateActive");

        // --- 1. FETCH DATA (GLOBAL) ---
        $queryHistory = HistoryWi::with('kode')
                    ->where('wi_document_code', 'LIKE', 'WIH%')
                    ->whereDate('document_date', $dateHistory)
                    ->orderBy('created_at', 'desc');

        $queryActive = HistoryWi::with('kode')
                    ->where('wi_document_code', 'LIKE', 'WIH%')
                    ->whereDate('document_date', $dateActive)
                    ->orderBy('created_at', 'desc');

        $historyDocsAll = $queryHistory->get();
        $activeDocsAll = $queryActive->get();
        
        if ($historyDocsAll->isEmpty() && $activeDocsAll->isEmpty()) {
            $this->info("No documents found for both dates.");
            return;
        }

        $groupedData = []; 
        
        foreach ($historyDocsAll as $doc) {
            $pCode = $doc->plant_code; 
            $kData = $doc->kode; 
            if (!$kData) {
                $kData = Kode::find($pCode);
            }
            $rawName = $kData ? $kData->nama_bagian : 'UNKNOWN';
            
            $upperName = strtoupper($rawName);
            $slug = preg_replace('/[^A-Z0-9]/', '', $upperName); 
            
            if (empty($slug)) $slug = 'UNKNOWN';
            
            if (!isset($groupedData[$slug])) {
                $groupedData[$slug] = [
                    'display_name' => $rawName, 
                    'items' => [],
                    'plant_codes' => [],
                    'printed_by' => ($kData && $kData->sapUser) ? $kData->sapUser->sap_id : 'AUTO_SCHEDULER',
                ];
            }
            $groupedData[$slug]['plant_codes'][] = $pCode;
            
            $payload = is_string($doc->payload_data) ? json_decode($doc->payload_data, true) : (is_array($doc->payload_data) ? $doc->payload_data : []);
            if (!is_array($payload)) continue;

            foreach ($payload as $item) {
                 $item['_doc_no'] = $doc->wi_document_code;
                 $groupedData[$slug]['items'][] = $item;
            }
        }
        foreach ($groupedData as $slug => $group) {
            $namaBagian = $group['display_name']; 
            $uniqueCodes = array_unique($group['plant_codes']);
            $rawItems = $group['items'];
            if (empty($rawItems)) continue;

            $this->info(">>> Processing Bagian: '{$namaBagian}' [Slug: {$slug}] (Items: " . count($rawItems) . ")");
            $this->info("    Plant Codes: " . implode(', ', $uniqueCodes));

            $wcMap = \App\Models\workcenter::whereIn('werksx', $uniqueCodes)
                ->orWhereIn('werks', $uniqueCodes)
                ->pluck('description', 'kode_wc')
                ->mapWithKeys(fn($d, $k) => [strtoupper($k) => $d])
                ->toArray();

            $processedItems = [];
            
            foreach ($rawItems as $item) {
                 $wcCode = !empty($item['child_workcenter']) ? $item['child_workcenter'] : ($item['workcenter_induk'] ?? '-');
                 $wcDesc = $wcMap[strtoupper($wcCode)] ?? '-';
                 
                 $matnr = $item['material_number'] ?? '';
                 if(ctype_digit($matnr)) { $matnr = ltrim($matnr, '0'); }

                 $assigned = isset($item['assigned_qty']) ? floatval($item['assigned_qty']) : 0;
                 $confirmed = isset($item['confirmed_qty']) ? floatval($item['confirmed_qty']) : 0;
                 $netpr = isset($item['netpr']) ? floatval($item['netpr']) : 0;
                 $waerk = isset($item['waerk']) ? $item['waerk'] : '';
                 
                 $prefix = strtoupper($waerk) === 'USD' ? '$ ' : (strtoupper($waerk) === 'IDR' ? 'Rp ' : $waerk . ' ');
                 $decimals = strtoupper($waerk) === 'USD' ? 2 : 0;
                 $confirmedPrice = $netpr * $confirmed;
                 
                 $remarkQty = isset($item['remark_qty']) ? floatval($item['remark_qty']) : 0;
                 $remarkText = isset($item['remark']) ? $item['remark'] : '-';
                 $remarkText = isset($item['remark']) ? $item['remark'] : '-';
                 // Strip "Qty X:" or "M:X" prefix to avoid duplication with the view's "Qty:" label
                 $remarkText = preg_replace('/^(Qty|M)[:\s]*\d+[:]?\s*/i', '', $remarkText);
                 
                 $balance = $assigned - ($confirmed + $remarkQty);
                 $failedPrice = $netpr * ($balance + $remarkQty);

                 $baseTime = isset($item['vgw01']) ? floatval($item['vgw01']) : 0;
                 $unit = isset($item['vge01']) ? strtoupper($item['vge01']) : '';
                 $totalTime = $baseTime * $assigned;
                 $finalTime = ($unit == 'S' || $unit == 'SEC') ? $totalTime/60 : $totalTime;
                 $timeUnit = ($unit == 'S' || $unit == 'SEC') ? 'Menit' : $unit;
                 $taktFull = ((fmod($finalTime, 1) !== 0.00) ? number_format($finalTime, 2) : number_format($finalTime, 0)) . ' ' . $timeUnit;

                 $priceOkFmt = $prefix . number_format($confirmedPrice, $decimals, ',', '.');
                 $priceFailFmt = $prefix . number_format($failedPrice, $decimals, ',', '.');

                 $kdauf = $item['kdauf'] ?? '';
                 $matKdauf = $item['mat_kdauf'] ?? '';
                 $isMakeStock = (strcasecmp($kdauf, 'Make Stock') === 0) || (strcasecmp($matKdauf, 'Make Stock') === 0);
                 $kdpos = isset($item['kdpos']) ? ltrim($item['kdpos'], '0') : '';
                 
                 $soItem = $isMakeStock ? $kdauf : ($kdauf . ($kdpos ? '-' . $kdpos : ''));

                    $nik = $item['nik'] ?? '-';
                 
                 $processedItems[] = [
                    'doc_no'        => $item['_doc_no'],
                    'workcenter'    => $wcCode,
                    'wc_description'=> $wcDesc,
                    'so_item'       => $soItem,
                    'aufnr'         => $item['aufnr'] ?? '-',
                    'vornr'         => $item['vornr'] ?? '', // [NEW] Added vornr
                    'material'      => $matnr,
                    'description'   => $item['material_desc'] ?? '-',
                    'assigned'      => $assigned,
                    'confirmed'     => $confirmed,
                    'remark_qty'    => $remarkQty,
                    'remark_text'   => $remarkText,
                    'takt_time'     => $taktFull,
                    'nik'           => $nik,
                    'name'          => $item['name'] ?? '-',
                    'price_ok_fmt'    => $priceOkFmt,
                    'price_fail_fmt'  => $priceFailFmt,
                    'confirmed_price' => $confirmedPrice, 
                    'failed_price'    => $failedPrice,
                    'currency'        => strtoupper($waerk),
                    'raw_total_time'  => $finalTime
                 ];
            }

            $sortedItems = collect($processedItems)->sortBy([
                ['nik', 'asc'],
                ['workcenter', 'asc']
            ])->values()->all();

            $totalAssigned = collect($sortedItems)->sum('assigned');
            $totalConfirmed = collect($sortedItems)->sum('confirmed'); 
            $totalFailed = $totalAssigned - $totalConfirmed; 
            $totalConfirmedPrice = collect($sortedItems)->sum('confirmed_price');
            $totalFailedPrice = collect($sortedItems)->sum('failed_price');
            $achievement = $totalAssigned > 0 ? round(($totalConfirmed / $totalAssigned) * 100) . '%' : '0%';

            $firstCurr = collect($sortedItems)->first()['currency'] ?? '';
            $pfx = (strtoupper($firstCurr) === 'USD') ? '$ ' : 'Rp ';
            $dec = (strtoupper($firstCurr) === 'USD') ? 2 : 0;

            $reportData = [
                'items' => $sortedItems,
                'summary' => [
                    'total_assigned' => $totalAssigned,
                    'total_confirmed' => $totalConfirmed,
                    'total_failed' => $totalFailed,
                    'achievement_rate' => $achievement,
                    'total_price_ok' => $pfx . number_format($totalConfirmedPrice, $dec, ',', '.'),
                    'total_price_fail' => $pfx . number_format($totalFailedPrice, $dec, ',', '.')
                ],
                'nama_bagian' => $namaBagian,  
                'printDate' => now()->format('d-M-Y H:i'),
                'filterInfo' => "DATE: " . $dateHistory,
                'report_title' => 'DAILY REPORT WI'
            ];
            
            // Generate PDF
            $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $namaBagian);
            $historyPdfName = "Daily Report WI - {$safeName} - {$dateHistory}.pdf";
            
            $pdfViewData = ['reports' => [$reportData], 'isEmail' => true];
            
            $historyPdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.log_history', $pdfViewData)
                          ->setPaper('a4', 'landscape');
            
            $historyPath = storage_path("app/public/{$historyPdfName}");
            $historyPdf->save($historyPath);
            $filesToAttach[] = $historyPath;
            
            $this->info("   Generated PDF: {$historyPdfName}");
        }

        foreach($activeDocsAll as $doc) {
             $payload = is_string($doc->payload_data) ? json_decode($doc->payload_data, true) : (is_array($doc->payload_data) ? $doc->payload_data : []);
             if (is_array($payload)) {
                $updatedPayload = [];
                foreach ($payload as $item) {
                    // 1. Buyer
                    $item['buyer_sourced'] = $item['name1'] ?? '-';
                    
                    $netpr = isset($item['netpr']) ? floatval($item['netpr']) : 0;
                    $waerk = isset($item['waerk']) ? $item['waerk'] : '';
                    
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
                 
                 $totalDocPrice = 0;
                 $docCurrency = 'IDR'; 
                 
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
 
         }

        if (empty($filesToAttach)) {
            $this->info("   No reports/files to send.");
        } else {
            $recipients = ['tataamal1128@gmail.com','finc.smg@pawindo.com','kmi356smg@gmail.com','adm.mkt5.smg@gmail.com','lily.smg@pawindo.com','kmi3.60.smg@gmail.com','kmi3.31.smg@gmail.com','kmi3.16.smg@gmail.com','kmi3.29.smg@gmail.com'];
            // $recipients = ['tataamal1128@gmail.com'];
            $dateInfoFormatted = Carbon::parse($dateHistory)->locale('id')->translatedFormat('d F Y');

            try {
                \Illuminate\Support\Facades\Mail::to($recipients)->send(new \App\Mail\LogHistoryMail($filesToAttach, $dateInfoFormatted));
                $this->info("   Global Email sent successfully.");
            } catch (\Exception $e) {
                $this->error("   Failed to send Global Email: " . $e->getMessage());
            }
        }
    }
}
