<?php

namespace App\Providers;

use App\Services\SidebarService;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    // ... (method register) ...

    public function boot(): void
    {
        View::composer('components.navigation.sidebar', function ($view) {
            $sidebarService = new SidebarService();
            $menuItems = [];
            
            $activeKode = Request::route('kode');
            $activeBuyer = Request::route('buyer');
            $activeStatus = Request::route('status');

            if (Request::routeIs(['manufaktur.dashboard.show', 'list.gr', 'wc.details'])) {
                $menuItems = $sidebarService->getDashboardMenu($activeKode);
            } 
            elseif (Request::routeIs('manufaktur.show.detail.data2')) {
                $menuItems = $sidebarService->getFilteredBuyerListMenu($activeKode);
            }
            elseif (Request::routeIs(['monitoring-pro.index', 'pro.detail.buyer'])) {
                $menuItems = $sidebarService->getFilteredBuyerMultiLevelMenu($activeKode, $activeBuyer, $activeStatus);
            }

            $view->with('menuItems', $menuItems);
        });
    }
}