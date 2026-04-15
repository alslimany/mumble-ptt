<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory;

    protected $fillable = ['name', 'settings'];

    protected $casts = ['settings' => 'array'];

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
