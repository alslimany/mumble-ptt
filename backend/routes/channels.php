<?php

use App\Models\Device;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
 * Private channel for a specific device.
 * The authenticated JWT subject must be the device that owns this channel.
 */
Broadcast::channel('device.{deviceId}', function (Device $device, int $deviceId) {
    return (int) $device->id === $deviceId;
});
