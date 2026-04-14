<?php

namespace App\Models\Update;

use App\Models\Ordersheet;
use App\Models\OrdersheetPackage;
use App\Models\OrdersheetPackageweight;
use App\Models\Timbangan_riwayat;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $guarded = [];
    protected $casts = [
        'is_online' => 'boolean',
        'last_seen_at' => 'datetime',
        'last_online_at' => 'datetime',
    ];

    public function updates()
    {
        return $this->hasMany(Device_update::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ordersheets()
    {
        return $this->hasMany(Ordersheet::class);
    }

    public function timbanganRiwayats()
    {
        return $this->hasMany(Timbangan_riwayat::class);
    }

    public function ordersheetPackages()
    {
        return $this->hasMany(OrdersheetPackage::class);
    }

    public function ordersheetPackageWeights()
    {
        return $this->hasMany(OrdersheetPackageweight::class);
    }

    public function pendingFirmware()
    {
        return $this->belongsTo(Firmwares::class, 'pending_firmware_id');
    }
}
