<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WcRelation;
use App\Models\workcenter;

class WcRelationController extends Controller
{
    public function index(Request $request)
    {
        $query = WcRelation::with(['wcAsal', 'wcTujuan']);

        if ($request->filled('search_wc')) {
            $searchWc = $request->search_wc;
            $query->whereHas('wcAsal', function($q) use ($searchWc) {
                $q->where('kode_wc', 'like', "%{$searchWc}%")
                  ->orWhere('description', 'like', "%{$searchWc}%");
            })->orWhereHas('wcTujuan', function($q) use ($searchWc) {
                $q->where('kode_wc', 'like', "%{$searchWc}%")
                  ->orWhere('description', 'like', "%{$searchWc}%");
            });
        }

        $relations = $query->latest()->get();
        $workcenters = workcenter::all();

        return view('wc-relation.index', compact('relations', 'workcenters'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'wc_asal_id' => 'required|array',
            'wc_asal_id.*' => 'exists:workcenters,id',
            'wc_tujuan_id' => 'required|array',
            'wc_tujuan_id.*' => 'exists:workcenters,id',
        ]);

        $asals = $request->wc_asal_id;
        $tujuans = $request->wc_tujuan_id;
        $status = 'compatible'; // Default

        $count = 0;

        foreach ($asals as $asal) {
            foreach ($tujuans as $tujuan) {
                // Prevent self-relation if needed, but sometimes compatible with self is ok.
                // Creating relation
                WcRelation::firstOrCreate([
                    'wc_asal_id' => $asal,
                    'wc_tujuan_id' => $tujuan,
                ], [
                    'status' => $status
                ]);
                $count++;
            }
        }

        return redirect()->route('wc-relation.index')->with('success', "$count Relation(s) created successfully.");
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:wc_relations,id',
        ]);

        $count = count($request->ids);
        WcRelation::whereIn('id', $request->ids)->delete();

        return redirect()->route('wc-relation.index')->with('success', "$count Relation(s) deleted successfully.");
    }

    public function destroy($id)
    {
        $relation = WcRelation::findOrFail($id);
        $relation->delete();

        return redirect()->route('wc-relation.index')->with('success', 'Relation deleted successfully.');
    }
}
