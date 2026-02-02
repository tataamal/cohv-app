<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WcRelation;
use App\Models\workcenter;
use Illuminate\Support\Facades\Log;

class WcRelationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lokasi file CSV di dalam folder public/csv
        $filePath = public_path('csv/workcenter_relation.csv');

        if (!file_exists($filePath)) {
            $this->command->error("File tidak ditemukan di: $filePath");
            return;
        }

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); // Melewati baris pertama (header)

        $count = 0;
        $skipped = 0;

        $this->command->info('Memulai import relasi workcenter...');

        while (($row = fgetcsv($file)) !== false) {
            // Mapping kolom berdasarkan index CSV: 
            // 1: kode_wc_asal, 2: kode_wc_tujuan, 3: status
            $kodeAsal = $row[1];
            $kodeTujuan = $row[2];
            $status = $row[3];

            // Cari ID workcenter asal
            $wcAsal = workcenter::where('kode_wc', $kodeAsal)->first();
            
            // Cari ID workcenter tujuan
            $wcTujuan = workcenter::where('kode_wc', $kodeTujuan)->first();

            if ($wcAsal && $wcTujuan) {
                WcRelation::updateOrCreate(
                    [
                        'wc_asal_id' => $wcAsal->id,
                        'wc_tujuan_id' => $wcTujuan->id,
                    ],
                    [
                        'status' => $status,
                    ]
                );
                $count++;
            } else {
                $skipped++;
                Log::warning("Gagal mapping: Asal ($kodeAsal) atau Tujuan ($kodeTujuan) tidak ditemukan di database.");
            }
        }

        fclose($file);

        $this->command->info("Selesai! Berhasil mengimpor $count data. (Skipped: $skipped)");
    }
}