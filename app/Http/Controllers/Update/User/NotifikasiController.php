<?php

namespace App\Http\Controllers\Update\User;

use App\Http\Controllers\Controller;
use App\Models\Update\Device;
use App\Models\Update\Firmwares;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotifikasiController extends Controller
{
    public function checkNotification()
    {
        $user = Auth::user();

        // Jika tidak login (meskipun seharusnya middleware auth sudah handle)
        if (!$user) {
            return response()->json([
                'has_notification' => false,
                'message' => 'User tidak terautentikasi'
            ], 401);
        }

        try {
            // Cari device aktif milik user ini
            $device = Device::where('user_id', $user->id)
                ->where('status', 'in_use')
                ->first();

            if (!$device) {
                return response()->json([
                    'has_notification' => false,
                    'message' => 'Tidak ada device aktif'
                ]);
            }

            // Ambil versi saat ini (fallback aman)
            $currentVersion = $device->current_firmware_version ?? '0.0.0';

            // Cari firmware published terbaru yang cocok dengan device_type user
            $latestFirmware = Firmwares::where('device_type', $device->device_type)
                ->where('status', 'published')
                ->orderByDesc('released_at')
                ->first();

            // Tidak ada firmware baru atau versi tidak lebih tinggi
            if (!$latestFirmware || version_compare($latestFirmware->version, $currentVersion, '<=')) {
                return response()->json([
                    'has_notification' => false,
                ]);
            }

            // Ada update → siapkan payload notifikasi
            $releasedAtFormatted = $latestFirmware->created_at
                ? \Carbon\Carbon::parse($latestFirmware->created_at)->format('d M Y H:i')
                : '-';

            $deviceDisplayName = $device->name ?? $device->esp_id ?? 'Device';

            return response()->json([
                'has_notification' => true,
                'count'            => 1,
                'message'          => "Firmware versi {$latestFirmware->version} tersedia untuk {$deviceDisplayName}",
                'firmware'         => [
                    'id'          => $latestFirmware->id,
                    'version'     => $latestFirmware->version,
                    'released_at' => $releasedAtFormatted,
                    'notes'       => $latestFirmware->notes ?? '',
                ],
                'link'             => route('firmware.user'), // pastikan route ini ada dan benar
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal check notifikasi firmware', [
                'user_id'    => $user?->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return response()->json([
                'has_notification' => false,
                'error'            => 'Terjadi kesalahan server',
            ], 500);
        }
    }
}
