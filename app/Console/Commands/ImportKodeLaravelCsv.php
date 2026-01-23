<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportKodeLaravelCsv extends Command
{
    /**
     * The name and signature of the console command.
     * php artisan import:kode-laravel /public/csv/kode_laravel.csv --dry-run
     * php artisan import:kode-laravel /public/csv/kode_laravel.csv
     * @var string
     */
    protected $signature = 'import:kode-laravel
                            {path : Path CSV}
                            {--delimiter=, : Delimiter CSV}
                            {--dry-run : Validasi saja}
                            {--truncate : Kosongkan tabel kode_laravel sebelum import}';

    protected $description = 'Import Kode Laravel dari CSV ke tabel kode_laravel (laravel_code, description, plant)';

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

        // CSV biasanya: "KODE LARAVEL", "DESKRIPSI", "PLANT"
        // Tapi kita toleran variasi:
        $idxCode = $this->findIndex($header, ['kode laravel', 'laravel code', 'laravel_code', 'code']);
        $idxDesc = $this->findIndex($header, ['deskripsi', 'description', 'desc']);
        $idxPlant = $this->findIndex($header, ['plant']);

        if ($idxCode === null || $idxDesc === null || $idxPlant === null) {
            fclose($fh);
            $this->error("Header wajib ada: KODE LARAVEL / DESCRIPTION (DESKRIPSI) / PLANT");
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

            $code = $this->val($data, $idxCode);
            $desc = $this->val($data, $idxDesc);
            $plant = $this->val($data, $idxPlant);

            $code = $code !== null ? strtoupper(trim($code)) : null;
            $plant = $plant !== null ? strtoupper(trim($plant)) : null;
            $desc = $desc !== null ? trim($desc) : null;

            if (empty($code) || empty($plant)) {
                $invalid++;
                $this->warn("Line {$line} invalid (laravel_code/plant kosong). Skip.");
                continue;
            }

            $rows[] = [
                'laravel_code' => $code,
                'description'  => ($desc === '' ? null : $desc),
                'plant'        => $plant,
                'deleted_at'   => null,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
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
                DB::table('kode_laravel')->truncate();
                $this->warn("Tabel kode_laravel di-truncate.");
            }

            DB::table('kode_laravel')->upsert(
                $rows,
                ['laravel_code', 'plant'],
                ['description', 'deleted_at', 'updated_at']
            );

            DB::commit();
            $this->info("Import kode_laravel sukses. Upserted: " . count($rows));
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

    private function val(array $data, int $idx): ?string
    {
        $v = $data[$idx] ?? null;
        if ($v === null) return null;
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }

    private function isRowEmpty(array $data): bool
    {
        foreach ($data as $v) {
            if (trim((string)$v) !== '') return false;
        }
        return true;
    }
}
