<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AlertRuleController;
use App\Http\Controllers\ClientPreviewController;
use App\Http\Controllers\CustomizationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DeviceCommandController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\MaintenancePlanController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MobileDownloadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileTwoFactorController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServerConsoleTicketController;
use App\Http\Controllers\ServerLogController;
use App\Http\Controllers\ServerMonitoringController;
use App\Http\Controllers\TrackerEventController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return redirect()->route('dashboard');
});

Route::get('/application', [MobileDownloadController::class, 'index'])->name('mobile.downloads.index');
Route::get('/application/android/{release}', [MobileDownloadController::class, 'download'])
    ->middleware('throttle:android-downloads')
    ->where('release', '[a-z0-9-]+')
    ->name('mobile.downloads.android');

Route::get('/lang/{locale}', function (string $locale): RedirectResponse {
    session(['locale' => $locale]);

    return back();
})->whereIn('locale', ['fr', 'en'])->name('lang.switch');

Route::get('/auth/csrf-token', fn () => response()->json([
    'token' => csrf_token(),
])->withHeaders([
    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
    'Pragma' => 'no-cache',
    'Expires' => '0',
]))->name('auth.csrf-token');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile/personal-information', [ProfileController::class, 'updatePersonal'])->name('profile.personal.update');
    Route::patch('/profile/email', [ProfileController::class, 'updateEmail'])->name('profile.email.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::post('/profile/two-factor', [ProfileTwoFactorController::class, 'enable'])->middleware('throttle:6,1')->name('profile.two-factor.enable');
    Route::post('/profile/two-factor/confirm', [ProfileTwoFactorController::class, 'confirm'])->middleware('throttle:10,1')->name('profile.two-factor.confirm');
    Route::delete('/profile/two-factor', [ProfileTwoFactorController::class, 'disable'])->middleware('throttle:6,1')->name('profile.two-factor.disable');
    Route::post('/profile/two-factor/recovery-codes', [ProfileTwoFactorController::class, 'showRecoveryCodes'])->middleware('throttle:6,1')->name('profile.two-factor.recovery-codes');
    Route::post('/profile/two-factor/recovery-codes/regenerate', [ProfileTwoFactorController::class, 'regenerateRecoveryCodes'])->middleware('throttle:6,1')->name('profile.two-factor.recovery-codes.regenerate');

    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/drivers', [DriverController::class, 'index'])->name('drivers.index');
    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::post('/vehicles/{vehicle}/engine-commands', DeviceCommandController::class)
        ->middleware('throttle:engine-control')
        ->name('vehicles.engine-commands.store');
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::get('/alerts/recent', [AlertController::class, 'recent'])->name('alerts.recent');
    Route::patch('/alerts/{alert}/acknowledge', [AlertController::class, 'acknowledge'])->name('alerts.acknowledge');
    Route::get('/events', [TrackerEventController::class, 'index'])->name('events.index');

    Route::middleware('client.permission:map.view')->group(function () {
        Route::get('/map', [MapController::class, 'index'])->name('map.index');
        Route::get('/map/devices', [MapController::class, 'devices'])->name('map.devices');
        Route::get('/vehicles/{vehicle}/tracker-details', [DeviceController::class, 'vehicleDetails'])->name('vehicles.tracker-details');
        Route::get('/vehicles/{vehicle}/trips', [DeviceController::class, 'vehicleTrips'])->name('vehicles.trips');
    });

    Route::middleware('client.permission:reports.generate')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::post('/reports/schedules', [ReportController::class, 'storeSchedule'])->name('reports.schedules.store');
        Route::delete('/reports/schedules/{scheduledReport}', [ReportController::class, 'destroySchedule'])->name('reports.schedules.destroy');
    });

    Route::middleware('client.permission:garages.manage')->group(function () {
        Route::resource('garages', GarageController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::middleware('client.permission:maintenance.manage')->group(function () {
        Route::patch('/maintenance/{maintenancePlan}/complete', [MaintenancePlanController::class, 'complete'])->name('maintenance.complete');
        Route::patch('/maintenance/{maintenancePlan}/toggle', [MaintenancePlanController::class, 'toggle'])->name('maintenance.toggle');
        Route::resource('maintenance', MaintenancePlanController::class)->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['maintenance' => 'maintenancePlan']);
    });

    Route::get('/addresses/search', AddressController::class)
        ->middleware('throttle:30,1')
        ->name('addresses.search');
});

Route::middleware(['auth', 'superadmin'])->group(function () {
    Route::get('/fleets/{fleet}/dashboard', [ClientPreviewController::class, 'store'])->name('fleets.dashboard');
    Route::post('/client-preview/exit', [ClientPreviewController::class, 'destroy'])->name('client-preview.exit');
    Route::resource('fleets', FleetController::class)->except(['show']);
    Route::resource('drivers', DriverController::class)->only(['store', 'update', 'destroy']);
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');
    Route::post('/trackers', [DeviceController::class, 'store'])->name('trackers.store');
    Route::get('/trackers', [DeviceController::class, 'index'])->name('trackers.index');
    Route::get('/trackers/{device}/details', [DeviceController::class, 'details'])->name('trackers.details');
    Route::post('/trackers/{device}/engine-commands', DeviceCommandController::class)
        ->middleware('throttle:engine-control')
        ->name('trackers.engine-commands.store');
    Route::get('/trackers/{device}/trips', [DeviceController::class, 'trips'])->name('trackers.trips');
    Route::put('/trackers/{device}', [DeviceController::class, 'update'])->name('trackers.update');
    Route::delete('/trackers/{device}', [DeviceController::class, 'destroy'])->name('trackers.destroy');
    Route::resource('alert-rules', AlertRuleController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/server-logs', [ServerLogController::class, 'index'])->name('server-logs.index');
    Route::get('/server-logs/content', [ServerLogController::class, 'content'])->name('server-logs.content');
    Route::post('/server-logs/console-ticket', ServerConsoleTicketController::class)
        ->middleware('throttle:5,1')
        ->name('server-logs.console-ticket');
    Route::get('/server-monitoring', [ServerMonitoringController::class, 'index'])->name('server-monitoring.index');
    Route::get('/server-monitoring/metrics', [ServerMonitoringController::class, 'metrics'])->name('server-monitoring.metrics');
    Route::get('/customization', [CustomizationController::class, 'index'])->name('customization.index');
    Route::patch('/customization', [CustomizationController::class, 'update'])->name('customization.update');
});
