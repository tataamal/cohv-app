<?php

use App\Models\Kode;

$target = ['3410', '3411', '3412', '3015'];
$kodes = Kode::whereIn('kode', $target)->get();
foreach ($kodes as $k) {
    echo "Code: " . $k->kode . " | Name: [" . $k->nama_bagian . "]\n";
}
// Also search by name
$names = Kode::where('nama_bagian', 'LIKE', '%PAINTING%')->get();
foreach ($names as $k) {
    echo "Code: " . $k->kode . " | Name: [" . $k->nama_bagian . "]\n";
}
