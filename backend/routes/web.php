<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeviceController as AdminDeviceController;
use App\Http\Controllers\Admin\LiveMapController;
use App\Http\Controllers\Admin\LocationHistoryController;
use App\Http\Controllers\Admin\RecordingController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Devices
        Route::get('/devices', [AdminDeviceController::class, 'index'])->name('devices.index');
        Route::put('/devices/{device}/rooms', [AdminDeviceController::class, 'assignRooms'])->name('devices.assign-rooms');
        Route::put('/devices/{device}', [AdminDeviceController::class, 'update'])->name('devices.update');

        // Rooms
        Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

        // Live Map
        Route::get('/live-map', [LiveMapController::class, 'index'])->name('live-map.index');

        // Location History
        Route::get('/location-history', [LocationHistoryController::class, 'index'])->name('location-history.index');

        // Recordings
        Route::get('/recordings', [RecordingController::class, 'index'])->name('recordings.index');
    });
});

require __DIR__.'/auth.php';
