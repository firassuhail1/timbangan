<?php

namespace App\Http\Controllers\Update\User;

use App\Http\Controllers\Controller;
use App\Models\Update\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SwitchController extends Controller
{
    // Ambil semua device online atau sedang dipakai oleh user login
    public function available()
    {
        $devices = Device::where(function ($query) {
                $query->where('status', 'online')
                      ->orWhere(function ($q) {
                          $q->where('status', 'in_use')
                            ->where('user_id', Auth::id());
                      });
            })
            ->orderBy('name')
            ->get(['id', 'esp_id', 'name', 'status', 'user_id']);

        return response()->json($devices);
    }

    // Switch device
    public function switch(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id'
        ]);

        $newDevice = Device::findOrFail($request->device_id);

        // Device baru harus online atau sedang dipakai user login
        if ($newDevice->status === 'in_use' && $newDevice->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Device sedang digunakan oleh pengguna lain.'
            ], 403);
        }

        // Device lama milik user login
        $oldDevice = Device::where('user_id', Auth::id())->first();

        DB::transaction(function () use ($oldDevice, $newDevice) {
            if ($oldDevice) {
                $oldDevice->update([
                    'user_id' => null,
                    'status'  => 'online',
                ]);
            }

            $newDevice->update([
                'user_id' => Auth::id(),
                'status'  => 'in_use',
                'last_online_at' => now(),
            ]);
        });

        // Ambil tipe device
        $deviceType = null;
        if (preg_match('/Timbangan-([OP])\d+-/', $newDevice->esp_id, $matches)) {
            $deviceType = $matches[1];
        }

        // Update session minimal untuk UI
        session([
            'selected_device'      => $newDevice->id,
            'selected_esp_id'      => $newDevice->esp_id,
            'selected_device_type' => $deviceType,
        ]);

        return response()->json([
            'success'     => true,
            'message'     => 'Berhasil pindah ke device: ' . $newDevice->name,
            'device'      => $newDevice,
            'device_type' => $deviceType
        ]);
    }

    // public function available()
    // {
    //     $devices = Device::where(function ($query) {
    //             $query->where('status', 'online');
    //                 //   ->orWhere(function ($q) {
    //                 //       $q->where('status', 'in_use')
    //                 //         ->where('user_id', Auth::id());
    //                 //   });
    //         })
    //         ->orderBy('name')
    //         ->get(['id', 'esp_id', 'name', 'status']);

    //     // dd($devices);

    //     return response()->json($devices);
    // }

    // public function switch(Request $request)
    // {
    //     $request->validate([
    //         'device_id' => 'required|exists:devices,id'
    //     ]);

    //     $newDevice = Device::findOrFail($request->device_id);

    //     // Kunci logika: device baru harus online & tidak dipakai user lain
    //     if (
    //         $newDevice->status === 'in_use' &&
    //         $newDevice->user_id !== Auth::id()
    //     ) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Device sedang digunakan oleh pengguna lain.'
    //         ], 403);
    //     }

    //     $oldDevice = Device::where('user_id', Auth::id())->first();

    //     DB::transaction(function () use ($oldDevice, $newDevice) {
    //         if ($oldDevice) {
    //             $oldDevice->update([
    //                 'user_id' => null,
    //                 'status' => 'online',
    //             ]);
    //         }

    //         $newDevice->update([
    //             'user_id' => Auth::id(),
    //             'status' => 'in_use',
    //         ]);
    //     });

    //     session(['active_esp' => $newDevice->esp_id]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Berhasil pindah ke device: ' . $newDevice->name,
    //         'device' => $newDevice
    //     ]);
    // }

}
