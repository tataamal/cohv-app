<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class sync_gr extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync_gr_historical';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'PERHATIAN: Menjalankan sinkronisasi data GR historis (bisa sangat lama).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('Proses ini akan menarik data dari 1 September 2025 sampai hari ini dan bisa memakan waktu berjam-jam. Lanjutkan?')) {
            $this->info('Proses dibatalkan.');
            return;
        }

        $pythonScriptPath = base_path('sync_gr.py');
        if (!File::exists($pythonScriptPath)) {
            $this->error("File python 'sync_gr.py' tidak ditemukan.");
            return 1;
        }

        $this->info("Memulai sinkronisasi GR historis...");
        $this->line('----------------------------------------------------');

        $pythonExecutablePath = 'C:\Users\Niltal Amal\AppData\Local\Programs\Python\Python39\python.exe'; // GANTI INI
        
        // Memanggil skrip Python dengan argumen 'run_historical'
        $command = "\"{$pythonExecutablePath}\" -u \"{$pythonScriptPath}\" run_historical";
        
        // Beri timeout yang sangat panjang, misal 24 jam (86400 detik)
        $result = Process::timeout(86400)->run($command, function (string $type, string $output) {
            $this->line(rtrim($output));
        });

        $this->line('----------------------------------------------------');

        if ($result->successful()) {
            $this->info("✅ Sinkronisasi GR historis berhasil diselesaikan.");
        } else {
            $this->error("❌ Terjadi kesalahan saat sinkronisasi GR historis.");
            Log::error('Error saat eksekusi sync_gr.py (historical)', ['output' => $result->errorOutput()]);
        }

        return $result->exitCode();
    }
}
