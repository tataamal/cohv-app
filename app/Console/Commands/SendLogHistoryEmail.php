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
        $date = $this->option('date') ?? now()->format('Y-m-d'); 
        
        $this->info("Processing log for Plant: $plantCode, Date: $date");

        // 1. Fetch Data
        $query = HistoryWi::where('plant_code', $plantCode)->whereDate('created_at', $date);
        
        $documents = $query->orderBy('created_at', 'desc')->get();
        if ($documents->isEmpty()) {
            $this->info("No documents found for this date.");
            return;
        }

        // 2. Prepare Data
        $csvData = [];
        $today = Carbon::today();

        foreach ($documents as $doc) {
            if (empty($doc->payload_data) || !is_array($doc->payload_data)) continue;

            $docDate = Carbon::parse($doc->document_date)->startOfDay();
            $expiredAt = Carbon::parse($doc->expired_at);

            foreach ($doc->payload_data as $item) {
                // Reuse logic from controller needed here
                $wc = !empty($item['workcenter_induk']) ? $item['workcenter_induk'] : ($item['child_workcenter'] ?? '-');
                $matnr = $item['material_number'] ?? '';
                if(ctype_digit($matnr)) { $matnr = ltrim($matnr, '0'); }
                
                $assigned = isset($item['assigned_qty']) ? floatval($item['assigned_qty']) : 0;
                $confirmed = isset($item['confirmed_qty']) ? floatval($item['confirmed_qty']) : 0;
                $balance = $assigned - $confirmed;

                // --- PRICE LOGIC ---
                $netpr = isset($item['NETPR']) ? floatval($item['NETPR']) : 0;
                $waerk = isset($item['WAERK']) ? $item['WAERK'] : '';
                $rawPrice = $netpr * $confirmed;
                
                if (strtoupper($waerk) === 'USD') {
                    $priceFormatted = '$ ' . number_format($rawPrice, 2);
                } elseif (strtoupper($waerk) === 'IDR') {
                    $priceFormatted = 'Rp. ' . number_format($rawPrice, 0, ',', '.');
                } else {
                    $priceFormatted = $rawPrice; 
                }

                // --- STATUS LOGIC ---
                if (isset($item['status_pro']) && $item['status_pro'] === 'Completed') {
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
                    
                    'nik'           => $item['nik'] ?? '-',
                    'name'          => $item['name'] ?? '-',
                    'status'        => $status,
                ];
            }
        }

        // 3. Create CSV
        $fileName = 'log_wi_auto_' . $plantCode . '_' . now()->format('Ymd_His') . '.csv';
        $filePath = storage_path('app/public/' . $fileName);
        
        try {
            $csv = \League\Csv\Writer::createFromPath($filePath, 'w+');

             // --- HEADER SUMMARY (Sync with Controller) ---
             $totalAssignedReal = 0; 
             $totalConfirmed = 0;
             foreach($csvData as $row) {
                 $totalAssignedReal += $row['assigned'];
                 $totalConfirmed += $row['confirmed'];
             }
             $balance = $totalAssignedReal - $totalConfirmed;
             $achievement = $totalAssignedReal > 0 ? round(($totalConfirmed / $totalAssignedReal) * 100) . '%' : '0%';
 
             // Baris 1-4: Summary
             $csv->insertOne(['PT KAYU MEBEL INDONESIA', '', '', '', 'EXPIRED SUMMARY']);
             $csv->insertOne(['DEPARTMENT: PACKING', 'USER: AUTO_EMAIL', 'PRINT DATE: ' . now()->format('d-M-Y H:i'), 'TOTAL ITEM: ' . $documents->count() . ' DOCUMENTS']);
             $csv->insertOne(['TOTAL ASSIGNED QTY', 'TOTAL CONFIRMED QTY', 'UNCONFIRMED (LOSS/WIP)', 'ACHIEVEMENT RATE']);
             $csv->insertOne([$totalAssignedReal, $totalConfirmed, $balance, $achievement]);
             $csv->insertOne([]); // Spacer
 
            // Baris 6: Header Table
            $csv->insertOne([
                'NO', 'WI CODE', 'EXPIRED AT', 'WC', 'PRO', 'MATERIAL', 'DESCRIPTION', 
                'ASSIGNED', 'CONFIRMED', 'BALANCE', 'PRICE', 'NIK', 'NAME', 'STATUS'
            ]);
            
            $no = 1;
            foreach ($csvData as $row) {
                $csv->insertOne([
                    $no++,
                    $row['doc_no'],
                    $row['expired_at'],
                    $row['workcenter'],
                    $row['aufnr'],
                    $row['material'],
                    $row['description'],
                    $row['assigned'],
                    $row['confirmed'],
                    $row['balance'],
                    $row['price_formatted'],
                    $row['nik'],
                    $row['name'],
                    $row['status']
                ]);
            }

            // 4. Send Email
            $recipients = [
                // 'finc.smg@pawindo.com',
                // 'kmi356smg@gmail.com',
                // 'adm.mkt5.smg@pawindo.com',
                // 'lily.smg@pawindo.com',
                'tataamal1128@gmail.com'
            ];
            $dateInfo = \Carbon\Carbon::parse($date)->format('d-M-Y');
            
            \Illuminate\Support\Facades\Mail::to($recipients)->send(new \App\Mail\LogHistoryMail($filePath, "Auto Report $plantCode - $dateInfo"));
            
            $this->info("Email sent successfully to recipients.");

        } catch (\Exception $e) {
            $this->error("Failed to send email: " . $e->getMessage());
        }
    }
}
