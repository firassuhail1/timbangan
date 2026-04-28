<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Update\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

// use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    public function lupa()
    {
        return view('auth.password.lupa-password');
    }

    public function index()
    {
        $autoSelectedEspId = null;
        $lastUsername = Cookie::get('username');

        if ($lastUsername) {
            $user = User::where('username', $lastUsername)->first();
            if ($user) {
                $device = Device::where('user_id', $user->id)
                    ->where('status', 'in_use')
                    ->first();

                if ($device) {
                    $autoSelectedEspId = $device->esp_id;
                }
            }
        }

        $availableDevices = Device::where(function ($q) {
            // 1. Tampilkan yang memang kosong & online
            $q->whereNull('user_id')
                ->where('status', 'online');
        })
            ->orWhere(function ($q) use ($autoSelectedEspId) {
                // 2. Tampilkan yang nyangkut di USER INI (agar auto-select jalan)
                if ($autoSelectedEspId) {
                    $q->where('esp_id', $autoSelectedEspId)
                        ->where('status', 'in_use');
                }
            })
            ->orWhere(function ($q) {
                // 3. Tampilkan yang nyangkut di USER LAIN tapi sudah lewat 5 menit (TIMEOUT)
                $q->where('status', 'in_use')
                    ->whereNotNull('user_id')
                    ->where('last_seen_at', '<', now()->subMinutes(5));
            })
            ->orderBy('esp_id') // Biasanya lebih enak urut ID timbangan
            ->get();

        Log::info('available devices : ' . $availableDevices);

        return view('auth.login', compact(
            'availableDevices',
            'autoSelectedEspId'
        ));
    }

    // public function store(Request $request)
    // {
    //     Log::info('Proses Login Dimulai', $request->only('username', 'esp_id'));

    //     // =========================
    //     // 1. VALIDASI
    //     // =========================
    //     $request->validate([
    //         'username' => 'required|string',
    //         'password' => 'required|string',
    //         'esp_id'   => 'required|string|exists:devices,esp_id',
    //     ]);

    //     // =========================
    //     // 2. AUTH USER
    //     // =========================
    //     if (!Auth::attempt(
    //         $request->only('username', 'password'),
    //         $request->boolean('remember')
    //     )) {
    //         return back()->withErrors([
    //             'password' => 'Username atau password salah!',
    //         ])->withInput();
    //     }

    //     $user = Auth::user();

    //     // =========================
    //     // 3. KHUSUS ADMIN
    //     // =========================
    //     if ($user->role === 'admin') {
    //         $request->session()->regenerate();
    //         return redirect()->route('admin.view');
    //     }

    //     // =========================
    //     // 4. PENCARIAN & RESET DEVICE
    //     // =========================

    //     // Cari device berdasarkan esp_id yang dipilih di form
    //     $device = Device::where('esp_id', $request->esp_id)->first();

    //     if ($device) {
    //         // Cek apakah device sedang "nyangkut" di user lain tapi sudah tidak aktif > 5 menit
    //         if (
    //             $device->status === 'in_use' &&
    //             $device->last_seen_at &&
    //             $device->last_seen_at->lt(now()->subMinutes(5))
    //         ) {
    //             Log::info("Resetting device {$device->esp_id} karena timeout 5 menit.");
    //             $device->update([
    //                 'user_id' => null,
    //                 'status'  => 'online',
    //             ]);
    //             $device->refresh();
    //         }

    //         // Cek validasi kepemilikan setelah potensi reset di atas
    //         if ($device->user_id !== null && $device->user_id !== $user->id) {
    //             Auth::logout();
    //             return back()->withErrors([
    //                 'esp_id' => 'Timbangan ini sedang digunakan oleh user lain.',
    //             ])->withInput();
    //         }
    //     } else {
    //         Auth::logout();
    //         return back()->withErrors([
    //             'esp_id' => 'Timbangan tidak ditemukan di database.',
    //         ])->withInput();
    //     }

    //     // =========================
    //     // 5. KUNCI DEVICE UNTUK USER INI
    //     // =========================
    //     $device->update([
    //         'user_id'              => $user->id,
    //         'status'               => 'in_use',
    //         'api_key'              => bin2hex(random_bytes(32)),
    //         'api_key_generated_at' => now(),
    //         'last_online_at'       => now(),
    //         'last_seen_at'         => now(),
    //     ]);

    //     // =========================
    //     // 6. SETUP SESSION
    //     // =========================
    //     session([
    //         'selected_device' => $device->id,
    //         'selected_esp_id' => $device->esp_id,
    //         'device_api_key'  => $device->api_key,
    //     ]);

    //     $request->session()->regenerate();
    //     Log::info("User {$user->username} berhasil login menggunakan {$device->esp_id}");

    //     // =========================
    //     // 7. REDIRECT BERDASARKAN TIPE TIMBANGAN
    //     // =========================
    //     if ($user->role === 'user') {
    //         // Cek apakah Timbangan Order (O) atau Package (P)
    //         if (preg_match('/Timbangan-([OP])\d+/', $device->esp_id, $m)) {
    //             return $m[1] === 'O'
    //                 ? redirect()->route('order.view')
    //                 : redirect()->route('package.view');
    //         }
    //     }

    //     // Fallback jika role/device tidak dikenali
    //     Auth::logout();
    //     return redirect()->route('login')->with('error', 'Role atau tipe device tidak dikenali.');
    // }

    public function store(Request $request)
    {
        Log::info('Proses Login Dimulai', $request->only('username', 'esp_id'));

        // =========================
        // 1. VALIDASI DASAR (tanpa esp_id dulu)
        // =========================
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // =========================
        // 2. AUTH USER
        // =========================
        if (!Auth::attempt(
            $request->only('username', 'password'),
            $request->boolean('remember')
        )) {
            return back()->withErrors([
                'password' => 'Username atau password salah!',
            ])->withInput();
        }

        $user = Auth::user();

        // =========================
        // 3. KHUSUS ADMIN (tidak butuh esp_id)
        // =========================
        if ($user->role === 'admin') {
            $request->session()->regenerate();
            return redirect()->route('admin.view');
        }

        // =========================
        // 4. VALIDASI esp_id HANYA UNTUK NON-ADMIN
        // =========================
        $request->validate([
            'esp_id' => 'required|string|exists:devices,esp_id',
        ]);

        // =========================
        // 5. PENCARIAN & RESET DEVICE
        // =========================
        $device = Device::where('esp_id', $request->esp_id)->first();

        if ($device) {
            if (
                $device->status === 'in_use' &&
                $device->last_seen_at &&
                $device->last_seen_at->lt(now()->subMinutes(5))
            ) {
                Log::info("Resetting device {$device->esp_id} karena timeout 5 menit.");
                $device->update([
                    'user_id' => null,
                    'status'  => 'online',
                ]);
                $device->refresh();
            }

            if ($device->user_id !== null && $device->user_id !== $user->id) {
                Auth::logout();
                return back()->withErrors([
                    'esp_id' => 'Timbangan ini sedang digunakan oleh user lain.',
                ])->withInput();
            }
        } else {
            Auth::logout();
            return back()->withErrors([
                'esp_id' => 'Timbangan tidak ditemukan di database.',
            ])->withInput();
        }

        // =========================
        // 6. KUNCI DEVICE UNTUK USER INI
        // =========================
        $device->update([
            'user_id'              => $user->id,
            'status'               => 'in_use',
            'api_key'              => bin2hex(random_bytes(32)),
            'api_key_generated_at' => now(),
            'last_online_at'       => now(),
            'last_seen_at'         => now(),
        ]);

        // =========================
        // 7. SETUP SESSION
        // =========================
        session([
            'selected_device' => $device->id,
            'selected_esp_id' => $device->esp_id,
            'device_api_key'  => $device->api_key,
        ]);

        $request->session()->regenerate();
        Log::info("User {$user->username} berhasil login menggunakan {$device->esp_id}");

        // =========================
        // 8. REDIRECT BERDASARKAN TIPE TIMBANGAN
        // =========================
        if ($user->role === 'user') {
            if (preg_match('/Timbangan-([OP])\d+/', $device->esp_id, $m)) {
                return $m[1] === 'O'
                    ? redirect()->route('order.view')
                    : redirect()->route('package.view');
            }
        }

        Auth::logout();
        return redirect()->route('login')->with('error', 'Role atau tipe device tidak dikenali.');
    }

    public function logout(Request $request)
    {
        // Lepaskan device jika ada
        if (Auth::check()) {
            $device = Device::where('user_id', Auth::id())->first();
            if ($device) {
                $device->update([
                    'user_id' => null,
                    'status' => 'online',
                    'last_online_at' => now(),
                ]);
            }
        }

        // Logout user terlebih dahulu
        Auth::logout();

        // Hancurkan seluruh session lama
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Hapus cookie terkait
        Cookie::queue(Cookie::forget('last_esp_id'));

        return redirect()->route('login');
    }
}
