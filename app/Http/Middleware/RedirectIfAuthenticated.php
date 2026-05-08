<?php

namespace App\Http\Middleware;

use App\Models\Update\Device;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::user();

                if ($user->role === 'admin') {
                    return redirect()->route('admin.view');
                }

                $device = Device::where('user_id', $user->id)
                    ->where('status', 'in_use')
                    ->first();

                if ($device && preg_match('/Timbangan-([OP])\d+/', $device->esp_id, $m)) {
                    return $m[1] === 'O'
                        ? redirect()->route('order.view')
                        : redirect()->route('package.view');
                }

                // Fallback jika device tidak ditemukan
                return redirect()->route('order.view');
            }
        }

        return $next($request);
    }
}