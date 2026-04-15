<?php

use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\GpsController;
use App\Http\Controllers\Api\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::post('/device/register', [DeviceController::class, 'register']);
Route::post('/device/gps', [GpsController::class, 'store']);
Route::get('/organizations/{id}/devices', [OrganizationController::class, 'devices']);
