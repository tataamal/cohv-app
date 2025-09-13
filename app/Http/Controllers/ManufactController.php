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
        Log::info("==================================================");
        Log::info("Memulai proses sinkronisasi untuk Plant: {$kode}");
        Log::info("==================================================");

        try {
            // 1. Ambil data dari API SAP (Sama seperti kedua versi)
            Log::info("[Langkah 1/5] Mengambil data dari API SAP...");
            $response = Http::timeout(0)->withHeaders([
                'X-SAP-Username' => session('username'),
                'X-SAP-Password' => session('password'),
            ])->get('http://127.0.0.1:8050/api/sap_combined', ['plant' => $kode]);

            if (!$response->successful()) {
                Log::error("Gagal mengambil data dari SAP. Status: " . $response->status());
                return back()->with('error', 'Gagal mengambil data dari SAP.');
            }
            $payload = $response->json();
            Log::info(" -> Data dari SAP berhasil diterima.");

            // Helper untuk memformat tanggal dengan aman
            $formatTanggal = function ($tgl) {
                if (empty($tgl) || trim($tgl) === '00000000') return null;
                try { return Carbon::createFromFormat('Ymd', $tgl)->format('d-m-Y'); }
                catch (\Exception $e) { return null; }
            };
            
            // Memulai transaksi database
            DB::transaction(function () use ($payload, $kode, $formatTanggal) {
                
                // 2. Ekstrak dan Ratakan Data dari Payload (Logika dari Controller Lama)
                Log::info("[Langkah 2/5] Mengekstrak dan meratakan data payload...");
                $T_DATA = $T1 = $T2 = $T3 = $T4 = [];
                $dataBlocks = $payload['results'] ?? [$payload];

                foreach ($dataBlocks as $res) {
                    if (!empty($res['T_DATA']) && is_array($res['T_DATA']))   $T_DATA = array_merge($T_DATA, $res['T_DATA']);
                    if (!empty($res['T_DATA1']) && is_array($res['T_DATA1']))  $T1 = array_merge($T1, $res['T_DATA1']);
                    if (!empty($res['T_DATA2']) && is_array($res['T_DATA2']))  $T2 = array_merge($T2, $res['T_DATA2']);
                    if (!empty($res['T_DATA3']) && is_array($res['T_DATA3']))  $T3 = array_merge($T3, $res['T_DATA3']);
                    if (!empty($res['T_DATA4']) && is_array($res['T_DATA4']))  $T4 = array_merge($T4, $res['T_DATA4']);
                }
                Log::info(" -> Jumlah data mentah: T_DATA: " . count($T_DATA) . ", T1: " . count($T1) . ", T2: " . count($T2) . ", T3: " . count($T3) . ", T4: " . count($T4));

                // 3. Hapus Data Lama (Logika dari Controller Lama, lebih aman)
                Log::info("[Langkah 3/5] Menghapus data lama untuk Plant {$kode}...");
                ProductionTData4::where('WERKSX', $kode)->delete();
                ProductionTData1::where('WERKSX', $kode)->delete(); // Diubah dari LIKE menjadi =
                ProductionTData3::where('WERKSX', $kode)->delete();
                ProductionTData2::where('WERKSX', $kode)->delete();
                ProductionTData::where('WERKSX', $kode)->delete();
                Log::info(" -> Data lama berhasil dihapus.");

                // 4. Kelompokkan Data Anak untuk Relasi yang Efisien (Logika dari Controller Lama)
                Log::info("[Langkah 4/5] Mengelompokkan data anak berdasarkan kunci relasi...");
                
                // MENGGUNAKAN KUNCI BARU DARI CONTROLLER BARU UNTUK T_DATA2
                $t2_grouped = collect($T2)->groupBy(fn($item) => trim($item['KUNNR'] ?? '') . '-' . trim($item['NAME1'] ?? ''));
                $t3_grouped = collect($T3)->groupBy(fn($item) => trim($item['KDAUF'] ?? '') . '-' . trim($item['KDPOS'] ?? ''));
                
                // Kunci untuk T1 dan T4 tetap sama
                $t1_grouped = collect($T1)->groupBy(fn($item) => trim($item['AUFNR'] ?? ''));
                $t4_grouped = collect($T4)->groupBy(fn($item) => trim($item['AUFNR'] ?? ''));
                Log::info(" -> Data anak berhasil dikelompokkan.");

                // 5. Lakukan INSERT secara Berjenjang (Logika Utama dari Controller Lama)
                Log::info("[Langkah 5/5] Memulai proses penyisipan data berjenjang...");
                
                foreach ($T_DATA as $t_data_row) {
                    // Normalisasi dan validasi kunci dari Controller Baru
                    $kunnr = trim((string)($t_data_row['KUNNR'] ?? ''));
                    $name1 = trim((string)($t_data_row['NAME1'] ?? ''));
                    if ($kunnr === '' && $name1 === '') continue;

                    $t_data_row['KUNNR'] = $kunnr;
                    $t_data_row['NAME1'] = $name1;
                    $t_data_row['WERKSX'] = $kode;
                    $t_data_row['EDATU'] = (empty($t_data_row['EDATU']) || trim($t_data_row['EDATU']) === '00000000') ? null : $t_data_row['EDATU'];

                    $parentRecord = ProductionTData::create($t_data_row);
                    
                    // Cari anak TData2 MENGGUNAKAN KUNCI BARU
                    $key_t2 = $kunnr . '-' . $name1;
                    $children_t2 = $t2_grouped->get($key_t2, []);

                    foreach ($children_t2 as $t2_row) {
                        $t2_row['WERKSX'] = $kode;
                        $t2_row['EDATU'] = (empty($t2_row['EDATU']) || trim($t2_row['EDATU']) === '00000000') ? null : $t2_row['EDATU'];
                        // Pastikan KUNNR & NAME1 dari induk ikut tersimpan untuk konsistensi
                        $t2_row['KUNNR'] = $parentRecord->KUNNR;
                        $t2_row['NAME1'] = $parentRecord->NAME1;
                        
                        $t2Record = ProductionTData2::create($t2_row);
                        
                        // Proses anak TData3 (logika relasi lama)
                        $key_t3 = trim($t2Record->KDAUF ?? '') . '-' . trim($t2Record->KDPOS ?? '');
                        $children_t3 = $t3_grouped->get($key_t3, []);

                        foreach ($children_t3 as $t3_row) {
                            $t3_row['WERKSX'] = $kode;
                            $t3Record = ProductionTData3::create($t3_row);
                            
                            // Proses anak TData1 dan TData4 (logika relasi lama)
                            $key_t1_t4 = trim($t3Record->AUFNR ?? '');
                            if (empty($key_t1_t4)) continue;

                            $children_t1 = $t1_grouped->get($key_t1_t4, []);
                            $children_t4 = $t4_grouped->get($key_t1_t4, []);
                            
                            foreach ($children_t1 as $t1_row) {
                                $sssl1 = $formatTanggal($t1_row['SSSLDPV1'] ?? '');
                                $sssl2 = $formatTanggal($t1_row['SSSLDPV2'] ?? '');
                                $sssl3 = $formatTanggal($t1_row['SSSLDPV3'] ?? '');
                                
                                // Logika baru untuk PV1
                                $partsPv1 = [];
                                if (!empty($t1_row['ARBPL1'])) {
                                    $partsPv1[] = strtoupper($t1_row['ARBPL1']);
                                }
                                if (!empty($sssl1)) {
                                    $partsPv1[] = $sssl1;
                                }
                                $t1_row['PV1'] = !empty($partsPv1) ? implode(' - ', $partsPv1) : null;

                                // Logika baru untuk PV2
                                $partsPv2 = [];
                                if (!empty($t1_row['ARBPL2'])) {
                                    $partsPv2[] = strtoupper($t1_row['ARBPL2']);
                                }
                                if (!empty($sssl2)) {
                                    $partsPv2[] = $sssl2;
                                }
                                $t1_row['PV2'] = !empty($partsPv2) ? implode(' - ', $partsPv2) : null;

                                // Logika baru untuk PV3
                                $partsPv3 = [];
                                if (!empty($t1_row['ARBPL3'])) {
                                    $partsPv3[] = strtoupper($t1_row['ARBPL3']);
                                }
                                if (!empty($sssl3)) {
                                    $partsPv3[] = $sssl3;
                                }
                                $t1_row['PV3'] = !empty($partsPv3) ? implode(' - ', $partsPv3) : null;

                                // --- [PERUBAHAN SELESAI] ---
                                
                                $t1_row['WERKSX'] = $kode; // Perbaikan dari diskusi sebelumnya
                                ProductionTData1::create($t1_row);
                            }
                            
                            foreach ($children_t4 as $t4_row) {
                                // Penting: Pastikan T_DATA4 punya kolom WERKSX
                                $t4_row['WERKSX'] = $kode;
                                ProductionTData4::create($t4_row);
                            }
                        }
                    }
                }
                Log::info(" -> Proses penyisipan data berjenjang selesai.");
            });

            Log::info("================ SINKRONISASI BERHASIL ================");
            return redirect()->route('dashboard.show', $kode)->with('success', 'Data berhasil disinkronkan dengan relasi yang benar.');

        } catch (\Exception $e) {
            Log::error('Gagal sinkronisasi data: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            Log::info("================ SINKRONISASI GAGAL ================");
            return back()->with('error', 'Terjadi kesalahan saat sinkronisasi: ' . $e->getMessage());
        }
    }

    public function showDetail(Request $request, $kode)
    {
        $kodeInfo = Kode::where('kode', $kode)->firstOrFail();

        // 1. Ambil data utama (T_DATA) - Tidak ada perubahan
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

        // 2. Ambil T_DATA2 (Anak dari T_DATA)
        $kunnrValues = $tdata->pluck('KUNNR')->filter()->unique();
        
        // Ambil semua T_DATA2 yang berpotensi terkait dengan halaman ini
        $allTData2_flat = ProductionTData2::where('WERKSX', $kode)
            ->whereIn('KUNNR', $kunnrValues)
            ->get();

        // PERBAIKAN KUNCI: Kelompokkan T_DATA2 berdasarkan KUNNR dan NAME1 agar cocok dengan T_DATA
        $allTData2 = $allTData2_flat->groupBy(function ($r) {
            return trim($r->KUNNR ?? '') . '-' . trim($r->NAME1 ?? '');
        });

        // 3. Ambil T_DATA3 (Anak dari T_DATA2)
        // PERBAIKAN SUMBER: Ambil kunci dari $allTData2_flat, bukan dari $tdata
        $kdaufValues = $allTData2_flat->pluck('KDAUF')->filter()->unique();
        $kdposValues = $allTData2_flat->pluck('KDPOS')->filter()->unique();
        
        $allTData3_flat = ProductionTData3::where('WERKSX', $kode)
            ->when($kdaufValues->isNotEmpty(), fn($q) => $q->whereIn('KDAUF', $kdaufValues))
            ->when($kdposValues->isNotEmpty(), fn($q) => $q->whereIn('KDPOS', $kdposValues))
            ->get();

        // Kelompokkan T_DATA3 berdasarkan KDAUF dan KDPOS (ini sudah benar)
        $allTData3Grouped = $allTData3_flat->groupBy(function($it) {
            return trim($it->KDAUF ?? '') . '-' . trim($it->KDPOS ?? '');
        });

        // 4. Ambil T_DATA1 & T_DATA4 (Anak dari T_DATA3)
        // Logika ini sekarang akan menerima data yang benar karena sumbernya (allTData3_flat) sudah benar
        $aufnrValues = $allTData3_flat->pluck('AUFNR')->filter()->unique();
        $plnumValues = $allTData3_flat->pluck('PLNUM')->filter()->unique();
        $orderxVornrKeys = $allTData3_flat
            ->map(fn($it) => ($it->ORDERX ?? '').'-'.($it->VORNR ?? ''))
            ->filter()->unique();

        $allTData1 = ProductionTData1::query()
            ->whereIn(DB::raw("CONCAT(ORDERX, '-', VORNR)"), $orderxVornrKeys->values())
            ->get()
            ->groupBy(fn($it) => ($it->ORDERX ?? '').'-'.($it->VORNR ?? ''));

        $allTData4ByAufnr = ProductionTData4::whereIn('AUFNR', $aufnrValues->values())->get()->groupBy('AUFNR');
        $allTData4ByPlnum = ProductionTData4::whereIn('PLNUM', $plnumValues->values())->get()->groupBy('PLNUM');

        // 5. Kirim semua data yang sudah benar ke view
        return view('Admin.detail-data2', [
            'plant'            => $kode,
            'categories'       => $kodeInfo->kategori,
            'bagian'           => $kodeInfo->nama_bagian,
            'tdata'            => $tdata,
            'allTData2'        => $allTData2,
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
            ])->post('http://127.0.0.1:8050/api/create_prod_order',[
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

        $groupedByDate = $dataGr->groupBy(function($item) {
            return Carbon::parse($item->BUDAT_MKPF)->format('Y-m-d');
        });

        $calendarEvents = [];
        foreach ($groupedByDate as $date => $dailyRecords) {
            $totalPro = $dailyRecords->unique('AUFNR')->count();
            $totalGrCount = $dailyRecords->sum('MENGE');
            // Asumsi Value adalah SUM dari MENGE * (harga satuan). Sesuaikan ini.
            $totalValue = $dailyRecords->sum(function($item) {
                return $item->MENGE * 10; // Contoh: Asumsi harga satuan 10, ganti sesuai logika Anda
            });

            // Breakdown per DISPO
            $dispoBreakdown = $dailyRecords->groupBy('DISPO')->map(function ($items, $dispo) {
                return [
                    'dispo' => $dispo,
                    'gr_count' => $items->sum('MENGE')
                ];
            })->values()->all(); // Mengambil hanya value dan mengubahnya jadi array numerik objek

            $calendarEvents[] = [
                'title'   => '', // Biarkan kosong, kita akan render sepenuhnya dengan eventContent
                'start'   => $date,
                // 'color'   => '#6f42c1', // Hapus atau jadikan komentar baris ini agar warna default (putih/transparan)
                'details' => $dailyRecords->toArray(),

                'extendedProps' => [
                    'totalPro' => $totalPro,
                    'totalGrCount' => $totalGrCount,
                    'totalValue' => $totalValue,
                    'dispoBreakdown' => $dispoBreakdown,
                ]
            ];
        }

        return view('Admin.list-gr', [
            'kode'          => $kode,
            'dataGr'        => $dataGr,
            'processedData' => $calendarEvents
        ]);
    }
}
