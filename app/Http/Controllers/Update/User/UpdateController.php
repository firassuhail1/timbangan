<?php

namespace App\Http\Controllers\Update\User;

use App\Http\Controllers\Controller;
use App\Models\Update\Device;
use App\Models\Update\Firmwares;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UpdateController extends Controller
{
    public function userFirmware(Request $request)
    {
        $user = $request->user();

        // Ambil device aktif milik user login
        $device = $user->devices()
            ->where('status', 'in_use')
            ->first();

        if (!$device) {
            return view('user.firmware.index', ['noDevice' => true]);
        }

        // dd($device);

        $firmware = Firmwares::where('device_type', $device->device_type)
            ->where('status', 'published')
            ->latest('released_at')
            ->first();

        dd($firmware);

        return view('user.firmware.index', [
            'device' => $device,
            'firmware' => $firmware,
            'currentVersion' => $device->current_firmware_version ?? 'Unknown',
        ]);
    }

    public function checkFirmwareUpdate($deviceId)
    {
        try {
            $device = Device::find($deviceId);

            if (!$device) {
                return response()->json([
                    'error' => 'Device tidak ditemukan',
                    'device_id' => $deviceId
                ], 404);
            }

            $currentVersion = $device->current_firmware_version ?? '1.1.0';

            $latestFirmware = Firmwares::where('device_type', $device->device_type)
                ->where('status', 'published')
                ->orderByDesc('released_at')
                ->first();

            if (!$latestFirmware || version_compare($latestFirmware->version, $currentVersion, '<=')) {
                return response()->json(['has_update' => false]);
            }

            return response()->json([
                'has_update'     => true,
                'current_version' => $currentVersion,
                'firmware'       => [
                    'id'          => $latestFirmware->id,
                    'version'     => $latestFirmware->version,
                    'released_at' => $latestFirmware->released_at
                        ? $latestFirmware->released_at->format('d M Y H:i')
                        : ($latestFirmware->created_at ? $latestFirmware->created_at->format('d M Y H:i') : '-'),
                    'notes'       => $latestFirmware->notes ?? '',
                    'device_type' => $latestFirmware->device_type,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Check firmware update gagal', [
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan server',
                'message' => $e->getMessage() // hapus ini di production
            ], 500);
        }
    }
}
