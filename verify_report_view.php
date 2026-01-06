<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$mockData = [
    'items' => [
        [
            'doc_no' => 'TEST001',
            'doc_date' => '06-01-2026',
            'workcenter' => 'WC01',
            'wc_description' => 'Test Workcenter',
            'so_item' => 'SO-001',
            'aufnr' => 'PRO12345',
            'vornr' => '0010', // The valuable data
            'material' => 'MAT001',
            'description' => 'Test Material',
            'assigned' => 10,
            'confirmed' => 10,
            'remark_qty' => 0,
            'remark_text' => '',
            'takt_time' => '10 Menit',
            'nik' => '12345',
            'name' => 'John Doe',
            'price_ok_fmt' => 'Rp 10.000',
            'price_fail_fmt' => 'Rp 0',
            'confirmed_price' => 10000,
            'failed_price' => 0,
            'currency' => 'IDR',
            'raw_total_time' => 10
        ]
    ],
    'summary' => [
        'total_assigned' => 10,
        'total_confirmed' => 10,
        'total_failed' => 0,
        'achievement_rate' => '100%',
        'total_price_ok' => 'Rp 10.000',
        'total_price_fail' => 'Rp 0'
    ],
    'nama_bagian' => 'TEST DEPT',
    'filterInfo' => 'TEST FILTER',
    'report_title' => 'TEST REPORT',
    'doc_metadata' => ['status' => 'ACTIVE']
];

try {
    $view = view('pdf.log_history', ['reports' => [$mockData], 'isEmail' => true])->render();
    
    if (strpos($view, '(0010)') !== false && strpos($view, 'font-style: italic') !== false) {
        echo "Verification PASSED: Found (0010) with italic style.\n";
    } else {
        echo "Verification FAILED: vornr not found or not styled correctly.\n";
    }
} catch (\Exception $e) {
    echo "Verification ERROR: " . $e->getMessage() . "\n";
}
