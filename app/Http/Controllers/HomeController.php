<?php

namespace App\Http\Controllers;

use App\Models\Update\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class HomeController extends Controller
{
    public function index()
    {
        $availableDevices = Device::where(function ($query) {
                $query->where('status', 'online')
                    ->orWhere(function ($q) {
                        $q->where('status', 'in_use')
                            ->where('user_id', Auth::id()); // device milik user tetap muncul
                    });
            })
            ->orderBy('name')
            ->get();

        $lastUsedDevice = Device::where('user_id', Auth::id())
                        ->orderByDesc('last_seen_at')
                        ->first();

        $lastUsedEspId = $lastUsedDevice?->esp_id;

        // dd($lastUsedEspId);

        return view('auth.login', compact('availableDevices', 'lastUsedEspId'));
    }
}
