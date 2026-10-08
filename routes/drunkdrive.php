<?php

use App\Http\Controllers\Api\DrunkDrive\AuthController;
use App\Http\Controllers\Api\DrunkDrive\DriverController;
use App\Http\Controllers\Api\DrunkDrive\FareController;
use App\Http\Controllers\Api\DrunkDrive\NearbyController;
use App\Http\Controllers\Api\DrunkDrive\StoreController;
use App\Http\Controllers\Api\DrunkDrive\TripController;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| DrunkDrive API (clean domain on top of existing Sanctum / RBAC)
| Prefix: /api/v1/drunkdrive
|--------------------------------------------------------------------------
*/

Route::prefix('v1/drunkdrive')->group(function () {
    // Auth
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/register-driver', [AuthController::class, 'registerDriver']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Public fare helpers
    Route::get('/fare/formula', [FareController::class, 'formula']);
    Route::post('/fare/quote', [FareController::class, 'quote']);

    // Public active store list (read)
    Route::get('/stores', [StoreController::class, 'index']);
    Route::get('/stores/{store}', [StoreController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Nearby drivers — any authenticated DrunkDrive user
        Route::get('/drivers/nearby', [NearbyController::class, 'drivers']);

        // Driver self-service
        Route::middleware(RoleMiddleware::class.':driver')->group(function () {
            Route::get('/driver/profile', [DriverController::class, 'profile']);
            Route::post('/driver/licence', [DriverController::class, 'updateLicence']);
            Route::put('/driver/licence', [DriverController::class, 'updateLicence']);
            Route::post('/driver/location', [DriverController::class, 'updateLocation']);
            Route::put('/driver/availability', [DriverController::class, 'updateAvailability']);
        });

        // Trips — riders
        Route::middleware(RoleMiddleware::class.':user,driver,admin,super_admin,superadmin')->group(function () {
            Route::post('/trips', [TripController::class, 'store']);
            Route::get('/trips/history', [TripController::class, 'history']);
            Route::get('/trips/{trip}', [TripController::class, 'show']);
            Route::patch('/trips/{trip}/status', [TripController::class, 'updateStatus']);
        });

        // Admin / superadmin store + driver management
        Route::middleware(RoleMiddleware::class.':admin,super_admin,superadmin')->group(function () {
            Route::post('/stores', [StoreController::class, 'store']);
            Route::put('/stores/{store}', [StoreController::class, 'update']);
            Route::patch('/stores/{store}', [StoreController::class, 'update']);
            Route::delete('/stores/{store}', [StoreController::class, 'destroy']);

            Route::get('/admin/drivers', [DriverController::class, 'index']);
            Route::patch('/admin/drivers/{driverProfile}/status', [DriverController::class, 'approve']);
        });
    });
});
