<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HistoryWi;
use Carbon\Carbon;

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
        $plantCode = $this->argument('plant_code');
        $date = $this->option('date') ?? Carbon::today()->subDay()->toDateString(); // Default H-1
        
        $this->info("Processing log for Plant: $plantCode, Date: $date");
        $query = HistoryWi::where('wi_document_code', 'LIKE', 'WIH%')
                    ->whereDate('document_date', $date);
        

        $documents = $query->orderBy('created_at', 'desc')->get();
        if ($documents->isEmpty()) {
            $this->info("No documents found for this date.");
            return;
        }

        // GROUP BY PLANT CODE
        // "buat dia 1 Kode 1 Kertas" -> NOW: Combined in one PDF, separate pages.
        $groups = $documents->groupBy('plant_code');
        $allReports = [];

        foreach ($groups as $pCode => $docs) {
            $this->info("Processing group for Plant: $pCode (" . $docs->count() . " docs)");

            // --- FETCH DYNAMIC INFO ---
            $kodeData = \App\Models\Kode::with('sapUser')->where('kode', $pCode)->first();
            
            $department = $kodeData ? $kodeData->nama_bagian : 'UNKNOWN DEPT';
            $printedBy = ($kodeData && $kodeData->sapUser) ? $kodeData->sapUser->sap_id : 'AUTO_SCHEDULER';
            
            // --- PREPARE DATA ---
            $csvData = [];
            foreach ($docs as $doc) {
                if (empty($doc->payload_data) || !is_array($doc->payload_data)) continue;

                $docDate = Carbon::parse($doc->document_date)->startOfDay();
                $expiredAt = Carbon::parse($doc->expired_at);
                $today = Carbon::today();

                foreach ($doc->payload_data as $item) {
                     $wc = !empty($item['workcenter_induk']) ? $item['workcenter_induk'] : ($item['child_workcenter'] ?? '-');
                    $matnr = $item['material_number'] ?? '';
                    if(ctype_digit($matnr)) { $matnr = ltrim($matnr, '0'); }
                    
                    $assigned = isset($item['assigned_qty']) ? floatval($item['assigned_qty']) : 0;
                    $confirmed = isset($item['confirmed_qty']) ? floatval($item['confirmed_qty']) : 0;
                    $balance = $assigned - $confirmed;

                    // --- PRICE LOGIC ---
                    $netpr = isset($item['netpr']) ? floatval($item['netpr']) : 0;
                    $waerk = isset($item['waerk']) ? $item['waerk'] : '';
                    $rawPrice = $netpr * $confirmed;
                    
                    if (strtoupper($waerk) === 'USD') {
                        $priceFormatted = '$ ' . number_format($rawPrice, 2);
                    } elseif (strtoupper($waerk) === 'IDR') {
                        $priceFormatted = 'Rp ' . number_format($rawPrice, 0, ',', '.');
                    } else {
                        $priceFormatted = $rawPrice; 
                    }

                    // --- STATUS LOGIC ---
                    if ($balance < 0) {
                        $status = 'COMPLETED';
                    }
                    elseif ($docDate->equalTo($today) && $balance > 0) {
                        $status = 'ACTIVE';
                    }
                    elseif ($today->lt($docDate)) {
                        $status = 'INACTIVE';
                    }
                    elseif (now()->gt($expiredAt) && $balance > 0) {
                        $status = 'NOT COMPLETED';
                    }
                    else {
                        $status = $item['status_pro'] ?? '-';
                    }

                    $confirmedPrice = $netpr * $confirmed;
                    $failedPrice = $netpr * $balance;

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
                        
                        'price_formatted' => $priceFormatted,
                        'confirmed_price' => $confirmedPrice, 
                        'failed_price'    => $failedPrice,    
                        'currency'        => strtoupper($waerk),
                        'buyer'           => $item['name1'] ?? '-',
                        
                        'nik'           => $item['nik'] ?? '-',
                        'name'          => $item['name'] ?? '-',
                        'status'        => $status,
                    ];
                }
            }

            if (empty($csvData)) continue;

             // Filter Info
             $filterString = "Date: " . $date . " (Docs: WIH%)";

             // Calculations for Summary
             $totalAssigned = collect($csvData)->sum('assigned');
             $totalConfirmed = collect($csvData)->sum('confirmed'); 
             $totalFailed = $totalAssigned - $totalConfirmed;
             $achievement = $totalAssigned > 0 ? round(($totalConfirmed / $totalAssigned) * 100) . '%' : '0%';

             // Add to All Reports
             $allReports[] = [
                'items' => $csvData,
                'summary' => [
                    'total_assigned' => $totalAssigned,
                    'total_confirmed' => $totalConfirmed,
                    'total_failed' => $totalFailed,
                    'achievement_rate' => $achievement
                ],
                'printedBy' => $printedBy,
                'department' => $department,
                'printDate' => now()->format('d-M-Y H:i'),
                'filterInfo' => $filterString
             ];

        } // End Group Loop

        if (empty($allReports)) {
             $this->info("No valid report data generated.");
             return;
        }

        // 3. Create PDF File (Combined)
        $fileName = 'log_wi_combined_' . now()->format('Ymd_His') . '.pdf';
        $filePath = storage_path('app/public/' . $fileName);
        
        try {
            // Generate PDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.log_history', ['reports' => $allReports])
                    ->setPaper('a4', 'landscape');
            
            $pdf->save($filePath);

            // 4. Send Email
            $recipients = [
                // 'finc.smg@pawindo.com',
                // 'kmi356smg@gmail.com',
                // 'adm.mkt5.smg@pawindo.com',
                // 'lily.smg@pawindo.com',
                'tataamal1128@gmail.com'
            ];
            $dateInfo = Carbon::parse($date)->locale('id')->translatedFormat('d F Y');
            
            \Illuminate\Support\Facades\Mail::to($recipients)->send(new \App\Mail\LogHistoryMail($filePath, $dateInfo));
            
            $this->info("Email sent successfully with " . count($allReports) . " reports.");

        } catch (\Exception $e) {
            $this->error("Failed to send email: " . $e->getMessage());
        }
    }
}
