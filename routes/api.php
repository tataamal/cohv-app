<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\WorkInstructionApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'status' => 'OK',
            'message' => 'Application and Database are healthy.'
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'ERROR',
            'message' => 'Database connection failed.',
            'error' => $e->getMessage()
        ], 503);
    }
});

Route::middleware('auth:sanctum')->prefix('wi')->group(function () {
    
    Route::get('aufnr/unexpired', [WorkInstructionApiController::class, 'getUniqueUnexpiredAufnrs']); 
    Route::post('document/get', [WorkInstructionApiController::class, 'getWiDocumentByCode']); 
    Route::post('pro/complete', [WorkInstructionApiController::class, 'completeProStatus']); 

});

