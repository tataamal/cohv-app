<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cogi;
use App\Models\Kode;
use Illuminate\Support\Facades\DB;

class CogiController extends Controller
{
    public function index(Request $request, $kode)
    {
        // --- LOGIKA FILTER DWERK (Tetap sama) ---
        $targetWerk = match (true) {
            in_array($kode, ['1001', '1002', '1003']) => '1001',
            $kode == '1200' => '1200',
            str_starts_with($kode, '20') => '2000',
            str_starts_with($kode, '30') => '3000',
            default => $kode
        };

        // --- [BARU] LOGIKA FILTER BERDASARKAN MRP ---
        // 1. Cari objek Kode berdasarkan nilai 'kode' yang unik
        $kodeModel = Kode::where('kode', $kode)->first();

        // 2. Ambil semua nilai 'mrp' yang berelasi dengan Kode tersebut
        // pluck('mrp') akan membuat array berisi nilai MRP, contoh: ['F01', 'F02']
        $mrpList = $kodeModel ? $kodeModel->mrps()->pluck('mrp')->toArray() : [];

        // Buat query dasar
        $baseQuery = Cogi::query();

        // Terapkan filter DWERK
        $baseQuery->where('DWERK', $targetWerk);

        // 3. Jika ditemukan daftar MRP, tambahkan filter whereIn ke query
        if (!empty($mrpList)) {
            $baseQuery->whereIn('DISPOH', $mrpList);
        }

        // --- PENGOLAHAN DATA UNTUK CARD ---
        $queryForCards = clone $baseQuery;
        
        $totalError = $queryForCards->count();
        $errorBaru = (clone $queryForCards)->whereDate('BUDAT', today())->count();
        $errorLama = (clone $queryForCards)->where('BUDAT', '<', today()->subDays(7))->count();
        
        // --- PENGAMBILAN DATA UTAMA UNTUK TABEL ---
        $filter = $request->query('filter');
        
        $queryForTable = clone $baseQuery;

        if ($filter === 'baru') {
            $queryForTable->whereDate('BUDAT', today());
        } elseif ($filter === 'lama') {
            $queryForTable->where('BUDAT', '<', today()->subDays(7));
        }

        $cogiData = $queryForTable->latest('BUDAT')->get();

        return view('Features.monitoring-cogi', compact(
            'kode',
            'cogiData',
            'totalError',
            'errorBaru',
            'errorLama',
            'filter'
        ));
    }
}

