<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MappingTable;
use App\Models\UserSap;
use App\Models\KodeLaravel;
use App\Models\MRP;
use App\Models\workcenter;

class MappingController extends Controller
{
    public function index(Request $request)
    {
        $query = MappingTable::with(['userSap', 'kodeLaravel', 'mrp', 'workcenter']);

        if ($request->filled('search_sap')) {
            $searchSap = $request->search_sap;
            $query->whereHas('userSap', function($q) use ($searchSap) {
                $q->where('name', 'like', "%{$searchSap}%")
                  ->orWhere('user_sap', 'like', "%{$searchSap}%");
            });
        }

        if ($request->filled('search_kode')) {
            $searchKode = $request->search_kode;
            $query->whereHas('kodeLaravel', function($q) use ($searchKode) {
                $q->where('laravel_code', 'like', "%{$searchKode}%")
                  ->orWhere('description', 'like', "%{$searchKode}%");
            });
        }

        $mappings = $query->latest()->get();
        
        $userSaps = UserSap::all();
        $kodeLaravels = KodeLaravel::all();
        $mrps = MRP::all();
        $workcenters = workcenter::all();

        return view('mapping.index', compact('mappings', 'userSaps', 'kodeLaravels', 'mrps', 'workcenters'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_sap_id' => 'required|array',
            'user_sap_id.*' => 'exists:user_sap,id',
            'kode_laravel_id' => 'required|array',
            'kode_laravel_id.*' => 'exists:kode_laravel,id',
            'mrp_id' => 'required|array',
            'mrp_id.*' => 'exists:mrp,id',
            'workcenter_id' => 'required|array',
            'workcenter_id.*' => 'exists:workcenters,id',
        ]);

        $users = $request->user_sap_id;
        $kodes = $request->kode_laravel_id;
        $mrps = $request->mrp_id;
        $workcenters = $request->workcenter_id;

        $count = 0;

        foreach ($users as $user) {
            foreach ($kodes as $kode) {
                foreach ($mrps as $mrp) {
                    foreach ($workcenters as $wc) {
                        // Prevent duplicates if needed, but for now simple create
                        MappingTable::firstOrCreate([
                            'user_sap_id' => $user,
                            'kode_laravel_id' => $kode,
                            'mrp_id' => $mrp,
                            'workcenter_id' => $wc,
                        ]);
                        $count++;
                    }
                }
            }
        }

        return redirect()->route('mapping.index')->with('success', "$count Mapping(s) created successfully.");
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:mapping_table,id',
        ]);

        $count = count($request->ids);
        MappingTable::whereIn('id', $request->ids)->delete();

        return redirect()->route('mapping.index')->with('success', "$count Mapping(s) deleted successfully.");
    }

    public function destroy($id)
    {
        $mapping = MappingTable::findOrFail($id);
        $mapping->delete();

        return redirect()->route('mapping.index')->with('success', 'Mapping deleted successfully.');
    }
}
