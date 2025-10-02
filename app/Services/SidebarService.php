<?php

namespace App\Services;

use App\Models\Kode;
use App\Models\ProductionTData;
use App\Models\ProductionTData3;
use App\Models\SapUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class SidebarService
{
    /**
     * MENU TIPE 1: Untuk Dashboard & List GR
     */
    public function getDashboardMenu(?string $activeKode): array
    {
        $menuItems = [];
        if (!Auth::check()) return $menuItems;

        $user = Auth::user();
        $sapUser = null;
        $submenuItems = [];

        if ($user->role === 'admin') {
            $sapId = str_replace('@kmi.local', '', $user->email);
            $sapUser = SapUser::where('sap_id', $sapId)->first();
        } elseif ($user->role === 'korlap') {
            $nik = str_replace('@kmi.local', '', $user->email);
            $kode = Kode::where('nik', $nik)->first();
            if ($kode) $sapUser = $kode->sapUser;
        }

        if ($sapUser) {
            $uniqueKodes = $sapUser->kode()->orderBy('nama_bagian')->get()->unique('kode');
            foreach ($uniqueKodes as $kode) {
                $submenuItems[] = [
                    'name' => $kode->nama_bagian,
                    'route_name'   => 'manufaktur.dashboard.show',
                    'route_params' => ['kode' => $kode->kode],
                    'is_active'    => $activeKode === $kode->kode, // <-- Kunci 'is_active' ada di sini
                ];
            }
        }
        
        // Cek apakah ada submenu yang aktif untuk menandai parent-nya
        $isParentActive = collect($submenuItems)->contains('is_active', true);

        $items = [
            [
                'name' => 'Manufaktur',
                'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
                'submenu' => $submenuItems,
                'is_active' => $isParentActive, // <-- Kunci 'is_active' ada di sini
            ]
        ];
        
        return [['title' => 'Semua Task', 'items' => $items]];
    }

    /**
     * MENU TIPE 2: Untuk List Data (Daftar Buyer satu level)
     */
    public function getFilteredBuyerListMenu(?string $activeKode): array
    {
        if (!Auth::check() || !$activeKode) return [];

        $buyerListItems = [];
        $uniqueBuyers = $this->getUniqueBuyersByWerks($activeKode);

        foreach ($uniqueBuyers as $buyer) {
            $buyerListItems[] = [
                'name'         => $buyer->NAME1,
                'icon'         => 'fas fa-user-tag',
                'route_name'   => null,
                'route_params' => [],
                'is_active'    => false, // <-- Kunci 'is_active' ada di sini
            ];
        }
        
        return [['title' => 'Buyer di ' . $activeKode, 'items' => $buyerListItems]];
    }

    /**
     * MENU TIPE 3: Untuk Monitoring PRO (Buyer -> Status)
     */
    public function getFilteredBuyerMultiLevelMenu(?string $activeKode, ?string $activeBuyer, ?string $activeStatus): array
    {
        if (!Auth::check() || !$activeKode) return [];

        $today = Carbon::today()->toDateString(); // Format Y-m-d untuk query

        // --- QUERY UTAMA: Mengambil semua data dalam satu kali jalan ---
        $buyerStats = ProductionTData3::query()
            ->where('WERKSX', $activeKode)
            ->whereNotNull('NAME1')
            ->select(
                'NAME1',
                DB::raw('COUNT(*) as total_pro'),
                DB::raw("SUM(CASE WHEN GLTRP >= '{$today}' THEN 1 ELSE 0 END) as on_schedule_count"),
                DB::raw("SUM(CASE WHEN GLTRP < '{$today}' THEN 1 ELSE 0 END) as overdue_count"),
                DB::raw("SUM(CASE WHEN STATS = 'CRTD' THEN 1 ELSE 0 END) as created_count")
            )
            ->groupBy('NAME1')
            ->orderBy('NAME1', 'asc')
            ->get();

        // --- Membangun struktur menu dari hasil query ---
        $buyerMenuItems = [];
        foreach ($buyerStats as $buyer) {
            $isParentActive = $activeBuyer === $buyer->NAME1;

            // Submenu untuk setiap status
            $statusSubmenu = [
                [
                    'name' => 'On Schedule',
                    'route_name'   => 'pro.detail.buyer', // Gunakan route yang sama
                    'route_params' => ['kode' => $activeKode, 'buyerName' => $buyer->NAME1, 'status' => 'on-schedule'],
                    'badge' => $buyer->on_schedule_count,
                    'is_active' => $isParentActive && $activeStatus === 'on-schedule',
                ],
                [
                    'name' => 'Overdue',
                    'route_name'   => 'pro.detail.buyer',
                    'route_params' => ['kode' => $activeKode, 'buyerName' => $buyer->NAME1, 'status' => 'overdue'], 
                    'badge' => $buyer->overdue_count,
                    'is_active' => $isParentActive && $activeStatus === 'overdue',
                ],
                [
                    'name' => 'Created',
                    'route_name'   => 'pro.detail.buyer',
                    'route_params' => ['kode' => $activeKode, 'buyerName' => $buyer->NAME1, 'status' => 'created'], 
                    'badge' => $buyer->created_count,
                    'is_active' => $isParentActive && $activeStatus === 'created',
                ]
            ];

            $buyerMenuItems[] = [
                'name'        => $buyer->NAME1,
                'submenu'     => $statusSubmenu,
                'badge'       => $buyer->total_pro,
                'is_active'   => $isParentActive,
                'route_name'  => 'pro.detail.buyer',
                'route_params' => ['kode' => $activeKode, 'buyerName' => $buyer->NAME1],
            ];
        }
        
        return [['title' => 'Buyer Clients', 'items' => $buyerMenuItems]];
    }

    private function getUniqueBuyersByWerks(?string $werksFilter): Collection
    {
        return ProductionTData::where('WERKSX', $werksFilter)
            ->select('NAME1')->distinct()->whereNotNull('NAME1')
            ->orderBy('NAME1', 'asc')->get();
    }
}