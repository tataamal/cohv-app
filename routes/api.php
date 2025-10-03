use App\Models\ProductionTData3;
use Illuminate\Http\Request;

Route::get('/api/pro-by-status', function (Request $request) {
    $status = strtoupper($request->query('status', ''));
    $plant = $request->query('plant', '');

    $query = ProductionTData3::where('WERKSX', $plant);

    if ($status === 'LAINNYA') {
         // Logika untuk status selain yang utama
         $query->whereNotIn('STATS', ['REL', 'CRTD', 'TECO', 'CNF', 'PCNF']);
    } elseif (!empty($status)) {
         $query->where('STATS', $status);
    }
    
    // Pilih kolom yang dibutuhkan (sesuaikan dengan yang Anda tampilkan di tabel)
    $data = $query->get(['AUFNR', 'STATS', 'MATNR', 'PSMNG']);

    return response()->json($data);
});