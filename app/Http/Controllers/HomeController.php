<?php

namespace App\Http\Controllers;

use App\Models\Update\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

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

        $availableDevices = Device::where(function ($q) {
                // 1. Kosong & online
                $q->whereNull('user_id')
                ->where('status', 'online');
            })
            ->orWhere(function ($q) use ($autoSelectedEspId) {
                // 2. Device milik user ini sendiri yang sedang in_use
                if ($autoSelectedEspId) {
                    $q->where('esp_id', $autoSelectedEspId)
                    ->where('status', 'in_use');
                } else {
                    // Jika tidak ada autoSelected, skip kondisi ini dengan impossible clause
                    $q->whereRaw('1 = 0');
                }
            })
            ->orWhere(function ($q) {
                // 3. In_use tapi sudah timeout (> 5 menit) - dianggap tersedia
                $q->where('status', 'in_use')
                ->whereNotNull('user_id')
                ->where('last_seen_at', '<', now()->subMinutes(5));
            })
            ->orderBy('esp_id')
            ->get();

        Log::info('available devices : ' . $availableDevices);

        return view('auth.login', compact(
            'availableDevices',
            'autoSelectedEspId'
        ));
    }
}
