<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionTData4;
use App\Models\Kode;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class OutstandingReservasiController extends Controller
{
    public function index($kode) 
    {
        $kodeModel = Kode::where('kode', $kode)->first();
        if (!$kodeModel) {
            abort(404, 'Kode Bagian/Plant tidak ditemukan.');
        }
        $kategori_header = $kodeModel->kategori;
        $sub_kategori_header = $kodeModel->sub_kategori;
        $nama_bagian_header = $kodeModel->nama_bagian;
        try {
            $outstandingData = DB::table('production_t_data4')
                ->join('mrps', 'production_t_data4.DISPO', '=', 'mrps.mrp')
                ->join('kodes', 'mrps.kode_id', '=', 'kodes.id') 
                ->select(
                    'production_t_data4.RSNUM',
                    'production_t_data4.RSPOS',
                    'production_t_data4.MATNR',
                    'production_t_data4.MAKTX',
                    'production_t_data4.OUTSREQ',
                    'production_t_data4.MEINS',
                    'production_t_data4.DISPO',
                    'kodes.nama_bagian as nama_bagian',
                    'kodes.kategori as kategori',
                    'kodes.sub_kategori',
                )
                ->where('production_t_data4.OUTSREQ', '>', 0)
                ->where('production_t_data4.WERKS', $kode) 
                ->orderBy('production_t_data4.RSNUM', 'asc')
                ->get();

        } catch (\Exception $e) {
            logger()->error('Gagal mengambil data outstanding reservasi: ' . $e->getMessage());
            $outstandingData = collect(); 
        }
        $groupedData = $outstandingData->groupBy('DISPO');

        // PERUBAHAN 5: Kirim semua data (groupedData + data header) ke view
        return view("outstanding-reservasi.index", [
            'groupedData'   => $groupedData,
            'kode'          => $kode,
            'nama_bagian'   => $nama_bagian_header,
            'sub_kategori'  => $sub_kategori_header,
            'kategori'      => $kategori_header,
        ]);
    }
}
