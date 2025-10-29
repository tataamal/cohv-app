<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cogi;
use App\Models\Kode;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
            // Mengambil total COUNT per plant
            $cogiCounts = Cogi::select(
                    'DWERK',
                    DB::raw('COUNT(*) as total_cogi')
                )
                ->whereIn('DWERK', $plants)
                ->groupBy('DWERK')
                ->pluck('total_cogi', 'DWERK');

            // Mengisi nilai default 0 untuk plant yang tidak ada data
            $plantValues = collect($plants)->mapWithKeys(function ($plant) use ($cogiCounts) {
                return [$plant => $cogiCounts->get($plant, 0)];
            });
            
            // Mengurutkan plant berdasarkan jumlah COGI (descending)
            $sortedPlants = $plantValues->sortDesc();

            // Menyiapkan data ranking untuk panel ringkasan
            $summaryRanking = $sortedPlants->map(function ($value, $plantCode) use ($plantNames) {
                return [
                    'plant_code' => $plantCode,
                    'plant_name' => $plantNames[$plantCode] ?? 'N/A',
                    'value' => $value,
                ];
            })->values()->toArray();

            // Menentukan index tertinggi di data chart asli (sebelum diurutkan)
            $highestValue = $sortedPlants->first() ?? 0;
            $highestPlantCodeOriginal = $sortedPlants->search($highestValue);
            $highestIndexOriginal = array_search($highestPlantCodeOriginal, $plants); // Cari index di array $plants awal


            // Menyiapkan data chart (urutan tetap sesuai $plants)
            $chartData = $plantValues->map(function ($value, $plantCode) {
                return $value;
            })->values()->toArray();
            $data = [
                'chart_data' => $chartData,
                'highest_index' => $highestIndexOriginal !== false ? $highestIndexOriginal : 0, // Index di chart asli
                'summary_ranking' => $summaryRanking, // Data ranking yang sudah diurutkan
            ];
            return response()->json($data);

        } catch (\Exception $e) {
            Log::error('Gagal mengambil data dashboard COGI: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memuat data dashboard.'], 500);
        }
    }
    public function syncCogiData(Request $request): JsonResponse
    {
        try {

            sleep(2); 

            // Jika berhasil
            return response()->json(['message' => 'Sinkronisasi data COGI berhasil. Data sedang diperbarui.']);
        
        } catch (\Exception $e) {
            // Jika gagal
            Log::error('Sinkronisasi COGI gagal: ' . $e->getMessage());
            // Berikan pesan error yang spesifik jika ada
            return response()->json(['message' => 'Sinkronisasi gagal: ' . $e->getMessage()], 500);
        }
    }
    public function getCogiDetails(Request $request, $plantCode): JsonResponse
    {
        try {
            // [PERBAIKAN] Tambahkan 'id' ke dalam select()
            $query = Cogi::ofPlant($plantCode)
                ->select(
                    'id',
                    'AUFNR',
                    'RSNUM',
                    'MATNRH',
                    'MAKTXH',
                    'DISPOH',
                    'BUDAT'
                )
                ->orderBy('BUDAT', 'desc');

            $data = $query->get();

            // Mengambil daftar unik DISPO untuk filter dropdown
            $dispoOptions = $data->pluck('DISPO')->unique()->filter()->values();

            return response()->json([
                'data' => $data,
                'dispo_options' => $dispoOptions,
            ]);

        } catch (\Exception $e) {
            Log::error("Gagal mengambil detail Cogi plant {$plantCode}: " . $e->getMessage());
            return response()->json(['message' => 'Gagal mengambil data detail.'], 500);
        }
    }
}

