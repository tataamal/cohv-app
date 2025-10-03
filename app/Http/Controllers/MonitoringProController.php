<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionTData3;
use Illuminate\View\View;
use Carbon\Carbon;

class MonitoringProController extends Controller
{
    public function index(string $kode): View
    {
        $today = Carbon::today();
        $baseQuery = ProductionTData3::where('WERKSX', $kode);

        // Menghitung jumlah untuk setiap status dengan LOGIKA BARU
        $outgoingProCount = $baseQuery->clone()->whereDate('GSTRP', $today)->count();
        
        // On-Schedule sekarang memeriksa STATS == 'REL'
        $onScheduleProCount = $baseQuery->clone()
                                        ->where('STATS', 'REL')
                                        ->whereDate('GLTRP', '>=', $today)
                                        ->count();
        
        // Overdue sekarang memeriksa STATS == 'REL'
        $overdueProCount = $baseQuery->clone()
                                    ->where('STATS', 'REL')
                                    ->whereDate('GLTRP', '<', $today)
                                    ->count();
        
        // Created tetap sama
        $createdProCount = $baseQuery->clone()->where('STATS', 'CRTD')->count();

        // Mengambil semua PRO untuk tampilan awal
        $pros = $baseQuery->clone()->orderBy('AUFNR', 'desc')->get();

        return view('Features.monitoring-pro', [
            'activeKode' => $kode,
            'outgoingProCount' => $outgoingProCount,
            'onScheduleProCount' => $onScheduleProCount,
            'overdueProCount' => $overdueProCount,
            'createdProCount' => $createdProCount,
            'pros' => $pros,
        ]);
    }
    public function filter(Request $request, string $kode): View
    {
        $status = $request->query('status');
        $today = Carbon::today();
        $query = ProductionTData3::where('WERKSX', $kode);

        // Terapkan filter berdasarkan status dengan LOGIKA BARU
        match ($status) {
            'outgoing'    => $query->whereDate('GSTRP', $today),
            
            // On-Schedule sekarang memeriksa STATS == 'REL'
            'on-schedule' => $query->where('STATS', 'REL')->whereDate('GLTRP', '>=', $today),
            
            // Overdue sekarang memeriksa STATS == 'REL'
            'overdue'     => $query->where('STATS', 'REL')->whereDate('GLTRP', '<', $today),
            
            // Created tetap sama
            'created'     => $query->where('STATS', 'CRTD'),
            
            default       => null, // 'all' atau status tidak valid tidak perlu filter tambahan
        };

        $pros = $query->orderBy('AUFNR', 'desc')->get();

        // Kembalikan HANYA bagian tabelnya saja (bukan seluruh halaman)
        return view('Features.partials.pro-table', ['pros' => $pros]);
    }

    public function showByBuyer(string $kode, string $buyerName, ?string $status = null): View
    {
        // 1. Inisialisasi tanggal dan query dasar
        $today = Carbon::today();
        $query = ProductionTData3::where('WERKSX', $kode)->where('NAME1', $buyerName);

        // 2. Kalkulasi statistik HANYA untuk PRO yang relevan (CRTD atau REL)
        $allProForBuyer = $query->clone()
                                ->whereIn('STATS', ['CRTD', 'REL'])
                                ->get();

        $totalPro = $allProForBuyer->count();
        $totalOrderQuantity = $allProForBuyer->sum('PSMNG');
        $totalCompletedQuantity = $allProForBuyer->sum('WEMNG');

        // Menghitung persentase penyelesaian berdasarkan data yang sudah difilter
        if ($totalOrderQuantity > 0) {
            $rawCompletionRate = ($totalCompletedQuantity / $totalOrderQuantity) * 100;
            $completionRate = number_format($rawCompletionRate, 1) . '%';
        } else {
            $completionRate = '0.0%';
        }

        // 3. Terapkan filter berdasarkan status jika ada
        $query->when($status, function ($q, $status) use ($today) {
            $todayString = $today->toDateString();
            
            if ($status === 'on-schedule') {
                return $q->where('STATS', 'REL')->whereDate('GLTRP', '>=', $todayString);
            }
            
            if ($status === 'overdue') {
                return $q->where('STATS', 'REL')->whereDate('GLTRP', '<', $todayString);
            }

            if ($status === 'created') {
                return $q->where('STATS', 'CRTD');
            }
        });

        // 4. Ambil hasil PRO setelah mungkin difilter, urutkan berdasarkan deadline
        $proList = $query->orderBy('GLTRP', 'asc')->get();

        // 5. Proses setiap PRO (transformasi) untuk menambahkan data yang dibutuhkan di view
        $proList->transform(function ($pro) use ($today) {
            $deadline = Carbon::parse($pro->GLTRP);

            // Menentukan status text, class, dan data tambahan
            if ($pro->STATS === 'CRTD') {
                $pro->status_text = 'Created';
                $pro->status_class = 'created';
            } elseif ($pro->STATS === 'REL') {
                if ($deadline->isPast() && !$deadline->isToday()) {
                    $pro->status_text = 'Overdue';
                    $pro->status_class = 'overdue';
                    $pro->overdue_days = $deadline->diffInDays($today); 
                } else {
                    $pro->status_text = 'On Schedule';
                    $pro->status_class = 'on-schedule';
                }
            }
            
            $pro->progress_percentage = ($pro->PSMNG > 0) ? ($pro->WEMNG / $pro->PSMNG) * 100 : 0;
            $pro->formatted_deadline = $deadline->format('d M');
            
            return $pro;
        });

        // Menyiapkan data dinamis untuk summary card berdasarkan status yang aktif
        $summaryCardTitle = 'Total PRO';
        $summaryCardCount = $totalPro; // Defaultnya adalah total keseluruhan
        $summaryCardSubtitle = 'Count All Relevant PRO';

        if ($status) {
            $summaryCardCount = $proList->count(); // Jika ada filter, hitung dari hasil filter
            switch ($status) {
                case 'on-schedule':
                    $summaryCardTitle = 'PRO On Schedule';
                    $summaryCardSubtitle = 'Filtered by On Schedule';
                    break;
                case 'overdue':
                    $summaryCardTitle = 'PRO Overdue';
                    $summaryCardSubtitle = 'Filtered by Overdue';
                    break;
                case 'created':
                    $summaryCardTitle = 'PRO Created';
                    $summaryCardSubtitle = 'Filtered by Created';
                    break;
            }
        }

        // 6. Kirim semua data yang sudah siap ke view
        return view('Features.pro-details', [
            'buyerName' => $buyerName,
            'totalPro' => $totalPro,
            'totalOrderQuantity' => $totalOrderQuantity,
            'completionRate' => $completionRate,
            'proList' => $proList,
            'activeKode' => $kode,
            // Variabel dinamis untuk kartu ringkasan
            'summaryCardTitle' => $summaryCardTitle,
            'summaryCardCount' => $summaryCardCount,
            'summaryCardSubtitle' => $summaryCardSubtitle,
        ]);
    }
}
