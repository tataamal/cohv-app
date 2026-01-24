<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionTData3;
use App\Models\KodeLaravel;
use Illuminate\View\View;
use Carbon\Carbon;

class MonitoringProController extends Controller
{
    public function index(string $kode): View
    {
        $today = Carbon::today();
        $baseQuery = ProductionTData3::where('WERKSX', $kode);
        $outgoingProCount = $baseQuery->clone()
                                        ->where('STATS', 'like', '%REL%')
                                        ->whereDate('GSTRP', $today)
                                        ->count();
        $onScheduleProCount = $baseQuery->clone()
                                        ->where('STATS', 'like', '%REL%')
                                        ->whereDate('GSTRP', '<=', $today) // Sudah berjalan
                                        ->whereDate('GLTRP', '>=', $today) // Belum terlambat
                                        ->count();
        $overdueProCount = $baseQuery->clone()
                                        ->where('STATS', 'like', '%REL%')
                                        ->whereDate('GLTRP', '<', $today)
                                        ->count();
        $createdProCount = $baseQuery->clone()->where('STATS', 'CRTD')->count();
        $pros = $baseQuery->clone()->orderBy('AUFNR', 'desc')->get();

        $kodeInfo = KodeLaravel::where('laravel_code', $kode)->first();
        $kategori = $kodeInfo->plant ?? '-';
        $sub_kategori = $kodeInfo->description ?? '-';
        $nama_bagian = $kodeInfo->description ?? '-';

        return view('Features.monitoring-pro', [
            'activeKode' => $kode,
            'outgoingProCount' => $outgoingProCount,
            'onScheduleProCount' => $onScheduleProCount,
            'overdueProCount' => $overdueProCount,
            'createdProCount' => $createdProCount,
            'nama_bagian' => $nama_bagian,
            'sub_kategori' => $sub_kategori,
            'kategori' => $kategori,
            'pros' => $pros,
        ]);
    }
    public function filter(Request $request, string $kode): View
    {
        $status = $request->query('status');
        $today = Carbon::today();
        $query = ProductionTData3::where('WERKSX', $kode);
        match ($status) {
            'outgoing' => $query->where('STATS', 'like', '%REL%')
                                ->whereDate('GSTRP', $today),
            'on-schedule' => $query->where('STATS', 'like', '%REL%')
                                ->whereDate('GSTRP', '<=', $today)
                                ->whereDate('GLTRP', '>=', $today),
            'overdue' => $query->where('STATS', 'like', '%REL%')
                            ->whereDate('GLTRP', '<', $today),
            'created' => $query->where('STATS', 'CRTD'),
            default => null,
        };

        $pros = $query->orderBy('AUFNR', 'desc')->get();

        // Kembalikan HANYA bagian tabelnya saja (bukan seluruh halaman)
        return view('Features.partials.pro-table', ['pros' => $pros]);
    }

    public function showByBuyer(string $kode, string $buyerName, ?string $status = null): View
    {
        $today = Carbon::today();
        $query = ProductionTData3::where('WERKSX', $kode)->where('NAME1', $buyerName);
        $allProForBuyer = $query->clone()
                                ->where(function ($q) {
                                    $q->where('STATS', 'CRTD')
                                    ->orWhere('STATS', 'like', '%REL%');
                                })
                                ->get();

        $totalPro = $allProForBuyer->count();
        $totalOrderQuantity = $allProForBuyer->sum('PSMNG');
        $totalCompletedQuantity = $allProForBuyer->sum('WEMNG');
        if ($totalOrderQuantity > 0) {
            $rawCompletionRate = ($totalCompletedQuantity / $totalOrderQuantity) * 100;
            $completionRate = number_format($rawCompletionRate, 1) . '%';
        } else {
            $completionRate = '0.0%';
        }
        $query->when($status, function ($q, $status) use ($today) {
            $todayString = $today->toDateString();
            if ($status === 'on-schedule') {
                return $q->where('STATS', 'like', '%REL%')->whereDate('GLTRP', '>=', $todayString);
            }
            
            if ($status === 'overdue') {
                return $q->where('STATS', 'like', '%REL%')->whereDate('GLTRP', '<', $todayString);
            }

            if ($status === 'created') {
                return $q->where('STATS', 'CRTD');
            }
        });
        $proList = $query->orderBy('GLTRP', 'asc')->get();
        $proList->transform(function ($pro) use ($today) {
            $deadline = Carbon::parse($pro->GLTRP);
            if ($pro->STATS === 'CRTD') {
                $pro->status_text = 'Created';
                $pro->status_class = 'created';
            } elseif (str_contains($pro->STATS, 'REL')) { 
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

        $summaryCardTitle = 'Total PRO';
        $summaryCardCount = $totalPro; 
        $summaryCardSubtitle = 'Count All Relevant PRO';

        if ($status) {
            $summaryCardCount = $proList->count();
            switch ($status) {
                case 'on-schedule':
                    $summaryCardTitle = 'PRO On Schedule';
                    $summaryCardSubtitle = 'Filtered by On Schedule (Status REL)';
                    break;
                case 'overdue':
                    $summaryCardTitle = 'PRO Overdue';
                    $summaryCardSubtitle = 'Filtered by Overdue (Status REL)';
                    break;
                case 'created':
                    $summaryCardTitle = 'PRO Created';
                    $summaryCardSubtitle = 'Filtered by Created';
                    break;
            }
        }

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
