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
            'vornr' => '0010', // Data with leading zero
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
    
    // Check for '10' instead of '0010'
    // Pattern: (10) in the HTML
    if (strpos($view, '(10)') !== false && strpos($view, 'font-style: italic') !== false) {
        echo "Verification PASSED: Found (10) (leading zero removed) with italic style.\n";
    } else {
        echo "Verification FAILED: Expected (10) not found. Output might contain (0010) or formatting issue.\n";
        // Dump a snippet for debugging if needed
        // echo substr($view, strpos($view, 'PRO12345'), 200); 
    }
} catch (\Exception $e) {
    echo "Verification ERROR: " . $e->getMessage() . "\n";
}
