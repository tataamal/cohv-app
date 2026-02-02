<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkcenterMapping;
use App\Models\workcenter;
use App\Models\KodeLaravel;
use Illuminate\Support\Facades\Log;

class WorkcenterMappingSeeder extends Seeder
{
    /**
     * Jalankan database seeds.
     */
    public function run(): void
    {
        $filePath = public_path('csv/workcenter_grouping.csv');

        if (!file_exists($filePath)) {
            $this->command->error("File tidak ditemukan di: $filePath");
            return;
        }

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); 

        $count = 0;
        $skipped = 0;

        $this->command->info('Memulai import mapping workcenter...');

        while (($row = fgetcsv($file)) !== false) {
            $kodeInduk   = $row[1];
            $kodeAnak    = $row[3];
            $valLaravel  = $row[5];

            $induk = workcenter::where('kode_wc', $kodeInduk)->first();
            
            $anak = workcenter::where('kode_wc', $kodeAnak)->first();

            $laravel = KodeLaravel::where('laravel_code', $valLaravel)->first();

            if ($induk && $anak) {
                WorkcenterMapping::updateOrCreate(
                    [
                        'wc_induk_id' => $induk->id,
                        'wc_anak_id'  => $anak->id,
                    ],
                    [
                        'kode_laravel_id' => $laravel ? $laravel->id : null,
                    ]
                );
                $count++;
            } else {
                $skipped++;
                Log::warning("Mapping Gagal: Induk ($kodeInduk) atau Anak ($kodeAnak) tidak ditemukan di master workcenters.");
            }
        }

        fclose($file);

        $this->command->info("Selesai! Berhasil mengimpor $count mapping. (Gagal/Skipped: $skipped)");
    }
}