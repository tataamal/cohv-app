<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Models\Kode;
use App\Models\SapUser;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.navigation.sidebar', function ($view) {
            $menuItems = [];
            
            if (Auth::check()) {
                $user = Auth::user();
                $sapUser = null;
                $submenuItems = [];

                // --- LOGIKA UNTUK MENCARI SAP USER ---
                if ($user->role === 'admin') {
                    $sapId = str_replace('@kmi.local', '', $user->email);
                    $sapUser = SapUser::where('sap_id', $sapId)->first();
                } 
                elseif ($user->role === 'korlap') {
                    $nik = str_replace('@kmi.local', '', $user->email);
                    $kode = Kode::where('nik', $nik)->first();
                    if ($kode) {
                        $sapUser = $kode->sapUser;
                    }
                }

                // --- LOGIKA PEMBUATAN SUBMENU DINAMIS ---
                if ($sapUser) {
                    $allKodes = $sapUser->kode()->orderBy('nama_bagian')->get();
                    if ($allKodes->isNotEmpty()) {
                        foreach ($allKodes as $kode) {
                            $submenuItems[] = [
                                'name' => $kode->nama_bagian,
                                // [DIUBAH] Mengirim nama route dan parameter secara terpisah
                                'route_name'   => 'manufaktur.dashboard.show',
                                'route_params' => ['kode' => $kode->kode],
                                'badge' => $kode->kategori,
                            ];
                        }
                    }
                }
                
                // --- STRUKTUR MENU UTAMA ---
                $menuItems = [
                    [
                        'title' => 'Semua Task',
                        'items' => [
                            [
                                'name' => 'Manufaktur',
                                'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
                                'route_pattern' => 'manufaktur.*',
                                'submenu' => $submenuItems
                            ],
                        ]
                    ]
                ];
            }
            
            $view->with('menuItems', $menuItems);
        });
    }
}
