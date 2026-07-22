<?php

use App\Http\Controllers\Api\V1\MobileAlertController;
use App\Http\Controllers\Api\V1\MobileAuthController;
use App\Http\Controllers\Api\V1\MobileBootstrapController;
use App\Http\Controllers\Api\V1\MobileDashboardController;
use App\Http\Controllers\Api\V1\MobileEventController;
use App\Http\Controllers\Api\V1\MobileMapController;
use App\Http\Controllers\Api\V1\MobileProfileController;
use App\Http\Controllers\Api\V1\MobileVehicleController;
use App\Http\Controllers\Api\V1\MobileVehicleDetailController;
use App\Http\Controllers\Api\V1\MobileVehicleTripController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/mobile')->name('api.v1.mobile.')->group(function (): void {
    Route::post('/auth/login', [MobileAuthController::class, 'login'])
        ->middleware('throttle:mobile-login')
        ->name('auth.login');
    Route::post('/auth/two-factor', [MobileAuthController::class, 'twoFactor'])
        ->middleware('throttle:mobile-two-factor')
        ->name('auth.two-factor');
    Route::post('/auth/refresh', [MobileAuthController::class, 'refresh'])
        ->middleware(['auth:sanctum', 'throttle:mobile-refresh'])
        ->name('auth.refresh');

    Route::middleware(['auth:sanctum', 'mobile.access', 'throttle:mobile-api'])->group(function (): void {
        Route::post('/auth/logout', [MobileAuthController::class, 'logout'])->name('auth.logout');
        Route::post('/auth/logout-all', [MobileAuthController::class, 'logoutAll'])->name('auth.logout-all');

        Route::get('/bootstrap', MobileBootstrapController::class)->name('bootstrap');
        Route::get('/me', MobileProfileController::class)->name('me');
        Route::get('/dashboard', MobileDashboardController::class)->name('dashboard');
        Route::get('/vehicles', [MobileVehicleController::class, 'index'])->name('vehicles.index');
        Route::get('/vehicles/{vehicle}', [MobileVehicleController::class, 'show'])
            ->whereNumber('vehicle')
            ->name('vehicles.show');
        Route::get('/vehicles/{vehicle}/details', MobileVehicleDetailController::class)
            ->middleware('client.permission:'.User::PERMISSION_MAP_VIEW)
            ->whereNumber('vehicle')
            ->name('vehicles.details');
        Route::get('/vehicles/{vehicle}/trips', MobileVehicleTripController::class)
            ->middleware('client.permission:'.User::PERMISSION_MAP_VIEW)
            ->whereNumber('vehicle')
            ->name('vehicles.trips');
        Route::get('/alerts', [MobileAlertController::class, 'index'])->name('alerts.index');
        Route::get('/events', [MobileEventController::class, 'index'])->name('events.index');
        Route::get('/map/vehicles', MobileMapController::class)
            ->middleware('client.permission:'.User::PERMISSION_MAP_VIEW)
            ->name('map.vehicles');
    });
});
