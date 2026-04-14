<?php

namespace App\Models\Update;

use Illuminate\Database\Eloquent\Model;

class Firmwares extends Model
{
    protected $guarded = [];

    protected $casts = [
        'released_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function devices()
    {
        return $this->hasMany(Device::class, 'pending_firmware_id');
    }

    public function updates()
    {
        return $this->hasMany(Device_update::class);
    }
}
