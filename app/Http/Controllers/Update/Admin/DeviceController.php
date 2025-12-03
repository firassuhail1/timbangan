<?php

namespace App\Http\Controllers\Update\Admin;

use App\Http\Controllers\Controller;
use App\Models\Update\Device;
use App\Models\Update\Device_update;
use App\Models\Update\Firmwares;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $entries = $request->input('entries', 10);

        $query = Device::with( 'user', 'firmware', 'update')
            ->where('user_id', Auth::id());

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('device_id', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%")
                    ->orWhere('device_type', 'LIKE', "%{$search}%")
                    ->orWhere('ip_address', 'LIKE', "%{$search}%")
                    ->orWhere('firmware_version', 'LIKE', "%{$search}%")
                    ->orWhere('last_online_at', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', fn($q2) => $q2->where('username', 'LIKE', "%{$search}%"));
            });
        }

        $devices = $query->paginate($entries);
        $devices->appends($request->query());

        $latestFirmware = Firmwares::orderBy('id', 'desc')->first();

        // dd($devices);

        return view('admin.master.view', compact('devices', 'search', 'entries', 'latestFirmware'));
    }

    public function heartbeat(Request $request)
    {
        $request->validate([
            'esp_id'      => 'required|string',
            'device_type' => 'required|string',
            'user_id'     => 'nullable|integer',
            'name'        => 'nullable|string',
            'wifi_ssid'   => 'nullable|string',
            'wifi_password'   => 'nullable|string',
        ]);

        // Ambil atau buat device baru berdasarkan ESP ID
        $device = Device::firstOrNew(
            ['esp_id' => $request->esp_id],
            [
                'device_type' => $request->device_type,
                'name'        => $request->name
            ]
        );

        // Update informasi umum
        $device->ip_address     = $request->ip();
        $device->last_seen_at   = now();
        $device->last_online_at = now();

        $incomingUserId = $request->user_id;
        $currentUserId  = $device->user_id;

        if ($currentUserId) {

            // Jika heartbeat membawa user_id berbeda → tolak
            if ($incomingUserId && $incomingUserId != $currentUserId) {
                return response()->json([
                    'status'  => 'forbidden',
                    'message' => 'Device sedang digunakan oleh pengguna lain.',
                    'device'  => $device
                ], 403);
            }

            // Tetapkan status
            $device->status = 'in_use';
        }

        elseif (!$currentUserId && $incomingUserId) {
            $device->user_id = $incomingUserId;
            $device->status  = 'in_use';
        }

        else {
            $device->status = 'online';
        }

        if ($request->filled('wifi_ssid') && $request->filled('wifi_password')) {
            $device->wifi_ssid     = $request->wifi_ssid;
            $device->wifi_password = $request->wifi_password;
        }

        $device->save();

        return response()->json([
            'status'  => 'ok',
            'device'  => $device,
            'message' => 'Device heartbeat updated.'
        ]);
    }

    public function handleOtaStatus($espId, $payload)
    {
        $data = json_decode($payload, true);

        $device = Device::where('esp_id', $espId)->first();
        if (!$device) return;

        $update = Device_update::where('device_id', $device->id)
            ->where('status', 'pushed')
            ->latest()
            ->first();

        if (!$update) return;

        if ($data['status'] === 'success') {
            $update->update([
                'status' => 'success',
                'installed_at' => now(),
            ]);

            $device->update([
                'current_firmware_version' => $data['version']
            ]);
        } else {
            $update->update([
                'status' => 'failed',
                'error_message' => $data['error'] ?? null
            ]);
        }
    }

}
