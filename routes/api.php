<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WilayahController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('wilayah')->group(function () {
    // Get all provinces
    // e.g., GET /api/wilayah/provinces
    Route::get('provinces', [WilayahController::class, 'provinces']);

    // Get cities by province ID
    // e.g., GET /api/wilayah/cities?province_id=31
    Route::get('cities', [WilayahController::class, 'cities']);

    // Get detailed data for a specific city, including districts and villages
    // e.g., GET /api/wilayah/cities/3171
    Route::get('cities/{cityId}', [WilayahController::class, 'showCity']);
});
