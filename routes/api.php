<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AdministrativeRegionController;
use App\Http\Controllers\Api\V1\PostalCodeController;
use App\Http\Controllers\Api\V1\GeographicBoundaryController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\StatisticsController;
use App\Http\Controllers\Api\V1\ExportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    // Administrative Regions
    Route::get('/regions', [AdministrativeRegionController::class, 'index']);
    Route::get('/regions/{id}', [AdministrativeRegionController::class, 'show']);
    Route::get('/regions/{id}/children', [AdministrativeRegionController::class, 'children']);
    Route::get('/regions/{id}/ancestors', [AdministrativeRegionController::class, 'ancestors']);

    // Postal Codes
    Route::get('/postal-codes', [PostalCodeController::class, 'index']);
    Route::get('/postal-codes/{code}', [PostalCodeController::class, 'show']);
    Route::post('/postal-codes/bulk-lookup', [PostalCodeController::class, 'bulkLookup']);

    // Geographic Boundaries
    Route::get('/boundaries', [GeographicBoundaryController::class, 'index']);
    Route::get('/boundaries/{region_id}', [GeographicBoundaryController::class, 'show']);

    // Search and Discovery
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/autocomplete', [SearchController::class, 'autocomplete']);

    // Statistics and Analytics
    Route::get('/stats', [StatisticsController::class, 'index']);
    Route::get('/stats/{region_id}', [StatisticsController::class, 'regionStats']);

    // Data Export
    Route::get('/export', [ExportController::class, 'export']);

    // Data Updates
    Route::get('/updates', [StatisticsController::class, 'updates']);
});