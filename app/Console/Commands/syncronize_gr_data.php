<?php

namespace App\Console\Commands;

use Symfony\Component\Process\Process as SymfonyProcess;
use Illuminate\Console\Command;

class syncronize_gr_data extends Command
{
    protected $signature = 'syncronize:gr-data';
    protected $description = 'Syncronize GR data seng akeh kui';

    public function handle(): int
    {
        $apiPath = base_path('sync_gr.py');
        if (!file_exists($apiPath)) {
            $this->error("api.py tidak ditemukan di: {$apiPath}");
            return self::FAILURE;
        }

        $python = $this->detectPython();
        $this->info("Python: {$python}");

        $cmd = [$python, $apiPath, 'run_historical'];
        $process = new SymfonyProcess($cmd, base_path(), null);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error('Sync failed');
            return self::FAILURE;
        }

        $this->info('Sync done');
        return self::SUCCESS;
    }

    private function detectPython(): string
    {
        $candidates = [
            base_path('venv\\Scripts\\python.exe'),
            base_path('venv\\Scripts\\python'),
            base_path('venv/bin/python'),
            'py',
            'python',
            'python3',
        ];
        foreach ($candidates as $bin) {
            if (preg_match('/[\\\\\\/]/', $bin)) {
                if (file_exists($bin)) return $bin;
            } else {
                try {
                    $p = new SymfonyProcess([$bin, '--version']);
                    $p->setTimeout(5);
                    $p->run();
                    if ($p->isSuccessful()) return $bin;
                } catch (\Throwable $e) {
                }
            }
        }
        return 'python';
    }
}
