<?php

use App\Http\Controllers\Api\DeviceLoginController;
use App\Http\Controllers\Api\OrderSheetController;
use App\Http\Controllers\Api\WeightController;
use App\Http\Controllers\Ordersheet\OrderPackageController;
use App\Http\Controllers\Ordersheet\PackageController;
use App\Http\Controllers\Update\Admin\DeviceController;
use App\Http\Controllers\Update\User\WifiController;
use App\Models\Update\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// LOGIN ESP
Route::post('/login/device', [DeviceLoginController::class, 'login']);

// ordersheet routes
Route::get('/ordersheet', [OrderSheetController::class, 'getData'])->name('api.ordersheet');

// Login
Route::prefix('login')->group(function () {
    Route::post('/esp/heartbeat', [DeviceController::class, 'heartbeat']);

    Route::get('/wifi/stream', [WifiController::class, 'stream']);
    Route::middleware('auth.api.key')->group(function () {
        Route::get('/esp/wifi/check', [WifiController::class, 'checkNewWifi']);
    });
});

// Current_id Ordersheet
// Untuk ESP32 (tetap pakai API Key)
Route::prefix('timbang/esp32')->group(function () {
    Route::get('/cek-id', [WeightController::class, 'cekIdAktif']);
    Route::post('/kirim-berat', [WeightController::class, 'terimaBerat']);
    Route::get('/cek-perintah', [WeightController::class, 'cekPerintah']);
});

//  Package Current_id
Route::prefix('package')->middleware('web')->group(function () {
    Route::get('/timbangan/live', [OrderPackageController::class, 'getPreview']);
    Route::post('/timbangan/tare', [OrderPackageController::class, 'tare']);
    Route::post('/store', [PackageController::class, 'store']);
});

// Route khusus ESP (tanpa auth, tapi validasi esp_id)
Route::prefix('package/esp')->group(function () {
    Route::post('/timbangan/live', [OrderPackageController::class, 'terimaBerat']);
    Route::get('/timbangan/set', [OrderPackageController::class, 'setTimbanganCommand']); // ubah ke POST
});