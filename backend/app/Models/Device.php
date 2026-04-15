<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Device extends Model implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\DeviceFactory> */
    use HasFactory;

    protected $fillable = ['organization_id', 'unique_identifier', 'name', 'model', 'is_active'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'room_devices')
            ->withPivot('can_switch')
            ->withTimestamps();
    }

    public function gpsLogs()
    {
        return $this->hasMany(GpsLog::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
