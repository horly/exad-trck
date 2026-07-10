<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AlertRuleController;
use App\Http\Controllers\CustomizationController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServerLogController;
use App\Http\Controllers\ServerMonitoringController;
use App\Http\Controllers\SubscriptionPlanController;
use App\Http\Controllers\TrackerEventController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()->isSuperadmin()
        ? redirect()->route('dashboard')
        : redirect()->route('fleets.index');
});

Route::get('/lang/{locale}', function (string $locale): RedirectResponse {
    session(['locale' => $locale]);

    return back();
})->whereIn('locale', ['fr', 'en'])->name('lang.switch');

Route::middleware(['auth', 'superadmin'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/subscriptions', [SubscriptionPlanController::class, 'index'])->name('subscriptions.index');
    Route::patch('/subscriptions', [SubscriptionPlanController::class, 'update'])->name('subscriptions.update');
    Route::resource('fleets', FleetController::class)->except(['show']);
    Route::resource('vehicles', VehicleController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/trackers/{device}/details', [DeviceController::class, 'details'])->name('trackers.details');
    Route::get('/trackers/{device}/trips', [DeviceController::class, 'trips'])->name('trackers.trips');
    Route::resource('trackers', DeviceController::class)->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['trackers' => 'device']);
    Route::get('/map', [MapController::class, 'index'])->name('map.index');
    Route::get('/map/devices', [MapController::class, 'devices'])->name('map.devices');
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::get('/alerts/recent', [AlertController::class, 'recent'])->name('alerts.recent');
    Route::patch('/alerts/{alert}/acknowledge', [AlertController::class, 'acknowledge'])->name('alerts.acknowledge');
    Route::resource('alert-rules', AlertRuleController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/events', [TrackerEventController::class, 'index'])->name('events.index');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::post('/reports/schedules', [ReportController::class, 'storeSchedule'])->name('reports.schedules.store');
    Route::delete('/reports/schedules/{scheduledReport}', [ReportController::class, 'destroySchedule'])->name('reports.schedules.destroy');
    Route::get('/server-logs', [ServerLogController::class, 'index'])->name('server-logs.index');
    Route::get('/server-logs/content', [ServerLogController::class, 'content'])->name('server-logs.content');
    Route::get('/server-monitoring', [ServerMonitoringController::class, 'index'])->name('server-monitoring.index');
    Route::get('/server-monitoring/metrics', [ServerMonitoringController::class, 'metrics'])->name('server-monitoring.metrics');
    Route::get('/customization', CustomizationController::class)->name('customization.index');
});
