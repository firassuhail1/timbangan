<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Laravel\Sanctum\HasApiTokens;
use App\Models\Update\Device;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $guarded = [];

    // Di model User
    // protected $fillable = ['username', 'line', 'role', 'password', 'status'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function ordersheets()
    {
        return $this->hasMany(Ordersheet::class, 'id_user');
    }

    public function timbanganRiwayats()
    {
        return $this->hasMany(Timbangan_riwayat::class, 'id_user');
    }

    public function ordersheetPackages()
    {
        return $this->hasMany(OrdersheetPackage::class, 'id_user');
    }

    public function ordersheetPackageWeights()
    {
        return $this->hasMany(OrdersheetPackageweight::class, 'id_user');
    }
}
