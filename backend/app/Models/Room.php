<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    /** @use HasFactory<\Database\Factories\RoomFactory> */
    use HasFactory;

    protected $fillable = ['organization_id', 'name', 'mumble_channel_id'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function devices()
    {
        return $this->belongsToMany(Device::class, 'room_devices')
            ->withPivot('can_switch')
            ->withTimestamps();
    }
}
