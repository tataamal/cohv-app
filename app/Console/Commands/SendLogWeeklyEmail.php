<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HistoryWi;
use Carbon\Carbon;
use App\Models\Kode;
use Illuminate\Support\Facades\Log;

class SendLogWeeklyEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wi:send-weekly-email {date? : Specific end date dd-mm-yyyy (default: today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Weekly Work Instruction Log History via Email (Mon-Sat, optionally prev Sun)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Determine Date Range
        $dateArg = $this->argument('date');
        
        if ($dateArg) {
            try {
                $endDateCarbon = Carbon::createFromFormat('d-m-Y', $dateArg);
            } catch (\Exception $e) {
                $this->error("Invalid date format. Please use dd-mm-yyyy.");
                return 1;
            }
        } else {
            $endDateCarbon = Carbon::today();
        }

        // Logic: 
        // End Date = Given Date (Ideally Saturday)
        // Start Date = Previous Monday relative to End Date
        // Check Date = Previous Sunday (Day before Start Date)

        // Ensure we handle cases where it's run not on Saturday?
        // User said: "Run on Saturday". Assumption: End Date is the run date (Saturday).
        // If run on Saturday, Start of week is Monday.
        
        // Use logic: Get Start of Week (Monday)
        $startDateCarbon = $endDateCarbon->copy()->startOfWeek(Carbon::MONDAY);
        
        // If the command is run on Saturday (6), $endDateCarbon is Saturday.
        // If $endDateCarbon is NOT Saturday, should we force it? 
        // User's requirement: "Range tanggal adalah satu minggu kebelakang".
        // Let's stick to: EndDate = Input/Today. StartDate = Input/Today's Monday.
        
        // Check Previous Sunday
        $prevSundayCarbon = $startDateCarbon->copy()->subDay();
        $prevSundayDate = $prevSundayCarbon->toDateString();
        
        // Check if data exists for Previous Sunday
        $hasSundayData = HistoryWi::where('wi_document_code', 'LIKE', 'WIH%')
                                  ->whereDate('document_date', $prevSundayDate)
                                  ->exists();

        if ($hasSundayData) {
            $startDateCarbon = $prevSundayCarbon;
            $this->info("Found data on previous Sunday ($prevSundayDate). Including it in range.");
        }

        $startDate = $startDateCarbon->toDateString();
        $endDate = $endDateCarbon->toDateString();

        $this->info("Processing Weekly Reports");
        $this->info("Range: $startDate to $endDate");

        // --- 2. FETCH DATA (Range) ---
        $queryHistory = HistoryWi::with('kode')
                    ->where('wi_document_code', 'LIKE', 'WIH%')
                    ->whereBetween('document_date', [$startDate, $endDate])
                    ->orderBy('created_at', 'desc');

        $historyDocsAll = $queryHistory->get();
        
        if ($historyDocsAll->isEmpty()) {
            $this->info("No documents found for this range.");
            return;
        }

        // --- 3. PROCESS DATA (GROUPING) ---
        // Duplicated Logic from SendLogHistoryEmail to ensure stability
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
                ];
            }
            $groupedData[$slug]['plant_codes'][] = $pCode;
            
            $payload = is_array($doc->payload_data) ? $doc->payload_data : (is_string($doc->payload_data) ? json_decode($doc->payload_data, true) : []);
            if (!is_array($payload)) continue;

            foreach ($payload as $item) {
                 $item['_doc_no'] = $doc->wi_document_code;
                 $item['_doc_date'] = $doc->document_date; // Capture Date
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
                 $remarkText = preg_replace('/^Qty\s+\d+:\s*/i', '', $remarkText);
                 
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
                 $kdpos = isset($item['kdpos']) ? ltrim($item['kdpos'], '0') : '';
                 $soItem = $kdauf . ($kdpos ? '-' . $kdpos : '');

                 $nik = $item['nik'] ?? '-';
                 
                 $processedItems[] = [
                    'doc_no'        => $item['_doc_no'],
                    'doc_date'      => isset($item['_doc_date']) ? Carbon::parse($item['_doc_date'])->format('d-m-Y') : '', // Format Date
                    'workcenter'    => $wcCode,
                    'wc_description'=> $wcDesc,
                    'so_item'       => $soItem,
                    'aufnr'         => $item['aufnr'] ?? '-',
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
                    'currency'        => strtoupper($waerk)
                 ];
            }

            // SORT: NIK ASC, WC ASC
            $sortedItems = collect($processedItems)->sortBy([
                ['nik', 'asc'],
                ['workcenter', 'asc']
            ])->values()->all();

            // Summary
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
                'filterInfo' => "DATE: " . $startDate . " TO " . $endDate, // Range Info
                'report_title' => 'WEEKLY REPORT WI'
            ];
            
            // Filename: "Daily Weekly Report_nama bagian_ range tanggal.pdf"
            $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $namaBagian);
            $weeklyPdfName = "Weekly Report WI_{$safeName}_{$startDate}_{$endDate}.pdf";
            
            $pdfViewData = ['reports' => [$reportData]];
            
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
            $recipients = [
                'tataamal1128@gmail.com',
                'finc.smg@pawindo.com',
                'kmi356smg@gmail.com',
                'adm.mkt5.smg@gmail.com',
                'lily.smg@pawindo.com',
                'kmi3.60.smg@gmail.com',
                'kmi3.31.smg@gmail.com',
                'kmi3.16.smg@gmail.com',
                'kmi3.29.smg@gmail.com'
            ];

            // $recipients = ['tataamal1128@gmail.com','kmi3.60.smg@gmail.com'];
            
            $dateInfoFormatted = Carbon::parse($startDate)->format('d-m-Y') . " to " . Carbon::parse($endDate)->format('d-m-Y');
            $subject = "Weekly Report WI_" . $dateInfoFormatted;

            try {
                // Using the same Mail Class? The User didn't specify a new Email Template.
                // Assuming LogHistoryMail can handle a generic subject or we pass the formatted date as subject suffix.
                // LogHistoryMail signature: __construct($files, $dateInfo)
                // $dateInfo is used in Subject: "Log History WI - $dateInfo"
                // We want Subject: "Daily Weekly Report_..."
                // We might need to modify LogHistoryMail to accept a full custom subject or create a new one.
                // For now, I will assume I can reuse LogHistoryMail and it puts the $dateInfo in the subject.
                // If the Mailable hardcodes "Log History WI", I might need to adjust it or create a generic one.
                // Let's check LogHistoryMail if possible. I'll just send it.
                
                $dateBody = Carbon::parse($startDate)->format('d-m-Y') . " hingga " . Carbon::parse($endDate)->format('d-m-Y');
                
                // Subject is handled in Envelope of Mailable, but we pass dateInfo string
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
