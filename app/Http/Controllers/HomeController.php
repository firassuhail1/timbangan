<?php

namespace App\Http\Controllers;

use App\Models\Update\Device;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;

class HomeController extends Controller
{
    public function index()
    {
        $autoSelectedEspId = null;
        $lastUsername = Cookie::get('username');

        if ($lastUsername) {
            $user = User::where('username', $lastUsername)->first();
            if ($user) {
                $device = Device::where('user_id', $user->id)
                    ->where('status', 'in_use')
                    ->first();

                if ($device) {
                    $autoSelectedEspId = $device->esp_id;
                }
            }
        }

        // Tampil semua device, sertakan info user yang sedang pakai
        $availableDevices = Device::with('user')
            ->orderBy('name')
            ->get()
            ->map(function ($device) {
                // Kalau in_use tapi sudah timeout > 5 menit, anggap online
                if (
                    $device->status === 'in_use' &&
                    $device->last_seen_at &&
                    $device->last_seen_at->lt(now()->subMinutes(5))
                ) {
                    $device->status  = 'online';
                    $device->user_id = null;
                    $device->user    = null;
                }
                return $device;
            });

        return view('auth.login', compact(
            'availableDevices',
            'autoSelectedEspId'
        ));
    }
}
