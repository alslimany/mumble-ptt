<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Recording extends Model
{
    /** @use HasFactory<\Database\Factories\RecordingFactory> */
    use HasFactory;

    protected $fillable = ['organization_id', 'room_id', 'file_path', 'duration', 'started_at', 'ended_at'];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    protected $appends = ['url'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the public URL for the recording file.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}
