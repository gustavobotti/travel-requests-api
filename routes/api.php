<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TravelRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Health Check
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        $dbStatus = 'healthy';
    } catch (\Exception $e) {
        $dbStatus = 'unhealthy';
    }

    return response()->json([
        'status' => $dbStatus === 'healthy' ? 'healthy' : 'degraded',
        'service' => 'corporate-travel-api',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
        'checks' => [
            'database' => $dbStatus,
        ],
    ], $dbStatus === 'healthy' ? 200 : 503);
})->name('health');

Route::prefix('v1')->name('v1.')->group(function () {

    // Public routes
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
    });

    // Protected routes
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::get('/user', function (Request $request) {
            return $request->user();
        })->name('user');

        // Travel Requests CRUD
        Route::apiResource('travel-requests', TravelRequestController::class);

        // Special route for status update
        Route::patch('travel-requests/{travel_request}/status', [TravelRequestController::class, 'updateStatus'])
            ->name('travel-requests.update-status');
    });
});

