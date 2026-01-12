<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Update\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

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

        $availableDevices = Device::where(function ($q) use ($autoSelectedEspId) {
                $q->whereNull('user_id')
                ->where('status', 'online');
            })
            ->orWhere(function ($q) use ($autoSelectedEspId) {
                if ($autoSelectedEspId) {
                    $q->where('esp_id', $autoSelectedEspId)
                    ->where('status', 'in_use');
                }
            })
            ->orderBy('name')
            ->get();

        return view('auth.login', compact(
            'availableDevices',
            'autoSelectedEspId'
        ));
    }

    public function store(Request $request)
    {
        // =========================
        // 1. VALIDASI
        // =========================
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'esp_id'   => 'nullable|string|exists:devices,esp_id',
        ]);

        // =========================
        // 2. AUTH USER
        // =========================
        if (!Auth::attempt(
            $request->only('username', 'password'),
            $request->boolean('remember')
        )) {
            return back()->withErrors([
                'password' => 'Username atau password salah!'
            ])->withInput();
        }

        $user = Auth::user();

        // =========================
        // 3. AMBIL DEVICE
        // PRIORITAS:
        // - device milik user
        // - device dari dropdown (jika bebas)
        // =========================
        $device = Device::where('user_id', $user->id)
            ->where('status', 'in_use')
            ->first();

        if (!$device && $request->filled('esp_id')) {
            $device = Device::where('esp_id', $request->esp_id)
                ->where(function ($q) use ($user) {
                    $q->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
                })
                ->first();
        }

        if (!$device) {
            Auth::logout();
            return back()->withErrors([
                'esp_id' => 'Silakan pilih timbangan.'
            ])->withInput();
        }

        // =========================
        // 4. TIMEOUT RESET
        // =========================
        if (
            $device->status === 'in_use' &&
            $device->last_seen_at &&
            $device->last_seen_at->lt(now()->subMinutes(5))
        ) {
            $device->update([
                'user_id' => null,
                'status'  => 'online',
            ]);
        }

        // =========================
        // 5. VALIDASI KEPEMILIKAN
        // =========================
        if (
            $device->status === 'in_use' &&
            $device->user_id !== null &&
            $device->user_id !== $user->id
        ) {
            Auth::logout();
            return back()->withErrors([
                'esp_id' => 'Timbangan ini sedang digunakan user lain.'
            ]);
        }

        // =========================
        // 6. KUNCI DEVICE
        // =========================
        $device->update([
            'user_id'        => $user->id,
            'status'         => 'in_use',
            'last_online_at' => now(),
            'last_seen_at'   => now(),
        ]);

        // =========================
        // 7. SESSION
        // =========================
        session([
            'selected_device' => $device->id,
            'selected_esp_id' => $device->esp_id,
            'device_api_key'  => $device->api_key,
        ]);

        $request->session()->regenerate();

        // =========================
        // 8. REDIRECT
        // =========================
        if ($user->role === 'admin') {
            return redirect()->route('admin.view');
        }

        if ($user->role === 'user') {
            if (preg_match('/Timbangan-([OP])\d+-/', $device->esp_id, $m)) {
                return $m[1] === 'O'
                    ? redirect()->route('order.view')
                    : redirect()->route('package.view');
            }
        }

        Auth::logout();
        return redirect()->route('login')
            ->with('error', 'Role atau device tidak dikenali.');
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
