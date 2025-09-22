<?php

use App\Http\Controllers\Data3Controller;
use App\Http\Controllers\Data1Controller;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\bulkController;
use App\Http\Controllers\Data4Controller;
use App\Http\Controllers\ManufactController;
use App\Http\Controllers\WcCompatibilityController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [LoginController::class, 'checkAuth']);
// Routing untuk user yang belum melakukan register
Route::middleware('guest')->group(function (){
    Route::get('/login', [LoginController::class, 'showLoginForm'])->middleware('clear.cookies')->name('login');
    Route::post('/login/admin', [LoginController::class, 'loginAdmin'])->name('login.admin');
});

Route::middleware('auth')->group(function (){

    // Routing Admin
    Route::get('/dashboard-landing', [AdminController::class, 'AdminDashboard'])->name('dashboard-landing');
    Route::get('/dashboard/{kode}', [AdminController::class, 'index'])->name('dashboard.show');
    Route::get('data2/{kode}', [ManufactController::class, 'DetailData2'])->name('detail.data2');
    Route::get('data2/detail/{kode}', [ManufactController::class, 'showDetail'])->name('show.detail.data2');

    // Routing Korlap

    // Routing Manufact
    Route::post('/create_prod_order', [ManufactController::class, 'convertPlannedOrder'])->name('convert-button');
    Route::post('/component/add', [Data4Controller::class, 'addComponent'])->name('component.add');
    Route::post('/component/delete-bulk', [Data4Controller::class, 'deleteBulkComponents'])->name('component.delete.bulk');

    // Routing Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // route untuk kelola T-DATA3
    Route::get('/release-order/{aufnr}',[Data3Controller::class, 'releaseOrderDirect'])->name('release.order.direct');
    Route::post('/reschedule', [Data3Controller::class,'reschedule'])->name('reschedule.store');
    Route::post('/teco-order', [Data3Controller::class, 'tecoOrder'])->name('order.teco');
    Route::post('/read-pp-order', [Data3Controller::class, 'readPpOrder'])->name('order.readpp');

    // route untuk kelola T-DATA1
    Route::post('/change-wc-pro', [Data1Controller::class,'changeWC'])->name('change-wc-pro');
    Route::post('/changePV', [Data1Controller::class,'changePV'])->name('change-pv');

    // route untuk kelola gr
    Route::get('gr/{kode}', [ManufactController::class, 'list_gr'])->name('list.gr');

    // Route untuk kelola PRO
    Route::post('/refresh-pro', [ManufactController::class, 'refreshPro'])->name('refresh.pro');
    // Route untuk menampilkan halaman detail PRO berdasarkan workcenter
    Route::get('gr/{kode}', [ManufactController::class, 'list_gr'])->name('list.gr');
    Route::get('/wc-mapping', [WcCompatibilityController::class, 'index']);
    Route::get('/wc-mapping/details/{kode}/{wc}', [WcCompatibilityController::class, 'showDetails'])->name('wc.details');
    Route::post('/changeWC/{kode}/{wcTujuan}', [WcCompatibilityController::class,'changeWorkcenter'])->name('change-wc-drag');
    Route::post('/changePV/{kode}/{wc}', [WcCompatibilityController::class,'changePV'])->name('change-pv-drag');

    // Routing untuk Bulk Function
    Route::post('/bulk-refresh-pro', [bulkController::class, 'handleBulkRefresh'])->name('bulk-refresh.store');
    Route::post('/bulk-teco-process', [bulkController::class, 'processBulkTeco'])->name('bulk.teco.process');
    Route::post('/bulk-read-pp-process', [bulkController::class, 'processBulkReadPp'])->name('bulk.readpp.process');
    Route::post('/bulk-schedule-process', [bulkController::class, 'processBulkSchedule'])->name('bulk.schedule.process');
    Route::post('/bulk-change-and-refresh', [bulkController::class, 'handleBulkChangeAndRefresh']);

});
