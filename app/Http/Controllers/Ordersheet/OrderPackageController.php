<?php

namespace App\Http\Controllers\Ordersheet;

use App\Events\BeratUpdated;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OrderPackageController extends Controller
{
    public function terimaBerat(Request $request)
    {
        $t1 = microtime(true);

        $request->validate([
            'esp_id' => 'required|string|exists:devices,esp_id',
            'berat'  => 'required|numeric'
        ]);

        $t2 = microtime(true);

        $esp_id = $request->esp_id;

        Cache::put("timbangan_live_{$esp_id}", floatval($request->berat), now()->addMinutes(7));

        $currentId = Cache::get("current_id_{$esp_id}");
        if ($currentId) {
            Cache::put(
                "weight_preview_{$esp_id}_{$currentId}",
                $request->berat,
                now()->addSeconds(30)
            );
        }

        $t3 = microtime(true);

        broadcast(new BeratUpdated($esp_id, $request->berat));

        $t4 = microtime(true);

        // Log waktu tiap tahap
        Log::info('terimaBerat timing', [
            'validate_ms'   => round(($t2 - $t1) * 1000, 2),
            'cache_ms'      => round(($t3 - $t2) * 1000, 2),
            'broadcast_ms'  => round(($t4 - $t3) * 1000, 2),
            'total_ms'      => round(($t4 - $t1) * 1000, 2),
        ]);

        return response()->json(['status' => 'ok']);
    }

    public function getPreview(Request $request)
    {
        $esp_id = session('selected_esp_id');

        if (!$esp_id) {
            return response()->json([
                'success' => false,
                'message' => 'ESP belum dipilih user.'
            ], 400);
        }

        // JANGAN PAKAI DEFAULT 0 → pakai null
        $berat = Cache::get("timbangan_live_{$esp_id}"); // <-- hapus , 0

        return response()->json([
            'success' => true,
            'berat'   => $berat !== null ? floatval($berat) : null   // pastikan null kalau belum ada
        ]);
    }

    // public function tare(Request $request)
    // {
    //     $esp_id = session('selected_esp_id');
    //     if (!$esp_id) {
    //         return response()->json(['success' => false, 'message' => 'ESP tidak ditemukan'], 400);
    //     }

    //     $key = "timbangan_command_tare_{$esp_id}";

    //     // Buat perintah tare, berlaku 10 detik
    //     Cache::put($key, true, now()->addSeconds(10));

    //     // Opsional: set nilai beban ke UI jadi 0 biar kelihatan instan
    //     Cache::put("timbangan_live_{$esp_id}", 0, now()->addSeconds(5));

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Perintah Tare dikirim!'
    //     ]);
    // }

    // public function setTimbanganCommand(Request $request)
    // {
    //     $request->validate([
    //         'esp_id' => 'required|string|exists:devices,esp_id'
    //     ]);

    //     $esp_id = $request->esp_id;
    //     $key = "timbangan_command_tare_{$esp_id}";

    //     $tareCommand = Cache::get($key, false);

    //     // HANYA HAPUS JIKA ESP SUDAH MENERIMA (tare == true)
    //     if ($tareCommand === true) {
    //         // JANGAN langsung forget di sini!
    //         // Biarkan ESP yang hapus setelah berhasil tare
    //         // Kita cukup return true, ESP nanti hapus sendiri via logika lain atau cukup abaikan
    //     }

    //     return response()->json([
    //         'tare' => $tareCommand === true
    //     ]);
    // }
}
