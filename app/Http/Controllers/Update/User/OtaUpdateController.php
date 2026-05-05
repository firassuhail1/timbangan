<?php

namespace App\Http\Controllers\Update\User;

use App\Http\Controllers\Controller;
use App\Models\Update\Device;
use App\Models\Update\Firmwares;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OtaUpdateController extends Controller
{
    public function ota(Request $request)
    {
        $validated = $request->validate([
            'firmware_id' => 'required|exists:firmwares,id',
            'device_id'   => 'required|exists:devices,id',
        ]);

        $user = $request->user();
        $device = Device::findOrFail($validated['device_id']);

        // Auth: Pastikan device milik user
        if ($device->user_id !== $user->id || $device->status !== 'in_use') {
            return response()->json(['success' => false, 'error' => 'Device tidak valid atau bukan milik Anda'], 403);
        }

        $firmware = Firmwares::findOrFail($validated['firmware_id']);

        // Validasi kompatibilitas
        if ($firmware->device_type !== $device->device_type || $firmware->status !== 'published') {
            return response()->json(['success' => false, 'error' => 'Firmware tidak kompatibel atau belum published'], 400);
        }

        // Set pending
        $device->pending_firmware_id = $firmware->id;
        $device->ota_started_at = now();
        $device->save();

        // Optional: Log atau notify admin
        Log::info('OTA dimulai untuk device', ['esp_id' => $device->esp_id, 'firmware_version' => $firmware->version]);

        return response()->json([
            'success' => true,
            'message' => 'Update OTA dimulai. Device akan segera mendownload firmware.',
        ]);
    }

    public function checkOta(Request $request)
    {
        $deviceType     = $request->query('device_type');
        $currentVersion = $request->query('version', '0.0.0');

        if (!$deviceType) {
            return response()->json(['has_ota' => false]);
        }

        $firmware = Firmwares::where('device_type', $deviceType)
                            ->where('status', 'published')
                            ->latest('released_at')
                            ->first();

        // // Tidak ada firmware published, atau versi sama
        // if (!$firmware || $firmware->version === $currentVersion) {
        //     return response()->json(['has_ota' => false]);
        // }

        // Tidak ada firmware published sama sekali
        if (!$firmware) {
            return response()->json([
                'has_ota'  => false,
                'reason'   => 'no_published_firmware'  // ← tambah ini
            ]);
        }

        // Versi sama
        if ($firmware->version === $currentVersion) {
            return response()->json([
                'has_ota'  => false,
                'reason'   => 'up_to_date'  // ← tambah ini
            ]);
        }

        $token = Str::random(32);
        Cache::put("ota_token_{$token}", $firmware->id, 600);

        Log::info('OTA check', [
            'device_type'     => $deviceType,
            'current_version' => $currentVersion,
            'new_version'     => $firmware->version,
        ]);

        return response()->json([
            'has_ota' => true,
            'version' => $firmware->version,
            'sha256'  => $firmware->checksum,
            'url'     => url("api/update/firmware/download/{$firmware->id}?token={$token}"),
        ]);
    }

    public function download($id, Request $request)
    {
        $firmware = Firmwares::findOrFail($id);
        $token = $request->query('token');
        
        $cached = Cache::get("ota_token_{$token}");
        
        Log::info('OTA Download debug', [
            'token'       => $token,
            'cached'      => $cached,
            'firmware_id' => (int)$firmware->id,
            'match'       => $cached === (int)$firmware->id,
            'cache_driver'=> config('cache.default'),
        ]);

        if (!$token || $cached !== (int)$firmware->id) {
            abort(403, 'Token invalid atau expired');
        }

        $path = public_path($firmware->file_path);
        if (!file_exists($path)) {
            Log::error('OTA Download: File tidak ditemukan', ['path' => $path]);
            abort(404, 'File tidak ditemukan');
        }

        // ← Hapus Cache::forget, biarkan token expire sendiri
        // Cache::forget("ota_token_{$token}");

        Log::info('OTA Download: Sukses', ['id' => $id, 'file' => $firmware->file_name]);

        return response()->download($path, $firmware->file_name, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    public function otaComplete(Request $request)
    {
        $validated = $request->validate([
            'esp_id'      => 'required|string',
            'new_version' => 'required|string',
        ]);

        $device = Device::where('esp_id', $validated['esp_id'])
                        ->firstOrFail();

        $device->current_firmware_version = $validated['new_version'];
        $device->pending_firmware_id      = null;
        $device->ota_started_at           = null;
        $device->save();

        Log::info('OTA selesai', [
            'esp_id'      => $device->esp_id,
            'new_version' => $validated['new_version']
        ]);

        return response()->json(['success' => true]);
    }
}
