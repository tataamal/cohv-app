<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\ProductionTData;
use App\Models\ProductionTData1;
use App\Models\ProductionTData2;
use App\Models\ProductionTData3;
use App\Models\ProductionTData4;
use App\Models\MRP;
use App\Models\Gr;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class ManufactController extends Controller
{
    public function DetailData2(string $kode)
    {
        set_time_limit(0);

        try {
            $response = Http::timeout(0)->withHeaders([
                'X-SAP-Username' => session('username'),
                'X-SAP-Password' => session('password'),
            ])->get('http://127.0.0.1:8006/api/sap_combined', [
                'plant' => $kode
            ]);

            if (!$response->successful()) {
                return back()->with('error', 'Gagal mengambil data dari SAP.');
            }
            $payload = $response->json();
            //  dd($payload);

            // helper tanggal (SAP Ymd -> d-m-Y) untuk tampilan jika diperlukan
            $formatTanggal = function ($tgl) {
                if (empty($tgl)) return null;
                try { return Carbon::createFromFormat('Ymd', $tgl)->format('d-m-Y'); }
                catch (\Exception $e) { return $tgl; }
            };

            // ========================
            // 1) Normalisasi payload
            // ========================
            $T_DATA = $T1 = $T2 = $T3 = $T4 = [];

            if (isset($payload['results']) && is_array($payload['results'])) {
                foreach ($payload['results'] as $res) {
                    foreach (($res['T_DATA']  ?? []) as $r) $T_DATA[] = $r;
                    foreach (($res['T_DATA1'] ?? []) as $r) $T1[]     = $r;
                    foreach (($res['T_DATA2'] ?? []) as $r) $T2[]     = $r;
                    foreach (($res['T_DATA3'] ?? []) as $r) $T3[]     = $r;
                    foreach (($res['T_DATA4'] ?? []) as $r) $T4[]     = $r;
                }
            } else {
                $T_DATA = $payload['T_DATA']  ?? [];
                $T1     = $payload['T_DATA1'] ?? [];
                $T2     = $payload['T_DATA2'] ?? [];
                $T3     = $payload['T_DATA3'] ?? [];
                $T4     = $payload['T_DATA4'] ?? [];

                // jika salah satu datang sebagai objek tunggal -> bungkus jadi array
                if (!empty($T_DATA) && Arr::isAssoc($T_DATA)) $T_DATA = [$T_DATA];
                if (!empty($T1)     && Arr::isAssoc($T1))     $T1     = [$T1];
                if (!empty($T2)     && Arr::isAssoc($T2))     $T2     = [$T2];
                if (!empty($T3)     && Arr::isAssoc($T3))     $T3     = [$T3];
                if (!empty($T4)     && Arr::isAssoc($T4))     $T4     = [$T4];
            }

            // ===============================================
            // 2) HAPUS SEMUA DATA LAMA BERDASARKAN WERKSX
            // ===============================================
            
            // Hapus semua data yang ada untuk plant ini
            ProductionTData::where('WERKSX', $kode)->delete();
            ProductionTData1::where('WERKSX', $kode)->delete();
            ProductionTData2::where('WERKSX', $kode)->delete();
            ProductionTData3::where('WERKSX', $kode)->delete();
            ProductionTData4::where('WERKSX', $kode)->delete();
            
            // Untuk T_DATA4, jika ada relasi dengan plant tertentu
            // Jika tidak ada kolom WERKSX di T_DATA4, Anda perlu menyesuaikan logic ini
            // ProductionTData4::where('WERKSX', $kode)->delete();

            // ===============================================
            // 3) INSERT DATA BARU DARI SAP
            // ===============================================

            // == T_DATA (BARU) — Insert data baru
            foreach ($T_DATA as $row) {
                $row['WERKSX'] = $kode;
                if (empty($row['EDATU'])) $row['EDATU'] = null;

                // normalisasi kunci
                $kunnr = trim((string)($row['KUNNR'] ?? ''));
                $name1 = trim((string)($row['NAME1'] ?? ''));

                // jika dua-duanya kosong, skip agar tidak ada baris tanpa kunci
                if ($kunnr === '' && $name1 === '') {
                    Log::warning('Skip T_DATA tanpa kunci (KUNNR & NAME1 kosong)', ['row' => $row]);
                    continue;
                }

                // pastikan field kunci ikut tersimpan sesuai normalisasi
                $row['KUNNR'] = $kunnr !== '' ? $kunnr : null;
                $row['NAME1'] = $name1 !== '' ? $name1 : null;

                try {
                    ProductionTData::create($row);
                } catch (\Exception $e) {
                    Log::warning('Gagal simpan T_DATA', ['row' => $row, 'error' => $e->getMessage()]);
                }
            }

            // == T_DATA1 — Insert data baru
            foreach ($T1 as $row) {
                $orderx = $row['ORDERX'] ?? null;
                $vornr  = $row['VORNR'] ?? null;
                if (!$orderx) continue;

                $sssl1 = $formatTanggal($row['SSSLDPV1'] ?? '');
                $sssl2 = $formatTanggal($row['SSSLDPV2'] ?? '');
                $sssl3 = $formatTanggal($row['SSSLDPV3'] ?? '');

                $pv1 = (!empty($row['ARBPL1']) && !empty($sssl1)) ? strtoupper($row['ARBPL1'].' - '.$sssl1) : null;
                $pv2 = (!empty($row['ARBPL2']) && !empty($sssl2)) ? strtoupper($row['ARBPL2'].' - '.$sssl2) : null;
                $pv3 = (!empty($row['ARBPL3']) && !empty($sssl3)) ? strtoupper($row['ARBPL3'].' - '.$sssl3) : null;

                $row['WERKSX'] = $kode;

                try {
                    ProductionTData1::create(array_merge($row, ['PV1'=>$pv1, 'PV2'=>$pv2, 'PV3'=>$pv3]));
                } catch (\Exception $e) {
                    Log::warning('Gagal simpan T_DATA1', ['row' => $row, 'error' => $e->getMessage()]);
                }
            }

            // == T_DATA2 — Insert data baru
            foreach ($T2 as $row) {
                $row['WERKSX'] = $kode;
                if (empty($row['EDATU'])) $row['EDATU'] = null;

                try {
                    ProductionTData2::create($row);
                } catch (\Exception $e) {
                    Log::warning('Gagal simpan T_DATA2', ['row'=>$row, 'error'=>$e->getMessage()]);
                }
            }

            // == T_DATA3 — Insert data baru
            foreach ($T3 as $row) {
                if (!isset($row['ORDERX'])) continue;
                $row['WERKSX'] = $kode;

                try {
                    ProductionTData3::create($row);
                } catch (\Exception $e) {
                    Log::warning('Gagal simpan T_DATA3', ['row' => $row, 'error' => $e->getMessage()]);
                }
            }

            // == T_DATA4 — Insert data baru
            // Catatan: Jika T_DATA4 tidak memiliki kolom WERKSX, 
            // Anda perlu menyesuaikan logic ini atau menambahkan kolom WERKSX
            foreach ($T4 as $row) {
                if (!isset($row['RSNUM']) || !isset($row['RSPOS'])) continue;
                
                // Uncomment jika T_DATA4 memiliki kolom WERKSX
                // $row['WERKSX'] = $kode;

                try {
                    ProductionTData4::create($row);
                } catch (\Exception $e) {
                    Log::warning('Gagal simpan T_DATA4', ['row' => $row, 'error' => $e->getMessage()]);
                }
            }

            return redirect()->route('dashboard.show', $kode)
                ->with('success', 'Data berhasil disinkronkan.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal sinkronisasi: ' . $e->getMessage());
        }
    }

    public function showDetail(Request $request, $kode)
    {
        $kodeInfo = Kode::where('kode', $kode)->firstOrFail();

        // Sumber utama: T_DATA (bukan T_DATA2)
        $query = ProductionTData::where('WERKSX', $kode);

        $search = $request->input('search');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('KDAUF', 'like', "%{$search}%")
                ->orWhere('KDPOS', 'like', "%{$search}%")
                ->orWhere('MATNR', 'like', "%{$search}%")
                ->orWhere('MAKTX', 'like', "%{$search}%")
                ->orWhere('KUNNR', 'like', "%{$search}%")
                ->orWhere('NAME1', 'like', "%{$search}%");
            });
        }

        $tdata = $query->orderBy('KDAUF')->orderBy('KDPOS')->get();

        // Kunci untuk data terkait
        $kdaufValues = $tdata->pluck('KDAUF')->filter()->unique();
        $kdposValues = $tdata->pluck('KDPOS')->filter()->unique();

        // === T_DATA2 untuk SO-Item pada halaman ini ===
        $allTData2 = ProductionTData2::where('WERKSX', $kode)
            ->when($kdaufValues->isNotEmpty(), fn($q) => $q->whereIn('KDAUF', $kdaufValues))
            ->when($kdposValues->isNotEmpty(), fn($q) => $q->whereIn('KDPOS', $kdposValues))
            ->get()
            ->groupBy(fn($r) => ($r->KDAUF ?? '').'-'.($r->KDPOS ?? ''));

        // === T_DATA3 terkait (seperti sebelumnya) ===
        $allTData3 = ProductionTData3::where('WERKSX', $kode)
            ->when($kdaufValues->isNotEmpty(), fn($q) => $q->whereIn('KDAUF', $kdaufValues))
            ->when($kdposValues->isNotEmpty(), fn($q) => $q->whereIn('KDPOS', $kdposValues))
            ->get();

        $orderxVornrKeys = $allTData3
            ->map(fn($it) => ($it->ORDERX ?? '').'-'.($it->VORNR ?? ''))
            ->filter()->unique();

        $aufnrValues = $allTData3->pluck('AUFNR')->filter()->unique();
        $plnumValues = $allTData3->pluck('PLNUM')->filter()->unique();

        $allTData1Query = ProductionTData1::query();
        if (Schema::hasColumn((new ProductionTData1)->getTable(), 'WERKSX')) {
            $allTData1Query->where('WERKSX', $kode);
        }
        if ($orderxVornrKeys->isNotEmpty()) {
            $allTData1Query->whereIn(DB::raw("CONCAT(ORDERX, '-', VORNR)"), $orderxVornrKeys->values());
        } else {
            $allTData1Query->whereRaw('1=0');
        }
        $allTData1 = $allTData1Query->get()
            ->groupBy(fn($it) => ($it->ORDERX ?? '').'-'.($it->VORNR ?? ''));

        $allTData4ByAufnr = ProductionTData4::whereIn('AUFNR', $aufnrValues->values())->get()->groupBy('AUFNR');
        $allTData4ByPlnum = ProductionTData4::whereIn('PLNUM', $plnumValues->values())->get()->groupBy('PLNUM');

        $allTData3Grouped = $allTData3->groupBy(fn($it) => ($it->KDAUF ?? '').'-'.($it->KDPOS ?? ''));

        return view('Admin.detail-data2', [
            'plant'            => $kode,
            'categories'       => $kodeInfo->kategori,
            'bagian'           => $kodeInfo->nama_bagian,
            'tdata'            => $tdata,                 // tabel utama = T_DATA
            'allTData2'        => $allTData2,            // <-- baru
            'allTData3'        => $allTData3Grouped,
            'allTData1'        => $allTData1,
            'allTData4ByAufnr' => $allTData4ByAufnr,
            'allTData4ByPlnum' => $allTData4ByPlnum,
            'search'           => $search,
        ]);
    }

    public function convertPlannedOrder(Request $request)
    {
        // dd($request);
        // 1. Validasi input dari frontend
        $validated = $request->validate([
            'PLANNED_ORDER' => 'required|string',
            'AUART' => 'required|string',
            // 'PLANT' => 'required|string',
        ]);
        // 2. Tentukan URL API Python Anda
        try {
            // 3. Kirim request POST ke API Python menggunakan Laravel HTTP Client
            $response = Http::timeout(0)->withHeaders([
                'X-SAP-Username' => session('username'),
                'X-SAP-Password' => session('password'),
            ])->post('http://127.0.0.1:8006/api/create_prod_order',[
                'PLANNED_ORDER' => $validated['PLANNED_ORDER'],
                'AUART' => $validated['AUART'],
                'PLANT' => $request['PLANT'],
            ]);
            // 4. Periksa jika request ke Python gagal
            if ($response->failed()) {
                // Jika gagal, kirim pesan error ke frontend
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal terhubung ke service SAP/Python.',
                    'details' => $response->body() // Opsional: kirim detail error
                ], 502); // 502 Bad Gateway adalah status yang tepat untuk proxy error
            }
            // 5. Jika berhasil, teruskan respons asli dari Python ke frontend
            return $response->json();
        } catch (\Exception $e) {
            // Menangani error jika server Python tidak bisa dihubungi sama sekali
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menjangkau server SAP/Python.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function list_gr($kode)
    {
        // ... (Logika untuk mencari $kodeModel dan $mrpList tetap sama) ...
        $kodeModel = Kode::where('kode', $kode)->first();
        if (!$kodeModel) {
            return view('Admin.list-gr', [
                'kode' => $kode,
                'dataGr' => collect(),
                'processedData' => collect(),
                'error' => 'Kode "' . $kode . '" tidak ditemukan.'
            ]);
        }
        $mrpList = Mrp::where('kode_id', $kodeModel->id)->pluck('mrp');
        if ($mrpList->isEmpty()) {
            return view('Admin.list-gr', [
                'kode' => $kode,
                'dataGr' => collect(),
                'processedData' => collect(),
                'error' => 'Tidak ada MRP yang terhubung dengan kode ' . $kode
            ]);
        }

        // [PERBAIKAN 1] Ambil data mentah untuk tabel utama
        $dataGr = Gr::whereIn('DISPO', $mrpList)
            ->select(
                'AUFNR', 'MAKTX', 'KDAUF', 'KDPOS', 'PSMNG',
                'MENGE', 'MEINS', 'BUDAT_MKPF', 'DISPO'
            )
            ->orderBy('BUDAT_MKPF', 'desc')
            ->get();

        // Logika untuk mengolah data kalender (tetap sama)
        $groupedByDate = $dataGr->groupBy('BUDAT_MKPF');
        $processedData = $groupedByDate->mapWithKeys(function ($dailyRecords, $date) {
            return [
                $date => [
                    'total_gr' => $dailyRecords->count(),
                    'mrp_breakdown' => $dailyRecords->groupBy('DISPO')->map->count(),
                    'records' => $dailyRecords
                ]
            ];
        });
        
        // [PERBAIKAN 2] Kirim KEDUA variabel ke view
        return view('Admin.list-gr', [
            'kode'           => $kode,
            'dataGr'         => $dataGr, // <-- Untuk tabel utama
            'processedData'  => $processedData // <-- Untuk kalender & modal
        ]);
    }
}
