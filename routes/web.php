<?php

use App\Http\Controllers\Admin\Rekap\Ordersheet\OrderController;
use App\Http\Controllers\Admin\Rekap\Package\PackController;
use App\Http\Controllers\Api\DeviceLoginController;
use App\Http\Controllers\Api\OrderSheetController;
use App\Http\Controllers\Api\WeightController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Ordersheet\PackageController;
use App\Http\Controllers\Ordersheet\Rekap\TimbanganBesarController;
use App\Http\Controllers\Update\Admin\FirmwareController;
use App\Http\Controllers\Update\Admin\UserController;
use App\Http\Controllers\Update\User\NotifikasiController;
use App\Http\Controllers\Update\User\OtaUpdateController;
use App\Http\Controllers\Update\User\SettingController;
use App\Http\Controllers\Update\User\SwitchController;
use App\Http\Controllers\Update\User\UpdateController;
use App\Http\Controllers\Update\User\WifiController;
use App\Models\Update\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;


// Login & Logout

Route::middleware('guest')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('login');
    // Route::get('login/view', [LoginController::class, 'index']);
    Route::post('login/store', [LoginController::class, 'store'])->name('login.store');
    Route::get('login/admin', [LoginController::class, 'admin'])->name('login.admin');
});

Route::get('/devices/list', [DeviceLoginController::class, 'listDevices'])->name('devices.list');

Route::get('lupa-password', [LoginController::class, 'lupa'])->name('lupa.password');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Profiles
Route::prefix('setting')->group(function () {
    Route::get('/profile', [SettingController::class, 'setting'])->name('setting.profile');
    Route::put('/profile/update/{id}', [SettingController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'admin'])->name('admin.view');

    Route::get('/ordersheet-index', [OrderSheetController::class, 'index'])->name('admin.view.index');

    // Rekap
    Route::prefix('/rekap')->group(function () {
        // Ordersheet
        Route::get('/order', [OrderController::class, 'index'])->name('admin.rekap.order');
        Route::get('/order-data', [OrderController::class, 'getRekapData'])->name('admin.rekap.order.data');
        Route::get('/order/export', [OrderController::class, 'export'])->name('admin.rekap.order.export');

        // Package
        Route::get('/package', [PackController::class, 'index'])->name('admin.rekap.package');
    });

    // View
    Route::get('/view-firmware', [FirmwareController::class, 'index'])->name('admin.view-firmware');

    Route::resource('users', UserController::class)->names([
        'index'   => 'admin.users.index',
        'create'  => 'admin.users.create',
        'store'   => 'admin.users.store',
        'edit'    => 'admin.users.edit',
        'update'  => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
    ]);

    // Update ESP
    Route::post('/firmware/upload', [FirmwareController::class, 'upload'])
        ->name('admin.firmware.upload');

    Route::post('/firmware/post/{id}', [FirmwareController::class, 'postToUsers'])->name('admin.firmware.post');

    // Download
    Route::get('firmware/download/{id}', [FirmwareController::class, 'download'])->name('admin.firmware.download');

    // Delete
    Route::delete('/firmware/delete/{id}', [FirmwareController::class, 'delete'])
        ->name('admin.firmware.delete');
});

Route::middleware(['auth', 'role:user'])->prefix('user')->group(function () {
    Route::get('/home', [DashboardController::class, 'index'])->name('dashboard');

    // ORDERSHEET
    Route::get('/ordersheet-view', [OrderSheetController::class, 'index'])->name('order.view');
    Route::get('/order/formal-report', [OrderSheetController::class, 'formalReport'])
        ->name('order.formal-report');

    Route::get('/order/my-report', [OrderSheetController::class, 'myReport'])
        ->name('order.my-report');

    Route::get('/order/buyers',      [OrderSheetController::class, 'buyers']);
    Route::get('/order/buyer-report',[OrderSheetController::class, 'buyerReport']);

    Route::get('/order/get-keterangan', [OrdersheetController::class, 'getKeterangan']);
    Route::post('/order/update-keterangan', [OrdersheetController::class, 'updateKeterangan']);
    Route::get('/order/checking-info', [OrderSheetController::class, 'getCheckingInfo']);

    // tambah data ordersheet
    Route::get('/ordersheet-view/create', [OrderSheetController::class, 'create'])->name('ordersheet.create');
    Route::post('/ordersheet/store', [OrderSheetController::class, 'store'])->name('ordersheet.store');
    Route::get('/order/report', [OrderSheetController::class, 'reportData']);

    // cetak timbangan
    Route::get('/order/print', [OrderSheetController::class, 'print'])->name('order.print');
    Route::get('/print/{orderCode}', [OrderSheetController::class, 'printByOrderCode'])->name('order.print.orderCode');

    // rekap data
    Route::get('/rekap/timbangan/besar', [TimbanganBesarController::class, 'index'])->name('rekap.besar');
    Route::get('/rekap/get-Rekapdata', [TimbanganBesarController::class, 'getRekapData'])->name('rekap.getRekapData');

    // PACKAGE
    Route::get('/ordersheet-package', [PackageController::class, 'getData'])->name('package.ordersheet');
    Route::get('/package/search', [PackageController::class, 'apiSearch']);

    Route::get('/package-view', [PackageController::class, 'index'])->name('package.view');

    Route::get('/devices/available', [SwitchController::class, 'available'])->name('login.devices.available');

    Route::post('/devices/switch', [SwitchController::class, 'switch'])->name('login.devices.switch');

    // WIFI
    Route::get('/wifi/config', [WifiController::class, 'getWifiConfig']);
    Route::post('/wifi/update', [WifiController::class, 'updateWifi']);
    Route::get('/wifi/check-latest', [WifiController::class, 'checkLatest']);

    Route::prefix('/order')->group(function () {
        Route::post('/set-id', [WeightController::class, 'setCurrentId']);
        Route::get('/preview/{id}', [WeightController::class, 'getPreview']);
        Route::post('/tare', [WeightController::class, 'tare']);
        Route::post('/simpan', [WeightController::class, 'simpan']);
    });

    // Firmware
    Route::get('/firmware', [UpdateController::class, 'userFirmware'])->name('firmware.user');

    // Polling realtime
    Route::get('/device/{device}/check-firmware-update', [UpdateController::class, 'checkFirmwareUpdate'])
        ->name('device.check-firmware-update');

    // Route::post('/firmware/ota-update', [UpdateController::class, 'performOtaUpdate'])->name('firmware.ota');

    // Notifikasi firmware baru
    Route::get('/check-firmware-notification', [NotifikasiController::class, 'checkNotification'])
        ->name('firmware.check-notification');

    // User OTA trigger
    Route::post('/firmware/ota', [OtaUpdateController::class, 'ota'])->name('firmware.ota');


    Route::post('/weight-cache', function (Request $request) {
        $device = Device::where('user_id', Auth::id())
            ->where('status', 'in_use')
            ->first();

        if (!$device) return response()->json(['success' => false], 403);

        $berat     = (float) $request->input('berat', 0);
        $currentId = $request->input('order_id');

        Cache::put("timbangan_live_{$device->esp_id}", $berat, now()->addMinutes(7));

        if ($currentId) {
            Cache::put(
                "weight_preview_{$device->esp_id}_{$currentId}",
                $berat,
                now()->addSeconds(30)
            );
        }

        $berat = Cache::get("weight_preview_{$device->esp_id}_{$currentId}", 0);
        Log::info('berat : ' . $berat);

        return response()->json(['success' => true]);
    });
});
