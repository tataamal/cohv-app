<?php

namespace App\Services;

use App\Models\workcenter;
use Illuminate\Support\Facades\DB;

class WorkcenterConsumeService
{
    public const MIN_CAPACITY_SEC = 34200; // 9.5 jam

    /**
     * Convert operating_time (TIME "HH:MM:SS" / "HH:MM" / numeric jam decimal) -> detik
     */
    public function operatingTimeToSeconds($value): int
    {
        if ($value === null) {
            return self::MIN_CAPACITY_SEC;
        }

        // kalau sudah numeric (mis. 7.5 jam), anggap jam decimal
        if (is_numeric($value)) {
            $sec = (int) round(((float) $value) * 3600);
            return max($sec, self::MIN_CAPACITY_SEC);
        }

        $s = trim((string) $value);
        if ($s === '') {
            return self::MIN_CAPACITY_SEC;
        }

        // format HH:MM:SS atau H:MM:SS
        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $s)) {
            [$h, $m, $sec] = array_map('intval', explode(':', $s));
            $total = ($h * 3600) + ($m * 60) + $sec;
            return max($total, self::MIN_CAPACITY_SEC);
        }

        // format HH:MM
        if (preg_match('/^\d{1,2}:\d{2}$/', $s)) {
            [$h, $m] = array_map('intval', explode(':', $s));
            $total = ($h * 3600) + ($m * 60);
            return max($total, self::MIN_CAPACITY_SEC);
        }

        // fallback: coba parse jam decimal di string
        $normalized = str_replace(',', '.', $s);
        if (is_numeric($normalized)) {
            $sec = (int) round(((float) $normalized) * 3600);
            return max($sec, self::MIN_CAPACITY_SEC);
        }

        // format aneh -> fallback aman
        return self::MIN_CAPACITY_SEC;
    }

    /**
     * Batch resolve kode_wc -> workcenters via mapping_table.kode_laravel_id (FK ID)
     *
     * return:
     *  [
     *    'WC327' => [
     *        'id' => 396,
     *        'operating_time' => '07:00:00',
     *        'capacity_total_sec' => 34200
     *    ],
     *    ...
     *  ]
     */
    public function resolveWorkcentersByKodeLaravel(int $kodeLaravelId, array $kodeWcList): array
    {
        $kodeWcList = array_values(array_unique(array_filter(array_map(
            fn ($x) => trim((string) $x),
            $kodeWcList
        ))));

        if (empty($kodeWcList)) return [];

        $rows = workcenter::query()
            ->join('mapping_table as m', 'm.workcenter_id', '=', 'workcenters.id')
            ->where('m.kode_laravel_id', $kodeLaravelId)
            ->whereIn('workcenters.kode_wc', $kodeWcList)
            ->select([
                'workcenters.id',
                'workcenters.kode_wc',
                'workcenters.operating_time',
            ])
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $opTime = $r->operating_time;

            $map[$r->kode_wc] = [
                'id' => (int) $r->id,
                'operating_time' => $opTime,
                'capacity_total_sec' => $this->operatingTimeToSeconds($opTime),
            ];
        }

        return $map;
    }

    public function ensureDailyRow(string $date, int $workcenterId, int $totalSec): void
    {
        DB::statement(
            "INSERT INTO `workcenter_consume`
                (work_date, workcenter_id, capacity_total_sec, capacity_used_sec, created_at, updated_at)
             VALUES (?, ?, ?, 0, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                capacity_total_sec = GREATEST(capacity_total_sec, VALUES(capacity_total_sec)),
                updated_at = NOW()",
            [$date, $workcenterId, $totalSec]
        );
    }

    public function tryConsume(string $date, int $workcenterId, int $needSec): bool
    {
        $affected = DB::update(
            "UPDATE `workcenter_consume`
             SET capacity_used_sec = capacity_used_sec + ?,
                 updated_at = NOW()
             WHERE work_date = ?
               AND workcenter_id = ?
               AND capacity_used_sec + ? <= capacity_total_sec",
            [$needSec, $date, $workcenterId, $needSec]
        );

        return $affected === 1;
    }

    public function consumeManyOrFail(string $date, array $needsByWcId, array $totalsByWcId): void
    {
        // urutkan key biar konsisten (mengurangi potensi deadlock kalau suatu saat multi-WC)
        ksort($needsByWcId);

        foreach ($needsByWcId as $wcId => $needSec) {
            $wcId = (int) $wcId;
            $needSec = (int) $needSec;
            if ($needSec <= 0) continue;

            $totalSec = (int) ($totalsByWcId[$wcId] ?? self::MIN_CAPACITY_SEC);

            $this->ensureDailyRow($date, $wcId, $totalSec);

            if (!$this->tryConsume($date, $wcId, $needSec)) {
                throw new \DomainException("Kapasitas workcenter penuh (workcenter_id={$wcId}) pada tanggal {$date}");
            }
        }
    }

    public function releaseMany(string $date, array $needsByWcId): void
    {
        ksort($needsByWcId);

        foreach ($needsByWcId as $wcId => $sec) {
            $wcId = (int) $wcId;
            $sec = (int) $sec;
            if ($sec <= 0) continue;

            DB::update(
                "UPDATE `workcenter_consume`
                 SET capacity_used_sec = CASE
                    WHEN capacity_used_sec >= ? THEN capacity_used_sec - ?
                    ELSE 0
                 END,
                 updated_at = NOW()
                 WHERE work_date = ? AND workcenter_id = ?",
                [$sec, $sec, $date, $wcId]
            );
        }
    }
}
