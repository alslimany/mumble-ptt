<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GpsLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GpsController extends Controller
{
    public function store(Request $request)
    {
        $points = $request->input('points', []);

        $now = now();
        $records = array_map(function ($point) use ($now) {
            return [
                'device_id' => $point['device_id'],
                'latitude' => $point['latitude'],
                'longitude' => $point['longitude'],
                'recorded_at' => $point['recorded_at'] ?? $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $points);

        GpsLog::insert($records);

        return response()->json(['count' => count($records)], 201);
    }
}
