<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Update\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceLoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'esp_id' => 'required|string|exists:devices,esp_id',
        ]);

        $espId = $request->esp_id;

        // Cari device yang sedang in_use (ada user_id)
        $device = Device::where('esp_id', $espId)
                        ->where('status', 'in_use')
                        ->whereNotNull('user_id')
                        ->first();

        if (!$device) {
            return response()->json([
                'error' => 'Device belum diaktifkan oleh user manapun'
            ], 403);
        }

        // Generate API Key kalau belum ada
        if (!$device->api_key) {
            $device->api_key = bin2hex(random_bytes(32));
            $device->api_key_generated_at = now();
            $device->save();
        }

        // Update last seen
        $device->update(['last_online_at' => now()]);

        return response()->json([
            'token'      => $device->api_key,     // ← ESP32 terima ini sebagai X-API-KEY
            'device_id'  => $device->esp_id,
            'device_name'=> $device->name ?? $device->esp_id,
            'message'    => 'Login sukses via user aktif'
        ]);
    }

    public function listDevices(Request $request)
    {
        $username = $request->query('username');

        if (!$username) {
            return response()->json([]);
        }

        $user = User::where('username', $username)->first();
        if (!$user) {
            return response()->json([]);
        }

        $devices = Device::where(function ($q) use ($user) {
                $q->whereNull('user_id')
                ->where('status', 'online');
            })
            ->orWhere(function ($q) use ($user) {
                $q->where('status', 'in_use')
                ->where('user_id', $user->id);
            })
            ->orderBy('name')
            ->get(['esp_id', 'name']);

        return response()->json($devices);
    }
}
        