<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\MumbleIceService;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class DeviceController extends Controller
{
    public function register(Request $request, MumbleIceService $mumbleIceService)
    {
        $device = Device::where('unique_identifier', $request->input('unique_identifier'))->first();

        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        if (!$device->organization_id) {
            return response()->json(['error' => 'Device has no organization'], 403);
        }

        $organization = $device->organization;
        $settings = $organization?->settings ?? [];

        if (empty($settings['mumble_server_id'])) {
            $server = $mumbleIceService->createVirtualServer($organization->id);
            $settings['mumble_server_id'] = $server['serverId'];
            $settings['mumble_port'] = $server['port'];
            $settings['mumble_server_password'] = $server['password'];
            $organization->settings = $settings;
            $organization->save();
        }

        $mumblePassword = substr(hash('sha256', $device->unique_identifier.'|'.config('app.key')), 0, 24);

        if (!$device->mumble_user_id) {
            $device->mumble_user_id = $mumbleIceService->registerUser(
                (int) $settings['mumble_server_id'],
                $device->unique_identifier,
                $mumblePassword,
            );
            $device->save();
        }

        $token = JWTAuth::fromUser($device);

        return response()->json([
            'token' => $token,
            'mumble_host' => config('app.mumble_host', '127.0.0.1'),
            'mumble_port' => $settings['mumble_port'] ?? config('app.mumble_port', 64738),
            'mumble_username' => $device->unique_identifier,
            'mumble_password' => $mumblePassword,
        ]);
    }
}
