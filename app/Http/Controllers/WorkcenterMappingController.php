<?php

namespace App\Http\Controllers;

use App\Models\WorkcenterMapping;
use App\Models\workcenter;
use App\Models\KodeLaravel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkcenterMappingController extends Controller
{
    public function index()
    {
        $mappings = WorkcenterMapping::with(['parentWorkcenter', 'childWorkcenter', 'kodeLaravel'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        $workcenters = workcenter::orderBy('kode_wc')->get();
        $kodeLaravels = KodeLaravel::all();

        return view('workcenter-mapping.index', compact('mappings', 'workcenters', 'kodeLaravels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_laravel_id' => 'required|exists:kode_laravel,id',
            'wc_induk_id' => 'required|array',
            'wc_induk_id.*' => 'exists:workcenters,id',
            'wc_anak_id' => 'required|array',
            'wc_anak_id.*' => 'exists:workcenters,id',
        ]);

        $kodeId = $request->kode_laravel_id;
        $parents = $request->wc_induk_id;
        $children = $request->wc_anak_id;
        $count = 0;

        foreach ($parents as $parentId) {
            foreach ($children as $childId) {
                if ($parentId == $childId) continue;

                WorkcenterMapping::firstOrCreate([
                    'wc_induk_id' => $parentId,
                    'wc_anak_id' => $childId,
                    'kode_laravel_id' => $kodeId,
                ]);
                $count++;
            }
        }

        return redirect()->route('workcenter-mapping.index')->with('success', "$count Mapping(s) created successfully.");
    }

    public function destroy($id)
    {
        $mapping = WorkcenterMapping::findOrFail($id);
        $mapping->delete();

        return back()->with('success', 'Mapping deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids');
        
        if (empty($ids)) {
            return response()->json(['message' => 'No items selected.'], 400);
        }

        WorkcenterMapping::destroy($ids);

        return redirect()->route('workcenter-mapping.index')->with('success', 'Selected mappings deleted successfully.');
    }
}
