<?php

use App\Http\Controllers\Api\DeviceLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Api\OrderSheetController;
use App\Http\Controllers\Api\WeightController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Ordersheet\PackageController;
use App\Http\Controllers\Ordersheet\Rekap\TimbanganBesarController;
use App\Http\Controllers\Update\Admin\DeviceController;
use App\Http\Controllers\Update\Admin\FirmwareController;
use App\Http\Controllers\Update\User\SettingController;
use App\Http\Controllers\Update\User\SwitchController;
use App\Http\Controllers\Update\User\WifiController;
use App\Models\Update\Device;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

Route::get('/', [HomeController::class, 'index']);

// Login & Logout
Route::get('login/view', [LoginController::class, 'index'])->name('login');
Route::post('login/store', [LoginController::class, 'store'])->name('login.store');
Route::get('login/admin', [LoginController::class, 'admin'])->name('login.admin');

Route::get('/devices/list', [DeviceLoginController::class, 'listDevices'])->name('devices.list');

Route::get('lupa-password', [LoginController::class, 'lupa'])->name('lupa.password');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Profiles
Route::prefix('setting')->group(function(){
    Route::get('/profile', [SettingController::class, 'setting'])->name('setting.profile');
    Route::put('/profile/update/{id}', [SettingController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function(){
    Route::get('/dashboard',[DashboardController::class, 'index'])->name('admin.view');

    // master
    Route::get('/view-data', [DeviceController::class, 'index'])->name('admin.view-data');

    Route::get('/ordersheet-index',[OrderSheetController::class, 'index'])->name('admin.view.index');
    
    // Update ESP
    Route::post('/firmware/upload', [FirmwareController::class, 'upload'])
        ->name('admin.firmware.upload');

    Route::post('/firmware/{firmware}/post', [FirmwareController::class, 'postToUsers'])
        ->name('admin.firmware.post');
});

Route::middleware(['auth', 'role:user'])->prefix('user')->group(function(){
    Route::get('/home',[DashboardController::class, 'index'])->name('dashboard');

    // ORDERSHEET
    Route::get('/ordersheet-view',[OrderSheetController::class, 'index'])->name('order.view');

    // tambah data ordersheet
    Route::get('/ordersheet-view/create', [OrderSheetController::class, 'create'])->name('ordersheet.create');
    Route::post('/ordersheet/store', [OrderSheetController::class, 'store'])->name('ordersheet.store');
    Route::get('/order/report-data', [OrderSheetController::class, 'reportData']);

    // cetak timbangan
    Route::get('/order/print', [OrderSheetController::class, 'print'])->name('order.print');
    Route::get('/print/{buyer}', [OrderSheetController::class, 'print'])->name('order.print.buyer');

    // rekap data
    Route::get('/rekap/timbangan/besar', [TimbanganBesarController::class, 'index'])->name('rekap.besar');
    Route::get('/rekap/get-Rekapdata', [TimbanganBesarController::class, 'getRekapData'])->name('rekap.getRekapData');

    // PACKAGE
    Route::get('/ordersheet-package', [PackageController::class, 'getData'])->name('package.ordersheet');
    Route::get('/package/search', [PackageController::class, 'apiSearch']);

    Route::get('/package-view',[PackageController::class, 'index'])->name('package.view');

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
});

