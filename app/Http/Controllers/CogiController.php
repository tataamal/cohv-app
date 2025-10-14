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
        $targetWerk = match (true) {
            in_array($kode, ['1001', '1002', '1003']) => '1001',
            $kode == '1200' => '1200',
            str_starts_with($kode, '20') => '2000',
            str_starts_with($kode, '30') => '3000',
            default => $kode
        };
        $kodeModel = Kode::where('kode', $kode)->first();
        $mrpList = $kodeModel ? $kodeModel->mrps()->pluck('mrp')->toArray() : [];
        $baseQuery = Cogi::query();
        $baseQuery->where('DWERK', $targetWerk);

        if (!empty($mrpList)) {
            $baseQuery->whereIn('DISPOH', $mrpList);
        }

        $queryForCards = clone $baseQuery;
        
        $totalError = $queryForCards->count();
        $errorBaru = (clone $queryForCards)->where('BUDAT', '<', today()->subDays(7))->count();
        $errorLama = (clone $queryForCards)->where('BUDAT', '>', today()->subDays(7))->count();
        
        $filter = $request->query('filter');
        
        $queryForTable = clone $baseQuery;

        if ($filter === 'baru') {
            $queryForTable->where('BUDAT', '<', today()->subDays(7));
        } elseif ($filter === 'lama') {
            $queryForTable->where('BUDAT', '>', today()->subDays(7));
        }

        $cogiData = $queryForTable->latest('BUDAT')->get();
        
        $kategori = Kode::where('kode', $kode)->value('kategori');
        $sub_kategori = Kode::where('kode', $kode)->value('sub_kategori');
        $nama_bagian = Kode::where('kode', $kode)->value('nama_bagian');

        return view('Features.monitoring-cogi', compact(
            'kode',
            'cogiData',
            'totalError',
            'errorBaru',
            'errorLama',
            'filter',
            'nama_bagian',
            'sub_kategori',
            'kategori',
        ));
    }
}

