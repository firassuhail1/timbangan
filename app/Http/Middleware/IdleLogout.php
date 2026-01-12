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
        if (!Auth::check()) {
            return $next($request);
        }

        $lastActivity = session('last_activity');
        $maxIdle = config('session.lifetime') * 60;
        $now = time();

        if ($lastActivity && ($now - $lastActivity) > $maxIdle) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect()->route('login')
                ->with('message', 'Session anda habis.');
        }

        session(['last_activity' => $now]);

        return $next($request);
    }

}
