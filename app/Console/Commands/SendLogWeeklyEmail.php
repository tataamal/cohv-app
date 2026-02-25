<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HistoryWi;
use Carbon\Carbon;
use App\Models\KodeLaravel;
use Illuminate\Support\Facades\Log;

class SendLogWeeklyEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wi:send-weekly-email {start_date? : Start date (dd-mm-yyyy)} {end_date? : End date (dd-mm-yyyy)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Weekly Work Instruction Log History via Email (Mon-Sun)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Determine Date Range
        $startArg = $this->argument('start_date');
        $endArg = $this->argument('end_date');

        try {
            if ($startArg && $endArg) {
                // Custom Range: Start to End
                $startDateCarbon = Carbon::createFromFormat('d-m-Y', $startArg);
                $endDateCarbon = Carbon::createFromFormat('d-m-Y', $endArg);
                
                $startDate = $startDateCarbon->toDateString();
                $endDate = $endDateCarbon->toDateString();
            }
            elseif ($startArg && !$endArg) {
                // Backward Compatibility: Arg is "Target Date" (EndDate)
                // Calculate week range ending at this date (Monday Start)
                $targetDate = Carbon::createFromFormat('d-m-Y', $startArg);
                $startDateCarbon = $targetDate->copy()->startOfWeek(Carbon::MONDAY);
                
                $startDate = $startDateCarbon->toDateString();
                $endDate = $targetDate->toDateString();
            }
            else {
                // Default: Last full week (Start Mon, End Yesterday/Sunday)
                // If running on Monday, "yesterday" is Sunday, which is the end of the target week
                $endDateCarbon = Carbon::yesterday();
                $startDateCarbon = $endDateCarbon->copy()->startOfWeek(Carbon::MONDAY);

                $startDate = $startDateCarbon->toDateString();
                $endDate = $endDateCarbon->toDateString();
            }
        } catch (\Exception $e) {
            $this->error("Invalid date format. Please use dd-mm-yyyy.");
            return 1;
        }

        $this->info("Processing Weekly Reports");
        $this->info("Range: $startDate to $endDate");

        // --- 2. FETCH DATA (Range) ---
        $queryHistory = HistoryWi::with(['kode', 'items.pros'])
                    ->where('wi_document_code', 'LIKE', 'WIH%')
                    ->whereBetween('document_date', [$startDate, $endDate])
                    ->orderBy('created_at', 'desc');

        $historyDocsAll = $queryHistory->get();
        
        if ($historyDocsAll->isEmpty()) {
            $this->info("No documents found for this range.");
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
                ];
            }
            $groupedData[$slug]['plant_codes'][] = $pCode;
            
            // Refactored: Use relationship
            foreach ($doc->items as $item) {
                 $item->setAttribute('_doc_no', $doc->wi_document_code);
                 // Using document_date directly from doc later is safer or attach it here
                 $item->setAttribute('_doc_date', $doc->document_date);
                 $groupedData[$slug]['items'][] = $item;
            }
        }

        $filesToAttach = [];

        // --- 4. GENERATE PDF ---
        foreach ($groupedData as $slug => $group) {
            $namaBagian = $group['display_name']; 
            $uniqueCodes = array_unique($group['plant_codes']);
            $rawItems = $group['items'];
            if (empty($rawItems)) continue;

            $this->info(">>> Processing Bagian: '{$namaBagian}'");

            // Fetch WC Map
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
                    // Try to match key format
                    return [strtoupper(trim($item->kode_wc)) => [
                        'description' => $item->description,
                        'operating_time' => $item->operating_time
                    ]];
                })
                ->toArray();

            $processedItems = [];
            
            foreach ($rawItems as $item) {
                 $wcCode = !empty($item->child_wc) ? $item->child_wc : ($item->parent_wc ?? '-');
                 // Ensure wcCode is clean
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
                    $st = strtolower(trim($pro->status ?? ''));
                    // Add 'confirmasi' and 'konfirmasi' variations
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
                 // Machining Override Disabled per request to use strict Assigned * VGW01
                 
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
                    'doc_date'      => isset($item->_doc_date) ? Carbon::parse($item->_doc_date)->format('d-m-Y') : '', 
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
                'filterInfo' => "DATE: " . Carbon::parse($startDate)->format('d-m-Y') . " TO " . Carbon::parse($endDate)->format('d-m-Y'), // Range Info
                'report_title' => 'WEEKLY REPORT'
            ];
            
            $safeName = preg_replace('/[^A-Za-z0-9]/', ' ', $namaBagian);
            $safeName = trim(preg_replace('/\s+/', ' ', $safeName));
            $weeklyPdfName = "Weekly Report_{$safeName}_{$startDate}_{$endDate}.pdf";
            
            $pdfViewData = ['reports' => [$reportData], 'isEmail' => true];
            
            $weeklyPdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.log_history', $pdfViewData)
                          ->setPaper('a4', 'landscape');
            
            $weeklyPath = storage_path("app/public/{$weeklyPdfName}");
            $weeklyPdf->save($weeklyPath);
            $filesToAttach[] = $weeklyPath;
            
            $this->info("   Generated PDF: {$weeklyPdfName}");
        }

        // --- 5. SEND EMAIL ---
        if (empty($filesToAttach)) {
            $this->info("   No files to send.");
        } else {
            // $recipients = [
            //     'tataamal1128@gmail.com',
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
            //     'kmi3.1.smg@gmail.com'
            // ];

            $recipients = ['tataamal1128@gmail.com'];
            
            $dateInfoFormatted = Carbon::parse($startDate)->format('d-m-Y') . " to " . Carbon::parse($endDate)->format('d-m-Y');
            $subject = "Weekly Report_" . $dateInfoFormatted;

            try {
                
                $dateBody = Carbon::parse($startDate)->format('d-m-Y') . " hingga " . Carbon::parse($endDate)->format('d-m-Y');
                $dateSubject = Carbon::parse($startDate)->format('d-m-Y') . " to " . Carbon::parse($endDate)->format('d-m-Y');

                \Illuminate\Support\Facades\Mail::to($recipients)
                    ->send(new \App\Mail\WeeklyLogHistoryMail($filesToAttach, $dateSubject, $dateBody));
                
                $this->info("   Weekly Email sent successfully.");
            } catch (\Exception $e) {
                $this->error("   Failed to send Weekly Email: " . $e->getMessage());
            }
        }
    }
}
