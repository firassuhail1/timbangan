<?php

namespace App\Http\Middleware;

use App\Models\Update\Device;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class Role
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        // Log::debug('Roles received in middleware: ', [$roles]);

        $allowedRoles = is_array($roles) ? $roles : explode(',', $roles);
        $allowedRoles = array_map('trim', $allowedRoles);

        if (!Auth::check()) {
            return redirect()->route('login.view')->with('error', 'Silakan login terlebih dahulu.');
        }

        // 🔥 Tambahan: sinkronisasi device aktif
        $device = Device::where('user_id', Auth::id())
            ->where('status', 'in_use')
            ->first();

        if ($device) {
            session([
                'selected_esp_id' => $device->esp_id,
                'selected_device_name' => $device->name,
            ]);
        }

        $userRole = Auth::user()->role;

        if ($userRole === 'admin') {
            return $next($request);
        }

        foreach ($allowedRoles as $role) {
            if ($userRole === $role) {
                return $next($request);
            }
        }

        return redirect('/')->with('error', 'Akses dilarang. Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}
