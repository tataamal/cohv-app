<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\workcenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ReadWorkcenterCsv extends Command
{
    /**
     * The name and signature of the console command.
     * cara penggunaan signature : 
     * Contoh file taruh di public/csv/workcenters.csv: php artisan import:workcenters public/csv/workcenters.csv
     * Contoh file Kalau delimiter (;) : php artisan import:workcenters public/csv/workcenters.csv --delimiter=";"
     * Cek dulu tanpa insert : php artisan import:workcenters public/csv/workcenters.csv --dry-run
     * Kosongkan tabel dulu sebelum isi ulang: php artisan import:workcenters public/csv/workcenters.csv --truncate
     * @var string
     */
    protected $signature = 'import:workcenters
                            {path : Path CSV}
                            {--delimiter=, : Delimiter CSV}
                            {--dry-run : Validasi saja, tanpa insert/update}
                            {--truncate : Kosongkan tabel workcenters sebelum import}';

    protected $description = 'Import Workcenters dari CSV ke tabel workcenters (kolom: plant,kode_wc,description,start_time,end_time,operating_time,capacity)';

    public function handle(): int
    {
        $path = $this->argument('path');
        $delimiter = (string) $this->option('delimiter');
        $dryRun = (bool) $this->option('dry-run');
        $truncate = (bool) $this->option('truncate');

        if (!File::exists($path) || !is_readable($path)) {
            $this->error("File tidak ditemukan / tidak bisa dibaca: {$path}");
            return self::FAILURE;
        }

        $fh = fopen($path, 'r');
        if ($fh === false) {
            $this->error("Gagal membuka file: {$path}");
            return self::FAILURE;
        }

        $rawHeader = fgetcsv($fh, 0, $delimiter);
        if ($rawHeader === false) {
            fclose($fh);
            $this->error("CSV kosong / header tidak terbaca.");
            return self::FAILURE;
        }

        $header = array_map([$this, 'normHeader'], $rawHeader);

        // Mapping header CSV -> kolom DB (toleran variasi nama + typo)
        $idx = [
            'plant'          => $this->findIndex($header, ['plant', 'finishing - echo']),
            'kode_wc'        => $this->findIndex($header, ['kode wc', 'kode_wc', 'workcenter', 'work center', 'wc']),
            'description'    => $this->findIndex($header, ['deskripsi', 'description', 'desc']),
            'start_time'     => $this->findIndex($header, ['start time', 'start_time', 'jam mulai']),
            'end_time'       => $this->findIndex($header, ['end time', 'end_time', 'jam selesai']),
            'operating_time' => $this->findIndex($header, ['operatiing time', 'operating time', 'operating_time', 'waktu operasi']),
            'capacity'       => $this->findIndex($header, ['capacity', 'kapasitas']),
        ];

        // Minimal wajib ada
        if ($idx['plant'] === null || $idx['kode_wc'] === null) {
            fclose($fh);
            $this->error("Header wajib ada minimal: PLANT dan KODE WC");
            $this->line("Header terbaca: " . implode(' | ', $rawHeader));
            return self::FAILURE;
        }

        $rows = [];
        $line = 1;
        $skippedEmpty = 0;
        $invalid = 0;
        $now = now();

        while (($data = fgetcsv($fh, 0, $delimiter)) !== false) {
            $line++;

            if ($this->isRowEmpty($data)) {
                $skippedEmpty++;
                continue;
            }

            $plant = $this->val($data, $idx['plant']);
            $kodeWc = $this->val($data, $idx['kode_wc']);

            $plant = $plant !== null ? strtoupper(trim($plant)) : null;
            $kodeWc = $kodeWc !== null ? strtoupper(trim($kodeWc)) : null;

            if (empty($plant) || empty($kodeWc)) {
                $invalid++;
                $this->warn("Line {$line} invalid (plant/kode_wc kosong). Skip.");
                continue;
            }

            $row = [
                'plant'          => $plant,
                'kode_wc'        => $kodeWc,
                'description'    => $this->val($data, $idx['description']),
                'start_time'     => $this->val($data, $idx['start_time']),
                'end_time'       => $this->val($data, $idx['end_time']),
                'operating_time' => $this->val($data, $idx['operating_time']),
                'capacity'       => $this->val($data, $idx['capacity']),

                // revive bila sebelumnya soft-deleted
                'deleted_at'     => null,

                // timestamps untuk upsert
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            $rows[] = $row;
        }

        fclose($fh);

        $this->info("Baca CSV selesai.");
        $this->line("Valid rows: " . count($rows));
        $this->line("Skipped empty rows: {$skippedEmpty}");
        $this->line("Invalid rows: {$invalid}");

        if ($dryRun) {
            $this->comment("Dry-run aktif: tidak ada perubahan ke database.");
            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            if ($truncate) {
                DB::table('workcenters')->truncate();
                $this->warn("Tabel workcenters di-truncate.");
            }

            // Unique key sesuai migration: plant + kode_wc
            Workcenter::upsert(
                $rows,
                ['plant', 'kode_wc'],
                [
                    'description',
                    'start_time',
                    'end_time',
                    'operating_time',
                    'capacity',
                    'deleted_at',
                    'updated_at',
                ]
            );

            DB::commit();
            $this->info("Import workcenters sukses. Upserted: " . count($rows));
            return self::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Import gagal: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function normHeader(string $h): string
    {
        $h = strtolower(trim($h));
        $h = preg_replace('/\s+/', ' ', $h);
        return $h;
    }

    private function findIndex(array $header, array $aliases): ?int
    {
        foreach ($aliases as $a) {
            $a = $this->normHeader($a);
            $i = array_search($a, $header, true);
            if ($i !== false) return $i;
        }
        return null;
    }

    private function val(array $data, ?int $idx): ?string
    {
        if ($idx === null) return null;
        $v = $data[$idx] ?? null;
        if ($v === null) return null;
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }

    private function isRowEmpty(array $data): bool
    {
        foreach ($data as $v) {
            if (trim((string) $v) !== '') return false;
        }
        return true;
    }
}
