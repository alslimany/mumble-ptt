<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class DeviceController extends Controller
{
    public function register(Request $request)
    {
        $device = Device::where('unique_identifier', $request->input('unique_identifier'))->first();

        if (!$device) {
            return response()->json(['error' => 'Device not found'], 404);
        }

        if (!$device->organization_id) {
            return response()->json(['error' => 'Device has no organization'], 403);
        }

        $token = JWTAuth::fromUser($device);

        return response()->json([
            'token' => $token,
            'mumble_host' => config('app.mumble_host', '127.0.0.1'),
            'mumble_port' => config('app.mumble_port', 64738),
            'mumble_username' => $device->unique_identifier,
            'mumble_password' => '',
        ]);
    }
}
