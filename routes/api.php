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
});

// Current_id Ordersheet
// Untuk web (user login)
Route::middleware('web')->group(function () {
    Route::post('/set-id', [WeightController::class, 'setCurrentId']);
    Route::get('/preview/{id}', [WeightController::class, 'getPreview']);
    Route::post('/tare', [WeightController::class, 'tare']);
    Route::post('/simpan', [WeightController::class, 'simpan']);
});

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

// lalu bagiaman jika saya melakukan tare dimana tra ini tidak berdasarkan pollig tetapi berdasarka dari perintah button di ui akan dikirim melalui server dan diterima oleh esp dan nilai timbangan diesp menjadi 0 dan dikembalikan ke tampilan ui lagi, dari hal ini apakah perlu membuat endpoint lagi untuk komunikasi antara server dan esp untuk menjalankan perintah tare?
