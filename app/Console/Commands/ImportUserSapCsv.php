<?php

namespace App\Console\Commands;

use App\Models\UserSap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportUserSapCsv extends Command
{
    /**
     * The name and signature of the console command.
     * UNTUK MEMBACA FILE CSV : php artisan import:user-sap public/csv/user_sap.csv --dry run
     * Untuk memproses file CSV : php artisan import:user-sap public/csv/user_sap.csv
     * @var string
     */
    protected $signature = 'import:user-sap
                            {path : Path CSV}
                            {--delimiter=, : Delimiter CSV}
                            {--dry-run : Validasi saja, tanpa insert/update}
                            {--truncate : Kosongkan tabel user_sap sebelum import}';

    protected $description = 'Import User SAP dari CSV (header: SAP ID, NAMA) ke tabel user_sap (kolom: user_sap, name)';

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

        // CSV: "SAP ID", "NAMA"
        $idxSapId = $this->findIndex($header, ['sap id', 'sap_id', 'sapid']);
        $idxNama  = $this->findIndex($header, ['nama', 'name']);

        if ($idxSapId === null || $idxNama === null) {
            fclose($fh);
            $this->error("Header wajib ada: SAP ID, NAMA");
            $this->line("Header terbaca: " . implode(' | ', $rawHeader));
            return self::FAILURE;
        }

        $rows = [];
        $line = 1; // header line
        $skippedEmpty = 0;
        $invalid = 0;
        $now = now();

        while (($data = fgetcsv($fh, 0, $delimiter)) !== false) {
            $line++;

            if ($this->isRowEmpty($data)) {
                $skippedEmpty++;
                continue;
            }

            $sapId = $this->val($data, $idxSapId);
            $nama  = $this->val($data, $idxNama);

            if (empty($sapId)) {
                $invalid++;
                $this->warn("Line {$line} invalid (SAP ID kosong). Skip.");
                continue;
            }

            $sapId = strtoupper(trim($sapId));
            $nama  = $nama !== null ? trim($nama) : null;

            $rows[] = [
                'user_sap'   => $sapId,
                'name'       => ($nama === '' ? null : $nama),
                'deleted_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        fclose($fh);

        $this->info("Baca CSV selesai.");
        $this->line("Valid rows: " . count($rows));
        $this->line("Skipped empty rows: {$skippedEmpty}");
        $this->line("Invalid rows (SAP ID kosong): {$invalid}");

        if ($dryRun) {
            $this->comment("Dry-run aktif: tidak ada perubahan ke database.");
            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            if ($truncate) {
                DB::table('user_sap')->truncate();
                $this->warn("Tabel user_sap di-truncate.");
            }

            UserSap::upsert(
                $rows,
                ['user_sap'],
                ['name', 'deleted_at', 'updated_at']
            );

            DB::commit();
            $this->info("Import user_sap sukses. Upserted: " . count($rows));
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
            if (trim((string) $v) !== '') return false;
        }
        return true;
    }
}
