<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string[]  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (!in_array($request->user()->role, $roles)) {
            if ($request->user()->role === 'santri') {
                return redirect()->route('santri.dashboard')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
            }
            if (in_array($request->user()->role, ['admin', 'ustadz'])) {
                return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
            }
            Auth::logout();
            return redirect()->route('login')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        }

        return $next($request);
    }
}
