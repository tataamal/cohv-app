<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Data3Controller;
use App\Http\Controllers\Data1Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\KorlapController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Data4Controller;
use App\Http\Controllers\ManufactController;
use App\Http\Controllers\NoteController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Routing untuk user yang belum melakukan register
Route::middleware('guest')->group(function (){

    Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
    // Routing untuk login masing-masing role
    Route::post('/login/korlap', [LoginController::class, 'loginKorlap'])->name('login.korlap');
    Route::post('/login/admin', [LoginController::class, 'loginAdmin'])->name('login.admin');
    // routing untuk mendapatkan sap_id otomatis ketika korlap login
    Route::post('api/get-sap-user-id', [LoginController::class, 'getSapUserByKode'])->name('get_sap_user_id');
    
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
    Route::post('/changeWC', [Data1Controller::class,'changeWC'])->name('change-wc');
    Route::post('/changePV', [Data1Controller::class,'changePV'])->name('change-pv');

    // route untuk kelola gr
    Route::get('gr/{kode}', [ManufactController::class, 'list_gr'])->name('list.gr');

});
