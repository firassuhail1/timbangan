<?php

namespace App\Http\Middleware;

use App\Models\Update\Device;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdleLogout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        // Jika user belum login, lanjut
        if (!Auth::check()) {
            return $next($request);
        }

        // Ambil waktu last activity dari session
        $lastActivity = session('last_activity');
        $maxIdle = config('session.lifetime') * 60; // detik
        $now = now()->timestamp;

        if ($lastActivity && ($now - $lastActivity) > $maxIdle) {
            // Reset semua device user di JSON all_devices
            $devices = Device::all();

            foreach ($devices as $device) {
                $all = json_decode($device->all_devices, true);
                $updated = false;

                foreach ($all as &$d) {
                    if (isset($d['user_id'], $d['status']) &&
                        $d['user_id'] === Auth::id() &&
                        $d['status'] === 'in_use') {
                        $d['status'] = 'online';
                        $d['user_id'] = null;
                        $updated = true;
                    }
                }

                if ($updated) {
                    $device->all_devices = json_encode($all);
                    $device->save();
                }
            }

            // Logout user
            Auth::logout();
            session()->flush();

            return redirect()->route('login')
                ->with('message', 'Session anda habis.');
        }

        // Update last activity
        session(['last_activity' => $now]);

        return $next($request);
    }
}
