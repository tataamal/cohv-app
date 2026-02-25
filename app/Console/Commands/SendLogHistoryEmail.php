<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HistoryWi;
use Carbon\Carbon;
use App\Models\KodeLaravel;

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
        $queryHistory = HistoryWi::with(['kode', 'items.pros'])
                    ->where('wi_document_code', 'LIKE', 'WIH%')
                    ->whereDate('document_date', $dateHistory)
                    ->orderBy('created_at', 'desc');

        $queryActive = HistoryWi::with(['kode', 'items.pros'])
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
            if ($kData && $kData->kode != $pCode) {
                 $kData = null; // Invalidate if mismatch
            }
            if (!$kData) {
                $kData = KodeLaravel::where('laravel_code', $pCode)->first();
            }
            $rawName = $kData ? $kData->description : 'UNKNOWN';
            // Sanitize: Replace en-dash/em-dash with standard hyphen
            $rawName = str_replace(['–', '—'], '-', $rawName);
            
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
            
            // Refactored: Use relationship
            foreach ($doc->items as $item) {

                 $item->setAttribute('_doc_no', $doc->wi_document_code);
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

            $wcCodesToFetch = [];
            foreach ($rawItems as $item) {
                 $w = !empty($item->child_wc) ? $item->child_wc : ($item->parent_wc ?? '-');
                 if ($w !== '-') $wcCodesToFetch[] = $w;
            }
            $wcCodesToFetch = array_unique($wcCodesToFetch);

            $wcMap = \App\Models\workcenter::whereIn('kode_wc', $wcCodesToFetch)
                ->select('kode_wc', 'description', 'operating_time')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [strtoupper(trim($item->kode_wc)) => [
                        'description' => $item->description,
                        'operating_time' => $item->operating_time
                    ]];
                })
                ->toArray();

            $processedItems = [];
            
            foreach ($rawItems as $item) {
                 $wcCode = !empty($item->child_wc) ? $item->child_wc : ($item->parent_wc ?? '-');
                 $wcCodeClean = strtoupper(trim($wcCode));
                 $wcData = $wcMap[$wcCodeClean] ?? ['description' => '-', 'operating_time' => 0];
                 $wcDesc = $wcData['description'];
                 
                 $matnr = $item->material_number ?? '';
                 if(ctype_digit($matnr)) { $matnr = ltrim($matnr, '0'); }

                 $assigned = floatval($item->assigned_qty ?? 0);
                 
                 // Calculate confirmed & remarks from PROS
                 $confirmed = 0;
                 $remarkQty = 0;
                 $remarkTexts = [];
                 $remarkDetails = [];

                 foreach ($item->pros as $pro) {
                    $st = strtolower($pro->status ?? '');
                    if (in_array($st, ['confirmation', 'confirm', 'confirmed', 'confirmasi', 'konfirmasi'])) {
                        $confirmed += $pro->qty_pro;
                    } elseif (str_contains($st, 'remark')) {
                        $remarkQty += $pro->qty_pro; // Restored
                        
                        $rText = $pro->remark_text;
                        $rTag = $pro->tag;
                        
                        $effectiveRemark = $rText;
                        if (empty($effectiveRemark)) {
                            $effectiveRemark = $rTag;
                        }
                        if (empty($effectiveRemark)) {
                            $effectiveRemark = '-';
                        }
                        
                        if (!empty($rText)) {
                            $remarkTexts[] = $rText;
                        } elseif (!empty($rTag)) {
                            $remarkTexts[] = $rTag;
                        }

                        $remarkDetails[] = [
                            'qty' => $pro->qty_pro,
                            'remark' => $effectiveRemark,
                            'remark_text' => $rText,
                            'tag' => $rTag
                        ];
                    }
                 }

                 $remarkText = !empty($remarkTexts) ? implode("\n", $remarkTexts) : '-';
                 $netpr = floatval($item->netpr ?? 0); 
                 $waerk = $item->waerk ?? ''; 
                 
                 $prefix = strtoupper($waerk) === 'USD' ? '$ ' : (strtoupper($waerk) === 'IDR' ? 'Rp ' : (!empty($waerk) ? strtoupper($waerk) . ' ' : ''));
                 $decimals = strtoupper($waerk) === 'USD' ? 2 : 0;
                 $confirmedPrice = $netpr * $confirmed;
                 
                 $balance = $assigned - ($confirmed + $remarkQty);
                 $failedPrice = $netpr * ($balance + $remarkQty);

                 // Takt Time Logic (Updated to Assigned Qty)
                 $vgw01 = floatval($item->vgw01 ?? 0);
                 $vge01 = strtoupper(trim($item->vge01 ?? ''));
                 
                 // 1. Planned (Assigned)
                 $baseMins = $vgw01 * $assigned; 
                 if (in_array($vge01, ['S', 'SEC'])) {
                     $baseMins = $baseMins / 60;
                 } elseif (in_array($vge01, ['H', 'HUR', 'HR'])) {
                     $baseMins = $baseMins * 60;
                 }

                 // 2. Confirmed
                 $confBaseMins = $vgw01 * $confirmed;
                 if (in_array($vge01, ['S', 'SEC'])) {
                     $confBaseMins = $confBaseMins / 60;
                 } elseif (in_array($vge01, ['H', 'HUR', 'HR'])) {
                     $confBaseMins = $confBaseMins * 60;
                 }

                 $finalTime = $baseMins;
                 // Machining Logic Override: Removed/Disabled as user requested strict "Assigned * VGW01"
                 // If previous logic wanted Daily Load, this change overwrites it to Total Load as per instruction.
                 
                 if ($finalTime > 0) {
                     $totSec = $finalTime * 60;
                     $hrs = floor($totSec / 3600);
                     $mins = floor(($totSec % 3600) / 60);
                     $secs = round($totSec % 60);
                     
                     $parts = [];
                     if ($hrs > 0) $parts[] = $hrs . ' Jam';
                     if ($mins > 0) $parts[] = $mins . ' Menit';
                     if ($secs > 0 || empty($parts)) $parts[] = $secs . ' Detik';
                     
                     $taktFull = implode(', ', $parts);
                 } else {
                     $taktFull = '-';
                 }

                 $priceOkFmt = $prefix . number_format($confirmedPrice, $decimals, ',', '.');
                 $priceFailFmt = $prefix . number_format($failedPrice, $decimals, ',', '.');

                 // SO Item Logic
                 $kdauf = $item->kdauf ?? '-';
                 $kdpos = $item->kdpos ? ltrim((string)$item->kdpos, '0') : '-';
                 $soItem = ($kdauf !== '-' && $kdpos !== '-') ? "{$kdauf}-{$kdpos}" : '-';

                 $nik = $item->nik ?? '-';
                 
                 $processedItems[] = [
                    'doc_no'        => $item->_doc_no,
                    'workcenter'    => $wcCode,
                    'wc_description'=> $wcDesc,
                    'so_item'       => $soItem,
                    'aufnr'         => $item->aufnr ?? '-',
                    'vornr'         => $item->vornr ?? '', 
                    'material'      => $matnr,
                    'description'   => $item->material_desc ?? '-',
                    'assigned'      => $assigned,
                    'confirmed'     => $confirmed,
                    'remark_qty'    => $remarkQty,
                    'remark_text'   => $remarkText,
                    'remark_details'=> $remarkDetails,
                    'takt_time'     => $taktFull,
                    'nik'           => $nik,
                    'name'          => $item->operator_name ?? '-',
                    'price_formatted' => $priceOkFmt,
                    'price_ok_fmt'    => $priceOkFmt,
                    'price_fail_fmt'  => $priceFailFmt,
                    'confirmed_price' => $confirmedPrice, 
                    'failed_price'    => $failedPrice,
                    'currency'        => strtoupper($waerk),
                    'raw_total_time'  => $finalTime,
                    'raw_confirmed_time' => $confBaseMins, // NEW
                    'is_machining'    => (int)($item->machining ?? 0) === 1, // NEW
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
            $totalAssignedPrice = $totalConfirmedPrice + $totalFailedPrice;
            $achievement = $totalAssigned > 0 ? round(($totalConfirmed / $totalAssigned) * 100) . '%' : '0%';

            $firstCurr = collect($sortedItems)->first()['currency'] ?? '';
            $pfx = (strtoupper($firstCurr) === 'USD') ? '$ ' : 'Rp ';
            $dec = (strtoupper($firstCurr) === 'USD') ? 2 : 0;

            $wcKendalaArr = collect($sortedItems)
                ->filter(fn($i) => ($i['remark_qty'] ?? 0) > 0)
                ->pluck('workcenter')
                ->unique()
                ->filter()
                ->values()
                ->all();

            $reportData = [
                'items' => $sortedItems,
                'summary' => [
                    'total_assigned' => $totalAssigned,
                    'total_confirmed' => $totalConfirmed,
                    'total_failed' => $totalFailed,
                    'achievement_rate' => $achievement,
                    'wc_kendala' => empty($wcKendalaArr) ? '-' : implode(', ', $wcKendalaArr),
                    'total_price_assigned_raw' => $totalAssignedPrice,
                    'total_price_assigned' => $pfx . number_format($totalAssignedPrice, $dec, ',', '.'),
                    'total_price_ok_raw' => $totalConfirmedPrice,
                    'total_price_ok' => $pfx . number_format($totalConfirmedPrice, $dec, ',', '.'),
                    'total_price_fail_raw' => $totalFailedPrice,
                    'total_price_fail' => $pfx . number_format($totalFailedPrice, $dec, ',', '.')
                ],
                'nama_bagian' => $namaBagian,  
                'printDate' => now()->format('d-M-Y H:i'),
                'filterInfo' => "DATE: " . $dateHistory,
                'report_title' => 'DAILY REPORT'
            ];
            
            // Generate PDF
            $safeName = preg_replace('/[^A-Za-z0-9]/', ' ', $namaBagian);
            $safeName = trim(preg_replace('/\s+/', ' ', $safeName));
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
                        $priceFmt = (!empty($waerk) ? strtoupper($waerk) . ' ' : '') . number_format($netpr, 0, ',', '.'); 
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
            // $recipients = [
            //     'finc.smg@pawindo.com',
            //     'kmi356smg@gmail.com',
            //     'adm.mkt5.smg@gmail.com',
            //     'lily.smg@pawindo.com',
            //     'kmi3.60.smg@gmail.com',
            //     'kmi3.31.smg@gmail.com',
            //     'kmi3.16.smg@gmail.com',
            //     'kmi3.29.smg@gmail.com',
            //     'kmi3.58.smg@gmail.com',
            //     'kmi3.57.smg@gmail.com',
            //     'kmi3.2.smg@gmail.com',
            //     'kmi3.1.smg@gmail.com',
            //     'tataamal1128@gmail.com'
            // ];
            $recipients = ['tataamal1128@gmail.com'];
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
