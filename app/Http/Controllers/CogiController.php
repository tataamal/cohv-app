<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cogi;
use Illuminate\Support\Facades\DB;

class CogiController extends Controller
{
    // Method sekarang menerima Request object dan $kode
    public function index(Request $request, $kode) // <-- Tambahkan Request $request
    {
        // --- LOGIKA FILTER DWERK ---
        $targetWerk = match (true) {
            in_array($kode, ['1001', '1002', '1003']) => '1001',
            $kode == '1200' => '1200',
            str_starts_with($kode, '20') => '2000',
            str_starts_with($kode, '30') => '3000',
            default => $kode
        };

        // --- PENGOLAHAN DATA UNTUK CARD (Selalu hitung total dari plant) ---
        $queryForCards = Cogi::where('DWERK', $targetWerk);

        $totalError = (clone $queryForCards)->count();
        $errorBaru = (clone $queryForCards)->whereDate('BUDAT', today())->count();
        $errorLama = (clone $queryForCards)->where('BUDAT', '<', today()->subDays(7))->count();

        // --- PENGAMBILAN DATA UTAMA UNTUK TABEL (dengan filter dari URL) ---
        $filter = $request->query('filter'); // Ambil parameter 'filter' dari URL

        $queryForTable = Cogi::where('DWERK', $targetWerk);

        if ($filter === 'baru') {
            $queryForTable->whereDate('BUDAT', today());
        } elseif ($filter === 'lama') {
            $queryForTable->where('BUDAT', '<', today()->subDays(7));
        }
        // Jika tidak ada filter, query tetap seperti semula (menampilkan semua)

        $cogiData = $queryForTable->latest('BUDAT')->get();

        return view('Features.monitoring-cogi', compact(
            'kode',
            'cogiData',
            'totalError',
            'errorBaru',
            'errorLama',
            'filter' // Kirim variabel filter ke view untuk menandai kartu aktif
        ));
    }
}

