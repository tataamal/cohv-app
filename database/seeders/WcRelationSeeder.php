<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\wc_relation; // Menggunakan model Anda
use App\Models\workcenter; 
use Illuminate\Support\Facades\DB;

class WcRelationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Kosongkan tabel relasi
        DB::table('wc_relations')->truncate();

        // 2. Buat peta lookup (kode_wc => id) dari tabel workcenters untuk efisiensi
        $this->command->info('Memuat data workcenter ke memori...');
        $wcLookup = workcenter::pluck('id', 'kode_wc');
        $this->command->info(count($wcLookup) . ' workcenter berhasil dimuat.');

        // 3. Path ke file CSV
        $surabayaFile = database_path('seeders/data/List_WC_Kompatible_Surabaya_cleaned.csv');
        $semarangFile = database_path('seeders/data/List_WC_Kompatible_Semarang_cleaned.csv');

        // 4. Proses setiap file
        $relations = array_merge(
            $this->processFile($surabayaFile, $wcLookup),
            $this->processFile($semarangFile, $wcLookup)
        );

        // 5. Masukkan semua data relasi yang terkumpul
        $this->command->info("Memasukkan total " . count($relations) . " relasi ke database...");
        foreach (array_chunk($relations, 500) as $chunk) {
            DB::table('wc_relations')->insert($chunk);
        }

        $this->command->info("Proses seeding relasi selesai.");
    }

    /**
     * Membaca CSV dan mengubahnya menjadi array relasi.
     */
    protected function processFile(string $filePath, $wcLookup): array
    {
        $this->command->info("Memproses relasi dari file: " . basename($filePath));
        $fileHandle = fopen($filePath, 'r');
        $header = fgetcsv($fileHandle);
        $relationsToInsert = [];
        $skippedCount = 0;

        while (($row = fgetcsv($fileHandle)) !== false) {
            $record = array_combine($header, $row);

            $wcAsalKode = trim($record['WC Asal'] ?? '');
            $wcAsalId = $wcLookup->get($wcAsalKode);

            // Lewati jika WC Asal kosong atau tidak ada di database
            if (empty($wcAsalKode) || !$wcAsalId) {
                continue;
            }

            // **LOGIKA BARU: Proses kolom 'WC Kompatibel'**
            $wcTujuanNormal = trim($record['WC Kompatibel'] ?? '');
            if (!empty($wcTujuanNormal)) {
                $wcTujuanId = $wcLookup->get($wcTujuanNormal);
                if ($wcTujuanId) {
                    $relationsToInsert[] = [
                        'wc_asal_id'   => $wcAsalId,
                        'wc_tujuan_id' => $wcTujuanId,
                        'status'       => 'compatible',
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];
                } else {
                    $skippedCount++;
                }
            }
            
            // **LOGIKA BARU: Proses kolom 'WC Kompatibel With Condition'**
            $wcTujuanCondition = trim($record['WC Kompatibel With Condition'] ?? '');
            if (!empty($wcTujuanCondition)) {
                $wcTujuanId = $wcLookup->get($wcTujuanCondition);
                if ($wcTujuanId) {
                    $relationsToInsert[] = [
                        'wc_asal_id'   => $wcAsalId,
                        'wc_tujuan_id' => $wcTujuanId,
                        'status'       => 'compatible with condition',
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];
                } else {
                    $skippedCount++;
                }
            }
        }
        fclose($fileHandle);

        if ($skippedCount > 0) {
            $this->command->warn($skippedCount . " relasi dilewati di file " . basename($filePath) . " karena workcenter tujuan tidak ditemukan.");
        }
        
        return $relationsToInsert;
    }
}
