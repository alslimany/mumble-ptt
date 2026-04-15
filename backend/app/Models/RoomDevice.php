<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomDevice extends Model
{
    protected $table = 'room_devices';

    protected $fillable = ['device_id', 'room_id', 'can_switch'];
}
