<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        $user = \App\Models\SapUser::select('id', 'sap_id', 'nama')
            ->where('sap_id', $sap_id)
            ->with(['kodes' => function ($query) {
                $query->select('id', 'sap_user_id', 'kode', 'kategori');
            }, 'kodes.mrps' => function ($query) {
                $query->select('id', 'kode', 'mrp');
            }])
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        $groupedKodes = $user->kodes->groupBy('kode')->map(function ($group) {
            $first = $group->first();
            return [
                'kode' => $first->kode,
                'kategori' => $first->kategori,
                'mrps' => $group->pluck('mrps')->flatten()->pluck('mrp')->unique()->values()
            ];
        });

        $groupedByCategory = $groupedKodes->groupBy('kategori')->map(function ($group, $kategori) {
            return [
                'kategori' => $kategori,
                'kodes' => $group->map(function ($item) {
                    return [
                        'kode' => $item['kode'],
                        'mrps' => $item['mrps']
                    ];
                })->values()
            ];
        })->values();

        return [
            'sap_id' => $user->sap_id,
            'nama' => $user->nama,
            'details' => $groupedByCategory
        ];
    }
}
