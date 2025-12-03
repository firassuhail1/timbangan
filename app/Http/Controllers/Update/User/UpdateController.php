<?php

namespace App\Http\Controllers\Update\User;

use App\Http\Controllers\Controller;
use App\Models\Update\Device;
use App\Models\Update\Device_update;
use App\Models\Update\Firmwares;
use App\Services\MqttService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UpdateController extends Controller
{
    protected $mqtt;

    public function __construct(MqttService $mqtt)
    {
        $this->mqtt = $mqtt;
    }

    // public function updateDevice(Device $device)
    // {
    //     $latestUpdate = Device_update::where('device_id', $device->id)
    //         ->where('status', 'pending')
    //         ->latest()
    //         ->first();

    //     if (!$latestUpdate) {
    //         return back()->with('error', 'Tidak ada firmware baru untuk device ini.');
    //     }

    //     // Mark as pushed
    //     $latestUpdate->update([
    //         'status' => 'pushed',
    //         'pushed_at' => now(),
    //     ]);

    //     // Perintah update via MQTT
    //     $this->mqtt->publish(
    //         "device/{$device->esp_id}/ota/update",
    //         json_encode([
    //             "version" => $latestUpdate->firmware->version,
    //             "url"     => Storage::url($latestUpdate->firmware->path),
    //         ]),
    //         1
    //     );

    //     return back()->with('success', 'Update sudah dikirim ke device.');
    // }

}
