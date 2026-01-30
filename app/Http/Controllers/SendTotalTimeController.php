<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\Models\HistoryWiItem;
use App\Models\DailyTimeWi;
use Illuminate\Support\Facades\Log;

class SendTotalTimeController extends Controller
{

    public function calculateAndStoreDailyTime($startDate = null, $endDate = null)
    {
        try {
            // Determine date range
            if ($startDate) {
                $start = Carbon::parse($startDate)->startOfDay();
                $end   = $endDate ? Carbon::parse($endDate)->startOfDay() : Carbon::parse($startDate)->startOfDay();
            } else {
                $start = Carbon::yesterday()->startOfDay();
                $end   = Carbon::yesterday()->startOfDay();
            }

            $totalProcessed = 0;
            $processedDates = [];

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $currentDate = $date->toDateString();

                // Ambil semua item di tanggal tersebut + plant_code dari header
                // NOTE: HistoryWi pakai SoftDeletes, jadi aman untuk exclude deleted header dengan whereNull(deleted_at)
                $rows = HistoryWiItem::query()
                    ->join('history_wi', 'history_wi_item.history_wi_id', '=', 'history_wi.id')
                    ->whereNull('history_wi.deleted_at')
                    ->whereDate('history_wi.document_date', $currentDate)
                    ->whereNotNull('history_wi_item.nik')
                    ->where('history_wi_item.nik', '!=', '')
                    ->select([
                        'history_wi_item.nik',
                        'history_wi_item.operator_name',
                        'history_wi_item.name1',
                        'history_wi.plant_code',
                        'history_wi_item.calculated_takt_time',
                    ])
                    ->get();

                $aggregatedData = [];

                foreach ($rows as $r) {
                    $nik = trim((string) $r->nik);
                    if ($nik === '') continue;

                    // pilih nama operator yang tersedia
                    $nama = $r->operator_name ?: ($r->name1 ?: null);

                    // calculated_takt_time (decimal:2) -> float
                    $timeVal = (float) ($r->calculated_takt_time ?? 0);
                    if ($timeVal < 0) $timeVal = 0;

                    if (!isset($aggregatedData[$nik])) {
                        $aggregatedData[$nik] = [
                            'nik'        => $nik,
                            'nama'       => $nama,
                            'plants'     => [],
                            'total_time' => 0.0,
                        ];
                    }

                    if (empty($aggregatedData[$nik]['nama']) && !empty($nama)) {
                        $aggregatedData[$nik]['nama'] = $nama;
                    }

                    $aggregatedData[$nik]['total_time'] += $timeVal;

                    $plantCode = (string) ($r->plant_code ?? '');
                    if ($plantCode !== '' && !in_array($plantCode, $aggregatedData[$nik]['plants'], true)) {
                        $aggregatedData[$nik]['plants'][] = $plantCode;
                    }
                }

                $count = 0;
                foreach ($aggregatedData as $data) {
                    sort($data['plants']);
                    $plantString = implode(',', $data['plants']);

                    $record = DailyTimeWi::firstOrNew([
                        'tanggal' => $currentDate,
                        'nik'     => $data['nik'],
                    ]);

                    $record->kode_laravel   = $plantString;
                    $record->total_time_wi  = (int) ceil($data['total_time']); // sama seperti sebelumnya
                    $record->nama           = $data['nama'];

                    $record->save();
                    $count++;
                }

                $totalProcessed += $count;
                $processedDates[] = $currentDate;
            }

            $dateRangeStr = $start->toDateString() . ($start->notEqualTo($end) ? ' to ' . $end->toDateString() : '');

            return response()->json([
                'success'           => true,
                'message'           => "Berhasil menghitung dan menyimpan data harian untuk: {$dateRangeStr}.",
                'records_processed' => $totalProcessed,
                'dates_processed'   => $processedDates,
            ]);

        } catch (\Exception $e) {
            Log::error("Error Calculating Daily Time WI: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal: ' . $e->getMessage(),
            ], 500);
        }
    }
}
