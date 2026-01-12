<?php

namespace App\Http\Controllers\Ordersheet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OrderPackageController extends Controller
{    
    public function terimaBerat(Request $request)
    {
        $request->validate([
            'esp_id' => 'required|string|exists:devices,esp_id',
            'berat'  => 'required|numeric'
        ]);

        $esp_id = $request->esp_id;

        Cache::put("timbangan_live_{$esp_id}", floatval($request->berat), now()->addMinutes(7));

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

    public function tare(Request $request)
    {
        $esp_id = session('selected_esp_id');
        if (!$esp_id) {
            return response()->json(['success' => false, 'message' => 'ESP tidak ditemukan'], 400);
        }

        $key = "timbangan_command_tare_{$esp_id}";

        // Buat perintah tare, berlaku 10 detik
        Cache::put($key, true, now()->addSeconds(10));

        // Opsional: set nilai beban ke UI jadi 0 biar kelihatan instan
        Cache::put("timbangan_live_{$esp_id}", 0, now()->addSeconds(5));

        return response()->json([
            'success' => true,
            'message' => 'Perintah Tare dikirim!'
        ]);
    }

   public function setTimbanganCommand(Request $request)
    {
        $request->validate([
            'esp_id' => 'required|string|exists:devices,esp_id'
        ]);

        $esp_id = $request->esp_id;
        $key = "timbangan_command_tare_{$esp_id}";

        $tareCommand = Cache::get($key, false);

        // HANYA HAPUS JIKA ESP SUDAH MENERIMA (tare == true)
        if ($tareCommand === true) {
            // JANGAN langsung forget di sini!
            // Biarkan ESP yang hapus setelah berhasil tare
            // Kita cukup return true, ESP nanti hapus sendiri via logika lain atau cukup abaikan
        }

        return response()->json([
            'tare' => $tareCommand === true
        ]);
    }

}