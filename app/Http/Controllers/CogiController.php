<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Models\Cogi;
use App\Models\KodeLaravel;
use App\Models\MRP;
use Carbon\Carbon;

class CogiController extends Controller
{
    public function index(Request $request, $kode)
    {
        $targetWerk = match (true) {
            in_array($kode, ['1001', '1002', '1003']) => '1001',
            $kode == '1200' => '1200',
            str_starts_with($kode, '10') => '1000',
            str_starts_with($kode, '20') => '2000',
            str_starts_with($kode, '30') => '3000',
            default => $kode
        };

        $kodeModels = KodeLaravel::where('laravel_code', $kode)->get();
        $mrpList = [];
        $kategori = null;
        $sub_kategori = null;
        $nama_bagian = null;
        
        if ($kodeModels->isNotEmpty()) {
            
            $kodeLaravelIds = $kodeModels->pluck('id');
            
            // Ambil mrp_id dari MappingTable
            $mrpIds = \App\Models\MappingTable::whereIn('kode_laravel_id', $kodeLaravelIds)
                        ->whereNotNull('mrp_id')
                        ->pluck('mrp_id');

            // Ambil kode MRP sebenarnya (string) dari tabel MRP
            $mrpList = MRP::whereIn('id', $mrpIds)
                            ->pluck('mrp')
                            ->unique()
                            ->toArray();

            $firstKodeModel = $kodeModels->first();
            $kategori = $firstKodeModel->plant;
            $sub_kategori = $firstKodeModel->description;
            $nama_bagian = $firstKodeModel->description;
        }

        $baseQuery = Cogi::query();
        $baseQuery->where('DWERK', $targetWerk);

        if (!empty($mrpList)) {
            $baseQuery->whereIn('DISPOH', $mrpList);
        }

        $queryForCards = clone $baseQuery;
        $totalError = $queryForCards->count();
        $errorBaru = (clone $queryForCards)->where('BUDAT', '>=', today()->subDays(7))->count();
        $errorLama = (clone $queryForCards)->where('BUDAT', '<', today()->subDays(7))->count();
        
        $filter = $request->query('filter');
        $queryForTable = clone $baseQuery;

        if ($filter === 'baru') {
            $queryForTable->where('BUDAT', '>=', today()->subDays(7));
        } elseif ($filter === 'lama') {
            $queryForTable->where('BUDAT', '<', today()->subDays(7));
        }

        $cogiData = $queryForTable->latest('BUDAT')->get();
        try {
            Log::info('--- DATA MONITORING COGI UNTUK VIEW ---', [
                'kode' => $kode,
                'cogiData_count' => $cogiData->count(),
                'totalError' => $totalError,
                'errorBaru' => $errorBaru,
                'errorLama' => $errorLama,
                'filter' => $filter,
                'nama_bagian' => $nama_bagian,
                'sub_kategori' => $sub_kategori,
                'kategori' => $kategori,
                'mrpList' => $mrpList,
                'targetWerk' => $targetWerk
            ]);
            Log::info('Detail CogiData:', $cogiData->toArray());
            
        } catch (\Exception $e) {
            Log::error('GAGAL MELAKUKAN LOGGING: ' . $e->getMessage());
        }

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
    public function syncCogiData(Request $request): JsonResponse
    {
        // [LANGKAH 1] Tentukan URL API Flask (ambil dari .env)
        $flaskApiUrl = env('FLASK_API_URL') . '/api/cogi/sync';

        try {
            // [LANGKAH 2] Ambil kredensial SAP dari session Laravel
            $sapUser = session('username');
            $sapPass = session('password');

            if (!$sapUser || !$sapPass) {
                return response()->json(['message' => 'Kredensial SAP tidak ditemukan di session Anda.'], 401);
            }

            Log::info('Memulai sinkronisasi COGI: Memanggil API Flask...');

            // [LANGKAH 3] Panggil API Flask (Python)
            $response = Http::withHeaders([
                'X-SAP-Username' => $sapUser,
                'X-SAP-Password' => $sapPass,
                'Accept' => 'application/json',
            ])
            ->timeout(300) // Beri waktu 5 menit untuk SAP
            ->post($flaskApiUrl);

            if (!$response->successful()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error'] ?? 'Gagal mengambil data dari server Flask.';
                throw new \Exception($errorMessage);
            }

            // [LANGKAH 4] Ambil data mentah dari respons Flask
            $cogiData = $response->json();
            $all_cogi_data = $cogiData['data'] ?? null;

            if (is_null($all_cogi_data)) {
                throw new \Exception('Respon dari Flask tidak valid (tidak ada key "data").');
            }

            $saved_count = count($all_cogi_data);
            Log::info("Berhasil mengambil {$saved_count} baris data dari Flask. Memulai penyimpanan ke DB...");

            // [LANGKAH 5] Siapkan data untuk insert
            $data_to_insert = [];
            $now = Carbon::now();

            foreach ($all_cogi_data as $d) {
                $data_to_insert[] = [
                    'MANDT' => $d['MANDT'] ?? null,
                    'AUFNR' => $d['AUFNR'] ?? null,
                    'RSNUM' => $d['RSNUM'] ?? null,
                    'BUDAT' => (empty($d['BUDAT']) || $d['BUDAT'] == '0000-00-00') ? null : $d['BUDAT'],
                    'KDAUF' => $d['KDAUF'] ?? null,
                    'KDPOS' => $d['KDPOS'] ?? null,
                    'DWERK' => $d['DWERK'] ?? null,
                    'MATNRH' => $d['MATNRH'] ?? null,
                    'MAKTXH' => $d['MAKTXH'] ?? null,
                    'DISPOH' => $d['DISPOH'] ?? null,
                    'PSMNG' => $d['PSMNG'] ?? null,
                    'WEMNG' => $d['WEMNG'] ?? null,
                    'MATNR' => $d['MATNR'] ?? null,
                    'MAKTX' => $d['MAKTX'] ?? null,
                    'DISPO' => $d['DISPO'] ?? null,
                    'ERFMG' => $d['ERFMG'] ?? null,
                    'AUFNRX' => $d['AUFNRX'] ?? null,
                    'P1' => $d['P1'] ?? null,
                    'PW' => $d['PW'] ?? null,
                    'MENGE' => $d['MENGE'] ?? null,
                    'MEINS' => $d['MEINS'] ?? null,
                    'LGORTH' => $d['LGORTH'] ?? null,
                    'LGORT' => $d['LGORT'] ?? null,
                    'DEVISI' => $d['DEVISI'] ?? null,
                    'TYPMAT' => $d['TYPMAT'] ?? null,
                    'PESAN_ERROR' => $d['PESAN_ERROR'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // [LANGKAH 6] Simpan ke Database (Logika Inti)
            DB::transaction(function () use ($data_to_insert) {

                Cogi::query()->delete();
                foreach (array_chunk($data_to_insert, 500) as $chunk) {
                    Cogi::insert($chunk);
                }
            });
            
            Log::info("Berhasil menyimpan $saved_count baris ke tb_cogi.");

            // [LANGKAH 7] Kirim respons sukses ke frontend
            return response()->json([
                'message' => "Sinkronisasi berhasil. {$saved_count} baris data telah diperbarui."
            ]);

        } catch (ConnectionException $e) {
            // Error jika server Python mati
            Log::error('Sinkronisasi COGI gagal: Gagal terhubung ke server Python/Flask. ' . $e->getMessage());
            return response()->json(['message' => 'Gagal terhubung ke server sinkronisasi. Pastikan layanan Python berjalan.'], 503); // 503 Service Unavailable
        
        } catch (\Illuminate\Database\QueryException $e) {
            // Error saat menyimpan ke DB
            Log::error('Sinkronisasi COGI gagal: Error Database. ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menyimpan data ke database: ' . $e->getMessage()], 500);
        
        } catch (\Exception $e) {
            // Error lainnya (misal: Flask kirim 401, 500, atau $all_cogi_data null)
            Log::error('Sinkronisasi COGI gagal (Error Umum): ' . $e->getMessage());
            return response()->json(['message' => 'Sinkronisasi gagal: ' . $e->getMessage()], 500);
        }
    }
    public function getCogiDetails(Request $request, $plantCode): JsonResponse
    {
        try {
            $query = Cogi::ofPlant($plantCode)
                ->select(
                    'id',
                    'AUFNR',
                    'RSNUM',
                    'MATNR',
                    'MAKTX',
                    'DISPO', 
                    'LGORT',
                    'MEINS',
                    'ERFMG',
                    'TYPMAT',
                    'DEVISI', 
                    'BUDAT'
                )
                ->orderBy('BUDAT', 'desc');

            $data = $query->get();

            return response()->json([
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            Log::error("Gagal mengambil detail Cogi plant {$plantCode}: " . $e->getMessage());
            return response()->json(['message' => 'Gagal mengambil data detail.'], 500);
        }
    }

    public function getDashboardData(Request $request): JsonResponse
    {
        $plants = ['1000', '1001', '2000', '3000'];
        $plantNames = [
            '1000' => 'Plant 1000 (Sby)',
            '1001' => 'Plant 1001 (Sby)',
            '2000' => 'Plant 2000 (Sby)',
            '3000' => 'Plant 3000 (Smg)',
        ];

        try {
            Log::info('--- MENJALANKAN KODE BARU v6 (Perbaikan TYPMAT) ---');

            // --- 1. Query untuk Ranking Plant (Sudah Benar) ---
            $cogiCounts = Cogi::select('DWERK', DB::raw('COUNT(*) as total_cogi'))
                ->whereIn('DWERK', $plants)
                ->groupBy('DWERK')
                ->pluck('total_cogi', 'DWERK');
            
            $plantValues = collect($plants)->mapWithKeys(function ($plant) use ($cogiCounts) {
                return [$plant => $cogiCounts->get($plant, 0)];
            });
            $sortedPlants = $plantValues->sortDesc();
            $summaryRanking = $sortedPlants->map(function ($value, $plantCode) use ($plantNames) {
                return [
                    'plant_code' => $plantCode,
                    'plant_name' => $plantNames[$plantCode] ?? 'N/A',
                    'value' => $value,
                ];
            })->values()->toArray();

            // --- 2. Query untuk Data Chart (Sudah Benar) ---
            $allCogiForCharts = Cogi::select('DWERK', 'DEVISI')
                ->whereIn('DWERK', $plants)
                ->get();

            $chartData = collect($plants)->mapWithKeys(function ($plant) {
                return [$plant => ['labels' => [], 'values' => []]];
            })->toArray(); // ->toArray() sudah benar

            $groupedByPlant = $allCogiForCharts->map(function ($item) {
                return [
                    'DWERK_string'  => (string) $item->DWERK,
                    'division_name' => (!empty($item->DEVISI)) ? $item->DEVISI : 'Others DEVISI'
                ];
            })
            ->groupBy('DWERK_string');

            $allDivisionsSet = [];
            foreach ($groupedByPlant as $plantCode => $itemsInPlant) {
                if (isset($chartData[$plantCode])) {
                    $divisionCounts = $itemsInPlant->countBy('division_name')->sortDesc();
                    foreach ($divisionCounts as $divisionName => $count) {
                        $chartData[$plantCode]['labels'][] = $divisionName;
                        $chartData[$plantCode]['values'][] = $count;
                        $allDivisionsSet[$divisionName] = true;
                    }
                }
            }
            $allDivisions = collect(array_keys($allDivisionsSet))->sort()->values();

            // --- 3. Query untuk TYPMAT ---
            $allCogiForTypmat = Cogi::select('DWERK', 'TYPMAT')
                ->whereIn('DWERK', $plants)
                ->get();

            // --- [PERBAIKAN KUNCI DI SINI] ---
            // Inisialisasi data TYPMAT dan tambahkan ->toArray()
            $typmatData = collect($plants)->mapWithKeys(function ($plant) {
                return [$plant => []];
            })
            ->toArray(); // <-- INI PERBAIKANNYA

            // Proses di PHP
            $groupedTypmat = $allCogiForTypmat->map(function ($item) {
                return [
                    'DWERK_string' => (string) $item->DWERK,
                    'typmat_name'  => (!empty($item->TYPMAT)) ? $item->TYPMAT : 'Others'
                ];
            })
            ->groupBy('DWERK_string');

            // Kode ini sekarang AMAN karena $typmatData adalah Array
            foreach ($groupedTypmat as $plantCode => $items) {
                if (isset($typmatData[$plantCode])) {
                    $typmatCounts = $items->countBy('typmat_name')->sortDesc();
                    foreach ($typmatCounts as $name => $count) {
                        $typmatData[$plantCode][] = ['name' => $name, 'value' => $count];
                    }
                }
            }
            // --- [AKHIR PERBAIKAN] ---

            // --- 4. Menyusun data JSON final ---
            $data = [
                'summary_ranking' => $summaryRanking,
                'chart_data'      => $chartData,
                'division_filter_options' => $allDivisions,
                'typmat_summary'  => $typmatData,
            ];
            
            Log::info('Berhasil membentuk data dashboard v6');
            return response()->json($data);

        } catch (\Exception $e) {
            Log::error('Gagal mengambil data dashboard COGI v6: ' . $e->getMessage(), [
                'trace'   => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            
            return response()->json([
                'message' => 'Gagal memuat data dashboard. Terjadi error internal.',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }
}

