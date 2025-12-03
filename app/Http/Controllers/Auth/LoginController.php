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
        $availableDevices = Device::where(function ($query) {
                $query->where('status', 'online')
                    ->orWhere(function ($q) {
                        $q->where('status', 'in_use')
                            ->where('user_id', Auth::id() ?? 0); // device milik user tetap muncul
                    });
            })
            ->orderBy('name')
            ->get();

        // Ambil cookie yang benar
        $lastUsedEspId = Cookie::get('last_esp_id');

        // $token = PersonalAccessToken::all();

        // dd($token);

        return view('auth.login', compact('availableDevices', 'lastUsedEspId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'esp_id'   => 'required|string|exists:devices,esp_id',
        ]);

        // Auth check
        if (!Auth::attempt($request->only('username', 'password'), $request->has('remember'))) {
            return back()->withErrors(['password' => 'Username atau password salah!'])->withInput();
        }

        // Ambil device ESP yang dipilih, pastikan tidak sedang dipakai
        $device = Device::where('esp_id', $request->esp_id)
                        ->where('status', '!=', 'in_use')
                        ->first();

        if (!$device) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['esp_id' => 'Device sedang digunakan atau tidak tersedia.'])->withInput();
        }

        // Lock device untuk user login
        $device->update([
            'user_id'        => Auth::id(),
            'status'         => 'in_use',
            'last_online_at' => now(),
        ]);

        // Token per device
        $tokenName = "device_token_{$device->esp_id}";
        $token = $request->user()->tokens()->where('name', $tokenName)->first();
        if (!$token) {
            $token = $request->user()->createToken($tokenName, ['device:use']);
        }

        // Generate API key jika belum ada
        if (!$device->api_key) {
            $device->api_key = bin2hex(random_bytes(32));
            $device->api_key_generated_at = now();
            $device->save();
        }

        // Simpan session minimal untuk UI, tapi bukan untuk logika menu
        session([
            'selected_device' => $device->id,
            'selected_esp_id' => $device->esp_id,
            'device_api_key'  => $device->api_key,
        ]);

        // Remember cookie
        if ($request->has('remember')) {
            Cookie::queue('username', $request->username, 60 * 24 * 30);
            Cookie::queue('last_esp_id', $device->esp_id, 60 * 24 * 30);
        } else {
            Cookie::queue(Cookie::forget('username'));
            Cookie::queue(Cookie::forget('last_esp_id'));
        }

        $request->session()->regenerate();

        // Ambil tipe ESP dari esp_id untuk redirect
        $deviceType = null;
        if (preg_match('/Timbangan-([OP])\d+-/', $device->esp_id, $matches)) {
            $deviceType = $matches[1];
        }

        // Redirect berdasarkan role dan tipe ESP
        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.view');
        }

        if (Auth::user()->role === 'user') {
            if ($deviceType === 'O') {
                return redirect()->route('order.view');
            } elseif ($deviceType === 'P') {
                return redirect()->route('package.view');
            }
        }

        return redirect()->route('login')->with('error', 'Role atau ESP tidak dikenali');
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'username' => 'required|string',
    //         'password' => 'required|string',
    //         'esp_id'   => 'required|string|exists:devices,esp_id',
    //     ]);

    //     // Langsung gunakan Auth::attempt (ini yang benar)
    //     if (!Auth::attempt($request->only('username', 'password'), $request->has('remember'))) {
    //         return back()->withErrors(['password' => 'Username atau password salah!'])->withInput();
    //     }

    //     // Setelah berhasil login, baru cek device
    //     $device = Device::where('esp_id', $request->esp_id)
    //                     ->where('status', '!=', 'in_use')
    //                     ->first();

    //     if (!$device) {
    //         Auth::logout();
    //         $request->session()->invalidate();
    //         $request->session()->regenerateToken();
    //         return back()->withErrors(['esp_id' => 'Device sedang digunakan atau tidak tersedia.'])->withInput();
    //     }

    //     // Lock device dengan user yang sudah login
    //     $device->update([
    //         'user_id'        => Auth::id(),
    //         'status'         => 'in_use',
    //         'last_online_at' => now(),
    //     ]);

    //     // Token per device
    //     $tokenName = "device_token_{$device->esp_id}";
    //     $token = $request->user()->tokens()->where('name', $tokenName)->first();
    //     if (!$token) {
    //         $token = $request->user()->createToken($tokenName, ['device:use']);
    //     }

    //     // Generate API key jika belum ada
    //     if (!$device->api_key) {
    //         $device->api_key = bin2hex(random_bytes(32));
    //         $device->api_key_generated_at = now();
    //         $device->save();
    //     }

    //     // Setelah generate api_key
    //     session()->put('device_api_key', $device->api_key);   // INI YANG PENTING!
    //     session()->put('current_esp_id', $device->esp_id);

    //     // Simpan ke session
    //     session([
    //         'device_token'     => $token->plainTextToken,
    //         'selected_device'  => $device->id,
    //         'selected_esp_id'  => $device->esp_id,
    //         'device_api_key'   => $device->api_key,   // WAJIB ADA
    //         'current_esp_id'   => $device->esp_id,
    //     ]);

    //     // Remember cookie
    //     if ($request->has('remember')) {
    //         Cookie::queue('username', $request->username, 60 * 24 * 30);
    //         Cookie::queue('last_esp_id', $device->esp_id, 60 * 24 * 30);
    //     } else {
    //         Cookie::queue(Cookie::forget('username'));
    //         Cookie::queue(Cookie::forget('last_esp_id'));
    //     }

    //     $request->session()->regenerate();

    //     // Sekarang Auth::user() pasti ada dan benar
    //     return match (Auth::user()->role) {
    //         'admin' => redirect()->route('admin.view'),
    //         'user'  => redirect()->route('order.view'),
    //         default => redirect()->route('login')->with('error', 'Role tidak dikenali'),
    //     };
    // }

    
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
