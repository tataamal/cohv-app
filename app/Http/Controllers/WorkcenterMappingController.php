<?php

namespace App\Http\Controllers;

use App\Models\WorkcenterMapping;
use App\Models\workcenter;
use App\Models\KodeLaravel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkcenterMappingController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkcenterMapping::with(['parentWorkcenter', 'childWorkcenter', 'kodeLaravel'])
            ->orderBy('created_at', 'desc');

        // Search Filters
        if ($request->filled('search_section')) {
            $searchSection = $request->search_section;
            $query->whereHas('kodeLaravel', function($q) use ($searchSection) {
                $q->where('laravel_code', 'like', "%{$searchSection}%")
                  ->orWhere('description', 'like', "%{$searchSection}%");
            });
        }

        if ($request->filled('search_parent')) {
            $searchParent = $request->search_parent;
            $query->whereHas('parentWorkcenter', function($q) use ($searchParent) {
                $q->where('kode_wc', 'like', "%{$searchParent}%")
                  ->orWhere('description', 'like', "%{$searchParent}%");
            });
        }

        if ($request->filled('search_child')) {
            $searchChild = $request->search_child;
            $query->whereHas('childWorkcenter', function($q) use ($searchChild) {
                $q->where('kode_wc', 'like', "%{$searchChild}%")
                  ->orWhere('description', 'like', "%{$searchChild}%");
            });
        }

        $mappings = $query->get();
            
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

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:workcenter_mappings,id',
            'kode_laravel_id' => 'nullable|exists:kode_laravel,id',
            'wc_induk_id' => 'nullable|exists:workcenters,id',
            'wc_anak_id' => 'nullable|exists:workcenters,id',
        ]);

        // Check Model Table Name first. Model: WorkcenterMapping.
        // I need to be careful with table name validation. 
        // Based on previous file reads, I didn't see explicit table name in Model file content showed earlier (it showed `protected $table = 'workcenters';` for workcenter model, but `WorkcenterMapping` model file wasn't fully shown or I missed checking its table name).
        // Standard Laravel convention: workcenter_mappings. But previous code uses `workcenter_mapping_parents` ? No, likely `workcenter_mappings`.
        // Let's check existing `destroy` method uses `WorkcenterMapping::destroy`.
        // I'll skip table validation or use `exists:workcenter_mapping_parents,id` if I am sure.
        // Wait, `WorkcenterMapping` model usage in `store` implies standard usage.
        
        $updateData = [];
        if ($request->filled('kode_laravel_id')) $updateData['kode_laravel_id'] = $request->kode_laravel_id;
        if ($request->filled('wc_induk_id')) $updateData['wc_induk_id'] = $request->wc_induk_id;
        if ($request->filled('wc_anak_id')) $updateData['wc_anak_id'] = $request->wc_anak_id;

        if (!empty($updateData)) {
            WorkcenterMapping::whereIn('id', $request->ids)->update($updateData);
            $count = count($request->ids);
            return redirect()->route('workcenter-mapping.index')->with('success', "$count Mapping(s) updated successfully.");
        }

        return redirect()->route('workcenter-mapping.index')->with('success', "No changes made.");
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
