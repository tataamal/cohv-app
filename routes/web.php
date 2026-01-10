<?php

use App\Http\Controllers\Data3Controller;
use App\Http\Controllers\Data1Controller;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\adminController;
use App\Http\Controllers\bulkController;
use App\Http\Controllers\Data4Controller;
use App\Http\Controllers\ManufactController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\WcCompatibilityController;
use App\Http\Controllers\MonitoringProController;
use App\Http\Controllers\ProTransactionController;
use App\Http\Controllers\CogiController;
use App\Http\Controllers\CreateWiController;

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
    Route::prefix('manufaktur')->name('manufaktur.')->group(function () {
        Route::get('/dashboard/{kode}', [adminController::class, 'index'])->name('dashboard.show');
        Route::get('data2/{kode}', [ManufactController::class, 'DetailData2'])->name('detail.data2');
        Route::get('data2/detail/{kode}', [ManufactController::class, 'showDetail'])->name('show.detail.data2');
        // Route::post('/pro/multi-search', [adminController::class, 'showMultiProDetail'])->name('pro.multi-search');
        Route::post('/pro/multi-search', [adminController::class, 'handleMultiProSearch'])->name('pro.search.submit');
        Route::get('/pro/hasil-pencarian', [adminController::class, 'showMultiProResult'])->name('pro.search.hasil');
    });

    // Routing Admin
    Route::get('/dashboard-landing', [adminController::class, 'AdminDashboard'])->name('dashboard-landing');
    Route::get('/api/pro-details/{status}', [adminController::class, 'getProDetails'])->name('pro.details');

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
    Route::post('/gr/print-pdf', [ManufactController::class, 'printPdf'])->name('gr.print_pdf');
    Route::post('/gr/print-set-pdf', [ManufactController::class, 'printSetPdf'])->name('gr.print_set_pdf');

    // Route untuk kelola PRO
    Route::post('/refresh-pro', [ManufactController::class, 'refreshPro'])->name('refresh.pro');
    // Route untuk menampilkan halaman detail PRO berdasarkan workcenter
    Route::get('gr/{kode}', [ManufactController::class, 'list_gr'])->name('list.gr');
    Route::get('/wc-mapping', [WcCompatibilityController::class, 'index']);
    Route::get('/wc-mapping/details/{kode}/{wc}', [WcCompatibilityController::class, 'showDetails'])->name('wc.details');
    Route::post('/changeWC', [Data1Controller::class, 'changeWc'])->name('wc.change.single');
    Route::post('/components/update', [Data4Controller::class, 'update'])->name('components.update');
    Route::post('/change-order-quantity', [Data3Controller::class, 'changeQuantity'])
     ->name('order.changeQuantity');

    // Routing untuk Bulk Function
    Route::post('/bulk-refresh-pro', [bulkController::class, 'handleBulkRefresh'])->name('bulk-refresh.store');
    Route::post('/bulk-teco-process', [bulkController::class, 'processBulkTeco'])->name('bulk.teco.process');
    Route::post('/bulk-read-pp-process', [bulkController::class, 'processBulkReadPp'])->name('bulk.readpp.process');
    Route::post('/bulk-schedule-process', [bulkController::class, 'processBulkSchedule'])->name('bulk.schedule.process');
    Route::post('/bulk-change-and-refresh', [bulkController::class, 'handleBulkChangeAndRefresh']);
    Route::post('/changeWCBulk/{kode}/{wc_tujuan}', [Data1Controller::class, 'changeWcBulk'])->name('wc.change.bulk');
    Route::post('/changeWCBulkStream/{kode}/{wc_tujuan}', [Data1Controller::class, 'changeWcBulkStream'])->name('wc.change.bulk.stream');
    Route::post('/bulk-change-quantity', [bulkController::class, 'bulkChangeQuantity'])->name('order.bulkChangeQuantity');

    Route::get('/monitoring-pro/{kode}', [MonitoringProController::class, 'index'])->name('monitoring-pro.index');
    Route::get('/monitoring-pro/{kode}/filter', [MonitoringProController::class, 'filter'])->name('monitoring-pro.filter');
    Route::get('/monitoring-pro/{buyer}/{status}', [MonitoringProController::class, 'show'])->name('monitoring-pro.show');
    Route::get('/pro-details/{kode}/{buyerName}/{status?}', [MonitoringProController::class, 'showByBuyer'])->name('pro.detail.buyer');

    // Cogi Routing
    Route::get('/monitoring/cogi/{kode}', [CogiController::class, 'index'])->name('cogi.report');
    Route::get('/show-stock', [Data4Controller::class, 'show_stock']);
    Route::get('/cogi/dashboard', [CogiController::class, 'getDashboardData'])->name('api.cogi.dashboard');
    Route::post('/api/cogi/sync', [CogiController::class, 'syncCogiData'])->name('api.cogi.sync');
    Route::get('cogi/details/{plantCode}', [CogiController::class, 'getCogiDetails'])->name('api.cogi.details');

    // Search Stock Route
    Route::get('/search-stock', [StockController::class, 'index'])->name('search.stock');
    Route::get('/search-stock/results', [StockController::class, 'show_stock'])->name('search.stock.show');
    Route::get('/search-stock/data', [StockCOntroller::class, 'show_stock'])->name('search.stock.data');

    // Create WI
    Route::get('/create-wi/get-remark-history', [CreateWiController::class, 'getRemarkHistory'])->name('create-wi.get-remark-history');
    Route::post('/create-wi/refresh/{kode}', [CreateWiController::class, 'refreshData'])->name('create-wi.refresh');
    Route::post('/create-wi/stream-schedule', [CreateWiController::class, 'streamSchedule'])->name('create-wi.stream-schedule');
    Route::get('/create-wi/{kode}', [CreateWiController::class, 'index'])->name('create-wi.index');
    Route::get('work-instruction/create/{kode}', [CreateWiController::class, 'index'])->name('wi.create');
    Route::post('work-instruction/save', [CreateWiController::class, 'saveWorkInstruction'])->name('wi.save');
    Route::get('/wi/history/{kode}', [CreateWiController::class, 'history'])->name('wi.history');
    Route::post('/history-wi/update-qty', [CreateWiController::class, 'updateQty'])->name('history-wi.update-qty');
    Route::post('/work-instruction/history/print/{kode}', [CreateWiController::class, 'printPdf'])->name('wi.print-pdf');
    Route::post('/work-instruction/print-single', [CreateWiController::class, 'printSingleWi'])->name('wi.print-single');
    Route::post('/work-instruction/print-expired', [CreateWiController::class, 'printExpiredReport'])->name('wi.print-expired-report');
    Route::post('/work-instruction/print-completed', [CreateWiController::class, 'printCompletedReport'])->name('wi.print-completed-report');
    Route::get('/work-instruction/history/preview/{kode}', [CreateWiController::class, 'previewLog'])->name('wi.preview-log');
    Route::post('/work-instruction/history/email/{kode}', [CreateWiController::class, 'emailLog'])->name('wi.email-log');
    Route::post('/work-instruction/delete', [CreateWiController::class, 'delete'])->name('wi.delete'); // Added Delete Route
    Route::post('/work-instruction/history/print-log-nik/{kode}', [CreateWiController::class, 'printLogByNik'])->name('wi.print-log-nik');
    
    // Edit WI Routes
    Route::get('/work-instruction/available-items/{kode}', [CreateWiController::class, 'getAvailableItems'])->name('wi.available-items');
    Route::get('/work-instruction/get-employees/{kode}', [CreateWiController::class, 'getEmployees'])->name('wi.get-employees');
    Route::post('/work-instruction/add-item', [CreateWiController::class, 'addItem'])->name('wi.add-item');
    Route::post('/work-instruction/add-item-batch', [CreateWiController::class, 'addItemBatch'])->name('wi.add-item-batch');
    Route::post('/work-instruction/remove-item', [CreateWiController::class, 'removeItem'])->name('wi.remove-item');
    Route::get('/work-instruction/fetch-all-ids/{kode}', [CreateWiController::class, 'fetchAllIds'])->name('wi.fetch-all-ids');
    
    // Outstanding Reservasi Route
    // Route::get('/outstanding-reservasi/{kode}', [OutstandingReservasiController::class, 'index'])->name('outstanding.reservasi');

});
