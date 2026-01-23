<?php

namespace App\Console\Commands;

use App\Models\Mrp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportMrpCsv extends Command
{
    /**
     * The name and signature of the console command.
     * php artisan import:mrp public/csv/mrp.csv --dry-run
     * php artisan import:mrp public/csv/mrp.csv
     * php artisan import:mrp public/csv/mrp.csv --truncate
     * @var string
     */
    protected $signature = 'import:mrp
                            {path : Path CSV}
                            {--delimiter=, : Delimiter CSV}
                            {--dry-run : Validasi saja, tanpa insert/update}
                            {--truncate : Kosongkan tabel mrp sebelum import}';

    protected $description = 'Import MRP dari CSV (header: MRP, PLANT) ke tabel mrp';

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
        $idxMrp   = $this->findIndex($header, ['mrp', 'mrp ']);
        $idxPlant = $this->findIndex($header, ['plant']);

        if ($idxMrp === null || $idxPlant === null) {
            fclose($fh);
            $this->error("Header wajib ada: MRP, PLANT");
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

            $mrp = $this->val($data, $idxMrp);
            $plant = $this->val($data, $idxPlant);

            $mrp = $mrp !== null ? strtoupper(trim($mrp)) : null;
            $plant = $plant !== null ? strtoupper(trim($plant)) : null;

            if (empty($mrp) || empty($plant)) {
                $invalid++;
                $this->warn("Line {$line} invalid (mrp/plant kosong). Skip.");
                continue;
            }

            $rows[] = [
                'mrp'        => $mrp,
                'plant'      => $plant,

                // revive jika sebelumnya soft-deleted
                'deleted_at' => null,

                // timestamps untuk upsert
                'created_at' => $now,
                'updated_at' => $now,
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
                DB::table('mrp')->truncate();
                $this->warn("Tabel mrp di-truncate.");
            }

            // Upsert by (mrp, plant)
            Mrp::upsert(
                $rows,
                ['mrp', 'plant'],
                ['deleted_at', 'updated_at']
            );

            DB::commit();
            $this->info("Import mrp sukses. Upserted: " . count($rows));
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
