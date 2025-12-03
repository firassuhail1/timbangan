<?php

namespace App\Http\Controllers\Update\User;

use App\Http\Controllers\Controller;
use App\Models\Update\Device;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WifiController extends Controller
{
    // WifiController.php

    private function getActiveDevice()
    {
        $device = Device::where('user_id', Auth::id())
                    ->where('status', 'in_use')
                    ->first();
        return $device?->esp_id;
    }

    // GET: Ambil WiFi config dari device yang sedang in_use
    public function getWifiConfig()
    {
        $device = $this->getActiveDevice();

        if (!$device) {
            return response()->json([
                'error' => 'Tidak ada ESP yang sedang digunakan (status in_use tidak ditemukan)',
            ], 404);
        }

        return response()->json([
            'ssid' => $device->wifi_ssid ?? '',
            'password' => $device->wifi_password ?? '',
            'esp_id' => $device->esp_id,
            'device_id' => $device->id
        ]);
    }

    // POST: Update WiFi → hanya device in_use
    public function updateWifi(Request $request)
    {
        $request->validate([
            'ssid' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        $device = $this->getActiveDevice();

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device aktif tidak ditemukan.'], 404);
        }

        $device->wifi_ssid = $request->ssid;
        $device->wifi_password = $request->password;
        $device->wifi_updated_at = now();
        $device->save();

        return response()->json(['success' => true, 'message' => 'WiFi berhasil diperbarui']);
    }

    // SSE Stream untuk ESP (dipanggil oleh ESP!)
    public function stream()
    {
        $device = $this->getActiveDevice();

        if (!$device) {
            return response()->json(['error' => 'No active device'], 404);
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Kirim koneksi awal + esp_id
        echo "event: connected\n";
        echo "data: " . json_encode(['esp_id' => $device->esp_id]) . "\n\n";
        flush();

        $lastChecked = $device->wifi_updated_at ?? now();

        while (true) {
            // Cek apakah ada update WiFi
            $updated = Device::where('id', $device->id)
                ->where('wifi_updated_at', '>', $lastChecked)
                ->select('wifi_ssid', 'wifi_password', 'wifi_updated_at')
                ->first();

            if ($updated) {
                echo "event: wifi-change\n";
                echo "data: " . json_encode([
                    'ssid' => $updated->wifi_ssid,
                    'password' => $updated->wifi_password
                ]) . "\n\n";
                flush();

                $lastChecked = $updated->wifi_updated_at;
            }

            if (connection_aborted()) break;
            sleep(1);
        }
    }

    // Digunakan frontend untuk cek apakah ESP sudah pakai WiFi baru
    public function checkLatest()
    {
        $device = $this->getActiveDevice();

        if (!$device) {
            return response()->json(['status' => 'no_device'], 404);
        }

        return response()->json([
            'status' => 'ok',
            'wifi_ssid' => $device->wifi_ssid,
            'last_seen_at' => $device->last_seen_at,
            'is_online' => $device->status === 'online' || $device->status === 'in_use',
        ]);
    }

}
