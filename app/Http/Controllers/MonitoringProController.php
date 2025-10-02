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
        $outgoingProCount = $baseQuery->clone()->whereDate('GSTRP', $today)->count();
        $onScheduleProCount = $baseQuery->clone()->whereDate('GLTRP', '>=', $today)->count();
        $overdueProCount = $baseQuery->clone()->whereDate('GLTRP', '<', $today)->count();
        $createdProCount = $baseQuery->clone()->where('STATS', 'CRTD')->count();
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

        // Terapkan filter berdasarkan status dari request
        match ($status) {
            'outgoing'    => $query->whereDate('GSTRP', $today),
            'on-schedule' => $query->whereDate('GLTRP', '>=', $today),
            'overdue'     => $query->whereDate('GLTRP', '<', $today),
            'created'     => $query->where('STATS', 'CRTD'),
            default       => null, // 'all' atau status tidak valid tidak perlu filter tambahan
        };

        $pros = $query->orderBy('AUFNR', 'desc')->get();


        // Kembalikan HANYA bagian tabelnya saja (bukan seluruh halaman)
        return view('Features.partials.pro-table', ['pros' => $pros]);
    }

    public function showByBuyer(string $kode, string $buyerName, ?string $status = null): View
    {
  
        $today = Carbon::today();
        $query = ProductionTData3::where('WERKSX', $kode)->where('NAME1', $buyerName);

        $allProForBuyer = $query->clone()->get();
        $totalPro = $allProForBuyer->count();
        $totalOrderQuantity = $allProForBuyer->sum('PSMNG');
        $totalCompletedQuantity = $allProForBuyer->sum('WEMNG');


        if ($totalOrderQuantity > 0) {
            $rawCompletionRate = ($totalCompletedQuantity / $totalOrderQuantity) * 100;
            // Format menjadi string dengan 1 angka desimal dan tanda persen
            $completionRate = number_format($rawCompletionRate, 1) . '%';
        } else {
            $completionRate = '0.0%';
        }

        $query->when($status, function ($q, $status) use ($today) {
            $todayString = $today->toDateString();
            if ($status === 'on-schedule') {
                return $q->whereDate('GLTRP', '>=', $todayString);
            }
            if ($status === 'overdue') {
                return $q->whereDate('GLTRP', '<', $todayString);
            }
            if ($status === 'created') {
                return $q->where('STATS', 'CRTD');
            }
            if ($status === 'complete') {
                return $q->whereIn('STATS', ['TECO', 'DLV']);
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
            } elseif ($deadline->isPast() && !$deadline->isToday()) {
                $pro->status_text = 'Overdue';
                $pro->status_class = 'overdue';
                // Hitung hari keterlambatan
                $pro->overdue_days = $deadline->diffInDays($today); 
            } else {
                $pro->status_text = 'On Schedule';
                $pro->status_class = 'on-schedule';
            }
            
            // Kalkulasi lain yang dibutuhkan di view
            $pro->progress_percentage = ($pro->PSMNG > 0) ? ($pro->WEMNG / $pro->PSMNG) * 100 : 0;
            $pro->formatted_deadline = $deadline->format('d M');
            
            return $pro;
        });

        // 6. Kirim semua data yang sudah siap ke view
        return view('Features.pro-details', [ // Pastikan nama view Anda benar
            'buyerName' => $buyerName,
            'totalPro' => $totalPro,
            'totalOrderQuantity' => $totalOrderQuantity,
            'completionRate' => $completionRate,
            'proList' => $proList,
            'activeKode' => $kode,
        ]);
    }
}
