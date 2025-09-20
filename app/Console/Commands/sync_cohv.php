<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File; // 1. Impor fasad File
use Illuminate\Support\Facades\Log;  // 2. Impor Log untuk mencatat error
use Illuminate\Support\Facades\Process; // 3. Impor Process untuk eksekusi

class sync_cohv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync_cohv';

    

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menjalankan proses sinkronisasi data COHV dari SAP ke database lokal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pythonScriptPath = base_path('sync_cohv.py'); 

        if (!File::exists($pythonScriptPath)) {
            $this->error("File python tidak ditemukan di lokasi: {$pythonScriptPath}");
            Log::error("...", ['path' => $pythonScriptPath]);
            return 1;
        }

        $this->info("Memulai eksekusi skrip Python secara real-time...");
        $this->line('----------------------------------------------------');

        // Ganti dengan path Python Anda
        $pythonExecutablePath = 'C:\Users\Niltal Amal\AppData\Local\Programs\Python\Python39\python.exe';
        
        // TAMBAHKAN FLAG "-u" PADA PERINTAH PYTHON
        // Ini SANGAT PENTING untuk menonaktifkan output buffering di Python
        $command = "\"{$pythonExecutablePath}\" -u \"{$pythonScriptPath}\"";

        // Jalankan proses dan berikan callback untuk menangani output live
        $result = Process::timeout(14400)->run($command, function (string $type, string $output) {
            // $output berisi baris yang baru saja dicetak oleh skrip Python
            // Kita cetak langsung ke konsol Laravel
            $this->line($output);
        });

        $this->line('----------------------------------------------------');

        // Setelah proses selesai, periksa hasilnya
        if ($result->successful()) {
            $this->info("✅ Skrip Python berhasil dijalankan.");
        } else {
            $this->error("❌ Terjadi kesalahan saat menjalankan skrip Python.");
            $this->error("Error Code: " . $result->exitCode());
            $this->error("Error Output: " . $result->errorOutput());
            Log::error('Error saat eksekusi sync_cohv.py', [
                'exit_code' => $result->exitCode(),
                'output' => $result->output(),
                'error_output' => $result->errorOutput()
            ]);
        }

        return $result->exitCode();
    }
}
