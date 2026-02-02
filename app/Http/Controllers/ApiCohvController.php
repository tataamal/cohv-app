<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSap;
use App\Models\MappingTable;

class ApiCohvController extends Controller
{
    public function getMappingBySapId(Request $request)
    {
        $sap_id = $request->input('sap_id');

        if (!$sap_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'SAP ID is required'
            ], 400);
        }

        $user = UserSap::select('id', 'user_sap', 'name')
            ->where('user_sap', $sap_id)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        // New mapping: read from MappingTable which links user_sap -> kode_laravel -> mrp
        if (strtolower($sap_id) === 'auto_email') {
            $mappings = MappingTable::with(['kodeLaravel', 'mrp'])->get();
        } else {
            $mappings = MappingTable::with(['kodeLaravel', 'mrp'])
                ->where('user_sap_id', $user->id)
                ->get();
        }

        // Normalize into kode entries: kode, kategori (plant), nama_bagian (description), mrps
        $groupedKodes = $mappings->groupBy(function ($m) {
            return optional($m->kodeLaravel)->laravel_code ?? ('kode_'.$m->kode_laravel_id);
        })->map(function ($group) {
            $first = $group->first();
            $kodeModel = optional($first->kodeLaravel);
            $mrps = $group->map(function ($m) {
                return optional($m->mrp)->mrp;
            })->filter()->unique()->values();

            return [
                'kode' => $kodeModel->laravel_code ?? null,
                'kategori' => $kodeModel->plant ?? (optional($first->mrp)->plant ?? 'unknown'),
                'nama_bagian' => $kodeModel->description ?? null,
                'mrps' => $mrps
            ];
        });

        $groupedByCategory = $groupedKodes->groupBy('kategori')->map(function ($group, $kategori) {
            return [
                'kategori' => $kategori,
                'kodes' => $group->map(function ($item) {
                    return [
                        'kode' => $item['kode'],
                        'nama_bagian' => $item['nama_bagian'],
                        'mrps' => $item['mrps']
                    ];
                })->values()
            ];
        })->values();

        return [
            'sap_id' => $user->user_sap,
            'nama' => $user->name,
            'details' => $groupedByCategory
        ];
    }
}
