<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Update\Device;
use App\Models\User;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;

class DeviceLoginController extends Controller
{
    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'esp_id' => 'required|string|exists:devices,esp_id',
    //     ]);

    //     $device = Device::where('esp_id', $request->esp_id)->first();

    //     if (!$device) {
    //         return response()->json(['error' => 'Device tidak ditemukan'], 404);
    //     }

    //     // Cek apakah device sudah diklaim oleh user manapun
    //     if ($device->status !== 'in_use' || is_null($device->user_id) || is_null($device->api_key)) {
    //         return response()->json([
    //             'error' => 'Device belum diaktifkan oleh user manapun. Silakan login web terlebih dahulu.',
    //             'status' => $device->status
    //         ], 403);
    //     }

    //     // Update last seen
    //     $device->update(['last_online_at' => now()]);

    //     return response()->json([
    //         'token'      => $device->api_key,
    //         'device_id'  => $device->esp_id,
    //         'device_name' => $device->name ?? $device->esp_id,
    //         'message'    => 'Login sukses, menggunakan api_key aktif'
    //     ]);
    // }

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

        $devices = Device::with('user')
            ->orderBy('name')
            ->get(['esp_id', 'name', 'status', 'user_id']);

        return response()->json($devices->map(function ($device) use ($user) {
            $label = $device->name ?: $device->esp_id;

            if ($device->status === 'in_use' && $device->user) {
                $label .= ' (' . $device->user->username . ')';
            }

            return [
                'esp_id'   => $device->esp_id,
                'name'     => $label,
                'status'   => $device->status,
                'is_mine'  => $device->user_id === $user->id,
                'user'    => $device->user ? ['username' => $device->user->username] : null,
            ];
        }));
    }
}
