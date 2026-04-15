<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpsLog extends Model
{
    /** @use HasFactory<\Database\Factories\GpsLogFactory> */
    use HasFactory;

    protected $fillable = ['device_id', 'latitude', 'longitude', 'recorded_at'];

    protected $casts = ['recorded_at' => 'datetime'];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
